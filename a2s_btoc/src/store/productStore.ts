import { create } from 'zustand';
import { Product, Brand } from '../types';
import { supabase } from '../lib/supabase';

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
      const { data, error } = await supabase
        .from('products')
        .select('*')
        .order('brand', { ascending: true });

      if (error) throw error;

      const products: Product[] = data.map(product => ({
        id: product.id,
        code: product.code,
        name: product.name,
        brand: product.brand,
        priceHT: product.price
      }));

      set({ products, loading: false });
    } catch (error) {
      console.error('Error fetching products:', error);
      set({ loading: false });
    }
  },
  
  getProductsByBrand: (brand) => {
    // Sort products by price (cheaper to more expensive)
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