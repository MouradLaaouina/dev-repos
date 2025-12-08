import { api } from './api';

export const discountService = {
  /**
   * Get discount for a single product and client
   * @param {number} productId - Product ID
   * @param {number} clientId - Client/Thirdparty ID
   * @returns {Promise<Object>} Discount information
   */
  getDiscount: async (productId, clientId) => {
    try {
      const response = await api.get(`/discounts/${productId}/${clientId}`);
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération de la réduction:', error);
      throw error;
    }
  },

  /**
   * Get discounts for multiple products (batch)
   * @param {number[]} productIds - Array of product IDs
   * @param {number} clientId - Client/Thirdparty ID
   * @returns {Promise<Object>} Object with discounts per product
   */
  getDiscountsBatch: async (productIds, clientId) => {
    try {
      const response = await api.post('/discounts/batch', {
        product_ids: productIds,
        client_id: clientId
      });
      return response.data.result || response.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des réductions:', error);
      throw error;
    }
  },

  /**
   * Calculate discounted price
   * @param {number} originalPrice - Original price
   * @param {number} discountPercent - Discount percentage
   * @returns {number} Discounted price
   */
  calculateDiscountedPrice: (originalPrice, discountPercent) => {
    if (!discountPercent || discountPercent <= 0) {
      return originalPrice;
    }
    return originalPrice * (1 - discountPercent / 100);
  },

  /**
   * Format discount for display
   * @param {number} discountPercent - Discount percentage
   * @returns {string} Formatted discount string
   */
  formatDiscount: (discountPercent) => {
    if (!discountPercent || discountPercent <= 0) {
      return '';
    }
    return `-${discountPercent}%`;
  }
};
