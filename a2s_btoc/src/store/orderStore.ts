import { create } from 'zustand';
import { supabase } from '../lib/supabase';
import { useAuthStore } from './authStore';

export interface OrderItem {
  id: string;
  brand: string;
  product_id: string;
  product_name: string;
  quantity: number;
  unit_price: number;
}

export interface Order {
  id: string;
  orderSupabaseId: string;
  contact_id: string;
  shipping_cost: number;
  total: number;
  created_at: string;
  updated_at: string;
  delivery_date?: string;
  confirmation_note?: string;
  confirmed_by?: string;
  confirmed_at?: Date;
  dispatched_by?: string;
  dispatched_at?: Date;
  tracking_number?: string;
  delivery_note?: string;
  order_number: number;
  exported_at?: Date;
  exported_by?: string;
  items?: OrderItem[];
  // Contact fields
  nom: string;
  telephone: string;
  telephone2?: string;
  ville: string;
  address: string;
  plateforme: string;
  status: string;
  paymentMethod?: string;
  transferNumber?: string;
  agentId: string;
  agentName?: string;
  agentCode?: string;
  source?: string;
  fromAds?: boolean;
  codeAgence?: string;
  orderNumber: string;
  createdAt: Date;
  cancellationNote?: string;
  shippingCost: number;
}

interface OrderState {
  orders: Order[];
  loading: boolean;
  error: string | null;
  fetchOrders: () => Promise<void>;
  createOrder: (orderData: Partial<Order>) => Promise<Order | null>;
  updateOrder: (id: string, updates: Partial<Order>) => Promise<void>;
  deleteOrder: (id: string) => Promise<void>;
  exportOrders: (orderIds: string[]) => Promise<void>;
  confirmOrder: (id: string, orderSupabaseId: string, note: string, userId: string) => Promise<void>;
  updateOrderStatus: (id: string, orderSupabaseId: string, status: string, note?: string, userId?: string) => Promise<void>;
  cancelOrder: (id: string, orderSupabaseId: string, note: string) => Promise<void>;
  getFilteredOrdersByTeam: (userRole: string, codeAgence?: string) => Order[];
  clearError: () => void;
}

