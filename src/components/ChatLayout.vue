<template>
  <div class="chat-layout">
    <!-- Sidebar (Glassmorphism) -->
    <aside class="sidebar" :class="{ 'sidebar-open': isSidebarOpen }">
      <div class="sidebar-header">
        <h2 class="brand-title">{{ brandTitle }}</h2>
        <button class="icon-btn close-sidebar-btn" @click="toggleSidebar">
          <svg viewBox="0 0 24 24" width="24" height="24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
        </button>
      </div>

      <div class="new-chat-container">
        <button class="new-chat-btn" @click="createNewChat">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
          <span>新的会话</span>
        </button>
      </div>
      
      <div class="history-list">
        <div class="history-item" :class="{ active: messages.length > 0 && !currentSessionId }" @click="selectCurrentSession">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          <span>当前会话</span>
        </div>
        
        <div v-for="session in sessions" :key="session.id" class="history-item" :class="{ active: currentSessionId === session.id }" @click="loadSession(session.id)">
          <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
          <span class="session-title">{{ session.title }}</span>
          <button class="delete-session-btn" @click.stop="deleteSession(session.id)" title="Delete Session">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
          </button>
        </div>
      </div>

      <div class="sidebar-footer">
        <button class="icon-btn settings-btn" @click="toggleSettings" title="Settings">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
        </button>
        <button class="icon-btn theme-toggle" @click="toggleTheme" title="Toggle Theme">
          <!-- Sun Icon -->
          <svg v-if="theme === 'light'" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4"/>
            <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>
          </svg>
          <!-- Moon Icon -->
          <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
          </svg>
        </button>
      </div>
    </aside>
    
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" v-if="isSidebarOpen" @click="toggleSidebar"></div>

    <!-- Settings Modal -->
    <SettingsModal :is-open="isSettingsOpen" @close="closeSettings" />

    <!-- Main Chat Area -->
    <main class="chat-main">
      <WelcomeWizard v-if="hasNoModels" />
      <header class="chat-header">
        <div class="header-left">
          <button class="icon-btn menu-btn" @click="toggleSidebar">
            <svg viewBox="0 0 24 24" width="24" height="24"><path d="M3 18h18v-2H3v2zm0-5h18v-2H3v2zm0-7v2h18V6H3z"/></svg>
          </button>
          <span class="header-title">New Chat</span>
        </div>
        
        <div class="header-right">
          <transition name="bounce">
            <button v-if="showBackupReminder" class="backup-btn-animated" @click="handleBackupClick" :title="backupReminderText">
              <span class="backup-text">提醒备份</span>
              <span class="pulse-ring"></span>
            </button>
          </transition>
        </div>
      </header>

      <transition name="fade">
        <div v-if="isCharacterManagerOpen" class="character-manager-overlay">
        <CharacterManager @close="isCharacterManagerOpen = false" />
      </div>
    </transition>



      <div class="messages-container" ref="messagesContainer" @click="activeMessageIndex = -1; exportMenuIndex = -1">
        <div v-if="messages.length === 0" class="empty-state">
          <h3>How can I help you today?</h3>
        </div>
        
        <div v-for="(msg, index) in messages" :key="index" :class="['message-row', msg.role]">
          <div class="message-wrapper"
               @mouseenter="handleMouseEnter(index)"
               @mouseleave="handleMouseLeave(index)"
               @click.stop="handleMessageClick(index)"
          >
            <div class="message-content">
            <!-- Thinking Process Block -->
            <div v-if="msg.thinking" class="thinking-block" :class="{ 'is-thinking': msg.isThinking && !msg.content }">
              <details :open="msg.isThinking && index === messages.length - 1">
                <summary>
                  <div class="thinking-header">
                    <span class="thinking-icon">
                      <!-- Search Icon if searching -->
                      <svg v-if="msg.isSearch" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                      </svg>
                      <!-- Thinking Icon if not searching -->
                      <svg v-else viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path>
                        <path d="M12 6v6l4 2"></path>
                      </svg>
                    </span>
                    <span class="thinking-title">{{ msg.isSearch ? '联网搜索过程与结果' : '思考过程' }}</span>
                    <span class="chevron-icon">
                      <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 12 15 18 9"></polyline>
                      </svg>
                    </span>
                  </div>
                </summary>
                <div class="thinking-content">
                  <MarkdownRenderer :content="msg.thinking" @copy-code="handleCopyCodeEvent" />
                </div>
              </details>
            </div>
            
            <!-- Message Text or Loading Animation -->
            <div v-if="msg.attachments && msg.attachments.length > 0" class="message-attachments">
                <div v-for="(att, i) in msg.attachments" :key="i" class="message-attachment-item">
                    <img v-if="att.type === 'image'" :src="att.url" alt="Attachment" loading="lazy" style="max-width: 300px; max-height: 300px; border-radius: 8px; cursor: pointer; display: block;" @click="openImage(att.url)">
                    <div v-else-if="att.type === 'file'" class="file-attachment-bubble">
                         <div class="file-icon-small">
                           <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                         </div>
                         <span>{{ att.name }}</span>
                    </div>
                </div>
            </div>
            <!-- Legacy support for old messages -->
            <div v-else-if="msg.imageUrl" class="message-attachment">
                <img :src="msg.imageUrl" alt="Attachment" loading="lazy" style="max-width: 300px; max-height: 300px; border-radius: 8px; margin-bottom: 8px; cursor: pointer; display: block;" @click="openImage(msg.imageUrl)">
            </div>
            
            <div v-if="!msg.content && !msg.thinking && !msg.isGeneratingImage && msg.role === 'assistant'" class="typing-indicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
            
            <!-- Skeleton Loader for Image Generation -->
            <div v-if="msg.isGeneratingImage" class="image-skeleton-loader">
                <div class="skeleton-image">
                    <div class="shimmer"></div>
                    <div class="skeleton-icon">
                        <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    </div>
                </div>
                <div class="skeleton-text">AI 正在绘图...</div>
            </div>

            <div v-else-if="msg.role === 'user'" class="text-content user-text" :style="{ fontSize: (store.settings.fontSize || 16) + 'px' }">{{ msg.content }}</div>
            <div v-else class="text-content" :style="{ fontSize: (store.settings.fontSize || 16) + 'px !important' }">
                <MarkdownRenderer :content="msg.content" @copy-code="handleCopyCodeEvent" />
            </div>
            
            <!-- Command Confirmation Embedded -->
            <div v-if="isConfirmDialogOpen && index === messages.length - 1 && msg.role === 'assistant'" class="command-embed-container">
               <div class="command-embed-header">
                  <span class="command-icon">⚡</span>
                  <span>AI 请求执行命令</span>
               </div>
               <div class="command-embed-content">
                  <pre>{{ pendingCommand }}</pre>
               </div>
               <div class="command-embed-actions">
                  <button class="embed-btn cancel" @click="cancelCommand">拒绝</button>
                  <button class="embed-btn confirm" @click="confirmCommand">允许执行</button>
               </div>
            </div>
          </div>
          <div v-if="msg.role === 'user'" class="message-actions" :class="{ visible: activeMessageIndex === index || isMobile }">
            <button class="action-btn" @click.stop="copyMessage(msg.content)">复制</button>
            <button class="action-btn" @click.stop="editMessage(index, msg)">编辑</button>
          </div>
          <div v-if="msg.role === 'assistant'" class="message-actions" :class="{ visible: activeMessageIndex === index || isMobile }">
             <button class="action-btn" @click.stop="copyMessage(msg.content)">复制</button>
             <div class="export-dropdown-wrapper">
                <button class="action-btn" @click.stop="toggleExportMenu(index)">导出 ▼</button>
                <div v-if="exportMenuIndex === index" class="export-menu">
                   <div class="export-menu-content">
                      <div class="export-item" @click.stop="exportMessage(index, msg.content, 'pdf')">导出 PDF</div>
                      <div class="export-item" @click.stop="exportMessage(index, msg.content, 'word')">导出 Word</div>
                      <div class="export-item" @click.stop="exportMessage(index, msg.content, 'markdown')">导出 Markdown</div>
                   </div>
                </div>
             </div>
          </div>
          </div>
        </div>
        

      </div>

      <!-- Input Area -->
      <div 
        class="input-area" 
        :class="{ 'dragging': isDragging }"
        @dragover.prevent="handleDragOver"
        @dragleave.prevent="handleDragLeave"
        @drop.prevent="handleDrop"
      >
        <!-- Expanded Role List Popover (Removed in favor of CharacterManager) -->

        <div class="drag-overlay" v-if="isDragging">
          <div class="drag-content">
            <svg viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <span>拖拽文件到这里上传</span>
          </div>
        </div>

        <!-- Scroll to bottom notification -->
        <transition name="fade">
          <div v-if="showScrollTip" class="scroll-tip" @click="scrollToBottom(true)">
            <div class="scroll-tip-content">
              <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M7 13l5 5 5-5M7 6l5 5 5-5"/></svg>
              <span>有新内容，点此回到底部</span>
            </div>
          </div>
        </transition>

        <div class="input-wrapper">
          <div v-if="(attachments.length > 0 || isUploading)" class="attachment-preview">
             <div v-if="isUploading" class="uploading-spinner">
                <svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>
                <span>Uploading...</span>
             </div>
             
             <div class="attachments-list" v-if="attachments.length > 0">
                 <div v-for="(att, index) in attachments" :key="index" class="preview-item">
                    <!-- Image Preview -->
                    <div v-if="att.type === 'image'" class="preview-img-container">
                       <img :src="att.url" :alt="att.name">
                       <button class="remove-btn" @click="removeAttachment(index)" title="Remove attachment">
                         <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                       </button>
                    </div>
                    <!-- File Preview -->
                    <div v-else class="preview-file-container">
                       <div class="file-icon">
                         <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                       </div>
                       <span class="file-name">{{ att.name }}</span>
                       <button class="remove-btn" @click="removeAttachment(index)" title="Remove attachment">
                         <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                       </button>
                    </div>
                 </div>
             </div>
          </div>
          <!-- Textarea auto-resize logic needed in script -->
          <textarea 
            v-model="inputText" 
            placeholder="有什么可以帮你的吗..." 
            rows="1"
            @focus="handleFocus"
            @input="adjustHeight"
            @keydown.enter.exact.prevent="sendMessage"
            @paste="handlePaste"
          ></textarea>
          
          <div class="toolbar">
            <div class="left-tools">
              <!-- Role Selector Button or Active Role Chip -->
              <div class="tool-group">
                <button 
                  v-if="currentRole.id === 'general'"
                  class="tool-tab role-selector" 
                  @click="toggleCharacterManager"
                  title="选择角色"
                >
                  <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                  </svg>
                  <span>角色</span>
                </button>
                
                <div 
                  v-else 
                  class="active-role-chip"
                  title="点击更换角色"
                  @click="toggleCharacterManager"
                >
                  <span class="role-icon">{{ currentRole.icon }}</span>
                  <span class="role-name">{{ currentRole.name }}</span>
                  <button class="remove-role-btn" @click.stop="selectRole('general')" title="取消角色">×</button>
                </div>
              </div>

              <div class="tool-divider"></div>

              <!-- Mobile Mode Trigger -->
              <button 
                v-if="isMobile"
                class="tool-tab mobile-mode-trigger" 
                :class="{ active: isModeExpanded }"
                @click="isModeExpanded = !isModeExpanded"
                title="Select Modes"
              >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 5v14M5 12h14" v-if="!isModeExpanded"/>
                  <path d="M5 12h14" v-else/>
                </svg>
                <span>功能模式</span>
              </button>

              <!-- Thinking Toggle -->
              <button 
                v-show="!isMobile || thinkingEnabled"
                class="tool-tab" 
                :class="{ active: thinkingEnabled }"
                @click="isMobile ? (isModeExpanded = true) : (thinkingEnabled = !thinkingEnabled)"
                title="Deep Thinking Mode"
              >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path>
                  <path d="M12 6v6l4 2"></path>
                </svg>
                <span>深度思考</span>
              </button>

              <!-- Web Search Toggle -->
              <button 
                v-show="!isMobile || webSearchEnabled"
                class="tool-tab" 
                :class="{ active: webSearchEnabled }"
                @click="isMobile ? (isModeExpanded = true) : (webSearchEnabled = !webSearchEnabled)"
                title="Web Search Mode"
              >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <span>联网搜索</span>
              </button>

              <!-- Image Generation Toggle -->
              <button 
                v-show="!isMobile || imageGenEnabled"
                class="tool-tab" 
                :class="{ active: imageGenEnabled }"
                @click="isMobile ? (isModeExpanded = true) : (imageGenEnabled = !imageGenEnabled)"
                title="Image Generation Mode"
              >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <circle cx="8.5" cy="8.5" r="1.5"></circle>
                  <polyline points="21 15 16 10 5 21"></polyline>
                </svg>
                <span>AI绘图</span>
              </button>
              
              <!-- Plan Mode Toggle -->
              <button 
                v-show="!isMobile || planEnabled"
                class="tool-tab" 
                :class="{ active: planEnabled }"
                @click="isMobile ? (isModeExpanded = true) : (planEnabled = !planEnabled)"
                title="Plan Mode"
              >
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
                <span>Plan模式</span>
              </button>
              
              <!-- Attachment -->
              <button class="icon-btn attachment-btn" title="Attach File" @click="$refs.fileInput.click()">
                <svg viewBox="0 0 24 24" width="20" height="20"><path d="M16.5 6v11.5c0 2.21-1.79 4-4 4s-4-1.79-4-4V5a2.5 2.5 0 0 1 5 0v10.5c0 .55-.45 1-1 1s-1-.45-1-1V6H10v9.5a2.5 2.5 0 0 0 5 0V5c0-2.21-1.79-4-4-4S7 2.79 7 5v12.5c0 3.04 2.46 5.5 5.5 5.5s5.5-2.46 5.5-5.5V6h-1.5z"/></svg>
              </button>
              <input type="file" ref="fileInput" @change="handleFileUpload" style="display: none" accept="image/*, .doc, .docx, .ppt, .pptx, .pdf, .xls, .xlsx, .txt, .csv, .json" multiple>
            </div>

            <button class="send-btn" @click="isSending ? stopGeneration() : sendMessage()" :disabled="!isSending && (!inputText.trim() && attachments.length === 0)" :title="isSending ? 'Stop generation' : 'Send message'">
              <svg v-if="!isSending" viewBox="0 0 24 24" width="20" height="20" fill="white"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
              <svg v-else viewBox="0 0 24 24" width="20" height="20" fill="white"><rect x="6" y="6" width="12" height="12" rx="2" ry="2" /></svg>
            </button>
          </div>
        </div>
      </div>
    </main>
    
    <!-- Mobile Mode Selection Modal -->
    <div v-if="isMobile && isModeExpanded" class="mobile-mode-modal-overlay" @click.self="isModeExpanded = false">
      <div class="mobile-mode-modal">
        <div class="modal-header">
          <h3>选择功能模式</h3>
          <button class="close-btn" @click="isModeExpanded = false">
            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        <div class="mode-list">
          <!-- Deep Thinking -->
          <div class="mode-item" :class="{ active: thinkingEnabled }" @click="thinkingEnabled = !thinkingEnabled">
            <div class="mode-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path>
                <path d="M12 6v6l4 2"></path>
              </svg>
            </div>
            <div class="mode-info">
              <div class="mode-name">深度思考</div>
              <div class="mode-desc">使用深度推理模型解决复杂问题</div>
            </div>
            <div class="mode-check">
              <div class="checkbox" :class="{ checked: thinkingEnabled }"></div>
            </div>
          </div>

          <!-- Web Search -->
          <div class="mode-item" :class="{ active: webSearchEnabled }" @click="webSearchEnabled = !webSearchEnabled">
            <div class="mode-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
            </div>
            <div class="mode-info">
              <div class="mode-name">联网搜索</div>
              <div class="mode-desc">搜索互联网获取实时信息</div>
            </div>
            <div class="mode-check">
              <div class="checkbox" :class="{ checked: webSearchEnabled }"></div>
            </div>
          </div>

          <!-- Image Generation -->
          <div class="mode-item" :class="{ active: imageGenEnabled }" @click="imageGenEnabled = !imageGenEnabled">
            <div class="mode-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                <polyline points="21 15 16 10 5 21"></polyline>
              </svg>
            </div>
            <div class="mode-info">
              <div class="mode-name">AI绘图</div>
              <div class="mode-desc">根据文本描述生成图片</div>
            </div>
            <div class="mode-check">
              <div class="checkbox" :class="{ checked: imageGenEnabled }"></div>
            </div>
          </div>
          
          <!-- Plan Mode -->
          <div class="mode-item" :class="{ active: planEnabled }" @click="planEnabled = !planEnabled">
            <div class="mode-icon">
              <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
              </svg>
            </div>
            <div class="mode-info">
              <div class="mode-name">Plan模式</div>
              <div class="mode-desc">生成并执行复杂计划</div>
            </div>
            <div class="mode-check">
              <div class="checkbox" :class="{ checked: planEnabled }"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { store, mutations, defaultRoles } from '../store';
