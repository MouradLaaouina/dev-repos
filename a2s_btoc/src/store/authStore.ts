import { create } from 'zustand';
import { User } from '../types';
import { authService } from '../services/authService';
import toast from 'react-hot-toast';

interface AuthState {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  login: (email: string, password: string) => Promise<boolean>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
}

export const useAuthStore = create<AuthState>()((set, get) => ({
  user: null,
  isAuthenticated: false,
  loading: true,

  checkAuth: async () => {
    try {
      const token = authService.getToken();
      if (token) {
        const userInfo = await authService.getCurrentUser();
        set({ 
          user: userInfo,
          isAuthenticated: true,
          loading: false
        });
      } else {
        set({ 
          user: null,
          isAuthenticated: false,
          loading: false
        });
      }
    } catch (error) {
      console.error('Auth check error:', error);
      set({ 
        user: null,
        isAuthenticated: false,
        loading: false
      });
    }
  },

  login: async (email, password) => {
    try {
      const { token, user } = await authService.login(email, password);
      set({ 
        user,
        isAuthenticated: true 
      });
      toast.success('Connexion réussie');
      return true;
    } catch (error) {
      console.error('Login error:', error);
      toast.error('Email ou mot de passe incorrect');
      return false;
    }
  },

  logout: async () => {
    try {
      authService.logout();
      set({ user: null, isAuthenticated: false });
      toast.success('Déconnexion réussie');
    } catch (error) {
      console.error('Logout error:', error);
      toast.error('Erreur lors de la déconnexion');
    }
  },
}));