export const useOrderStore = create<OrderState>()((set, get) => ({
  orders: [],
  loading: false,
  error: null,
  
  createOrderForNewContact: async (orderData, existingContactId) => {
    try {
      console.log('🆕 Adding new order with data:', orderData);
      console.log('Using existing contact ID:', existingContactId);
      
      // Validate order items before insertion
      const validatedItems = orderData.items?.map(item => {
        const validBrands = ['D-WHITE', 'D-CAP', 'SENSILIS', 'CUMLAUDE', 'BABE', 'BUCCOTHERM', 'CASMARA RETAIL'];
        const brand = validBrands.includes(item.brand) ? item.brand : 'D-WHITE';
        
        return {
          ...item,
          brand: brand,
          productCode: item.productCode || '',
          productName: item.productName || '',
          quantity: Math.max(1, item.quantity || 1),
          priceHT: Math.max(0, item.priceHT || 0),
          unitPrice: Math.max(0, item.unitPrice || 0)
        };
      }) || [];
      
      let contactId;
      
      if (existingContactId) {
        // Use existing contact but update its status and other order-related fields
        contactId = existingContactId;
        
        const { error: contactError } = await supabase
          .from('contacts')
          .update({
            status: 'À confirmer',
            type_de_demande: 'Commande',
            ville: orderData.ville,
            address: orderData.address,
            payment_method: orderData.paymentMethod,
            transfer_number: orderData.transferNumber,
            updated_at: new Date().toISOString()
          })
          .eq('id', existingContactId);

        if (contactError) {
          console.error('❌ Error updating existing contact:', contactError);
          throw contactError;
        }
        
        console.log('✅ Existing contact updated successfully:', existingContactId);
      } else {
        // Create a new contact
        const { data: contactData, error: contactError } = await supabase
          .from('contacts')
          .insert([
            {
              nom: orderData.nom,
              telephone: orderData.telephone,
              telephone2: orderData.telephone2,
              address: orderData.address,
              plateforme: orderData.plateforme,
              message: orderData.message,
              type_de_demande: 'Commande',
              ville: orderData.ville,
              sexe: orderData.sexe,
              from_ads: orderData.fromAds,
              status: 'À confirmer',
              agent_id: orderData.agentId,
              date_message: orderData.dateMessage,
              source: orderData.source,
              commerciale: orderData.agentCode,
              code_agence: orderData.codeAgence,
              payment_method: orderData.paymentMethod,
              transfer_number: orderData.transferNumber,
              client_code: orderData.clientCode // Use provided client code if available
            }
          ])
          .select()
          .single();

        if (contactError) {
          console.error('❌ Contact creation error:', contactError);
          throw contactError;
        }

        contactId = contactData.id;
        console.log('✅ New contact created successfully:', contactId);
      }

      // Let PostgreSQL auto-generate the order number
      const { data: orderDbData, error: orderError } = await supabase
        .from('orders')
        .insert([
          {
            contact_id: contactId,
            shipping_cost: orderData.shippingCost,
            total: orderData.total,
          }
        ])
        .select()
        .single();

      if (orderError) {
        console.error('❌ Order creation error:', orderError);
        throw orderError;
      }

      console.log('✅ Order record created successfully with auto-generated order number:', orderDbData.order_number);

      // Add order items with validation and product names
      if (validatedItems.length > 0) {
        const insertData = validatedItems.map(item => ({
          order_id: orderDbData.id,
          brand: item.brand,
          product_id: item.productCode,
          product_name: item.productName, // Store the actual product name
          quantity: item.quantity,
          unit_price: item.unitPrice,
        }));

        console.log('📤 Inserting validated order items:', insertData);

        const { error: itemsError } = await supabase
          .from('order_items')
          .insert(insertData);

        if (itemsError) {
          console.error('❌ Order items creation error:', itemsError);
          console.error('❌ Insert data that failed:', insertData);
          throw itemsError;
        }

        console.log('✅ Order items created successfully with validation');
      }

      // Get the contact details to ensure we have the latest data
      const { data: contactDetails, error: contactDetailsError } = await supabase
        .from('contacts')
        .select('*')
        .eq('id', contactId)
        .single();

      if (contactDetailsError) {
        console.error('❌ Error fetching contact details:', contactDetailsError);
        throw contactDetailsError;
      }

      const newOrder: Order = {
        ...orderData,
        id: contactId,
        orderSupabaseId: orderDbData.id,
        orderNumber: orderDbData.order_number.toString(), // Use auto-generated order number from database
        items: validatedItems,
        createdAt: new Date(contactDetails.created_at),
        updatedAt: new Date(contactDetails.updated_at),
        status: 'À confirmer',
        clientCode: contactDetails.client_code || undefined,
      };

      set((state) => ({
        orders: [newOrder, ...state.orders],
      }));

      console.log('✅ Order added successfully with auto-generated order number:', orderDbData.order_number);
      
      // Refresh orders to ensure we have the latest data
      get().fetchOrders();
      
      return newOrder;
    } catch (error) {
      console.error('❌ Error adding order:', error);
      set({ error: error.message || 'Failed to add order' });
      return null;
    }
  },

  fetchOrders: async () => {
    set({ loading: true, error: null });
    try {
      console.log('🔄 Starting to fetch orders from Supabase...');
      
      // Fetch orders with contact data and order items
      const { data: ordersData, error: ordersError } = await supabase
        .from('orders')
        .select(`
          *,
          contact:contact_id (
  id, nom, telephone, telephone2, address, plateforme, message, 
  type_de_demande, ville, sexe, from_ads, status, created_at, 
  agent_id, updated_at, date_message, source, commerciale, 
  marque, interet, payment_method, transfer_number, code_agence,
  client_code,
  agent_user:agent_id ( name )
),
        
          order_items (id, brand, product_id, product_name, quantity, unit_price)
        `)
        .not('contact.plateforme', 'eq', 'Clients') // Exclude orders from 'Clients' platform
        .range(0, 99999)
        .order('created_at', { ascending: false });

      if (ordersError) {
        console.error('❌ Error fetching orders:', ordersError);
        throw ordersError;
      }

      console.log('✅ Raw orders data from database:', ordersData?.length, 'orders');

      // Transform the data into Order objects
      const orders: Order[] = []; 

      ordersData?.forEach(orderData => {
        const contact = orderData.contact;
        if (!contact) {
          console.warn('⚠️ Order without contact found:', orderData.id);
          return;
        }

        console.log(`📋 Processing order ${orderData.order_number}`);
        console.log(`🔍 [DIAGNOSTIC] Raw order_items for order ${orderData.order_number}:`, JSON.stringify(orderData.order_items, null, 2));
        
        // Process order items
        const orderItems = orderData.order_items?.map((item: any) => {
          console.log(`🔍 [DIAGNOSTIC] Processing item:`, JSON.stringify(item, null, 2));
          return {
            id: item.id,
            brand: item.brand,
            productCode: item.product_id || '',
            productName: item.product_name || '',
            quantity: item.quantity || 1,
            priceHT: (item.unit_price / 1.2) || 0,
            unitPrice: item.unit_price || 0
          };
        }) || [];

        console.log(`🔍 [DIAGNOSTIC] Processed ${orderItems.length} items for order ${orderData.order_number}`);
        const order: Order = {
          id: contact.id,
          orderSupabaseId: orderData.id,
          orderNumber: orderData.order_number.toString(),
          nom: contact.nom,
          telephone: contact.telephone,
          telephone2: contact.telephone2,
          address: contact.address || '',
          plateforme: contact.plateforme,
          message: contact.message,
          typeDeDemande: contact.type_de_demande,
          ville: contact.ville,
          sexe: contact.sexe,
          fromAds: contact.from_ads,
          status: contact.status,
          createdAt: new Date(contact.created_at),
          agentId: contact.agent_id,
          agentCode: contact.commerciale,
          agentName: contact.agent_user?.name || contact.commerciale,
          codeAgence: contact.code_agence,
          updatedAt: new Date(contact.updated_at),
          dateMessage: contact.date_message ? new Date(contact.date_message) : undefined,
          source: contact.source,
          commerciale: contact.commerciale,
          marque: contact.marque,
          interet: contact.interet,
          paymentMethod: contact.payment_method || 'Espèce',
          transferNumber: contact.transfer_number,
          items: orderItems,
          shippingCost: orderData.shipping_cost || 0,
          total: orderData.total || 0,
          totalHT: (orderData.total / 1.2) || 0,
          confirmationNote: orderData.confirmation_note,
          confirmedBy: orderData.confirmed_by,
          confirmedAt: orderData.confirmed_at ? new Date(orderData.confirmed_at) : undefined,
          dispatchedBy: orderData.dispatched_by,
          dispatchedAt: orderData.dispatched_at ? new Date(orderData.dispatched_at) : undefined,
          trackingNumber: orderData.tracking_number,
          deliveryNote: orderData.delivery_note,
          exportedAt: orderData.exported_at ? new Date(orderData.exported_at) : undefined,
          exportedBy: orderData.exported_by,
          clientCode: contact.client_code,
        };
        
        console.log(`🔍 [DIAGNOSTIC] Final order object with ${order.items.length} items:`, JSON.stringify(order.items, null, 2));
        orders.push(order);
      });

      console.log(`✅ Successfully processed ${orders.length} orders`);
      
      set({ orders, loading: false });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to fetch orders',
        loading: false 
      });
    }
  },

  updateOrder: async (id, updates) => {
    set({ loading: true, error: null });
    try {
      set(state => ({
        orders: state.orders.map(order => 
          order.id === id ? { ...order, ...updates } : order
        ),
        loading: false
      }));
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to update order',
        loading: false 
      });
    }
  },

  deleteOrder: async (id) => {
    set({ loading: true, error: null });
    try {
      set(state => ({
        orders: state.orders.filter(order => order.id !== id),
        loading: false
      }));
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to delete order',
        loading: false 
      });
    }
  },

  exportOrders: async (orderIds) => {
    set({ loading: true, error: null });
    try {
      // This would be replaced with actual API call
      set({ loading: false });
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to export orders',
        loading: false 
      });
    }
  },

  confirmOrder: async (id, orderSupabaseId, note, userId) => {
    set({ loading: true, error: null });
    try {
      console.log('🔄 Confirming order:', { id, orderSupabaseId, note, userId });
      
      // Update order in Supabase
      const { error: orderError } = await supabase
        .from('orders')
        .update({
          confirmation_note: note,
          confirmed_by: userId,
          confirmed_at: new Date().toISOString()
        })
        .eq('id', orderSupabaseId);

      if (orderError) {
        console.error('❌ Error updating order:', orderError);
        throw orderError;
      }
      
      // Update contact status in Supabase
      const { error: contactError } = await supabase
        .from('contacts')
        .update({
          status: 'Confirmée',
          updated_at: new Date().toISOString()
        })
        .eq('id', id);

      if (contactError) {
        console.error('❌ Error updating contact status:', contactError);
        throw contactError;
      }
      
      console.log('✅ Order confirmed successfully');
      
      // Update local state
      set(state => ({
        orders: state.orders.map(order => 
          order.id === id ? { 
            ...order, 
            status: 'Confirmée',
            confirmationNote: note,
            confirmedBy: userId,
            confirmedAt: new Date()
          } : order
        ),
        loading: false
      }));
      
      // Refresh orders to ensure we have the latest data
      setTimeout(() => {
        get().fetchOrders();
      }, 500);
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to confirm order',
        loading: false 
      });
    }
  },

  updateOrderStatus: async (id, orderSupabaseId, status, note, userId) => {
    set({ loading: true, error: null });
    try {
      console.log('🔄 Updating order status:', { id, orderSupabaseId, status, note, userId });
      
      // Update order in Supabase if note or userId is provided
      if (note || userId) {
        const updateData: any = {};
        if (note) updateData.confirmation_note = note;
        if (userId) updateData.confirmed_by = userId;
        
        const { error: orderError } = await supabase
          .from('orders')
          .update(updateData)
          .eq('id', orderSupabaseId);

        if (orderError) {
          console.error('❌ Error updating order:', orderError);
          throw orderError;
        }
      }
      
      // Update contact status in Supabase
      const { error: contactError } = await supabase
        .from('contacts')
        .update({
          status: status,
          updated_at: new Date().toISOString()
        })
        .eq('id', id);

      if (contactError) {
        console.error('❌ Error updating contact status:', contactError);
        throw contactError;
      }
      
      console.log('✅ Order status updated successfully to:', status);
      
      // Update local state
      set(state => ({
        orders: state.orders.map(order => 
          order.id === id ? { 
            ...order, 
            status,
            ...(note && { confirmationNote: note }),
            ...(userId && { confirmedBy: userId })
          } : order
        ),
        loading: false
      }));
      
      // Refresh orders to ensure we have the latest data
      setTimeout(() => {
        get().fetchOrders();
      }, 500);
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to update order status',
        loading: false 
      });
    }
  },

  cancelOrder: async (id, orderSupabaseId, note) => {
    set({ loading: true, error: null });
    try {
      console.log('🔄 Cancelling order:', { id, orderSupabaseId, note });
      
      // Update order in Supabase
      const { error: orderError } = await supabase
        .from('orders')
        .update({
          cancellation_note: note
        })
        .eq('id', orderSupabaseId);

      if (orderError) {
        console.error('❌ Error updating order with cancellation note:', orderError);
        throw orderError;
      }
      
      // Update contact status in Supabase
      const { error: contactError } = await supabase
        .from('contacts')
        .update({
          status: 'Annulée',
          updated_at: new Date().toISOString()
        })
        .eq('id', id);

      if (contactError) {
        console.error('❌ Error updating contact status to cancelled:', contactError);
        throw contactError;
      }
      
      console.log('✅ Order cancelled successfully');
      
      // Update local state
      set(state => ({
        orders: state.orders.map(order => 
          order.id === id ? { 
            ...order, 
            status: 'Annulée',
            cancellationNote: note
          } : order
        ),
        loading: false
      }));
      
      // Refresh orders to ensure we have the latest data
      setTimeout(() => {
        get().fetchOrders();
      }, 500);
    } catch (error) {
      set({ 
        error: error instanceof Error ? error.message : 'Failed to cancel order',
        loading: false 
      });
    }
  },

  getFilteredOrdersByTeam: (userRole: string, codeAgence?: string) => {
    const { orders } = get();
    
    console.log(`🔍 Filtering orders for role: ${userRole}, team code: ${codeAgence || 'none'}`);
    console.log(`📊 Total orders before filtering: ${orders.length}`);
    
    // Admins see all orders
    if (userRole === 'admin') {
      return orders;
    }
    
    // For supervisors, filter by code_agence
    if (userRole === 'superviseur') {
      if (codeAgence) {
        // Supervisors see all orders from their team
        const teamOrders = orders.filter(order => order.codeAgence === codeAgence);
        console.log(`📊 Filtered orders for supervisor team ${codeAgence}: ${teamOrders.length}`);
        return teamOrders;
      } else {
        console.log('⚠️ Supervisor without team code, returning all orders');
        return orders;
      }
    }
    
    // For agents, filter by their own agent ID
    if (userRole === 'agent') {
      // Get the current user ID from the auth store
      const userId = useAuthStore.getState().user?.id;
      if (userId) {
        // Filter orders by the agent's ID
        const agentOrders = orders.filter(order => order.agentId === userId);
        console.log(`📊 Filtered orders for agent ${userId}: ${agentOrders.length}`);
        return agentOrders;
      } else {
        console.log('⚠️ No user ID available for agent, returning empty array');
        return [];
      }
    }
    
    // Default fallback - return all orders
    console.log('⚠️ Using default filter, returning all orders');
    return orders;
  },

  clearError: () => set({ error: null })
}));

