import CryptoJS from 'crypto-js';

const SECRET_KEY = 'rainshome_ai_secret_key_salt_v1'; // In a real app, this should be more secure or user-provided

export const encryptData = (data) => {
  try {
    return CryptoJS.AES.encrypt(JSON.stringify(data), SECRET_KEY).toString();
  } catch (e) {
    console.error('Encryption failed', e);
    return null;
  }
};

export const decryptData = (ciphertext) => {
  try {
    const bytes = CryptoJS.AES.decrypt(ciphertext, SECRET_KEY);
    const decryptedData = JSON.parse(bytes.toString(CryptoJS.enc.Utf8));
    return decryptedData;
  } catch (e) {
    console.error('Decryption failed', e);
    return null;
  }
};