import { streamChat, updateMemory } from '../api/chat';
import { MARKITDOWN_API_URL, WEBSCRAPE_API_URL } from '@/api/config';
import MarkdownRenderer from './MarkdownRenderer.vue';
import CharacterManager from './CharacterManager.vue';
import SettingsModal from './SettingsModal.vue';
import WelcomeWizard from './WelcomeWizard.vue';
import MarkdownIt from 'markdown-it';
import markdownItKatex from 'markdown-it-katex';
import hljs from 'highlight.js';
import 'highlight.js/styles/atom-one-dark.css';
import 'katex/dist/katex.min.css';
import 'github-markdown-css/github-markdown.css';
import html2pdf from 'html2pdf.js';
import { saveAs } from 'file-saver';

export default {
  name: 'ChatLayout',
  components: {
    MarkdownRenderer,
    CharacterManager,
    SettingsModal,
    WelcomeWizard
  },
  data() {
    return {
      inputText: '',
      isSidebarOpen: window.innerWidth > 768,
      isSending: false,
      isSettingsOpen: false,
      md: null,
      showScrollTip: false,
      isLockedToBottom: true,
      // Array of attachments: { type: 'image'|'file', url: string, content: string, name: string }
      attachments: [],
      isUploading: false,
      isDragging: false,
      abortController: null,
      activeMessageIndex: -1,
      exportMenuIndex: -1,
      defaultRoles, // Expose to template
      isCharacterManagerOpen: false,
      isConfirmDialogOpen: false,
      pendingCommand: '',
      isModeExpanded: false,
      windowWidth: typeof window !== 'undefined' ? window.innerWidth : 1024,
      showBackupReminder: false,
      backupReminderText: '',
    };
  },
  created() {
    this.md = new MarkdownIt({
      html: true,
      linkify: true,
      typographer: true,
      highlight: (str, lang) => {
        let highlighted = '';
        if (lang && hljs.getLanguage(lang)) {
          try {
            highlighted = hljs.highlight(str, { language: lang, ignoreIllegals: true }).value;
          } catch (__) {
            highlighted = this.md.utils.escapeHtml(str);
          }
        } else {
          highlighted = this.md.utils.escapeHtml(str);
        }
        return '<div class="code-block-wrapper"><button class="copy-code-btn">复制</button><pre class="hljs"><code>' + highlighted + '</code></pre></div>';
      }
    }).use(markdownItKatex);

    // Optimize: Add target="_blank" via custom renderer instead of regex
    const defaultLinkRender = this.md.renderer.rules.link_open || function(tokens, idx, options, env, self) {
      return self.renderToken(tokens, idx, options);
    };

    this.md.renderer.rules.link_open = function (tokens, idx, options, env, self) {
      // Add target="_blank" attribute
      const aIndex = tokens[idx].attrIndex('target');
      if (aIndex < 0) {
        tokens[idx].attrPush(['target', '_blank']);
      } else {
        tokens[idx].attrs[aIndex][1] = '_blank';
      }
      // Add rel="noopener noreferrer" for security
      const relIndex = tokens[idx].attrIndex('rel');
      if (relIndex < 0) {
        tokens[idx].attrPush(['rel', 'noopener noreferrer']);
      } else {
        tokens[idx].attrs[relIndex][1] = 'noopener noreferrer';
      }
      return defaultLinkRender(tokens, idx, options, env, self);
    };
  },
  computed: {
    hasNoModels() {
      return !store.settings.models || store.settings.models.length === 0;
    },
    isMobile() {
      return this.windowWidth <= 768;
    },
    brandTitle() {
      if (typeof window !== 'undefined' && window.location.hostname.includes('sioyie.com')) {
        return '芯毅AI';
      }
      return '雨见AI';
    },
    store() {
      return store;
    },
    theme() {
      return store.theme;
    },
    messages() {
      return store.history;
    },
    sessions() {
      return store.sessions;
    },
    currentSessionId() {
      return store.currentSessionId;
    },
    thinkingEnabled: {
      get() {
        return store.settings.thinkingEnabled;
      },
      set(val) {
        mutations.setThinkingEnabled(val);
      }
    },
    webSearchEnabled: {
      get() {
        return store.settings.webSearchEnabled;
      },
      set(val) {
        mutations.setWebSearchEnabled(val);
      }
    },
    imageGenEnabled: {
      get() {
        return store.settings.imageGenEnabled;
      },
      set(val) {
        mutations.setImageGenEnabled(val);
      }
    },
    planEnabled: {
      get() {
        return store.settings.planEnabled;
      },
      set(val) {
        mutations.setPlanEnabled(val);
      }
    },
    currentRole() {
      const id = store.roleSettings.activeRoleId;
      return this.allRoles.find(r => r.id === id) || defaultRoles[0];
    },
    allRoles() {
      const custom = store.roleSettings.customRoles || [];
      return [...defaultRoles, ...custom];
    },
    topRoles() {
      // Return all roles sorted by priority:
      // Priority 1: Current role
      // Priority 2: Recently used (from history)
      // Priority 3: Default order
      
      const history = store.roleSettings.roleHistory || [];
      const currentId = store.roleSettings.activeRoleId;
      
      // Get all roles map for quick access
      const rolesMap = new Map(this.allRoles.map(r => [r.id, r]));
      
      const result = [];
      const addedIds = new Set();
      
      // 1. Current Role
      if (rolesMap.has(currentId)) {
        result.push(rolesMap.get(currentId));
        addedIds.add(currentId);
      }
      
      // 2. History
      for (const id of history) {
        if (!addedIds.has(id) && rolesMap.has(id)) {
          result.push(rolesMap.get(id));
          addedIds.add(id);
        }
      }
      
      // 3. Fill with remaining roles
      for (const role of this.allRoles) {
        if (!addedIds.has(role.id)) {
          result.push(role);
          addedIds.add(role.id);
        }
      }
      
      return result;
    }
  },
  mounted() {
    window.addEventListener('resize', this.handleResize);
    this.checkBackupStatus();
    
    // Auto sync check
    if (mutations && mutations.checkCloudSync) {
        mutations.checkCloudSync();
    }

    // Listen for external doc additions
    window.addEventListener('rainshome-doc-added', this.handleDocAdded);
    
    // Add scroll and interaction listeners
    if (this.$refs.messagesContainer) {
      const container = this.$refs.messagesContainer;
      container.addEventListener('scroll', this.handleScroll);
      container.addEventListener('mousedown', this.handleUserInteraction);
      container.addEventListener('touchstart', this.handleUserInteraction);
      container.addEventListener('wheel', this.handleUserInteraction);
    }
  },
  beforeDestroy() {
    window.removeEventListener('resize', this.handleResize);
    window.removeEventListener('rainshome-doc-added', this.handleDocAdded);
    if (this.$refs.messagesContainer) {
      const container = this.$refs.messagesContainer;
      container.removeEventListener('scroll', this.handleScroll);
      container.removeEventListener('mousedown', this.handleUserInteraction);
      container.removeEventListener('touchstart', this.handleUserInteraction);
      container.removeEventListener('wheel', this.handleUserInteraction);
    }
  },
  methods: {
    handleFocus() {
        if (mutations && mutations.checkCloudSync) {
            mutations.checkCloudSync();
        }
    },
    handleResize() {
      this.windowWidth = window.innerWidth;
    },
    handleUserInteraction() {
      // Once user interacts with the container, unlock the bottom lock
      if (this.isLockedToBottom) {
        this.isLockedToBottom = false;
      }
    },
    handleScroll() {
      const container = this.$refs.messagesContainer;
      if (!container) return;
      
      const threshold = 5; // Reduce threshold for more precise detection
      const isNearBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) < threshold;
      
      // Update lock state based on user scroll
      this.isLockedToBottom = isNearBottom;

      // If user manually scrolls to bottom, hide the tip
      if (isNearBottom && this.showScrollTip) {
        this.showScrollTip = false;
      }
    },
    handleCopyCodeEvent(text, target) {
      this.copyMessage(text);
      
      const originalText = target.innerText;
      target.innerText = '已复制';
      setTimeout(() => {
        target.innerText = originalText;
      }, 2000);
    },
    // Legacy click handler
    handleCopyCodeClick(e) {
      const target = e.target;
      if (target.classList.contains('copy-code-btn')) {
        const wrapper = target.parentElement;
        const pre = wrapper.querySelector('pre');
        const codeBlock = pre ? pre.querySelector('code') : null;
        if (codeBlock) {
          const text = codeBlock.innerText || codeBlock.textContent;
          this.copyMessage(text);
          
          const originalText = target.innerText;
          target.innerText = '已复制';
          setTimeout(() => {
            target.innerText = originalText;
          }, 2000);
        }
      }
    },
    // Markdown render now handled by component, this is kept for legacy or special cases if any
    renderMarkdown(content) {
      if (!content) return '';
      // Fallback for parts not using component
      return this.md ? this.md.render(content) : content;
    },
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen;
    },
    toggleSettings() {
      this.isSettingsOpen = !this.isSettingsOpen;
    },
    closeSettings() {
      this.isSettingsOpen = false;
    },
    toggleTheme() {
      mutations.toggleTheme();
    },
    async createNewChat() {
      // updateCoreMemoryFromSession 维持在createNewChat后进行
      if (store.history.length > 0 || store.currentSessionId) {
        this.updateCoreMemoryFromSession();
        mutations.clearHistory();
      } else {
        mutations.setChatId(null);
      }
      if (window.innerWidth <= 768) {
        this.isSidebarOpen = false;
      }
    },
    async updateCoreMemoryFromSession() {
      // Mock API call to update Soul, User, Memory
      const lastUserMsg = store.history.filter(m => m.role === 'user').pop();
      
      if (!lastUserMsg) return;

      console.log('Updating core memory from session...');
      
      // Prepare Data
      const memoryData = {
        soul: store.soul,
        user: store.user,
        memory: store.memory,
        history: store.history
      };

      try {
          const updatedMemory = await updateMemory(memoryData);
          if (updatedMemory) {
             // Update store and DB
             if (updatedMemory.soul) await mutations.updateCoreMemory('soul', updatedMemory.soul);
             if (updatedMemory.user) await mutations.updateCoreMemory('user', updatedMemory.user);
             if (updatedMemory.memory) await mutations.updateCoreMemory('memory', updatedMemory.memory);
             console.log('Core memory updated successfully');
          }
      } catch (e) {
          console.error('Core memory update failed', e);
      }
    },
    loadSession(sessionId) {
      mutations.loadSession(sessionId);
      if (window.innerWidth <= 768) {
        this.isSidebarOpen = false;
      }
      this.scrollToBottom(true);
    },
    deleteSession(sessionId) {
      if (confirm('Delete this chat session?')) {
        mutations.deleteSession(sessionId);
      }
    },
    selectCurrentSession() {
      mutations.setCurrentSession();
      if (window.innerWidth <= 768) {
        this.isSidebarOpen = false;
      }
      this.scrollToBottom(true);
    },
    adjustHeight(e) {
      const el = e.target;
      el.style.height = 'auto';
      el.style.height = Math.min(el.scrollHeight, 150) + 'px';
    },
    handleDocAdded(e) {
      const text = e.detail;
      this.inputText = (this.inputText ? this.inputText + '\n' : '') + text;
      this.$nextTick(() => {
        this.adjustHeight({ target: this.$el.querySelector('textarea') });
      });
    },
    // Role Management
    toggleCharacterManager() {
      this.isCharacterManagerOpen = !this.isCharacterManagerOpen;
    },
    selectRole(roleOrId) {
      const id = typeof roleOrId === 'string' ? roleOrId : roleOrId.id;
      mutations.setActiveRole(id);
      this.isCharacterManagerOpen = false;
    },
    handleDragOver(e) {
      if (!this.isDragging) {
        this.isDragging = true;
      }
    },
    handleDragLeave(e) {
      // Check if we are really leaving the element, not just moving between children
      const rect = this.$el.querySelector('.input-area').getBoundingClientRect();
      if (
        e.clientX < rect.left ||
        e.clientX > rect.right ||
        e.clientY < rect.top ||
        e.clientY > rect.bottom
      ) {
        this.isDragging = false;
      }
    },
    async handleDrop(e) {
      this.isDragging = false;
      const files = Array.from(e.dataTransfer.files);
      if (files.length > 0) {
        for (const file of files) {
          await this.processFile(file);
        }
      }
    },
    async handlePaste(e) {
      const items = (e.clipboardData || e.originalEvent.clipboardData).items;
      for (const item of items) {
        if (item.kind === 'file') {
          const file = item.getAsFile();
          if (file) {
            await this.processFile(file);
          }
        }
      }
    },
    async handleFileUpload(event) {
      const files = Array.from(event.target.files);
      if (files.length === 0) return;
      for (const file of files) {
        await this.processFile(file);
      }
      event.target.value = ''; // Reset input
    },
    async processFile(file) {
      if (!file) return;

      this.isUploading = true;
      
      try {
        // Check if image or document
        if (file.type.startsWith('image/')) {
            const formdata = new FormData();
            formdata.append("file", file);

            const requestOptions = {
              method: 'POST',
              body: formdata,
              redirect: 'follow'
            };

            const response = await fetch("//picmain.rainsee.cn/api.php", requestOptions);
            const result = await response.text();
            
            // Try to parse JSON, fallback to raw text
            let url = result;
            try {
                const json = JSON.parse(result);
                // Adapt to common response formats
                if (json.url) url = json.url;
                else if (json.data && json.data.url) url = json.data.url;
                else if (json.link) url = json.link; 
            } catch (e) {
                // Not JSON, treat as raw URL string
            }
            
            if (url && url.startsWith('http')) {
                 this.attachments.push({
                   type: 'image',
                   url: url.trim(),
                   name: file.name
                 });
            } else {
                 console.error('Invalid upload response:', result);
                 alert('Upload failed or returned invalid URL.');
            }
        } else {
            // Handle document upload
            const formdata = new FormData();
            formdata.append("file", file); // Backend expects "file"
            
            const requestOptions = {
               method: 'POST',
               body: formdata,
               redirect: 'follow'
            };
            
            // Parallel execution: Get content AND upload for URL
            const [contentPromise, urlPromise] = [
                fetch(MARKITDOWN_API_URL, requestOptions),
                fetch("//picmain.rainsee.cn/api.php", requestOptions)
            ];
            
            const [contentResponse, urlResponse] = await Promise.all([contentPromise, urlPromise]);

            if (!contentResponse.ok) {
                throw new Error(`Upload failed: ${contentResponse.status} ${contentResponse.statusText}`);
            }
            
            const json = await contentResponse.json();
            
            // Process URL response
            let fileUrl = '';
            try {
                const urlResult = await urlResponse.text();
                try {
                    const urlJson = JSON.parse(urlResult);
                    if (urlJson.url) fileUrl = urlJson.url;
                    else if (urlJson.data && urlJson.data.url) fileUrl = urlJson.data.url;
                    else if (urlJson.link) fileUrl = urlJson.link;
                } catch (e) {
                     if (urlResult.startsWith('http')) fileUrl = urlResult.trim();
                }
            } catch (e) {
                console.error('Failed to get file URL:', e);
            }
            
            if (json && json.markdown) {
                this.attachments.push({
                   type: 'file',
                   content: json.markdown,
                   url: fileUrl,
                   name: file.name
                });
            } else {
                console.error('Invalid document response:', json);
                alert('Document processing failed.');
            }
        }

      } catch (error) {
        console.error('Upload error:', error);
        alert('Failed to upload file.');
      } finally {
        this.isUploading = false;
      }
    },
    removeAttachment(index) {
      this.attachments.splice(index, 1);
    },
    openImage(url) {
      window.open(url, '_blank');
    },
    handleMessageClick(index) {
      if (this.activeMessageIndex === index) {
        this.activeMessageIndex = -1;
        this.exportMenuIndex = -1;
      } else {
        this.activeMessageIndex = index;
      }
    },
    handleMouseEnter(index) {
      this.activeMessageIndex = index;
      // If we move to a new message, close any open export menus
      if (this.exportMenuIndex !== -1 && this.exportMenuIndex !== index) {
        this.exportMenuIndex = -1;
      }
    },
    handleMouseLeave(index) {
      if (this.exportMenuIndex !== index) {
        this.activeMessageIndex = -1;
        this.exportMenuIndex = -1;
      }
    },
    toggleExportMenu(index) {
      if (this.exportMenuIndex === index) {
        this.exportMenuIndex = -1;
      } else {
        this.exportMenuIndex = index;
      }
    },
    exportMessage(index, content, format) {
      console.log("content",content);
      this.exportMenuIndex = -1;
      
      if (format === 'markdown') {
        const blob = new Blob([content], { type: 'text/markdown;charset=utf-8' });
        saveAs(blob, `chat-export-${Date.now()}.md`);
        return;
      }

      const messageRows = this.$refs.messagesContainer.querySelectorAll('.message-row');
      if (!messageRows[index]) return;

      // Select only the main content, explicitly excluding the thinking section
      const contentEl = messageRows[index].querySelector('.text-content .markdown-body');
      if (!contentEl) return;

      if (format === 'pdf') {
        // Clone content to apply custom styles for PDF export
        const contentClone = contentEl.cloneNode(true);
        const wrapper = document.createElement('div');
        wrapper.style.position = 'absolute';
        wrapper.style.left = '-9999px';
        wrapper.style.top = '0';
        // Set width to simulate A4 paper content area (approx) to ensure correct layout
        wrapper.style.width = '700px'; 
        
        // Apply Chinese-friendly font settings
        contentClone.style.fontFamily = '"SimSun", "宋体", serif';
        
        wrapper.appendChild(contentClone);
        document.body.appendChild(wrapper);

        const opt = {
          margin: [15, 20, 15, 20], // Top, Left, Bottom, Right
          filename: `chat-export-${Date.now()}.pdf`,
          image: { type: 'jpeg', quality: 0.98 },
          html2canvas: { scale: 2, useCORS: true, logging: false },
          jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
        };
        
        html2pdf().set(opt).from(contentClone).save().finally(() => {
          if (document.body.contains(wrapper)) {
            document.body.removeChild(wrapper);
          }
        });

      } else if (format === 'word') {
        const header = `
          <html xmlns:o='urn:schemas-microsoft-com:office:office' 
                xmlns:w='urn:schemas-microsoft-com:office:word' 
                xmlns='http://www.w3.org/TR/REC-html40'>
          <head><meta charset='utf-8'><title>Export Document</title>
          <!--[if gte mso 9]>
          <xml>
            <w:WordDocument>
              <w:View>Print</w:View>
              <w:Zoom>100</w:Zoom>
              <w:DoNotOptimizeForBrowser/>
            </w:WordDocument>
          </xml>
          <![endif]-->
          <style>
             /* Standard Word Margins: Top/Bottom 2.54cm, Left/Right 3.17cm */
             @page Section1 {
                size: 21.0cm 29.7cm;
                margin: 2.54cm 3.17cm 2.54cm 3.17cm;
                mso-header-margin: 35.4pt;
                mso-footer-margin: 35.4pt;
                mso-paper-source: 0;
             }
             div.Section1 {
                page: Section1;
             }
             body { font-family: 'SimSun', '宋体', serif; line-height: 1.6; color: #333; }
             pre { background: #f5f5f5; padding: 10px; border-radius: 5px; white-space: pre-wrap; font-family: monospace; }
             code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
             table { border-collapse: collapse; width: 100%; margin: 10px 0; }
             th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
             th { background-color: #f2f2f2; }
             blockquote { border-left: 4px solid #ddd; padding-left: 10px; color: #666; margin: 10px 0; }
             img { max-width: 100%; height: auto; }
          </style>
          </head><body><div class="Section1">`;
        const footer = "</div></body></html>";
        const sourceHTML = header + contentEl.innerHTML + footer;
        
        const blob = new Blob(['\ufeff', sourceHTML], { type: 'application/msword' });
        saveAs(blob, `chat-export-${Date.now()}.doc`);
      }
    },
    copyMessage(content) {
      if (!content) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(content).then(() => {
          // Optional: show toast or success indicator
        }).catch(err => {
          console.error('Failed to copy: ', err);
        });
      } else {
        // Fallback for non-secure contexts or older browsers
        const textArea = document.createElement("textarea");
        textArea.value = content;
        
        // Ensure textarea is not visible but part of the DOM
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        textArea.style.top = "0";
        document.body.appendChild(textArea);
        
        textArea.focus();
        textArea.select();
        
        try {
          const successful = document.execCommand('copy');
          if (!successful) {
            console.error('Fallback copy failed');
          }
        } catch (err) {
          console.error('Fallback copy error: ', err);
        }
        
        document.body.removeChild(textArea);
      }
    },
    editMessage(index, message) {
      this.inputText = message.content || '';
      this.attachments = [];

      if (message.attachments && message.attachments.length > 0) {
        // Deep copy attachments to avoid reference issues
        this.attachments = JSON.parse(JSON.stringify(message.attachments));
      } else if (message.imageUrl) {
        // Legacy support
        this.attachments.push({
          type: 'image',
          url: message.imageUrl,
          name: 'Image'
        });
      }

      this.$nextTick(() => {
        const textarea = this.$el.querySelector('textarea');
        if (textarea) {
          textarea.focus();
          this.adjustHeight({ target: textarea });
        }
      });
      // Rollback history to before this message
      mutations.rollbackTo(index);
    },
    async scrapeUrl(url) {
      try {
        const myHeaders = new Headers();
        myHeaders.append("user-agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0");
        myHeaders.append("Content-Type", "application/json");

        const raw = JSON.stringify({
          "url": url,
          "engine": "playwright",
          "wait_for": 1000,
          "formats": ["markdown"]
        });

        const requestOptions = {
          method: 'POST',
          headers: myHeaders,
          body: raw,
          redirect: 'follow'
        };

        const response = await fetch(WEBSCRAPE_API_URL, requestOptions);
        const result = await response.json();
        
        if (result.success && result.data && result.data.markdown) {
          return {
            title: result.data.title,
            markdown: result.data.markdown
          };
        }
        return null;
      } catch (error) {
        console.error('Scrape error', error);
        return null;
      }
    },
    async sendMessage(payload, options = {}) {
      this.isConfirmDialogOpen = false;
      this.pendingCommand = '';
      
      const isManual = typeof payload === 'string';
      const text = (isManual ? payload : this.inputText).trim();
      const currentAttachments = isManual ? [] : [...this.attachments];
      
      if ((!text && currentAttachments.length === 0) || this.isSending) return;

      this.isSending = true;
      
      if (!isManual) {
        this.inputText = '';
        this.attachments = [];
      }
      
      // Reset textarea height
      this.$nextTick(() => {
        const textarea = this.$el.querySelector('.input-wrapper textarea');
        if (textarea) {
          this.adjustHeight({ target: textarea });
        }
      });
      
      // Add User Message
      mutations.addMessage({
        role: 'user',
        content: text,
        attachments: currentAttachments, // Store all attachments
        timestamp: Date.now(),
        isSearch: this.webSearchEnabled
      });

      // Prepare AI Message Placeholder
      const aiMsgIndex = store.history.length;
      mutations.addMessage({
        role: 'assistant',
        content: '',
        thinking: '',
        isThinking: false,
        timestamp: Date.now(),
        isSearch: this.webSearchEnabled
      });

      this.scrollToBottom(true);

      // Check for URL and scrape
      const urlRegex = /((https?:\/\/)?(www\.)?[-a-zA-Z0-9@:%._+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_+.~#?&//=]*))/;
      const match = text.match(urlRegex);
      if (match) {
        let url = match[0];
        if (!url.startsWith('http')) {
            url = 'https://' + url;
        }
        
        // Check if URL points to a static file (image, video, archive, document)
        const isStaticFile = /\.(jpg|jpeg|png|gif|bmp|webp|svg|ico|mp3|wav|ogg|mp4|avi|mov|webm|zip|rar|7z|tar|gz|pdf|doc|docx|xls|xlsx|ppt|pptx)$/i.test(url.split('?')[0]);

        if (!isStaticFile) {
            // Optional: show a toast or indicator that we are scraping
            const scraped = await this.scrapeUrl(url);
            if (scraped) {
               currentAttachments.push({
                 type: 'file',
                 content: scraped.markdown,
                 name: scraped.title || 'Scraped Content'
               });
            }
        }
      }
      
      this.abortController = new AbortController();

      // Prepare Context with Role System Prompt
      let context = store.history.slice(0, -1);
      const rolePrompt = this.currentRole.systemPrompt;
      
      // Inject Core Memory (Soul, User, Memory)
      const soul = store.soul?.personality || '';
      const user = store.user?.profile || '';
      const memory = store.memory?.longTerm ? JSON.stringify(store.memory.longTerm) : '';
      
      const memoryContext = `
[Core Memory System]
Soul (Personality): ${soul}
User Profile: ${user}
Long-term Memory: ${memory}
`;

      let systemContent = rolePrompt || '';
      if (memoryContext) {
          systemContent = `${systemContent}\n${memoryContext}`;
      }
      
      // Plan Mode Logic
      if (this.planEnabled) {
          options.plan_mode = true;
      }

      if (systemContent) {
        // Prepend system prompt to the context sent to API
        // We don't add it to store.history to keep UI clean
        context = [{ role: 'system', content: systemContent }, ...context];
      }

      await streamChat(text, context, {
        onContent: (chunk) => {
          const currentMsg = store.history[aiMsgIndex];
          // Collapse thinking block when formal content starts
          if (currentMsg.isThinking) {
             currentMsg.isThinking = false;
          }
          // If we were generating image, turn off skeleton once we get content
          if (currentMsg.isGeneratingImage) {
             currentMsg.isGeneratingImage = false;
          }
          currentMsg.content += chunk;
          this.scrollToBottom();
        },
        onThinking: (chunk) => {
          const currentMsg = store.history[aiMsgIndex];
          currentMsg.thinking += chunk;
          currentMsg.isThinking = true; // Open details automatically
          this.scrollToBottom();
        },
        onChatId: (id) => {
          console.log('[DEBUG] Received chat_id:', id);
          mutations.setChatId(id);
        },
        onImageStart: () => {
          const currentMsg = store.history[aiMsgIndex];
          // Enable skeleton loader
          // Use Vue.set or re-assign to ensure reactivity if property wasn't there
          this.$set(currentMsg, 'isGeneratingImage', true);
          this.scrollToBottom();
        },
        onDecision: (content) => this.handleDecision(content),
        onDone: () => {
          console.log('[DEBUG] onDone callback triggered');
          this.isSending = false;
          this.abortController = null;
          console.log('[DEBUG] Calling mutations.finalizeLastMessage()');
          try {
              mutations.finalizeLastMessage();
              console.log('[DEBUG] mutations.finalizeLastMessage() completed');
          } catch (e) {
              console.error('[DEBUG] Error in finalizeLastMessage:', e);
          }
          
          // Plan Mode Completion Logic
          const lastMsg = store.history[aiMsgIndex];
          if (this.planEnabled && lastMsg && lastMsg.content.includes('[PLAN_READY]')) {
              const parts = lastMsg.content.split('[PLAN_READY]');
              if (parts.length > 1) {
                  const summary = parts[1].trim();
                  
                  // Automatically trigger execution with the summarized plan
                  setTimeout(() => {
                      this.planEnabled = false; // Turn off Plan Mode
                      this.sendMessage(`Here is the finalized plan. Please execute it now:\n\n${summary}`);
                  }, 500);
              }
          }
        },
        onError: (err) => {
          if (err.name === 'AbortError') {
             console.log('Generation stopped by user');
             this.isSending = false;
             this.abortController = null;
             // Optional: Add a system note that generation was stopped?
             return;
          }
          console.error(err);
          this.isSending = false;
          this.abortController = null;
          const currentMsg = store.history[aiMsgIndex];
          currentMsg.content += '\n[Error: Connection failed]';
          this.scrollToBottom();
        },
        signal: this.abortController.signal
      }, currentAttachments, options);
    },
    stopGeneration() {
      if (this.abortController) {
        this.abortController.abort();
        this.abortController = null;
        this.isSending = false;
      }
    },
    scrollToBottom(force = false) {
      const container = this.$refs.messagesContainer;
      if (!container) return;

      if (force) {
        this.isLockedToBottom = true;
        this.showScrollTip = false;
      }

      // If not locked to bottom and not forced, do nothing (unless sending, then maybe show tip)
      if (!this.isLockedToBottom && !force) {
          if (this.isSending) {
              this.showScrollTip = true;
          }
          return;
      }

      // Optimization: Throttle scroll updates using requestAnimationFrame
      if (this._scrollFrame) {
          return; // Already scheduled
      }
      
      this._scrollFrame = requestAnimationFrame(() => {
        this._scrollFrame = null;
        
        // Use nextTick to ensure DOM is updated
        this.$nextTick(() => {
             const targetTop = container.scrollHeight;
             // Check if we are already at bottom to avoid unnecessary setting (which might trigger scroll events)
             if (Math.abs(container.scrollTop - (targetTop - container.clientHeight)) < 2) {
                 return;
             }
             
             if (force) {
               container.scrollTo({
                 top: targetTop,
                 behavior: 'smooth'
               });
             } else {
               // Direct assignment is significantly smoother during rapid streaming
               container.scrollTop = targetTop;
             }
        });
      });
    },
    handleDecision(content) {
      console.log('Decision needed:', content);
      try {
        if (!content) {
             console.error('Empty decision content');
             return;
        }
        const data = typeof content === 'string' ? JSON.parse(content) : content;
        
        if (data && data.args && data.args.command) {
            this.pendingCommand = data.args.command;
            this.isConfirmDialogOpen = true;
        } else if (data && data.type === 'confirmation') {
             // Fallback for missing command but valid type
             console.warn('Decision content missing command:', data);
             this.pendingCommand = data.args && data.args.command ? data.args.command : "(No command found in arguments)";
             this.isConfirmDialogOpen = true;
        } else {
             console.warn('Invalid decision data structure:', data);
        }
      } catch (e) {
          console.error('Error parsing decision content:', e);
      }
    },
    async confirmCommand() {
      this.isConfirmDialogOpen = false;
      const cmd = this.pendingCommand;
      this.pendingCommand = '';
      
      const aiMsgIndex = this.messages.length - 1;
      this.isSending = true;
      this.abortController = new AbortController();
      
      // Use existing messages as context
      const context = this.messages;

      // We send a system prompt to resume execution
      // The prompt text is just a carrier, the real action is triggered by confirmed_command
      const prompt = "System: User confirmed command execution.";

      try {
        await streamChat(prompt, context, {
          onContent: (chunk) => {
            const currentMsg = this.messages[aiMsgIndex];
            if (currentMsg) {
                currentMsg.content += chunk;
                this.scrollToBottom();
            }
          },
          onThinking: (chunk) => {
             const currentMsg = this.messages[aiMsgIndex];
             if (currentMsg) {
                if (!currentMsg.thinking) currentMsg.thinking = '';
                currentMsg.thinking += chunk;
                currentMsg.isThinking = true;
                this.scrollToBottom();
             }
          },
          onDecision: this.handleDecision,
          onDone: () => {
             this.isSending = false;
             this.abortController = null;
             mutations.finalizeLastMessage();
          },
          onError: (err) => {
             console.error(err);
             this.isSending = false;
             this.abortController = null;
          },
          signal: this.abortController.signal
        }, [], { confirmed_command: cmd });
      } catch (err) {
          console.error('Confirm Command Error:', err);
          this.isSending = false;
      }
    },
    cancelCommand() {
      if (this.isSending) {
        this.stopGeneration();
        // Ensure flag is reset even if stopGeneration didn't (e.g. no abortController)
        this.isSending = false;
      }
      this.isConfirmDialogOpen = false;
      this.pendingCommand = '';
      this.sendMessage("User cancelled the command execution.", { disable_claude_tool: true });
    },
    // Backup Reminder Methods
    checkBackupStatus() {
      const lastTime = localStorage.getItem("lastBackupTime");
      const days = store.settings.backupReminderDays || 1;
      
      if (!lastTime) {
         if (store.history.length > 0 || store.sessions.length > 0) {
             this.showBackupReminder = true;
             this.backupReminderText = '您尚未备份过数据，点击此处导出备份。';
         }
         return;
      }

      const now = Date.now();
      const diff = now - lastTime;
      const diffDays = Math.floor(diff / (1000 * 60 * 60 * 24));

      if (diffDays >= days) {
        this.showBackupReminder = true;
        this.backupReminderText = `您已经 ${diffDays} 天没备份了，点击立即备份。`;
      }
    },
    async handleBackupClick(e) {
      try {
        const backup = await mutations.getFullBackup();
        const blob = new Blob([JSON.stringify(backup, null, 2)], { type: 'application/json' });
        const date = new Date().toISOString().split('T')[0];
        saveAs(blob, `ai-assistant-backup-${date}.json`);
        this.showBackupReminder = false;
      } catch (err) {
        console.error('Export failed', err);
        alert('导出失败: ' + err.message);
      }
    }
  }
};
</script>

