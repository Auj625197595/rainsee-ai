import Vue from 'vue';
import { memoryDB } from '@/utils/db';
import { encryptData, decryptData } from '@/utils/crypto';
import { CHARACTER_STORE_URL } from '@/api/config';
import { generateDefaultModels, generateActiveModelIds } from './modelConfig';

const STORAGE_KEY = 'rainshome_ai_store';

const defaultRoles = [
  { id: 'general', name: '全能专家', icon: '🤖', description: '知识渊博，能够回答各种问题', systemPrompt: '你是一个ai助手，能够回答用户的各种问题。', isDefault: true },
  { id: 'lawyer', name: '律师', icon: '⚖️', description: '提供专业的法律咨询', systemPrompt: '你是一位经验丰富的律师，请用专业的法律知识回答用户的问题。', isDefault: true },
  { id: 'teacher', name: '老师', icon: '👩‍🏫', description: '耐心解答，循循善诱', systemPrompt: '你是一位耐心的老师，请用通俗易懂的语言解释复杂的概念。', isDefault: true },
  { id: 'biologist', name: '生物专家', icon: '🧬', description: '解答生物学相关问题', systemPrompt: '你是一位生物学专家，请用专业的生物学知识回答用户的问题。', isDefault: true },
  { id: 'programmer', name: '程序员', icon: '💻', description: '编写代码，解决技术难题', systemPrompt: '你是一位资深的程序员，请提供高效、健壮的代码解决方案。', isDefault: true },
  { id: 'psychologist', name: '心理咨询师', icon: '🧠', description: '倾听烦恼，提供心理疏导', systemPrompt: '你是一位温暖的心理咨询师，请倾听用户的烦恼并提供建议。', isDefault: true },
  { id: 'translator', name: '翻译官', icon: '🔤', description: '专业翻译，支持多种语言', systemPrompt: '你是一位专业的翻译官，请准确地在不同语言之间进行翻译，并保持原意。', isDefault: true },
  { id: 'fitness', name: '健身教练', icon: '🏋️‍♂️', description: '提供运动指导和健身计划', systemPrompt: '你是一位专业的健身教练，请根据用户需求提供科学的锻炼建议和饮食计划。', isDefault: true },
  { id: 'chef', name: '大厨', icon: '👨‍🍳', description: '提供食谱和烹饪技巧', systemPrompt: '你是一位厨艺精湛的大厨，请分享美味的食谱和实用的烹饪技巧。', isDefault: true },
  { id: 'travel', name: '旅行规划师', icon: '✈️', description: '规划行程，推荐景点', systemPrompt: '你是一位资深的旅行规划师，请根据用户的喜好规划完美的旅行行程。', isDefault: true },
  { id: 'finance', name: '理财顾问', icon: '💰', description: '提供投资建议和财务规划', systemPrompt: '你是一位专业的理财顾问，请提供稳健的投资建议和合理的财务规划。', isDefault: true },
  { id: 'parenting', name: '育儿专家', icon: '👶', description: '解答育儿难题，提供建议', systemPrompt: '你是一位经验丰富的育儿专家，请为父母们提供科学的育儿指导和心理支持。', isDefault: true },
  { id: 'historian', name: '历史学家', icon: '📜', description: '讲述历史故事，解析事件', systemPrompt: '你是一位知识渊博的历史学家，请深入浅出地讲解历史知识和文化背景。', isDefault: true },
  { id: 'critic', name: '影评人', icon: '🎬', description: '推荐电影，深入解析剧情', systemPrompt: '你是一位犀利的影评人，请提供深刻的电影分析和有价值的观影推荐。', isDefault: true },
  { id: 'nutritionist', name: '营养师', icon: '🥗', description: '提供健康饮食建议', systemPrompt: '你是一位专业的营养师，请根据用户的身体状况提供健康的饮食方案。', isDefault: true },
  { id: 'career', name: '职业规划师', icon: '💼', description: '职业发展建议和简历优化', systemPrompt: '你是一位资深的职业规划师，请协助用户进行职业定位和简历优化。', isDefault: true },
  { id: 'writer', name: '作家/诗人', icon: '✍️', description: '创作文学作品，润色文字', systemPrompt: '你是一位富有才华的作家，请协助用户进行文学创作或文字润色。', isDefault: true },
  { id: 'marketing', name: '营销专家', icon: '📈', description: '制定营销策略和文案创意', systemPrompt: '你是一位资深的营销专家，请提供有创意的营销方案和吸引人的文案。', isDefault: true },
  { id: 'game', name: '游戏策划', icon: '🎮', description: '设计游戏机制和背景故事', systemPrompt: '你是一位富有想象力的游戏策划，请协助设计有趣的游戏玩法和故事情节。', isDefault: true },
  { id: 'astronomer', name: '天文学家', icon: '🔭', description: '探索宇宙奥秘，解答问题', systemPrompt: '你是一位探索宇宙的天文学家，请用科学的语言揭示星空的奥秘。', isDefault: true },
  { id: 'astrologer', name: '占星师', icon: '🔮', description: '解读星座运势和星盘', systemPrompt: '你是一位神秘的占星师，请为用户解读星座运势和提供心理建议。', isDefault: true },
  { id: 'photographer', name: '摄影师', icon: '📷', description: '提供构图建议和后期技巧', systemPrompt: '你是一位专业的摄影师，请分享构图秘籍和实用的后期修图技巧。', isDefault: true },
  { id: 'language', name: '语言伙伴', icon: '🗣️', description: '练习对话，纠正语法错误', systemPrompt: '你是一位耐心的语言学习伙伴，请与用户进行对话练习并纠正其错误。', isDefault: true },
  { id: 'debate', name: '辩论教练', icon: '🗣️', description: '提升逻辑思维和辩论技巧', systemPrompt: '你是一位逻辑严密的辩论教练，请引导用户提升逻辑分析和表达能力。', isDefault: true },
  { id: 'pm', name: '产品经理', icon: '💡', description: '分析需求，打磨产品逻辑', systemPrompt: '你是一位资深的产品经理，请协助进行需求分析和产品原型设计。', isDefault: true },
  { id: 'designer', name: '室内设计师', icon: '🏠', description: '提供装修建议和空间布局', systemPrompt: '你是一位富有审美的室内设计师，请提供实用的装修灵感和空间规划建议。', isDefault: true },
];

