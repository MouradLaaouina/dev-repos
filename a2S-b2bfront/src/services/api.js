import axios from 'axios';

// API Express (proxy backend) – paramétrable via VITE_API_URL
// Exemple par défaut: http://localhost:3000/api
const API_BASE_URL =
  import.meta.env.VITE_API_URL ||
  'https://ns327060.ip-5-135-138.eu/api_express/api';

export const api = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000,
});

// Intercepteur pour ajouter le token dans le header DOLAPIKEY
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
      // Votre backend attend DOLAPIKEY comme header
      config.headers['DOLAPIKEY'] = token;
    }
    
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response) {
      switch (error.response.status) {
        case 401:
          localStorage.removeItem('auth_token');
          localStorage.removeItem('user');
          window.location.href = '/';
          break;
        case 403:
          console.error('Accès refusé');
          break;
        case 404:
          console.error('Ressource non trouvée');
          break;
        case 500:
          console.error('Erreur serveur');
          break;
        default:
          console.error('Erreur API:', error.response.data);
      }
    } else if (error.request) {
      console.error('Pas de réponse du serveur');
    } else {
      console.error('Erreur:', error.message);
    }
    
    return Promise.reject(error);
  }
);
