import { api } from './api';

export const proposalService = {
  getProposals: async (params = {}) => {
    try {
      const response = await api.get('/proposals', { params });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des devis:', error);
      throw error;
    }
  },

  getProposalById: async (proposalId) => {
    try {
      const response = await api.get(`/proposals/${proposalId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération du devis:', error);
      throw error;
    }
  },

  getSignedProposals: async (thirdpartyId) => {
    try {
      const sqlfilters = "(t.fk_statut:=:2)";
      const params = {
        page: 0,
        sqlfilters
      };
      if (thirdpartyId) {
        params.thirdparty_ids = thirdpartyId;
      }
      const response = await api.get('/proposals', { params });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des devis signés:', error);
      throw error;
    }
  },
};
