import React, { useState, useEffect, useMemo } from 'react';
import { Search, RefreshCw, Clock, CheckCircle, XCircle, MessageSquare, User, Phone, Calendar } from 'lucide-react';
import { useOrderStore } from '../../store/orderStore';
import { useAuthStore } from '../../store/authStore';
import { OrderStatus, Platform, Order } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import Flatpickr from 'react-flatpickr';
import 'flatpickr/dist/themes/light.css';
import { French } from 'flatpickr/dist/l10n/fr';
import DateFilter, { DateRange } from '../common/DateFilter';

const ConfirmationTab: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { orders, loading, fetchOrders, confirmOrder, updateOrderStatus, getFilteredOrdersByTeam } = useOrderStore();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<OrderStatus | ''>('');
  const [platformFilter, setPlatformFilter] = useState<Platform | ''>('');
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [confirmationNote, setConfirmationNote] = useState('');
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });

  useEffect(() => {
    fetchOrders();
  }, [fetchOrders]);
  
  // Apply team-based platform filtering
  const teamFilteredOrders = getFilteredOrdersByTeam(user?.role || 'agent', user?.codeAgence);
  
  // Get orders based on user role and team platform access
  const relevantOrders = useMemo(() => {
    // Filter orders that need confirmation or are already confirmed (using reduced status options)
    const statusFiltered = teamFilteredOrders.filter(order => 
      ['À confirmer', 'Confirmée', 'Prête à être livrée', 'Livrée', 'Retournée'].includes(order.status)
    );
    
    if (user?.role === 'admin') {
      return statusFiltered;
    } else if (user?.role === 'superviseur') {
      // Supervisors see ALL orders from their team (all agents in their team)
      return statusFiltered.filter(order => order.codeAgence === user?.codeAgence);
    } else {
      // Regular confirmation team sees all orders
      return statusFiltered;
    }
  }, [teamFilteredOrders, user]);
    
  // Filtered orders
  const filteredOrders = relevantOrders.filter(order => {
    const matchesSearch = order.nom.toLowerCase().includes(searchTerm.toLowerCase()) || 
                          order.orderNumber.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          (order.ville || '').toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesStatus = statusFilter ? order.status === statusFilter : true;
    const matchesPlatform = platformFilter ? order.plateforme === platformFilter : true;
    
    // Date range filter from DateFilter component
    const matchesDateRange = !dateFilter.startDate || !dateFilter.endDate || 
                           (new Date(order.createdAt) >= dateFilter.startDate && 
                            new Date(order.createdAt) <= dateFilter.endDate);
    
    return matchesSearch && matchesStatus && matchesPlatform && matchesDateRange;
  });
  
  // Sort by date (newest first)
  const sortedOrders = [...filteredOrders].sort(
    (a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime()
  );
  
  const resetFilters = () => {
    setSearchTerm('');
    setStatusFilter('');
    setPlatformFilter('');
    setDateFilter({
      startDate: null,
      endDate: null,
      label: 'Toutes les dates'
    });
  };

  const handleConfirmOrder = (order: Order) => {
    if (confirmationNote.trim()) {
      confirmOrder(order.id, order.orderSupabaseId, confirmationNote, user?.id || '');
      setSelectedOrder(null);
      setConfirmationNote('');
    }
  };

  const handleStatusUpdate = (order: Order, status: OrderStatus, note?: string) => {
    updateOrderStatus(order.id, order.orderSupabaseId, status, note, user?.id);
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
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  // Count orders by reduced status options
  const statusCounts = {
    '': relevantOrders.length,
    'À confirmer': relevantOrders.filter(o => o.status === 'À confirmer').length,
    'Confirmée': relevantOrders.filter(o => o.status === 'Confirmée').length,
    'Prête à être livrée': relevantOrders.filter(o => o.status === 'Prête à être livrée').length,
    'Livrée': relevantOrders.filter(o => o.status === 'Livrée').length,
    'Retournée': relevantOrders.filter(o => o.status === 'Retournée').length,
  };

  // Count orders by platform
  const platformCounts = {
    '': relevantOrders.length,
    'Facebook': relevantOrders.filter(o => o.plateforme === 'Facebook').length,
    'Instagram': relevantOrders.filter(o => o.plateforme === 'Instagram').length,
    'WhatsApp': relevantOrders.filter(o => o.plateforme === 'WhatsApp').length,
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

  // Handle date filter changes
  const handleDateFilterChange = (newDateRange: DateRange) => {
    setDateFilter(newDateRange);
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
              {user?.role === 'superviseur' ? 'Confirmation Superviseur' : 'Équipe de Confirmation'}
              {/* Show team context for supervisors */}
              {user?.role === 'superviseur' && user?.codeAgence && (
                <span className="text-lg font-normal text-secondary-600 ml-2">
                  - {getTeamName(user.codeAgence)}
                </span>
              )}
            </h1>
            <p className="text-secondary-600">
              {user?.role === 'superviseur' 
                ? 'Confirmez les commandes et suivez les performances de votre équipe'
                : 'Confirmez les commandes et suivez les performances des agents'
              }
            </p>
            <div className="mt-2 text-sm text-secondary-500">
              {filteredOrders.length} commande{filteredOrders.length > 1 ? 's' : ''} à traiter
            </div>
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
                  📊 Total commandes équipe: <span className="font-semibold">{relevantOrders.length}</span>
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

            <button
              onClick={fetchOrders}
              className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white"
            >
              <RefreshCw className="h-4 w-4" />
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
        </div>
      </div>

      {/* Platform Filter Tabs */}
      <div className="mb-6">
        <h3 className="text-sm font-medium text-secondary-700 mb-3">Filtrer par plateforme</h3>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setPlatformFilter('')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              platformFilter === '' 
                ? 'bg-secondary-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Toutes ({platformCounts['']})
          </button>
          <button
            onClick={() => setPlatformFilter('Facebook')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
              platformFilter === 'Facebook' 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            <div className="w-4 h-4 bg-blue-600 rounded"></div>
            Facebook ({platformCounts['Facebook']})
          </button>
          <button
            onClick={() => setPlatformFilter('Instagram')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
              platformFilter === 'Instagram' 
                ? 'bg-pink-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            <div className="w-4 h-4 bg-pink-600 rounded"></div>
            Instagram ({platformCounts['Instagram']})
          </button>
          <button
            onClick={() => setPlatformFilter('WhatsApp')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
              platformFilter === 'WhatsApp' 
                ? 'bg-green-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            <div className="w-4 h-4 bg-green-600 rounded"></div>
            WhatsApp ({platformCounts['WhatsApp']})
          </button>
        </div>
      </div>
      
      {sortedOrders.length === 0 ? (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <div className="text-secondary-500 mb-2">
            Aucune commande trouvée
          </div>
          <p className="text-sm text-secondary-400">
            {searchTerm || statusFilter || platformFilter || (dateFilter.startDate && dateFilter.endDate) ? 
              "Essayez de modifier vos critères de recherche" : 
              "Aucune commande à confirmer pour le moment"}
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
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                {/* Client Info */}
                <div>
                  <h4 className="font-medium text-secondary-700 mb-2 flex items-center gap-2">
                    <User className="h-4 w-4" />
                    Informations Client
                  </h4>
                  <div className="space-y-2 text-sm">
                    <p className="text-secondary-600">
                      <Phone className="h-3 w-3 inline mr-1" />
                      {order.telephone}
                    </p>
                    {order.telephone2 && (
                      <p className="text-secondary-600">
                        <Phone className="h-3 w-3 inline mr-1" />
                        {order.telephone2}
                      </p>
                    )}
                    <p className="text-secondary-600">Ville: {order.ville}</p>
                    <p className="text-secondary-600">Adresse: {order.address}</p>
                    <p className="text-secondary-600">
                      Paiement: {order.paymentMethod}
                      {order.transferNumber && ` (${order.transferNumber})`}
                    </p>
                  </div>
                </div>
                
                {/* Agent Info */}
                <div>
                  <h4 className="font-medium text-secondary-700 mb-2">Agent Responsable</h4>
                  <div className="space-y-1 text-sm">
                    <p className="text-secondary-600">Nom: <span className="text-secondary-900">{order.agentName || 'N/A'}</span></p>
                    <p className="text-secondary-600">Code: <span className="text-secondary-900">{order.agentCode || 'N/A'}</span></p>
                    <p className="text-secondary-600">Source: <span className="text-secondary-900">{order.source || 'N/A'}</span></p>
                    <p className="text-secondary-600">Plateforme: <span className="text-secondary-900">{order.plateforme}</span></p>
                    {order.fromAds && (
                      <span className="inline-block bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">
                        Publicité
                      </span>
                    )}
                  </div>
                </div>
                
                {/* Order Details */}
                <div>
                  <h4 className="font-medium text-secondary-700 mb-2">Détails Commande</h4>
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

              {/* Confirmation Notes */}
              {order.confirmationNote && (
                <div className="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
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

              {/* Confirmation Form */}
              {selectedOrder?.id === order.id && order.status === 'À confirmer' && (
                <div className="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                  <h4 className="font-medium text-blue-800 mb-2">Confirmer la commande</h4>
                  <textarea
                    value={confirmationNote}
                    onChange={(e) => setConfirmationNote(e.target.value)}
                    placeholder="Note de confirmation (détails vérifiés, modifications, etc.)..."
                    className="w-full p-2 border border-blue-300 rounded-md text-sm"
                    rows={3}
                  />
                  <div className="flex gap-2 mt-3">
                    <button
                      onClick={() => handleConfirmOrder(order)}
                      disabled={!confirmationNote.trim()}
                      className="btn btn-primary py-1 px-3 text-sm disabled:opacity-50"
                    >
                      Confirmer la commande
                    </button>
                    <button
                      onClick={() => setSelectedOrder(null)}
                      className="btn btn-outline py-1 px-3 text-sm"
                    >
                      Annuler
                    </button>
                  </div>
                </div>
              )}
              
              {/* Actions */}
              <div className="pt-4 border-t border-gray-200">
                <h4 className="font-medium text-secondary-700 mb-2">Actions</h4>
                <div className="flex flex-wrap gap-2">
                  {order.status === 'À confirmer' && (
                    <button
                      onClick={() => setSelectedOrder(order)}
                      className="btn btn-primary py-1 px-3 text-sm flex items-center gap-1"
                    >
                      <CheckCircle className="h-4 w-4" />
                      Confirmer
                    </button>
                  )}
                  
                  {/* Updated condition to include both 'Prête à être livrée' and 'Confirmée' statuses */}
                  {(order.status === 'Prête à être livrée' || order.status === 'Confirmée') && (
                    <>
                      <button
                        onClick={() => handleStatusUpdate(order, 'Livrée')}
                        className="btn btn-success py-1 px-3 text-sm flex items-center gap-1"
                      >
                        <CheckCircle className="h-4 w-4" />
                        Marquer comme livrée
                      </button>
                      <button
                        onClick={() => handleStatusUpdate(order, 'Retournée')}
                        className="btn btn-outline py-1 px-3 text-sm text-red-600 border-red-300 hover:bg-red-50 flex items-center gap-1"
                      >
                        <XCircle className="h-4 w-4" />
                        Marquer comme retournée
                      </button>
                    </>
                  )}
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
};

export default ConfirmationTab;