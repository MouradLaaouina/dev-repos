import React, { useMemo, useEffect, useState } from 'react';
import { BarChart2, Users, MessageCircle, Activity, TrendingUp, ShoppingBag, CheckCircle, AlertCircle, Calendar, Target, Clock, Database, RefreshCw } from 'lucide-react';
import { useContactStore } from '../../store/contactStore';
import { useOrderStore } from '../../store/orderStore';
import { useAuthStore } from '../../store/authStore';
import { calculateConversionRate } from '../../utils/helpers';
import { supabase } from '../../lib/supabase';
import DateFilter, { DateRange } from '../common/DateFilter';

const StatsTab: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { contacts, fetchContacts, totalCount, getFilteredContactsByTeam } = useContactStore();
  const { orders, fetchOrders, getFilteredOrdersByTeam } = useOrderStore();
  const [refreshing, setRefreshing] = useState(false);
  const [dbStats, setDbStats] = useState({
    totalContacts: 0,
    totalOrders: 0,
    totalConversations: 0
  });
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });
  const [actualTeamAgentCount, setActualTeamAgentCount] = useState(0);
  const [teamAgentNames, setTeamAgentNames] = useState<string[]>([]);

  // Fetch database statistics using RPC or direct count queries
  const fetchDatabaseStats = async () => {
    try {
      console.log('📊 Fetching database statistics...');
      
      // Get exact counts from database using count queries
      const [contactsCount, ordersCount, conversationsCount] = await Promise.all([
        supabase.from('contacts').select('*', { count: 'exact', head: true }),
        supabase.from('orders').select('*', { count: 'exact', head: true }),
        supabase.from('conversations').select('*', { count: 'exact', head: true })
      ]);

      setDbStats({
        totalContacts: contactsCount.count || 0,
        totalOrders: ordersCount.count || 0,
        totalConversations: conversationsCount.count || 0
      });

      console.log('✅ Database stats fetched:', {
        contacts: contactsCount.count,
        orders: ordersCount.count,
        conversations: conversationsCount.count
      });
    } catch (error) {
      console.error('❌ Error fetching database stats:', error);
    }
  };

  // Fetch actual team agent names from database
  const fetchTeamAgentNames = async () => {
    if (user?.role === 'superviseur' && user?.codeAgence) {
      try {
        console.log(`🔍 Fetching actual agents for team ${user.codeAgence}...`);
        
        const { data, error } = await supabase
          .from('users')
          .select('name')
          .eq('role', 'agent')
          .eq('code_agence', user.codeAgence);
        
        if (error) {
          console.error('❌ Error fetching team agent names:', error);
          return;
        }
        
        const agentNames = data?.map(agent => agent.name) || [];
        console.log(`✅ Found ${agentNames.length} actual agents in team ${user.codeAgence}:`, agentNames);
        
        setTeamAgentNames(agentNames);
        setActualTeamAgentCount(agentNames.length);
      } catch (error) {
        console.error('❌ Error fetching team agent names:', error);
      }
    }
  };

  useEffect(() => {
    fetchContacts(true); // Fetch with exact count
    fetchOrders();
    fetchDatabaseStats();
    fetchTeamAgentNames();
  }, [fetchContacts, fetchOrders, user?.id, user?.role, user?.codeAgence]);
  
  // Apply team-based platform filtering for supervisors
  const teamFilteredContacts = getFilteredContactsByTeam(user?.role || 'agent', user?.codeAgence);
  
  const teamFilteredOrders = useOrderStore.getState().getFilteredOrdersByTeam(user?.role || 'agent', user?.codeAgence);
  
  // For supervisors, show ALL team data (not just their own)
  const filteredContacts = useMemo(() => {
    let contacts = [];
    
    if (user?.role === 'admin') {
      contacts = teamFilteredContacts;
    } else if (user?.role === 'superviseur') {
      // Supervisors see ALL data from their team (all agents in their team)
      contacts = teamFilteredContacts.filter(contact => contact.codeAgence === user?.codeAgence);
    } else {
      // Agents see only their own data
      contacts = teamFilteredContacts.filter(contact => contact.agentId === user?.id);
    }
    
    // Apply date filter if set
    if (dateFilter.startDate && dateFilter.endDate) {
      contacts = contacts.filter(contact => {
        const contactDate = new Date(contact.createdAt);
        return contactDate >= dateFilter.startDate! && contactDate <= dateFilter.endDate!;
      });
    }
    
    return contacts;
  }, [teamFilteredContacts, user, dateFilter]);

  // For supervisors, show ALL team orders (not just their own)
  const filteredOrders = useMemo(() => {
    let orders = [];
    
    if (user?.role === 'admin') {
      orders = teamFilteredOrders;
    } else if (user?.role === 'superviseur') {
      // Supervisors see ALL orders from their team (all agents in their team)
      orders = teamFilteredOrders.filter(order => order.codeAgence === user?.codeAgence);
    } else {
      // Agents see only their own data
      orders = teamFilteredOrders.filter(order => order.agentId === user?.id);
    }
    
    // Apply date filter if set
    if (dateFilter.startDate && dateFilter.endDate) {
      orders = orders.filter(order => {
        const orderDate = new Date(order.createdAt);
        return orderDate >= dateFilter.startDate! && orderDate <= dateFilter.endDate!;
      });
    }
    
    return orders;
  }, [teamFilteredOrders, user, dateFilter]);
  
  // Separate clients and orders
  const clients = useMemo(() => filteredContacts.filter(contact => contact.typeDeDemande !== 'Commande'), [filteredContacts]);
  
  // Calculate stats with exact counts
  const totalContacts = filteredContacts.length;
  const totalClients = clients.length;
  const totalOrders = filteredOrders.length;
  
  // Calculate conversion rate manually to ensure it's working correctly
  const conversionRate = useMemo(() => {
    if (totalContacts === 0) return 0;
    return (totalOrders / totalContacts) * 100;
  }, [totalContacts, totalOrders]);
  
  // Calculate order stats with reduced status options
  const orderStats = useMemo(() => {
    return {
      'À confirmer': filteredOrders.filter(order => order.status === 'À confirmer').length,
      'Confirmée': filteredOrders.filter(order => order.status === 'Confirmée').length,
      'Prête à être livrée': filteredOrders.filter(order => order.status === 'Prête à être livrée').length,
      'Livrée': filteredOrders.filter(order => order.status === 'Livrée').length,
      'Annulée': filteredOrders.filter(order => order.status === 'Annulée').length,
    };
  }, [filteredOrders]);

  // Calculate revenue stats with agent progress
  const revenueStats = useMemo(() => {
    const deliveredOrders = filteredOrders.filter(order => order.status === 'Livrée');
    const totalRevenue = deliveredOrders.reduce((sum, order) => sum + order.total, 0);
    const averageOrderValue = deliveredOrders.length > 0 ? totalRevenue / deliveredOrders.length : 0;
    
    // Calculate progress towards 50,000 DH goal
    const goalAmount = 50000;
    const progressPercentage = Math.min((totalRevenue / goalAmount) * 100, 100);
    
    return {
      totalRevenue,
      averageOrderValue,
      deliveredOrdersCount: deliveredOrders.length,
      goalAmount,
      progressPercentage
    };
  }, [filteredOrders]);
  
  // Calculate ads stats
  const adsContacts = filteredContacts.filter(contact => contact.fromAds);
  const adsPercentage = totalContacts > 0 ? (adsContacts.length / totalContacts) * 100 : 0;
  
  // Get last 7 days contacts
  const last7Days = useMemo(() => {
    const date = new Date();
    date.setDate(date.getDate() - 7);
    return filteredContacts.filter(contact => new Date(contact.createdAt) >= date);
  }, [filteredContacts]);
  
  const recent7DaysCount = last7Days.length;

  // Manual refresh function
  const handleForceRefresh = async () => {
    if (refreshing) return;
    
    setRefreshing(true);
    try {
      console.log('🔄 Manual refresh of all data...');
      await Promise.all([
        fetchContacts(true),
        fetchOrders(),
        fetchDatabaseStats(),
        fetchTeamAgentNames()
      ]);
    } finally {
      setRefreshing(false);
    }
  };

  // Handle date filter changes
  const handleDateFilterChange = (newDateRange: DateRange) => {
    setDateFilter(newDateRange);
  };

  // Reset date filter
  const handleResetDateFilter = () => {
    setDateFilter({
      startDate: null,
      endDate: null,
      label: 'Toutes les dates'
    });
  };

  // Get team name for display
  const getTeamName = (codeAgence?: string) => {
    switch (codeAgence) {
      case '000001': return 'Réseaux sociaux';
      case '000002': return 'Centre d\'appel';
      case '000003': return 'WhatsApp';
      default: return 'Équipe';
    }
  };

  // Get unique agents in the team for supervisors (legacy method - kept for backup)
  const teamAgents = useMemo(() => {
    if (user?.role === 'superviseur') {
      const uniqueAgents = new Set();
      filteredContacts.forEach(contact => {
        if (contact.agentName) {
          uniqueAgents.add(contact.agentName);
        }
      });
      return Array.from(uniqueAgents);
    }
    return [];
  }, [filteredContacts, user?.role]);
  
  return (
    <div className="py-6">
      {/* Header with gradient banner */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-8 border border-secondary-100">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2">
              Tableau de Bord
              {/* Show team context for supervisors */}
              {user?.role === 'superviseur' && user?.codeAgence && (
                <span className="text-lg font-normal text-secondary-600 ml-2">
                  - {getTeamName(user.codeAgence)}
                </span>
              )}
            </h1>
            <p className="text-secondary-600">
              {user?.role === 'superviseur' 
                ? `Vue d'ensemble des performances de votre équipe ${getTeamName(user?.codeAgence)}`
                : 'Vue d\'ensemble de vos performances commerciales'
              }
            </p>
            {/* Show date filter info if active */}
            {dateFilter.startDate && dateFilter.endDate && (
              <div className="mt-2 p-2 bg-blue-100 border border-blue-300 rounded-md inline-block">
                <p className="text-blue-800 text-sm flex items-center gap-2">
                  <Calendar className="h-4 w-4" />
                  <strong>Période: {dateFilter.label}</strong>
                </p>
              </div>
            )}
            {/* Show team info for supervisors */}
            {user?.role === 'superviseur' && (
              <div className="mt-2 p-3 bg-blue-100 border border-blue-300 rounded-md">
                <p className="text-blue-800 text-sm">
                  👥 <strong>Équipe {getTeamName(user?.codeAgence)} ({user?.codeAgence})</strong>
                </p>
                <p className="text-blue-700 text-sm mt-1">
                  📊 Agents actifs: <span className="font-semibold">{actualTeamAgentCount}</span> 
                  {teamAgentNames.length > 0 && actualTeamAgentCount > 0 && actualTeamAgentCount <= 10 && (
                    <span className="ml-2">({teamAgentNames.join(', ')})</span>
                  )}
                </p>
                <p className="text-blue-700 text-sm">
                  🔍 Données affichées: Toutes les données de votre équipe (tous agents confondus)
                </p>
              </div>
            )}
            {/* Show database statistics */}
            <div className="mt-3 flex flex-wrap items-center gap-4 text-sm">
              <div className="flex items-center gap-2 text-secondary-500">
                <Database className="h-4 w-4" />
                <span>Contacts: <span className="font-semibold text-secondary-700">{dbStats.totalContacts.toLocaleString()}</span></span>
              </div>
              <div className="flex items-center gap-2 text-secondary-500">
                <ShoppingBag className="h-4 w-4" />
                <span>Commandes: <span className="font-semibold text-secondary-700">{dbStats.totalOrders.toLocaleString()}</span></span>
              </div>
              <div className="flex items-center gap-2 text-secondary-500">
                <MessageCircle className="h-4 w-4" />
                <span>Messages: <span className="font-semibold text-secondary-700">{dbStats.totalConversations.toLocaleString()}</span></span>
              </div>
              <div className="flex items-center gap-2 text-secondary-500">
                <Activity className="h-4 w-4" />
                <span>Chargés: <span className="font-semibold text-secondary-700">{contacts.length.toLocaleString()}</span></span>
              </div>
              {dbStats.totalContacts > 0 && (
                <div className="flex items-center gap-2 text-secondary-500">
                  <TrendingUp className="h-4 w-4" />
                  <span>Couverture: <span className="font-semibold text-secondary-700">
                    {((contacts.length / dbStats.totalContacts) * 100).toFixed(1)}%
                  </span></span>
                </div>
              )}
            </div>
          </div>

          <div className="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2">
            <DateFilter 
              onFilterChange={handleDateFilterChange}
              onReset={handleResetDateFilter}
            />
            
            <button
              onClick={handleForceRefresh}
              disabled={refreshing}
              className="btn btn-primary flex items-center gap-2 disabled:opacity-50"
            >
              <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
              {refreshing ? 'Actualisation...' : 'Actualiser'}
            </button>
          </div>
        </div>
      </div>
      
      {/* Main stats cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-primary-100 text-primary-700 mr-4">
            <Users className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">
              {user?.role === 'superviseur' ? 'Contacts Équipe' : 'Total Contacts'}
            </p>
            <p className="text-2xl font-bold text-secondary-900">{totalContacts.toLocaleString()}</p>
            <p className="text-xs text-blue-600">sur {dbStats.totalContacts.toLocaleString()} en base</p>
          </div>
        </div>
        
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-secondary-100 text-secondary-700 mr-4">
            <MessageCircle className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Taux de Conversion</p>
            <p className="text-2xl font-bold text-secondary-900">{conversionRate.toFixed(1)}%</p>
            <p className="text-xs text-blue-600">{totalOrders} commandes / {totalContacts} contacts</p>
          </div>
        </div>
        
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-success-100 text-success-700 mr-4">
            <ShoppingBag className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">
              {user?.role === 'superviseur' ? 'Commandes Équipe' : 'Commandes'}
            </p>
            <p className="text-2xl font-bold text-secondary-900">{totalOrders.toLocaleString()}</p>
            <p className="text-xs text-blue-600">sur {dbStats.totalOrders.toLocaleString()} en base</p>
          </div>
        </div>
        
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-warning-100 text-warning-700 mr-4">
            <Activity className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">7 derniers jours</p>
            <p className="text-2xl font-bold text-secondary-900">{recent7DaysCount.toLocaleString()}</p>
          </div>
        </div>
      </div>

      {/* Revenue Stats with Goal Progress */}
      <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-green-100 text-green-700 mr-4">
            <Target className="h-6 w-6" />
          </div>
          <div className="flex-1">
            <p className="text-sm font-medium text-secondary-500">
              {user?.role === 'superviseur' ? 'Chiffre d\'Affaires Équipe' : 'Chiffre d\'Affaires'}
            </p>
            <p className="text-2xl font-bold text-secondary-900">
              {revenueStats.totalRevenue.toFixed(0)} / {revenueStats.goalAmount.toLocaleString()} DH
            </p>
            <div className="w-full bg-gray-200 rounded-full h-2 mt-2">
              <div 
                className="bg-green-600 h-2 rounded-full transition-all duration-300" 
                style={{ width: `${revenueStats.progressPercentage}%` }}
              ></div>
            </div>
            <p className="text-xs text-secondary-500 mt-1">
              {revenueStats.progressPercentage.toFixed(1)}% de l'objectif atteint
            </p>
          </div>
        </div>
        
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-blue-100 text-blue-700 mr-4">
            <BarChart2 className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Panier Moyen</p>
            <p className="text-2xl font-bold text-secondary-900">{revenueStats.averageOrderValue.toFixed(0)} DH</p>
          </div>
        </div>
        
        <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-purple-100 text-purple-700 mr-4">
            <CheckCircle className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Commandes Livrées</p>
            <p className="text-2xl font-bold text-secondary-900">{revenueStats.deliveredOrdersCount.toLocaleString()}</p>
          </div>
        </div>
      </div>
      
      {/* Order Status stats with reduced options */}
      <div className="card border border-gray-100 mb-8">
        <h2 className="text-lg font-semibold text-secondary-900 mb-4">
          {user?.role === 'superviseur' ? 'Statut des Commandes de l\'Équipe' : 'Statut des Commandes'}
          {dateFilter.startDate && dateFilter.endDate && (
            <span className="text-sm font-normal text-secondary-600 ml-2">
              - {dateFilter.label}
            </span>
          )}
        </h2>
        <div className="grid grid-cols-2 gap-4">
          <div className="flex flex-col items-center bg-gradient-to-br from-secondary-25 to-primary-25 p-4 rounded-lg border border-secondary-100">
            <div className="p-2 rounded-full bg-warning-100 text-warning-700 mb-2">
              <Clock className="h-5 w-5" />
            </div>
            <p className="text-sm font-medium text-secondary-500">À Confirmer</p>
            <p className="text-xl font-bold text-secondary-900">{orderStats['À confirmer'].toLocaleString()}</p>
          </div>
          
          <div className="flex flex-col items-center bg-gradient-to-br from-secondary-25 to-primary-25 p-4 rounded-lg border border-secondary-100">
            <div className="p-2 rounded-full bg-primary-100 text-primary-700 mb-2">
              <ShoppingBag className="h-5 w-5" />
            </div>
            <p className="text-sm font-medium text-secondary-500">Prêtes</p>
            <p className="text-xl font-bold text-secondary-900">{orderStats['Prête à être livrée'].toLocaleString()}</p>
          </div>
          
          <div className="flex flex-col items-center bg-gradient-to-br from-secondary-25 to-primary-25 p-4 rounded-lg border border-secondary-100">
            <div className="p-2 rounded-full bg-success-100 text-success-700 mb-2">
              <CheckCircle className="h-5 w-5" />
            </div>
            <p className="text-sm font-medium text-secondary-500">Livrées</p>
            <p className="text-xl font-bold text-secondary-900">{orderStats['Livrée'].toLocaleString()}</p>
          </div>
          
          <div className="flex flex-col items-center bg-gradient-to-br from-secondary-25 to-primary-25 p-4 rounded-lg border border-secondary-100">
            <div className="p-2 rounded-full bg-red-100 text-red-700 mb-2">
              <AlertCircle className="h-5 w-5" />
            </div>
            <p className="text-sm font-medium text-secondary-500">Annulées</p>
            <p className="text-xl font-bold text-secondary-900">{orderStats['Annulée'].toLocaleString()}</p>
          </div>
        </div>
      </div>
      
      {/* Source stats */}
      <div className="card border border-gray-100">
        <h2 className="text-lg font-semibold text-secondary-900 mb-4">
          {user?.role === 'superviseur' ? 'Sources des Contacts de l\'Équipe' : 'Sources des Contacts'}
          {dateFilter.startDate && dateFilter.endDate && (
            <span className="text-sm font-normal text-secondary-600 ml-2">
              - {dateFilter.label}
            </span>
          )}
        </h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <div className="flex items-center justify-between mb-4">
              <div>
                <p className="text-sm font-medium text-secondary-500">Provenant de Publicités</p>
                <p className="text-2xl font-bold text-secondary-900">{adsContacts.length.toLocaleString()}</p>
              </div>
              <p className="text-xl font-semibold text-primary-600">{adsPercentage.toFixed(1)}%</p>
            </div>
            <div className="w-full bg-secondary-200 rounded-full h-3">
              <div 
                className="bg-primary-600 h-3 rounded-full" 
                style={{ width: `${adsPercentage}%` }}
              ></div>
            </div>
          </div>
          
          <div className="flex flex-col">
            <div className="grid grid-cols-2 gap-4">
              <div className="bg-gradient-to-br from-secondary-25 to-primary-25 p-4 rounded-lg border border-secondary-100">
                <p className="text-sm font-medium text-secondary-500">Clients</p>
                <p className="text-xl font-bold text-secondary-900">{totalClients.toLocaleString()}</p>
                <p className="text-xs text-secondary-500 mt-1">
                  {totalContacts > 0 ? ((totalClients / totalContacts) * 100).toFixed(1) : 0}% du total
                </p>
              </div>
              
              <div className="bg-gradient-to-br from-secondary-25 to-primary-25 p-4 rounded-lg border border-secondary-100">
                <p className="text-sm font-medium text-secondary-500">Commandes</p>
                <p className="text-xl font-bold text-secondary-900">{totalOrders.toLocaleString()}</p>
                <p className="text-xs text-secondary-500 mt-1">
                  {totalContacts > 0 ? ((totalOrders / totalContacts) * 100).toFixed(1) : 0}% du total
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default StatsTab;