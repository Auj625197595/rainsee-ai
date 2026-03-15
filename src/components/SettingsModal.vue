<template>
  <div class="modal-overlay" v-if="isOpen" @click.self="close">
    <div class="modal-container">
      <div class="modal-sidebar">
        <div class="modal-header">
          <h3>设置</h3>
        </div>
        <ul class="settings-nav">
          <li :class="{ active: activeSettingTab === 'general' }" @click="activeSettingTab = 'general'">
            <span>常规</span>
          </li>
          <li :class="{ active: activeSettingTab === 'model' }" @click="activeSettingTab = 'model'">
            <span>模型</span>
          </li>
          <li :class="{ active: activeSettingTab === 'backup' }" @click="activeSettingTab = 'backup'">
            <span>备份</span>
          </li>
        </ul>
      </div>
      <div class="modal-content">
        <div class="modal-close-btn" @click="close">
          <svg viewBox="0 0 24 24" width="20" height="20"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </div>
        
        <div v-if="activeSettingTab === 'general'" class="settings-panel">
          <h4>常规设置</h4>
          <div class="form-group">
            <label>主题</label>
            <select :value="theme" @change="setTheme($event.target.value)">
              <option value="light">浅色</option>
              <option value="dark">深色</option>
            </select>
          </div>
        </div>

        <div v-if="activeSettingTab === 'model'" class="settings-panel">
          <div class="settings-header">
            <h4>模型配置</h4>
            <button v-if="!editingModelId" class="btn-text" @click="startAddModel">+ 添加模型</button>
          </div>
          
          <!-- Model List -->
          <div v-if="!editingModelId" class="model-list-container">
            <!-- Text Generation Models -->
            <div class="model-type-section">
              <h5>文字合成模型 (Text Generation)</h5>
              <div class="model-list">
                <div v-for="model in store.settings.models.filter(m => m.type === 'text' || !m.type)" 
                     :key="model.id" class="model-item" 
                     :class="{ active: store.settings.activeTextModelId === model.id }" 
                     @click="selectModelByType(model.id, 'text')">
                  <div class="model-info">
                    <div class="model-name">{{ model.name }}</div>
                    <div class="model-detail">{{ model.model }} | {{ model.endpoint }}</div>
                  </div>
                  <div class="model-actions">
                    <button class="icon-btn-small" @click.stop="startEditModel(model)" title="编辑">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="icon-btn-small delete-btn" @click.stop="confirmDeleteModel(model.id)" title="删除" :disabled="store.settings.models.length <= 1">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- T2I Models -->
            <div class="model-type-section">
              <h5>文生图模型 (Text-to-Image)</h5>
              <div class="model-list">
                <div v-for="model in store.settings.models.filter(m => m.type === 't2i')" 
                     :key="model.id" class="model-item" 
                     :class="{ active: store.settings.activeT2iModelId === model.id }" 
                     @click="selectModelByType(model.id, 't2i')">
                  <div class="model-info">
                    <div class="model-name">{{ model.name }}</div>
                    <div class="model-detail">{{ model.model }} | {{ model.endpoint }}</div>
                  </div>
                  <div class="model-actions">
                    <button class="icon-btn-small" @click.stop="startEditModel(model)" title="编辑">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="icon-btn-small delete-btn" @click.stop="confirmDeleteModel(model.id)" title="删除" :disabled="store.settings.models.length <= 1">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </div>
                <div v-if="store.settings.models.filter(m => m.type === 't2i').length === 0" class="empty-hint">
                  暂无文生图模型，请点击“添加模型”并选择类型。
                </div>
              </div>
            </div>

            <!-- I2I Models -->
            <div class="model-type-section">
              <h5>图生图模型 (Image-to-Image)</h5>
              <div class="model-list">
                <div v-for="model in store.settings.models.filter(m => m.type === 'i2i')" 
                     :key="model.id" class="model-item" 
                     :class="{ active: store.settings.activeI2iModelId === model.id }" 
                     @click="selectModelByType(model.id, 'i2i')">
                  <div class="model-info">
                    <div class="model-name">{{ model.name }}</div>
                    <div class="model-detail">{{ model.model }} | {{ model.endpoint }}</div>
                  </div>
                  <div class="model-actions">
                    <button class="icon-btn-small" @click.stop="startEditModel(model)" title="编辑">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="icon-btn-small delete-btn" @click.stop="confirmDeleteModel(model.id)" title="删除" :disabled="store.settings.models.length <= 1">
                      <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </div>
                <div v-if="store.settings.models.filter(m => m.type === 'i2i').length === 0" class="empty-hint">
                  暂无图生图模型，请点击“添加模型”并选择类型。
                </div>
              </div>
            </div>
          </div>

          <!-- Model Editor Form -->
          <div v-else class="model-editor">
            <div class="form-group">
              <label>供应商 (Provider)</label>
              <select v-model="modelEditForm.provider" @change="handleProviderChange">
                <option value="">通用转发</option>
                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>模型类型 (Model Type)</label>
              <select v-model="modelEditForm.type" @change="handleTypeChange">
                <option v-for="t in filteredModelTypes" :key="t.id" :value="t.id">{{ t.name }}</option>
              </select>
            </div>
            <div class="form-group">
              <label>配置名称</label>
              <input type="text" v-model="modelEditForm.name" placeholder="例如：OpenAI GPT-4">
            </div>
            <div class="form-group">
              <label>API 终端 (Endpoint)</label>
              <input type="text" v-model="modelEditForm.endpoint" placeholder="https://api.openai.com/v1/chat/completions">
              <small>兼容 OpenAI API 格式</small>
            </div>
            <div class="form-group">
              <label>API Key</label>
              <input type="password" v-model="modelEditForm.apiKey" placeholder="sk-...">
            </div>
            <div class="form-group">
              <label>模型名称 (Model Name)</label>
              <input type="text" v-model="modelEditForm.model" placeholder="gpt-4">
            </div>
            <div class="editor-footer">
              <button class="btn-secondary" @click="cancelEditModel">返回</button>
              <button class="btn-primary" @click="saveModel">保存模型</button>
            </div>
          </div>
        </div>

        <div v-if="activeSettingTab === 'backup'" class="settings-panel">
          <h4>备份</h4>
          <div class="backup-actions">
            <div class="backup-section">
              <h5>数据导出</h5>
              <p>将当前的所有聊天记录、设置、以及记忆系统数据导出为 JSON 文件。</p>
              <button class="btn-primary" @click="exportData">导出完整备份</button>
            </div>
            
            <div class="backup-section">
              <h5>数据导入</h5>
              <p>从 JSON 备份文件中恢复聊天记录和设置。这将会覆盖当前的本地数据。</p>
              <button class="btn-secondary" @click="$refs.backupInput.click()">选择备份文件导入</button>
              <input type="file" ref="backupInput" @change="importData" style="display: none" accept=".json">
            </div>

            <div class="backup-section danger-zone">
              <h5>危险区域</h5>
              <p>清除所有本地存储的数据，包括聊天记录、设置、IndexedDB 中的记忆。此操作不可撤销。</p>
              <button class="btn-danger" @click="resetAllData">清除所有本地数据</button>
            </div>
          </div>
        </div>

        <div v-if="!editingModelId" class="modal-footer">
          <button class="btn-secondary" @click="close">取消</button>
          <button class="btn-primary" @click="saveSettings">保存更改</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { store, mutations } from '../store';