// Add the createOrderForExistingContact function
const createOrderForExistingContact = async (contactId: string, orderData: any) => {
  try {
    console.log('🔄 Creating new order for existing contact:', contactId);
    console.log('🔍 Order data:', JSON.stringify(orderData, null, 2));
    
    // Always create a new order for the existing contact
    console.log('🆕 Creating new order for contact:', contactId);
    
    // Create new order
    const { data: newOrderData, error: newOrderError } = await supabase
      .from('orders')
      .insert([{
        contact_id: contactId,
        shipping_cost: orderData.shippingCost,
        total: orderData.total,
      }])
      .select()
      .single();

    if (newOrderError) {
      console.error('❌ New order creation error:', newOrderError);
      throw newOrderError;
    }

    console.log('✅ New order created with ID:', newOrderData.id);
    
    // Insert order items
    if (orderData.items && orderData.items.length > 0) {
      const insertData = orderData.items.map((item: any) => ({
        order_id: newOrderData.id,
        brand: item.brand,
        product_id: item.productCode || item.product_id,
        product_name: item.productName || item.product_name,
        quantity: item.quantity,
        unit_price: item.unitPrice || item.unit_price,
      }));

      const { error: itemsError } = await supabase
        .from('order_items')
        .insert(insertData);

      if (itemsError) {
        console.error('❌ Order items creation error:', itemsError);
        throw itemsError;
      }
    }
    
    // Update contact status
    const { error: contactError } = await supabase
      .from('contacts')
      .update(orderData.clientCode ? {
        status: 'À confirmer',
        type_de_demande: 'Commande',
        ville: orderData.ville,
        address: orderData.address,
        payment_method: orderData.paymentMethod,
        transfer_number: orderData.transferNumber,
        updated_at: new Date().toISOString(),
        client_code: orderData.clientCode
      } : {
        status: 'À confirmer',
        type_de_demande: 'Commande',
        ville: orderData.ville,
        address: orderData.address,
        payment_method: orderData.paymentMethod,
        transfer_number: orderData.transferNumber,
        updated_at: new Date().toISOString()
      })
      .eq('id', contactId);

    if (contactError) {
      console.error('❌ Contact update error:', contactError);
      throw contactError;
    }
    
    console.log('✅ New order created successfully');
    
    // Refresh orders to get the new data
    await useOrderStore.getState().fetchOrders();
    
    // Return the order data with the new order ID
    return {
      ...orderData,
      id: contactId,
      orderSupabaseId: newOrderData.id,
      orderNumber: newOrderData.order_number.toString()
    };
  } catch (error) {
    console.error('❌ Error upserting order:', error);
    throw error;
  }
};

// Add the function to the store
useOrderStore.getState().createOrderForExistingContact = createOrderForExistingContact;

// Export the function for backward compatibility
export const getFilteredOrdersByTeam = (userRole: string, codeAgence?: string) => {
  return useOrderStore.getState().getFilteredOrdersByTeam(userRole, codeAgence);
};