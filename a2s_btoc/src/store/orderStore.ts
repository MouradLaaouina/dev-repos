import { create } from 'zustand';
import { Order } from '../types';
import { apiFetch } from '../lib/api';
import toast from 'react-hot-toast';

interface OrderState {
  orders: Order[];
  loading: boolean;
  error: string | null;
  fetchOrders: () => Promise<void>;
  createOrder: (orderData: Partial<Order>) => Promise<Order | null>;
  updateOrder: (id: string, updates: Partial<Order>) => Promise<void>;
  deleteOrder: (id: string) => Promise<void>;
}

export const useOrderStore = create<OrderState>()((set, get) => ({
  orders: [],
  loading: false,
  error: null,

  fetchOrders: async () => {
    set({ loading: true, error: null });
    try {
      const data = await apiFetch('/orders');
      const allOrders: Order[] = data.result.map((order: any) => ({
        ...order,
        createdAt: new Date(order.date_creation),
        updatedAt: new Date(order.date_modification),
      }));

      set({
        orders: allOrders,
        loading: false,
      });

    } catch (error) {
      set({
        error: error instanceof Error ? error.message : 'Failed to fetch orders',
        loading: false
      });
    }
  },

  createOrder: async (orderData) => {
    try {
      const data = await apiFetch('/orders', {
        method: 'POST',
        body: JSON.stringify(orderData),
      });

      const newOrder: Order = {
        ...data,
        createdAt: new Date(data.date_creation),
        updatedAt: new Date(data.date_modification),
      };

      set((state) => ({
        orders: [newOrder, ...state.orders],
      }));

      return newOrder;
    } catch (error) {
      console.error('❌ Error adding order:', error);
      set({ error: error.message || 'Failed to add order' });
      return null;
    }
  },

  updateOrder: async (id, updates) => {
    try {
      await apiFetch(`/orders/${id}`, {
        method: 'PUT',
        body: JSON.stringify(updates),
      });

      set((state) => ({
        orders: state.orders.map((order) =>
          order.id === id
            ? { ...order, ...updates, updatedAt: new Date() }
            : order
        ),
      }));

      toast.success('Commande mise à jour avec succès');
    } catch (error) {
      console.error('Error updating order:', error);
      toast.error('Erreur lors de la mise à jour de la commande');
    }
  },

  deleteOrder: async (id) => {
    try {
      await apiFetch(`/orders/${id}`, {
        method: 'DELETE',
      });

      set((state) => ({
        orders: state.orders.filter((order) => order.id !== id),
      }));

      toast.success('Commande supprimée avec succès');
    } catch (error) {
      console.error('Error deleting order:', error);
      toast.error('Erreur lors de la suppression de la commande');
    }
  },
}));
