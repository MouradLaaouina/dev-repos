import React, { useState, useEffect, useMemo } from 'react';
import { Search, RefreshCw, Clock, ShoppingBag, Package, Download, Calendar, XCircle, MessageSquare, Trash2 } from 'lucide-react';
import { supabase } from '../../lib/supabase';
import { useOrderStore } from '../../store/orderStore';
import { useAuthStore } from '../../store/authStore';
import { OrderStatus, Platform, Order, ContactStatus } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import { exportOrders } from '../../utils/export';
import { useNavigate } from 'react-router-dom';
import Flatpickr from 'react-flatpickr';
import 'flatpickr/dist/themes/light.css';
import { French } from 'flatpickr/dist/l10n/fr';
import DateFilter, { DateRange } from '../common/DateFilter';
import { toast } from 'react-hot-toast';

const OrdersTab: React.FC = () => {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const { orders, loading, fetchOrders, updateOrderStatus, cancelOrder, getFilteredOrdersByTeam } = useOrderStore();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<OrderStatus | ''>('');
  const [showExportFilters, setShowExportFilters] = useState(false);
  const [exportStartDate, setExportStartDate] = useState<Date | null>(null);
  const [exportEndDate, setExportEndDate] = useState<Date | null>(null);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [cancellingOrder, setCancellingOrder] = useState<Order | null>(null);
  const [deletingOrderId, setDeletingOrderId] = useState<string | null>(null);
  const [cancellationNote, setCancellationNote] = useState('');
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });
  const [lastExportSummary, setLastExportSummary] = useState<{
    date: Date | null;
    numberOfOrders: number;
    numberOfClients: number;
  } | null>(null);

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);
  
  // Calculate last export summary
  useEffect(() => {
    if (orders.length > 0) {
      let maxExportedAt: Date | null = null;
      orders.forEach(order => {
        if (order.exportedAt) {
          if (!maxExportedAt || order.exportedAt > maxExportedAt) {
            maxExportedAt = order.exportedAt;
          }
        }
      });

      if (maxExportedAt) {
        const ordersFromLastExport = orders.filter(order => 
          order.exportedAt && order.exportedAt.getTime() === maxExportedAt.getTime()
        );
        const uniqueClientIds = new Set<string>();
        ordersFromLastExport.forEach(order => {
          uniqueClientIds.add(order.id);
        });
        setLastExportSummary({
          date: maxExportedAt,
          numberOfOrders: ordersFromLastExport.length,
          numberOfClients: uniqueClientIds.size,
        });
      } else {
        setLastExportSummary(null);
      }
    } else {
      setLastExportSummary(null);
    }
  }, [orders]);
  
  // Apply team-based platform filtering
  const teamFilteredOrders = getFilteredOrdersByTeam(user?.role || 'agent', user?.codeAgence);
  
  // Get orders based on user role and team platform access
  const userOrders = useMemo(() => {
    if (user?.role === 'admin') {
      return teamFilteredOrders;
    } else if (user?.role === 'superviseur') {
      // Supervisors see ALL orders from their team (all agents in their team)
      const supervisorOrders = teamFilteredOrders.filter(order => {
        const matches = order.codeAgence === user.codeAgence;
        return matches;
      });
      return supervisorOrders;
    } else {
      // Regular agents only see their own orders
      return teamFilteredOrders.filter(order => order.agentId === user?.id);
    }
  }, [teamFilteredOrders, user]);
    
  // Filtered orders with date filter
  const filteredOrders = userOrders.filter(order => {
    const matchesSearch = order.nom.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          order.orderNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          (order.ville || '').toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter ? order.status === statusFilter : true;
    
    // Date range filter from DateFilter component
    const matchesDateRange = !dateFilter.startDate || !dateFilter.endDate || 
                           (new Date(order.createdAt) >= dateFilter.startDate && 
                            new Date(order.createdAt) <= dateFilter.endDate);
    
    return matchesSearch && matchesStatus && matchesDateRange;
  });
  
  // Sort by date (newest first)
  const sortedOrders = [...filteredOrders].sort(
    (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  );
  
  const resetFilters = () => {
    setSearchTerm('');
    setStatusFilter('');
    setDateFilter({
      startDate: null,
      endDate: null,
      label: 'Toutes les dates'
    });
  };

  const handleStatusChange = (order: Order) => {
    setSelectedOrder(order);
  };

  const confirmStatusChange = (order: Order, newStatus: OrderStatus) => {
    updateOrderStatus(order.id, order.orderSupabaseId, newStatus);
    setSelectedOrder(null);
  };

  const handleCancelOrder = (order: Order) => {
    setCancellingOrder(order);
    setCancellationNote('');
  };

  const confirmCancelOrder = (order: Order) => {
    if (cancellationNote.trim()) {
      cancelOrder(order.id, order.orderSupabaseId, cancellationNote);
      setCancellingOrder(null);
      setCancellationNote('');
    }
  };

  const handleExport = async () => {
    try {
      // Get all relevant orders for the current user's role and team
      let ordersToExport = getFilteredOrdersByTeam(user?.role || 'agent', user?.codeAgence)
        .filter(order => {
          // Filter only confirmed orders that haven't been exported yet
          return order.status === 'Confirmée' && !order.exportedAt;
        });

      // If no orders match the filter criteria
      if (ordersToExport.length === 0) {
        toast.error('Aucune commande confirmée à exporter.');
        return;
      }

      // Log the number of orders by team code for debugging
      const teamCounts = ordersToExport.reduce((acc, order) => {
        const teamCode = order.codeAgence || 'unknown';
        acc[teamCode] = (acc[teamCode] || 0) + 1;
        return acc;
      }, {} as Record<string, number>);
      
      console.log('📊 Orders to export by team:', teamCounts);
      
      // Show detailed feedback about orders being exported
      toast(`Préparation de ${ordersToExport.length} commande(s) pour export.`);

      // Apply the date range filters from the export modal
      if (exportStartDate || exportEndDate) {
        ordersToExport = ordersToExport.filter(order => {
          const orderDate = new Date(order.createdAt);
          return (!exportStartDate || orderDate >= exportStartDate) &&
                (!exportEndDate || orderDate <= exportEndDate);
        });
      }

      // If no orders match the date range filter criteria
      if (ordersToExport.length === 0) {
        toast.error('Aucune commande confirmée à exporter dans la plage de dates sélectionnée.');
        return;
      }

      // After date filtering, show detailed breakdown by team
      const filteredTeamCounts = ordersToExport.reduce((acc, order) => {
        const teamCode = order.codeAgence || 'unknown';
        acc[teamCode] = (acc[teamCode] || 0) + 1;
        return acc;
      }, {} as Record<string, number>);
      
      console.log('📊 Orders to export after date filtering by team:', filteredTeamCounts);
      
      // Show detailed feedback about orders being exported after date filtering
      const teamBreakdown = Object.entries(filteredTeamCounts)
        .map(([code, count]) => `${code}: ${count}`)
        .join(', ');
      
      toast(`Export de ${ordersToExport.length} commande(s) après filtrage par date. Répartition: ${teamBreakdown}`);

      // Proceed with the export if there are matching orders
      console.log('🔄 Starting export process with', ordersToExport.length, 'orders');
      
      // Call the export function from utils/export.ts
      const result = await exportOrders(ordersToExport, exportStartDate || undefined, exportEndDate || undefined);
      console.log('✅ Export completed:', result);
      
      toast.success(`${ordersToExport.length} commande(s) exportée(s) avec succès!`);
      setShowExportFilters(false);
      
      // Refresh orders to update exported status
      await fetchOrders();
    } catch (error) {
      console.error('❌ Export error:', error);
      toast.error(`Erreur lors de l'export: ${error.message || 'Erreur inconnue'}`);
    }
  };

  // Manual refresh with loading state
  const handleForceRefresh = async () => {
    if (refreshing) return;
    
    setRefreshing(true);
    try {
      await fetchOrders();
    } finally {
      setRefreshing(false);
    }
  };

  const handleDeleteOrder = async (order: Order) => {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer définitivement la commande ${order.orderNumber} ? Cette action est irréversible.`)) {
      return;
    }
    
    console.log('🗑️ Starting deletion process for order:', {
      orderId: order.id,
      orderSupabaseId: order.orderSupabaseId,
      orderNumber: order.orderNumber
    });
    
    setDeletingOrderId(order.id);
    try {
      // Step 1: Delete order items
      console.log('🗑️ Step 1: Deleting order items for order_id:', order.orderSupabaseId);
      
      const { data: itemsDeleted, error: deleteItemsError, count: deletedItemsCount } = await supabase
        .from('order_items')
        .delete()
        .eq('order_id', order.orderSupabaseId)
        .select();
        
      if (deleteItemsError) {
        console.error('❌ Error deleting order items:', deleteItemsError);
        toast.error(`Erreur lors de la suppression des produits: ${deleteItemsError.message}`);
        throw deleteItemsError;
      }
      
      console.log(`✅ Successfully deleted ${deletedItemsCount || 0} order items:`, itemsDeleted);
      
      // Step 2: Delete the order itself
      console.log('🗑️ Step 2: Deleting order record with ID:', order.orderSupabaseId);
      
      const { data: orderDeleted, error: deleteOrderError } = await supabase
        .from('orders')
        .delete()
        .eq('id', order.orderSupabaseId)
        .select();
        
      if (deleteOrderError) {
        console.error('❌ Error deleting order record:', deleteOrderError);
        toast.error(`Erreur lors de la suppression de la commande: ${deleteOrderError.message}`);
        throw deleteOrderError;
      }
      
      // Check if any rows were actually deleted
      if (!orderDeleted || orderDeleted.length === 0) {
        console.error('❌ No order record was deleted - order may not exist or access denied');
        toast.error('Impossible de supprimer la commande - vérifiez vos permissions');
        throw new Error('No order record was deleted');
      }
      
      console.log('✅ Successfully deleted order record:', orderDeleted);
      
      // Step 3: Update the contact status
      console.log('🔄 Step 3: Updating contact status for contact ID:', order.id);
      
      // Get valid status values from the database to ensure we use a valid one
      const { data: validStatuses, error: statusError } = await supabase
        .from('contacts')
        .select('status')
        .limit(1);
        
      if (statusError) {
        console.error('❌ Error fetching valid statuses:', statusError);
      }
      
      // Use a status we know is valid based on the database schema
      const validStatus: ContactStatus = 'À confirmer';
      
      console.log('🔍 Using valid status for contact update:', validStatus);
      
      const { data: contactUpdated, error: updateContactError } = await supabase
        .from('contacts')
        .update({
          status: validStatus,
          type_de_demande: 'Information', // Reset type
          updated_at: new Date().toISOString()
        })
        .eq('id', order.id)
        .select();
        
      if (updateContactError) {
        console.error('❌ Error updating contact status:', updateContactError);
        toast.error(`Erreur lors de la mise à jour du contact: ${updateContactError.message}`);
        // We'll continue even if contact update fails
      } else {
        console.log('✅ Successfully updated contact status:', contactUpdated);
      }
      
      // Step 4: Verify the order is gone
      console.log('🔍 Verifying order deletion...');
      const { data: checkOrder, error: checkError } = await supabase
        .from('orders')
        .select('id')
        .eq('id', order.orderSupabaseId);
        
      if (checkError) {
        console.error('❌ Error checking order deletion:', checkError);
      } else {
        console.log('🔍 Order check result:', checkOrder);
        if (checkOrder && checkOrder.length > 0) {
          console.error('⚠️ Order still exists in database after deletion!');
          toast.error('La commande existe toujours dans la base de données après suppression.');
        } else {
          console.log('✅ Order successfully deleted from database');
        }
      }
      
      // Step 5: Refresh orders list
      console.log('🔄 Refreshing orders list...');
      await fetchOrders();
      
      toast.success('✅ Commande supprimée avec succès');
      console.log('✅ Order deletion process completed successfully');
      
    } catch (error) {
      console.error('❌ Order deletion failed:', error);
      toast.error(`Erreur lors de la suppression: ${error.message || 'Erreur inconnue'}`);
    } finally {
      setDeletingOrderId(null);
    }
  };

  // Handle date filter changes
  const handleDateFilterChange = (newDateRange: DateRange) => {
    setDateFilter(newDateRange);
  };

  // Get status colors for reduced status options
  const getStatusColor = (status: OrderStatus) => {
    switch (status) {
      case 'À confirmer':
        return 'bg-warning-100 text-warning-800';
      case 'Confirmée':
        return 'bg-blue-100 text-blue-800';
      case 'Prête à être livrée':
        return 'bg-primary-100 text-primary-800';
      case 'Livrée':
        return 'bg-success-100 text-success-800';
      case 'Retournée':
        return 'bg-red-100 text-red-800';
      case 'Annulée':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  // Count orders by reduced status options
  const statusCounts = {
    '': userOrders.length,
    'À confirmer': user?.role === 'superviseur' 
      ? userOrders.filter(o => o.status === 'À confirmer').length
      : userOrders.filter(o => o.status === 'À confirmer').length,
    'Confirmée': userOrders.filter(o => o.status === 'Confirmée').length,
    'Prête à être livrée': userOrders.filter(o => o.status === 'Prête à être livrée').length,
    'Livrée': userOrders.filter(o => o.status === 'Livrée').length,
    'Retournée': userOrders.filter(o => o.status === 'Retournée').length,
    'Annulée': userOrders.filter(o => o.status === 'Annulée').length,
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

  if (loading) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-secondary-600">Chargement des commandes...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      {/* Header with gradient banner */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 border border-secondary-100">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2">
              Gestion des Commandes
              {/* Show team context for supervisors */}
              {user?.role === 'superviseur' && user?.codeAgence && (
                <span className="text-lg font-normal text-secondary-600 ml-2">
                  - {getTeamName(user.codeAgence)}
                </span>
              )}
            </h1>
            <p className="text-secondary-600">
              {filteredOrders.length} commande{filteredOrders.length > 1 ? 's' : ''} trouvée{filteredOrders.length > 1 ? 's' : ''}
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
                <p className="text-blue-700 text-sm">
                  🔍 Données affichées: Toutes les commandes de votre équipe (tous agents confondus)
                </p>
                <p className="text-blue-700 text-sm">
                  📊 Total commandes équipe: <span className="font-semibold">{userOrders.length}</span>
                </p>
              </div>
            )}
          </div>
          
          <div className="w-full md:w-auto flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
            <div className="relative">
              <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <Search className="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                placeholder="Rechercher..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input pl-10 w-full sm:w-60 bg-white/80 backdrop-blur-sm"
              />
            </div>
            
            <DateFilter 
              onFilterChange={handleDateFilterChange}
              onReset={() => setDateFilter({
                startDate: null,
                endDate: null,
                label: 'Toutes les dates'
              })}
            />

            {user?.role === 'admin' && (
              <button
                onClick={() => setShowExportFilters(!showExportFilters)}
                className="btn btn-primary flex items-center gap-2 bg-primary-600/90 hover:bg-primary-600"
              >
                <Download className="h-4 w-4" />
                Exporter
              </button>
            )}

            <button
              onClick={handleForceRefresh}
              disabled={refreshing}
              className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white disabled:opacity-50"
            >
              <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
              Actualiser
            </button>

            <button
              onClick={resetFilters}
              className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white"
            >
              <RefreshCw className="h-4 w-4" />
              Réinitialiser
            </button>
          </div>
        </div>
      </div>

      {/* Last Export Summary - Only for admin */}
      {user?.role === 'admin' && lastExportSummary && (
        <div className="bg-white border border-gray-200 rounded-lg p-4 mb-6 shadow-sm">
          <h2 className="text-lg font-semibold text-gray-800 mb-2">Dernière exportation</h2>
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
            <div>
              <p className="text-sm text-gray-500">Date</p>
              <p className="text-lg font-bold text-gray-800">{formatDateTime(lastExportSummary.date!)}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Commandes</p>
              <p className="text-lg font-bold text-gray-800">{lastExportSummary.numberOfOrders}</p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Clients uniques</p>
              <p className="text-lg font-bold text-gray-800">{lastExportSummary.numberOfClients}</p>
            </div>
          </div>
        </div>
      )}

      {/* Export Filters - Only for admin */}
      {showExportFilters && user?.role === 'admin' && (
        <div className="bg-gradient-to-r from-secondary-25 to-primary-25 p-4 rounded-lg shadow-sm mb-6 border border-secondary-100 animate-slide-up">
          <h3 className="font-medium text-secondary-700 mb-3">Filtres d'exportation</h3>
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
              <label className="label text-secondary-700">Date et heure début</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <Calendar className="h-5 w-5 text-gray-400" />
                </div>
                <Flatpickr
                  value={exportStartDate || undefined}
                  onChange={([date]) => setExportStartDate(date)}
                  options={{
                    locale: French,
                    dateFormat: "d/m/Y H:i",
                    enableTime: true,
                    time_24hr: true,
                    maxDate: exportEndDate || undefined
                  }}
                  className="input pl-10 bg-white/90"
                  placeholder="Sélectionner date et heure"
                />
              </div>
            </div>

            <div>
              <label className="label text-secondary-700">Date et heure fin</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <Calendar className="h-5 w-5 text-gray-400" />
                </div>
                <Flatpickr
                  value={exportEndDate || undefined}
                  onChange={([date]) => setExportEndDate(date)}
                  options={{
                    locale: French,
                    dateFormat: "d/m/Y H:i",
                    enableTime: true,
                    time_24hr: true,
                    minDate: exportStartDate || undefined
                  }}
                  className="input pl-10 bg-white/90"
                  placeholder="Sélectionner date et heure"
                />
              </div>
            </div>
          </div>
          <div className="flex gap-2">
            <button
              onClick={handleExport}
              className="btn btn-primary flex items-center gap-2"
            >
              <Download className="h-4 w-4" />
              Exporter les données filtrées
            </button>
            <button
              onClick={() => {
                setExportStartDate(null);
                setExportEndDate(null);
              }}
              className="btn btn-outline"
            >
              Réinitialiser
            </button>
          </div>
        </div>
      )}

      {/* Status Filter Tabs with reduced options */}
      <div className="mb-6">
        <h3 className="text-sm font-medium text-secondary-700 mb-3">Filtrer par statut</h3>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setStatusFilter('')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === '' 
                ? 'bg-primary-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Toutes ({statusCounts['']})
          </button>
          <button
            onClick={() => setStatusFilter('À confirmer')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === 'À confirmer' 
                ? 'bg-warning-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            À confirmer ({statusCounts['À confirmer']})
          </button>
          <button
            onClick={() => setStatusFilter('Confirmée')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === 'Confirmée' 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Confirmée ({statusCounts['Confirmée']})
          </button>
          <button
            onClick={() => setStatusFilter('Prête à être livrée')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === 'Prête à être livrée' 
                ? 'bg-primary-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Prête ({statusCounts['Prête à être livrée']})
          </button>
          <button
            onClick={() => setStatusFilter('Livrée')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === 'Livrée' 
                ? 'bg-success-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Livrée ({statusCounts['Livrée']})
          </button>
          <button
            onClick={() => setStatusFilter('Retournée')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === 'Retournée' 
                ? 'bg-red-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Retournée ({statusCounts['Retournée']})
          </button>
          <button
            onClick={() => setStatusFilter('Annulée')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              statusFilter === 'Annulée' 
                ? 'bg-gray-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Annulée ({statusCounts['Annulée']})
          </button>
        </div>
      </div>
      
      {sortedOrders.length === 0 ? (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <div className="text-secondary-500 mb-2">
            Aucune commande trouvée
          </div>
          <p className="text-sm text-secondary-400">
            {searchTerm || statusFilter || (dateFilter.startDate && dateFilter.endDate) ? 
              "Essayez de modifier vos critères de recherche" : 
              "Aucune commande disponible pour le moment"}
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4">
          {sortedOrders.map(order => (
            <div key={order.id} className="card card-hoverable border border-gray-100">
              <div className="flex flex-col sm:flex-row sm:items-center justify-between mb-4">
                <div className="flex flex-col sm:flex-row sm:items-center">
                  <div className="mb-2 sm:mb-0">
                    <span className={`badge ${getStatusColor(order.status)}`}>
                      {order.status}
                    </span>
                  </div>
                  <h3 className="text-lg font-semibold sm:ml-3 text-secondary-800">
                    {order.orderNumber} - {order.nom}
                  </h3>
                  <div className="flex items-center mt-1 sm:mt-0 sm:ml-3 text-secondary-500 text-sm">
                    <Clock className="h-4 w-4 mr-1" />
                    <span>{formatDateTime(new Date(order.createdAt))}</span>
                  </div>
                </div>
                
                {/* Edit Button */}
                <div className="mt-2 sm:mt-0">
                  <div className="flex gap-2">
                    <button
                      onClick={() => handleDeleteOrder(order)}
                      disabled={deletingOrderId === order.id}
                      className="btn btn-danger py-1 px-3 text-sm flex items-center gap-1"
                    >
                      {deletingOrderId === order.id ? (
                        <>
                          <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                          Suppression...
                        </>
                      ) : (
                        <>
                          <Trash2 className="h-4 w-4" />
                          Supprimer
                        </>
                      )}
                    </button>
                  </div>
                </div>
              </div>
              
              <div className="mt-4 pt-4 border-t border-gray-200">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-medium text-secondary-700 mb-2">Informations client</h4>
                    <div className="space-y-2">
                      <p className="text-sm text-secondary-600">Téléphone: <span className="text-secondary-900">{order.telephone}</span></p>
                      {order.telephone2 && (
                        <p className="text-sm text-secondary-600">Téléphone 2: <span className="text-secondary-900">{order.telephone2}</span></p>
                      )}
                      <p className="text-sm text-secondary-600">Ville: <span className="text-secondary-900">{order.ville}</span></p>
                      <p className="text-sm text-secondary-600">Adresse: <span className="text-secondary-900">{order.address}</span></p>
                      <p className="text-sm text-secondary-600">
                        Paiement: <span className="text-secondary-900">{order.paymentMethod}</span>
                        {order.transferNumber && ` (${order.transferNumber})`}
                      </p>
                      {order.codeAgence && (
                        <p className="text-sm text-secondary-600">Code Agence: <span className="text-secondary-900">{order.codeAgence}</span></p>
                      )}
                      {/* Show agent name for supervisors */}
                      {user?.role === 'superviseur' && order.agentName && (
                        <p className="text-sm text-secondary-600">Agent: <span className="text-secondary-900">{order.agentName}</span></p>
                      )}
                    </div>
                  </div>
                  
                  <div>
                    <h4 className="font-medium text-secondary-700 mb-2">Détails de la commande</h4>
                    <div className="space-y-2">
                      {order.items && order.items.length > 0 ? (
                        <>
                          {order.items.map((item, index) => (
                            <div key={index} className="flex justify-between text-sm">
                              <span className="text-secondary-600">
                                {item.brand} - {item.productName} x{item.quantity}
                              </span>
                              <span className="text-secondary-900 font-medium">
                                {(item.unitPrice * item.quantity).toFixed(2)} DH
                              </span>
                            </div>
                          ))}
                        </>
                      ) : (
                        <p className="text-sm text-secondary-500">Aucun article disponible</p>
                      )}
                      <div className="border-t border-gray-200 pt-2 mt-2">
                        <div className="flex justify-between text-sm">
                          <span className="text-secondary-600">Livraison</span>
                          <span className="text-secondary-900 font-medium">{order.shippingCost} DH</span>
                        </div>
                        <div className="flex justify-between text-sm font-semibold mt-1">
                          <span className="text-secondary-700">Total</span>
                          <span className="text-secondary-900">{order.total.toFixed(2)} DH</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                {/* Cancellation Note */}
                {order.status === 'Annulée' && order.cancellationNote && (
                  <div className="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div className="flex items-start gap-2">
                      <MessageSquare className="h-4 w-4 text-red-600 mt-0.5" />
                      <div>
                        <p className="text-sm font-medium text-red-800">Note d'annulation:</p>
                        <p className="text-sm text-red-700">{order.cancellationNote}</p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Confirmation Note */}
                {order.confirmationNote && (
                  <div className="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <div className="flex items-start gap-2">
                      <MessageSquare className="h-4 w-4 text-blue-600 mt-0.5" />
                      <div>
                        <p className="text-sm font-medium text-blue-800">Note de confirmation:</p>
                        <p className="text-sm text-blue-700">{order.confirmationNote}</p>
                        {order.confirmedAt && (
                          <p className="text-xs text-blue-600 mt-1">
                            Confirmé le {formatDateTime(order.confirmedAt)}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                )}

                {/* Tracking Info */}
                {order.trackingNumber && (
                  <div className="mt-4 p-3 bg-purple-50 border border-purple-200 rounded-lg">
                    <div className="flex items-start gap-2">
                      <Package className="h-4 w-4 text-purple-600 mt-0.5" />
                      <div>
                        <p className="text-sm font-medium text-purple-800">
                          Numéro de suivi: {order.trackingNumber}
                        </p>
                        {order.dispatchedAt && (
                          <p className="text-xs text-purple-600 mt-1">
                            Expédié le {formatDateTime(order.dispatchedAt)}
                          </p>
                        )}
                      </div>
                    </div>
                  </div>
                )}

                {/* Export Info */}
                {order.exportedAt && (
                  <div className="mt-4 p-3 bg-green-50 border border-green-200 rounded-lg">
                    <div className="flex items-start gap-2">
                      <Download className="h-4 w-4 text-green-600 mt-0.5" />
                      <div>
                        <p className="text-sm font-medium text-green-800">
                          Exportée le {formatDateTime(order.exportedAt)}
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {/* Cancellation Form */}
                {cancellingOrder?.id === order.id && (
                  <div className="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h4 className="font-medium text-red-800 mb-2">Annuler la commande</h4>
                    <textarea
                      value={cancellationNote}
                      onChange={(e) => setCancellationNote(e.target.value)}
                      placeholder="Raison de l'annulation..."
                      className="w-full p-2 border border-red-300 rounded-md text-sm"
                      rows={3}
                    />
                    <div className="flex gap-2 mt-3">
                      <button
                        onClick={() => confirmCancelOrder(order)}
                        disabled={!cancellationNote.trim()}
                        className="btn btn-danger py-1 px-3 text-sm disabled:opacity-50"
                      >
                        Confirmer l'annulation
                      </button>
                      <button
                        onClick={() => setCancellingOrder(null)}
                        className="btn btn-outline py-1 px-3 text-sm"
                      >
                        Annuler
                      </button>
                    </div>
                  </div>
                )}
                

              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default OrdersTab;