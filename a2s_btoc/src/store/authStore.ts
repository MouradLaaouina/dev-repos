import { create } from 'zustand';
import { User } from '../types';
import { supabase } from '../lib/supabase';
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

export const useAuthStore = create<AuthState>()((set, get) => ({
  user: null,
  isAuthenticated: false,
  loading: true,

  checkAuth: async () => {
    try {
      const { data: { session } } = await supabase.auth.getSession();
      
      if (session?.user) {
        const { data: userData, error } = await supabase
          .from('users')
          .select('*')
          .eq('id', session.user.id)
          .single();

        if (error) throw error;

        // Determine team name based on code_agence
        let team = '';
        if (userData.code_agence === '000001') team = 'Réseaux sociaux';
        else if (userData.code_agence === '000002') team = 'Centre d\'appel';
        else if (userData.code_agence === '000003') team = 'WhatsApp';

        set({ 
          user: {
            ...userData,
            codeAgence: userData.code_agence, // Map database field to type
            team, // Add team name
            createdAt: new Date(userData.created_at)
          },
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
      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });

      if (error) throw error;

      if (data.user) {
        const { data: userData, error: userError } = await supabase
          .from('users')
          .select('*')
          .eq('id', data.user.id)
          .single();

        if (userError) throw userError;

        // Determine team name based on code_agence
        let team = '';
        if (userData.code_agence === '000001') team = 'Réseaux sociaux';
        else if (userData.code_agence === '000002') team = 'Centre d\'appel';
        else if (userData.code_agence === '000003') team = 'WhatsApp';

        set({ 
          user: {
            ...userData,
            codeAgence: userData.code_agence, // Map database field to type
            team, // Add team name
            createdAt: new Date(userData.created_at)
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
    try {
      await supabase.auth.signOut();
      set({ user: null, isAuthenticated: false });
      toast.success('Déconnexion réussie');
    } catch (error) {
      console.error('Logout error:', error);
      toast.error('Erreur lors de la déconnexion');
    }
  },

  register: async (name, email, password) => {
    try {
      const { data: { user }, error } = await supabase.auth.signUp({
        email,
        password,
        options: {
          data: {
            name,
            role: 'agent'
          }
        }
      });

      if (error) throw error;

      if (user) {
        // Create user profile in users table
        const { error: profileError } = await supabase
          .from('users')
          .insert([
            {
              id: user.id,
              name,
              email,
              role: 'agent',
              created_at: new Date().toISOString()
            }
          ]);

        if (profileError) throw profileError;
        
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
supabase.auth.onAuthStateChange((event, session) => {
  const { checkAuth } = useAuthStore.getState();
  checkAuth();
});