<?php

class DockerManager
{
    private $runtimePath;
    private $mapFile;
    private $lockDir;

    public function __construct($runtimePath)
    {
        $this->runtimePath = $runtimePath;
        $this->lockDir = $runtimePath . DIRECTORY_SEPARATOR . 'locks';
        
        // Suppress warnings and try to create
        if (!is_dir($this->runtimePath)) {
            @mkdir($this->runtimePath, 0777, true);
        }
        if (!is_dir($this->lockDir)) {
            @mkdir($this->lockDir, 0777, true);
        }
        
        $this->mapFile = $this->runtimePath . DIRECTORY_SEPARATOR . 'docker_map.json';
    }

    /**
     * Get container ID for chat ID
     */
    public function getContainerId($chatId)
    {
        // Use deterministic container name based on Chat ID to ensure persistence
        // Remove special characters to be safe for Docker names
        $safeId = preg_replace('/[^a-zA-Z0-9]/', '', $chatId);
        // Limit length if necessary, but docker names can be long
        return 'aihelp_u_' . $safeId;
    }

    /**
     * Start a new container for chat ID
     */
    public function startContainer($chatId)
    {
        $containerId = $this->getContainerId($chatId);

        // Check container status
        $state = trim(shell_exec("docker inspect -f '{{.State.Status}}' $containerId 2>/dev/null"));

        if ($state === 'running') {
            // Hot-fix: Ensure bash/curl/wget are installed in running containers
            $checkBash = shell_exec("docker exec $containerId which bash");
            if (empty(trim($checkBash))) {
                 $hotFixCmd = "sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories && " .
                             "apk add --no-cache bash curl wget python3 && " .
                             "ln -sf /usr/bin/python3 /usr/bin/python";
                 shell_exec("docker exec $containerId sh -c " . escapeshellarg($hotFixCmd));
            }
            $this->_installAnycrawl($containerId);
            return $containerId;
        }

        if ($state === 'exited' || $state === 'created') {
            // Container exists but stopped, restart it
            shell_exec("docker start $containerId");
            $this->_installAnycrawl($containerId);
            return $containerId;
        }

        // Command to start container
        // Using node:latest as base image since claude-code is a node package
        // Keep container running with tail -f /dev/null
        // Mount a volume for workspace if needed (e.g., /app)
        
        // Add environment variables for Claude Code
        // Added SHELL and TERM for better environment detection
        $claudeBaseUrl = defined('CLAUDE_API_BASE_URL') ? CLAUDE_API_BASE_URL : 'https://open.bigmodel.cn/api/anthropic';
        $claudeApiKey = defined('CLAUDE_API_KEY') ? CLAUDE_API_KEY : '';
        $envVars = "-e ANTHROPIC_BASE_URL=$claudeBaseUrl -e ANTHROPIC_API_KEY=$claudeApiKey -e SHELL=/bin/bash -e TERM=xterm-256color";

        // Capture stderr to debug failure
        // Use Huawei Cloud mirror for Alpine to avoid timeout issues in China
        $image = "swr.cn-north-4.myhuaweicloud.com/ddn-k8s/docker.io/alpine:latest";
        // REMOVED --rm to ensure persistence
        // Added --name for deterministic addressing
        $cmd = "docker run -d --name $containerId -w /app $envVars $image tail -f /dev/null 2>&1";
        $output = shell_exec($cmd);
        
        // Verify start
        $realId = trim($output);

        // Check if output looks like a container ID (hex string)
        if (!$realId || !preg_match('/^[a-f0-9]{64}$/', $realId)) {
            // Provide helpful hint for permission denied errors
            if (strpos($output, 'permission denied') !== false && strpos($output, 'docker.sock') !== false) {
                throw new Exception("Docker Permission Denied. Please run this on your server:\n`sudo chmod 666 /var/run/docker.sock`\nor add the web user to the docker group.");
            }
            throw new Exception("Failed to start Docker container. Output: " . $output);
        }

        // Install nodejs and npm first (since we are on alpine)
        // Switch to Aliyun mirror for faster package installation in China
        // Added bash, curl, wget, and python symlink for better tool compatibility
        $setupCmd = "sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories && " .
                    "apk add --no-cache nodejs npm git python3 bash curl wget && " .
                    "ln -sf /usr/bin/python3 /usr/bin/python && " .
                    "npm config set registry https://registry.npmmirror.com";
        shell_exec("docker exec $containerId sh -c " . escapeshellarg($setupCmd));

        // Install claude-code inside container (if not present in image)
        // This effectively makes 'claude' command available
        $npmInstall = shell_exec("docker exec $containerId sh -c 'command -v claude >/dev/null 2>&1 || npm install -g @anthropic-ai/claude-code' 2>&1");
        
        // Locate claude binary
        // On alpine it might be in /usr/bin or /usr/local/bin or just npm bin location
        $claudePath = trim(shell_exec("docker exec $containerId which claude"));
        
        if (!$claudePath) {
             // Try to find it manually
             $claudePath = trim(shell_exec("docker exec $containerId find / -name claude -type f -o -type l 2>/dev/null | head -n 1"));
        }
        
        if (!$claudePath) {
             // Attempt to locate via npm bin -g
             $npmBin = trim(shell_exec("docker exec $containerId npm bin -g"));
             if ($npmBin) {
                 $claudePath = $npmBin . '/claude';
             }
        }

        if (!$claudePath) {
             // Final check: assume installation failed
             throw new Exception("Failed to locate 'claude' binary after installation. NPM Output: " . $npmInstall);
        }

        // Create a non-root user 'claude' and grant permissions
        $userSetupCmd = "adduser -D -s /bin/bash claude && " .
                        "mkdir -p /app && " .
                        "chown -R claude:claude /app && " .
                        "chown -R claude:claude /usr/local/lib/node_modules && " . // Ensure global modules are writable/readable
                        "chown -R claude:claude /usr/local/bin"; // Allow writing wrappers
        
        shell_exec("docker exec $containerId sh -c " . escapeshellarg($userSetupCmd));

        // Create a wrapper script to enforce --dangerously-skip-permissions
        // We move the original binary and replace it with a shell script
        // Note: Using dirname to ensure we write to the same directory
        $dir = dirname($claudePath);
        $original = $claudePath . '-original';
        
        $wrapperCmd = "if [ -f $claudePath ] && [ ! -f $original ]; then " .
                      "mv $claudePath $original && " .
                      "printf '#!/bin/sh\\nexec $original --dangerously-skip-permissions \"$@\"\\n' > $claudePath && " .
                      "chmod +x $claudePath; fi";
        
        shell_exec("docker exec $containerId sh -c " . escapeshellarg($wrapperCmd));

        $this->_installAnycrawl($containerId);

        // Map is no longer strictly needed for ID lookup but we keep it for metadata if needed
        $this->saveMap($chatId, $containerId);
        return $containerId;
    }

