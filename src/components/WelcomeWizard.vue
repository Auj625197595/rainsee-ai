<template>
  <div class="wizard-overlay">
    <div class="wizard-bg-glow"></div>
    <div class="wizard-container">

      <!-- Step 1: Introduction -->
      <div v-if="step === 1" class="wizard-step step-intro">
        <a href="https://github.com/Auj625197595/rainsee-ai" target="_blank" class="github-link-badge">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
            <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
          </svg>
          <span>GitHub 开源项目</span>
        </a>
        <div class="intro-header">
          <div class="logo-container">
            <div class="logo-glow"></div>
            <div class="logo-ring"></div>
            <div class="logo-icon">
              <svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
              </svg>
            </div>
          </div>
          <h2 class="hero-title">欢迎使用 <span class="gradient-text">AI 客户端</span></h2>
          <p class="hero-subtitle">纯净 · 安全 · 私有化</p>
        </div>

        <div class="info-card">
          <p class="info-text">
            这是一个不直接提供 AI 服务的纯净客户端，您需要自行配置服务接口。
          </p>
          <a href="https://github.com/Auj625197595/rainsee-ai" target="_blank" class="github-repo-link">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor">
              <path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>
            </svg>
            <span>已开源: Auj625197595/rainsee-ai</span>
          </a>
        </div>

        <div class="feature-grid">
          <div class="feature-card">
            <div class="card-icon-wrapper memory-bg">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"></path>
                <path d="M8.5 8.5v.01"></path>
                <path d="M16 12v.01"></path>
                <path d="M12 16v.01"></path>
              </svg>
            </div>
            <div class="card-content">
              <h4>专属记忆</h4>
              <p>本地向量数据库，打造懂你的 AI</p>
            </div>
          </div>

          <div class="feature-card">
            <div class="card-icon-wrapper privacy-bg">
              <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
              </svg>
            </div>
            <div class="card-content">
              <h4>数据隐私</h4>
              <p>数据仅存储在本地，完全由您掌控</p>
            </div>
          </div>
        </div>

        <div class="capability-badges">
          <div class="badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 19l7-7 3 3-7 7-3-3z"></path>
              <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"></path>
              <path d="M2 2l7.586 7.586"></path>
              <circle cx="11" cy="11" r="2"></circle>
            </svg>
            <span>绘画支持</span>
          </div>
          <div class="badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>计划模式</span>
          </div>
          <div class="badge">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span>无限对话</span>
          </div>
        </div>

        <button class="start-btn" @click="nextStep">
          <span>开始配置</span>
          <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"></line>
            <polyline points="12 5 19 12 12 19"></polyline>
          </svg>
        </button>
      </div>

      <!-- Step 2: Configure Interface -->
      <div v-else-if="step === 2" class="wizard-step step-config">
        <div class="step-header">
          <h3>配置文字合成接口</h3>
          <p>为了开始使用，请配置一个基础的文字对话模型接口。</p>
        </div>

        <div class="config-form">
          <div class="form-group">
            <div class="provider-label-row">
              <label>供应商 (Provider)</label>
              <a v-if="currentProvider && currentProvider.course"
                 :href="currentProvider.course"
                 target="_blank"
                 class="tutorial-link"
                 title="查看配置教程">
                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <line x1="12" y1="16" x2="12" y2="12"></line>
                  <line x1="12" y1="8" x2="12.01" y2="8"></line>
                </svg>
                <span>查看教程</span>
              </a>
            </div>
            <select v-model="modelForm.provider" @change="handleProviderChange">
              <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
            </select>
          </div>

          <div class="form-group">
            <label>配置名称</label>
            <input type="text" v-model="modelForm.name" placeholder="例如：我的 GPT-4">
          </div>

          <div class="form-group">
            <label>API 终端 (Endpoint)</label>
            <input type="text" v-model="modelForm.endpoint" placeholder="https://api.openai.com/v1/chat/completions">
            <small class="hint">兼容 OpenAI API 格式</small>
          </div>

          <div class="form-group">
            <label>API Key</label>
            <input type="password" v-model="modelForm.apiKey" placeholder="sk-...">
          </div>

          <div class="form-group">
            <label>模型名称 (Model Name)</label>
            <input type="text" v-model="modelForm.model" placeholder="gpt-4">
          </div>
        </div>

        <div class="step-actions">
          <button class="btn-secondary" @click="step = 1">上一步</button>
          <button class="cool-btn" @click="saveAndStart" :disabled="!isValid">
            <span>开始使用</span>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { store, mutations } from '../store';
