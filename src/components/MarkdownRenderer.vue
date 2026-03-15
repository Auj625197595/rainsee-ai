<template>
  <div class="markdown-body" v-html="renderedContent" @click="handleClick"></div>
</template>

<script>
import MarkdownIt from 'markdown-it';
import markdownItKatex from 'markdown-it-katex';
import hljs from 'highlight.js';
import 'highlight.js/styles/atom-one-dark.css';
import 'katex/dist/katex.min.css';

// Shared instance to avoid recreating it for every component
const md = new MarkdownIt({
  html: true,
  linkify: true,
  typographer: true,
  highlight: (str, lang) => {
    let highlighted = '';
    if (lang && hljs.getLanguage(lang)) {
      try {
        highlighted = hljs.highlight(str, { language: lang, ignoreIllegals: true }).value;
      } catch (__) {
        highlighted = md.utils.escapeHtml(str);
      }
    } else {
      highlighted = md.utils.escapeHtml(str);
    }
    return '<div class="code-block-wrapper"><button class="copy-code-btn">复制</button><pre class="hljs"><code>' + highlighted + '</code></pre></div>';
  }
}).use(markdownItKatex);

// Configure link renderer
const defaultLinkRender = md.renderer.rules.link_open || function(tokens, idx, options, env, self) {
  return self.renderToken(tokens, idx, options);
};

md.renderer.rules.link_open = function (tokens, idx, options, env, self) {
  const aIndex = tokens[idx].attrIndex('target');
  if (aIndex < 0) {
    tokens[idx].attrPush(['target', '_blank']);
  } else {
    tokens[idx].attrs[aIndex][1] = '_blank';
  }
  const relIndex = tokens[idx].attrIndex('rel');
  if (relIndex < 0) {
    tokens[idx].attrPush(['rel', 'noopener noreferrer']);
  } else {
    tokens[idx].attrs[relIndex][1] = 'noopener noreferrer';
  }
  return defaultLinkRender(tokens, idx, options, env, self);
};

export default {
  name: 'MarkdownRenderer',
  props: {
    content: {
      type: String,
      default: ''
    }
  },
  data() {
    return {
      renderedContent: '',
      renderTimer: null,
      stableHtml: '',
      lastCommittedSource: ''
    };
  },
  watch: {
    content: {
      immediate: true,
      handler(newVal) {
        if (!newVal) {
            this.renderedContent = '';
            this.stableHtml = '';
            this.lastCommittedSource = '';
            this.clearTimer();
            return;
        }
        
        // If content reset or non-append update (e.g. history load), full render immediately
        if (newVal.length < this.lastCommittedSource.length || !newVal.startsWith(this.lastCommittedSource)) {
            this.doFullRender(newVal);
            return;
        }

        // Calculate the new part (diff)
        const diff = newVal.slice(this.lastCommittedSource.length);
        if (!diff) return;

        // Simple text escape for the preview (append to HTML)
        const escapedDiff = diff
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\n/g, "<br/>");
        
        // Append to current view
        this.renderedContent = this.stableHtml + escapedDiff;

        // Schedule full render (Throttle)
        if (!this.renderTimer) {
            this.renderTimer = setTimeout(() => {
                this.doFullRender(this.content);
            }, 1000);
        }
      }
    }
  },
  beforeDestroy() {
    this.clearTimer();
  },
  methods: {
    doFullRender(text) {
      //console.log("doFullRender",text);
        this.clearTimer();
        const html = md.render(text);
        this.stableHtml = html;
        this.lastCommittedSource = text;
        this.renderedContent = html;
    },
    clearTimer() {
        if (this.renderTimer) {
            clearTimeout(this.renderTimer);
            this.renderTimer = null;
        }
    },
    handleClick(e) {
      // Delegate copy code event
      const target = e.target;
      if (target.classList.contains('copy-code-btn')) {
        const wrapper = target.parentElement;
        const pre = wrapper.querySelector('pre');
        const codeBlock = pre ? pre.querySelector('code') : null;
        if (codeBlock) {
          const text = codeBlock.innerText || codeBlock.textContent;
          // Emit event to parent to handle copy (or handle locally if simple)
          this.$emit('copy-code', text, target);
        }
      }
    }
  }
};
</script>

<style scoped>
/* Scoped styles if needed, but markdown-body usually relies on global or deep styles */
</style>