    /**
     * Execute command with locking and streaming
     */
    public function execCommandStream($chatId, $command, $callback)
    {
        $lockFile = $this->lockDir . DIRECTORY_SEPARATOR . $chatId . '.lock';
        $fp = fopen($lockFile, 'w+');

        if ($callback) $callback("🔒 [DockerManager] Acquiring lock for chat ID: $chatId...\n");
        // Blocking lock
        if (!flock($fp, LOCK_EX)) {
            throw new Exception("Could not acquire lock for chat ID: $chatId");
        }
        if ($callback) $callback("🔓 [DockerManager] Lock acquired.\n");

        try {
            // Always call startContainer to ensure environment checks/hot-fixes are run
            if ($callback) $callback("🔍 [DockerManager] Checking container status...\n");
            $containerId = $this->startContainer($chatId);
            if ($callback) $callback("✅ [DockerManager] Container is ready: $containerId\n");
        } finally {
            if ($callback) $callback("🔓 [DockerManager] Releasing lock early for concurrent execution...\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        try {
            
            // Create a marker file to track changes
            $markerFile = '/tmp/cmd_marker_' . uniqid();
            if ($callback) $callback("📍 [DockerManager] Creating marker file: $markerFile\n");
            shell_exec("docker exec $containerId touch $markerFile");
            
            // Prepare command
            // 2>&1 to capture stderr
            // Execute as 'claude' user to satisfy security checks
            // We use 'su claude -c' because standard 'docker exec -u' might need user ID, but this is simpler
            // Important: Set working directory to /app explicitly and use -p flag for claude if it's a claude command
            
            $finalCommand = $command;
            if (strpos($command, 'claude') === 0) {
                 // Inject the progress reporting instruction
                 $progressInstruction = " (Please report what you just did to the console every 5 seconds so I can see your progress)";
                 
                 // Try to inject inside existing quotes if present
                 if (preg_match('/^(claude(?:\s+ask)?\s+["\'])(.*)(["\'])(.*)$/i', $command, $matches)) {
                     $finalCommand = $matches[1] . $matches[2] . $progressInstruction . $matches[3] . $matches[4];
                 } else {
                     // Otherwise append it to the command, Claude will treat it as part of the prompt
                     // We check if it's just 'claude' or 'claude ask' and wrap it properly if needed
                     if (preg_match('/^claude(\s+ask)?$/i', trim($command))) {
                         // No prompt provided, maybe just starting interactive? 
                         // But we are in stream mode, so we usually have a prompt.
                         $finalCommand = $command; 
                     } else {
                         // Append it. If there are flags like -p, this might put it after flags, 
                         // but Claude CLI usually handles that fine or we can try to be smarter.
                         // For simplicity, let's just append it.
                         $finalCommand = $command;
                     }
                 }

                 // Final check to ensure -p is present for streaming output
                 if (strpos($finalCommand, ' -p ') === false && strpos($finalCommand, '--print') === false) {
                     $finalCommand .= " -p";
                 }
            }

            if ($callback) $callback("🚀 [DockerManager] Executing: $finalCommand\n");

            // Use explicit /bin/bash to avoid PATH issues
            $dockerCmd = "docker exec -i -u claude -w /app $containerId /bin/bash -c " . escapeshellarg($finalCommand . " 2>&1");
            
            $descriptors = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];

            if ($callback) $callback("🛠️ [DockerManager] Starting process...\n");
            $process = proc_open($dockerCmd, $descriptors, $pipes);

            if (is_resource($process)) {
                if ($callback) $callback("🟢 [DockerManager] Process started.\n");
                fclose($pipes[0]); // Close stdin
                
                // Set non-blocking for heartbeat
                stream_set_blocking($pipes[1], 0);
                
                $buffer = '';
                $lastOutputTime = time();
                $isClaude = (strpos($command, 'claude') === 0);

                while (true) {
                    $chunk = fread($pipes[1], 4096);
                    
                    if ($chunk !== false && $chunk !== '') {
                        $buffer .= $chunk;
                        // Process lines from buffer
                        while (($pos = strpos($buffer, "\n")) !== false) {
                            $line = substr($buffer, 0, $pos + 1);
                            $buffer = substr($buffer, $pos + 1);
                            
                            // Strip ANSI codes
                            $cleanLine = preg_replace('/\x1b\[[0-9;]*m/', '', $line);
                            // Callback for SSE
                            $callback($cleanLine);
                        }
                        $lastOutputTime = time();
                    } else {
                        // No data available
                        if (feof($pipes[1])) {
                            break;
                        }
                        
                        $status = proc_get_status($process);
                        if (!$status['running']) {
                            // Process finished, ensure we read everything
                            while (($rest = fread($pipes[1], 4096)) !== false && $rest !== '') {
                                $buffer .= $rest;
                            }
                            break;
                        }

                        // Heartbeat: If claude command and no output for > 1s, print dot
                        if ($isClaude && (time() - $lastOutputTime >= 1)) {
                             $callback(".");
                             $lastOutputTime = time();
                        }
                        
                        usleep(100000); // 100ms
                    }
                }

                // Flush remaining buffer
                if (!empty($buffer)) {
                    $cleanLine = preg_replace('/\x1b\[[0-9;]*m/', '', $buffer);
                    $callback($cleanLine);
                }

                fclose($pipes[1]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);
                if ($callback) $callback("\n🏁 [DockerManager] Process execution finished with exit code: $exitCode\n");
                
                // Check for new files generated during execution
                // Exclude common directories like node_modules, .git, vendor to avoid massive syncs
                // Use sh -c to handle redirection properly inside the container
                if ($callback) $callback("📂 [DockerManager] Checking for generated files...\n");
                $findCmd = "docker exec $containerId sh -c \"find /app -type f -newer $markerFile -not -path '*/node_modules/*' -not -path '*/.git/*' -not -path '*/vendor/*' 2>/dev/null\"";
                $newFilesOutput = shell_exec($findCmd);
                
                if ($newFilesOutput) {
                    $files = array_filter(explode("\n", str_replace("\r\n", "\n", $newFilesOutput)));
                    foreach ($files as $file) {
                        $file = trim($file);
                        if (empty($file)) continue;
                        
                        try {
                            if ($callback) $callback("\n📦 [DockerManager] Syncing generated file: $file...\n");
                            $url = $this->copyFileFromContainer($chatId, $file);
                            if ($callback) {
                                $callback("✅ [DockerManager] File uploaded: $url\n");
                                // Send a Markdown link for frontend display
                                $callback("\n[FILE_PREVIEW]($url)\n");
                            }
                        } catch (Exception $e) {
                            // Only report error if it's not a trivial file or if debugging
                            if ($callback) $callback("⚠️ [DockerManager] Failed to sync $file: " . $e->getMessage() . "\n");
                        }
                    }
                } else {
                    if ($callback) $callback("ℹ️ [DockerManager] No new files generated.\n");
                }
                
                // Cleanup marker
                if ($callback) $callback("🧹 [DockerManager] Cleaning up marker file...\n");
                shell_exec("docker exec $containerId rm -f $markerFile");
            } else {
                if ($callback) $callback("🔴 [DockerManager] Failed to start process.\n");
            }
        } catch (Exception $e) {
            if ($callback) $callback("⚠️ [DockerManager] Error: " . $e->getMessage() . "\n");
            throw $e;
        }

        if ($callback) $callback("👋 [DockerManager] Done.\n");
    }

