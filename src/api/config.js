/**
 * API Configuration
 */

export const SERVERS = ['icon2.yjllq.com', 'icon144.yjllq.com'];
export const STORAGE_KEY_SERVER_PREFIX = 'aihelp_server_pref_';

export const API_BASE_DOMAIN = 'icon144.yjllq.com';
export const API_URL = `https://${API_BASE_DOMAIN}/AiAssistantv2.php`;

// Helper to generate key for a specific endpoint
export function getServerKey(endpoint) {
    if (!endpoint) return STORAGE_KEY_SERVER_PREFIX + 'default';
    // Create a safe key from the endpoint URL
    // We'll replace special chars with underscores to keep it clean
    const safeKey = endpoint.replace(/[^a-zA-Z0-9]/g, '_');
    return STORAGE_KEY_SERVER_PREFIX + safeKey;
}

// Dynamic Getters for Chat Interface Only
export function getChatApiUrl(targetEndpoint, forceOptimized = false) {
    const key = getServerKey(targetEndpoint);
    const stored = localStorage.getItem(key);
    
    // If forceOptimized is true, we MUST use a server from the SERVERS list
    if (forceOptimized) {
        // Fallback to the first available server in the list
        return `https://${SERVERS[1]}/AiAssistantv2.php`;
    }

    if (stored && SERVERS.includes(stored)) {
        return `https://${stored}/AiAssistantv2.php`;
    }

    // If no preference found, trigger checkServerSpeed in App.vue via event
    // This is a "lazy" check triggered when a chat request is about to happen but no optimization exists
    if (typeof window !== 'undefined') {
        window.dispatchEvent(new CustomEvent('aihelp-check-server-speed'));
    }

    return `https://${API_BASE_DOMAIN}/AiAssistantv2.php`;
}

export const CHARACTER_STORE_URL = `https://${API_BASE_DOMAIN}/CharacterStore.php`;
export const MARKITDOWN_API_URL = `https://${API_BASE_DOMAIN}/AiAssistantv2.php?action=markitdown&key=sfjakjfhksdha`;
export const WEBSCRAPE_API_URL = `https://${API_BASE_DOMAIN}/AiAssistantv2.php?action=webscrape&key=sfjakjfhksdha`;
export const BACKUP_API_URL = `https://${API_BASE_DOMAIN}/UserCenter.php`;
