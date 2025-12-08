import React, { useState, useEffect, useMemo } from 'react';
import { Headphones, Phone, MessageCircle, Calendar, Search, Filter, RefreshCw, Star, FileText, Clock, CheckCircle, XCircle, Users, TrendingUp, Target, ShoppingCart, MessageSquare, Eye } from 'lucide-react';
import { useCallCenterStore } from '../../store/callCenterStore';
import { useAuthStore } from '../../store/authStore';
import { useNavigate } from 'react-router-dom';
import { CallCenterLead, CallStatus, CallLog } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import CallFollowUpModal from './CallFollowUpModal';
import LeadDetailsModal from './LeadDetailsModal';
import { Tooltip } from '../common/Tooltip';
import NotesViewer from './NotesViewer';
import toast from 'react-hot-toast';

const CallCenterDashboard: React.FC = () => {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const { 
    leads, 
    callLogs, 
    loading, 
    fetchLeads, 
    loadCallLogs, 
    saveCallLog, 
    getLeadsByStatus, 
    getAgentStats 
  } = useCallCenterStore();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [statusFilter, setStatusFilter] = useState<CallStatus | ''>('');
  const [selectedLead, setSelectedLead] = useState<CallCenterLead | null>(null);
  const [showFollowUpModal, setShowFollowUpModal] = useState(false);
  const [expandedNotes, setExpandedNotes] = useState<string | null>(null);
  const [selectedLog, setSelectedLog] = useState<CallLog | null>(null);
  const [showLeadDetails, setShowLeadDetails] = useState(false);
  const [tooltipContent, setTooltipContent] = useState<string>('');
  const [tooltipPosition, setTooltipPosition] = useState<{ x: number; y: number }>({ x: 0, y: 0 });
  const [showTooltip, setShowTooltip] = useState<boolean>(false);
  const [isCallInitiatedModal, setIsCallInitiatedModal] = useState<boolean>(false);

  // Check if user has access to call center (code_agence 000002 or admin)
  const hasCallCenterAccess = user?.role === 'admin' || user?.codeAgence === '000002';

  useEffect(() => {
    if (hasCallCenterAccess) {
      // Pass user role and code_agence to fetchLeads for proper filtering
      fetchLeads(
        user?.id, 
        user?.role, 
        user?.codeAgence
      );
    }
  }, [hasCallCenterAccess, user?.id, user?.role, user?.codeAgence, fetchLeads]);

  // Get leads based on status filter
  const getFilteredLeads = () => {
    if (statusFilter) {
      // When a status filter is active, only show leads with that status as their MOST RECENT status
      return getLeadsByStatus(statusFilter);
    } else {
      // For "All leads" tab, only show leads that don't have any call logs yet
      // or whose most recent status is not one of the specific statuses we filter by
      return leads.filter(lead => {
        const leadLogs = callLogs[lead.id] || [];
        
        // If there are no logs, include this lead in "All leads"
        if (leadLogs.length === 0) return true;
        
        // If the most recent log has a status that's not one of our filter statuses,
        // include it in "All leads" tab
        const mostRecentStatus = leadLogs[0].callStatus;
        const filterStatuses: CallStatus[] = [
          'À rappeler',
          'Pas intéressé(e)',
          'Ne réponds jamais',
          'Faux numéro',
          'Intéressé(e)',
          'Commande'
        ];
        
        return !filterStatuses.includes(mostRecentStatus);
      });
    }
  };

  // Get filtered leads based on status and search
  const filteredLeads = getFilteredLeads().filter(lead => {
    const matchesSearch = lead.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         lead.phoneNumber.includes(searchTerm) ||
                         lead.commercialAgent.toLowerCase().includes(searchTerm.toLowerCase());
    
    return matchesSearch;
  });

  // Get active agents in the call center team
  const activeAgents = useMemo(() => {
    // Get unique agent names from leads that belong to the current user's team
    const uniqueAgents = new Set<string>();
    leads.forEach(lead => {
      if (lead.assignedTo && lead.assignedName) {
        // Only include agents from the user's team (for supervisors)
        if (user?.role !== 'superviseur' || 
            (lead.assignedTo && lead.assignedName)) {
          uniqueAgents.add(lead.assignedName);
        }
      }
    });
    return Array.from(uniqueAgents);
  }, [leads, user?.role]);

  const handleCall = (phoneNumber: string) => {
    // Trigger MicroSIP call using SIP URI format
    const sipUrl = `sip:${phoneNumber}`;
    window.open(sipUrl, '_blank');
    
    // Find the lead with this phone number
    const lead = leads.find(l => l.phoneNumber === phoneNumber);
    if (lead) {
      setSelectedLead(lead);
      setShowFollowUpModal(true);
      setIsCallInitiatedModal(true); // Mark this as a call-initiated modal
    }
    
    toast.success(`Appel initié vers ${phoneNumber}`);
  };

  const handleWhatsApp = (phoneNumber: string) => {
    // Clean phone number and format for international dialing
    let cleanPhone = phoneNumber.replace(/\D/g, '');
    
    // If the number starts with '0', replace it with '+212'
    if (cleanPhone.startsWith('0')) {
      cleanPhone = '+212' + cleanPhone.substring(1);
    } 
    // If it doesn't have a country code, add +212
    else if (!cleanPhone.startsWith('+')) {
      cleanPhone = '+' + cleanPhone;
    }
    
    const whatsappUrl = `https://wa.me/${cleanPhone}`;
    window.open(whatsappUrl, '_blank');
  };

  const handleOpenForm = (lead: CallCenterLead) => {
    setSelectedLead(lead);
    setShowFollowUpModal(true);
    setIsCallInitiatedModal(false); // This is a manually opened form, not mandatory
    setShowLeadDetails(false);
  };

  const handleCreateOrder = (lead: CallCenterLead) => {
    // First, create a call log entry with "Commande" status
    if (user) {
      const saveLogPromise = saveCallLog({
        leadId: lead.id,
        clientId: lead.contactId || lead.id,
        callStatus: 'Commande',
        satisfactionLevel: 5, // Assume highest satisfaction for direct orders
        interested: 'Oui',
        callDate: new Date(),
        notes: "Commande initiée directement sans formulaire de suivi",
        agentId: user.id
      });
      
      // After saving the log, refresh the leads and call logs to update the UI
      saveLogPromise.then(() => {
        // Refresh the leads and call logs to update the UI
        fetchLeads(user.id, user.role, user.codeAgence);
        loadCallLogs();
        
        // Then navigate to contact form with pre-filled data for creating an order
        navigate('/dashboard/contacts/new', {
          state: {
            nom: lead.name,
            telephone: lead.phoneNumber.replace(/\D/g, ''), // Clean phone number
            plateforme: 'Centre d\'appel', // CHANGE THIS LINE
            message: `Commande suite à appel - Produit: ${lead.productBought}`,
            typeDeDemande: 'Commande',
            ville: 'Casablanca', // Default city
            sexe: 'Femme', // Default
            source: 'CENTRE APPEL',
            commerciale: lead.commercialAgent,
            marque: 'D-WHITE', // Default brand
            fromCallCenter: true,
            leadId: lead.id
          }
        });
      }).catch(error => {
        console.error("Error saving call log before creating order:", error);
        // Continue with order creation even if log fails
        navigate('/dashboard/contacts/new', {
          state: {
            nom: lead.name,
            telephone: lead.phoneNumber.replace(/\D/g, ''),
            plateforme: 'Centre d\'appel', // CHANGE THIS LINE
            message: `Commande suite à appel - Produit: ${lead.productBought}`,
            typeDeDemande: 'Commande',
            ville: 'Casablanca',
            sexe: 'Femme',
            source: 'CENTRE APPEL',
            commerciale: lead.commercialAgent,
            marque: 'D-WHITE',
            fromCallCenter: true,
            leadId: lead.id
          }
        });
      });
    } else {
      // Fallback if no user is logged in
      navigate('/dashboard/contacts/new', {
        state: {
          nom: lead.name,
          telephone: lead.phoneNumber.replace(/\D/g, ''),
          plateforme: 'Centre d\'appel', // CHANGE THIS LINE
          message: `Commande suite à appel - Produit: ${lead.productBought}`,
          typeDeDemande: 'Commande',
          ville: 'Casablanca',
          sexe: 'Femme',
          source: 'CENTRE APPEL',
          commerciale: lead.commercialAgent,
          marque: 'D-WHITE',
          fromCallCenter: true,
          leadId: lead.id
        }
      });
    }
  };

  const handleSaveFollowUp = async (followUpData: any) => {
    if (!selectedLead || !user) return;

    try {
      console.log('🔄 Saving call follow-up for lead:', selectedLead.id);
      console.log('📝 Follow-up data:', followUpData);
      
      const isOrderStatus = followUpData.callStatus === 'Commande';

      await saveCallLog({
        leadId: selectedLead.id,
        clientId: selectedLead.contactId || selectedLead.id, // Use the actual contact ID if available
        callStatus: followUpData.callStatus,
        satisfactionLevel: followUpData.satisfactionLevel,
        interested: followUpData.interested,
        callDate: followUpData.callDate,
        nextCallDate: followUpData.nextCallDate,
        nextCallTime: followUpData.nextCallTime,
        notes: followUpData.notes,
        agentId: user.id
      });

      setShowFollowUpModal(false);

      console.log('✅ Call log saved, refreshing data...');
      
      // Refresh the leads and call logs to update the UI
      await fetchLeads(user.id, user.role, user.codeAgence);
      await loadCallLogs();
      
      // If the status is "Commande", navigate to the contact form to create an order
      if (isOrderStatus) {
        console.log('🛒 Commande status detected, navigating to contact form...');
        
        navigate('/dashboard/contacts/new', {
          state: {
            nom: selectedLead.name,
            telephone: selectedLead.phoneNumber.replace(/\D/g, ''),
            plateforme: 'WhatsApp',
            message: `Commande suite à appel - Produit: ${selectedLead.productBought}`,
            typeDeDemande: 'Commande',
            ville: 'Casablanca',
            sexe: 'Femme',
            source: 'CENTRE APPEL',
            commerciale: selectedLead.commercialAgent,
            marque: selectedLead.brand || 'D-WHITE',
            fromCallCenter: true,
            leadId: selectedLead.id
          }
        });
      }
      
      setSelectedLead(null);
      setIsCallInitiatedModal(false);
    } catch (error) {
      // Error handling is done in the store
    }
  };

  const handleViewLeadDetails = (lead: CallCenterLead) => {
    setSelectedLead(lead);
    setShowLeadDetails(true);
  };

  // Get color based on number of call logs
  const getCallLogColorClass = (logCount: number): string => {
    if (logCount === 0) return 'bg-gray-300'; // No calls yet
    if (logCount === 1) return 'bg-blue-500'; // First call - blue
    if (logCount === 2) return 'bg-green-500'; // Second call - green
    if (logCount === 3) return 'bg-yellow-500'; // Third call - yellow
    return 'bg-red-500'; // Fourth call and beyond - red
  };

  // Show tooltip with call count information
  const handleShowTooltip = (e: React.MouseEvent, logCount: number) => {
    setTooltipContent(`${logCount} appel${logCount > 1 ? 's' : ''} effectué${logCount > 1 ? 's' : ''}`);
    setTooltipPosition({ x: e.clientX, y: e.clientY });
    setShowTooltip(true);
  };

  const handleHideTooltip = () => {
    setShowTooltip(false);
  };

  // Status filter options (excluding "Commande" as requested)
  const statusFilterOptions: CallStatus[] = [
    'À rappeler',
    'Pas intéressé(e)',
    'Ne réponds jamais',
    'Faux numéro',
    'Intéressé(e)',
    'Commande'
  ];

  // Calculate stats for current user
  const stats = user ? getAgentStats(user.id) : {
    totalCalls: 0,
    todayCalls: 0,
    averageSatisfaction: 0,
    ordersConfirmed: 0
  };

  // Count leads by status
  const statusCounts = {
    'all': leads.filter(lead => {
      const leadLogs = callLogs[lead.id] || [];
      
      // For "All" tab, count leads with no logs or whose most recent status is not in our filter list
      if (leadLogs.length === 0) return true;
      
      const mostRecentStatus = leadLogs[0].callStatus;
      return !statusFilterOptions.includes(mostRecentStatus);
    }).length,
    ...statusFilterOptions.reduce((acc, status) => {
      acc[status] = getLeadsByStatus(status).length;
      return acc;
    }, {} as Record<string, number>)
  };

  // Add total leads to stats
  const enhancedStats = {
    ...stats,
    totalLeads: leads.length
  };

  // Toggle expanded notes for a lead
  const toggleNotes = (leadId: string) => {
    if (expandedNotes === leadId) {
      setExpandedNotes(null);
    } else {
      setExpandedNotes(leadId);
    }
  };

  // Access control check
  if (!hasCallCenterAccess) {
    return (
      <div className="py-6">
        <div className="bg-red-50 border border-red-200 rounded-lg p-8 text-center">
          <XCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
          <h2 className="text-xl font-semibold text-red-800 mb-2">
            Accès restreint
          </h2>
          <p className="text-red-700 mb-4">
            Cette section est réservée aux agents du centre d'appel (équipe 000002).
          </p>
          <div className="text-sm text-red-600">
            <p>Votre rôle: <span className="font-medium">{user?.role}</span></p>
            <p>Votre équipe: <span className="font-medium">{user?.codeAgence || 'Non assignée'}</span></p>
          </div>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-secondary-600">Chargement du centre d'appel...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      {/* Header */}
      <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-6 mb-6 border border-blue-200">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-blue-900 mb-2 flex items-center gap-3">
              <Headphones className="h-8 w-8 text-blue-600" />
              Centre d'appel
            </h1>
            <p className="text-blue-700">
              Interface de gestion des appels et suivi des leads assignés
            </p>
            <p className="text-blue-600 text-sm mt-1">
              {filteredLeads.length} lead{filteredLeads.length > 1 ? 's' : ''} assigné{filteredLeads.length > 1 ? 's' : ''}
            </p>
            <div className="mt-3 flex items-center gap-4 text-sm">
              <span className="text-blue-600">
                👤 Agent: <span className="font-semibold text-blue-800">{user?.name}</span>
              </span>
              <span className="text-blue-600">
                🏢 Équipe: <span className="font-semibold text-blue-800">Centre d'appel (000002)</span>
              </span>
            </div>
          </div>
          <div className="text-right">
            <div className="text-sm text-blue-600">
              Accès: <span className="font-semibold text-blue-800">Autorisé</span>
            </div>
            <div className="text-sm text-blue-600">
              Statut: <span className="font-semibold text-blue-800">Actif</span>
            </div>
          </div>
        </div>
      </div>

      {/* Team Information */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6">
        <h3 className="font-medium text-gray-900 mb-3 flex items-center gap-2">
          <Users className="h-5 w-5 text-blue-600" />
          Équipe Centre d'appel (000002)
        </h3>
        <div className="bg-blue-50 p-3 rounded-md border border-blue-100">
          <p className="text-blue-800 text-sm">
            📊 Agents actifs: <span className="font-semibold">{activeAgents.length}</span>
            {activeAgents.length > 0 && activeAgents.length <= 10 && (
              <span className="ml-2">({activeAgents.join(', ')})</span>
            )}
          </p>
          <p className="text-blue-700 text-xs mt-1">
            Les agents sont identifiés par leur nom complet dans le système, pas par leur code.
          </p>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div className="card flex items-center border border-gray-100 hover:border-blue-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-blue-100 text-blue-700 mr-4">
            <Users className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Leads assignés</p>
            <p className="text-2xl font-bold text-secondary-900">{enhancedStats.totalLeads}</p>
          </div>
        </div>

        <div className="card flex items-center border border-gray-100 hover:border-blue-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-indigo-100 text-indigo-700 mr-4">
            <Phone className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Total appels</p>
            <p className="text-2xl font-bold text-secondary-900">{enhancedStats.totalCalls}</p>
          </div>
        </div>

        <div className="card flex items-center border border-gray-100 hover:border-blue-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-green-100 text-green-700 mr-4">
            <Clock className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Appels aujourd'hui</p>
            <p className="text-2xl font-bold text-secondary-900">{enhancedStats.todayCalls}</p>
          </div>
        </div>

        <div className="card flex items-center border border-gray-100 hover:border-blue-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-yellow-100 text-yellow-700 mr-4">
            <Star className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Satisfaction</p>
            <p className="text-2xl font-bold text-secondary-900">{enhancedStats.averageSatisfaction.toFixed(1)}/5</p>
          </div>
        </div>

        <div className="card flex items-center border border-gray-100 hover:border-blue-200 transition-colors duration-200">
          <div className="p-3 rounded-full bg-purple-100 text-purple-700 mr-4">
            <CheckCircle className="h-6 w-6" />
          </div>
          <div>
            <p className="text-sm font-medium text-secondary-500">Commandes</p>
            <p className="text-2xl font-bold text-secondary-900">{enhancedStats.ordersConfirmed}</p>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-4 mb-6">
        <div className="flex flex-col gap-4">
          {/* Search */}
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <Search className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  type="text"
                  placeholder="Rechercher par nom, téléphone ou agent..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="input pl-10 w-full"
                />
              </div>
            </div>
            
            <button
              onClick={() => {
                setSearchTerm('');
                setStatusFilter('');
              }}
              className="btn btn-outline flex items-center gap-2"
            >
              <RefreshCw className="h-4 w-4" />
              Réinitialiser
            </button>
          </div>

          {/* Status Filter Buttons */}
          <div>
            <h3 className="text-sm font-medium text-secondary-700 mb-3">Filtrer par statut d'appel</h3>
            <div className="flex flex-wrap gap-2">
              <button
                onClick={() => setStatusFilter('')}
                className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
                  statusFilter === '' 
                    ? 'bg-primary-600 text-white shadow-md' 
                    : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
                }`}
              >
                Tous les leads ({statusCounts['all']})
              </button>
              {statusFilterOptions.map(status => {
                const count = statusCounts[status] || 0;
                return (
                  <button
                    key={status}
                    onClick={() => setStatusFilter(status)}
                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
                      statusFilter === status 
                        ? 'bg-primary-600 text-white shadow-md' 
                        : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
                    }`}
                  >
                    {status} ({count})
                  </button>
                );
              })}
            </div>
          </div>
        </div>
      </div>

      {/* Leads Table */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div className="p-6 border-b border-gray-200">
          <h2 className="text-lg font-semibold text-secondary-800 flex items-center gap-2">
            <TrendingUp className="h-5 w-5 text-blue-600" />
            Leads assignés ({filteredLeads.length})
            {statusFilter && (
              <span className="text-sm font-normal text-blue-600 ml-2">
                - Statut: {statusFilter}
              </span>
            )}
          </h2>
          <p className="text-sm text-secondary-600 mt-1">
            Utilisez les boutons d'action pour appeler, envoyer un message, remplir le formulaire de suivi ou créer une commande
          </p>
        </div>

        {filteredLeads.length === 0 ? (
          <div className="p-8 text-center">
            <Headphones className="h-12 w-12 text-secondary-400 mx-auto mb-4" />
            <div className="text-secondary-500 mb-2">
              Aucun lead trouvé
            </div>
            <p className="text-sm text-secondary-400">
              {searchTerm || statusFilter ? 
                "Essayez de modifier vos critères de recherche" : 
                "Aucun lead assigné pour le moment"}
            </p>
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date d'achat
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Client
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Téléphone
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Agent commercial
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Produit acheté
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Dernier statut
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {filteredLeads.map((lead) => {
                  const leadLogs = callLogs[lead.id] || [];
                  const lastCallLog = leadLogs[0]; // Most recent call log
                  
                  return (
                    <tr key={lead.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {formatDateTime(lead.purchaseDate)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="text-sm font-medium text-gray-900">{lead.name}</div>
                        <div className="text-sm text-gray-500">{lead.brand}</div>
                        {/* Call log indicator */}
                        {(() => {
                          const logCount = (callLogs[lead.id] || []).length;
                          return (
                            <div 
                              className="mt-1 flex items-center gap-1"
                              onMouseEnter={(e) => handleShowTooltip(e, logCount)}
                              onMouseLeave={handleHideTooltip}
                            >
                              <div 
                                className={`w-3 h-3 rounded-full ${getCallLogColorClass(logCount)}`} 
                              />
                              <span className="text-xs text-gray-500">
                                {logCount === 0 ? 'Jamais appelé' : 
                                 logCount === 1 ? 'Premier appel' : 
                                 logCount === 2 ? 'Deuxième appel' : 
                                 logCount === 3 ? 'Troisième appel' : 
                                 `${logCount} appels`}
                              </span>
                            </div>
                          );
                        })()}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {lead.phoneNumber}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {lead.commercialAgent}
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-900 max-w-xs truncate">
                        {lead.productBought}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {lastCallLog ? (
                          <div>
                            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                              lastCallLog.callStatus === 'À rappeler' ? 'bg-yellow-100 text-yellow-800' :
                              lastCallLog.callStatus === 'Intéressé(e)' ? 'bg-blue-100 text-blue-800' :
                              lastCallLog.callStatus === 'Pas intéressé(e)' ? 'bg-red-100 text-red-800' :
                              lastCallLog.callStatus === 'Ne réponds jamais' ? 'bg-gray-100 text-gray-800' :
                              lastCallLog.callStatus === 'Faux numéro' ? 'bg-orange-100 text-orange-800' :
                              lastCallLog.callStatus === 'Commande' ? 'bg-green-100 text-green-800' :
                              'bg-gray-100 text-gray-800'
                            }`}>
                              {lastCallLog.callStatus}
                            </span>
                            <div className="text-xs text-gray-500 mt-1">
                              {formatDateTime(lastCallLog.callDate)}
                            </div>
                            {lastCallLog.nextCallDate && (
                              <div className="text-xs text-blue-600 mt-1">
                                Rappel: {formatDateTime(lastCallLog.nextCallDate)} {lastCallLog.nextCallTime}
                              </div>
                            )}
                            
                            {/* Notes button - only show if there are notes */}
                            {lastCallLog.notes && (
                              <button 
                                onClick={() => toggleNotes(lead.id)}
                                className="mt-1 text-xs flex items-center gap-1 text-blue-600 hover:text-blue-800"
                              >
                                <MessageSquare className="h-3 w-3" />
                                {expandedNotes === lead.id ? 'Masquer notes' : 'Voir notes'}
                              </button>
                            )}
                            
                            {/* Expanded notes section */}
                            {expandedNotes === lead.id && lastCallLog.notes && (
                              <div className="mt-2 p-2 bg-blue-50 rounded-md text-xs text-blue-800 max-w-xs">
                                <p className="font-medium mb-1">Notes:</p>
                                <p>{lastCallLog.notes}</p>
                                <div className="mt-1 text-blue-600 text-xs">
                                  <p>Intéressé(e): {lastCallLog.interested}</p>
                                  <p>Satisfaction: {lastCallLog.satisfactionLevel}/5</p>
                                </div>
                              </div>
                            )}
                          </div>
                        ) : (
                          <span className="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                            Pas encore appelé
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div className="flex items-center gap-2">
                          <button
                            onClick={() => handleViewLeadDetails(lead)}
                            className="btn btn-primary py-1 px-3 text-sm flex items-center gap-1 text-white"
                            title="Voir détails"
                          >
                            <Eye className="h-4 w-4" />
                            <span>Détails</span>
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {/* Instructions Panel */}
      <div className="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 className="font-medium text-blue-900 mb-2 flex items-center gap-2">
          <Target className="h-5 w-5" />
          Instructions pour le centre d'appel
        </h3>
        <div className="text-sm text-blue-800 space-y-2">
          <p>
            <strong>1. Appeler les clients</strong> - Utilisez le bouton <Phone className="h-4 w-4 inline" /> pour appeler directement via MicroSIP
          </p>
          <p>
            <strong>2. Envoyer un message</strong> - Utilisez le bouton <MessageCircle className="h-4 w-4 inline" /> pour ouvrir WhatsApp
          </p>
          <p>
            <strong>3. Remplir le formulaire de suivi</strong> - Après chaque appel, utilisez le bouton <FileText className="h-4 w-4 inline" /> pour enregistrer les détails
          </p>
          <p>
            <strong>4. Créer une commande</strong> - Utilisez le bouton <ShoppingCart className="h-4 w-4 inline" /> pour créer une commande avec les données pré-remplies
          </p>
          <p>
            <strong>5. Planifier les rappels</strong> - Pour les clients intéressés, définissez une date et heure de rappel
          </p>
        </div>
      </div>
      
      {/* Tooltip */}
      {showTooltip && (
        <div 
          className="fixed bg-black bg-opacity-80 text-white text-xs rounded px-2 py-1 z-50 pointer-events-none"
          style={{ 
            left: `${tooltipPosition.x + 10}px`, 
            top: `${tooltipPosition.y - 20}px` 
          }}
        >
          {tooltipContent}
        </div>
      )}

      {/* Call Follow-Up Modal */}
      {showFollowUpModal && selectedLead && (
        <CallFollowUpModal
          lead={selectedLead}
          onSave={handleSaveFollowUp}
          isMandatory={isCallInitiatedModal}
          onClose={() => {
            // Only allow closing if it's not a call-initiated modal
            if (!isCallInitiatedModal) {
            setShowFollowUpModal(false);
            setSelectedLead(null);
            } else {
              toast.error("Vous devez remplir et enregistrer le formulaire de suivi d'appel");
            }
          }}
        />
      )}

      {/* Lead Details Modal */}
      {showLeadDetails && selectedLead && (
        <LeadDetailsModal
          lead={selectedLead}
          onClose={() => setShowLeadDetails(false)}
          onCall={handleCall}
          onWhatsApp={handleWhatsApp}
          onOpenForm={handleOpenForm}
          onCreateOrder={handleCreateOrder}
        />
      )}

      {/* Notes Viewer Modal */}
      {selectedLog && (
        <NotesViewer
          callLog={selectedLog}
          onClose={() => setSelectedLog(null)}
        />
      )}
    </div>
  );
};

export default CallCenterDashboard;