    /**
     * Stop container
     */
    public function stopContainer($chatId)
    {
        $containerId = $this->getContainerId($chatId);
        if ($containerId) {
            shell_exec("docker stop $containerId");
            // Do NOT remove from map to preserve identity
        }
    }

    /**
     * Copy file from container to host and upload
     * Returns the public URL of the uploaded file
     */
    public function copyFileFromContainer($chatId, $containerPath, $hostPath = null)
    {
        // Ensure container is running before copy
        $containerId = $this->startContainer($chatId);
        
        // Create a temporary file path
        $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . basename($containerPath) . '_' . uniqid();
        
        // Copy file from container to temp file
        $cmd = "docker cp $containerId:" . escapeshellarg($containerPath) . " " . escapeshellarg($tempFile);
        shell_exec($cmd);
        
        if (!file_exists($tempFile)) {
            throw new Exception("Failed to copy file from container: $containerPath");
        }
        
        // Upload the file via uloaddocker.php
        $url = 'http://127.0.0.1:4556/uloaddocker.php';
        $ch = curl_init();
        
        // Use CURLFile for file upload
        $cfile = new CURLFile($tempFile, 'application/octet-stream', basename($containerPath));
        $data = [
            'file' => $cfile
        ];
        
        if ($hostPath) {
            $data['path'] = $hostPath;
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            @unlink($tempFile);
            throw new Exception("Failed to upload file: $error");
        }
        
        curl_close($ch);
        @unlink($tempFile); // Clean up temp file
        
        if ($httpCode !== 200) {
            throw new Exception("Upload failed with HTTP code $httpCode: $response");
        }
        
        $result = json_decode($response, true);
        if (!$result || !isset($result['success']) || !$result['success']) {
            $msg = isset($result['error']) ? $result['error'] : 'Unknown error';
            throw new Exception("Upload failed: $msg");
        }
        
        // Return the public URL
        // Assuming the upload goes to the default uploads directory which is mapped to https://icon144.yjllq.com/uploads/
        return "https://icon144.yjllq.com/uploads/" . basename($containerPath);
    }

