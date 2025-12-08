import { api } from './api';

export const selloutService = {
  /**
   * Créer une vente sellout (passe par API Express)
   * @param {Object} saleData
   * @returns {Promise<Object>}
   */
  createSale: async (saleData) => {
    try {
      // L'API Dolibarr attend l'endpoint /sellout/sales
      const response = await api.post('/sellout/sales', saleData);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la création du sellout:', error);
      throw error;
    }
  },
};