// Default state definition
const defaultModels = generateDefaultModels();

const defaultState = {
  token: '',
  theme: 'light', // 'light' | 'dark'
  history: [], // Array of { role: 'user'|'assistant', content: '', thinking: '' }
  sessions: [], // Array of { id, title, messages, timestamp, chatId }
  currentSessionId: null, // ID of the currently loaded session
  chatId: null, // Backend session ID
  roleSettings: {
    activeRoleId: 'general',
    customRoles: [], // Array of { id, name, icon, description, systemPrompt, tags, author, isDefault: false }
    deletedDefaultRoles: [], // Array of IDs of default roles that are hidden/deleted
    roleHistory: [] // Array of role IDs
  },
  onlineStore: {
    cards: [],
    loading: false
  },
  settings: {
    thinkingEnabled: false, // Toggle for "Deep Thinking" mode
    webSearchEnabled: false, // Toggle for "Web Search" mode
    planEnabled: false, // Toggle for "Plan" mode
    imageGenEnabled: false, // Toggle for "Image Generation" mode
    backupReminderDays: 1, // Default reminder interval in days
    lastBackupTime: null, // Timestamp of last backup
    ...generateActiveModelIds(),
    models: defaultModels
  },
  inputContext: '', // Content added via window.addDoc
  soul: { personality: 'You are a helpful AI assistant.' },
  user: { profile: 'User' },
  memory: { longTerm: [] },
};