import providersData from '../config/providers.json';

export default {
  name: 'WelcomeWizard',
  data() {
    return {
      step: 1,
      providers: providersData,
      modelForm: {
        provider: '',
        name: 'My AI Model',
        endpoint: '',
        apiKey: '',
        model: '',
        type: 'text' // Fixed as text generation for the wizard
      }
    };
  },
  computed: {
    isValid() {
      return this.modelForm.name &&
          this.modelForm.endpoint &&
          this.modelForm.apiKey &&
          this.modelForm.model;
    },
    currentProvider() {
      return this.providers.find(p => p.id === this.modelForm.provider);
    }
  },
  methods: {
    nextStep() {
      this.step = 2;
    },
    handleProviderChange() {
      const provider = this.providers.find(p => p.id === this.modelForm.provider);
      if (provider && provider.configs && provider.configs.text) {
        this.modelForm.endpoint = provider.configs.text.endpoint;
        this.modelForm.model = provider.configs.text.model || '';
      }
    },
    saveAndStart() {
      if (!this.isValid) return;

      const newModel = {
        id: Date.now().toString(),
        ...this.modelForm
      };

      // Add the model to the store
      mutations.addModel(newModel);

      // Set as active text model
      mutations.setActiveModel(newModel.id, 'text');

      // Emit event to close wizard (parent should handle this based on store state change)
      this.$emit('finished');
    }
  }
};
</script>

<style scoped>
.wizard-overlay {
  position: fixed;

  top: 0;
  left: 0;
  bottom:0;
  right:0;
  background-color: var(--bg-main);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  overflow-y: auto;
  backdrop-filter: blur(10px);
}

.wizard-bg-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 600px;
  height: 600px;
  background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
  opacity: 0.15;
  pointer-events: none;
}

.wizard-container {
  background-color: var(--bg-surface);
  border: 1px solid var(--border-color);
  border-radius: 24px;
  padding: 40px;
  width: 90%;
  max-width: 600px;
  box-shadow: var(--shadow-card);
  position: relative;
  z-index: 10;
  display: flex;
  flex-direction: column;
  min-height: 500px;
}

.wizard-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex: 1;
  animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Intro Header */
.intro-header {
  text-align: center;
  margin-bottom: 32px;
  position: relative;
}

