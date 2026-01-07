import React, { useState, useEffect, useMemo } from 'react';
import { Search, RefreshCw, Clock, CheckCircle, XCircle, MessageSquare, User, Phone, Calendar } from 'lucide-react';
import { useOrderStore } from '../../store/orderStore';
import { useAuthStore } from '../../store/authStore';
import { OrderStatus, Platform, Order } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import DateFilter, { DateRange } from '../common/DateFilter';

const ConfirmationTab: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { orders, loading, fetchOrders, confirmOrder, updateOrderStatus, getFilteredOrdersByTeam } = useOrderStore();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<OrderStatus | ''>('');
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
  
  const relevantOrders = useMemo(() => {
    const teamFiltered = getFilteredOrdersByTeam(user?.role || 'agent', user?.codeAgence);
    return teamFiltered.filter(order => 
      ['À confirmer', 'Confirmée', 'Prête à être livrée', 'Livrée', 'Retournée'].includes(order.status)
    );
  }, [getFilteredOrdersByTeam, user]);
    
  const filteredOrders = relevantOrders.filter(order => {
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

  const handleConfirmOrder = (order: Order) => {
    if (confirmationNote.trim()) {
      confirmOrder(order.id, order.orderExternalId || '', confirmationNote, user?.id || '');
      setSelectedOrder(null);
      setConfirmationNote('');
    }
  };

  if (loading) return <div className="text-center py-10">Chargement...</div>;

  return (
    <div className="py-6">
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 border border-secondary-100 flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-secondary-800">Confirmation des Commandes</h1>
          <p className="text-secondary-600">{filteredOrders.length} commandes à traiter</p>
        </div>
        <div className="flex gap-2">
          <DateFilter onFilterChange={setDateFilter} onReset={() => setDateFilter({ startDate: null, endDate: null, label: 'Toutes les dates' })} />
          <button onClick={fetchOrders} className="btn btn-outline flex items-center gap-2 bg-white/80">
            <RefreshCw className="h-4 w-4" /> Actualiser
          </button>
        </div>
      </div>

      <div className="space-y-4">
        {sortedOrders.map(order => (
          <div key={order.id} className="card p-4 border border-gray-100">
            <div className="flex justify-between items-start">
              <div>
                <h3 className="text-lg font-semibold">{order.orderNumber} - {order.nom}</h3>
                <p className="text-sm text-secondary-500">{formatDateTime(order.createdAt)}</p>
                <div className="mt-2 text-sm">
                  <p><strong>Téléphone:</strong> {order.telephone}</p>
                  <p><strong>Ville:</strong> {order.ville}</p>
                  <p><strong>Total:</strong> {order.total.toFixed(2)} DH</p>
                </div>
              </div>
              <div className="flex flex-col gap-2">
                <span className={`badge px-2 py-1 rounded text-xs font-semibold ${order.status === 'À confirmer' ? 'bg-warning-100 text-warning-800' : 'bg-blue-100 text-blue-800'}`}>
                  {order.status}
                </span>
                {order.status === 'À confirmer' && (
                  <button onClick={() => setSelectedOrder(order)} className="btn btn-primary py-1 px-3 text-sm">Confirmer</button>
                )}
              </div>
            </div>
            {selectedOrder?.id === order.id && (
              <div className="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <textarea
                  value={confirmationNote}
                  onChange={(e) => setConfirmationNote(e.target.value)}
                  placeholder="Note de confirmation..."
                  className="input w-full mb-2"
                />
                <div className="flex justify-end gap-2">
                  <button onClick={() => setSelectedOrder(null)} className="btn btn-secondary text-sm">Annuler</button>
                  <button onClick={() => handleConfirmOrder(order)} className="btn btn-primary text-sm">Valider</button>
                </div>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ConfirmationTab;
