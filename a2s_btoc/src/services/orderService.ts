import { apiClient } from './apiClient';
import { Order, OrderItem, OrderStatus, PaymentMethod } from '../types';

const mapDolibarrToOrder = (d: any): Order => ({
  id: d.id,
  orderExternalId: d.id, // Using id as externalId for compatibility
  contact_id: d.socid,
  shipping_cost: d.total_shipping || 0,
  total: d.total_ttc || 0,
  totalHT: d.total_ht || 0,
  created_at: d.date_creation ? new Date(d.date_creation * 1000).toISOString() : new Date().toISOString(),
  createdAt: d.date_creation ? new Date(d.date_creation * 1000) : new Date(),
  updated_at: d.date_modification ? new Date(d.date_modification * 1000).toISOString() : new Date().toISOString(),
  order_number: parseInt(d.ref) || 0,
  orderNumber: d.ref || '',
  status: (d.statut_libelle || d.statut) as OrderStatus,
  paymentMethod: d.mode_reglement as PaymentMethod,
  nom: d.thirdparty_name || '',
  telephone: d.thirdparty_phone || '',
  ville: d.thirdparty_town || '',
  address: d.thirdparty_address || '',
  plateforme: d.array_options?.options_plateforme || 'Clients',
  agentId: d.array_options?.options_agent_id || '',
  shippingCost: d.total_shipping || 0,
  items: (d.lines || []).map((l: any) => ({
    id: l.id,
    brand: l.array_options?.options_brand || '',
    productCode: l.ref || '',
    productName: l.label || '',
    quantity: l.qty || 0,
    unitPrice: l.subprice || 0,
    priceHT: l.total_ht || 0,
  })),
} as Order);

const mapOrderToDolibarr = (o: Partial<Order>): any => ({
  socid: o.contact_id,
  date: new Date().toISOString().split('T')[0],
  type: 0,
  array_options: {
    options_plateforme: o.plateforme,
    options_agent_id: o.agentId,
  },
  lines: o.items?.map(i => ({
    fk_product: i.productCode, // Assuming productCode is the ID for now
    qty: i.quantity,
    subprice: i.unitPrice,
    array_options: {
      options_brand: i.brand
    }
  }))
});

export const orderService = {
  async fetchOrders(page = 0, limit = 100): Promise<Order[]> {
    const response = await apiClient.get<any>(`/orders?page=${page}&limit=${limit}`);
    return (response.result || []).map(mapDolibarrToOrder);
  },

  async getOrderById(id: string): Promise<Order> {
    const response = await apiClient.get<any>(`/orders/${id}`);
    return mapDolibarrToOrder(response.result);
  },

  async createOrder(order: Partial<Order>): Promise<Order> {
    const body = mapOrderToDolibarr(order);
    const response = await apiClient.post<any>('/orders', body);
    return mapDolibarrToOrder(response.result);
  },

  async updateOrder(id: string, order: Partial<Order>): Promise<void> {
    const body = mapOrderToDolibarr(order);
    await apiClient.put<any>(`/orders/${id}`, body);
  },

  async deleteOrder(id: string): Promise<void> {
    await apiClient.delete<any>(`/orders/${id}`);
  }
};
