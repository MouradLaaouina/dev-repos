import React, { useState } from 'react';
import { BarChart3, TrendingUp, Users, MessageCircle, Phone, ShoppingCart, Target, PieChart, RefreshCw, Shield, ChevronDown, ChevronUp, Activity, Database, Crown, UserCheck, Calendar } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';
import { useDashboardStore } from '../../store/dashboardStore';
import { getAccessibleDashboards, getTeamName, getTeamSupervisor, TEAM_CODES, getDashboardsByCategory, getFilterDescription, getTeamInfo } from '../../config/dashboardConfig';
import { useNavigate } from 'react-router-dom';
import DateFilter, { DateRange } from '../common/DateFilter';
import DashboardWrapper from './DashboardWrapper';

const AdvancedStatsTab: React.FC = () => {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const { clearCache } = useDashboardStore();
  const [refreshing, setRefreshing] = useState(false);
  const [expandedCategories, setExpandedCategories] = useState<{ [key: string]: boolean }>({
    Performance: true,
    Volume: true,
    Communication: false,
    Conversion: false,
    Agence: false,
    Sources: false
  });
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });

  const accessibleDashboards = user ? getAccessibleDashboards(user.role, user.codeAgence) : [];
  const dashboardsByCategory = getDashboardsByCategory(accessibleDashboards);
  const userCodeAgence = user?.codeAgence;
  const teamInfo = userCodeAgence ? getTeamInfo(userCodeAgence) : null;

  const handleRefreshAll = async () => {
    if (refreshing) return;
    
    setRefreshing(true);
    try {
      clearCache();
      // The DashboardWrapper components will automatically re-fetch their data
      // when the cache is cleared
    } finally {
      setRefreshing(false);
    }
  };

  const toggleCategoryExpansion = (category: string) => {
    setExpandedCategories(prev => ({
      ...prev,
      [category]: !prev[category]
    }));
  };

  const getCategoryIcon = (category: string) => {
    switch (category) {
      case 'Performance': return TrendingUp;
      case 'Volume': return BarChart3;
      case 'Communication': return MessageCircle;
      case 'Conversion': return Target;
      case 'Agence': return Users;
      case 'Sources': return PieChart;
      default: return Activity;
    }
  };

  const getCategoryColor = (category: string) => {
    switch (category) {
      case 'Performance': return 'text-green-600 bg-green-100';
      case 'Volume': return 'text-blue-600 bg-blue-100';
      case 'Communication': return 'text-purple-600 bg-purple-100';
      case 'Conversion': return 'text-orange-600 bg-orange-100';
      case 'Agence': return 'text-red-600 bg-red-100';
      case 'Sources': return 'text-amber-600 bg-amber-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const getRoleIcon = (role: string) => {
    switch (role) {
      case 'admin': return Crown;
      case 'superviseur': return UserCheck;
      case 'agent': return Users;
      default: return Shield;
    }
  };

  const getRoleColor = (role: string) => {
    switch (role) {
      case 'admin': return 'text-red-600 bg-red-100';
      case 'superviseur': return 'text-blue-600 bg-blue-100';
      case 'agent': return 'text-green-600 bg-green-100';
      default: return 'text-gray-600 bg-gray-100';
    }
  };

  const handleGoToSourceStats = () => {
    navigate('/dashboard/source-stats');
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

  if (!user) {
    return (
      <div className="py-6">
        <div className="text-center">
          <Shield className="h-12 w-12 text-secondary-400 mx-auto mb-4" />
          <p className="text-secondary-600">Veuillez vous connecter pour accéder aux tableaux de bord.</p>
        </div>
      </div>
    );
  }

  const RoleIcon = getRoleIcon(user.role);

  return (
    <div className="py-6">
      {/* Header with role and team information */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-8 border border-secondary-100">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2 flex items-center gap-3">
              <BarChart3 className="h-8 w-8 text-primary-600" />
              Tableaux de Bord Avancés
            </h1>
            <div className="space-y-3">
              {/* Role Information */}
              <div className="flex items-center gap-3">
                <div className={`px-3 py-1 rounded-full text-sm font-medium flex items-center gap-2 ${getRoleColor(user.role)}`}>
                  <RoleIcon className="h-4 w-4" />
                  <span className="capitalize">{user.role}</span>
                </div>
                <span className="text-secondary-600 text-sm">
                  {user.name}
                </span>
              </div>

              {/* Team Information */}
              {teamInfo && (
                <div className="flex items-center gap-3">
                  <div className={`px-3 py-1 rounded-full text-sm font-medium flex items-center gap-2 ${
                    teamInfo.color === 'green' ? 'bg-green-100 text-green-800' :
                    teamInfo.color === 'pink' ? 'bg-pink-100 text-pink-800' :
                    teamInfo.color === 'blue' ? 'bg-blue-100 text-blue-800' :
                    'bg-gray-100 text-gray-800'
                  }`}>
                    <div className={`w-2 h-2 rounded-full ${
                      teamInfo.color === 'green' ? 'bg-green-500' :
                      teamInfo.color === 'pink' ? 'bg-pink-500' :
                      teamInfo.color === 'blue' ? 'bg-blue-500' :
                      'bg-gray-500'
                    }`}></div>
                    {teamInfo.name}
                  </div>
                  <span className="text-secondary-500 text-sm">
                    Code: {teamInfo.code}
                  </span>
                  {user.role === 'superviseur' && (
                    <span className="text-secondary-500 text-sm">
                      • Superviseur: {teamInfo.supervisor}
                    </span>
                  )}
                </div>
              )}

              {/* Date Filter Badge */}
              {dateFilter.startDate && dateFilter.endDate && (
                <div className="flex items-center gap-3">
                  <div className="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 flex items-center gap-2">
                    <Calendar className="h-4 w-4" />
                    Période: {dateFilter.label}
                  </div>
                </div>
              )}

              {/* Statistics */}
              <div className="flex flex-wrap items-center gap-4 text-sm">
                <span className="text-secondary-500">
                  📊 Tableaux accessibles: <span className="font-semibold text-secondary-700">{accessibleDashboards.length}</span>
                </span>
                <span className="text-secondary-500">
                  🔒 Accès: <span className="font-semibold text-secondary-700">
                    {user.role === 'admin' ? 'Données globales' : 'Données filtrées'}
                  </span>
                </span>
                <span className="text-secondary-500">
                  📈 Catégories: <span className="font-semibold text-secondary-700">
                    {Object.keys(dashboardsByCategory).length}
                  </span>
                </span>
              </div>
            </div>
          </div>
          
          <div className="mt-4 md:mt-0 flex flex-col sm:flex-row gap-2">
            <DateFilter 
              onFilterChange={handleDateFilterChange}
              onReset={handleResetDateFilter}
            />
            
            <button
              onClick={handleGoToSourceStats}
              className="btn btn-outline flex items-center gap-2 bg-amber-50 border-amber-300 text-amber-700 hover:bg-amber-100"
            >
              <PieChart className="h-4 w-4" />
              Stats par Source
            </button>
            <button
              onClick={handleRefreshAll}
              disabled={refreshing}
              className="btn btn-primary flex items-center gap-2 disabled:opacity-50"
            >
              <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
              {refreshing ? 'Actualisation...' : 'Actualiser'}
            </button>
          </div>
        </div>
      </div>

      {/* Team structure information for admin and supervisors */}
      {(user.role === 'admin' || user.role === 'superviseur') && (
        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <h3 className="font-medium text-blue-900 mb-3 flex items-center gap-2">
            <Database className="h-4 w-4" />
            Structure des équipes et supervision
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div className="flex flex-col gap-2">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                <span className="text-blue-800 font-medium">WhatsApp ({TEAM_CODES.WHATSAPP})</span>
              </div>
              <span className="text-blue-700 text-xs ml-5">
                Superviseur: {getTeamSupervisor(TEAM_CODES.WHATSAPP)}
              </span>
            </div>
            <div className="flex flex-col gap-2">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-pink-500 rounded-full"></div>
                <span className="text-blue-800 font-medium">Réseaux sociaux ({TEAM_CODES.RESEAUX_SOCIAUX})</span>
              </div>
              <span className="text-blue-700 text-xs ml-5">
                Superviseur: {getTeamSupervisor(TEAM_CODES.RESEAUX_SOCIAUX)}
              </span>
            </div>
            <div className="flex flex-col gap-2">
              <div className="flex items-center gap-2">
                <div className="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span className="text-blue-800 font-medium">Centre d'appel ({TEAM_CODES.CENTRE_APPEL})</span>
              </div>
              <span className="text-blue-700 text-xs ml-5">
                Superviseur: {getTeamSupervisor(TEAM_CODES.CENTRE_APPEL)}
              </span>
            </div>
          </div>
          <div className="mt-3 text-xs text-blue-700">
            {user.role === 'admin' ? 
              'En tant qu\'administrateur, vous avez accès à toutes les données. Les tableaux avec filtrage utilisent ces codes pour limiter les données selon l\'équipe.' :
              `En tant que superviseur de l'équipe ${teamInfo?.name}, vous ne voyez que les données de votre équipe.`
            }
          </div>
        </div>
      )}

      {/* Dashboard Categories */}
      {Object.keys(dashboardsByCategory).length === 0 ? (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <Shield className="h-12 w-12 text-secondary-400 mx-auto mb-4" />
          <div className="text-secondary-500 mb-2">
            Aucun tableau de bord disponible
          </div>
          <p className="text-sm text-secondary-400">
            Votre rôle <span className="font-medium">{user.role}</span> ne donne accès à aucun tableau de bord pour le moment.
          </p>
        </div>
      ) : (
        <div className="space-y-6">
          {Object.entries(dashboardsByCategory).map(([category, dashboards]) => {
            const CategoryIcon = getCategoryIcon(category);
            const isExpanded = expandedCategories[category];
            const categoryColor = getCategoryColor(category);

            return (
              <div key={category} className="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                {/* Category Header */}
                <div 
                  className="p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors duration-200"
                  onClick={() => toggleCategoryExpansion(category)}
                >
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <div className={`p-2 rounded-lg ${categoryColor}`}>
                        <CategoryIcon className="h-5 w-5" />
                      </div>
                      <div>
                        <h2 className="text-lg font-semibold text-secondary-800">{category}</h2>
                        <p className="text-sm text-secondary-600">
                          {dashboards.length} tableau{dashboards.length > 1 ? 'x' : ''} disponible{dashboards.length > 1 ? 's' : ''}
                        </p>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      <div className="text-xs text-secondary-500">
                        {isExpanded ? 'Masquer' : 'Afficher'}
                      </div>
                      {isExpanded ? (
                        <ChevronUp className="h-5 w-5 text-secondary-600" />
                      ) : (
                        <ChevronDown className="h-5 w-5 text-secondary-600" />
                      )}
                    </div>
                  </div>
                </div>

                {/* Category Content */}
                {isExpanded && (
                  <div className="p-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                      {dashboards.map((dashboard) => (
                        <DashboardWrapper
                          key={dashboard.component}
                          component={dashboard.component}
                          title={dashboard.title}
                          description={dashboard.description}
                          userRole={user.role}
                          userId={user.id}
                          userCodeAgence={user.codeAgence}
                          filter={dashboard.filter}
                          startDate={dateFilter.startDate}
                          endDate={dateFilter.endDate}
                          dateLabel={dateFilter.label}
                        />
                      ))}
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Access Information Footer */}
      <div className="mt-8 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-lg p-6">
        <h3 className="font-medium text-secondary-900 mb-4 flex items-center gap-2">
          <Shield className="h-5 w-5 text-primary-600" />
          Informations d'accès et sécurité
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-secondary-600">Rôle:</span>
              <span className="font-medium text-secondary-900 capitalize">{user.role}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-secondary-600">Utilisateur:</span>
              <span className="font-medium text-secondary-900">{user.name}</span>
            </div>
            {teamInfo && (
              <>
                <div className="flex justify-between">
                  <span className="text-secondary-600">Équipe:</span>
                  <span className="font-medium text-secondary-900">{teamInfo.name} ({teamInfo.code})</span>
                </div>
                {user.role === 'superviseur' && (
                  <div className="flex justify-between">
                    <span className="text-secondary-600">Supervision:</span>
                    <span className="font-medium text-secondary-900">{teamInfo.supervisor}</span>
                  </div>
                )}
              </>
            )}
            <div className="flex justify-between">
              <span className="text-secondary-600">Tableaux accessibles:</span>
              <span className="font-medium text-secondary-900">{accessibleDashboards.length}</span>
            </div>
            <div className="flex justify-between">
              <span className="text-secondary-600">Catégories:</span>
              <span className="font-medium text-secondary-900">{Object.keys(dashboardsByCategory).length}</span>
            </div>
          </div>
          <div className="space-y-2">
            <div className="flex justify-between">
              <span className="text-secondary-600">Type d'accès:</span>
              <span className="font-medium text-secondary-900">
                {user.role === 'admin' ? 'Administrateur' : 
                 user.role === 'superviseur' ? 'Superviseur d\'équipe' : 'Utilisateur filtré'}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-secondary-600">Portée des données:</span>
              <span className="font-medium text-secondary-900">
                {user.role === 'admin' ? 'Toutes les équipes' : 
                 user.role === 'superviseur' ? 'Équipe supervisée' : 'Personnel uniquement'}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-secondary-600">Filtrage automatique:</span>
              <span className="font-medium text-secondary-900">
                {user.role === 'admin' ? 'Désactivé' : 'Activé'}
              </span>
            </div>
            <div className="flex justify-between">
              <span className="text-secondary-600">Mise en cache:</span>
              <span className="font-medium text-secondary-900">Activée</span>
            </div>
          </div>
        </div>
        
        {/* Role-specific information */}
        <div className="mt-4 p-3 bg-white rounded-md border border-gray-200">
          <h4 className="font-medium text-secondary-800 mb-2">Permissions de votre rôle ({user.role}):</h4>
          <div className="text-xs text-secondary-600 space-y-1">
            {user.role === 'admin' && (
              <>
                <p>✅ Accès à tous les tableaux de bord</p>
                <p>✅ Vue globale de toutes les équipes</p>
                <p>✅ Statistiques par agent et marque</p>
                <p>✅ Comparaisons inter-équipes</p>
                <p>✅ Tableaux de conversion avancés</p>
                <p>✅ Gestion des superviseurs d'équipe</p>
                <p>✅ Statistiques par source et influenceur</p>
              </>
            )}
            {user.role === 'superviseur' && (
              <>
                <p>✅ Tableaux de bord de supervision</p>
                <p>✅ Données filtrées par votre équipe ({teamInfo?.name})</p>
                <p>✅ Performance des agents de votre équipe</p>
                <p>✅ Statistiques détaillées par agent</p>
                <p>✅ Suivi des appels et communications</p>
                <p>✅ Statistiques par source et influenceur</p>
                <p>✅ Supervision: {teamInfo?.supervisor}</p>
                <p>⚠️ Accès limité à votre équipe uniquement</p>
              </>
            )}
            {user.role === 'agent' && (
              <>
                <p>✅ Tableaux de bord personnels</p>
                <p>✅ Vos propres statistiques de performance</p>
                <p>✅ Messages et commandes traités</p>
                <p>✅ Suivi de vos types de demandes</p>
                <p>⚠️ Accès limité aux données de votre équipe</p>
                {teamInfo && (
                  <p>👥 Équipe: {teamInfo.name} (supervisé par {teamInfo.supervisor})</p>
                )}
              </>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default AdvancedStatsTab;