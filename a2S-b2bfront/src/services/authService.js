import { api } from './api';

export const authService = {
   /**
   * Connexion utilisateur
   */
  login: async (login, password) => {
    try {
      const response = await api.post('/auth/login', {
        login,
        password,
      });

      const { succes, token, user } = response.data;

      if (succes === 'ok' && token) {
        localStorage.setItem('auth_token', token);
        
        if (user) {
          localStorage.setItem('user', JSON.stringify(user));
        } else {
          const basicUser = { login, token };
          localStorage.setItem('user', JSON.stringify(basicUser));
        }
        
        return {
          success: true,
          token,
          user: user || { login, token },
        };
      }

      throw new Error('Échec de la connexion');
    } catch (error) {
      console.error('Erreur lors de la connexion:', error);
      
      if (error.response) {
        const errorMessage = error.response.data?.error || 'Identifiants incorrects';
        throw new Error(errorMessage);
      } else if (error.request) {
        throw new Error('Impossible de contacter le serveur');
      } else {
        throw error;
      }
    }
  },

  /**
   * Déconnexion
   */
  logout: () => {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user');
  },

  /**
   * Vérifier si l'utilisateur est connecté
   */
  isAuthenticated: () => {
    const token = localStorage.getItem('auth_token');
    return !!token;
  },

  /**
   * Récupérer l'utilisateur courant
   */
  getCurrentUser: () => {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  },

  /**
   * Récupérer le token
   */
  getToken: () => {
    return localStorage.getItem('auth_token');
  },
};