// Load state from localStorage
const loadState = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (!saved) return {};
    const parsed = JSON.parse(saved);

    // Decrypt roleSettings if it's a string
    if (parsed.roleSettings && typeof parsed.roleSettings === 'string') {
      parsed.roleSettings = decryptData(parsed.roleSettings) || defaultState.roleSettings;
    }

    // Ignore sessions from localStorage as we use IndexedDB now
    if (parsed.sessions) {
      delete parsed.sessions;
    }

    // Migration: Migrate old settings structure to new models array
    if (parsed.settings) {
      if (parsed.settings.backupReminderDays === undefined) {
        parsed.settings.backupReminderDays = 1;
      }
      if (parsed.settings.lastBackupTime === undefined) {
        parsed.settings.lastBackupTime = null;
      }

      if (!parsed.settings.models) {
        const oldSettings = parsed.settings;
        parsed.settings = {
          thinkingEnabled: oldSettings.thinkingEnabled ?? false,
          webSearchEnabled: oldSettings.webSearchEnabled ?? false,
          imageGenEnabled: oldSettings.imageGenEnabled ?? false,
          activeModelId: 'default',
          activeTextModelId: 'default',
          activeT2iModelId: '',
          activeI2iModelId: '',
          models: [
            {
              id: 'default',
              name: 'Default Model',
              type: 'text',
              endpoint: oldSettings.endpoint || 'https://api.openai.com/v1/chat/completions',
              apiKey: oldSettings.apiKey || '',
              model: oldSettings.model || 'gpt-3.5-turbo'
            }
          ]
        };
      } else {
        // Ensure type exists on all models
        parsed.settings.models.forEach(m => {
          if (!m.type) m.type = 'text';
        });

        // Ensure new active IDs exist
        if (parsed.settings.activeTextModelId === undefined) {
          parsed.settings.activeTextModelId = parsed.settings.activeModelId || 'default';
        }
        if (parsed.settings.activeT2iModelId === undefined) {
          parsed.settings.activeT2iModelId = '';
        }
        if (parsed.settings.activeI2iModelId === undefined) {
          parsed.settings.activeI2iModelId = '';
        }
      }
    }

    return parsed;
  } catch (e) {
    console.error('Failed to load state', e);
    return {};
  }
};

// Initialize state: Merge defaults with saved state
const savedState = loadState();
const initialState = {
  ...defaultState,
  ...savedState,
  roleSettings: {
    ...defaultState.roleSettings,
    ...(savedState.roleSettings || {})
  },
  settings: {
    ...defaultState.settings,
    ...(savedState.settings || {}),
    // Always initialize volatile states to default
    imageGenEnabled: defaultState.settings.imageGenEnabled
  }
};

// 1. URL Token Logic: Check for token in URL params on initialization
const urlParams = new URLSearchParams(window.location.search);
const tokenFromUrl = urlParams.get('token');
if (tokenFromUrl) {
  initialState.token = tokenFromUrl;
  // Clean URL to hide token
  const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
  window.history.replaceState({ path: newUrl }, '', newUrl);
}

// Create the observable store
export const store = Vue.observable(initialState);
export { defaultRoles };

// Persistence helper
const saveState = () => {
  try {
    // Create a copy of the state to avoid modifying the reactive store directly
    const stateToSave = {
      ...store,
      settings: { ...store.settings }
    };

    // Do not persist volatile states
    delete stateToSave.settings.imageGenEnabled;
    // Do not persist sessions to localStorage (moved to IndexedDB)
    delete stateToSave.sessions;

    // Encrypt roleSettings
    if (stateToSave.roleSettings) {
      stateToSave.roleSettings = encryptData(stateToSave.roleSettings);
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(stateToSave));
  } catch (e) {
    console.error('Failed to save state', e);
  }
};


