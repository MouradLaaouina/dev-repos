import React, { useState, useEffect } from 'react';
import { BarChart2, Users, MessageCircle, ShoppingBag, TrendingUp, Target, Clock, CheckCircle, AlertCircle, Filter } from 'lucide-react';
import { useContactStore } from '../../store/contactStore';
import { useOrderStore } from '../../store/orderStore';
import { useAuthStore } from '../../store/authStore';
import { RequestType } from '../../types';
import ClientTable from './ClientTable';
import StatsView from './StatsView';

const AgentDashboard: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { contacts, fetchContacts } = useContactStore();
  const { orders, fetchOrders } = useOrderStore();
  const [selectedRequestType, setSelectedRequestType] = useState<RequestType | ''>('');

  useEffect(() => {
    fetchContacts();
    fetchOrders();
  }, [fetchContacts, fetchOrders]);

  // Filter data for current agent only
  const agentContacts = contacts.filter(contact => contact.agentId === user?.id);
  const agentOrders = orders.filter(order => order.agentId === user?.id);
  const agentClients = agentContacts.filter(contact => contact.typeDeDemande !== 'Commande');

  // Filter clients by request type instead of status
  const filteredClients = selectedRequestType 
    ? agentClients.filter(client => client.typeDeDemande === selectedRequestType)
    : agentClients;

  // Calculate stats based on request types
  const stats = {
    totalClients: agentClients.length,
    totalOrders: agentOrders.length,
    totalRevenue: agentOrders
      .filter(order => order.status === 'Livrée')
      .reduce((sum, order) => sum + order.total, 0),
    conversionRate: agentClients.length > 0 
      ? (agentOrders.length / agentClients.length) * 100 
      : 0,
    requestTypeBreakdown: {
      'Information': agentClients.filter(c => c.typeDeDemande === 'Information').length,
      'En attente de traitement': agentClients.filter(c => c.typeDeDemande === 'En attente de traitement').length,
      'Orientation Para': agentClients.filter(c => c.typeDeDemande === 'Orientation Para').length,
      'Sans réponse': agentClients.filter(c => c.typeDeDemande === 'Sans réponse').length,
      'En attente de réponse': agentClients.filter(c => c.typeDeDemande === 'En attente de réponse').length,
      'Annulee': agentClients.filter(c => c.typeDeDemande === 'Annulee').length,
    }
  };

  const requestTypeTabs = [
    { key: '', label: 'Tous', count: agentClients.length, color: 'bg-gray-100 text-gray-800' },
    { key: 'Information', label: 'Information', count: stats.requestTypeBreakdown.Information, color: 'bg-blue-100 text-blue-800' },
    { key: 'En attente de traitement', label: 'En attente', count: stats.requestTypeBreakdown['En attente de traitement'], color: 'bg-yellow-100 text-yellow-800' },
    { key: 'Orientation Para', label: 'Orientation', count: stats.requestTypeBreakdown['Orientation Para'], color: 'bg-purple-100 text-purple-800' },
    { key: 'Sans réponse', label: 'Sans réponse', count: stats.requestTypeBreakdown['Sans réponse'], color: 'bg-red-100 text-red-800' },
    { key: 'En attente de réponse', label: 'En attente réponse', count: stats.requestTypeBreakdown['En attente de réponse'], color: 'bg-orange-100 text-orange-800' },
    { key: 'Annulee', label: 'Annulée', count: stats.requestTypeBreakdown.Annulee, color: 'bg-gray-100 text-gray-800' },
  ];

  return (
    <div className="py-6">
      {/* Header */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 border border-secondary-100">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2 flex items-center gap-3">
              <BarChart2 className="h-8 w-8 text-primary-600" />
              Tableau de Bord Agent
            </h1>
            <p className="text-secondary-600">
              Bienvenue, <span className="font-semibold">{user?.name}</span>
            </p>
            <p className="text-secondary-500 text-sm mt-1">
              Gérez vos clients et suivez vos performances
            </p>
          </div>
          <div className="text-right">
            <div className="text-sm text-secondary-500">
              Rôle: <span className="font-semibold capitalize">{user?.role}</span>
            </div>
            {user?.codeAgence && (
              <div className="text-sm text-secondary-500">
                Équipe: <span className="font-semibold">{user.codeAgence}</span>
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Stats Overview */}
      <StatsView stats={stats} />

      {/* Request Type Filter Tabs */}
      <div className="mb-6">
        <div className="flex items-center gap-3 mb-4">
          <Filter className="h-5 w-5 text-secondary-600" />
          <h3 className="text-lg font-semibold text-secondary-800">Filtrer par type de demande</h3>
        </div>
        <div className="flex flex-wrap gap-2">
          {requestTypeTabs.map((tab) => (
            <button
              key={tab.key}
              onClick={() => setSelectedRequestType(tab.key as RequestType | '')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
                selectedRequestType === tab.key
                  ? 'bg-primary-600 text-white shadow-md'
                  : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
              }`}
            >
              {tab.label} ({tab.count})
            </button>
          ))}
        </div>
      </div>

      {/* Client Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-100">
        <div className="p-6 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-secondary-800 flex items-center gap-2">
            <Users className="h-5 w-5 text-primary-600" />
            Mes Clients
            {selectedRequestType && (
              <span className="text-sm font-normal text-secondary-600">
                - Type: {requestTypeTabs.find(t => t.key === selectedRequestType)?.label}
              </span>
            )}
          </h2>
          <p className="text-secondary-600 text-sm mt-1">
            {filteredClients.length} client{filteredClients.length > 1 ? 's' : ''} 
            {selectedRequestType ? ` avec le type "${requestTypeTabs.find(t => t.key === selectedRequestType)?.label}"` : ''}
          </p>
        </div>
        
        <ClientTable clients={filteredClients} />
      </div>
    </div>
  );
};

export default AgentDashboard;