import { saveAs } from 'file-saver';
import providersData from '../config/providers.json';

export default {
  name: 'SettingsModal',
  props: {
    isOpen: {
      type: Boolean,
      required: true
    }
  },
  emits: ['close'],
  data() {
    return {
      activeSettingTab: 'model',
      editingModelId: null, // null = list, 'new' = add, ID = edit
      modelEditForm: {
        name: '',
        provider: '',
        type: 'text',
        endpoint: '',
        apiKey: '',
        model: ''
      },
      modelTypes: [
        { id: 'text', name: '文字生成' },
        { id: 't2i', name: '文生图' },
        { id: 'i2i', name: '图生图' }
      ],
      providers: providersData,
      localSettings: {
        endpoint: '',
        apiKey: '',
        model: '',
        thinkingEnabled: false,
        webSearchEnabled: false,
        imageGenEnabled: false
      }
    };
  },
  computed: {
    store() {
      return store;
    },
    theme() {
      return store.theme;
    },
    filteredModelTypes() {
      if (!this.modelEditForm.provider) return this.modelTypes;
      const provider = this.providers.find(p => p.id === this.modelEditForm.provider);
      if (!provider) return this.modelTypes;
      return this.modelTypes.filter(t => !!provider.configs[t.id]);
    }
  },
  watch: {
    isOpen(newVal) {
      if (newVal) {
        // Reset editing state
        this.editingModelId = null;
        // Load general settings
        this.localSettings = { ...store.settings };
      }
    }
  },
  methods: {
    close() {
      this.$emit('close');
    },
    saveSettings() {
      // For general settings (theme, etc)
      mutations.setSettings({
        thinkingEnabled: this.localSettings.thinkingEnabled,
        webSearchEnabled: this.localSettings.webSearchEnabled,
        imageGenEnabled: this.localSettings.imageGenEnabled
      });
      this.close();
    },
    setTheme(theme) {
      mutations.setTheme(theme);
    },
    // Model Management Methods
    selectModel(id) {
      mutations.setActiveModel(id);
    },
    selectModelByType(id, type) {
      mutations.setActiveModelByType(id, type);
    },
    startAddModel() {
      this.editingModelId = 'new';
      this.modelEditForm = {
        name: '',
        provider: '',
        type: 'text',
        endpoint: '',
        apiKey: '',
        model: ''
      };
    },
    startEditModel(model) {
      this.editingModelId = model.id;
      this.modelEditForm = {
        provider: '', // Default to empty, will be loaded from model if exists
        type: 'text', // Default to text
        ...model
      };
    },
    handleProviderChange() {
      if (!this.modelEditForm.provider) return;
      
      const provider = this.providers.find(p => p.id === this.modelEditForm.provider);
      if (provider) {
        // Auto-fill name if empty
        this.modelEditForm.name = this.modelEditForm.name || provider.name;
        
        // Check if current type is supported by the provider, if not default to 'text'
        if (this.modelEditForm.type && !provider.configs[this.modelEditForm.type]) {
          this.modelEditForm.type = 'text';
        }
        
        // Use current type or default to 'text'
        const type = this.modelEditForm.type || 'text';
        const config = provider.configs[type] || provider.configs['text'];
        
        if (config) {
          this.modelEditForm.endpoint = config.endpoint;
          this.modelEditForm.model = config.model;
        }
      }
    },
    handleTypeChange() {
      // If a provider is selected, update endpoint and model based on the new type
      if (this.modelEditForm.provider) {
        const provider = this.providers.find(p => p.id === this.modelEditForm.provider);
        if (provider) {
          const config = provider.configs[this.modelEditForm.type];
          if (config) {
            this.modelEditForm.endpoint = config.endpoint;
            this.modelEditForm.model = config.model;
          }
        }
      } else {
        // Handle custom provider defaults for different types if needed
        if (this.modelEditForm.type === 'text') {
          this.modelEditForm.endpoint = this.modelEditForm.endpoint || 'https://api.openai.com/v1/chat/completions';
          this.modelEditForm.model = this.modelEditForm.model || 'gpt-4';
        }
      }
    },
    cancelEditModel() {
      this.editingModelId = null;
    },
    saveModel() {
      if (!this.modelEditForm.name || !this.modelEditForm.endpoint || !this.modelEditForm.model) {
        alert('Please fill in all required fields (Name, Endpoint, Model Name).');
        return;
      }

      if (this.editingModelId === 'new') {
        mutations.addModel(this.modelEditForm);
      } else {
        mutations.updateModel(this.editingModelId, this.modelEditForm);
      }
      this.editingModelId = null;
    },
    confirmDeleteModel(id) {
      if (confirm('确定要删除这个模型配置吗？')) {
        mutations.deleteModel(id);
      }
    },
    // Backup & Restore Methods
    async exportData() {
      try {
        const backup = await mutations.getFullBackup();
        const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' });
        const date = new Date().toISOString().split('T')[0];
        saveAs(blob, `ai-assistant-backup-${date}.json`);
      } catch (e) {
        console.error('Export failed', e);
        alert('导出失败: ' + e.message);
      }
    },
    async importData(event) {
      const file = event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = async (e) => {
        try {
          const backup = JSON.parse(e.target.result);
          if (confirm('导入备份将会覆盖当前所有数据。确定要继续吗？')) {
            await mutations.restoreFromBackup(backup);
            alert('导入成功，页面将刷新以应用更改。');
            window.location.reload();
          }
        } catch (err) {
          console.error('Import failed', err);
          alert('导入失败: 备份文件格式不正确。');
        }
      };
      reader.readAsText(file);
      event.target.value = ''; // Reset file input
    },
    async resetAllData() {
      if (confirm('警告：此操作将永久清除所有本地数据（聊天记录、模型配置、记忆等）。此操作不可撤销！确定要继续吗？')) {
        localStorage.clear();
        const { memoryDB } = await import('../utils/db');
        await memoryDB.clearAll();
        alert('所有数据已清除，页面将刷新。');
        window.location.reload();
      }
    }
  }
};
</script>

