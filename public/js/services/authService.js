import { request } from './apiClient.js';

/**
 * Auth service for Google OAuth and JWT management
 */

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

/**
 * Check if user is authenticated
 */
export function isAuthenticated() {
    return !!localStorage.getItem(TOKEN_KEY);
}

/**
 * Get stored JWT token
 */
export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

/**
 * Get stored user data
 */
export function getUser() {
    const userData = localStorage.getItem(USER_KEY);
    return userData ? JSON.parse(userData) : null;
}

/**
 * Store authentication data
 */
export function setAuth(token, user) {
    localStorage.setItem(TOKEN_KEY, token);
    localStorage.setItem(USER_KEY, JSON.stringify(user));
}

/**
 * Clear authentication data (logout)
 */
export function clearAuth() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(USER_KEY);
}

/**
 * Redirect to Google OAuth login
 */
export function loginWithGoogle() {
    // Frontend initiates OAuth by redirecting to backend, which will redirect to Google
    // Google will then redirect back to our frontend callback page
    const authUrl = window.API_BASE + '/auth/google';
    window.location.href = authUrl;
}

/**
 * Handle OAuth callback from URL params
 * Returns {success: boolean, token?: string, user?: object, error?: string}
 */
export async function handleOAuthCallback() {
    const params = new URLSearchParams(window.location.search);
    const code = params.get('code');
    const error = params.get('error');

    if (error) {
        return { success: false, error: 'OAuth authentication cancelled or failed' };
    }

    if (!code) {
        return { success: false, error: 'No authorization code received' };
    }

    try {
        // Call backend callback endpoint with code
        const url = window.API_BASE + '/auth/google/callback?code=' + encodeURIComponent(code);
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        });

        const text = await response.text();
        let result;
        
        try {
            result = JSON.parse(text);
        } catch (e) {
            console.error('Failed to parse response:', text);
            return { success: false, error: 'Invalid response from server: ' + text.substring(0, 100) };
        }

        if (result && result.success && result.token) {
            setAuth(result.token, result.user);
            return { success: true, token: result.token, user: result.user };
        }

        return { success: false, error: result.message || result.error || 'Authentication failed' };
    } catch (err) {
        console.error('OAuth callback error:', err);
        return { success: false, error: err.message || 'Failed to complete authentication' };
    }
}

/**
 * Logout user
 */
export function logout() {
    clearAuth();
    window.location.reload();
}

export default {
    isAuthenticated,
    getToken,
    getUser,
    setAuth,
    clearAuth,
    loginWithGoogle,
    handleOAuthCallback,
    logout
};
