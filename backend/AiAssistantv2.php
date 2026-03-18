<?php

/**
 * AiAssistant Standalone Version
 * No dependencies on ThinkPHP or other frameworks.
 * Compatible with PHP 7.2+
 */

require_once __DIR__ . '/DockerManager.php';
require_once __DIR__ . '/ProviderTransformer.php';
require_once __DIR__ . '/config.php';

class AiAssistant
{
    /**
     * Dispatch request based on action
     */
    public function dispatch()
    {
        $action = isset($_GET['action']) ? $_GET['action'] : 'chat';
        if ($action === 'update_memory') {
            $this->updateMemory();
        } elseif ($action === 'debug_claude') {
            $this->debugClaude();
        } elseif ($action === 'markitdown') {
            $this->proxyMarkitdown();
        } elseif ($action === 'webscrape') {
            $this->proxyWebscrape();
        } elseif ($action === 'speed_test') {
            $this->speedTest();
        } else {
            $this->chat();
        }
    }

    /**
     * Debugging endpoint for direct Claude Code execution
     */
    public function debugClaude()
    {
        // 0. CORS Headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }

        // Disable time limit
        set_time_limit(0);

        // 1. Parse Input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        // Support GET params for quick browser testing too
        if (!$input) {
            $input = $_GET;
        }

        $command = isset($input['command']) ? $input['command'] : 'ls -la';
        $chatId = isset($input['chat_id']) ? $input['chat_id'] : 'debug_session';

        // 2. Set SSE Headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); 
        
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_implicit_flush(1);

        $this->sendSseEvent('text', "🔧 **Debug Mode: Executing Command**\n`$command`\n\n");

        // 3. Execute Command via DockerManager
        // Use a safer runtime path determination
        // Try local runtime dir first, then system temp
        $localRuntime = __DIR__ . '/runtime';
        $runtimePath = $localRuntime;
        
        if (!is_dir($localRuntime)) {
            // Attempt to create local runtime
            if (!@mkdir($localRuntime, 0777, true)) {
                // Fallback to system temp if local is not writable
                $runtimePath = sys_get_temp_dir() . '/aihelp_runtime';
            }
        } elseif (!is_writable($localRuntime)) {
             $runtimePath = sys_get_temp_dir() . '/aihelp_runtime';
        }

        $docker = new DockerManager($runtimePath);

        try {
            // Track files before execution
            // We can't easily do this in docker exec without overhead, so we'll check for *new* files based on timestamp or just known outputs
            // For now, let's just assume we want to sync the whole /app folder or specific files if we knew them.
            // But user asked to add copyFileFromContainer.
            // Let's implement a mechanism to detect newly created files in /app.
            
            // 1. Get list of files before
            // $beforeFiles = $docker->execCommandAndReturn("ls -1 /app"); 
            // (We need a helper for non-streaming output first, but let's keep it simple as requested)

            $docker->execCommandStream($chatId, $command, function($line) {
                $this->sendSseEvent('text', $line);
            });
            
            // Stop container after debug execution
            $docker->stopContainer($chatId);
            
            // 2. After execution, try to copy relevant files
            // For this debug mode, we'll try to copy everything in /app that isn't a hidden file or node_modules
            // Actually, copying everything might be too much.
            // Let's look for files mentioned in the command? Or just copy specific common types?
            // Or maybe just copy the whole /app folder to a download area?
            
            // Let's implement a "sync workspace" approach.
            $downloadsDir = __DIR__ . '/downloads/' . $chatId;
            if (!is_dir($downloadsDir)) {
                @mkdir($downloadsDir, 0777, true);
            }
            
            // Copy /app from container to host downloads
            $docker->copyFileFromContainer($chatId, "/app/.", $downloadsDir);
            
            // List the files we just copied to give user links
            $files = scandir($downloadsDir);
            $links = [];
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            $scriptDir = str_replace('\\', '/', $scriptDir);
            $scriptDir = rtrim($scriptDir, '/');
            
            foreach ($files as $f) {
                if ($f === '.' || $f === '..' || $f === 'node_modules') continue;
                // Ignore hidden files
                if (strpos($f, '.') === 0) continue;
                
                $url = "$protocol://$host$scriptDir/downloads/$chatId/$f";
                $links[] = "[$f]($url)";
            }
            
            if (!empty($links)) {
                $this->sendSseEvent('text', "\n\n📂 **Workspace Files:**\n" . implode(" | ", $links) . "\n");
            }

            $this->sendSseEvent('text', "\n✅ **Execution Completed**\n");
        } catch (Exception $e) {
            $this->sendSseEvent('text', "\n❌ **Error:** " . $e->getMessage() . "\n");
        }

        echo "data: [DONE]\n\n";
        $this->flush();
        exit();
    }

    /**
     * Update Soul, User, Memory using AI
     */
    public function updateMemory()
    {
        // 0. CORS Headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }

        // 1. Parse Input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        // Required Inputs
        $soul = isset($input['soul']) ? $input['soul'] : [];
        $user = isset($input['user']) ? $input['user'] : [];
        $memory = isset($input['memory']) ? $input['memory'] : [];
        $history = isset($input['history']) ? $input['history'] : [];
        $locationHost = isset($input['location_host']) ? $input['location_host'] : '';
        
        // AI Config
        $endpoint = isset($input['endpoint']) ? $input['endpoint'] : '';
        $apiKey = isset($input['api_key']) ? $input['api_key'] : '';
        $model = isset($input['model']) ? $input['model'] : '';
        $chatId = isset($input['chat_id']) ? $input['chat_id'] : 'default_session';

        // Apply default interface transformation
        applyDefaultConfig($endpoint, $apiKey, $model, false, $locationHost);
        applyFastConfig($endpoint, $apiKey, $model, $locationHost);

        if (!$endpoint || !$apiKey || !$model) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing AI configuration (endpoint, api_key, model)']);
            exit;
        }

        // 2. Construct Prompt for Memory Update
        $soulStr = json_encode($soul, JSON_UNESCAPED_UNICODE);
        $userStr = json_encode($user, JSON_UNESCAPED_UNICODE);
        $memoryStr = json_encode($memory, JSON_UNESCAPED_UNICODE);
        
        // Take only last 20 messages to avoid context overflow
        $recentHistory = array_slice($history, -20);
        $historyText = "";
        foreach ($recentHistory as $msg) {
            $role = isset($msg['role']) ? $msg['role'] : 'unknown';
            $content = isset($msg['content']) ? $msg['content'] : '';
            $historyText .= "[$role]: " . mb_substr($content, 0, 500) . "\n";
        }

