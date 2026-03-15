<template>
  <div class="character-manager">
    <div class="manager-header">
      <h3>角色卡管理</h3>
      <div class="tabs">
        <button 
          :class="{ active: activeTab === 'local' }" 
          @click="activeTab = 'local'"
        >
          我的角色
        </button>
        <button 
          :class="{ active: activeTab === 'store' }" 
          @click="loadStore"
        >
          在线商店
        </button>
      </div>
      <button class="close-btn" @click="$emit('close')">
        <svg viewBox="0 0 24 24" width="24" height="24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
    </div>

    <div class="manager-content">
      <!-- Local Characters -->
      <div v-if="activeTab === 'local'" class="local-tab">
        <div class="actions-bar">
          <button class="btn-primary" @click="startCreate">
            <span>+ 创建角色</span>
          </button>
          <button class="btn-secondary" @click="triggerImport">
            <span>📥 导入</span>
          </button>
          <input type="file" ref="importInput" @change="handleImport" style="display: none" accept=".json">
        </div>

        <div class="roles-container">
          <!-- Custom Roles (Downloaded/Created) -->
          <div v-if="customRoles.length > 0" class="role-section">
            <div class="section-title">我的下载 / 自定义</div>
            
            <div v-for="(group, groupName) in customRolesByTag" :key="groupName" class="tag-group">
              <div class="group-label" v-if="groupName !== '未分类'">{{ groupName }}</div>
              <div class="cards-grid">
                <div 
                  v-for="role in group" 
                  :key="role.id" 
                  class="character-card custom"
                  :class="{ active: currentRoleId === role.id }"
                  @click="selectRole(role)"
                >
                  <div class="card-avatar">{{ role.icon }}</div>
                  <div class="card-info">
                    <div class="card-name">{{ role.name }}</div>
                    <div class="card-desc" :title="role.description">{{ role.description }}</div>
                    <div class="card-tags" v-if="role.tags && role.tags.length">
                      <span v-for="tag in role.tags.slice(0, 2)" :key="tag" class="tag-pill">{{ tag }}</span>
                    </div>
                  </div>
                  <div class="card-actions">
                    <button class="icon-btn-small" @click.stop="shareRole(role)" title="分享到商店">
                      <svg viewBox="0 0 24 24" width="14" height="14"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                    </button>
                    <button class="icon-btn-small" @click.stop="exportRole(role)" title="导出 JSON">
                      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    </button>
                    <button class="icon-btn-small" @click.stop="startEdit(role)" title="编辑">
                      <svg viewBox="0 0 24 24" width="14" height="14"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    <button class="icon-btn-small delete-btn" @click.stop="deleteRole(role.id)" title="删除">
                      <svg viewBox="0 0 24 24" width="14" height="14"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Default Roles -->
          <div class="role-section">
            <div class="section-title">系统默认</div>
            <div class="cards-grid">
              <div 
                v-for="role in visibleDefaultRoles" 
                :key="role.id" 
                class="character-card"
                :class="{ active: currentRoleId === role.id }"
                @click="selectRole(role)"
              >
                <div class="card-avatar">{{ role.icon }}</div>
                <div class="card-info">
                  <div class="card-name">{{ role.name }}</div>
                  <div class="card-desc" :title="role.description">{{ role.description }}</div>
                </div>
                <div class="card-actions">
                   <button class="icon-btn-small" @click.stop="toggleDefaultVisibility(role.id)" title="隐藏">
                     <svg viewBox="0 0 24 24" width="14" height="14"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
                   </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Hidden Roles Toggle -->
          <div v-if="hiddenRolesCount > 0" class="show-hidden-btn" @click="showHidden = !showHidden">
             {{ showHidden ? '隐藏已屏蔽的角色' : `显示 ${hiddenRolesCount} 个已屏蔽的角色` }}
          </div>
          
          <div v-if="showHidden" class="role-section">
             <div class="cards-grid">
               <div 
                  v-for="role in hiddenDefaultRoles" 
                  :key="role.id" 
                  class="character-card hidden-role"
                >
                  <div class="card-avatar">{{ role.icon }}</div>
                  <div class="card-info">
                    <div class="card-name">{{ role.name }}</div>
                  </div>
                  <div class="card-actions">
                     <button class="icon-btn-small" @click.stop="toggleDefaultVisibility(role.id)" title="恢复显示">
                       <svg viewBox="0 0 24 24" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8-11-8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                     </button>
                  </div>
                </div>
             </div>
          </div>

        </div>
      </div>

      <!-- Online Store -->
      <div v-else class="store-tab">
        <div v-if="storeLoading" class="loading-state">
          <svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>
          <span>正在加载在线商店...</span>
        </div>
        <div v-else class="cards-grid">
           <div v-for="card in storeCards" :key="card.id" class="character-card store-card">
              <div class="card-avatar">{{ card.avatar }}</div>
              <div class="card-info">
                <div class="card-name">{{ card.name }}</div>
                <div class="card-desc">{{ card.description }}</div>
                <div class="store-meta">
                  <span class="meta-item">@{{ card.author }}</span>
                  <span class="meta-item">{{ formatDate(card.date) }}</span>
                </div>
              </div>
              <div class="card-actions-store">
                <button class="icon-btn-small download-btn" @click="downloadCard(card)" title="下载角色">
                  <svg viewBox="0 0 24 24" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
              </div>
           </div>
           <div v-if="storeCards.length === 0" class="empty-store">
             暂无在线角色卡
           </div>
        </div>
      </div>
    </div>

    <!-- Edit/Create Modal -->
    <div v-if="isEditing" class="edit-modal-overlay">
      <div class="edit-modal">
        <div class="modal-header">
          <h3>{{ editForm.id ? '编辑角色' : '创建新角色' }}</h3>
          <button class="close-btn" @click="cancelEdit">×</button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group avatar-group">
              <label>头像 (Emoji)</label>
              <input v-model="editForm.icon" class="icon-input" maxlength="2" placeholder="🤖">
            </div>
            <div class="form-group name-group">
              <label>名称</label>
              <input v-model="editForm.name" placeholder="角色名称">
            </div>
          </div>
          <div class="form-group">
            <label>简短描述</label>
            <input v-model="editForm.description" placeholder="一句话描述这个角色的特点">
          </div>
          <div class="form-group">
            <label>System Prompt (核心设定)</label>
            <textarea v-model="editForm.systemPrompt" rows="6" placeholder="详细设定角色的性格、能力、语气等..."></textarea>
          </div>
          <div class="form-group">
            <label>标签 (Tags)</label>
            <input v-model="editForm.tagsInput" placeholder="例如: 助手, 编程, 幽默 (用逗号分隔)">
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-secondary" @click="cancelEdit">取消</button>
          <button class="btn-primary" @click="saveEdit">保存</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { store, mutations, defaultRoles } from '../store';
