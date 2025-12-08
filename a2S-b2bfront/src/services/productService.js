import { api } from './api';

export const productService = {
  /**
   * Récupérer tous les produits
   */
  getProducts: async (params = {}) => {
    try {
      const response = await api.get('/products', {
        params: {
          page: params.page || 0,
          ...params,
        },
      });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des produits:', error);
      throw error;
    }
  },

  /**
   * Récupérer un produit par ID
   */
  getProductById: async (productId) => {
    try {
      const response = await api.get(`/products/${productId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération du produit:', error);
      throw error;
    }
  },

  /**
   * Récupérer le stock d'un produit
   */
  getProductStock: async (productId) => {
    try {
      const response = await api.get(`/products/${productId}/stock`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération du stock:', error);
      throw error;
    }
  },

  /**
   * Récupérer un produit par code-barres
   */
  getProductByBarcode: async (barcode) => {
    try {
      const response = await api.get(`/products/barcode/${barcode}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la recherche par code-barres:', error);
      throw error;
    }
  },

  /**
   * Rechercher des produits
   */
  searchProducts: async (searchTerm) => {
    try {
      const response = await api.get('/products', {
        params: { page: 0 },
      });

      const products = response.data.result || response.data;

      if (searchTerm && Array.isArray(products)) {
        return products.filter(p =>
          p.label?.toLowerCase().includes(searchTerm.toLowerCase()) ||
          p.ref?.toLowerCase().includes(searchTerm.toLowerCase())
        );
      }

      return products;
    } catch (error) {
      console.error('Erreur lors de la recherche de produits:', error);
      throw error;
    }
  },

  /**
   * Récupérer toutes les catégories de produits
   */
  getCategories: async () => {
    try {
      const response = await api.get('/products/categories/list', {
        params: { type: 0 },  // 0 = catégories de produits
      });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des catégories:', error);
      throw error;
    }
  },
};