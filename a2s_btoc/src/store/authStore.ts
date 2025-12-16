import { create } from 'zustand';
import { User } from '../types';
import { apiFetch, setAuthToken, removeAuthToken, getAuthToken } from '../lib/api';
import toast from 'react-hot-toast';

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  register: (name: string, email: string, password: string) => Promise<boolean>;
  checkAuth: () => Promise<void>;
}

const getTeamName = (codeAgence: string) => {
  switch (codeAgence) {
    case '000001':
      return 'Réseaux sociaux';
    case '000002':
      return 'Centre d\'appel';
    case '000003':
      return 'WhatsApp';
    default:
      return '';
  }
};

export const useAuthStore = create<AuthState>()((set, get) => ({
  user: null,
  isAuthenticated: false,
  loading: true,

  checkAuth: async () => {
    const token = getAuthToken();
    if (token) {
      try {
        const userInfo = await apiFetch('/auth/me');
        const team = getTeamName(userInfo.result.code_agence);
        set({
          user: {
            ...userInfo.result,
            codeAgence: userInfo.result.code_agence,
            team,
            createdAt: new Date(userInfo.result.created_at)
          },
          isAuthenticated: true,
          loading: false
        });
      } catch (error) {
        removeAuthToken();
        set({
          user: null,
          isAuthenticated: false,
          loading: false
        });
      }
    } else {
      set({
        user: null,
        isAuthenticated: false,
        loading: false
      });
    }
  },

  login: async (email, password) => {
    try {
      const { token, user } = await apiFetch('/auth/login', {
        method: 'POST',
        body: JSON.stringify({ login: email, password }),
      });

      if (token) {
        setAuthToken(token);
        const team = getTeamName(user.code_agence);
        set({
          user: {
            ...user,
            codeAgence: user.code_agence,
            team,
            createdAt: new Date(user.created_at)
          },
          isAuthenticated: true
        });
        toast.success('Connexion réussie');
        return true;
      }
      return false;
    } catch (error) {
      console.error('Login error:', error);
      toast.error('Email ou mot de passe incorrect');
      return false;
    }
  },

  logout: async () => {
    removeAuthToken();
    set({ user: null, isAuthenticated: false });
    toast.success('Déconnexion réussie');
  },

  register: async (name, email, password) => {
    try {
      const [firstname, lastname] = name.split(' ');
      const { token, user } = await apiFetch('/auth/signup', {
        method: 'POST',
        body: JSON.stringify({ email, password, firstname, lastname }),
      });

      if (token) {
        setAuthToken(token);
        const team = getTeamName(user.code_agence);
        set({
          user: {
            ...user,
            codeAgence: user.code_agence,
            team,
            createdAt: new Date(user.created_at)
          },
          isAuthenticated: true
        });
        toast.success('Inscription réussie! Vous pouvez maintenant vous connecter.');
        return true;
      }
      return false;
    } catch (error) {
      console.error('Registration error:', error);
      toast.error('Erreur lors de l\'inscription');
      return false;
    }
  }
}));

// Initialize auth state on app load
useAuthStore.getState().checkAuth();
