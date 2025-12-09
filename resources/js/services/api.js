/**
 * Axios API Service with Sanctum Authentication
 * 
 * This service provides a configured Axios instance with automatic
 * CSRF cookie handling and authentication for Laravel Sanctum.
 */

import axios from 'axios';

// Create axios instance with default configuration
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true, // Required for Sanctum cookie-based auth
});

// Request interceptor - Ensure CSRF cookie before each request
api.interceptors.request.use(
    async (config) => {
        // Get CSRF cookie before making authenticated requests
        if (config.url !== '/sanctum/csrf-cookie') {
            try {
                await axios.get('/sanctum/csrf-cookie', {
                    withCredentials: true,
                });
            } catch (error) {
                console.error('Failed to get CSRF cookie:', error);
            }
        }

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// Response interceptor - Handle errors globally
api.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        // Handle 401 Unauthorized - redirect to login
        if (error.response?.status === 401) {
            window.location.href = '/login';
        }

        // Handle 403 Forbidden
        if (error.response?.status === 403) {
            console.error('Access forbidden:', error.response.data);
        }

        // Handle 422 Validation errors
        if (error.response?.status === 422) {
            console.error('Validation errors:', error.response.data.errors);
        }

        return Promise.reject(error);
    }
);

export default api;

// Convenience methods
export const apiService = {
    /**
     * GET request
     */
    get: (url, config = {}) => api.get(url, config),

    /**
     * POST request
     */
    post: (url, data = {}, config = {}) => api.post(url, data, config),

    /**
     * PUT request
     */
    put: (url, data = {}, config = {}) => api.put(url, data, config),

    /**
     * PATCH request
     */
    patch: (url, data = {}, config = {}) => api.patch(url, data, config),

    /**
     * DELETE request
     */
    delete: (url, config = {}) => api.delete(url, config),
};

