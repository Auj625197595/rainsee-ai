<?php

/**
 * ProviderTransformer - Handles transformation of payloads and responses
 * for different AI providers.
 */
class ProviderTransformer
{
    /**
     * Transform messages and tools to provider-specific format
     */
    public static function transformPayload($provider, $model, $messages, $tools, $options = [])
    {
        // Provider-specific message normalization
        $normalizedMessages = self::normalizeMessages($provider, $messages);

        // Default (OpenAI-compatible)
        $payload = [
            'model' => $model,
            'messages' => $normalizedMessages,
            'stream' => isset($options['stream']) ? $options['stream'] : true,
        ];
        
        if (isset($options['temperature'])) {
            $payload['temperature'] = $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
        if (isset($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        if (!empty($tools)) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        // Provider-specific adjustments
        switch (strtolower($provider)) {
            case 'openai':
                // OpenAI is fully compatible with the default payload structure
                // But some older models might not support specific tool_choice options
                if (strpos($model, 'o1-') === 0 || strpos($model, 'o3-') === 0) {
                     // o1/o3 series models might have restrictions on system prompts or tools
                     // Example: change 'system' to 'user' for older o1 models, or remove tools
                     // Currently assuming standard compatibility based on latest updates
                }
                break;

            case 'deepseek':
                // DeepSeek specific parameters
                if (strpos($model, 'reasoner') !== false) {
                    // deepseek-reasoner doesn't support tools in some scenarios or requires specific formatting
                    // We keep it as is since DeepSeek generally follows OpenAI format, 
                    // but we ensure stream is true for reasoning_content to work best.
                    $payload['stream'] = true;
                }
                break;
                
            case 'gemini':
                // Gemini Native API Adaptation
                // Transform OpenAI-style payload to Gemini-style
                
                // 1. Extract System Instruction
                $systemInstruction = null;
                $geminiContents = [];
                
                foreach ($normalizedMessages as $msg) {
                    if ($msg['role'] === 'system') {
                        $systemInstruction = ['parts' => [['text' => $msg['content']]]];
                    } else {
                        // Map roles: user -> user, assistant -> model
                        $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
                        $parts = [];
                        
                        // Fix for mixed content handling: ensure content is array if it's not
                        $content = $msg['content'];
                        if (!is_array($content)) {
                            // If it's a tool response (role=tool), we handle it separately below
                            if ($msg['role'] !== 'tool') {
                                $parts[] = ['text' => (string)$content];
                            }
                        } else {
                             foreach ($content as $contentPart) {
                                if ($contentPart['type'] === 'text') {
                                    $parts[] = ['text' => $contentPart['text']];
                                } elseif ($contentPart['type'] === 'image_url') {
                                    // Handle Image URL
                                    $url = $contentPart['image_url']['url'];
                                    if (strpos($url, 'data:') === 0) {
                                        // Data URI
                                        list($meta, $data) = explode(',', $url);
                                        $mime = explode(';', substr($meta, 5))[0];
                                        $parts[] = ['inlineData' => ['mimeType' => $mime, 'data' => $data]];
                                    } else {
                                        // Remote URL
                                        $imgData = @file_get_contents($url);
                                        if ($imgData) {
                                             $finfo = new \finfo(FILEINFO_MIME_TYPE);
                                             $mime = $finfo->buffer($imgData);
                                             $parts[] = ['inlineData' => ['mimeType' => $mime, 'data' => base64_encode($imgData)]];
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Handle Tool Calls (Assistant)
                        if (isset($msg['tool_calls']) && is_array($msg['tool_calls'])) {
                            foreach ($msg['tool_calls'] as $tc) {
                                $parts[] = [
                                    'functionCall' => [
                                        'name' => $tc['function']['name'],
                                        'args' => json_decode($tc['function']['arguments'], true)
                                    ]
                                ];
                            }
                        }
                        
                        // Handle Tool Responses (Tool)
                        if ($msg['role'] === 'tool') {
                            $role = 'function'; // Internal mapping helper
                            $parts[] = [
                                'functionResponse' => [
                                    'name' => 'unknown_tool', 
                                    'response' => ['result' => $msg['content']]
                                ]
                            ];
                        }

                        if (!empty($parts)) {
                             // Fix: Gemini role must be 'user' or 'model'.
                             if ($role === 'function') $role = 'user';
                             $geminiContents[] = ['role' => $role, 'parts' => $parts];
                        }
                    }
                }

                $generationConfig = [
                    'temperature' => isset($options['temperature']) ? $options['temperature'] : 0.7,
                ];
                if (isset($options['max_tokens'])) {
                    $generationConfig['maxOutputTokens'] = $options['max_tokens'];
                }
                // Gemini JSON mode
                if (isset($options['response_format']) && $options['response_format']['type'] === 'json_object') {
                    $generationConfig['responseMimeType'] = 'application/json';
                }

                $newPayload = [
                    'contents' => $geminiContents,
                    'generationConfig' => $generationConfig
                ];

                if ($systemInstruction) {
                    $newPayload['systemInstruction'] = $systemInstruction;
                }
                
                // Tools Adaptation
                if (!empty($tools)) {
                    $geminiTools = [];
                    foreach ($tools as $t) {
                        if ($t['type'] === 'function') {
                            $geminiTools[] = $t['function']; // Structure is similar enough
                        }
                    }
                    if (!empty($geminiTools)) {
                        $newPayload['tools'] = [['functionDeclarations' => $geminiTools]];
                        // toolConfig defaults to auto
                    }
                }
                
                return $newPayload;


            case 'anthropic':
            case 'claude':
                // Claude often requires top-level system parameter instead of message
                $system = '';
                $newMessages = [];
                foreach ($normalizedMessages as $msg) {
                    if ($msg['role'] === 'system') {
                        $system .= ($system ? "\n" : "") . $msg['content'];
                    } else {
                        $newMessages[] = $msg;
                    }
                }
                if ($system) {
                    $payload['system'] = $system;
                    $payload['messages'] = $newMessages;
                }
                break;
        }

        return $payload;
    }

    /**
     * Transform URL to provider-specific format
     */
    public static function transformUrl($provider, $baseUrl, $model, $apiKey, $stream = true)
    {
        switch (strtolower($provider)) {
            case 'gemini':
                // Remove OpenAI-specific suffixes
             
                $query = "key={$apiKey}";
                if ($stream) {
                    $query .= "&alt=sse";
                }
                
                return "{$baseUrl}?{$query}";
                
            default:
                return $baseUrl;
        }
    }

    /**
     * Ensure messages follow basic provider rules (e.g. no consecutive same-role messages)
     */
    private static function normalizeMessages($provider, $messages)
    {
        $normalized = [];
        $lastRole = null;

        foreach ($messages as $msg) {
            $role = $msg['role'];
            $content = $msg['content'];

            if ($role === $lastRole && $role !== 'system') {
                // Merge consecutive messages of same role (except system)
                $lastIdx = count($normalized) - 1;
                if (is_array($normalized[$lastIdx]['content']) || is_array($content)) {
                    // Handle mixed text/image content
                    if (!is_array($normalized[$lastIdx]['content'])) {
                        $normalized[$lastIdx]['content'] = [['type' => 'text', 'text' => $normalized[$lastIdx]['content']]];
                    }
                    if (is_array($content)) {
                        $normalized[$lastIdx]['content'] = array_merge($normalized[$lastIdx]['content'], $content);
                    } else {
                        $normalized[$lastIdx]['content'][] = ['type' => 'text', 'text' => $content];
                    }
                } else {
                    $normalized[$lastIdx]['content'] .= "\n\n" . $content;
                }
            } else {
                $normalized[] = $msg;
                $lastRole = $role;
            }
        }

        return $normalized;
    }

    /**
     * Parse a streaming chunk from a provider and return a unified format
     * Returns: ['content' => '', 'think' => '', 'tool_calls' => [], 'done' => false]
     */
    public static function parseStreamChunk($provider, $line)
    {
        $line = trim($line);
        if (empty($line)) return null;
        if (strpos($line, 'data: ') !== 0) return null;
        
        $dataStr = substr($line, 6);
        if ($dataStr === '[DONE]') {
            return ['done' => true];
        }

        $data = json_decode($dataStr, true);
        if (!$data) return null;

        $result = [
            'content' => '',
            'think' => '',
            'tool_calls' => [],
            'done' => false
        ];

        // Standard OpenAI-compatible format
        if (isset($data['choices'][0]['delta'])) {
            $delta = $data['choices'][0]['delta'];

            if (isset($delta['content'])) {
                $result['content'] = $delta['content'];
            }

            // Reasoning content (DeepSeek, etc.)
            if (isset($delta['reasoning_content'])) {
                $result['think'] = $delta['reasoning_content'];
            }
            
            // Legacy / Alternative reasoning field
            if (isset($delta['reasoning'])) {
                $result['think'] = $delta['reasoning'];
            }

            if (isset($delta['tool_calls'])) {
                $result['tool_calls'] = $delta['tool_calls'];
            }
        } else if (strtolower($provider) === 'gemini') {
             // Gemini direct API format fallback (if not using OpenAI compatibility layer)
             // Though the frontend config uses /v1beta/openai/chat/completions, 
             // adding this as a safeguard if the endpoint changes to native Gemini.
             if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                 $result['content'] = $data['candidates'][0]['content']['parts'][0]['text'];
             }
        }

        return $result;
    }

    /**
     * Transform Image Generation/Editing payload
     * 
     * @param string $provider Provider name (gemini, openai, etc.)
     * @param string $model Model name
     * @param string $prompt Text prompt
     * @param array $imageUrls Array of image URLs for editing/variation
     * @return array The payload array
     * @throws Exception If image fetching fails
     */
    public static function transformImagePayload($provider, $model, $prompt, $imageUrls = [])
    {
        // Detect if it's Gemini based on provider name or model name/endpoint conventions
        // But here we rely on the passed provider name or let the caller decide.
        // For compatibility with the calling code which detects "isGemini" based on endpoint,
        // we might need to be flexible. 
        // However, the caller should normalize the provider name.
        
        $isGemini = strpos(strtolower($provider), 'gemini') !== false || strpos(strtolower($model), 'gemini') !== false;

        if ($isGemini) {
            // Gemini Payload
            $parts = [];
            $parts[] = ['text' => $prompt];
            
            // If images provided, it's Image Editing / Variation
            if (!empty($imageUrls)) {
                $imageUrl = $imageUrls[0]; // Take the first image
                
                $base64Data = "";
                $mimeType = "image/png"; 
                
                try {
                    $imageData = @file_get_contents($imageUrl);
                    if ($imageData !== false) {
                        $base64Data = base64_encode($imageData);
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->buffer($imageData);
                    } else {
                        throw new \Exception("Failed to fetch reference image: $imageUrl");
                    }
                } catch (\Exception $e) {
                     throw $e;
                }
                
                if ($base64Data) {
                    $parts[] = [
                        'inline_data' => [
                            'mime_type' => $mimeType,
                            'data' => $base64Data
                        ]
                    ];
                }
            }

            return [
                'contents' => [
                    [
                        'parts' => $parts
                    ]
                ]
            ];
        } else {
            // OpenAI Compatible Payload (Standard T2I)
            // Note: Currently does not support OpenAI I2I (multipart) in this generic handler
            return [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024'
            ];
        }
    }

    /**
     * Parse Image Generation Response
     * 
     * @param string $provider
     * @param mixed $response Raw response string or decoded array
     * @return array List of image results: [['url' => '...', 'b64_json' => '...', 'text' => '...']]
     */
    public static function parseImageResponse($provider, $response)
    {
        $json = is_array($response) ? $response : json_decode($response, true);
        if (!$json) return [];

        $results = [];

        // Gemini Response
        if (isset($json['candidates'][0]['content']['parts'])) {
            foreach ($json['candidates'][0]['content']['parts'] as $part) {
                $inlineData = isset($part['inline_data']) ? $part['inline_data'] : (isset($part['inlineData']) ? $part['inlineData'] : null);
                
                if ($inlineData) {
                    $results[] = [
                        'b64_json' => $inlineData['data'], // Normalize to b64_json key
                        'mime_type' => isset($inlineData['mime_type']) ? $inlineData['mime_type'] : 'image/png'
                    ];
                } elseif (isset($part['text'])) {
                     $text = $part['text'];
                     if (filter_var($text, FILTER_VALIDATE_URL)) {
                         $results[] = ['url' => $text];
                     } else {
                         $results[] = ['text' => $text];
                     }
                }
            }
        }
        // OpenAI Response
        elseif (isset($json['data'])) {
             foreach ($json['data'] as $dataItem) {
                 if (isset($dataItem['url'])) {
                     $results[] = ['url' => $dataItem['url']];
                 } elseif (isset($dataItem['b64_json'])) {
                     $results[] = ['b64_json' => $dataItem['b64_json']];
                 }
             }
        }

        return $results;
    }

    /**
     * Parse non-streaming response from a provider
     * Returns the text content or null on failure
     */
    public static function parseResponse($provider, $response)
    {
        $data = is_array($response) ? $response : json_decode($response, true);
        if (!$data) return null;

        // 1. OpenAI Compatible
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        // 2. Gemini Native
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        // 3. Claude / Anthropic
        if (isset($data['content'][0]['text'])) {
            return $data['content'][0]['text'];
        }

        return null;
    }
}