import { saveAs } from 'file-saver';

export default {
  name: 'CharacterManager',
  data() {
    return {
      activeTab: 'local',
      isEditing: false,
      editForm: {
        id: null,
        name: '',
        icon: '',
        description: '',
        systemPrompt: '',
        tagsInput: ''
      },
      showHidden: false
    };
  },
  computed: {
    currentRoleId() {
      return store.roleSettings.activeRoleId;
    },
    visibleDefaultRoles() {
      const deletedIds = store.roleSettings.deletedDefaultRoles || [];
      return defaultRoles.filter(r => !deletedIds.includes(r.id));
    },
    hiddenDefaultRoles() {
      const deletedIds = store.roleSettings.deletedDefaultRoles || [];
      return defaultRoles.filter(r => deletedIds.includes(r.id));
    },
    hiddenRolesCount() {
        return (store.roleSettings.deletedDefaultRoles || []).length;
    },
    customRoles() {
      return store.roleSettings.customRoles || [];
    },
    storeCards() {
      return store.onlineStore.cards;
    },
    storeLoading() {
      return store.onlineStore.loading;
    },
    customRolesByTag() {
      const roles = this.customRoles;
      const groups = {};
      
      roles.forEach(role => {
        // Use first tag as category, or "未分类"
        const tag = (role.tags && role.tags.length > 0) ? role.tags[0] : '未分类';
        if (!groups[tag]) {
          groups[tag] = [];
        }
        groups[tag].push(role);
      });
      
      return groups;
    }
  },
  methods: {
    selectRole(role) {
      mutations.setActiveRole(role.id);
      this.$emit('close');
    },
    toggleDefaultVisibility(id) {
      mutations.toggleDefaultRole(id);
    },
    startCreate() {
      this.editForm = {
        id: null,
        name: '',
        icon: '',
        description: '',
        systemPrompt: '',
        tagsInput: ''
      };
      this.isEditing = true;
    },
    startEdit(role) {
      this.editForm = {
        ...role,
        tagsInput: role.tags ? role.tags.join(', ') : ''
      };
      this.isEditing = true;
    },
    cancelEdit() {
      this.isEditing = false;
    },
    saveEdit() {
      const roleData = {
        name: this.editForm.name,
        icon: this.editForm.icon || '👤',
        description: this.editForm.description,
        systemPrompt: this.editForm.systemPrompt,
        tags: this.editForm.tagsInput.split(/[,，]/).map(t => t.trim()).filter(t => t)
      };

      if (this.editForm.id) {
        // Update
        mutations.updateCustomRole({
          id: this.editForm.id,
          ...roleData
        });
      } else {
        // Create
        mutations.addCustomRole(roleData);
      }
      this.isEditing = false;
    },
    deleteRole(id) {
      if (confirm('确定要删除这个角色吗？')) {
        mutations.deleteCustomRole(id);
      }
    },
    exportRole(role) {
      const blob = new Blob([JSON.stringify(role, null, 2)], { type: 'application/json' });
      saveAs(blob, `${role.name}_character_card.json`);
    },
    triggerImport() {
      this.$refs.importInput.click();
    },
    handleImport(event) {
      const file = event.target.files[0];
      if (!file) return;
      
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          const card = JSON.parse(e.target.result);
          mutations.importCharacterCard(card);
          alert('导入成功！');
        } catch (err) {
          alert('导入失败：无效的文件格式');
        }
      };
      reader.readAsText(file);
    },
    async loadStore() {
      this.activeTab = 'store';
      await mutations.fetchOnlineCards();
    },
    async shareCard(role) {
        if (!confirm('确定要将此角色分享到在线商店吗？任何人都可以看到并下载。')) return;
        
        const result = await mutations.shareCard(role);
        if (result.success) {
            alert('分享成功！');
        } else {
            alert('分享失败：' + (result.error || '未知错误'));
        }
    },
    downloadCard(card) {
        mutations.importCharacterCard(card);
        alert('已下载并添加到"我的角色"');
    },
    formatDate(dateStr) {
        if (!dateStr) return '';
        return new Date(dateStr).toLocaleDateString();
    }
  }
};
</script>

