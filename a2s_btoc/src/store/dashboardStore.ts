import { create } from 'zustand';
import { contactService } from '../services/contactService';
import { orderService } from '../services/orderService';
import { agendaService } from '../services/agendaService';
import { DashboardData } from '../types';
import toast from 'react-hot-toast';

interface DashboardState {
  loading: boolean;
  error: string | null;
  dataCache: { [key: string]: DashboardData };
  fetchDashboardData: (
    component: string, 
    userRole: string, 
    userId?: string, 
    userCodeAgence?: string,
    startDate?: Date | null,
    endDate?: Date | null
  ) => Promise<DashboardData>;
  clearCache: () => void;
}

export const useDashboardStore = create<DashboardState>()((set, get) => ({
  loading: false,
  error: null,
  dataCache: {},

  clearCache: () => {
    set({ dataCache: {} });
  },

  fetchDashboardData: async (component, userRole, userId, userCodeAgence, startDate, endDate) => {
    const cacheKey = `${component}_${userRole}_${userId || 'all'}_${userCodeAgence || 'all'}`;
    
    const cached = get().dataCache[cacheKey];
    if (cached) return cached;

    set({ loading: true, error: null });
    
    try {
      let data: DashboardData = { labels: [], datasets: [] };

      // In a real migration, we would fetch and aggregate data here
      // For now, return empty data or implement some basic aggregation
      
      switch (component) {
        case 'DailyOrdersChart':
          const orders = await orderService.fetchOrders(0, 1000);
          // Aggregate by day...
          data = { labels: ['Today'], datasets: [{ label: 'Commandes', data: [orders.length] }] };
          break;
        // Add other cases as needed...
        default:
          data = { labels: [], datasets: [] };
      }

      set(state => ({
        dataCache: { ...state.dataCache, [cacheKey]: data },
        loading: false
      }));

      return data;
    } catch (error) {
      console.error(`Error fetching ${component}:`, error);
      set({ error: 'Erreur lors du chargement des données', loading: false });
      return { labels: [], datasets: [] };
    }
  }
}));