.logo-container {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.logo-glow {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 120%;
  height: 120%;
  background: radial-gradient(circle, var(--accent-color) 0%, transparent 70%);
  opacity: 0.4;
  filter: blur(10px);
}

.logo-ring {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border-radius: 50%;
  border: 2px solid var(--accent-color);
  opacity: 0.2;
  animation: ringPulse 3s infinite;
}

@keyframes ringPulse {
  0% { transform: scale(0.9); opacity: 0.3; }
  50% { transform: scale(1.1); opacity: 0.1; }
  100% { transform: scale(0.9); opacity: 0.3; }
}

.logo-icon {
  position: relative;
  z-index: 2;
  color: var(--accent-color);
  filter: drop-shadow(0 0 8px rgba(0,0,0, 0.2));
}

.hero-title {
  font-size: 32px;
  font-weight: 800;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
  color: var(--text-primary);
}

.gradient-text {
  background: linear-gradient(135deg, var(--accent-color) 0%, #a855f7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-subtitle {
  font-size: 16px;
  color: var(--text-secondary);
  font-weight: 500;
  opacity: 0.8;
  margin: 0;
}

/* Info Card */
.info-card {
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 16px;
  margin-bottom: 32px;
  text-align: center;
}

.info-text {
  font-size: 14px;
  color: var(--text-secondary);
  line-height: 1.6;
  margin: 0;
}

.highlight {
  color: var(--accent-color);
  font-weight: 600;
}

/* Feature Grid */
.feature-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  width: 100%;
  margin-bottom: 32px;
}

.feature-card {
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: 16px;
  padding: 20px;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.feature-card:hover {
  transform: translateY(-4px);
  border-color: var(--accent-color);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
}

.card-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
  color: var(--text-primary);
}

.memory-bg {
  background: rgba(255, 99, 132, 0.1);
  color: #ff6384;
}

.privacy-bg {
  background: rgba(54, 162, 235, 0.1);
  color: #36a2eb;
}

.card-content h4 {
  margin: 0 0 8px 0;
  font-size: 15px;
  font-weight: 600;
  color: var(--text-primary);
}

.card-content p {
  margin: 0;
  font-size: 12px;
  color: var(--text-secondary);
  line-height: 1.4;
}

/* Capability Badges */
.capability-badges {
  display: flex;
  justify-content: center;
  gap: 12px;
  margin-bottom: 40px;
  flex-wrap: wrap;
}

.badge {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: var(--bg-input);
  border-radius: 20px;
  font-size: 12px;
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
  transition: all 0.2s;
}

.badge:hover {
  background: var(--bg-surface);
  border-color: var(--accent-color);
  color: var(--accent-color);
}

/* Buttons */
.start-btn {
  background: linear-gradient(135deg, var(--accent-color) 0%, #7c3aed 100%);
  color: white;
  border: none;
  padding: 16px 48px;
  border-radius: 30px;
  font-size: 18px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
  position: relative;
  overflow: hidden;
}

.start-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(124, 58, 237, 0.4);
}

.start-btn::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
  transform: translateX(-100%);
  transition: transform 0.5s;
}

.start-btn:hover::after {
  transform: translateX(100%);
}

.cool-btn {
  background: linear-gradient(135deg, var(--accent-color) 0%, #7c3aed 100%);
  color: white;
  border: none;
  padding: 12px 32px;
  border-radius: 30px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
}

.cool-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
}

.cool-btn.large-btn {
  padding: 14px 48px;
  font-size: 18px;
}

.cool-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

/* Step 2 Styles */
.github-link-badge {
  position: absolute;
  top: 20px;
  right: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: var(--bg-input);
  border: 1px solid var(--border-color);
  border-radius: 20px;
  color: var(--text-secondary);
  text-decoration: none;
  font-size: 13px;
  font-weight: 500;
  transition: all 0.3s ease;
  z-index: 100;
}

.github-link-badge:hover {
  background: var(--accent-color);
  border-color: var(--accent-color);
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
}

.github-link-badge svg {
  opacity: 0.9;
}

.github-repo-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 6px 12px;
  background: rgba(124, 58, 237, 0.1);
  color: var(--accent-color);
  border-radius: 6px;
  text-decoration: none;
  font-size: 12px;
  font-weight: 600;
  transition: all 0.2s;
}

.github-repo-link:hover {
  background: var(--accent-color);
  color: white;
}

.step-header {
  text-align: center;
  margin-bottom: 30px;
}

.step-header h3 {
  font-size: 24px;
  margin-bottom: 8px;
  color: var(--text-primary);
}

.step-header p {
  color: var(--text-secondary);
  margin: 0;
}

.config-form {
  width: 100%;
  flex: 1;
  overflow-y: auto;
  padding-right: 4px;
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: var(--text-primary);
  font-size: 14px;
}

.provider-label-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.provider-label-row label {
  margin-bottom: 0;
}

.tutorial-link {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 12px;
  color: var(--accent-color);
  text-decoration: none;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 12px;
  background-color: rgba(124, 58, 237, 0.1);
  transition: all 0.2s;
}

.tutorial-link:hover {
  background-color: var(--accent-color);
  color: white;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px 12px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background-color: var(--bg-primary);
  color: var(--text-primary);
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: var(--accent-color);
}

.form-group .hint {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: var(--text-secondary);
}

.step-actions {
  display: flex;
  justify-content: space-between;
  width: 100%;
  margin-top: auto;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}

.btn-secondary {
  background-color: transparent;
  border: 1px solid var(--border-color);
  color: var(--text-primary);
  padding: 10px 24px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 14px;
}

.btn-secondary:hover {
  background-color: var(--bg-input);
}
</style>