    /**
     * Check if container is running
     */
    private function isContainerRunning($containerId)
    {
        $output = shell_exec("docker inspect -f '{{.State.Running}}' $containerId");
        return trim($output) === 'true';
    }

    private function loadMap()
    {
        if (file_exists($this->mapFile)) {
            return json_decode(file_get_contents($this->mapFile), true) ?: [];
        }
        return [];
    }

    private function saveMap($chatId, $containerId)
    {
        $map = $this->loadMap();
        $map[$chatId] = [
            'container_id' => $containerId,
            'created_at' => time()
        ];
        file_put_contents($this->mapFile, json_encode($map));
    }

    private function removeMap($chatId)
    {
        $map = $this->loadMap();
        if (isset($map[$chatId])) {
            unset($map[$chatId]);
            file_put_contents($this->mapFile, json_encode($map));
        }
    }

    private function _installAnycrawl($containerId)
    {
         // Check if anycrawl-mcp is installed globally
         $checkNpm = trim(shell_exec("docker exec $containerId npm list -g anycrawl-mcp --depth=0 2>/dev/null | grep anycrawl-mcp"));
         if (empty($checkNpm)) {
             shell_exec("docker exec $containerId npm install -g anycrawl-mcp");
         }
 
         // Configure anycrawl-mcp for the claude user
         $markerSetup = "/home/claude/.anycrawl_setup_done";
         $checkMarker = trim(shell_exec("docker exec $containerId sh -c '[ -f $markerSetup ] && echo 1 || echo 0'"));
         
         if ($checkMarker !== '1') {
             // Run as claude user to update claude config
            $anycrawlApiKey = ANYCRAWL_API_KEY;
            $anycrawlCmd = "claude mcp add anycrawl -e ANYCRAWL_API_KEY=$anycrawlApiKey -- npx -y anycrawl-mcp";
             shell_exec("docker exec -u claude $containerId sh -c " . escapeshellarg($anycrawlCmd));
             shell_exec("docker exec -u claude $containerId touch $markerSetup");
         }
    }
}
