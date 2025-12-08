import { useState, useEffect, useCallback } from 'react';
import { authService } from '../services';

export const useAuth = () => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Vérifier si un utilisateur est déjà connecté au chargement
  useEffect(() => {
    const checkAuth = () => {
      try {
        if (authService.isAuthenticated()) {
          const currentUser = authService.getCurrentUser();
          setUser(currentUser);
        }
      } catch (err) {
        console.error('Erreur lors de la vérification de l\'authentification:', err);
        setError(err.message);
      } finally {
        setLoading(false);
      }
    };

    checkAuth();
  }, []);

  // Fonction de connexion
  const login = useCallback(async (loginData) => {
    setLoading(true);
    setError(null);

    try {
      const { login, password } = loginData;
      
      const response = await authService.login(login, password);
      
      const userData = response.user;

      // Mettre à jour le localStorage
      localStorage.setItem('user', JSON.stringify(userData));
      
      setUser(userData);
      return { success: true, user: userData };
    } catch (err) {
      const errorMessage = err.message || 'Échec de la connexion';
      setError(errorMessage);
      return { success: false, error: errorMessage };
    } finally {
      setLoading(false);
    }
  }, []);

  // Fonction de déconnexion
  const logout = useCallback(() => {
    authService.logout();
    setUser(null);
    setError(null);
  }, []);

  // Fonction pour mettre à jour les données utilisateur
  const updateUser = useCallback((userData) => {
    const updatedUser = { ...user, ...userData };
    setUser(updatedUser);
    localStorage.setItem('user', JSON.stringify(updatedUser));
  }, [user]);

  return {
    user,
    loading,
    error,
    login,
    logout,
    updateUser,
    isAuthenticated: !!user,
  };
};