<style scoped>
/* Backup Reminder Toast */
.header-left {
  display: flex;
  align-items: center;
}

.header-right {
  display: flex;
  align-items: center;
}

.backup-btn-animated {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  border: none;
  background: linear-gradient(135deg, #a18cd1 0%, #fbc2eb 100%);
  color: white;
  border-radius: 20px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(161, 140, 209, 0.4);
  text-shadow: 0 1px 2px rgba(0,0,0,0.1);
  position: relative;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.backup-btn-animated:hover {
  transform: translateY(-2px) scale(1.05);
  box-shadow: 0 6px 16px rgba(251, 194, 235, 0.5);
}

.backup-btn-animated:active {
  transform: translateY(0) scale(0.95);
}

.backup-icon-wrapper {
  display: flex;
  align-items: center;
  animation: bounce-icon 2s infinite;
}

.backup-text {
  white-space: nowrap;
}

.pulse-ring {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 100%;
  height: 100%;
  border-radius: 20px;
  border: 2px solid rgba(255, 255, 255, 0.5);
  animation: pulse-ring 2s cubic-bezier(0.215, 0.61, 0.355, 1) infinite;
  opacity: 0;
}

@keyframes pulse-ring {
  0% {
    transform: translate(-50%, -50%) scale(0.8);
    opacity: 0.8;
  }
  100% {
    transform: translate(-50%, -50%) scale(1.5);
    opacity: 0;
  }
}

@keyframes bounce-icon {
  0%, 20%, 50%, 80%, 100% {
    transform: translateY(0);
  }
  40% {
    transform: translateY(-3px);
  }
  60% {
    transform: translateY(-1.5px);
  }
}

.bounce-enter-active {
  animation: bounce-in 0.5s;
}
.bounce-leave-active {
  animation: bounce-in 0.5s reverse;
}
@keyframes bounce-in {
  0% {
    transform: scale(0);
  }
  50% {
    transform: scale(1.2);
  }
  100% {
    transform: scale(1);
  }
}

/* Code Block Copy Button */
:deep(.code-block-wrapper) {
  position: relative;
  margin-bottom: 1rem;
}

:deep(.code-block-wrapper pre.hljs) {
  margin-bottom: 0;
  padding-top: 2.5em; /* Ensure space for button if content is long/wide */
  background-color: #0d0d0d !important; /* Deep black background as requested */
  color: #abb2bf; /* Comfortable text color from atom-one-dark */
  border-radius: 8px;
}

:deep(.code-block-wrapper pre.hljs code) {
  font-family: 'Fira Code', 'Consolas', monospace;
  background-color: transparent !important;
  color: inherit !important;
}

:deep(.copy-code-btn) {
  position: absolute;
  top: 6px;
  right: 6px;
  background-color: rgba(255, 255, 255, 0.9);
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 4px;
  padding: 4px 8px;
  font-size: 12px;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s;
  color: #333;
  z-index: 10;
  font-family: inherit;
  font-weight: 500;
}

:deep(.code-block-wrapper:hover .copy-code-btn) {
  opacity: 1;
}

[data-theme="dark"] :deep(.copy-code-btn) {
  background-color: rgba(60, 60, 60, 0.9);
  border-color: rgba(255, 255, 255, 0.1);
  color: #eee;
}

/* Global Layout - Modern High-End Flat Design */
.chat-layout {
  display: flex;
  height: 100vh;
  background-color: var(--bg-main, #ffffff);
  color: var(--text-primary, #1f1f1f);
  overflow: hidden;
  font-family: -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  letter-spacing: -0.01em;
}

[data-theme="dark"] .chat-layout {
  --bg-main: #111111;
  --text-primary: #ececec;
  --text-secondary: #a1a1a1;
  background-color: var(--bg-main);
  color: var(--text-primary);
}

/* Sidebar - Modern & Flat */
.sidebar {
  width: 0;
  opacity: 0;
  background: #fcfcfc;
  border-right: 1px solid rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  z-index: 100;
  overflow: hidden;
}

[data-theme="dark"] .sidebar {
  background: #161616;
  border-right: 1px solid rgba(255, 255, 255, 0.04);
}

.sidebar.sidebar-open {
  width: 260px; /* Compact width */
  opacity: 1;
}

.sidebar-header {
  padding: 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.brand-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
  opacity: 0.9;
}

/* New Chat Button - Minimalist */
.new-chat-container {
  padding: 0 0.75rem 0.75rem 0.75rem;
}

.new-chat-btn {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.7rem;
  background: #fff;
  color: #333;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 10px;
  font-weight: 500;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}

[data-theme="dark"] .new-chat-btn {
  background: #222;
  color: #fff;
  border-color: rgba(255,255,255,0.08);
}

.new-chat-btn:hover {
  background: #f4f4f4;
  transform: translateY(-1px);
}

[data-theme="dark"] .new-chat-btn:hover {
  background: #2a2a2a;
}

/* History List - Compact */
.history-list {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.history-item {
  padding: 0.6rem 0.75rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.85rem;
  color: #666;
  transition: all 0.2s ease;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  position: relative;
  background: transparent;
  border: none;
}

[data-theme="dark"] .history-item {
  color: #aaa;
}

.history-item:hover {
  background-color: rgba(0, 0, 0, 0.04);
  color: #000;
}

[data-theme="dark"] .history-item:hover {
  background-color: rgba(255, 255, 255, 0.04);
  color: #fff;
}

.history-item.active {
  background-color: rgba(0, 0, 0, 0.06);
  color: #000;
  font-weight: 600;
}

[data-theme="dark"] .history-item.active {
  background-color: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.session-title {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.delete-session-btn {
  opacity: 0;
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
  transition: opacity 0.2s ease;
  display: flex;
}

.history-item:hover .delete-session-btn {
  opacity: 0.5;
}

.delete-session-btn:hover {
  opacity: 1 !important;
  color: #ef4444;
}

.sidebar-footer {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-top: 1px solid rgba(0,0,0,0.04);
}

[data-theme="dark"] .sidebar-footer {
  border-top: 1px solid rgba(255,255,255,0.04);
}

/* Main Chat Area - Clean & Spacious */
.chat-main {
      overflow-x: auto;
    width: 100%;
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  background-color: var(--bg-main);
}

.chat-header {
  padding: 0.75rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  background-color: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  position: sticky;
  top: 0;
  z-index: 10;
  border-bottom: 1px solid rgba(0,0,0,0.02);
}

[data-theme="dark"] .chat-header {
  background-color: rgba(17, 17, 17, 0.7);
  border-bottom: 1px solid rgba(255,255,255,0.02);
}

.menu-btn {
  margin-right: 0.5rem;
  color: var(--text-secondary);
  opacity: 0.7;
  background: none;
  border: none;
  cursor: pointer;
}

.menu-btn:hover {
  opacity: 1;
}

.header-title {
  font-weight: 600;
  font-size: 0.95rem;
  color: var(--text-primary);
}

.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 2rem 15%; /* Centralized focus */
  padding-bottom: 140px;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  overflow-anchor: none;
}

@media (max-width: 1024px) {
  .messages-container {
    padding: 2rem 5%;
        padding-bottom: 140px !important;
  }
}

/* Empty State */
.empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
  opacity: 0.6;
  gap: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-primary);
  opacity: 0.8;
}

/* Messages - Flat & Modern */
.message-row {
  display: flex;
  margin-bottom: 0.5rem;
  animation: fadeIn 0.2s ease-out;
}

.message-row.user {
  justify-content: flex-end;
}

.message-row.assistant {
  justify-content: flex-start;
}

.message-wrapper {
  max-width: 85%;
  display: flex;
  flex-direction: column;
}

.message-row.user .message-wrapper {
  align-items: flex-end;
}
.message-row.assistant .message-wrapper {
   width: 85%;
}


/* Message Attachments */
.message-attachments {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
}

.message-attachment-item {
  display: flex;
  flex-direction: column;
}

.message-attachment-item img {
  max-width: 100%;
  max-height: 300px;
  border-radius: 8px;
  cursor: pointer;
  display: block;
}

.message-attachment {
  margin-bottom: 0.5rem;
}

.message-content {
  width:100% !important;
  width: fit-content;
  max-width: 100%;
  padding: 0.75rem 1rem; /* Compact padding */
  border-radius: 12px;
  font-size: 0.95rem;
  line-height: 1.6;
  position: relative;
  transition: all 0.2s;
}

/* File Attachment in Message */
.file-attachment-bubble {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
  border: 1px solid rgba(0, 0, 0, 0.03);
}

[data-theme="dark"] .file-attachment-bubble {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.05);
}

.file-icon-small {
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--text-secondary);
}

.message-row.user .file-attachment-bubble {
  background: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.1);
  color: #fff;
}

.message-row.user .file-icon-small {
  color: rgba(255, 255, 255, 0.8);
}

[data-theme="dark"] .message-row.user .file-attachment-bubble {
  background: rgba(0, 0, 0, 0.2);
  color: #000;
}

[data-theme="dark"] .message-row.user .file-icon-small {
  color: rgba(0, 0, 0, 0.6);
}

/* User Message Bubble */
.message-row.user .message-content {
  background: #000; /* High-end Black */
  color: #fff;
  border-radius: 14px 14px 2px 14px; /* Subtle shape */
  white-space: pre-wrap; /* Preserve newlines */
  word-break: break-word; /* Prevent long words from breaking layout */
}

[data-theme="dark"] .message-row.user .message-content {
  background: #fff;
  color: #000;
}

/* Assistant Message Bubble - Refined */
.message-row.assistant .message-content {
  background-color: rgba(0, 0, 0, 0.03); /* Subtle background for better readability */
  color: var(--text-primary);
  border-radius: 4px 14px 14px 14px; /* Organic shape */
  padding: 0.75rem 1rem; /* Consistent padding */
}

[data-theme="dark"] .message-row.assistant .message-content {
  background-color: rgba(255, 255, 255, 0.06);
}

.message-actions {
  display: flex;
  gap: 8px;
  margin-top: 4px;
  opacity: 0;
  transition: opacity 0.2s ease;
  pointer-events: none;
  padding-right: 4px;
}

.message-actions.visible {
  opacity: 1;
  pointer-events: auto;
}

.action-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 0.75rem;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
  transition: all 0.2s;
}

