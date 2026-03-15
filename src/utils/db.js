
const DB_NAME = 'ai_memory_db';
const DB_VERSION = 2; // Incremented version for new store
const STORE_NAME = 'core_attributes';
const SESSIONS_STORE = 'chat_sessions';

class MemoryDB {
  constructor() {
    this.db = null;
    this.ready = this.init();
  }

  init() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = (event) => {
        console.error('IndexedDB error:', event.target.error);
        reject(event.target.error);
      };

      request.onsuccess = (event) => {
        this.db = event.target.result;
        resolve();
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME);
        }
        if (!db.objectStoreNames.contains(SESSIONS_STORE)) {
          db.createObjectStore(SESSIONS_STORE, { keyPath: 'id' });
        }
      };
    });
  }

  // Core Attributes (Soul, User, Memory)
  async get(key) {
    await this.ready;
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([STORE_NAME], 'readonly');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.get(key);

      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async set(key, value) {
    await this.ready;
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([STORE_NAME], 'readwrite');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.put(value, key);

      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  // Sessions Management
  async getAllSessions() {
    await this.ready;
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([SESSIONS_STORE], 'readonly');
      const store = transaction.objectStore(SESSIONS_STORE);
      const request = store.getAll();

      request.onsuccess = () => {
        // Sort by timestamp descending
        const sessions = request.result || [];
        sessions.sort((a, b) => (b.timestamp || 0) - (a.timestamp || 0));
        resolve(sessions);
      };
      request.onerror = () => reject(request.error);
    });
  }

  async saveSession(session) {
    await this.ready;
    console.log('[DEBUG-DB] saveSession executing for id:', session.id);
    return new Promise((resolve, reject) => {
      try {
        const transaction = this.db.transaction([SESSIONS_STORE], 'readwrite');
        const store = transaction.objectStore(SESSIONS_STORE);
        
        // Use put without key param because keyPath is defined
        const request = store.put(session);

        request.onsuccess = () => {
            console.log('[DEBUG-DB] saveSession success');
            resolve();
        };
        request.onerror = (e) => {
            console.error('[DEBUG-DB] saveSession error:', e.target.error);
            reject(request.error);
        };
      } catch (e) {
        console.error('[DEBUG-DB] saveSession transaction error:', e);
        reject(e);
      }
    });
  }

  async deleteSession(id) {
    await this.ready;
    return new Promise((resolve, reject) => {
      const transaction = this.db.transaction([SESSIONS_STORE], 'readwrite');
      const store = transaction.objectStore(SESSIONS_STORE);
      const request = store.delete(id);

      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async getAll() {
    await this.ready;
    const coreData = await new Promise((resolve, reject) => {
      const transaction = this.db.transaction([STORE_NAME], 'readonly');
      const store = transaction.objectStore(STORE_NAME);
      const request = store.openCursor();
      const results = {};

      request.onsuccess = (event) => {
        const cursor = event.target.result;
        if (cursor) {
          results[cursor.key] = cursor.value;
          cursor.continue();
        } else {
          resolve(results);
        }
      };
      request.onerror = () => reject(request.error);
    });

    const sessions = await this.getAllSessions();
    return { coreData, sessions };
  }

  async clearAll() {
    await this.ready;
    const t1 = this.db.transaction([STORE_NAME], 'readwrite').objectStore(STORE_NAME).clear();
    const t2 = this.db.transaction([SESSIONS_STORE], 'readwrite').objectStore(SESSIONS_STORE).clear();
    return Promise.all([
      new Promise((res, rej) => { t1.onsuccess = res; t1.onerror = rej; }),
      new Promise((res, rej) => { t2.onsuccess = res; t2.onerror = rej; })
    ]);
  }

  async importAll(data) {
    await this.ready;
    await this.clearAll();
    
    const coreData = data.coreData || data; // Fallback for old backup format
    const sessions = data.sessions || [];

    const p1 = Object.entries(coreData).map(([key, value]) => this.set(key, value));
    const p2 = sessions.map(session => this.saveSession(session));

    return Promise.all([...p1, ...p2]);
  }
}

export const memoryDB = new MemoryDB();