// Mutations / Actions
export const mutations = {
  // Theme Management
  toggleTheme() {
    store.theme = store.theme === 'light' ? 'dark' : 'light';
    this.applyTheme();
    saveState();
  },
  setTheme(theme) {
    store.theme = theme;
    this.applyTheme();
    saveState();
  },
  applyTheme() {
    // We will use data-theme attribute on document element for CSS variables
    document.documentElement.setAttribute('data-theme', store.theme);
  },

  // Token Management
  setToken(token) {
    store.token = token;
    saveState();
  },

  // Initialize or get persistent ChatID (User Identity)
  initChatId() {
    let id = localStorage.getItem('aihelp_chat_id');
    if (!id) {
      id = 'user_' + Date.now().toString(36) + Math.random().toString(36).substr(2);
      localStorage.setItem('aihelp_chat_id', id);
    }
    store.chatId = id;
    return id;
  },

  setChatId(id) {
    // Legacy support or if we want to override
    store.chatId = id;
    localStorage.setItem('aihelp_chat_id', id);
    saveState();
  },

  // Settings
  setThinkingEnabled(enabled) {
    store.settings.thinkingEnabled = enabled;
    saveState();
  },
  setWebSearchEnabled(enabled) {
    store.settings.webSearchEnabled = enabled;
    saveState();
  },
  setImageGenEnabled(enabled) {
    store.settings.imageGenEnabled = enabled;
    saveState();
  },
  setPlanEnabled(enabled) {
    store.settings.planEnabled = enabled;
    saveState();
  },
  setBackupReminderDays(days) {
    store.settings.backupReminderDays = days;
    saveState();
  },
  updateLastBackupTime() {
    store.settings.lastBackupTime = Date.now();
    saveState();
  },
  setSettings(newSettings) {
    Object.assign(store.settings, newSettings);
    saveState();
  },

  // Model Management
  addModel(model) {
    store.settings.models.push({
      id: Date.now().toString(),
      ...model
    });
    saveState();
  },
  updateModel(id, updates) {
    const idx = store.settings.models.findIndex(m => m.id === id);
    if (idx !== -1) {
      store.settings.models[idx] = { ...store.settings.models[idx], ...updates };
      saveState();
    }
  },
  deleteModel(id) {


    const idx = store.settings.models.findIndex(m => m.id === id);
    if (idx === -1) return;

    store.settings.models.splice(idx, 1);

    if (store.settings.activeModelId === id) {
      store.settings.activeModelId = store.settings.models[0]?.id || '';
    }
    if (store.settings.activeTextModelId === id) {
      store.settings.activeTextModelId = store.settings.models.find(m => m.type === 'text')?.id || '';
    }
    if (store.settings.activeT2iModelId === id) {
      store.settings.activeT2iModelId = store.settings.models.find(m => m.type === 't2i')?.id || '';
    }
    if (store.settings.activeI2iModelId === id) {
      store.settings.activeI2iModelId = store.settings.models.find(m => m.type === 'i2i')?.id || '';
    }
    saveState();
  },
  setActiveModel(id) {
    store.settings.activeModelId = id;

    // Auto-assign to correct type if we know the model's type
    const model = store.settings.models.find(m => m.id === id);
    if (model) {
      if (model.type === 'text') store.settings.activeTextModelId = id;
      else if (model.type === 't2i') store.settings.activeT2iModelId = id;
      else if (model.type === 'i2i') store.settings.activeI2iModelId = id;
    }

    saveState();
  },
  setActiveModelByType(id, type) {
    if (type === 'text') store.settings.activeTextModelId = id;
    else if (type === 't2i') store.settings.activeT2iModelId = id;
    else if (type === 'i2i') store.settings.activeI2iModelId = id;

    // Also update general active model if it matches the type
    const model = store.settings.models.find(m => m.id === id);
    if (model) {
      store.settings.activeModelId = id;
    }

    saveState();
  },

  // Role Management
  setActiveRole(roleId) {
    store.roleSettings.activeRoleId = roleId;

    // Update History: Remove if exists, then unshift to top
    const history = store.roleSettings.roleHistory || [];
    const newHistory = history.filter(id => id !== roleId);
    newHistory.unshift(roleId);
    // Limit history size (e.g., 20)
    store.roleSettings.roleHistory = newHistory.slice(0, 20);

    saveState();
  },
  addCustomRole(role) {
    if (!store.roleSettings.customRoles) {
      Vue.set(store.roleSettings, 'customRoles', []);
    }
    store.roleSettings.customRoles.push({
      id: Date.now().toString(),
      ...role
    });
    saveState();
  },
  deleteCustomRole(roleId) {
    if (!store.roleSettings.customRoles) return;
    store.roleSettings.customRoles = store.roleSettings.customRoles.filter(r => r.id !== roleId);

    // If deleted role was active, switch to default
    if (store.roleSettings.activeRoleId === roleId) {
      this.setActiveRole('general');
    } else {
      // Also remove from history
      store.roleSettings.roleHistory = store.roleSettings.roleHistory.filter(id => id !== roleId);
      saveState();
    }
  },

  // Character Card Management
  toggleDefaultRole(roleId) {
    if (!store.roleSettings.deletedDefaultRoles) {
      Vue.set(store.roleSettings, 'deletedDefaultRoles', []);
    }
    const index = store.roleSettings.deletedDefaultRoles.indexOf(roleId);
    if (index === -1) {
      store.roleSettings.deletedDefaultRoles.push(roleId);
    } else {
      store.roleSettings.deletedDefaultRoles.splice(index, 1);
    }
    saveState();
  },

  updateCustomRole(role) {
    const idx = store.roleSettings.customRoles.findIndex(r => r.id === role.id);
    if (idx !== -1) {
      // Use Vue.set to ensure reactivity if replacing the object
      Vue.set(store.roleSettings.customRoles, idx, { ...store.roleSettings.customRoles[idx], ...role });
      saveState();
    }
  },

  importCharacterCard(card) {
    // Check if ID exists, if so, generate new ID
    const newId = 'imported_' + Date.now();
    const newCard = { ...card, id: newId, isDefault: false };
    if (!store.roleSettings.customRoles) {
      Vue.set(store.roleSettings, 'customRoles', []);
    }
    store.roleSettings.customRoles.push(newCard);
    saveState();
    return newCard;
  },

  // Online Store Actions
  async fetchOnlineCards() {
    store.onlineStore.loading = true;
    try {
      const response = await fetch(`${CHARACTER_STORE_URL}?action=list`);
      const data = await response.json();
      if (data.cards) {
        store.onlineStore.cards = data.cards;
      }
    } catch (e) {
      console.error('Failed to fetch online cards', e);
    } finally {
      store.onlineStore.loading = false;
    }
  },

  async shareCard(card) {
    try {
      const response = await fetch(`${CHARACTER_STORE_URL}?action=share`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ card })
      });
      const data = await response.json();
      return data;
    } catch (e) {
      console.error('Failed to share card', e);
      return { error: e.message };
    }
  },

  // Chat History
  addMessage(message) {
    // message: { role: 'user'|'assistant', content: '', thinking: '', timestamp: Date.now() }
    store.history.push(message);
    saveState();
  },
  updateLastMessage(updates) {
    const lastMsg = store.history[store.history.length - 1];
    if (lastMsg) {
      Object.assign(lastMsg, updates);
    }
  },
  async finalizeLastMessage() {
    console.log('[DEBUG-STORE] finalizeLastMessage started');
    try {
      saveState();
      console.log('[DEBUG-STORE] State saved to localStorage');
      // Auto-save session to IndexedDB after each AI response
      await this.saveCurrentSessionToDB();
      console.log('[DEBUG-STORE] Session saved to IndexedDB');
    } catch (e) {
      console.error('[DEBUG-STORE] Error in finalizeLastMessage:', e);
    }
  },
  async saveCurrentSessionToDB() {
    console.log('[DEBUG-STORE] saveCurrentSessionToDB called');
    if (store.history.length === 0) {
      console.log('[DEBUG-STORE] History empty, skipping save');
      return;
    }

    let session;
    if (store.currentSessionId) {
      session = store.sessions.find(s => s.id === store.currentSessionId);
      if (session) {
        session.messages = [...store.history];
        session.timestamp = Date.now();
        session.chatId = store.chatId;
      }
    }

    if (!session) {
      // Create new session if it doesn't exist or currentSessionId is null
      const firstUserMsg = store.history.find(m => m.role === 'user');
      const title = firstUserMsg ? firstUserMsg.content.substring(0, 30) + (firstUserMsg.content.length > 30 ? '...' : '') : 'New Session';

      session = {
        id: store.currentSessionId || Date.now(),
        title: title,
        messages: [...store.history],
        timestamp: Date.now(),
        chatId: store.chatId
      };

      if (!store.currentSessionId) {
        store.currentSessionId = session.id;
        store.sessions.unshift(session);
      }
    }

    console.log('[DEBUG-STORE] Calling memoryDB.saveSession');
    try {
      await memoryDB.saveSession(session);
      console.log('[DEBUG-STORE] memoryDB.saveSession completed');
    } catch (e) {
      console.error('[DEBUG-STORE] memoryDB.saveSession failed:', e);
    }
    saveState();
  },
  rollbackTo(index) {
    if (index >= 0 && index < store.history.length) {
      store.history = store.history.slice(0, index);
      saveState();
      this.saveCurrentSessionToDB();
    }
  },
  clearHistory() {
    // We don't need to manually move history to sessions anymore
    // as it's saved to DB after each turn.
    // Just clear the current state.
    store.history = [];
    store.currentSessionId = null;
    store.chatId = null;
    saveState();
  },
  async loadSession(sessionId) {
    const session = store.sessions.find(s => s.id === sessionId);
    if (session) {
      store.history = [...session.messages];
      store.currentSessionId = sessionId;
      store.chatId = session.chatId || null;
      saveState();
    }
  },
  async deleteSession(sessionId) {
    if (store.currentSessionId === sessionId) {
      store.currentSessionId = null;
      store.chatId = null;
      store.history = [];
    }
    store.sessions = store.sessions.filter(s => s.id !== sessionId);
    await memoryDB.deleteSession(sessionId);
    saveState();
  },
  setCurrentSession() {
    store.currentSessionId = null;
    // store.chatId = null; // Keep chatId persistent
    store.history = []; // Clear history when starting a fresh session
    saveState();
  },

  // External Communication
  appendInputContext(text) {
    if (!text) return;
    // Append with newline if not empty
    store.inputContext = store.inputContext
        ? store.inputContext + '\n' + text
        : text;
  },
  clearInputContext() {
    store.inputContext = '';
  },

  // Memory Management
  async loadMemory() {
    try {
      const soul = await memoryDB.get('soul');
      const user = await memoryDB.get('user');
      const memory = await memoryDB.get('memory');

      if (soul) store.soul = soul;
      if (user) store.user = user;
      if (memory) store.memory = memory;

      // Also load sessions from IndexedDB
      const sessions = await memoryDB.getAllSessions();
      if (sessions && sessions.length > 0) {
        store.sessions = sessions;
      }
    } catch (e) {
      console.error('Failed to load memory/sessions from DB', e);
    }
  },
  async updateCoreMemory(type, data) {
    // type: 'soul' | 'user' | 'memory'
    if (store[type] !== undefined) {
      store[type] = data;
      await memoryDB.set(type, data);
    }
  },

  // Backup & Restore
  async getFullBackup() {
    this.updateLastBackupTime();
    const indexedData = await memoryDB.getAll();
    const storeData = { ...store };
    return {
      store: storeData,
      indexed: indexedData,
      timestamp: Date.now(),
      version: '1.0'
    };
  },
  async restoreFromBackup(backup) {
    if (!backup || !backup.store || !backup.indexed) {
      throw new Error('Invalid backup format');
    }

    // Handle legacy backups: If sessions are missing in indexedDB backup but present in store backup,
    // migrate them to indexedDB import data.
    const indexedData = { ...backup.indexed };
    if ((!indexedData.sessions || indexedData.sessions.length === 0) &&
        backup.store.sessions && backup.store.sessions.length > 0) {
      indexedData.sessions = backup.store.sessions;
    }

    // Restore store state (reactive update)
    Object.keys(backup.store).forEach(key => {
      // Skip sessions as it will be loaded from DB after import
      if (key === 'sessions') return;

      if (store[key] !== undefined) {
        store[key] = backup.store[key];
      }
    });

    // Restore chat_id to localStorage
    if (store.chatId) {
      localStorage.setItem('aihelp_chat_id', store.chatId);
    }

    // Restore IndexedDB
    await memoryDB.importAll(indexedData);

    // Reload memory and sessions from DB to ensure store is in sync
    await mutations.loadMemory();

    saveState();
    return true;
  }
};

// Apply theme immediately
mutations.applyTheme();
// Ensure ChatId is initialized
mutations.initChatId();
// Load memory from IndexedDB
mutations.loadMemory();

// 2. Window exposure: window.addDoc
window.addDoc = (text) => {
  mutations.appendInputContext(text);
  // Dispatch a custom event if components need to react immediately beyond reactive state
  window.dispatchEvent(new CustomEvent('rainshome-doc-added', { detail: text }));
};

export default {
  store,
  mutations
};
