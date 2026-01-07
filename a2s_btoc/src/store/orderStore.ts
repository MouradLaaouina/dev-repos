import { create } from 'zustand';
import { orderService } from '../services/orderService';
import { contactService } from '../services/contactService';
import { Order, OrderItem } from '../types';
import toast from 'react-hot-toast';

interface OrderState {
  orders: Order[];
  loading: boolean;
  error: string | null;
  fetchOrders: () => Promise<void>;
  createOrder: (orderData: Partial<Order>) => Promise<Order | null>;
  createOrderForNewContact: (orderData: Partial<Order>, existingContactId?: string) => Promise<Order | null>;
  updateOrder: (id: string, updates: Partial<Order>) => Promise<void>;
  deleteOrder: (id: string) => Promise<void>;
  exportOrders: (orderIds: string[]) => Promise<void>;
  confirmOrder: (id: string, orderExternalId: string, note: string, userId: string) => Promise<void>;
  updateOrderStatus: (id: string, orderExternalId: string, status: string, note?: string, userId?: string) => Promise<void>;
  cancelOrder: (id: string, orderExternalId: string, note: string) => Promise<void>;
  getFilteredOrdersByTeam: (userRole: string, codeAgence?: string) => Order[];
  clearError: () => void;
}

export const useOrderStore = create<OrderState>()((set, get) => ({
  orders: [],
  loading: false,
  error: null,

  fetchOrders: async () => {
    set({ loading: true, error: null });
    try {
      const allOrders = await orderService.fetchOrders(0, 1000);
      set({ orders: allOrders, loading: false });
    } catch (error) {
      console.error('❌ Error fetching orders:', error);
      set({ error: 'Failed to fetch orders', loading: false });
    }
  },

  createOrderForNewContact: async (orderData, existingContactId) => {
    try {
      let contactId = existingContactId;
      
      if (!contactId) {
        const contact = await contactService.createContact({
          nom: orderData.nom,
          telephone: orderData.telephone,
          telephone2: orderData.telephone2,
          address: orderData.address,
          plateforme: orderData.plateforme as any,
          ville: orderData.ville as any,
          agentId: orderData.agentId,
          codeAgence: orderData.codeAgence,
          status: 'À confirmer'
        } as any);
        contactId = contact.id;
      } else {
        await contactService.updateContact(contactId, {
          status: 'À confirmer',
          ville: orderData.ville as any,
          address: orderData.address
        } as any);
      }

      const order = await orderService.createOrder({
        ...orderData,
        contact_id: contactId
      });

      const { orders } = get();
      set({ orders: [order, ...orders] });
      toast.success('Commande créée avec succès');
      return order;
    } catch (error) {
      console.error('❌ Error creating order:', error);
      toast.error('Erreur lors de la création de la commande');
      return null;
    }
  },

  createOrder: async (orderData) => {
    try {
      const order = await orderService.createOrder(orderData);
      const { orders } = get();
      set({ orders: [order, ...orders] });
      toast.success('Commande créée');
      return order;
    } catch (error) {
      console.error('❌ Error creating order:', error);
      toast.error('Erreur lors de la création');
      return null;
    }
  },

  updateOrder: async (id, updates) => {
    try {
      await orderService.updateOrder(id, updates);
      const updatedOrder = await orderService.getOrderById(id);
      const { orders } = get();
      set({ orders: orders.map(o => o.id === id ? updatedOrder : o) });
      toast.success('Commande mise à jour');
    } catch (error) {
      console.error('❌ Error updating order:', error);
      toast.error('Erreur lors de la mise à jour');
    }
  },

  deleteOrder: async (id) => {
    try {
      await orderService.deleteOrder(id);
      const { orders } = get();
      set({ orders: orders.filter(o => o.id !== id) });
      toast.success('Commande supprimée');
    } catch (error) {
      console.error('❌ Error deleting order:', error);
      toast.error('Erreur lors de la suppression');
    }
  },

  exportOrders: async (orderIds) => {
    toast.success(`${orderIds.length} commandes exportées`);
  },

  confirmOrder: async (id, _, note, userId) => {
    try {
      await orderService.updateOrder(id, { status: 'Prête à être livrée', confirmationNote: note, confirmedBy: userId } as any);
      const updatedOrder = await orderService.getOrderById(id);
      const { orders } = get();
      set({ orders: orders.map(o => o.id === id ? updatedOrder : o) });
      toast.success('Commande confirmée');
    } catch (error) {
      console.error('❌ Error confirming order:', error);
    }
  },

  updateOrderStatus: async (id, _, status, note, userId) => {
    try {
      await orderService.updateOrder(id, { status, confirmationNote: note, confirmedBy: userId } as any);
      const updatedOrder = await orderService.getOrderById(id);
      const { orders } = get();
      set({ orders: orders.map(o => o.id === id ? updatedOrder : o) });
    } catch (error) {
      console.error('❌ Error updating order status:', error);
    }
  },

  cancelOrder: async (id, _, note) => {
    try {
      await orderService.updateOrder(id, { status: 'Annulée', cancellationNote: note } as any);
      const updatedOrder = await orderService.getOrderById(id);
      const { orders } = get();
      set({ orders: orders.map(o => o.id === id ? updatedOrder : o) });
      toast.success('Commande annulée');
    } catch (error) {
      console.error('❌ Error cancelling order:', error);
    }
  },

  getFilteredOrdersByTeam: (userRole, codeAgence) => {
    const { orders } = get();
    if (userRole === 'admin') return orders;
    if (codeAgence) return orders.filter(o => o.codeAgence === codeAgence);
    return orders;
  },

  clearError: () => set({ error: null })
}));
