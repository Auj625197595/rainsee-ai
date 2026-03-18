import { store, mutations } from '../store';
import { API_URL, getChatApiUrl } from './config';

// Assuming the backend is served relative to the frontend or via proxy
// TP5 Route convention: /module/controller/action

/**
 * Sends a chat message and handles Server-Sent Events (SSE) streaming response.
 * 
 * @param {string} text - The user's message.
 * @param {Array} context - Previous chat history context.
 * @param {Object} callbacks - { onContent, onThinking, onDone, onError, onChatId, onDecision }
 */
export async function streamChat(text, context, { onContent, onThinking, onDone, onError, onImageStart, onChatId, onDecision, signal }, attachments = [], extraParams = {}) {
  // Ensure Chat ID exists (Lazy Initialization backup)
  if (!store.chatId) {
      console.warn('[streamChat] store.chatId is null, attempting to initialize...');
      if (mutations && mutations.initChatId) {
          mutations.initChatId();
      }
      // Fallback if still null
      if (!store.chatId) {
          const fallbackId = localStorage.getItem('aihelp_chat_id') || ('user_' + Date.now().toString(36) + Math.random().toString(36).substr(2));
          store.chatId = fallbackId;
          localStorage.setItem('aihelp_chat_id', fallbackId);
      }
      console.log('[streamChat] Chat ID initialized to:', store.chatId);
  }

  const headers = {
    'Content-Type': 'application/json',
    'Accept': 'text/event-stream', // Important for SSE
  };

  // --- Typewriter Logic ---
  let typingQueue = [];
  let isTyping = false;
  let streamFinished = false;

  const processQueue = () => {
    if (typingQueue.length === 0) {
      if (streamFinished) {
        if (onDone) onDone();
      } else {
        isTyping = false;
      }
      return;
    }

    isTyping = true;
    
    // Always process one character at a time for smooth typing
    const { type, content } = typingQueue.shift();
    
    if (type === 'think') {
        if (onThinking) onThinking(content);
    } else if (type === 'text') {
        if (onContent) onContent(content);
    }

    // Dynamic delay based on queue length
    // If queue is long, speed up significantly to catch up
    // If queue is short, slow down for readability
    let delay = 30; // Base speed (ms)
    
    if (typingQueue.length > 200) delay = 1;
    else if (typingQueue.length > 100) delay = 5;
    else if (typingQueue.length > 50) delay = 10;
    else if (typingQueue.length > 20) delay = 20;
    setTimeout(processQueue, delay);
  };

  const enqueue = (type, text) => {
    // For thinking content, process as a whole chunk to improve performance
    // This prevents UI freezing when large thinking blocks arrive
    if (type === 'think') {
        typingQueue.push({ type, content: text });
    } else {
        // Push individual characters to ensure smooth typing for regular content
        for (const char of text) {
            typingQueue.push({ type, content: char });
        }
    }
    
    if (!isTyping) {
      processQueue();
    }
  };
  // -------------------------

  // Optimize Context: Head + Tail Strategy
  // 1. Remove current prompt from history if present (to avoid duplication with backend)
  // 2. Keep System Prompt
  // 3. Keep last 10 rounds (20 messages)
  // 4. Truncate middle and add placeholder
  const optimizeContext = (fullContext, currentPrompt) => {
    // Clone to avoid mutating original
    let ctx = [...fullContext];

    // Remove the last message if it matches the current prompt (deduplication)
    if (ctx.length > 0) {
      const lastMsg = ctx[ctx.length - 1];
      if (lastMsg.role === 'user' && lastMsg.content === currentPrompt) {
        ctx.pop();
      }
    }

    const MAX_ROUNDS = 10;
    const KEEP_COUNT = MAX_ROUNDS * 2; // User + Assistant
    
    if (ctx.length <= KEEP_COUNT + 1) { // +1 for System prompt
      return ctx.map(msg => {
        let content = msg.content;
        // Reconstruct context from history attachments
        if (msg.attachments && msg.attachments.length > 0) {
            msg.attachments.forEach(att => {
                if (att.type === 'file') {
                    content += `\n\n[Reference Document Content (${att.name})]:\n${att.content}`;
                    if (att.url) {
                        content += `\n\n[Download ${att.name}]: ${att.url}`;
                    }
                }
            });
        }
        return { role: msg.role, content };
      });
    }

    let optimized = [];
    let startIndex = 0;

    // Preserve System Prompt
    if (ctx.length > 0 && ctx[0].role === 'system') {
      optimized.push({ role: ctx[0].role, content: ctx[0].content });
      startIndex = 1;
    }

    const remaining = ctx.slice(startIndex);
    
    // Helper to format message content
    const formatMsg = (m) => {
        let content = m.content;
        if (m.attachments && m.attachments.length > 0) {
            m.attachments.forEach(att => {
                if (att.type === 'file') {
                    content += `\n\n[Reference Document Content (${att.name})]:\n${att.content}`;
                    if (att.url) {
                        content += `\n\n[Download ${att.name}]: ${att.url}`;
                    }
                }
            });
        }
        return { role: m.role, content };
    };

    // If still small enough, return
    if (remaining.length <= KEEP_COUNT) {
      return optimized.concat(remaining.map(formatMsg));
    }

    // Keep Head (First 2 messages - usually first Q&A) to preserve topic
    // Keep Tail (Last KEEP_COUNT messages) to preserve recent context
    const headCount = 2;
    const tailCount = KEEP_COUNT;

    // Check if we have a middle section to compress
    if (remaining.length > headCount + tailCount) {
      const head = remaining.slice(0, headCount);
      const tail = remaining.slice(-tailCount);
      const skippedCount = remaining.length - headCount - tailCount;

      // Add Head
      head.forEach(m => optimized.push(formatMsg(m)));
      
      // Add Placeholder for compressed/skipped messages
      // Note: True keyword extraction requires an LLM call. 
      // Ideally, the backend would return a summary we could use here.
      optimized.push({
        role: 'system',
        content: `[System Note: ${skippedCount} messages from the middle of the conversation have been compressed/omitted to maintain context window. Please focus on the initial instructions and the most recent ${tailCount} messages.]`
      });

      // Add Tail
      tail.forEach(m => optimized.push(formatMsg(m)));
    } else {
       // Just append all if math says we don't need to split (should be covered by length check but safety first)
       optimized = optimized.concat(remaining.map(formatMsg));
    }

    return optimized;
  };

  const optimizedHistory = optimizeContext(context, text);

  // Combine text with fileContext for the current prompt
  let finalPrompt = text;
  let imageUrls = [];

  if (attachments && attachments.length > 0) {
      attachments.forEach(att => {
          if (att.type === 'file') {
              finalPrompt += `\n\n[Reference Document Content (${att.name})]:\n${att.content}`;
              if (att.url) {
                  finalPrompt += `\n\n[Download ${att.name}]: ${att.url}`;
              }
          } else if (att.type === 'image') {
              imageUrls.push(att.url);
          }
      });
  }

  try {
    const activeModel = store.settings.models.find(m => m.id === store.settings.activeModelId) || store.settings.models[0];
    
    // Get specialized models
    const textModel = store.settings.models.find(m => m.id === store.settings.activeTextModelId) || activeModel;
    const t2iModel = store.settings.models.find(m => m.id === store.settings.activeT2iModelId) || store.settings.models.find(m => m.type === 't2i');
    const i2iModel = store.settings.models.find(m => m.id === store.settings.activeI2iModelId) || store.settings.models.find(m => m.type === 'i2i');

    const payload = {
      prompt: finalPrompt,
      history: optimizedHistory, 
      image_urls: imageUrls,
      chat_id: store.chatId,
      thinking_mode: store.settings.thinkingEnabled,
      web_search: store.settings.webSearchEnabled,
      image_generation: store.settings.imageGenEnabled,
      location_host: window.location.host,
      
      // Default model info (usually text)
      endpoint: textModel.endpoint,
      api_key: textModel.apiKey,
      model: textModel.model,
      
      // Specialized models
      text_model: textModel ? { provider: textModel.provider, endpoint: textModel.endpoint, api_key: textModel.apiKey, model: textModel.model } : null,
      t2i_model: t2iModel ? { provider: t2iModel.provider, endpoint: t2iModel.endpoint, api_key: t2iModel.apiKey, model: t2iModel.model } : null,
      i2i_model: i2iModel ? { provider: i2iModel.provider, endpoint: i2iModel.endpoint, api_key: i2iModel.apiKey, model: i2iModel.model } : null,
      
      token: store.token,
      timestamp: Date.now(),
      ...extraParams // Merge extra params (like confirmed_command, disable_claude_tool)
    };

    // Check if Claude-related: either the model is Claude or it's a confirmed command execution
    const isClaudeRelated = (textModel && textModel.model && textModel.model.toLowerCase().includes('claude')) || 
                           (extraParams && extraParams.confirmed_command);

    const response = await fetch(`${getChatApiUrl(textModel.endpoint, isClaudeRelated)}?action=chat`, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload),
      signal: signal
    });

    if (!response.ok) {
      if (response.status === 401) {
        throw new Error('Unauthorized: Invalid or missing token.');
      }
      throw new Error(`Network error: ${response.status} ${response.statusText}`);
    }

    if (!response.body) {
      throw new Error('ReadableStream not supported in this browser.');
    }

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '';

    // eslint-disable-next-line no-constant-condition
    while (true) {
      const { done, value } = await reader.read();
      
      if (done) {
        break;
      }

      const chunk = decoder.decode(value, { stream: true });
      buffer += chunk;
      
      // Process complete lines
      const lines = buffer.split('\n');
      // The last element is the potentially incomplete line, keep it in buffer
      buffer = lines.pop() || ''; 

      for (const line of lines) {
        const trimmedLine = line.trim();
        if (!trimmedLine || !trimmedLine.startsWith('data:')) continue;

        const dataStr = trimmedLine.substring(5).trim(); // Remove 'data:' prefix

        if (dataStr === '[DONE]') {
          streamFinished = true;
          // If queue is empty, finish immediately; otherwise processQueue will handle it
          if (!isTyping && typingQueue.length === 0 && onDone) {
            onDone();
          }
          return;
        }

        try {
          const data = JSON.parse(dataStr);
          
          // Protocol: 
          // { type: 'think', content: '...' } -> Thinking process
          // { type: 'text', content: '...' } -> Final answer
          
          if (data.chat_id) {
            if (onChatId) onChatId(data.chat_id);
          }

          if (data.type === 'think') {
            enqueue('think', data.content);
          } else if (data.type === 'text') {
            enqueue('text', data.content);
          } else if (data.type === 'image_start') {
            if (onImageStart) onImageStart();
          } else if (data.type === 'decision') {
             if (onDecision) onDecision(data.content);
          } else if (data.error && onError) {
            streamFinished = true;
            typingQueue = [];
            onError(new Error(data.error));
            return;
          }
        } catch (e) {
          console.warn('Failed to parse SSE JSON:', dataStr);
        }
      }
    }
    
    // Fallback if [DONE] is not sent but stream closes
    streamFinished = true;
    if (!isTyping && typingQueue.length === 0 && onDone) {
      onDone();
    }

  } catch (err) {
    streamFinished = true;
    typingQueue = []; // Clear queue on error
    if (onError) onError(err);
    console.error('Stream Chat Error:', err);
  }
}

/**
 * Updates Soul, User, and Memory via Backend AI
 * 
 * @param {Object} data - { soul, user, memory, history }
 * @returns {Promise<Object>} - { soul, user, memory }
 */
export async function updateMemory(data) {
  const headers = {
    'Content-Type': 'application/json'
  };

  // Get active model config
  const activeModel = store.settings.models.find(m => m.id === store.settings.activeModelId) || store.settings.models[0];

  const payload = {
    ...data,
    token: store.token,
    location_host: window.location.host,
    endpoint: activeModel.endpoint,
    api_key: activeModel.apiKey,
    model: activeModel.model
  };

  try {
    const response = await fetch(`${getApiUrl()}?action=update_memory`, {
      method: 'POST',
      headers,
      body: JSON.stringify(payload)
    });

    if (!response.ok) {
      throw new Error(`Memory Update Failed: ${response.statusText}`);
    }

    const result = await response.json();
    if (result.error) {
        throw new Error(result.error);
    }
    return result;
  } catch (error) {
    console.error('Failed to update memory:', error);
    return null; // Fail silently or handle in UI
  }
}

