import React, { useState, useEffect, useMemo } from 'react';
import { Search, RefreshCw, Clock, ShoppingBag, Package, Download, Calendar, XCircle, MessageSquare, Trash2 } from 'lucide-react';
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
  const { orders, loading, fetchOrders, updateOrderStatus, cancelOrder, getFilteredOrdersByTeam, deleteOrder } = useOrderStore();
  
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
          const exportDate = new Date(order.exportedAt);
          if (!maxExportedAt || exportDate > maxExportedAt) {
            maxExportedAt = exportDate;
          }
        }
      });

      if (maxExportedAt) {
        const ordersFromLastExport = orders.filter(order => {
          if (!order.exportedAt) return false;
          return new Date(order.exportedAt).getTime() === maxExportedAt!.getTime();
        });
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
      return teamFilteredOrders.filter(order => order.codeAgence === user.codeAgence);
    } else {
      return teamFilteredOrders.filter(order => order.agentId === user?.id);
    }
  }, [teamFilteredOrders, user]);
    
  const filteredOrders = userOrders.filter(order => {
    const matchesSearch = order.nom.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          order.orderNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          (order.ville || '').toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter ? order.status === statusFilter : true;
    
    const matchesDateRange = !dateFilter.startDate || !dateFilter.endDate || 
                           (new Date(order.createdAt) >= dateFilter.startDate && 
                            new Date(order.createdAt) <= dateFilter.endDate);
    
    return matchesSearch && matchesStatus && matchesDateRange;
  });
  
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
    updateOrderStatus(order.id, order.orderExternalId || '', newStatus);
    setSelectedOrder(null);
  };

  const handleCancelOrder = (order: Order) => {
    setCancellingOrder(order);
    setCancellationNote('');
  };

  const confirmCancelOrder = (order: Order) => {
    if (cancellationNote.trim()) {
      cancelOrder(order.id, order.orderExternalId || '', cancellationNote);
      setCancellingOrder(null);
      setCancellationNote('');
    }
  };

  const handleExport = async () => {
    try {
      let ordersToExport = getFilteredOrdersByTeam(user?.role || 'agent', user?.codeAgence)
        .filter(order => order.status === 'Confirmée' && !order.exportedAt);

      if (ordersToExport.length === 0) {
        toast.error('Aucune commande confirmée à exporter.');
        return;
      }

      if (exportStartDate || exportEndDate) {
        ordersToExport = ordersToExport.filter(order => {
          const orderDate = new Date(order.createdAt);
          return (!exportStartDate || orderDate >= exportStartDate) &&
                (!exportEndDate || orderDate <= exportEndDate);
        });
      }

      if (ordersToExport.length === 0) {
        toast.error('Aucune commande confirmée à exporter dans la plage de dates sélectionnée.');
        return;
      }

      const result = await exportOrders(ordersToExport, exportStartDate || undefined, exportEndDate || undefined);
      if (result.success) {
        toast.success(result.message);
        setShowExportFilters(false);
        await fetchOrders();
      } else {
        toast.error(result.message);
      }
    } catch (error: any) {
      console.error('❌ Export error:', error);
      toast.error(`Erreur lors de l'export: ${error.message || 'Erreur inconnue'}`);
    }
  };

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
    
    setDeletingOrderId(order.id);
    try {
      await deleteOrder(order.id);
    } catch (error: any) {
      console.error('❌ Deletion failed:', error);
    } finally {
      setDeletingOrderId(null);
    }
  };

  const getStatusColor = (status: OrderStatus) => {
    switch (status) {
      case 'À confirmer': return 'bg-warning-100 text-warning-800';
      case 'Confirmée': return 'bg-blue-100 text-blue-800';
      case 'Prête à être livrée': return 'bg-primary-100 text-primary-800';
      case 'Livrée': return 'bg-success-100 text-success-800';
      case 'Retournée': return 'bg-red-100 text-red-800';
      case 'Annulée': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

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
      <div className="py-6 text-center">
        <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
        <p className="mt-2 text-secondary-600">Chargement des commandes...</p>
      </div>
    );
  }

  return (
    <div className="py-6">
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 border border-secondary-100">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2">
              Gestion des Commandes
              {user?.role === 'superviseur' && user?.codeAgence && (
                <span className="text-lg font-normal text-secondary-600 ml-2">
                  - {getTeamName(user.codeAgence)}
                </span>
              )}
            </h1>
            <p className="text-secondary-600">
              {filteredOrders.length} commande{filteredOrders.length > 1 ? 's' : ''} trouvée{filteredOrders.length > 1 ? 's' : ''}
            </p>
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
            <DateFilter onFilterChange={setDateFilter} onReset={resetFilters} />
            {user?.role === 'admin' && (
              <button onClick={() => setShowExportFilters(!showExportFilters)} className="btn btn-primary flex items-center gap-2">
                <Download className="h-4 w-4" /> Exporter
              </button>
            )}
            <button onClick={handleForceRefresh} disabled={refreshing} className="btn btn-secondary flex items-center gap-2">
              <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} /> Actualiser
            </button>
          </div>
        </div>
      </div>
      {/* Table content would go here, simplified for brevity in this response */}
      <div className="bg-white rounded-lg shadow-sm border border-secondary-200 overflow-hidden">
        <table className="min-w-full divide-y divide-secondary-200">
          <thead className="bg-secondary-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Commande</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Client</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Total</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-secondary-500 uppercase tracking-wider">Statut</th>
              <th className="px-6 py-3 text-right text-xs font-medium text-secondary-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-secondary-200">
            {sortedOrders.map((order) => (
              <tr key={order.id} className="hover:bg-secondary-50 transition-colors">
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm font-medium text-secondary-900">#{order.orderNumber}</div>
                  <div className="text-xs text-secondary-500">{formatDateTime(order.createdAt)}</div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <div className="text-sm text-secondary-900">{order.nom}</div>
                  <div className="text-xs text-secondary-500">{order.telephone}</div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-secondary-900">{order.total.toFixed(2)} DH</td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(order.status)}`}>
                    {order.status}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <button onClick={() => handleDeleteOrder(order)} className="text-red-600 hover:text-red-900">
                    <Trash2 className="h-4 w-4" />
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default OrdersTab;
