import React, { useMemo, useEffect, useState } from 'react';
import { BarChart2, Users, MessageCircle, Activity, TrendingUp, ShoppingBag, CheckCircle, AlertCircle, Calendar, Target, Clock, Database, RefreshCw } from 'lucide-react';
import { useContactStore } from '../../store/contactStore';
import { useOrderStore } from '../../store/orderStore';
import { useAuthStore } from '../../store/authStore';
import DateFilter, { DateRange } from '../common/DateFilter';

const StatsTab: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { contacts, fetchContacts } = useContactStore();
  const { orders, fetchOrders } = useOrderStore();
  const [refreshing, setRefreshing] = useState(false);
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });

  useEffect(() => {
    fetchContacts();
    fetchOrders();
  }, [fetchContacts, fetchOrders]);
  
  const filteredContacts = useMemo(() => {
    let result = contacts;
    if (user?.role === 'agent') {
      result = result.filter(c => c.agentId === user.id);
    } else if (user?.role === 'superviseur') {
      result = result.filter(c => c.codeAgence === user.codeAgence);
    }
    if (dateFilter.startDate && dateFilter.endDate) {
      result = result.filter(c => {
        const d = new Date(c.createdAt);
        return d >= dateFilter.startDate! && d <= dateFilter.endDate!;
      });
    }
    return result;
  }, [contacts, user, dateFilter]);

  const filteredOrders = useMemo(() => {
    let result = orders;
    if (user?.role === 'agent') {
      result = result.filter(o => o.agentId === user.id);
    } else if (user?.role === 'superviseur') {
      result = result.filter(o => o.codeAgence === user.codeAgence);
    }
    if (dateFilter.startDate && dateFilter.endDate) {
      result = result.filter(o => {
        const d = new Date(o.createdAt);
        return d >= dateFilter.startDate! && d <= dateFilter.endDate!;
      });
    }
    return result;
  }, [orders, user, dateFilter]);

  const conversionRate = useMemo(() => {
    if (filteredContacts.length === 0) return 0;
    return (filteredOrders.length / filteredContacts.length) * 100;
  }, [filteredContacts, filteredOrders]);

  const totalRevenue = useMemo(() => {
    return filteredOrders
      .filter(o => o.status === 'Livrée')
      .reduce((sum, o) => sum + o.total, 0);
  }, [filteredOrders]);

  const handleForceRefresh = async () => {
    setRefreshing(true);
    await Promise.all([fetchContacts(), fetchOrders()]);
    setRefreshing(false);
  };

  return (
    <div className="py-6">
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-8 border border-secondary-100 flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-secondary-800">Tableau de Bord</h1>
          <p className="text-secondary-600">Vue d'ensemble des performances</p>
        </div>
        <div className="flex gap-2">
          <DateFilter onFilterChange={setDateFilter} onReset={() => setDateFilter({ startDate: null, endDate: null, label: 'Toutes les dates' })} />
          <button onClick={handleForceRefresh} disabled={refreshing} className="btn btn-primary flex items-center gap-2">
            <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} /> Actualiser
          </button>
        </div>
      </div>
      
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <StatCard icon={<Users />} label="Contacts" value={filteredContacts.length} />
        <StatCard icon={<MessageCircle />} label="Taux Conversion" value={`${conversionRate.toFixed(1)}%`} />
        <StatCard icon={<ShoppingBag />} label="Commandes" value={filteredOrders.length} />
        <StatCard icon={<TrendingUp />} label="Chiffre d'Affaires" value={`${totalRevenue.toFixed(0)} DH`} />
      </div>
    </div>
  );
};

const StatCard = ({ icon, label, value }: { icon: React.ReactNode, label: string, value: string | number }) => (
  <div className="card flex items-center p-6 bg-white rounded-lg shadow border border-gray-100">
    <div className="p-3 rounded-full bg-primary-100 text-primary-700 mr-4">{icon}</div>
    <div>
      <p className="text-sm font-medium text-secondary-500">{label}</p>
      <p className="text-2xl font-bold text-secondary-900">{value}</p>
    </div>
  </div>
);

export default StatsTab;
