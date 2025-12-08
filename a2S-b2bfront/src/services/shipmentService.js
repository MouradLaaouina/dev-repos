import { api } from './api';

export const shipmentService = {
  /**
   * Récupérer la liste des livraisons
   * @param {Object} params - Paramètres de recherche
   * @returns {Promise<Array>}
   */
  getShipments: async (params = {}) => {
    try {
      const response = await api.get('/shipments', {
        params: {
          page: params.page || 0,
          status: params.status,
          thirdparty_ids: params.thirdparty_ids,
        },
      });
      return response.data.result || response.data || [];
    } catch (error) {
      console.error('Erreur lors de la récupération des livraisons:', error);
      throw error;
    }
  },

  /**
   * Récupérer une livraison par ID
   * @param {number} shipmentId - ID de la livraison
   * @returns {Promise<Object>}
   */
  getShipmentById: async (shipmentId) => {
    try {
      const response = await api.get(`/shipments/${shipmentId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération de la livraison:', error);
      throw error;
    }
  },

  /**
   * Valider une livraison (signature)
   * @param {number} shipmentId - ID de la livraison
   * @returns {Promise<Object>}
   */
  validateShipment: async (shipmentId) => {
    try {
      const response = await api.post(`/shipments/${shipmentId}/validate`, { notrigger: 0 });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la validation de la livraison:', error);
      throw error;
    }
  },

  /**
   * Clôturer une livraison (marquer comme livrée)
   * @param {number} shipmentId - ID de la livraison
   * @returns {Promise<Object>}
   */
  closeShipment: async (shipmentId) => {
    try {
      const response = await api.post(`/shipments/${shipmentId}/close`, { notrigger: 0 });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la clôture de la livraison:', error);
      throw error;
    }
  },

  /**
   * Helper pour obtenir le label du statut
   * @param {number} status - Code statut
   * @returns {string}
   */
  getStatusLabel: (status) => {
    const statusNum = Number(status);
    switch (statusNum) {
      case 0: return 'Brouillon';
      case 1: return 'Validé';
      case 2: return 'Livré';
      default: return 'Inconnu';
    }
  },

  /**
   * Helper pour obtenir la couleur du statut
   * @param {number} status - Code statut
   * @returns {Object} { bg, text }
   */
  getStatusColor: (status) => {
    const statusNum = Number(status);
    switch (statusNum) {
      case 0: return { bg: 'bg-gray-100', text: 'text-gray-700' };
      case 1: return { bg: 'bg-blue-100', text: 'text-blue-700' };
      case 2: return { bg: 'bg-green-100', text: 'text-green-700' };
      default: return { bg: 'bg-zinc-100', text: 'text-zinc-700' };
    }
  }
};