.action-btn:hover {
  background-color: rgba(0,0,0,0.05);
  color: var(--primary-color);
}

/* Export Menu Styles */
.export-dropdown-wrapper {
  position: relative;
  display: inline-block;
}

.export-menu {
  position: absolute;
  top: 100%;
  padding-top: 5px; /* Create a bridge area */
  left: 0;
  z-index: 100;
}

.export-menu-content {
  background-color: #ffffff;
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  min-width: 120px;
  overflow: hidden;
  animation: fadeIn 0.2s ease-out;
}

[data-theme="dark"] .export-menu-content {
  background-color: #222222;
  border-color: rgba(255, 255, 255, 0.08);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.export-item {
  padding: 8px 12px;
  font-size: 0.8rem;
  color: var(--text-primary);
  cursor: pointer;
  transition: background-color 0.2s;
  white-space: nowrap;
}

.export-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

[data-theme="dark"] .export-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

/* Thinking Block - Modern & Minimalist (Quote Style) */
.thinking-block {
  margin-bottom: 0.5rem;
  border-left: 3px solid rgba(0, 0, 0, 0.1);
  background: transparent;
  border-radius: 0;
  padding-left: 0.25rem;
  transition: all 0.3s ease;
}

[data-theme="dark"] .thinking-block {
  border-left-color: rgba(255, 255, 255, 0.15);
  background: transparent;
}

.thinking-block.is-thinking {
  border-left-color: var(--primary-color, #000);
  animation: border-pulse 2s infinite;
  background: rgba(0, 0, 0, 0.01); /* Very subtle highlight when active */
}

[data-theme="dark"] .thinking-block.is-thinking {
  border-left-color: #fff;
  background: rgba(255, 255, 255, 0.02);
}

@keyframes border-pulse {
  0% { border-left-color: rgba(0, 0, 0, 0.1); opacity: 0.8; }
  50% { border-left-color: rgba(0, 0, 0, 0.6); opacity: 1; }
  100% { border-left-color: rgba(0, 0, 0, 0.1); opacity: 0.8; }
}

[data-theme="dark"] @keyframes border-pulse {
  0% { border-left-color: rgba(255, 255, 255, 0.15); }
  50% { border-left-color: rgba(255, 255, 255, 0.6); }
  100% { border-left-color: rgba(255, 255, 255, 0.15); }
}

.thinking-block summary {
  padding: 0.4rem 0.5rem;
  cursor: pointer;
  list-style: none;
  outline: none;
  user-select: none;
  border-radius: 4px;
  transition: background 0.2s;
}

.thinking-block summary:hover {
  background: rgba(0, 0, 0, 0.03);
}

[data-theme="dark"] .thinking-block summary:hover {
  background: rgba(255, 255, 255, 0.05);
}

.thinking-block summary::-webkit-details-marker {
  display: none;
}

.thinking-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-secondary);
  font-size: 0.8rem;
  font-weight: 500;
}

