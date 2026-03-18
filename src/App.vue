<template>
  <div id="app">
    <ChatLayout />
  </div>
</template>

<script>
import ChatLayout from './components/ChatLayout.vue';
import { SERVERS, getServerKey } from '@/api/config';

export default {
  name: 'App',
  components: {
    ChatLayout
  },
  async mounted() {
    console.log('App mounted, checking server speed preference...');
    this.checkServerSpeed();
    
    // Listen for manual trigger from config.js
    window.addEventListener('aihelp-check-server-speed', this.checkServerSpeed);
  },
  destroyed() {
    window.removeEventListener('aihelp-check-server-speed', this.checkServerSpeed);
  },
  methods: {
    async checkServerSpeed() {
      // 1. Determine Target URL (Active Model Endpoint)
      let targetUrl = 'https://www.google.com'; // Default fallback
      try {
        const settings = this.$store.state.settings;

        if (settings && settings.models) {
          const activeModelId = settings.activeTextModelId || settings.activeModelId;
          const activeModel = settings.models.find(m => m.id === activeModelId);
          if (activeModel && activeModel.endpoint) {
            targetUrl = activeModel.endpoint;
          }
        }
      } catch (e) {
        console.warn('Could not retrieve active model endpoint, using default.', e);
      }
      
      console.log('Speed test target URL:', targetUrl);

      // 2. Generate Key based on Target URL
      const storageKey = getServerKey(targetUrl);
      const stored = localStorage.getItem(storageKey);

      // 3. Check if we need to run test
      if (!stored) {
        console.log(`No preference found for ${targetUrl} (key: ${storageKey}). Testing server speeds...`);
        
        const tests = SERVERS.map(async (server) => {
          try {
            // Test the server's connection speed to the target
            const response = await fetch(`https://${server}/AiAssistantv2.php?action=speed_test&url=${encodeURIComponent(targetUrl)}`);
            const data = await response.json();
            
            if (data.status === 'success') {
               // Prefer server's reported connection time (total_time is in seconds)
               return { server, time: data.total_time };
            }
            return { server, time: 9999 };
          } catch (e) {
            console.error(`Speed test failed for ${server}`, e);
            return { server, time: 9999 };
          }
        });

        const results = await Promise.all(tests);
        // Sort by time ascending
        results.sort((a, b) => a.time - b.time);
        
        const winner = results[0].server;
        console.log('Selected fastest server:', winner, results);
        localStorage.setItem(storageKey, winner);
      } else {
          console.log(`Using cached server for ${targetUrl}:`, stored);
      }
    }
  }
}
</script>

<style>
/* Global Reset & Typography */
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}
.markdown-body pre>code{
      white-space: break-spaces !important;
}

.markdown-body pre{
  margin-bottom:0px !important;
  padding:10px !important;
      background-color: transparent;
    overflow-x:auto !important;
}

.markdown-body table{
      width: 100%;
    overflow-x: auto;
}

.markdown-body {
  font-size:unset !important
}


.katex-display{
  overflow-x: auto;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  background-color: var(--bg-main);
  color: var(--text-primary);
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* CSS Variables System */
:root {
  /* Semantic Font Sizes - No PX allowed in components */
  --font-small: 0.875rem;   /* 14px */
  --font-medium: 1rem;      /* 16px */
  --font-large: 1.25rem;    /* 20px */
  --font-xl: 1.5rem;        /* 24px */

  /* Light Theme (iOS Style - Clean & Airy) */
  --bg-main: #f2f2f7; /* iOS System Gray 6 */
  --bg-surface: rgba(255, 255, 255, 0.75); /* Blur base */
  --bg-input: rgba(255, 255, 255, 0.8);
  --bg-message-user: #007aff; /* iOS Blue */
  --bg-message-assistant: #ffffff;
  
  --primary-color: #007aff;
  --primary-gradient: linear-gradient(135deg, #007aff 0%, #00c6ff 100%);
  --accent-color: #5856d6;
  --accent-hover: #4a48b8;
  
  --text-primary: #000000;
  --text-secondary: #8e8e93; /* iOS Gray */
  --text-on-primary: #ffffff;
  
  --border-color: transparent; /* Removed lines as requested */
  --shadow-soft: 0 8px 40px rgba(0, 0, 0, 0.08);
  --shadow-card: 0 12px 48px -12px rgba(0, 0, 0, 0.12);
  --radius-lg: 24px;
  --radius-md: 18px;
  --radius-sm: 12px;
}

[data-theme="dark"] {
  /* Night Mode (iOS Dark) */
  --bg-main: #000000;
  --bg-surface: rgba(28, 28, 30, 0.75); /* iOS System Gray 6 Dark */
  --bg-input: rgba(44, 44, 46, 0.8);
  --bg-message-user: #0a84ff;
  --bg-message-assistant: #1c1c1e;

  --primary-color: #0a84ff;
  --primary-gradient: linear-gradient(135deg, #0a84ff 0%, #00d4ff 100%);
  --accent-color: #5e5ce6;
  --accent-hover: #7d7aff;
  
  --text-primary: #ffffff;
  --text-secondary: #98989d;
  --text-on-primary: #ffffff;
  
  --border-color: transparent;
  --shadow-soft: 0 8px 40px rgba(0, 0, 0, 0.4);
  --shadow-card: 0 12px 48px -12px rgba(0, 0, 0, 0.6);
}



/* Utilities */
.text-small { font-size: var(--font-small); }
.text-medium { font-size: var(--font-medium); }
.text-large { font-size: var(--font-large); }

button {
  font-family: inherit;
}

textarea {
  font-family: inherit;
}
</style>
