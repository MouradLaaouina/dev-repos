import { api } from './api';

export const clientService = {
  /**
   * Récupérer tous les clients B2B
   * @param {Object} params - Filtres optionnels
   * @returns {Promise<Array>}
   */
  getClients: async (params = {}) => {
    try {
      const response = await api.get('/thirdparties', {
        params: {
          page: params.page || 0,
           client_type: 1,
          ...params,
        },
      });
      
      let clients = response.data.result || response.data;
      
      if (Array.isArray(clients)) {
        clients = clients.filter(c => c.client && parseInt(c.client) >= 1);
      }
      
      return clients;
    } catch (error) {
      console.error('Erreur lors de la récupération des clients:', error);
      throw error;
    }
  },

  /**
   * Récupérer un client par ID
   * @param {number} clientId - ID du client
   * @returns {Promise<Object>}
   */
  getClientById: async (clientId) => {
    try {
      const response = await api.get(`/thirdparties/${clientId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération du client:', error);
      throw error;
    }
  },

  /**
   * Récupérer les remises d'un client
   * @param {number} clientId - ID du client
   * @returns {Promise<Array>}
   */
  getClientDiscounts: async (clientId) => {
    try {
      const response = await api.get(`/thirdparties/${clientId}/fixedamountdiscounts`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des remises:', error);
      // Retourner un tableau vide en cas d'erreur
      return [];
    }
  },

  /**
   * Rechercher des clients
   * @param {string} searchTerm - Terme de recherche
   * @returns {Promise<Array>}
   */
  searchClients: async (searchTerm, allClients = []) => {
    // Filtrer côté client car le backend ne supporte peut-être pas les filtres avancés
    if (!searchTerm) return allClients;
    
    const term = searchTerm.toLowerCase();
    return allClients.filter(client => 
      client.name?.toLowerCase().includes(term) ||
      client.code_client?.toLowerCase().includes(term) ||
      client.nom?.toLowerCase().includes(term) ||
      client.email?.toLowerCase().includes(term) ||
      client.town?.toLowerCase().includes(term)
    );
  },
};