const VITE_API_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000/api';

const getAuthToken = () => {
    return localStorage.getItem('authToken');
};

const setAuthToken = (token: string) => {
    localStorage.setItem('authToken', token);
};

const removeAuthToken = () => {
    localStorage.removeItem('authToken');
};

const apiFetch = async (endpoint: string, options: RequestInit = {}) => {
    const token = getAuthToken();
    const headers = {
        'Content-Type': 'application/json',
        ...options.headers,
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const response = await fetch(`${VITE_API_URL}${endpoint}`, {
        ...options,
        headers,
    });

    if (!response.ok) {
        throw new Error(`API error: ${response.statusText}`);
    }

    return response.json();
};

export { apiFetch, setAuthToken, removeAuthToken, getAuthToken };