<style scoped>
/* Settings Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(4px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: fadeIn 0.2s ease-out;
}

[data-theme="dark"] .modal-overlay {
  background-color: rgba(0, 0, 0, 0.6);
}

.modal-container {
  width: 90%;
  max-width: 800px;
  height: 80vh;
  max-height: 600px;
  background: #fff;
  border-radius: 16px;
  box-shadow: 0 20px 50px rgba(0,0,0,0.15);
  display: flex;
  overflow: hidden;
  border: 1px solid rgba(0,0,0,0.05);
}

[data-theme="dark"] .modal-container {
  background: #1a1a1a;
  border: 1px solid rgba(255,255,255,0.05);
  box-shadow: 0 20px 50px rgba(0,0,0,0.4);
}

.modal-sidebar {
  background-color: #f9f9f9;
  border-right: 1px solid rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
}

[data-theme="dark"] .modal-sidebar {
  background-color: #111;
  border-right: 1px solid rgba(255,255,255,0.05);
}

.modal-header {
  padding: 1.5rem;
}

.modal-header h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0;
}

.settings-nav {
  list-style: none;
  padding: 0 0.5rem;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.settings-nav li {
  padding: 0.75rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9rem;
  color: var(--text-secondary);
  transition: all 0.2s;
}

.settings-nav li:hover {
  background-color: rgba(0,0,0,0.03);
  color: var(--text-primary);
}

[data-theme="dark"] .settings-nav li:hover {
  background-color: rgba(255,255,255,0.03);
}

.settings-nav li.active {
  background-color: #000;
  color: #fff;
  font-weight: 500;
}

[data-theme="dark"] .settings-nav li.active {
  background-color: #fff;
  color: #000;
}

.modal-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  position: relative;
}

.modal-close-btn {
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem;
  cursor: pointer;
  border-radius: 50%;
  transition: background 0.2s;
  color: var(--text-secondary);
}

.modal-close-btn:hover {
  background-color: rgba(0,0,0,0.05);
  color: var(--text-primary);
}

[data-theme="dark"] .modal-close-btn:hover {
  background-color: rgba(255,255,255,0.1);
}

.settings-panel h4 {
  margin-top: 0;
  margin-bottom: 1.5rem;
  font-size: 1.2rem;
  font-weight: 600;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.9rem;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 0.6rem 0.8rem;
  border: 1px solid rgba(0,0,0,0.1);
  border-radius: 8px;
  font-family: inherit;
  font-size: 0.95rem;
  background: transparent;
  color: var(--text-primary);
  transition: border-color 0.2s;
}

[data-theme="dark"] .form-group input,
[data-theme="dark"] .form-group select {
  border-color: rgba(255,255,255,0.1);
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #000;
}

[data-theme="dark"] .form-group input:focus,
[data-theme="dark"] .form-group select:focus {
  border-color: #fff;
}

/* Model List in Settings */
.model-list-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.model-type-section h5 {
  margin-top: 0;
  margin-bottom: 0.75rem;
  font-size: 0.85rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.empty-hint {
  padding: 1rem;
  text-align: center;
  font-size: 0.85rem;
  color: var(--text-secondary);
  border: 1px dashed rgba(0,0,0,0.1);
  border-radius: 8px;
}

[data-theme="dark"] .empty-hint {
  border-color: rgba(255,255,255,0.1);
}

.settings-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.btn-text {
  background: none;
  border: none;
  color: var(--primary-color);
  font-weight: 500;
  cursor: pointer;
}

.model-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.model-item {
  padding: 0.75rem;
  border: 1px solid rgba(0,0,0,0.05);
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  transition: all 0.2s;
}

.model-item:hover {
  background-color: rgba(0,0,0,0.02);
}

[data-theme="dark"] .model-item:hover {
  background-color: rgba(255,255,255,0.02);
}

.model-item.active {
  border-color: #000;
  background-color: rgba(0,0,0,0.02);
}

[data-theme="dark"] .model-item.active {
  border-color: #fff;
  background-color: rgba(255,255,255,0.02);
}

.model-name {
  font-weight: 600;
  font-size: 0.9rem;
}

.model-detail {
  word-break: break-all;
  font-size: 0.8rem;
  color: var(--text-secondary);
  margin-top: 2px;
}

.model-actions {
  display: flex;
  gap: 0.25rem;
}

.icon-btn-small {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: var(--text-secondary);
  border-radius: 4px;
}

.icon-btn-small:hover {
  background-color: rgba(0,0,0,0.05);
  color: #000;
}

[data-theme="dark"] .icon-btn-small:hover {
  background-color: rgba(255,255,255,0.1);
  color: #fff;
}

.icon-btn-small.delete-btn:hover {
  color: #ef4444;
  background-color: rgba(239, 68, 68, 0.1);
}

/* Backup Actions */
.backup-section {
  padding: 1rem;
  border: 1px solid rgba(0,0,0,0.05);
  border-radius: 8px;
  margin-bottom: 1rem;
}

[data-theme="dark"] .backup-section {
  border-color: rgba(255,255,255,0.05);
}

.backup-section h5 {
  margin: 0 0 0.5rem 0;
  font-size: 1rem;
}

.backup-section p {
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.danger-zone {
  border-color: rgba(239, 68, 68, 0.2);
  background-color: rgba(239, 68, 68, 0.02);
}

[data-theme="dark"] .danger-zone {
  background-color: rgba(239, 68, 68, 0.05);
}

.btn-primary, .btn-secondary, .btn-danger {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
}

.btn-primary {
  background: #000;
  color: #fff;
}

[data-theme="dark"] .btn-primary {
  background: #fff;
  color: #000;
}

.btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn-secondary {
  background: rgba(0,0,0,0.05);
  color: var(--text-primary);
}

[data-theme="dark"] .btn-secondary {
  background: rgba(255,255,255,0.1);
}

.btn-secondary:hover {
  background: rgba(0,0,0,0.1);
}

[data-theme="dark"] .btn-secondary:hover {
  background: rgba(255,255,255,0.15);
}

.btn-danger {
  background: #ef4444;
  color: #fff;
}

.btn-danger:hover {
  background: #dc2626;
}

.modal-footer {
  padding-top: 1.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  border-top: 1px solid rgba(0,0,0,0.05);
  margin-top: auto;
}

[data-theme="dark"] .modal-footer {
  border-top-color: rgba(255,255,255,0.05);
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>