.thinking-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  color: inherit;
  opacity: 0.6;
}

.thinking-title {
  flex: 1;
  opacity: 0.8;
  font-size: 0.75rem; /* Smaller, less intrusive */
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.chevron-icon {
  display: flex;
  align-items: center;
  transition: transform 0.3s ease;
  opacity: 0.4;
}

details[open] .chevron-icon {
  transform: rotate(180deg);
}

.thinking-content {
  padding: 0.25rem 0.5rem 0.5rem 0.5rem;
}

.thinking-text {
  font-size: 0.85rem;
  line-height: 1.6;
  color: var(--text-secondary);
  opacity: 0.9;
  border-top: none; /* Removed border */
  padding-top: 0;
}

[data-theme="dark"] .thinking-text {
  border-top-color: rgba(255, 255, 255, 0.05);
}

.thinking-text.markdown-body {
  font-size: 0.85rem !important;
}

.thinking-text.markdown-body p {
  margin-bottom: 0.5rem;
}

/* Typing Indicator */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  height: 24px;
  padding-left: 0;
}

.typing-indicator span {
  width: 5px;
  height: 5px;
  background-color: var(--text-secondary);
  border-radius: 50%;
  display: inline-block;
  opacity: 0.4;
  animation: typing-bounce 1.4s infinite ease-in-out both;
}

