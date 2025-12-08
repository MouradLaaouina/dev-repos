import { create } from 'zustand';
import { supabase } from '../lib/supabase';
import { DashboardData, AgentPerformance, TeamStats } from '../types';
import { TEAM_CODES } from '../config/dashboardConfig';
import toast from 'react-hot-toast';

interface DashboardState {
  loading: boolean;
  error: string | null;
  
  // Data cache
  dataCache: { [key: string]: DashboardData };
  
  // Generic fetch method
  fetchDashboardData: (
    component: string, 
    userRole: string, 
    userId?: string, 
    userCodeAgence?: string,
    startDate?: Date | null,
    endDate?: Date | null
  ) => Promise<DashboardData>;
  
  // Clear cache
  clearCache: () => void;
}

export const useDashboardStore = create<DashboardState>()((set, get) => ({
  loading: false,
  error: null,
  dataCache: {},

  clearCache: () => {
    set({ dataCache: {} });
  },

  fetchDashboardData: async (
    component: string, 
    userRole: string, 
    userId?: string, 
    userCodeAgence?: string,
    startDate?: Date | null,
    endDate?: Date | null
  ) => {
    // Create a cache key that includes date filter info
    const dateKey = startDate && endDate 
      ? `_${startDate.toISOString()}_${endDate.toISOString()}` 
      : '';
    const cacheKey = `${component}_${userRole}_${userId || 'all'}_${userCodeAgence || 'all'}${dateKey}`;
    
    // Return cached data if available
    const cached = get().dataCache[cacheKey];
    if (cached) {
      return cached;
    }

    set({ loading: true, error: null });
    
    try {
      console.log(`📊 Fetching ${component} for role: ${userRole}, team: ${userCodeAgence}, date range: ${startDate?.toISOString()} to ${endDate?.toISOString()}`);
      
      let data: DashboardData;

      switch (component) {
        case 'DailyOrdersChart':
          data = await fetchDailyOrders(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'DailyMessagesChart':
          data = await fetchDailyMessages(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'DailyCallsChart':
          data = await fetchDailyCalls(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'ConversionRateChart':
          data = await fetchConversionRate(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'AverageBasketChart':
          data = await fetchAverageBasket(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'OrdersByAgentChart':
          data = await fetchOrdersByAgent(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'MessagesByAgentChart':
          data = await fetchMessagesByAgent(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'CallsByAgentChart':
          data = await fetchCallsByAgent(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'MessagesByChannelChart':
          data = await fetchMessagesByChannel(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'OrdersByBrandChart':
          data = await fetchOrdersByBrand(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'RequestTypesByAgentChart':
          data = await fetchRequestTypesByAgent(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'PhoneNumbersByAgentChart':
          data = await fetchPhoneNumbersByAgent(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'CallDetailsChart':
          data = await fetchCallDetails(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'OrdersByTeamChart':
          data = await fetchOrdersByTeam(startDate, endDate);
          break;
        case 'MessagesByTeamChart':
          data = await fetchMessagesByTeam(startDate, endDate);
          break;
        case 'AverageBasketByTeamChart':
          data = await fetchAverageBasketByTeam(startDate, endDate);
          break;
        case 'ConversionByAgentBrandChart':
          data = await fetchConversionByAgentBrand(startDate, endDate);
          break;
        case 'CallResultsChart':
          data = await fetchCallResultsChart(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'SatisfactionChart':
          data = await fetchSatisfactionChart(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        // Source Statistics Charts
        case 'ContactsBySourceChart':
          data = await fetchContactsBySource(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'RequestTypesBySourceChart':
          data = await fetchRequestTypesBySource(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'ConversionRateBySourceChart':
          data = await fetchConversionRateBySource(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'OrdersBySourceChart':
          data = await fetchOrdersBySource(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        case 'AverageBasketBySourceChart':
          data = await fetchAverageBasketBySource(userRole, userId, userCodeAgence, startDate, endDate);
          break;
        default:
          throw new Error(`Unknown component: ${component}`);
      }

      // Cache the result
      set(state => ({
        dataCache: { ...state.dataCache, [cacheKey]: data },
        loading: false
      }));

      return data;
    } catch (error) {
      console.error(`Error fetching ${component}:`, error);
      set({ error: 'Erreur lors du chargement des données', loading: false });
      toast.error(`Erreur lors du chargement de ${component}`);
      throw error;
    }
  }
}));

// Helper functions for data fetching with role-based filtering and agent names

async function fetchDailyOrders(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('created_at, agent_id, code_agence')
    .eq('type_de_demande', 'Commande')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by day
  const dailyData: { [key: string]: number } = {};
  data?.forEach(order => {
    const day = new Date(order.created_at).toLocaleDateString('fr-FR');
    dailyData[day] = (dailyData[day] || 0) + 1;
  });

  // Get last 30 days or the date range if provided
  let dateRange: string[] = [];
  
  if (startDate && endDate) {
    // Create array of dates between start and end
    const dates = [];
    const currentDate = new Date(startDate);
    while (currentDate <= endDate) {
      dates.push(new Date(currentDate).toLocaleDateString('fr-FR'));
      currentDate.setDate(currentDate.getDate() + 1);
    }
    dateRange = dates;
  } else {
    // Default to last 30 days
    dateRange = Array.from({ length: 30 }, (_, i) => {
      const date = new Date();
      date.setDate(date.getDate() - (29 - i));
      return date.toLocaleDateString('fr-FR');
    });
  }

  return {
    labels: dateRange,
    datasets: [{
      label: 'Commandes',
      data: dateRange.map(day => dailyData[day] || 0),
      borderColor: '#10B981',
      backgroundColor: 'rgba(16, 185, 129, 0.1)',
      borderWidth: 2,
      fill: true
    }]
  };
}

async function fetchDailyMessages(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('created_at, agent_id, code_agence')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by day
  const dailyData: { [key: string]: number } = {};
  data?.forEach(contact => {
    const day = new Date(contact.created_at).toLocaleDateString('fr-FR');
    dailyData[day] = (dailyData[day] || 0) + 1;
  });

  // Get date range
  let dateRange: string[] = [];
  
  if (startDate && endDate) {
    // Create array of dates between start and end
    const dates = [];
    const currentDate = new Date(startDate);
    while (currentDate <= endDate) {
      dates.push(new Date(currentDate).toLocaleDateString('fr-FR'));
      currentDate.setDate(currentDate.getDate() + 1);
    }
    dateRange = dates;
  } else {
    // Default to last 30 days
    dateRange = Array.from({ length: 30 }, (_, i) => {
      const date = new Date();
      date.setDate(date.getDate() - (29 - i));
      return date.toLocaleDateString('fr-FR');
    });
  }

  return {
    labels: dateRange,
    datasets: [{
      label: 'Messages',
      data: dateRange.map(day => dailyData[day] || 0),
      borderColor: '#3B82F6',
      backgroundColor: 'rgba(59, 130, 246, 0.1)',
      borderWidth: 2,
      fill: true
    }]
  };
}

async function fetchDailyCalls(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Query call_logs table for actual call data
  let query = supabase
    .from('call_logs')
    .select('call_date, agent_id')
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    // For supervisors, we need to join with users table to filter by code_agence
    // This is a simplified approach - in a real implementation, you'd need a more complex query
    const { data: teamAgents } = await supabase
      .from('users')
      .select('id')
      .eq('code_agence', userCodeAgence);
    
    if (teamAgents && teamAgents.length > 0) {
      const agentIds = teamAgents.map(agent => agent.id);
      query = query.in('agent_id', agentIds);
    }
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('call_date', startDate.toISOString())
                .lte('call_date', endDate.toISOString());
  }

  const { data, error } = await query;
  
  // If no data or error, return placeholder
  if (error || !data || data.length === 0) {
    return {
      labels: ['Pas de données'],
      datasets: [{
        label: 'Appels',
        data: [0],
        borderColor: '#EF4444',
        backgroundColor: 'rgba(239, 68, 68, 0.1)',
        borderWidth: 2,
        fill: true
      }]
    };
  }

  // Group by day
  const dailyData: { [key: string]: number } = {};
  data.forEach(call => {
    const day = new Date(call.call_date).toLocaleDateString('fr-FR');
    dailyData[day] = (dailyData[day] || 0) + 1;
  });

  // Get date range
  let dateRange: string[] = [];
  
  if (startDate && endDate) {
    // Create array of dates between start and end
    const dates = [];
    const currentDate = new Date(startDate);
    while (currentDate <= endDate) {
      dates.push(new Date(currentDate).toLocaleDateString('fr-FR'));
      currentDate.setDate(currentDate.getDate() + 1);
    }
    dateRange = dates;
  } else {
    // Default to last 30 days
    dateRange = Array.from({ length: 30 }, (_, i) => {
      const date = new Date();
      date.setDate(date.getDate() - (29 - i));
      return date.toLocaleDateString('fr-FR');
    });
  }

  return {
    labels: dateRange,
    datasets: [{
      label: 'Appels',
      data: dateRange.map(day => dailyData[day] || 0),
      borderColor: '#EF4444',
      backgroundColor: 'rgba(239, 68, 68, 0.1)',
      borderWidth: 2,
      fill: true
    }]
  };
}

async function fetchCallResultsChart(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Query call_logs table for call status data
  let query = supabase
    .from('call_logs')
    .select('call_status, agent_id')
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    // For supervisors, we need to join with users table to filter by code_agence
    const { data: teamAgents } = await supabase
      .from('users')
      .select('id')
      .eq('code_agence', userCodeAgence);
    
    if (teamAgents && teamAgents.length > 0) {
      const agentIds = teamAgents.map(agent => agent.id);
      query = query.in('agent_id', agentIds);
    }
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('call_date', startDate.toISOString())
                .lte('call_date', endDate.toISOString());
  }

  const { data, error } = await query;
  
  // If no data or error, return placeholder
  if (error || !data || data.length === 0) {
    return {
      labels: ['Pas de données'],
      datasets: [{
        label: 'Résultats',
        data: [0],
        backgroundColor: ['#D1D5DB'],
        borderWidth: 0
      }]
    };
  }

  // Group by call status
  const statusCounts: { [status: string]: number } = {};
  data.forEach(call => {
    statusCounts[call.call_status] = (statusCounts[call.call_status] || 0) + 1;
  });

  // Prepare data for chart
  const labels = Object.keys(statusCounts);
  const values = Object.values(statusCounts);

  // Define colors for each status
  const colors = labels.map(status => {
    switch (status) {
      case 'À rappeler': return '#FBBF24'; // yellow
      case 'Pas intéressé(e)': return '#EF4444'; // red
      case 'Intéressé(e)': return '#10B981'; // green
      case 'Ne réponds jamais': return '#6B7280'; // gray
      case 'Faux numéro': return '#F97316'; // orange
      default: return '#3B82F6'; // blue
    }
  });

  return {
    labels,
    datasets: [{
      label: 'Résultats d\'appels',
      data: values,
      backgroundColor: colors,
      borderWidth: 0
    }]
  };
}

async function fetchSatisfactionChart(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Query call_logs table for satisfaction data
  let query = supabase
    .from('call_logs')
    .select(`
      satisfaction_level,
      agent_id,
      users:agent_id (name)
    `)
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    // For supervisors, we need to join with users table to filter by code_agence
    const { data: teamAgents } = await supabase
      .from('users')
      .select('id')
      .eq('code_agence', userCodeAgence);
    
    if (teamAgents && teamAgents.length > 0) {
      const agentIds = teamAgents.map(agent => agent.id);
      query = query.in('agent_id', agentIds);
    }
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('call_date', startDate.toISOString())
                .lte('call_date', endDate.toISOString());
  }

  const { data, error } = await query;
  
  // If no data or error, return placeholder
  if (error || !data || data.length === 0) {
    return {
      labels: ['Pas de données'],
      datasets: [{
        label: 'Satisfaction',
        data: [0],
        backgroundColor: ['#D1D5DB'],
        borderWidth: 0
      }]
    };
  }

  // Group by agent and calculate average satisfaction
  const agentSatisfaction: { [agent: string]: { total: number, count: number } } = {};
  
  data.forEach(log => {
    const agentName = log.users?.name || `Agent ${log.agent_id.substring(0, 8)}`;
    
    if (!agentSatisfaction[agentName]) {
      agentSatisfaction[agentName] = { total: 0, count: 0 };
    }
    
    agentSatisfaction[agentName].total += log.satisfaction_level;
    agentSatisfaction[agentName].count += 1;
  });

  // Calculate averages and prepare data for chart
  const agents = Object.keys(agentSatisfaction);
  const averages = agents.map(agent => {
    const { total, count } = agentSatisfaction[agent];
    return count > 0 ? total / count : 0;
  });

  return {
    labels: agents,
    datasets: [{
      label: 'Satisfaction moyenne (1-5)',
      data: averages,
      backgroundColor: '#8B5CF6',
      borderColor: '#8B5CF6',
      borderWidth: 1
    }]
  };
}

async function fetchConversionRate(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Join with users table to get agent names
  let query = supabase
    .from('contacts')
    .select(`
      type_de_demande, 
      agent_id, 
      code_agence,
      users!inner(name, id)
    `)
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Calculate conversion rate by agent using actual names
  const agentStats: { [agentName: string]: { total: number, orders: number } } = {};

  data?.forEach(contact => {
    const agentName = contact.users?.name || 'Agent inconnu';
    if (!agentStats[agentName]) {
      agentStats[agentName] = { total: 0, orders: 0 };
    }
    agentStats[agentName].total++;
    if (contact.type_de_demande === 'Commande') {
      agentStats[agentName].orders++;
    }
  });

  const agentNames = Object.keys(agentStats);
  const conversionRates = agentNames.map(agentName => {
    const stats = agentStats[agentName];
    return stats.total > 0 ? (stats.orders / stats.total) * 100 : 0;
  });

  return {
    labels: agentNames,
    datasets: [{
      label: 'Taux de conversion (%)',
      data: conversionRates,
      backgroundColor: '#8B5CF6',
      borderColor: '#8B5CF6',
      borderWidth: 1
    }]
  };
}

async function fetchAverageBasket(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Join with users table to get agent names
  let query = supabase
    .from('orders')
    .select(`
      total,
      contact:contact_id (
        agent_id, 
        code_agence,
        users!inner(name, id)
      )
    `)
    .not('contact.plateforme', 'eq', 'Clients') // Exclude orders from 'Clients' platform
    .range(0, 99999);

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Filter based on role
  const filteredData = data?.filter(order => {
    if (!order.contact) return false;
    
    if (userRole === 'agent' && userId) {
      return order.contact.agent_id === userId;
    } else if (userRole === 'superviseur' && userCodeAgence) {
      return order.contact.code_agence === userCodeAgence;
    }
    return true;
  });

  // Calculate average basket by agent using actual names
  const agentBaskets: { [agentName: string]: { total: number, count: number } } = {};

  filteredData?.forEach(order => {
    const agentName = order.contact?.users?.name || 'Agent inconnu';
    if (!agentBaskets[agentName]) {
      agentBaskets[agentName] = { total: 0, count: 0 };
    }
    agentBaskets[agentName].total += order.total || 0;
    agentBaskets[agentName].count++;
  });

  const agentNames = Object.keys(agentBaskets);
  const averages = agentNames.map(agentName => {
    const basket = agentBaskets[agentName];
    return basket.count > 0 ? basket.total / basket.count : 0;
  });

  return {
    labels: agentNames,
    datasets: [{
      label: 'Panier moyen (DH)',
      data: averages,
      backgroundColor: '#F59E0B',
      borderColor: '#F59E0B',
      borderWidth: 1
    }]
  };
}

async function fetchOrdersByAgent(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Join with users table to get agent names
  let query = supabase
    .from('contacts')
    .select(`
      agent_id, 
      code_agence,
      users!inner(name, id)
    `)
    .eq('type_de_demande', 'Commande')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by agent using actual names
  const agentOrders: { [agentName: string]: number } = {};
  data?.forEach(order => {
    const agentName = order.users?.name || 'Agent inconnu';
    agentOrders[agentName] = (agentOrders[agentName] || 0) + 1;
  });

  return {
    labels: Object.keys(agentOrders),
    datasets: [{
      label: 'Commandes',
      data: Object.values(agentOrders),
      backgroundColor: '#10B981',
      borderColor: '#10B981',
      borderWidth: 1
    }]
  };
}

async function fetchMessagesByAgent(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Join with users table to get agent names
  let query = supabase
    .from('contacts')
    .select(`
      agent_id, 
      code_agence,
      users!inner(name, id)
    `)
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by agent using actual names
  const agentMessages: { [agentName: string]: number } = {};
  data?.forEach(contact => {
    const agentName = contact.users?.name || 'Agent inconnu';
    agentMessages[agentName] = (agentMessages[agentName] || 0) + 1;
  });

  return {
    labels: Object.keys(agentMessages),
    datasets: [{
      label: 'Messages',
      data: Object.values(agentMessages),
      backgroundColor: '#3B82F6',
      borderColor: '#3B82F6',
      borderWidth: 1
    }]
  };
}

async function fetchCallsByAgent(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Query call_logs table for actual call data
  let query = supabase
    .from('call_logs')
    .select(`
      agent_id,
      users:agent_id (name, code_agence)
    `)
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    // For supervisors, we need to filter by code_agence from the joined users table
    // This is a simplified approach - in a real implementation, you'd need a more complex query
    const { data: teamAgents } = await supabase
      .from('users')
      .select('id')
      .eq('code_agence', userCodeAgence);
    
    if (teamAgents && teamAgents.length > 0) {
      const agentIds = teamAgents.map(agent => agent.id);
      query = query.in('agent_id', agentIds);
    }
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('call_date', startDate.toISOString())
                .lte('call_date', endDate.toISOString());
  }

  const { data, error } = await query;
  
  // If no data or error, return placeholder
  if (error || !data || data.length === 0) {
    return {
      labels: ['Pas de données'],
      datasets: [{
        label: 'Appels',
        data: [0],
        backgroundColor: '#EF4444',
        borderColor: '#EF4444',
        borderWidth: 1
      }]
    };
  }

  // Group by agent using actual names
  const agentCalls: { [agentName: string]: number } = {};
  data.forEach(call => {
    const agentName = call.users?.name || `Agent ${call.agent_id.substring(0, 8)}`;
    agentCalls[agentName] = (agentCalls[agentName] || 0) + 1;
  });

  return {
    labels: Object.keys(agentCalls),
    datasets: [{
      label: 'Appels',
      data: Object.values(agentCalls),
      backgroundColor: '#EF4444',
      borderColor: '#EF4444',
      borderWidth: 1
    }]
  };
}

async function fetchMessagesByChannel(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('plateforme, agent_id, code_agence')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by platform
  const platformCounts = {
    Facebook: 0,
    Instagram: 0,
    WhatsApp: 0
  };

  data?.forEach(contact => {
    if (contact.plateforme && platformCounts.hasOwnProperty(contact.plateforme)) {
      platformCounts[contact.plateforme as keyof typeof platformCounts]++;
    }
  });

  return {
    labels: Object.keys(platformCounts),
    datasets: [{
      label: 'Messages',
      data: Object.values(platformCounts),
      backgroundColor: ['#1877F2', '#E4405F', '#25D366'],
      borderWidth: 1
    }]
  };
}

async function fetchOrdersByBrand(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('marque, agent_id, code_agence')
    .eq('type_de_demande', 'Commande')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by brand
  const brandCounts: { [brand: string]: number } = {};
  data?.forEach(order => {
    const brand = order.marque || 'Marque inconnue';
    brandCounts[brand] = (brandCounts[brand] || 0) + 1;
  });

  return {
    labels: Object.keys(brandCounts),
    datasets: [{
      label: 'Commandes',
      data: Object.values(brandCounts),
      backgroundColor: [
        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
        '#FF9F40', '#FF6384', '#C9CBCF'
      ],
      borderWidth: 1
    }]
  };
}

async function fetchRequestTypesByAgent(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('type_de_demande, agent_id, code_agence')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by request type
  const requestTypes: { [type: string]: number } = {};
  data?.forEach(contact => {
    const type = contact.type_de_demande || 'Type inconnu';
    requestTypes[type] = (requestTypes[type] || 0) + 1;
  });

  return {
    labels: Object.keys(requestTypes),
    datasets: [{
      label: 'Types de demande',
      data: Object.values(requestTypes),
      backgroundColor: [
        '#8B5CF6', '#06B6D4', '#10B981', '#F59E0B', '#EF4444',
        '#EC4899', '#6366F1'
      ],
      borderWidth: 1
    }]
  };
}

async function fetchPhoneNumbersByAgent(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Join with users table to get agent names
  let query = supabase
    .from('contacts')
    .select(`
      telephone, 
      agent_id, 
      code_agence,
      users!inner(name, id)
    `)
    .not('telephone', 'is', null)
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by agent using actual names
  const agentPhones: { [agentName: string]: number } = {};
  data?.forEach(contact => {
    const agentName = contact.users?.name || 'Agent inconnu';
    agentPhones[agentName] = (agentPhones[agentName] || 0) + 1;
  });

  return {
    labels: Object.keys(agentPhones),
    datasets: [{
      label: 'Numéros collectés',
      data: Object.values(agentPhones),
      backgroundColor: '#F59E0B',
      borderColor: '#F59E0B',
      borderWidth: 1
    }]
  };
}

async function fetchCallDetails(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Query call_logs table for actual call data
  let query = supabase
    .from('call_logs')
    .select(`
      call_status,
      agent_id,
      users:agent_id (name, code_agence)
    `)
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'agent' && userId) {
    query = query.eq('agent_id', userId);
  } else if (userRole === 'superviseur' && userCodeAgence) {
    // For supervisors, we need to filter by code_agence from the joined users table
    const { data: teamAgents } = await supabase
      .from('users')
      .select('id')
      .eq('code_agence', userCodeAgence);
    
    if (teamAgents && teamAgents.length > 0) {
      const agentIds = teamAgents.map(agent => agent.id);
      query = query.in('agent_id', agentIds);
    }
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('call_date', startDate.toISOString())
                .lte('call_date', endDate.toISOString());
  }

  const { data, error } = await query;
  
  // If no data or error, return placeholder
  if (error || !data || data.length === 0) {
    return {
      labels: ['Pas de données'],
      datasets: [
        {
          label: 'Appels réussis',
          data: [0],
          backgroundColor: '#10B981'
        },
        {
          label: 'Appels échoués',
          data: [0],
          backgroundColor: '#EF4444'
        }
      ]
    };
  }

  // Group by agent and call status
  const agentDetails: { [agentName: string]: { [status: string]: number } } = {};
  
  data.forEach(call => {
    const agentName = call.users?.name || `Agent ${call.agent_id.substring(0, 8)}`;
    
    if (!agentDetails[agentName]) {
      agentDetails[agentName] = {};
    }
    
    const status = call.call_status || 'Inconnu';
    agentDetails[agentName][status] = (agentDetails[agentName][status] || 0) + 1;
  });

  // Prepare data for chart - group into "successful" and "failed" calls
  const agents = Object.keys(agentDetails);
  
  const successfulCalls = agents.map(agent => {
    const statuses = agentDetails[agent];
    // Consider "Intéressé(e)" and "À rappeler" as successful
    return (statuses['Intéressé(e)'] || 0) + (statuses['À rappeler'] || 0);
  });
  
  const failedCalls = agents.map(agent => {
    const statuses = agentDetails[agent];
    // Consider "Pas intéressé(e)", "Ne réponds jamais", and "Faux numéro" as failed
    return (statuses['Pas intéressé(e)'] || 0) + 
           (statuses['Ne réponds jamais'] || 0) + 
           (statuses['Faux numéro'] || 0);
  });

  return {
    labels: agents,
    datasets: [
      {
        label: 'Appels réussis',
        data: successfulCalls,
        backgroundColor: '#10B981'
      },
      {
        label: 'Appels échoués',
        data: failedCalls,
        backgroundColor: '#EF4444'
      }
    ]
  };
}

async function fetchOrdersByTeam(
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('code_agence')
    .eq('type_de_demande', 'Commande')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  const teamOrders = {
    [TEAM_CODES.WHATSAPP]: 0,
    [TEAM_CODES.RESEAUX_SOCIAUX]: 0,
    [TEAM_CODES.CENTRE_APPEL]: 0
  };

  data?.forEach(order => {
    if (order.code_agence && teamOrders.hasOwnProperty(order.code_agence)) {
      teamOrders[order.code_agence as keyof typeof teamOrders]++;
    }
  });

  return {
    labels: ['WhatsApp', 'Réseaux sociaux', 'Centre d\'appel'],
    datasets: [{
      label: 'Commandes',
      data: [
        teamOrders[TEAM_CODES.WHATSAPP],
        teamOrders[TEAM_CODES.RESEAUX_SOCIAUX],
        teamOrders[TEAM_CODES.CENTRE_APPEL]
      ],
      backgroundColor: ['#25D366', '#E4405F', '#1DA1F2']
    }]
  };
}

async function fetchMessagesByTeam(
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('code_agence')
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  const teamMessages = {
    [TEAM_CODES.WHATSAPP]: 0,
    [TEAM_CODES.RESEAUX_SOCIAUX]: 0,
    [TEAM_CODES.CENTRE_APPEL]: 0
  };

  data?.forEach(contact => {
    if (contact.code_agence && teamMessages.hasOwnProperty(contact.code_agence)) {
      teamMessages[contact.code_agence as keyof typeof teamMessages]++;
    }
  });

  return {
    labels: ['WhatsApp', 'Réseaux sociaux', 'Centre d\'appel'],
    datasets: [{
      label: 'Messages',
      data: [
        teamMessages[TEAM_CODES.WHATSAPP],
        teamMessages[TEAM_CODES.RESEAUX_SOCIAUX],
        teamMessages[TEAM_CODES.CENTRE_APPEL]
      ],
      backgroundColor: ['#25D366', '#E4405F', '#1DA1F2']
    }]
  };
}

async function fetchAverageBasketByTeam(
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('orders')
    .select(`
      total,
      contact:contact_id (code_agence)
    `)
    .range(0, 99999);

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Calculate average basket by team
  const teamBaskets = {
    [TEAM_CODES.WHATSAPP]: { total: 0, count: 0 },
    [TEAM_CODES.RESEAUX_SOCIAUX]: { total: 0, count: 0 },
    [TEAM_CODES.CENTRE_APPEL]: { total: 0, count: 0 }
  };

  let globalTotal = 0;
  let globalCount = 0;

  data?.forEach(order => {
    const codeAgence = order.contact?.code_agence;
    const total = order.total || 0;
    
    globalTotal += total;
    globalCount++;

    if (codeAgence && teamBaskets.hasOwnProperty(codeAgence)) {
      teamBaskets[codeAgence as keyof typeof teamBaskets].total += total;
      teamBaskets[codeAgence as keyof typeof teamBaskets].count++;
    }
  });

  const averages = [
    teamBaskets[TEAM_CODES.WHATSAPP].count > 0 
      ? teamBaskets[TEAM_CODES.WHATSAPP].total / teamBaskets[TEAM_CODES.WHATSAPP].count 
      : 0,
    teamBaskets[TEAM_CODES.RESEAUX_SOCIAUX].count > 0 
      ? teamBaskets[TEAM_CODES.RESEAUX_SOCIAUX].total / teamBaskets[TEAM_CODES.RESEAUX_SOCIAUX].count 
      : 0,
    teamBaskets[TEAM_CODES.CENTRE_APPEL].count > 0 
      ? teamBaskets[TEAM_CODES.CENTRE_APPEL].total / teamBaskets[TEAM_CODES.CENTRE_APPEL].count 
      : 0,
    globalCount > 0 ? globalTotal / globalCount : 0
  ];

  return {
    labels: ['WhatsApp', 'Réseaux sociaux', 'Centre d\'appel', 'Global'],
    datasets: [{
      label: 'Panier moyen (DH)',
      data: averages,
      backgroundColor: ['#25D366', '#E4405F', '#1DA1F2', '#6B7280']
    }]
  };
}

async function fetchConversionByAgentBrand(
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  // Join with users table to get agent names
  let query = supabase
    .from('contacts')
    .select(`
      marque, 
      type_de_demande,
      users!inner(name, id)
    `)
    .range(0, 99999);

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by agent and brand using actual names
  const agentBrandStats: { [key: string]: { total: number, orders: number } } = {};

  data?.forEach(contact => {
    const agentName = contact.users?.name || 'Agent inconnu';
    const key = `${agentName} - ${contact.marque || 'Marque inconnue'}`;
    if (!agentBrandStats[key]) {
      agentBrandStats[key] = { total: 0, orders: 0 };
    }
    agentBrandStats[key].total++;
    if (contact.type_de_demande === 'Commande') {
      agentBrandStats[key].orders++;
    }
  });

  // Calculate conversion rates
  const conversionRates = Object.entries(agentBrandStats).map(([key, stats]) => ({
    key,
    rate: stats.total > 0 ? (stats.orders / stats.total) * 100 : 0
  }));

  return {
    labels: conversionRates.map(cr => cr.key),
    datasets: [{
      label: 'Taux de conversion (%)',
      data: conversionRates.map(cr => cr.rate),
      backgroundColor: '#8B5CF6'
    }]
  };
}

// Source Statistics Charts - Updated to use RPC function

async function fetchContactsBySource(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  try {
    // Use RPC function for proper aggregation
    const { data, error } = await supabase.rpc('get_contacts_by_source', {
      start_date: startDate?.toISOString() || null,
      end_date: endDate?.toISOString() || null,
      p_code_agence: userRole === 'superviseur' ? userCodeAgence : null
    });

    if (error) throw error;

    // Take top 15 sources
    const topSources = data?.slice(0, 15) || [];

    return {
      labels: topSources.map(item => item.source),
      datasets: [{
        label: 'Contacts',
        data: topSources.map(item => item.count),
        backgroundColor: [
          '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
          '#FF9F40', '#FF6384', '#C9CBCF', '#FF6384', '#36A2EB', 
          '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40', '#FF6384'
        ],
        borderWidth: 1
      }]
    };
  } catch (error) {
    console.error('Error fetching contacts by source:', error);
    // Return fallback data
    return {
      labels: ['Erreur de chargement'],
      datasets: [{
        label: 'Contacts',
        data: [0],
        backgroundColor: ['#D1D5DB'],
        borderWidth: 1
      }]
    };
  }
}

async function fetchRequestTypesBySource(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('source, type_de_demande')
    .not('source', 'is', null)
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Get top 5 sources by count
  const sourceCounts: { [source: string]: number } = {};
  data?.forEach(contact => {
    const source = contact.source || 'Source inconnue';
    sourceCounts[source] = (sourceCounts[source] || 0) + 1;
  });

  const topSources = Object.entries(sourceCounts)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 5)
    .map(([source]) => source);

  // Group by source and request type
  const sourceRequestTypes: { [source: string]: { [type: string]: number } } = {};
  
  data?.forEach(contact => {
    const source = contact.source || 'Source inconnue';
    const type = contact.type_de_demande || 'Type inconnu';
    
    // Only include top sources
    if (!topSources.includes(source)) return;
    
    if (!sourceRequestTypes[source]) {
      sourceRequestTypes[source] = {};
    }
    
    sourceRequestTypes[source][type] = (sourceRequestTypes[source][type] || 0) + 1;
  });

  // Get unique request types
  const allRequestTypes = new Set<string>();
  Object.values(sourceRequestTypes).forEach(types => {
    Object.keys(types).forEach(type => allRequestTypes.add(type));
  });
  const requestTypes = Array.from(allRequestTypes);

  // Prepare datasets
  const datasets = requestTypes.map(type => {
    return {
      label: type,
      data: topSources.map(source => sourceRequestTypes[source]?.[type] || 0),
      backgroundColor: type === 'Commande' ? '#10B981' : 
                      type === 'Information' ? '#3B82F6' :
                      type === 'Orientation Para' ? '#8B5CF6' :
                      type === 'Sans réponse' ? '#EF4444' :
                      type === 'En attente de réponse' ? '#F59E0B' :
                      type === 'En attente de traitement' ? '#06B6D4' :
                      '#6B7280'
    };
  });

  return {
    labels: topSources,
    datasets
  };
}

async function fetchConversionRateBySource(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('source, type_de_demande')
    .not('source', 'is', null)
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Calculate conversion rate by source
  const sourceStats: { [source: string]: { total: number, orders: number } } = {};
  
  data?.forEach(contact => {
    const source = contact.source || 'Source inconnue';
    
    if (!sourceStats[source]) {
      sourceStats[source] = { total: 0, orders: 0 };
    }
    
    sourceStats[source].total++;
    
    if (contact.type_de_demande === 'Commande') {
      sourceStats[source].orders++;
    }
  });

  // Calculate conversion rates and filter out sources with less than 5 contacts
  const conversionRates = Object.entries(sourceStats)
    .filter(([_, stats]) => stats.total >= 5) // Only include sources with at least 5 contacts
    .map(([source, stats]) => ({
      source,
      rate: stats.total > 0 ? (stats.orders / stats.total) * 100 : 0,
      total: stats.total
    }))
    .sort((a, b) => b.rate - a.rate) // Sort by conversion rate (descending)
    .slice(0, 10); // Take top 10

  // Format rates to have exactly 2 decimal places
  const formattedRates = conversionRates.map(item => ({
    ...item,
    rate: parseFloat(item.rate.toFixed(2))
  }));

  return {
    labels: formattedRates.map(cr => cr.source),
    datasets: [{
      label: 'Taux de conversion (%)',
      data: formattedRates.map(cr => cr.rate),
      backgroundColor: '#8B5CF6'
    }]
  };
}

async function fetchOrdersBySource(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('contacts')
    .select('source')
    .eq('type_de_demande', 'Commande')
    .not('source', 'is', null)
    .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform
    .range(0, 99999);

  // Apply role-based filtering
  if (userRole === 'superviseur' && userCodeAgence) {
    query = query.eq('code_agence', userCodeAgence);
  }

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Group by source
  const sourceCounts: { [source: string]: number } = {};
  data?.forEach(order => {
    const source = order.source || 'Source inconnue';
    sourceCounts[source] = (sourceCounts[source] || 0) + 1;
  });

  // Sort by count (descending) and take top 10
  const sortedSources = Object.entries(sourceCounts)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 10);

  return {
    labels: sortedSources.map(([source]) => source),
    datasets: [{
      label: 'Commandes',
      data: sortedSources.map(([_, count]) => count),
      backgroundColor: '#10B981'
    }]
  };
}

async function fetchAverageBasketBySource(
  userRole: string, 
  userId?: string, 
  userCodeAgence?: string,
  startDate?: Date | null,
  endDate?: Date | null
): Promise<DashboardData> {
  let query = supabase
    .from('orders')
    .select(`
      total,
      contact:contact_id (source)
    `)
    .not('contact.plateforme', 'eq', 'Clients') // Exclude orders from 'Clients' platform
    .range(0, 99999);

  // Apply date filtering if provided
  if (startDate && endDate) {
    query = query.gte('created_at', startDate.toISOString())
                .lte('created_at', endDate.toISOString());
  }

  const { data, error } = await query;
  if (error) throw error;

  // Calculate average basket by source
  const sourceBaskets: { [source: string]: { total: number, count: number } } = {};
  
  data?.forEach(order => {
    const source = order.contact?.source || 'Source inconnue';
    const total = order.total || 0;
    
    if (!sourceBaskets[source]) {
      sourceBaskets[source] = { total: 0, count: 0 };
    }
    
    sourceBaskets[source].total += total;
    sourceBaskets[source].count++;
  });

  // Calculate averages and filter out sources with less than 3 orders
  const averages = Object.entries(sourceBaskets)
    .filter(([_, stats]) => stats.count >= 3) // Only include sources with at least 3 orders
    .map(([source, stats]) => ({
      source,
      average: stats.count > 0 ? parseFloat((stats.total / stats.count).toFixed(2)) : 0,
      count: stats.count
    }))
    .sort((a, b) => b.average - a.average) // Sort by average (descending)
    .slice(0, 10); // Take top 10

  return {
    labels: averages.map(a => a.source),
    datasets: [{
      label: 'Panier moyen (DH)',
      data: averages.map(a => a.average),
      backgroundColor: '#F59E0B'
    }]
  };
}