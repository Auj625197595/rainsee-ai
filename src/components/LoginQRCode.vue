<template>
  <div class="login-prompt">
     登录逻辑暂不提供
  </div>
</template>

<script>
import QRCode from 'qrcodejs2';
import { uuid } from 'vue-uuid';
import { mutations } from '../store';

export default {
  name: 'LoginQRCode',
  data() {
    return {
      showLoginQR: true,
      loginPassword: '',
      loginInterval: null
    };
  },
  mounted() {
    
  },
  beforeDestroy() {
  },
  methods: {
 
  }
};
</script>

<style scoped>
.login-prompt {
  text-align: center;
  padding: 1rem 0;
}

.qr-login-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.qr-code-wrapper {
  background: white;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  position: relative;
  margin: 0 auto;
}

.qr-loading {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.8);
  font-size: 0.8rem;
  color: #666;
}

.qr-tip {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.btn-primary {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  background: #000;
  color: #fff;
}

[data-theme="dark"] .btn-primary {
  background: #fff;
  color: #000;
}

.btn-text {
  background: none;
  border: none;
  color: var(--primary-color);
  font-weight: 500;
  cursor: pointer;
  font-size: 0.9rem;
}
</style>