.typing-indicator span:nth-child(1) { animation-delay: -0.32s; }
.typing-indicator span:nth-child(2) { animation-delay: -0.16s; }

/* Input Area - Floating Surface */
.input-area {
  position: absolute;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  width: 90%;
  max-width: 768px;
  z-index: 20;
  transition: all 0.3s ease;
}

.input-area.dragging {
  transform: translateX(-50%) scale(1.02);
}

.input-wrapper {
  background-color: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-radius: 16px; /* Smooth corners */
  padding: 0.6rem 0.8rem;
  border: 1px solid rgba(0, 0, 0, 0.06);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.06); /* Soft diffuse shadow */
  transition: all 0.2s ease;
}

[data-theme="dark"] .input-wrapper {
  background-color: rgba(30, 30, 30, 0.85);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.input-wrapper:focus-within {
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
  transform: translateY(-1px);
  border-color: rgba(0,0,0,0.1);
}

textarea {
  width: 100%;
  border: none;
  background: transparent;
  resize: none;
  outline: none;
  font-family: inherit;
  font-size: 0.95rem;
  color: var(--text-primary);
  max-height: 200px;
  padding: 0.5rem;
  line-height: 1.5;
}

/* Toolbar - Compact */
.toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 0.25rem;
  padding: 0 0.25rem;
}

