import { create } from 'zustand';
import { Product, Brand } from '../types';
import { apiClient } from '../services/apiClient';

interface ProductState {
  products: Product[];
  loading: boolean;
  fetchProducts: () => Promise<void>;
  getProductsByBrand: (brand: Brand) => Product[];
  getProductByCode: (code: string) => Product | undefined;
  getTTCPrice: (htPrice: number) => number;
}

export const useProductStore = create<ProductState>()((set, get) => ({
  products: [],
  loading: false,
  
  fetchProducts: async () => {
    set({ loading: true });
    try {
      const response = await apiClient.get<any>('/products?page=0&limit=1000');
      const data = response.result || [];

      const products: Product[] = data.map((product: any) => ({
        id: product.id,
        code: product.ref,
        name: product.label,
        brand: product.array_options?.options_brand || 'D-WHITE',
        priceHT: product.price
      }));

      set({ products, loading: false });
    } catch (error) {
      console.error('Error fetching products:', error);
      set({ loading: false });
    }
  },
  
  getProductsByBrand: (brand) => {
    return get().products
      .filter(product => product.brand === brand)
      .sort((a, b) => a.priceHT - b.priceHT);
  },
  
  getProductByCode: (code) => {
    return get().products.find(product => product.code === code);
  },

  getTTCPrice: (htPrice) => {
    return htPrice * 1.20; // 20% TVA
  },
}));