<style scoped>
.character-manager {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--bg-color, #ffffff);
  z-index: 1000;
  display: flex;
  flex-direction: column;
}

.manager-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border-color, #eee);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.manager-header h3 {
  margin: 0;
  font-size: 18px;
}

.tabs {
  display: flex;
  gap: 12px;
}

.tabs button {
  background: none;
  border: none;
  padding: 8px 16px;
  cursor: pointer;
  border-radius: 20px;
  font-weight: 500;
  color: var(--text-color-secondary, #666);
  transition: all 0.2s;
}

.tabs button.active {
  background: var(--primary-color, #4a90e2);
  color: white;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: var(--text-color, #333);
}

.manager-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.actions-bar {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 12px;
}

.character-card {
  border: 1px solid var(--border-color, #eee);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
  background: var(--card-bg, #fff);
  position: relative;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.character-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1), 0 0 0 1px var(--primary-color, #4a90e2) inset;
}

.character-card.active {
  border-color: var(--primary-color, #4a90e2);
  background: var(--primary-color-light, #eef6ff);
  box-shadow: 0 2px 8px rgba(74, 144, 226, 0.2);
}

.card-avatar {
  font-size: 24px;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border-radius: 8px;
  box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
}

.card-info {
  flex: 1;
  overflow: hidden;
}

.card-name {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: var(--text-color, #333);
}

.card-desc {
  font-size: 11px;
  color: var(--text-color-secondary, #666);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-actions {
  display: flex;
  gap: 2px;
  opacity: 0;
  transition: opacity 0.2s;
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: var(--card-bg, #fff);
  padding-left: 8px;
  border-radius: 4px;
  box-shadow: -4px 0 8px var(--card-bg, #fff);
}

.character-card:hover .card-actions {
  opacity: 1;
}

.icon-btn-small {
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: var(--text-color-secondary, #666);
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-btn-small:hover {
  background: rgba(0,0,0,0.05);
  color: var(--primary-color, #4a90e2);
}

.icon-btn-small.delete-btn:hover {
  color: #ff4d4f;
  background: rgba(255, 77, 79, 0.1);
}

.section-title {
  font-size: 13px;
  font-weight: 600;
  color: var(--text-color-secondary, #888);
  margin: 20px 0 10px;
  padding-bottom: 6px;
  border-bottom: 1px solid var(--border-color, #eee);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-section:first-child .section-title {
  margin-top: 0;
}

.tag-group {
  margin-bottom: 16px;
}

.group-label {
  font-size: 12px;
  color: var(--text-color-secondary, #888);
  margin-bottom: 6px;
  padding-left: 4px;
  font-weight: 600;
}

.card-tags {
  display: flex;
  gap: 4px;
  margin-top: 3px;
}

.tag-pill {
  font-size: 9px;
  background: var(--btn-secondary-bg, #f0f0f0);
  padding: 1px 5px;
  border-radius: 3px;
  color: var(--text-color-secondary, #666);
  border: 1px solid rgba(0,0,0,0.05);
}

.show-hidden-btn {
  grid-column: 1 / -1;
  text-align: center;
  padding: 12px;
  color: var(--text-color-secondary, #666);
  cursor: pointer;
  font-size: 13px;
}

.show-hidden-btn:hover {
  color: var(--primary-color, #4a90e2);
}

.hidden-role {
  opacity: 0.6;
  border-style: dashed;
}

/* Edit Modal */
.edit-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
}

.edit-modal {
  background: var(--bg-color, #fff);
  width: 500px;
  max-width: 90%;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.modal-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border-color, #eee);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 24px;
  overflow-y: auto;
  max-height: 70vh;
}

.modal-footer {
  padding: 16px 24px;
  border-top: 1px solid var(--border-color, #eee);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-size: 13px;
  font-weight: 500;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid var(--border-color, #ddd);
  border-radius: 6px;
  background: var(--input-bg, #fff);
  color: var(--text-color, #333);
}

.form-row {
  display: flex;
  gap: 16px;
}

.avatar-group {
  width: 80px;
}

.name-group {
  flex: 1;
}

.icon-input {
  text-align: center;
  font-size: 20px;
}

.btn-primary {
  background: var(--primary-color, #4a90e2);
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-secondary {
  background: var(--btn-secondary-bg, #f5f5f5);
  color: var(--text-color, #333);
  border: 1px solid var(--border-color, #ddd);
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
}

.btn-primary:hover {
  opacity: 0.9;
}

.btn-secondary:hover {
  background: var(--btn-secondary-hover, #e0e0e0);
}

/* Store Cards */
.store-card {
  display: flex; /* Changed from block to flex to match local cards */
  padding: 12px;
  gap: 12px;
  align-items: center;
}

.store-meta {
  display: flex;
  gap: 8px;
  margin-top: 4px;
}

.meta-item {
  font-size: 10px;
  color: var(--text-color-secondary, #999);
  background: rgba(0,0,0,0.03);
  padding: 1px 4px;
  border-radius: 3px;
}

.card-actions-store {
  display: flex;
  align-items: center;
}

.download-btn {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: var(--btn-secondary-bg, #f5f5f5);
  color: var(--primary-color, #4a90e2);
  transition: all 0.2s;
}

.download-btn:hover {
  background: var(--primary-color, #4a90e2);
  color: white;
  transform: scale(1.1);
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: #888;
  gap: 12px;
}

.empty-store {
  grid-column: 1 / -1;
  text-align: center;
  padding: 40px;
  color: #888;
}

.spinner {
  width: 30px;
  height: 30px;
  animation: rotate 2s linear infinite;
}

.spinner .path {
  stroke: var(--primary-color, #4a90e2);
  stroke-linecap: round;
  animation: dash 1.5s ease-in-out infinite;
}

@keyframes rotate {
  100% { transform: rotate(360deg); }
}

@keyframes dash {
  0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
  50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
  100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
}
</style>