.left-tools {
  display: flex;
  gap: 0.25rem;
  align-items: center;
  flex: 1;
  min-width: 0;
  flex-wrap: wrap;
}

.tool-divider {
  width: 1px;
  height: 16px;
  background-color: rgba(0, 0, 0, 0.08);
  margin: 0 4px;
  flex-shrink: 0;
}

[data-theme="dark"] .tool-divider {
  background-color: rgba(255, 255, 255, 0.08);
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  color: var(--text-secondary);
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-btn:hover {
  background-color: rgba(0,0,0,0.05);
  color: #000;
}

[data-theme="dark"] .icon-btn:hover {
  background-color: rgba(255,255,255,0.1);
  color: #fff;
}

.tool-tab {
  background: rgba(0,0,0,0.03);
  border: none;
  cursor: pointer;
  padding: 4px 10px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  gap: 6px;
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
  transition: all 0.2s;
}

[data-theme="dark"] .tool-tab {
  background: rgba(255,255,255,0.05);
}

.tool-tab:hover {
  background: rgba(0,0,0,0.06);
  color: inherit;
}

.tool-tab.active {
  background: #000;
  color: #fff;
}

[data-theme="dark"] .tool-tab.active {
  background: #fff;
  color: #000;
}

.send-btn {
  background: #000;
  color: #fff;
  border: none;
  border-radius: 10px;
  width: 32px;
  height: 32px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  box-shadow: none;
}

[data-theme="dark"] .send-btn {
  background: #fff;
  color: #000;
}

.send-btn:hover:not(:disabled) {
  transform: scale(1.05);
  background: #333;
}

.send-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
  background: #ccc;
}

/* Markdown Cleanups */
.markdown-body {
      word-break: break-all;
  font-family: inherit !important;
  line-height: 1.6 !important;
  background-color: transparent !important;
  color: inherit !important;
}

.message-row.user .markdown-body {
  white-space: pre-wrap; /* Preserve newlines specifically for user messages */
}

.markdown-body p:last-child {
  margin-bottom: 0;
}

.markdown-body p:first-child {
  margin-top: 0;
}

.markdown-body pre {

  background-color: rgba(0, 0, 0, 0.03) !important;
  border-radius: 8px;
  padding: 0.8rem;
  margin: 0.5rem 0;
  border: none !important;
  overflow-x: auto;
  max-width: 100%;
}

[data-theme="dark"] .markdown-body pre {
  background-color: rgba(255, 255, 255, 0.05) !important;
}

.markdown-body code {
  font-family: 'SF Mono', Consolas, monospace;
  background-color: rgba(0, 0, 0, 0.04) !important;
  padding: 0.2em 0.4em;
  border-radius: 4px;
  font-size: 0.85em;
}

[data-theme="dark"] .markdown-body code {
  background-color: rgba(255, 255, 255, 0.1) !important;
}

.message-row.user .markdown-body code {
  background-color: rgba(255, 255, 255, 0.2) !important;
  color: #fff !important;
}

/* Scroll Tip */
.scroll-tip {
  position: absolute;
  bottom: 100%;
  left: 0;
  right: 0;
  display: flex;
  justify-content: center;
  padding-bottom: 10px;
  z-index: 50;
  pointer-events: none;
}

.scroll-tip-content {
  pointer-events: auto;
  background: rgba(0, 0, 0, 0.5);
  color: #fff;
  padding: 8px 16px;
  border-radius: 24px;
  font-size: 0.85rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  transition: all 0.2s ease;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .scroll-tip-content {
  background: rgba(255, 255, 255, 0.6);
  color: #000;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.scroll-tip-content:hover {
  transform: translateY(-2px);
}

/* Attachment Preview */
.attachment-preview {
  padding: 0.75rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

[data-theme="dark"] .attachment-preview {
  border-bottom-color: rgba(255, 255, 255, 0.05);
}

.uploading-spinner {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
  color: var(--text-secondary);
}

.spinner {
  width: 16px;
  height: 16px;
  animation: rotate 2s linear infinite;
}

.spinner .path {
  stroke: var(--text-secondary);
  stroke-linecap: round;
  animation: dash 1.5s ease-in-out infinite;
}

/* Mobile Mode Modal */
.mobile-mode-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 1000;
  display: flex;
  align-items: flex-end; /* Bottom sheet style */
  animation: fadeIn 0.2s ease-out;
}

.mobile-mode-modal {
  background-color: var(--bg-main);
  width: 100%;
  border-top-left-radius: 20px;
  border-top-right-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  max-height: 80vh;
  overflow-y: auto;
}

[data-theme="dark"] .mobile-mode-modal {
  background-color: #1e1e1e;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: var(--text-secondary);
  padding: 0.25rem;
}

.mode-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.mode-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: rgba(0, 0, 0, 0.03);
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

[data-theme="dark"] .mode-item {
  background-color: rgba(255, 255, 255, 0.05);
}

.mode-item.active {
  background-color: rgba(0, 0, 0, 0.06);
  border-color: rgba(0, 0, 0, 0.1);
}

[data-theme="dark"] .mode-item.active {
  background-color: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.1);
}

.mode-icon {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: var(--bg-main);
  border-radius: 10px;
  color: var(--text-primary);
  flex-shrink: 0;
}

[data-theme="dark"] .mode-icon {
  background-color: #2c2c2c;
}

.mode-info {
  flex: 1;
}

.mode-name {
  font-weight: 600;
  font-size: 0.95rem;
  margin-bottom: 0.25rem;
}

.mode-desc {
  font-size: 0.8rem;
  color: var(--text-secondary);
  line-height: 1.3;
}

.mode-check {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.checkbox {
  width: 20px;
  height: 20px;
  border: 2px solid var(--text-secondary);
  border-radius: 50%;
  position: relative;
  transition: all 0.2s;
}

.checkbox.checked {
  background-color: #000;
  border-color: #000;
}

[data-theme="dark"] .checkbox.checked {
  background-color: #fff;
  border-color: #fff;
}

.checkbox.checked::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 10px;
  height: 10px;
  background-color: #fff;
  border-radius: 50%;
}

[data-theme="dark"] .checkbox.checked::after {
  background-color: #000;
}

@keyframes slideUp {
  from { transform: translateY(100%); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes rotate {
  100% { transform: rotate(360deg); }
}

@keyframes dash {
  0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
  50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
  100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
}

.attachments-list {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.preview-item {
  position: relative;
  display: flex;
  align-items: center;
}

.preview-img-container {
  position: relative;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.preview-img-container img {
  display: block;
  max-height: 60px;
  max-width: 100px;
  object-fit: cover;
}

.preview-file-container {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.75rem;
  background: rgba(0, 0, 0, 0.03);
  border-radius: 8px;
  font-size: 0.85rem;
  max-width: 150px;
}

[data-theme="dark"] .preview-file-container {
  background: rgba(255, 255, 255, 0.05);
}

.file-name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.remove-btn {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 18px;
  height: 18px;
  background: #000;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  border: 1px solid rgba(255, 255, 255, 0.2);
  z-index: 2;
  transition: transform 0.2s;
}

[data-theme="dark"] .remove-btn {
  background: #333;
  color: #fff;
}

.remove-btn:hover {
  transform: scale(1.1);
}

/* Role Chip & Panel */
.active-role-chip {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 4px 10px;
  background-color: rgba(0,0,0,0.03);
  border: 1px solid rgba(0,0,0,0.05);
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--text-primary);
  cursor: pointer;
  transition: all 0.2s;
}

[data-theme="dark"] .active-role-chip {
  background-color: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
}

.remove-role-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 1rem;
  padding: 0 2px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-left: 2px;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  transition: all 0.2s;
}

.remove-role-btn:hover {
  background-color: rgba(0,0,0,0.1);
  color: var(--text-primary);
}

[data-theme="dark"] .remove-role-btn:hover {
  background-color: rgba(255,255,255,0.2);
}

.role-expanded-panel {
  position: absolute;
  bottom: calc(100% + 12px);
  left: 0;
  right: 0;
  background-color: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(0, 0, 0, 0.08);
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
  padding: 1rem;
  z-index: 100;
  backdrop-filter: blur(20px);
}

[data-theme="dark"] .role-expanded-panel {
  background-color: rgba(30, 30, 30, 0.95);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.role-panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
  padding: 0 0.25rem;
}

.role-panel-header span {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-secondary);
}

.role-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 0.5rem;
  max-height: 300px;
  overflow-y: auto;
  padding: 2px;
}

