import React, { useState, useEffect, useMemo } from 'react';
import { Search, RefreshCw, ChevronLeft, ChevronRight, Users } from 'lucide-react';
import { useContactStore } from '../../store/contactStore';
import { useAuthStore } from '../../store/authStore';
import { Platform, RequestType } from '../../types';
import ClientCard from './ClientCard';
import DateFilter, { DateRange } from '../common/DateFilter';

const ClientsTab: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { 
    contacts, 
    loading, 
    fetchContactsWithPagination, 
    getFilteredContactsByTeam,
    totalCount 
  } = useContactStore();
  
  const [searchTerm, setSearchTerm] = useState('');
  const [platformFilter, setPlatformFilter] = useState<Platform | ''>('');
  const [requestTypeFilter, setRequestTypeFilter] = useState<RequestType | ''>('');
  const [currentPage, setCurrentPage] = useState(1);
  const [itemsPerPage, setItemsPerPage] = useState(100);
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });
  const [isInitialLoad, setIsInitialLoad] = useState(true);
  const [filteredCount, setFilteredCount] = useState(0);

  // Get accessible platforms based on team
  const getAccessiblePlatforms = () => {
    if (user?.role === 'admin') {
      return ['Facebook', 'Instagram', 'WhatsApp', 'Clients'];
    }
    
    switch (user?.codeAgence) {
      case '000001': // Réseaux sociaux
        return ['Facebook', 'Instagram'];
      case '000002': // Centre d'appel
        return ['Facebook', 'Instagram', 'WhatsApp', 'Clients'];
      case '000003': // WhatsApp
        return ['WhatsApp'];
      default:
        return ['Facebook', 'Instagram', 'WhatsApp', 'Clients'];
    }
  };

  const accessiblePlatforms = getAccessiblePlatforms();

  // Prepare filters for server-side filtering
  const prepareFilters = () => {
    return {
      searchTerm: searchTerm,
      platformFilter: platformFilter,
      requestTypeFilter: requestTypeFilter,
      dateFilter: dateFilter,
      userRole: user?.role,
      userCodeAgence: user?.codeAgence,
      userId: user?.id
    };
  };

  // Load contacts with pagination and filters
  useEffect(() => {
    const loadContacts = async () => {
      const filters = prepareFilters();
      await fetchContactsWithPagination(currentPage, itemsPerPage, filters);
      setIsInitialLoad(false);
    };
    
    loadContacts();
  }, [currentPage, itemsPerPage, searchTerm, platformFilter, requestTypeFilter, dateFilter, user?.id, user?.role, user?.codeAgence]);

  // Reset to first page when filters change
  useEffect(() => {
    if (!isInitialLoad) {
      setCurrentPage(1);
    }
  }, [searchTerm, platformFilter, requestTypeFilter, dateFilter, itemsPerPage]);
  
  const resetFilters = () => {
    setSearchTerm('');
    setPlatformFilter('');
    setRequestTypeFilter('');
    setDateFilter({
      startDate: null,
      endDate: null,
      label: 'Toutes les dates'
    });
    setCurrentPage(1);
  };

  // Calculate total pages
  const totalPages = Math.ceil(totalCount / itemsPerPage);
  
  // Calculate display range
  const startIndex = (currentPage - 1) * itemsPerPage + 1;
  const endIndex = Math.min(startIndex + contacts.length - 1, totalCount);

  // Get team name for display
  const getTeamName = (codeAgence?: string) => {
    switch (codeAgence) {
      case '000001': return 'Réseaux sociaux';
      case '000002': return 'Centre d\'appel';
      case '000003': return 'WhatsApp';
      default: return 'Équipe';
    }
  };

  // Get unique agents in the team for supervisors
  const teamAgents = useMemo(() => {
    if (user?.role === 'superviseur') {
      const uniqueAgents = new Set();
      contacts.forEach(contact => {
        if (contact.agentName && contact.codeAgence === user.codeAgence) {
          uniqueAgents.add(contact.agentName);
        }
      });
      return Array.from(uniqueAgents);
    }
    return [];
  }, [contacts, user]);

  // Handle date filter changes
  const handleDateFilterChange = (newDateRange: DateRange) => {
    setDateFilter(newDateRange);
  };

  // Platform counts - these are now estimates based on total count and current page data
  const platformCounts = useMemo(() => {
    // Calculate percentages from current page data
    const currentPageTotal = contacts.length;
    const facebookCount = contacts.filter(c => c.plateforme === 'Facebook').length;
    const instagramCount = contacts.filter(c => c.plateforme === 'Instagram').length;
    const whatsappCount = contacts.filter(c => c.plateforme === 'WhatsApp').length;
    const clientsCount = contacts.filter(c => c.plateforme === 'Clients').length;
    
    // Calculate percentages
    const facebookPercentage = currentPageTotal > 0 ? facebookCount / currentPageTotal : 0;
    const instagramPercentage = currentPageTotal > 0 ? instagramCount / currentPageTotal : 0;
    const whatsappPercentage = currentPageTotal > 0 ? whatsappCount / currentPageTotal : 0;
    const clientsPercentage = currentPageTotal > 0 ? clientsCount / currentPageTotal : 0;
    
    // Estimate total counts based on percentages
    return {
      '': totalCount,
      'Facebook': Math.round(totalCount * facebookPercentage),
      'Instagram': Math.round(totalCount * instagramPercentage),
      'WhatsApp': Math.round(totalCount * whatsappPercentage),
      'Clients': Math.round(totalCount * clientsPercentage)
    };
  }, [contacts, totalCount]);

  // Request type counts - also estimates based on current page data
  const requestTypeCounts = useMemo(() => {
    // Calculate percentages from current page data
    const currentPageTotal = contacts.length;
    const infoCount = contacts.filter(c => c.typeDeDemande === 'Information').length;
    const waitingCount = contacts.filter(c => c.typeDeDemande === 'En attente de traitement').length;
    const orientationCount = contacts.filter(c => c.typeDeDemande === 'Orientation Para').length;
    const noResponseCount = contacts.filter(c => c.typeDeDemande === 'Sans réponse').length;
    const waitingResponseCount = contacts.filter(c => c.typeDeDemande === 'En attente de réponse').length;
    const cancelledCount = contacts.filter(c => c.typeDeDemande === 'Annulee').length;
    const orderCount = contacts.filter(c => c.typeDeDemande === 'Commande').length;
    
    // Calculate percentages
    const infoPercentage = currentPageTotal > 0 ? infoCount / currentPageTotal : 0;
    const waitingPercentage = currentPageTotal > 0 ? waitingCount / currentPageTotal : 0;
    const orientationPercentage = currentPageTotal > 0 ? orientationCount / currentPageTotal : 0;
    const noResponsePercentage = currentPageTotal > 0 ? noResponseCount / currentPageTotal : 0;
    const waitingResponsePercentage = currentPageTotal > 0 ? waitingResponseCount / currentPageTotal : 0;
    const cancelledPercentage = currentPageTotal > 0 ? cancelledCount / currentPageTotal : 0;
    const orderPercentage = currentPageTotal > 0 ? orderCount / currentPageTotal : 0;
    
    // Estimate total counts based on percentages
    return {
      '': totalCount,
      'Information': Math.round(totalCount * infoPercentage),
      'En attente de traitement': Math.round(totalCount * waitingPercentage),
      'Orientation Para': Math.round(totalCount * orientationPercentage),
      'Sans réponse': Math.round(totalCount * noResponsePercentage),
      'En attente de réponse': Math.round(totalCount * waitingResponsePercentage),
      'Annulee': Math.round(totalCount * cancelledPercentage),
      'Commande': Math.round(totalCount * orderPercentage)
    };
  }, [contacts, totalCount]);

  // Handle manual refresh
  const handleRefresh = async () => {
    const filters = prepareFilters();
    await fetchContactsWithPagination(currentPage, itemsPerPage, filters);
    toast.success('Données actualisées');
  };

  if (loading && isInitialLoad) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-secondary-600">Chargement des clients...</p>
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
            <h1 className="text-2xl font-bold text-secondary-800 mb-2 flex items-center gap-3">
              <Users className="h-8 w-8 text-primary-600" />
              Gestion des Clients
              {/* Show team context for supervisors */}
              {user?.role === 'superviseur' && user?.codeAgence && (
                <span className="text-lg font-normal text-secondary-600 ml-2">
                  - {getTeamName(user.codeAgence)}
                </span>
              )}
            </h1>
            <div className="space-y-1">
              <p className="text-secondary-600 text-lg">
                {contacts.length.toLocaleString()} client{contacts.length > 1 ? 's' : ''} sur la page actuelle
              </p>
              <div className="flex items-center gap-4 text-sm">
                <span className="text-secondary-500">
                  📊 Total en base: <span className="font-semibold text-secondary-700">{totalCount.toLocaleString()}</span>
                </span>
                <span className="text-secondary-500">
                  📄 Page: <span className="font-semibold text-secondary-700">{currentPage}/{totalPages}</span>
                </span>
                {/* Show team platform access info */}
                {user?.codeAgence && user.role !== 'admin' && (
                  <span className="text-secondary-500">
                    🔒 Accès: <span className="font-semibold text-secondary-700">
                      {accessiblePlatforms.join(', ')}
                    </span>
                  </span>
                )}
              </div>
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
                <p className="text-blue-700 text-sm mt-1">
                  📊 Agents actifs: <span className="font-semibold">{teamAgents.length}</span> 
                  {teamAgents.length > 0 && teamAgents.length < 5 && (
                    <span className="ml-2">({teamAgents.join(', ')})</span>
                  )}
                </p>
                <p className="text-blue-700 text-sm">
                  🔍 Données affichées: Toutes les données de votre équipe (tous agents confondus)
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
                placeholder="Rechercher par nom, téléphone, message..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input pl-10 w-full sm:w-80 bg-white/80 backdrop-blur-sm"
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
              onClick={handleRefresh}
              disabled={loading}
              className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white disabled:opacity-50"
            >
              <RefreshCw className={`h-4 w-4 ${loading ? 'animate-spin' : ''}`} />
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

      {/* Platform Filter Tabs - Only show accessible platforms */}
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
            Toutes ({platformCounts[''].toLocaleString()})
          </button>
          
          {/* Only show accessible platforms based on team */}
          {accessiblePlatforms.includes('Facebook') && (
            <button
              onClick={() => setPlatformFilter('Facebook')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
                platformFilter === 'Facebook' 
                  ? 'bg-blue-600 text-white shadow-md' 
                  : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
              }`}
            >
              <div className="w-4 h-4 bg-blue-600 rounded"></div>
              Facebook ({platformCounts['Facebook'].toLocaleString()})
            </button>
          )}
          
          {accessiblePlatforms.includes('Instagram') && (
            <button
              onClick={() => setPlatformFilter('Instagram')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
                platformFilter === 'Instagram' 
                  ? 'bg-pink-600 text-white shadow-md' 
                  : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
              }`}
            >
              <div className="w-4 h-4 bg-pink-600 rounded"></div>
              Instagram ({platformCounts['Instagram'].toLocaleString()})
            </button>
          )}
          
          {accessiblePlatforms.includes('WhatsApp') && (
            <button
              onClick={() => setPlatformFilter('WhatsApp')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
                platformFilter === 'WhatsApp' 
                  ? 'bg-green-600 text-white shadow-md' 
                  : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
              }`}
            >
              <div className="w-4 h-4 bg-green-600 rounded"></div>
              WhatsApp ({platformCounts['WhatsApp'].toLocaleString()})
            </button>
          )}
          
          {accessiblePlatforms.includes('Clients') && (
            <button
              onClick={() => setPlatformFilter('Clients')}
              className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 flex items-center gap-2 ${
                platformFilter === 'Clients' 
                  ? 'bg-purple-600 text-white shadow-md' 
                  : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
              }`}
            >
              <div className="w-4 h-4 bg-purple-600 rounded"></div>
              Clients ({platformCounts['Clients'].toLocaleString()})
            </button>
          )}
        </div>
      </div>

      {/* Request Type Filter Tabs */}
      <div className="mb-6">
        <h3 className="text-sm font-medium text-secondary-700 mb-3">Filtrer par type de demande</h3>
        <div className="flex flex-wrap gap-2">
          <button
            onClick={() => setRequestTypeFilter('')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              requestTypeFilter === '' 
                ? 'bg-primary-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Tous ({requestTypeCounts[''].toLocaleString()})
          </button>
          <button
            onClick={() => setRequestTypeFilter('Information')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              requestTypeFilter === 'Information' 
                ? 'bg-blue-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Information ({requestTypeCounts['Information'].toLocaleString()})
          </button>
          <button
            onClick={() => setRequestTypeFilter('En attente de traitement')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              requestTypeFilter === 'En attente de traitement' 
                ? 'bg-yellow-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            En attente ({requestTypeCounts['En attente de traitement'].toLocaleString()})
          </button>
          <button
            onClick={() => setRequestTypeFilter('Orientation Para')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              requestTypeFilter === 'Orientation Para' 
                ? 'bg-purple-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Orientation ({requestTypeCounts['Orientation Para'].toLocaleString()})
          </button>
          <button
            onClick={() => setRequestTypeFilter('Sans réponse')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              requestTypeFilter === 'Sans réponse' 
                ? 'bg-red-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Sans réponse ({requestTypeCounts['Sans réponse'].toLocaleString()})
          </button>
          <button
            onClick={() => setRequestTypeFilter('Commande')}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200 ${
              requestTypeFilter === 'Commande' 
                ? 'bg-green-600 text-white shadow-md' 
                : 'bg-white text-secondary-600 border border-secondary-200 hover:bg-secondary-50'
            }`}
          >
            Commande ({requestTypeCounts['Commande'].toLocaleString()})
          </button>
        </div>
      </div>

      {/* Pagination Controls - Top */}
      {totalCount > 0 && (
        <div className="mb-6 bg-white rounded-lg shadow-sm border border-gray-100 p-4">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            {/* Items per page selector */}
            <div className="flex items-center gap-3">
              <label className="text-sm font-medium text-secondary-700">Clients par page:</label>
              <select
                value={itemsPerPage}
                onChange={(e) => setItemsPerPage(Number(e.target.value))}
                className="input py-1 px-2 text-sm w-20"
              >
                <option value={50}>50</option>
                <option value={100}>100</option>
                <option value={200}>200</option>
                <option value={500}>500</option>
              </select>
            </div>

            {/* Pagination info and controls */}
            <div className="flex items-center gap-4">
              <span className="text-sm text-secondary-600">
                {startIndex}-{endIndex} sur {totalCount.toLocaleString()}
              </span>
              
              <div className="flex items-center gap-2">
                <button
                  onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
                  disabled={currentPage === 1}
                  className="btn btn-outline py-1 px-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ChevronLeft className="h-4 w-4" />
                </button>
                
                <span className="text-sm font-medium text-secondary-700 px-3">
                  Page {currentPage} sur {totalPages}
                </span>
                
                <button
                  onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
                  disabled={currentPage === totalPages}
                  className="btn btn-outline py-1 px-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <ChevronRight className="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      
      {contacts.length === 0 ? (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <div className="text-secondary-500 mb-2">
            Aucun client trouvé
          </div>
          <p className="text-sm text-secondary-400">
            {searchTerm || platformFilter || requestTypeFilter || (dateFilter.startDate && dateFilter.endDate) ? 
              "Essayez de modifier vos critères de recherche" : 
              "Ajoutez des contacts pour les voir apparaître ici"}
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 gap-4">
          {contacts.map(client => (
            <ClientCard key={client.id} client={client} />
          ))}
        </div>
      )}

      {/* Pagination Controls - Bottom */}
      {totalCount > 0 && totalPages > 1 && (
        <div className="mt-6 bg-white rounded-lg shadow-sm border border-gray-100 p-4">
          <div className="flex justify-center items-center gap-4">
            <button
              onClick={() => setCurrentPage(1)}
              disabled={currentPage === 1}
              className="btn btn-outline py-1 px-3 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Première
            </button>
            
            <button
              onClick={() => setCurrentPage(prev => Math.max(1, prev - 1))}
              disabled={currentPage === 1}
              className="btn btn-outline py-1 px-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeft className="h-4 w-4" />
            </button>
            
            <span className="text-sm font-medium text-secondary-700 px-4">
              Page {currentPage} sur {totalPages}
            </span>
            
            <button
              onClick={() => setCurrentPage(prev => Math.min(totalPages, prev + 1))}
              disabled={currentPage === totalPages}
              className="btn btn-outline py-1 px-2 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronRight className="h-4 w-4" />
            </button>
            
            <button
              onClick={() => setCurrentPage(totalPages)}
              disabled={currentPage === totalPages}
              className="btn btn-outline py-1 px-3 text-sm disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Dernière
            </button>
          </div>
          
          <div className="text-center mt-3 text-sm text-secondary-500">
            Affichage de {startIndex}-{endIndex} sur {totalCount.toLocaleString()} clients
          </div>
        </div>
      )}
    </div>
  );
};

export default ClientsTab;