        $systemPrompt = "You are the Memory Manager for an advanced AI system.
Your goal is to maintain and evolve the long-term memory, user profile, and AI personality (Soul) based on the conversation history.

Current State:
[Soul (AI Personality)]: $soulStr
[User Profile]: $userStr
[Long-term Memory]: $memoryStr

Task:
Analyze the recent conversation below. Update the state JSON objects if necessary:
1. Soul: Evolve personality traits based on interaction style.
2. User: Add/Update user preferences, facts, name, style.
3. Memory: Extract key facts, summaries, or important events to remember. Append new memories to the list or consolidate existing ones.

Recent Conversation:
$historyText

Output Format:
Return ONLY a valid JSON object with the following structure (no markdown, no explanations):
{
  \"soul\": { ...updated soul object... },
  \"user\": { ...updated user object... },
  \"memory\": { ...updated memory object... }
}";

        // 3. Call AI (Non-streaming)
        $messages = [
            ['role' => 'user', 'content' => $systemPrompt]
        ];

        $response = $this->callLLM($endpoint, $apiKey, $model, $messages);
        
        // 4. Return Result
        header('Content-Type: application/json');
        
        // Try to parse JSON from response (handle potential markdown blocks)
        $cleanResponse = $this->extractJson($response);
        
        if ($cleanResponse) {
            $responseData = json_decode($cleanResponse, true);
            if (is_array($responseData)) {
                $responseData['model'] = $model;
                echo json_encode($responseData);
            } else {
                echo $cleanResponse;
            }
        } else {
            // Fallback: return original state if parsing fails
            echo json_encode([
                'soul' => $soul,
                'user' => $user,
                'memory' => $memory,
                'error' => 'Failed to parse AI response',
                'raw_response' => $response,
                'model' => $model
            ]);
        }
        exit;
    }

    /**
     * Helper to call LLM (Non-streaming)
     */
    private function callLLM($endpoint, $apiKey, $model, $messages)
    {
        // Extract prompt from messages since getTextResponse expects a prompt string and history
        $prompt = '';
        $history = [];
        
        if (!empty($messages)) {
            $lastMsg = array_pop($messages);
            if ($lastMsg['role'] === 'user') {
                $prompt = $lastMsg['content'];
                $history = $messages;
            } else {
                // Fallback if last message is not user
                $messages[] = $lastMsg;
                // Just use empty prompt and let history carry it? 
                // getTextResponse appends prompt to history.
                // If prompt is empty, it might be weird.
                // Let's assume the caller structured it correctly.
            }
        }

        return $this->getTextResponse($prompt, $history, $endpoint, $apiKey, $model, '', [
            'skip_config' => true, // callLLM usually gets configured endpoint/key
            'temperature' => 0.5,
            'response_format' => ['type' => 'json_object']
        ]);
    }

    /**
     * Extract JSON from potentially markdown-wrapped string
     */
    private function extractJson($text)
    {
        if (!$text) return null;
        
        // Remove markdown code blocks if present
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }
        if (preg_match('/```\s*(.*?)\s*```/s', $text, $matches)) {
            return $matches[1];
        }
        
        // Basic validation
        $decoded = json_decode($text, true);
        return $decoded ? $text : null;
    }

    /**
     * Proxy MarkItDown API
     */
    public function proxyMarkitdown()
    {
        $this->corsHeaders();
        $this->forwardRequest(MARKITDOWN_API_URL);
    }

    /**
     * Proxy WebScrape API
     */
    public function proxyWebscrape()
    {
        $this->corsHeaders();
        $this->forwardRequest(WEBSCRAPE_API_URL);
    }

    /**
     * Test connection speed to a URL
     */
    public function speedTest()
    {
        $this->corsHeaders();

        // Get URL from GET or POST
        $url = isset($_GET['url']) ? $_GET['url'] : '';
        if (empty($url)) {
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);
            if (isset($input['url'])) {
                $url = $input['url'];
            }
        }

        if (empty($url)) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'error' => 'Missing URL parameter']);
            exit;
        }

        // Add protocol if missing
        if (strpos($url, 'http') !== 0) {
            $url = 'https://' . $url;
        }

   
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // We only care about connection info
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'AiAssistant/2.0 SpeedTest');

        $startTime = microtime(true);
        curl_exec($ch);
        $endTime = microtime(true);
        
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);

        header('Content-Type: application/json');
        
        if ($error) {
            echo json_encode([
                'status' => 'error',
                'url' => $url,
                'error' => $error
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'url' => $url,
                'http_code' => $info['http_code'],
                'total_time' => $info['total_time'], // Seconds
                'namelookup_time' => $info['namelookup_time'],
                'connect_time' => $info['connect_time'],
                'time_taken_ms' => round($info['total_time'] * 1000, 2)
            ]);
        }
        exit;
    }

    private function corsHeaders()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
        header('Access-Control-Allow-Headers: *');
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }
    }

    private function forwardRequest($targetUrl)
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $headers = [];
        
        $requestHeaders = function_exists('getallheaders') ? getallheaders() : [];
        if (empty($requestHeaders)) {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                    $requestHeaders[$headerName] = $value;
                } elseif ($name == "CONTENT_TYPE") {
                    $requestHeaders["Content-Type"] = $value;
                } elseif ($name == "CONTENT_LENGTH") {
                    $requestHeaders["Content-Length"] = $value;
                }
            }
        }

        foreach ($requestHeaders as $name => $value) {
            $lowerName = strtolower($name);
            if (in_array($lowerName, ['host', 'content-length', 'connection', 'accept-encoding'])) {
                continue;
            }
            $headers[] = "$name: $value";
        }

        $ch = curl_init();
        
        // Handle query string
        $queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        $queryString = preg_replace('/(&?)action=[^&]*/', '', $queryString);
        $queryString = ltrim($queryString, '&');
        if (!empty($queryString)) {
            $targetUrl .= (strpos($targetUrl, '?') === false ? '?' : '&') . $queryString;
        }

        curl_setopt($ch, CURLOPT_URL, $targetUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $contentType = isset($_SERVER["CONTENT_TYPE"]) ? $_SERVER["CONTENT_TYPE"] : '';
            if (strpos($contentType, 'multipart/form-data') !== false) {
                $postFields = $_POST;
                foreach ($_FILES as $key => $file) {
                    if (is_array($file['tmp_name'])) {
                        foreach ($file['tmp_name'] as $i => $tmp_name) {
                            if (is_uploaded_file($tmp_name)) {
                                $postFields[$key . "[$i]"] = new CURLFile($tmp_name, $file['type'][$i], $file['name'][$i]);
                            }
                        }
                    } else {
                        if (is_uploaded_file($file['tmp_name'])) {
                            $postFields[$key] = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
                        }
                    }
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
            } else {
                $input = file_get_contents('php://input');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $input);
            }
        }

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            http_response_code(502);
            echo json_encode(['error' => 'Proxy error: ' . curl_error($ch)]);
            curl_close($ch);
            exit();
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        http_response_code($httpCode);
        
        $headerArray = explode("\r\n", $responseHeaders);
        foreach ($headerArray as $header) {
            if (!empty($header) && strpos(strtolower($header), 'transfer-encoding') === false) {
                header($header);
            }
        }

        echo $responseBody;
        exit();
    }

    /**
     * Chat API with SSE Streaming
     */
    public function chat()
    {
        // 0. CORS Headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: *');
        
        // Handle preflight OPTIONS request
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            header('HTTP/1.1 200 OK');
            exit();
        }

        // Disable time limit for long streaming
        set_time_limit(0);
        
        // 1. Receive Parameters
        // Since we are using fetch with JSON body, use php://input
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);

        // 2. Token Authentication
        // Get token from POST payload
        $token = isset($input['token']) ? $input['token'] : '';
        
        // In a real scenario, validate $token against your user/session database
        if (!$token) {
            // For demo purposes, we might allow it or return 401
            // header('HTTP/1.1 401 Unauthorized');
            // echo json_encode(['error' => 'Token required']);
            // exit;
        }

        $prompt = isset($input['prompt']) ? $input['prompt'] : '';
        $history = isset($input['history']) ? $input['history'] : [];
        $thinkingMode = isset($input['thinking_mode']) ? (bool)$input['thinking_mode'] : false;
        $webSearch = isset($input['web_search']) ? (bool)$input['web_search'] : false;
        $imageGeneration = isset($input['image_generation']) ? (bool)$input['image_generation'] : false;
        $imageUrls = isset($input['image_urls']) ? $input['image_urls'] : [];
        $locationHost = isset($input['location_host']) ? $input['location_host'] : '';
        $disableClaudeTool = isset($input['disable_claude_tool']) ? (bool)$input['disable_claude_tool'] : false;
        
        // Proxy Settings
        $endpoint = isset($input['endpoint']) ? $input['endpoint'] : '';
        $apiKey = isset($input['api_key']) ? $input['api_key'] : '';
        $originApiKey = $apiKey;
        $model = isset($input['model']) ? $input['model'] : '';
        $provider = isset($input['provider']) ? $input['provider'] : '';
        $chatId = isset($input['chat_id']) ? $input['chat_id'] : 'default_session';
        $confirmedCommand = isset($input['confirmed_command']) ? $input['confirmed_command'] : '';
        
        // Specialized models from frontend
        $textModel = isset($input['text_model']) ? $input['text_model'] : null;
        $t2iModel = isset($input['t2i_model']) ? $input['t2i_model'] : null;
        $i2iModel = isset($input['i2i_model']) ? $input['i2i_model'] : null;
        $planMode = isset($input['plan_mode']) ? (bool)$input['plan_mode'] : false;

        // Use specialized text model as default if provided
        if ($textModel) {
            $endpoint = $textModel['endpoint'] ?? $endpoint;
            $apiKey = $textModel['api_key'] ?? $apiKey;
            $model = $textModel['model'] ?? $model;
            $provider = $textModel['provider'] ?? $provider;
        }

        $confirmedOutput = "";

        // Apply default interface transformation
        applyDefaultConfig($endpoint, $apiKey, $model, $thinkingMode, $locationHost);

        // 3. Set SSE Headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        // Prevent buffering in Nginx/Apache
        header('X-Accel-Buffering: no'); 
        
        // Ensure immediate output (referencing user's provided code)
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        ob_implicit_flush(1);

        // Handle Confirmed Command Execution
        if ($confirmedCommand) {
             // Defense in depth: Ensure command starts with claude if missing
             $confirmedCommand = trim($confirmedCommand);
             if (stripos($confirmedCommand, 'claude') !== 0) {
                 $confirmedCommand = 'claude "' . str_replace('"', '\"', $confirmedCommand) . '"';
             }
             
             $this->sendSseEvent('think', "\n🚀 **Executing Confirmed Command:** `$confirmedCommand`\n");
             // Determine runtime path
             $localRuntime = __DIR__ . '/runtime';
             $runtimePath = $localRuntime;
             if (!is_dir($localRuntime)) {
                 if (!@mkdir($localRuntime, 0777, true)) {
                     $runtimePath = sys_get_temp_dir() . '/aihelp_runtime';
                 }
             } elseif (!is_writable($localRuntime)) {
                  $runtimePath = sys_get_temp_dir() . '/aihelp_runtime';
             }

             $docker = new DockerManager($runtimePath);
             $outputBuffer = "";
             
             try {
                 $docker->execCommandStream($chatId, $confirmedCommand, function($line) use (&$outputBuffer) {
                     $outputBuffer .= $line;
                     $this->sendSseEvent('think', $line); 
                 });
                 $confirmedOutput = "Command `$confirmedCommand` executed successfully. Output (last 2000 chars):\n" . substr($outputBuffer, -2000);
                 
                 // Stop container after task execution
                 $docker->stopContainer($chatId);
                 
             } catch (Exception $e) {
                 $confirmedOutput = "Error executing command: " . $e->getMessage();
                 $this->sendSseEvent('text', "\n❌ **Error:** " . $e->getMessage() . "\n");
                 // Ensure stopped even on error
                 $docker->stopContainer($chatId);
             }
        }

        // Image Generation Intent Detection (if not explicitly enabled)
        if (!$imageGeneration && $this->detectImageIntent($prompt)) {
             $imageGeneration = true;
             $this->sendSseEvent('think', "🎨 **检测到生图需求，自动切换到图像生成模式...**\n");
        }

        if ($imageGeneration) {
            if( $t2iModel == null&&$i2iModel==null){
                  $this->sendSseEvent('text', "您还没有配置绘画模型\n");
                   exit();
            }
            
             $this->performImageGeneration($prompt, $imageUrls, $locationHost, $t2iModel, $i2iModel);
            
        }

        // 4. Handle Web Search (if enabled)
        $searchContext = "";
        if ($webSearch && $prompt) {
            // Optimization Step: Rewriting the query using AI
            // Only perform if we have history and AI configuration
            $searchQuery = $prompt;
            if (!empty($history) && $endpoint && $apiKey) {
                 $this->sendSseEvent('think', "🤔 **正在思考搜索关键词...**\n");
                 $optimizedPrompt = $this->optimizeSearchQuery($prompt, $history, $endpoint, $apiKey, $model, $locationHost);
                 
                 // If optimization returned something different and valid
                 if ($optimizedPrompt && $optimizedPrompt !== $prompt) {
                     $this->sendSseEvent('think', "✨ **优化后的搜索关键词：** \"$optimizedPrompt\"\n\n");
                     $searchQuery = $optimizedPrompt;
                 }
            }

            $searchContext = $this->performWebSearch($searchQuery);
            if ($searchContext) {
                $prompt = "Search Results:\n" . $searchContext . "\n\nUser Question: " . $prompt;
            }
        }

        // 5. Proxy to AI Endpoint (if configured)
        if ($endpoint && $apiKey && $model) {
            $this->proxyToAi($endpoint, $apiKey,$originApiKey, $locationHost,$model, $history, $prompt, $imageUrls, $imageGeneration, $chatId, $confirmedOutput, $disableClaudeTool, $provider, $planMode);
            exit();
        }

        // 6. Fallback: Simulate Interaction (Mocking the stream)
        
        // A. Simulate "Deep Thinking" process (if enabled)
        if ($thinkingMode) {
            $thoughts = [
                "Analyzing input intent...",
                "Retrieving knowledge base...",
                "Planning response structure...",
                "Verifying constraints...",
                "Optimizing output..."
            ];

            foreach ($thoughts as $step) {
                $this->sendSseEvent('think', "• " . $step . "\n");
                // Simulate processing time
                usleep(rand(300000, 800000)); 
            }
            
            // End thinking
            $this->sendSseEvent('think', "\nThinking complete.\n");
        }

        // B. Simulate "Content Generation" (Typewriter effect)
        // In a real app, you would use curl with CURLOPT_WRITEFUNCTION to stream chunks from OpenAI/Anthropic
        
        $responseTemplate = "Here is a simulated response for: \"{$prompt}\".\n\n" .
                            "This project is now a **Standalone PHP Script**.\n" .
                            "It no longer depends on **ThinkPHP 5**.\n" .
                            "Token received: " . substr($token, 0, 8) . "...";

        // Split into chunks to simulate network stream
        $chars = preg_split('//u', $responseTemplate, -1, PREG_SPLIT_NO_EMPTY);
        
        foreach ($chars as $char) {
            $this->sendSseEvent('text', $char);
            usleep(30000); // 30ms per char
        }

        // 5. End Stream
        echo "data: [DONE]\n\n";
        $this->flush();
        exit();
    }

    /**
     * Proxy request to OpenAI-compatible endpoint with Tool Support
     */
    private function proxyToAi($endpoint, $apiKey,$originApiKey,$locationHost, $model, $history, $prompt, $imageUrls = [], $imageGeneration = false, $chatId = 'default_session', $confirmedOutput = '', $disableClaudeTool = false, $provider = '', $planMode = false)
    {
        // Construct standard OpenAI payload
        $messages = [];
        
        $currentTime = date('Y-m-d H:i:s');
        $planInstructions = "";
        
        if ($planMode) {
             $planInstructions = "\n\n[Plan Mode Activated]
You are currently in Plan Mode. Your goal is to deeply understand the user's request before providing a solution.
Protocol:
1. If this is the start of the conversation, immediately respond with exactly: \"我将按照你的要求问你一些问题，当我觉得计划已足够全面，我会为你回答。\"
2. Ask 1-3 clarifying questions to gather necessary details. Do NOT provide a solution yet.
3. Continue to ask questions in subsequent turns until you have a comprehensive understanding.
4. When you have sufficient information, output the tag \"[PLAN_READY]\" followed by a comprehensive, optimized PROMPT that summarizes all the user's requirements and the context gathered. Do NOT answer the question yet. This prompt will be used to generate the final response.";
        }

        // Add a strong system instruction to guide tool usage
        if (isClaudeToolAllowed($originApiKey,$locationHost)) {
            $messages[] = [
                'role' => 'system',
                'content' => "You are an advanced AI assistant with access to a \"Claude Code\" agent running in a container. 
Current Date and Time: $currentTime
Your primary way of interacting with the world (searching, coding, file operations) is by delegating tasks to this agent using the `claude_code` tool.
When using `claude_code`, the command MUST look like: claude \"your natural language instruction here\".
Example: claude \"search for recent news about AI\"
Do NOT generate raw shell commands like `ls`, `curl`, `apt-get` unless you are inside a debugging session or explicitly asked for shell commands.
Always prefer using the `claude` CLI agent for complex tasks." . $planInstructions
            ];
        }else{
             $messages[] = [
                'role' => 'system',
                'content' => "You are a helpful and intelligent AI assistant.
Current Date and Time: $currentTime
You are capable of answering questions, writing code, and assisting with various tasks.
Please provide accurate, safe, and helpful responses." . $planInstructions
            ]; 
        }

        foreach ($history as $msg) {
            $role = isset($msg['role']) ? $msg['role'] : 'user';
            // Ensure valid roles for OpenAI API
            if (!in_array($role, ['system', 'user', 'assistant', 'tool'])) {
                $role = 'user';
            }
            
            $content = isset($msg['content']) ? $msg['content'] : '';
            
            // Handle existing tool calls in history if any (simple reconstruction)
            // For now, we assume history is simple text.
            
            $messages[] = [
                'role' => $role,
                'content' => $content
            ];
        }

        // Add Confirmed Command Output if any
        if ($confirmedOutput) {
            $messages[] = [
                'role' => 'user',
                'content' => "Command executed successfully. Output:\n" . $confirmedOutput
            ];
        }
        
        // Add current user message
        if (!empty($imageUrls)) {
            $content = [['type' => 'text', 'text' => $prompt]];
            foreach ($imageUrls as $url) {
                $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
            }
            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        // 2. Define Tools
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'generate_file',
                    'description' => 'Generate a file for the user to download. Use this tool ONLY when the user explicitly requests to create, download, or save a file/document. Do NOT use this tool for general questions, analysis, or code generation unless the user asks for a file.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'filename' => [
                                'type' => 'string',
                                'description' => 'Name of the file with extension (e.g., proposal.doc, notes.txt)'
                            ],
                            'content' => [
                                'type' => 'string',
                                'description' => 'The content of the file. For .doc files, use simple HTML structure.'
                            ]
                        ],
                        'required' => ['filename', 'content']
                    ]
                ]
            ],
            [
                'type' => 'function',
                'function' => [
                    'name' => 'download_container_file',
                    'description' => 'Download a file or directory from the container workspace to the user. Use this when the user asks for a file generated inside the container.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'path' => [
                                'type' => 'string',
                                'description' => 'Path to the file or directory inside the container (e.g., /app/result.txt)'
                            ]
                        ],
                        'required' => ['path']
                    ]
                ]
            ]
        ];

        if (!$disableClaudeTool && isClaudeToolAllowed($originApiKey,$locationHost)) {
             $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => 'claude_code',
                    'description' => 'Use the Claude CLI agent to perform tasks. This is the primary tool for research, coding, system operations, and file management. You should delegate almost all complex tasks to this agent.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'command' => [
                                'type' => 'string',
                                'description' => 'The command string. It MUST start with "claude" followed by a natural language instruction in quotes describing what you want the agent to do. Example: claude "search for Huawei P8 images and save them"'
                            ]
                        ],
                        'required' => ['command']
                    ]
                ]
             ];
        }

        // 3. Main Loop for Tool Calls (Max 3 turns)
        $maxTurns = 3;
        $currentTurn = 0;

        while ($currentTurn < $maxTurns) {
            $currentTurn++;
            
            $payload = ProviderTransformer::transformPayload($provider, $model, $messages, $tools, ['stream' => true]);

            // Transform URL if needed (e.g. for Gemini)
            $requestUrl = ProviderTransformer::transformUrl($provider, $endpoint, $model, $apiKey, true);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            // State variables for the stream
            $buffer = '';
            $assistantContent = '';
            $toolCallsBuffer = []; 
            $isToolCall = false;

            // Use a closure to capture state
            $writeFunction = function($ch, $chunk) use (&$buffer, &$assistantContent, &$toolCallsBuffer, &$isToolCall, $provider) {
                $buffer .= $chunk;
                
                // Check for API errors in the chunk
                if (strpos($chunk, '"error":') !== false) {
                    $errorData = json_decode($chunk, true);
                    $errorMsg = isset($errorData['error']['message']) ? $errorData['error']['message'] : 'AI Endpoint Error';
                    $this->sendSseEvent('text', "\n[Error: $errorMsg]");
                    return strlen($chunk);
                }

                // Split by any newline sequence
                $lines = preg_split("/\r\n|\n|\r/", $buffer);
                // The last element is potentially incomplete, keep it in buffer
                $buffer = array_pop($lines);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $parsed = ProviderTransformer::parseStreamChunk($provider, $line);
                    if (!$parsed) continue;
                    if (isset($parsed['done']) && $parsed['done']) continue;

                    // 1. Handle Tool Calls
                    if (!empty($parsed['tool_calls'])) {
                        $isToolCall = true;
                        foreach ($parsed['tool_calls'] as $tc) {
                            $index = $tc['index'];
                            if (!isset($toolCallsBuffer[$index])) {
                                $toolCallsBuffer[$index] = [
                                    'id' => isset($tc['id']) ? $tc['id'] : '',
                                    'type' => 'function',
                                    'function' => [
                                        'name' => isset($tc['function']['name']) ? $tc['function']['name'] : '',
                                        'arguments' => ''
                                    ]
                                ];
                            }
                            if (isset($tc['id'])) {
                                $toolCallsBuffer[$index]['id'] = $tc['id'];
                            }
                            if (isset($tc['function']['name'])) {
                                $toolCallsBuffer[$index]['function']['name'] = $tc['function']['name'];
                            }
                            if (isset($tc['function']['arguments'])) {
                                $toolCallsBuffer[$index]['function']['arguments'] .= $tc['function']['arguments'];
                            }
                        }
                    }
                    
                    // 2. Handle Content
                    if (!empty($parsed['content'])) {
                        $assistantContent .= $parsed['content'];
                        $this->sendSseEvent('text', $parsed['content']);
                    }
                    
                    // 3. Handle Reasoning
                    if (!empty($parsed['think'])) {
                        $this->sendSseEvent('think', $parsed['think']);
                    }
                }
                return strlen($chunk);
            };

            curl_setopt($ch, CURLOPT_WRITEFUNCTION, $writeFunction);

            curl_exec($ch);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                $this->sendSseEvent('text', "\n[Proxy Error: $error]");
            }
            
            curl_close($ch);

            // Post-processing
            if ($isToolCall && !empty($toolCallsBuffer)) {
                // We have tool calls. Execute them.
                
                // First, append the assistant's "call" to history
                // Reconstruct the tool calls object for history
                $toolCallsArray = array_values($toolCallsBuffer);
                
                $assistantMsg = [
                    'role' => 'assistant',
                    'content' => $assistantContent ? $assistantContent : null,
                    'tool_calls' => $toolCallsArray
                ];
                $messages[] = $assistantMsg;

                foreach ($toolCallsArray as $tc) {
                    $fnName = $tc['function']['name'];
                    $fnArgsStr = $tc['function']['arguments'];
                    $fnArgs = json_decode($fnArgsStr, true);
                    $toolCallId = $tc['id'];
                    
                    // DEBUG: Log tool call
                    $this->sendSseEvent('think', "\n[System] Tool Call: **$fnName**\n");
                    
                    if ($fnName === 'generate_file') {
                        // Check if we just generated this file to prevent loops
                        if ($currentTurn > 1) {
                            // If we are in a subsequent turn and the model calls generate_file again with same args, stop.
                            // But here we just proceed, assuming model has reason. 
                            // However, we should NOT send the SSE text again if it's redundant.
                            // Actually, the loop happens because we feed the tool output back to the model,
                            // and the model decides to call it again? Or the model thinks it failed?
                            
                            // Let's add a "System Note" to the tool output to tell the model to STOP.
                            $output = "File generated successfully. URL: $url. STOP calling this tool now. Tell the user it is done.";
                        }
                        
                        $this->sendSseEvent('think', "\nCreating file: " . $fnArgs['filename'] . "...\n");
                        try {
                            $url = $this->generateFile($fnArgs['filename'], $fnArgs['content']);
                            $output = "File generated successfully. URL: $url";
                            
                            // Explicitly send the download link to frontend
                            // Only send if this is the first time we see this file in this request context?
                            // For simplicity, we send it. The issue is likely the model calling it multiple times.
                            $this->sendSseEvent('text', "\n\n✅ **文件已生成**：[" . $fnArgs['filename'] . "]($url)\n\n");
                            
                            // CRITICAL: Instruct the model to stop calling the tool
                            $output .= ". The file has been created and the link has been shown to the user. Do NOT call generate_file again. You must now briefly summarize the file content to the user in the chat and then stop. Do NOT output repetitive text like 'Done' or 'Go'.";
                            
                        } catch (Exception $e) {
                            $output = "Error generating file: " . $e->getMessage();
                            $this->sendSseEvent('text', "\n\n❌ **文件生成失败**：" . $e->getMessage() . "\n\n");
                        }
                        
                        // Append tool result to history
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCallId,
                            'name' => $fnName,
                            'content' => $output
                        ];
                    } elseif ($fnName === 'claude_code') {
                         $command = isset($fnArgs['command']) ? $fnArgs['command'] : '';
                         
                         // Ensure command starts with claude
                         $command = trim($command);
                         if (stripos($command, 'claude') !== 0) {
                             $command = 'claude "' . str_replace('"', '\"', $command) . '"';
                         }
                         
                         // CRITICAL: Update fnArgs so the frontend receives the full command with claude prefix
                         $fnArgs['command'] = $command;
                         
                         // DEBUG: Check confirmedOutput status
                         // $this->sendSseEvent('think', "\n[DEBUG] claude_code called. confirmedOutput length: " . strlen($confirmedOutput) . "\n");
                         
                         // Check if this is a resumed execution (confirmed output is present)
                         if ($confirmedOutput) {
                             // This means we already executed it in step 1, so we just use the output
                             // We should NOT execute it again, but we need to return the output to the message history so the loop continues (or not?)
                             // Actually, if we have confirmed output, we should have already injected it into the prompt history before calling the LLM.
                             // So the LLM *should* know the result.
                             
                             // If the LLM calls the tool AGAIN, it means it wants to run ANOTHER command (or the same one again).
                             // So, technically, if we are here, we should treat it as a NEW request, UNLESS the LLM is just repeating itself blindly.
                             
                             // Let's assume for now that ANY tool call here is a NEW request that needs confirmation, 
                             // UNLESS we just injected the result of THIS specific command.
                             
                             // But we don't track "which" command was confirmed in the $confirmedOutput variable, just the output.
                             // Simple heuristic: If confirmedOutput is present, we assume the LLM has seen it.
                             // If it calls tool again, it must be a new command.
                             
                             // Wait, if $confirmedOutput is present, it means we just finished a "turn" where we executed a command.
                             // The LLM was called with that output.
                             // Now the LLM is generating a response.
                             // If that response includes a tool call, it is a NEW tool call.
                             
                             // So, we should NEVER skip confirmation just because $confirmedOutput is not empty.
                             // $confirmedOutput was for the *previous* logical step (which happened milliseconds ago in the backend flow).
                             
                             // Therefore, we should ALWAYS ask for confirmation here.
                             // The only exception is if we had a mechanism to say "this specific tool call ID has been approved".
                             
                             // Let's remove the check for $confirmedOutput here to force confirmation every time the LLM *generates* a tool call.
                         }
                         
                         // Decision Point: Always ask for confirmation for claude_code
                         
                         $this->sendSseEvent('think', "\n⚠️ **Command Execution Paused**\nWaiting for user confirmation to execute: `$command`\n");
                         
                         $this->sendSseEvent('decision', json_encode([
                             'type' => 'confirmation',
                             'args' => $fnArgs,
                             'message' => 'The AI wants to execute a command. Please confirm.'
                         ]));
                         
                         // Stop processing further tools and stream
                         echo "data: [DONE]\n\n";
                         $this->flush();
                         exit();
                         
                     } elseif ($fnName === 'download_container_file') {
                          $containerPath = isset($fnArgs['path']) ? $fnArgs['path'] : '';
                          $filename = basename($containerPath);
                          $downloadsDir = __DIR__ . '/downloads';
                          if (!is_dir($downloadsDir)) {
                              mkdir($downloadsDir, 0777, true);
                          }
                          
                          // Unique filename
                          $targetFilename = $chatId . '_' . time() . '_' . $filename;
                          $hostPath = $downloadsDir . '/' . $targetFilename;
                          
                          $localRuntime = __DIR__ . '/runtime';
                          $runtimePath = $localRuntime;
                          if (!is_dir($localRuntime)) {
                              if (!@mkdir($localRuntime, 0777, true)) {
                                  $runtimePath = sys_get_temp_dir() . '/aihelp_runtime';
                              }
                          } elseif (!is_writable($localRuntime)) {
                               $runtimePath = sys_get_temp_dir() . '/aihelp_runtime';
                          }
                          
                          $docker = new DockerManager($runtimePath);
                          
                          try {
                              $this->sendSseEvent('think', "\n📦 **Packaging file:** `$containerPath`...\n");
                              $docker->copyFileFromContainer($chatId, $containerPath, $hostPath);
                              
                              // Generate URL
                              $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
                              $host = $_SERVER['HTTP_HOST'];
                              $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
                              $scriptDir = str_replace('\\', '/', $scriptDir);
                              $scriptDir = rtrim($scriptDir, '/');
                              $url = "$protocol://$host$scriptDir/downloads/" . $targetFilename;
                              
                              $output = "File downloaded successfully. URL: $url";
                              $this->sendSseEvent('text', "\n\n✅ **File Ready:** [$filename]($url)\n\n");
                              
                              // Stop container after file operation
                              $docker->stopContainer($chatId);
                              
                          } catch (Exception $e) {
                              $output = "Error downloading file: " . $e->getMessage();
                              $this->sendSseEvent('text', "\n❌ **Download Error:** " . $e->getMessage() . "\n");
                              // Ensure stopped on error
                              $docker->stopContainer($chatId);
                          }
                          
                          $messages[] = [
                              'role' => 'tool',
                              'tool_call_id' => $toolCallId,
                              'name' => $fnName,
                              'content' => $output
                          ];
                     } else {
                         // Unknown tool
                        $messages[] = [
                            'role' => 'tool',
                            'tool_call_id' => $toolCallId,
                            'name' => $fnName,
                            'content' => "Error: Tool '$fnName' not found."
                        ];
                    }
                }
                // Continue loop to send tool results back to LLM
            } else {
                // No tool calls, we are done.
                break;
            }
        }
        
        echo "data: [DONE]\n\n";
        $this->flush();
    }

    /**
     * Generate a file and return its public URL
     */
    private function generateFile($filename, $content)
    {
        // Security check: prevent directory traversal
        $filename = basename($filename);
        
        // Use current directory downloads folder
        $downloadsDir = __DIR__ . '/downloads';
        
        if (!is_dir($downloadsDir)) {
            mkdir($downloadsDir, 0777, true);
        }

        // Special handling for .doc files to ensure Chinese formatting (SimSun font, A4 margins)
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'doc') {
            // Check if content is already a full HTML document
            if (stripos($content, '<html') === false) {
                // Wrap content in HTML with Word-compatible styles
                $htmlContent = "<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>\n";
                $htmlContent .= "<head>\n";
                $htmlContent .= "<meta charset='utf-8'>\n";
                $htmlContent .= "<title>" . htmlspecialchars($filename) . "</title>\n";
                // Add Word XML to force Print Layout
                $htmlContent .= "<!--[if gte mso 9]><xml><w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom><w:DoNotOptimizeForBrowser/></w:WordDocument></xml><![endif]-->\n";
                $htmlContent .= "<style>\n";
                // A4 Paper, Standard Margins (2.54cm) - Use global @page to ensure WPS compatibility
                $htmlContent .= "@page { size: 21cm 29.7cm; margin: 2.54cm; mso-page-orientation: portrait; }\n";
                // SimSun font (宋体)
                $htmlContent .= "body { font-family: 'SimSun', '宋体', serif; font-size: 12pt; line-height: 1.5; }\n";
                $htmlContent .= "</style>\n";
                $htmlContent .= "</head>\n";
                $htmlContent .= "<body>\n";
                $htmlContent .= $content;
                $htmlContent .= "\n</body>\n";
                $htmlContent .= "</html>";
                $content = $htmlContent;
            } else {
                // If it is already HTML, inject the style into <head>
                $style = "<style>@page { size: 21cm 29.7cm; margin: 2.54cm; } body { font-family: 'SimSun', '宋体', serif !important; }</style>";
                if (stripos($content, '</head>') !== false) {
                     $content = str_ireplace('</head>', $style . "\n</head>", $content);
                }
            }
        }
        
        $path = $downloadsDir . '/' . $filename;
        file_put_contents($path, $content);
        
        // Construct public URL relative to the script execution
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        
        // Get the directory of the current script from URL perspective
        // script_name usually starts with /
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        // Ensure valid path formatting (replace backslashes on Windows)
        $scriptDir = str_replace('\\', '/', $scriptDir);
        // Remove trailing slash if root
        $scriptDir = rtrim($scriptDir, '/');
        
        return "$protocol://$host$scriptDir/downloads/$filename";
    }

    /**
     * Perform Web Search using LangSearch API with Caching
     */
    private function performWebSearch($query)
    {
        $cacheDir = __DIR__ . '/.cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        $cacheKey = md5(trim($query));
        $cacheFile = $cacheDir . '/' . $cacheKey . '.json';

        // Check cache (e.g., valid for 24 hours)
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if (isset($cached['results_text'])) {
                $this->sendSseEvent('think', "🔄 **发现缓存的搜索结果：** \"$query\"\n\n");
                $this->sendSseEvent('think', $cached['results_text'] . "\n");
                return $cached['results_text'];
            }
        }

        $this->sendSseEvent('think', "🌐 **正在联网搜索：** \"$query\"...\n\n");

        // LangSearch API Call
        // Note: Replace with actual endpoint/key if provided by user. 
        // Based on typical AI search APIs.
        $searchEndpoint = "https://api.langsearch.com/v1/web-search";
        $langSearchApiKey = LANG_SEARCH_API_KEY;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'query' => $query,
            'max_results' => 5
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $langSearchApiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            // Format results based on LangSearch response structure
            $resultsText = "";
            $searchItems = [];

            if (isset($data['data']['webPages']['value']) && is_array($data['data']['webPages']['value'])) {
                $searchItems = $data['data']['webPages']['value'];
            } else if (isset($data['data']) && is_array($data['data'])) {
                $searchItems = $data['data'];
            }

            foreach ($searchItems as $idx => $item) {
                $title = isset($item['name']) ? $item['name'] : (isset($item['title']) ? $item['title'] : 'Untitled');
                $url = isset($item['url']) ? $item['url'] : '';
                $content = isset($item['summary']) ? $item['summary'] : (isset($item['snippet']) ? $item['snippet'] : '');
                $snippet = isset($item['snippet']) ? $item['snippet'] : (mb_strlen($content) > 100 ? mb_substr($content, 0, 100) . '...' : $content);
                
                // Construct a structured search item that frontend can style
                $resultsText .= "<div class='search-item'>\n";
                $resultsText .= "  <div class='search-item-title'><strong>[$idx] $title</strong> <a href='$url' target='_blank'>[查看来源]</a></div>\n";
                $resultsText .= "  <details class='search-item-content'>\n";
                $resultsText .= "    <summary title='点击展开详情'>$snippet</summary>\n";
                $resultsText .= "    <div class='search-item-full'>$content</div>\n";
                $resultsText .= "  </details>\n";
                $resultsText .= "</div>\n\n";
            }

            if ($resultsText) {
                // Save to cache
                file_put_contents($cacheFile, json_encode(['results_text' => $resultsText]));
                $this->sendSseEvent('think', "✅ **搜索完成，获取到以下参考信息：**\n\n");
                $this->sendSseEvent('think', $resultsText . "\n");
                return $resultsText;
            }
        }

        $this->sendSseEvent('think', "Web search returned no results or failed.\n");
        return "";
    }

    /**
     * Detect intent for image generation
     */
    private function detectImageIntent($prompt)
    {
        $keywords = ['画一', '生成图片', '绘图', 'generate image', 'create an image', 'draw a', '画个', '画张'];
        foreach ($keywords as $kw) {
            if (mb_stripos($prompt, $kw) !== false) {
                return true;
            }
        }
        return false;
    }






    /**
     * Perform Image Generation or Editing
     */
    private function performImageGeneration($prompt, $imageUrls = [], $locationHost = '', $t2iModel = null, $i2iModel = null)
    {
        $this->sendSseEvent('think', "🖌️ **正在请求 AI 绘图模型...**\n");
        
        // Rcouyi Image Generation (migrated to config.php)
        if (performRcouyiImageGeneration($locationHost, $prompt, $imageUrls, $t2iModel, $i2iModel, function($type, $content) {
            $this->sendSseEvent($type, $content);
        }, function() {
            $this->flush();
        })) {
            return;
        }

        // Default Configuration (Gemini)
        $genEndpoint = "";
        $genApiKey = "";
        $modelName = ""; // Default model

        // Model Selection Logic
        $selectedModel = null;
        $isEdit = !empty($imageUrls);

        if ($isEdit) {
            // Edit Mode: Prefer I2I, fallback to T2I
            if ($i2iModel) {
                $selectedModel = $i2iModel;
            } elseif ($t2iModel) {
                $selectedModel = $t2iModel;
            }
        } else {
            // Generation Mode: Prefer T2I, fallback to I2I
            if ($t2iModel) {
                $selectedModel = $t2iModel;
            } elseif ($i2iModel) {
                // Fallback to i2iModel even if T2I mode
                $selectedModel = $i2iModel;
            }
        }

        if ($selectedModel) {
            $genEndpoint = $selectedModel['endpoint'] ?? $genEndpoint;
            $genApiKey = $selectedModel['api_key'] ?? $genApiKey;
            $modelName = $selectedModel['model'] ?? $modelName;
        }
        
        applyDefaultImgConfig($genEndpoint,$genApiKey,$modelName);

        // Detect Provider Type
        $isGemini = strpos($genEndpoint, 'generateContent') !== false || strpos($genEndpoint, 'googleapis.com') !== false;
        $providerType = $isGemini ? 'gemini' : 'openai';

        if ($isGemini) {
             if (!empty($imageUrls)) {
                  $this->sendSseEvent('think', "🖼️ **包含参考图片，进入图像编辑/参考模式...**\n");
             } else {
                  $this->sendSseEvent('think', "🎨 **纯文本描述，进入文生图模式...**\n");
             }
        } else {
             $this->sendSseEvent('think', "🎨 **请求兼容模型 ($modelName)...**\n");
        }

        try {
            $payload = ProviderTransformer::transformImagePayload($providerType, $modelName, $prompt, $imageUrls);
        } catch (Exception $e) {
             $this->sendSseEvent('text', "\n[Error: " . $e->getMessage() . "]\n");
             return;
        }

        // Send Image Start Signal
        $this->sendSseEvent('image_start', 'generating');
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $genEndpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $genApiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Longer timeout for image gen

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        //  $this->sendSseEvent('think', $genEndpoint);
        //  $this->sendSseEvent('think', json_encode($payload));
        //   $this->sendSseEvent('think', $response);
        //   echo "data: [DONE]\n\n";
        // $this->flush();
         
        if (curl_errno($ch)) {
             $this->sendSseEvent('text', "\n[Network Error: " . curl_error($ch) . "]\n");
             curl_close($ch);
             return;
        }
        curl_close($ch);
        
        if ($httpCode !== 200) {
             $this->sendSseEvent('text', "\n[API Error ($httpCode): $response]\n");
             return;
        }
        
        // Parse Response via Transformer
        $results = ProviderTransformer::parseImageResponse($providerType, $response);
        $foundImage = false;

        foreach ($results as $res) {
            if (isset($res['b64_json'])) {
                $this->saveAndSendImage($res['b64_json']);
                $foundImage = true;
            } elseif (isset($res['url'])) {
                $this->sendSseEvent('text', "\n![Generated Image]({$res['url']})\n");
                $this->sendSseEvent('text', "\n[下载图片]({$res['url']})\n");
                $foundImage = true;
            } elseif (isset($res['text'])) {
                $this->sendSseEvent('text', $res['text']);
            }
        }
        
        if (!$foundImage) {
            // Fallback dump
            $this->sendSseEvent('text', "\nResponse received but no image found. Raw response:\n" . substr($response, 0, 500));
        }
        
        echo "data: [DONE]\n\n";
        $this->flush();
    }

    private function saveAndSendImage($base64) 
    {
        $filename = 'gen_' . time() . '_' . rand(1000,9999) . '.png';
        $downloadsDir = __DIR__ . '/downloads';
        if (!is_dir($downloadsDir)) mkdir($downloadsDir, 0777, true);
        
        file_put_contents($downloadsDir . '/' . $filename, base64_decode($base64));
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $scriptDir = str_replace('\\', '/', $scriptDir);
        $scriptDir = rtrim($scriptDir, '/');
        $fileUrl = "$protocol://$host$scriptDir/downloads/$filename";
        
        $this->sendSseEvent('text', "\n![Generated Image]($fileUrl)\n");
        $this->sendSseEvent('text', "\n[下载图片]($fileUrl)\n");
    }



    /**
     * Get text response from AI (Non-streaming)
     * Generic helper for internal use or simple API calls
     */
    public function getTextResponse($prompt, $history = [], $endpoint = null, $apiKey = null, $model = null, $locationHost = '', $extraParams = [])
    {
        // 1. Setup Config
        if (!$endpoint) $endpoint = '';
        if (!$apiKey) $apiKey = '';
        if (!$model) $model = '';
        
        $skipConfig = isset($extraParams['skip_config']) ? $extraParams['skip_config'] : false;
        if (!$skipConfig) {
            applyDefaultConfig($endpoint, $apiKey, $model, false, $locationHost);
        }

        // 2. Prepare Messages
        $messages = $history;
        $messages[] = ['role' => 'user', 'content' => $prompt];

        // 3. Determine Provider
        $isGemini = strpos($endpoint, 'generateContent') !== false || strpos($endpoint, 'googleapis.com') !== false;
        $provider = $isGemini ? 'gemini' : 'openai';

        // 4. Transform Payload
        $transformOptions = array_merge(['stream' => false], $extraParams);
        $payload = ProviderTransformer::transformPayload($provider, $model, $messages, [], $transformOptions);

        // 5. Transform URL
        $requestUrl = ProviderTransformer::transformUrl($provider, $endpoint, $model, $apiKey, false);

        // 6. Execute Request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $response) {
            // 7. Parse Response
            return ProviderTransformer::parseResponse($provider, $response);
        }
        
        return null;
    }

    /**
     * Optimize search query using AI
     */
    private function optimizeSearchQuery($prompt, $history, $endpoint, $apiKey, $model, $locationHost = '')
    {
        // Apply fast config for optimization
        applyFastConfig($endpoint, $apiKey, $model, $locationHost);

        // Take last 6 messages to keep context concise
        $recentHistory = array_slice($history, -6);
        
        $historyText = "";
        foreach ($recentHistory as $msg) {
            $role = isset($msg['role']) ? $msg['role'] : 'unknown';
            $content = isset($msg['content']) ? $msg['content'] : '';
            // Limit content length per message to avoid token limits
            $content = mb_substr($content, 0, 500);
            $historyText .= $role . ": " . $content . "\n";
        }

        $currentTime = date('Y-m-d H:i:s');
        $systemPrompt = "You are a search query optimizer. 
The user is chatting with an AI assistant. 
Current Date and Time: $currentTime
Your task is to rewrite the user's LATEST message into a standalone, specific search query that includes necessary context from the conversation history.

Rules:
1. If the user's message is already clear and self-contained, return it as is.
2. If the user's message refers to previous context (e.g., \"how old is she?\", \"compare them\"), replace pronouns and ambiguous terms with specific entities from the history.
3. If the user uses relative time references (e.g., \"yesterday\", \"last week\"), convert them to specific dates based on the Current Date.
4. Output ONLY the rewritten query. Do not add quotes, explanations, or any other text.
5. Keep the query concise and search-engine friendly.

Conversation History:
$historyText

User's Latest Message: \"$prompt\"

Rewritten Search Query:";

        $response = $this->getTextResponse($systemPrompt, [], $endpoint, $apiKey, $model, $locationHost, [
            'skip_config' => true,
            'temperature' => 0.3,
            'max_tokens' => 100
        ]);

        return $response ? trim($response) : $prompt;
    }










    /**
     * Helper to send SSE data
     */
    private function sendSseEvent($type, $content)
    {
        $payload = json_encode([
            'type' => $type,
            'content' => $content
        ]);
        
        echo "data: " . $payload . "\n\n";
        $this->flush();
    }

    /**
     * Flush output buffer
     */
    private function flush()
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}

// Direct Execution Entry Point
if (php_sapi_name() !== 'cli') {
    $assistant = new AiAssistant();
    $assistant->dispatch();
}