.role-card {
  padding: 0.75rem;
  border-radius: 10px;
  border: 1px solid rgba(0,0,0,0.04);
  background: rgba(0,0,0,0.02);
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

[data-theme="dark"] .role-card {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.04);
}

.role-card:hover {
  border-color: #000;
  background-color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}

[data-theme="dark"] .role-card:hover {
  border-color: #fff;
  background-color: #222;
}

.role-card.active {
  border-color: #000;
  background-color: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

[data-theme="dark"] .role-card.active {
  border-color: #fff;
  background-color: #222;
}

/* Skeleton Loader Styles */
.image-skeleton-loader {
  width: 100%;
  max-width: 300px;
  background: var(--bg-secondary);
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid var(--border-color);
  margin-top: 0.5rem;
}

.skeleton-image {
  width: 100%;
  height: 200px;
  background: #f0f0f0;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

[data-theme="dark"] .skeleton-image {
  background: #2a2a2a;
}

.skeleton-icon {
  color: #ccc;
  opacity: 0.5;
}

[data-theme="dark"] .skeleton-icon {
  color: #444;
}

.shimmer {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.4) 50%,
    transparent 100%
  );
  animation: shimmer 1.5s infinite;
  transform: skewX(-20deg);
}

[data-theme="dark"] .shimmer {
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.05) 50%,
    transparent 100%
  );
}

@keyframes shimmer {
  0% { transform: translateX(-150%) skewX(-20deg); }
  100% { transform: translateX(150%) skewX(-20deg); }
}

.skeleton-text {
  padding: 12px;
  font-size: 0.9rem;
  color: var(--text-secondary);
  text-align: center;
  border-top: 1px solid var(--border-color);
  background: var(--bg-primary);
}

.role-card-icon {
  font-size: 1.25rem;
}

.role-card-info {
  flex: 1;
  min-width: 0;
}

.role-card-name {
  font-size: 0.85rem;
  font-weight: 600;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.role-card-desc {
  font-size: 0.7rem;
  color: var(--text-secondary);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.role-card.add-new {
  justify-content: center;
  border: 1px dashed rgba(0,0,0,0.1);
  background: transparent;
}

[data-theme="dark"] .role-card.add-new {
  border-color: rgba(255,255,255,0.1);
}

.delete-role-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 16px;
  height: 16px;
  background: rgba(0,0,0,0.05);
  border: none;
  border-radius: 50%;
  font-size: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: all 0.2s;
}

.role-card:hover .delete-role-btn {
  opacity: 1;
}

.delete-role-btn:hover {
  background: #ef4444;
  color: #fff;
}

/* Add Role Form */
.add-role-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.form-row {
  display: flex;
  gap: 0.5rem;
}

.icon-input {
  width: 40px;
  text-align: center;
}

.add-role-form input,
.add-role-form textarea {
  padding: 0.6rem;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 8px;
  font-size: 0.85rem;
  background: rgba(0,0,0,0.02);
  outline: none;
}

[data-theme="dark"] .add-role-form input,
[data-theme="dark"] .add-role-form textarea {
  background: rgba(255,255,255,0.03);
  border-color: rgba(255,255,255,0.08);
  color: #fff;
}

.prompt-input {
  height: 100px;
  resize: none;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.fade-up-enter-active, .fade-up-leave-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.fade-up-enter-from, .fade-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

/* Drag Overlay */
.drag-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  border: 2px dashed var(--text-primary);
  border-radius: 16px;
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
}

[data-theme="dark"] .drag-overlay {
  background: rgba(30, 30, 30, 0.95);
  border-color: rgba(255, 255, 255, 0.2);
}

.drag-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  color: var(--text-primary);
  font-weight: 500;
  pointer-events: none;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

@keyframes slideIn {
  from { width: 0; opacity: 0; }
  to { width: 14px; opacity: 1; }
}

@keyframes typing-bounce {
  0%, 80%, 100% { transform: translateY(0); }
  40% { transform: translateY(-4px); }
}

/* Mobile Responsive */
.mobile-only { display: none; }
.close-sidebar-btn { display: none; }
.sidebar-overlay { display: none; }

@media (max-width: 768px) {
  .mobile-only { display: block; }
  .close-sidebar-btn { display: flex; }
  
  .delete-session-btn {
    opacity: 0.8;
  }
  
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    transform: translateX(-100%);
    box-shadow: 10px 0 30px rgba(0,0,0,0.1);
    width: 80%;
    max-width: 300px;
    opacity: 1;
    overflow: visible;
  }
  
  .sidebar.sidebar-open {
    transform: translateX(0);
    width: 80%;
  }
  
  .sidebar-overlay {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 90;
    backdrop-filter: blur(4px);
  }
  
  .chat-header {
    background: rgba(255, 255, 255, 0.9);
  }
  
  .messages-container {
    padding: 1rem;
    padding-bottom: 120px;
  }
  
  .input-area {
    width: 95%;
    bottom: 1rem;
  }
  
  .message-content {
    max-width: 95%;
  }

  /* Optimize toolbar on mobile */
  .tool-tab span, 
  .active-role-chip .role-name {
    display: none;
  }
  
  .tool-tab, .active-role-chip {
    padding: 6px 8px;
    flex-shrink: 0;
  }

  .left-tools {
    gap: 0.35rem;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    padding-bottom: 2px;
  }
  
  .left-tools::-webkit-scrollbar {
    display: none;
  }

  .toolbar {
    gap: 0.5rem;
  }
}

.character-manager-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 100;
  background: var(--bg-main, #ffffff);
}

/* Embedded Command Confirmation - Cool Black Style */
.command-embed-container {
  margin-top: 14px;
  background: #111111;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  overflow: hidden;
  font-size: 0.9rem;
  max-width: 100%;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3), 0 2px 8px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.command-embed-container:hover {
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-1px);
}

.command-embed-header {
  padding: 10px 14px;
  background: rgba(255, 255, 255, 0.03);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 600;
  color: #e5e7eb;
  letter-spacing: 0.5px;
}

.command-icon {
  color: #fbbf24;
  filter: drop-shadow(0 0 4px rgba(251, 191, 36, 0.4));
  font-size: 1.1rem;
}

.command-embed-content {
  padding: 12px 14px;
  background: #000000;
}

.command-embed-content pre {
  margin: 0;
  white-space: pre-wrap;
  word-break: break-all;
  font-family: 'Fira Code', 'JetBrains Mono', monospace;
  font-size: 0.85rem;
  color: #10b981; /* Matrix green or #93c5fd for blue */
  line-height: 1.5;
}

.command-embed-actions {
  padding: 10px 14px;
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  background: rgba(255, 255, 255, 0.02);
}

.embed-btn {
  padding: 7px 16px;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  border: 1px solid transparent;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  outline: none;
}

.embed-btn.cancel {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  color: #9ca3af;
}

.embed-btn.cancel:hover {
  background: rgba(255, 255, 255, 0.1);
  color: #f3f4f6;
  border-color: rgba(255, 255, 255, 0.2);
}

.embed-btn.confirm {
  background: #ffffff;
  color: #000000;
  border-color: #ffffff;
  box-shadow: 0 2px 8px rgba(255, 255, 255, 0.1);
}

.embed-btn.confirm:hover {
  background: #f3f4f6;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(255, 255, 255, 0.15);
}

.embed-btn.confirm:active {
  transform: translateY(0);
}

/* No Models Overlay */
.no-models-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.4);
  backdrop-filter: blur(15px);
  -webkit-backdrop-filter: blur(15px);
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

[data-theme="dark"] .no-models-overlay {
  background: rgba(0, 0, 0, 0.4);
}

.no-models-bg-glow {
  position: absolute;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 50% 50%, rgba(0, 122, 255, 0.15), transparent 70%);
  animation: bgPulse 8s infinite alternate;
}

@keyframes bgPulse {
  0% { transform: scale(1); opacity: 0.5; }
  100% { transform: scale(1.2); opacity: 0.8; }
}

.no-models-modal {
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  padding: 3rem 2rem;
  border-radius: 24px;
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
  text-align: center;
  max-width: 420px;
  width: 90%;
  border: 1px solid rgba(255, 255, 255, 0.3);
  position: relative;
  z-index: 1;
}

[data-theme="dark"] .no-models-modal {
  background: rgba(30, 30, 30, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.1);
  box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

.no-models-icon-wrapper {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.no-models-icon-pulse {
  position: absolute;
  width: 100%;
  height: 100%;
  background: rgba(0, 122, 255, 0.2);
  border-radius: 50%;
  animation: iconPulse 2s infinite;
}

@keyframes iconPulse {
  0% { transform: scale(1); opacity: 0.8; }
  100% { transform: scale(1.8); opacity: 0; }
}

.no-models-icon {
  position: relative;
  z-index: 2;
  color: #007aff;
  filter: drop-shadow(0 0 10px rgba(0, 122, 255, 0.3));
}

.gradient-text {
  background: linear-gradient(135deg, #007aff, #5856d6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.no-models-modal p {
  color: var(--text-secondary);
  margin-bottom: 2rem;
  font-size: 0.95rem;
  line-height: 1.6;
}

.cool-btn {
  background: linear-gradient(135deg, #007aff, #5856d6);
  color: white;
  border: none;
  padding: 0.8rem 2rem;
  border-radius: 14px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  box-shadow: 0 10px 20px rgba(0, 122, 255, 0.2);
}

.cool-btn:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 15px 30px rgba(0, 122, 255, 0.4);
}

.cool-btn:active {
  transform: translateY(-1px);
}
</style>