import { api } from './api';

export const orderService = {
  /**
   * Créer un devis (proposal)
   * @param {Object} proposalData - Données du devis
   * @returns {Promise<Object>}
   */
  createProposal: async (proposalData) => {
    try {
      console.log(proposalData)
      const response = await api.post('/proposals', proposalData);
      console.log(response)
      console.log(response.data)
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la création du devis:', error);
      throw error;
    }
  },

  /**
   * Récupérer un devis par ID
   * @param {number} proposalId - ID du devis
   * @returns {Promise<Object>}
   */
  getProposalById: async (proposalId) => {
    try {
      const response = await api.get(`/proposals/${proposalId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération du devis:', error);
      throw error;
    }
  },

  /**
   * Récupérer les devis
   * @param {Object} params - Paramètres de recherche
   * @returns {Promise<Array>}
   */
  getProposals: async (params = {}) => {
    try {
      const response = await api.get('/proposals', {
        params: {
          page: params.page || 0,
          ...params,
        },
      });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des devis:', error);
      throw error;
    }
  },

  /**
   * Récupérer les commandes
   * @param {Object} params - Paramètres de recherche
   * @returns {Promise<Array>}
   */
  getOrders: async (params = {}) => {
    try {
      const response = await api.get('/orders', {
        params: {
          page: params.page || 0,
          ...params,
        },
      });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des commandes:', error);
      throw error;
    }
  },

  /**
   * Récupérer une commande par ID
   * @param {number} orderId - ID de la commande
   * @returns {Promise<Object>}
   */
  getOrderById: async (orderId) => {
    try {
      const response = await api.get(`/orders/${orderId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération de la commande:', error);
      throw error;
    }
  },

  /**
   * Récupérer les modes de paiement
   * @returns {Promise<Array>}
   */
  getPaymentMethods: async () => {
    try {
      const response = await api.get('/setup', {
        params: { page: 0 }
      });
      const methods = response.data.result || response.data;
      
      // Si la réponse est vide ou erreur, retourner des valeurs par défaut
      if (!methods || methods.length === 0) {
        return [
          { id: 1, code: 'CB', label: 'Carte Bancaire' },
          { id: 2, code: 'VIR', label: 'Virement' },
          { id: 3, code: 'CHQ', label: 'Chèque' },
          { id: 4, code: 'PRE', label: 'Prélèvement' },
        ];
      }
      
      return methods;
    } catch (error) {
      console.error('Erreur lors de la récupération des modes de paiement:', error);
      // Retourner des valeurs par défaut en cas d'erreur
      return [
        { id: 1, code: 'CB', label: 'Carte Bancaire' },
        { id: 2, code: 'VIR', label: 'Virement' },
        { id: 3, code: 'CHQ', label: 'Chèque' },
        { id: 4, code: 'PRE', label: 'Prélèvement' },
      ];
    }
  },
};