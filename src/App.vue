<template>
  <div id="app">
    <ChatLayout />
  </div>
</template>

<script>
import ChatLayout from './components/ChatLayout.vue';

export default {
  name: 'App',
  components: {
    ChatLayout
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
