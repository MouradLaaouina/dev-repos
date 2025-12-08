import React, { useState, useEffect } from 'react';
import { BarChart2, Users, MessageCircle, Activity, TrendingUp, ShoppingBag, CheckCircle, Calendar, Target, Clock, Database, RefreshCw, Filter, Search } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';
import { useDashboardStore } from '../../store/dashboardStore';
import { getTeamName } from '../../config/dashboardConfig';
import DashboardChart from './DashboardChart';
import DateFilter, { DateRange } from '../common/DateFilter';
import ViewModeToggle from './ViewModeToggle';

const SourceStatsTab: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const { fetchDashboardData, loading, clearCache } = useDashboardStore();
  const [chartData, setChartData] = useState<{ [key: string]: any }>({});
  const [loadingStates, setLoadingStates] = useState<{ [key: string]: boolean }>({});
  const [refreshing, setRefreshing] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedSource, setSelectedSource] = useState<string | null>(null);
  const [dateFilter, setDateFilter] = useState<DateRange>({
    startDate: null,
    endDate: null,
    label: 'Toutes les dates'
  });
  const [viewMode, setViewMode] = useState<'chart' | 'text'>('chart');

  const userCodeAgence = user?.codeAgence;
  const teamName = userCodeAgence ? getTeamName(userCodeAgence) : '';

  // Define the charts to load
  const charts = [
    {
      component: 'ContactsBySourceChart',
      title: 'Contacts par source (Influenceurs)',
      description: 'Répartition des contacts par source (influenceurs, publicités, etc.)',
      type: 'pie' as const
    },
    {
      component: 'RequestTypesBySourceChart',
      title: 'Types de demandes par source',
      description: 'Types de demandes générées par chaque source',
      type: 'bar' as const
    },
    {
      component: 'ConversionRateBySourceChart',
      title: 'Taux de conversion par source',
      description: 'Taux de conversion (commandes/contacts) par source',
      type: 'bar' as const
    },
    {
      component: 'OrdersBySourceChart',
      title: 'Commandes par source',
      description: 'Nombre de commandes générées par chaque source',
      type: 'bar' as const
    },
    {
      component: 'AverageBasketBySourceChart',
      title: 'Panier moyen par source',
      description: 'Valeur moyenne des commandes par source',
      type: 'bar' as const
    }
  ];

  // Filter charts based on search term
  const filteredCharts = charts.filter(chart => 
    chart.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
    chart.description.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const loadChartData = async (component: string) => {
    setLoadingStates(prev => ({ ...prev, [component]: true }));

    try {
      console.log(`📊 Loading ${component} with date filter:`, dateFilter);

      // Pass date filter to the dashboard data fetch function
      const data = await fetchDashboardData(
        component, 
        user?.role || 'agent', 
        user?.id, 
        userCodeAgence,
        dateFilter.startDate,
        dateFilter.endDate
      );

      setChartData(prev => ({ ...prev, [component]: data }));
      console.log(`✅ Loaded ${component} successfully`);
    } catch (error) {
      console.error(`❌ Error loading ${component}:`, error);
    } finally {
      setLoadingStates(prev => ({ ...prev, [component]: false }));
    }
  };

  const loadAllCharts = async () => {
    console.log('🔄 Loading all source statistics charts...');
    const promises = charts.map(chart => loadChartData(chart.component));
    await Promise.all(promises);
    console.log('✅ All source statistics charts loaded');
  };

  // Load charts when user or date filter changes
  useEffect(() => {
    if (user) {
      loadAllCharts();
    }
  }, [user?.role, userCodeAgence, dateFilter]);

  const handleRefreshAll = async () => {
    if (refreshing) return;
    
    setRefreshing(true);
    try {
      clearCache();
      setChartData({});
      await loadAllCharts();
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

  // Toggle view mode between chart and text
  const handleViewModeChange = (mode: 'chart' | 'text') => {
    setViewMode(mode);
  };

  if (!user) {
    return (
      <div className="py-6">
        <div className="text-center">
          <p className="text-secondary-600">Veuillez vous connecter pour accéder aux statistiques par source.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      {/* Header with gradient banner */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-8 border border-secondary-100">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2 flex items-center gap-3">
              <BarChart2 className="h-8 w-8 text-primary-600" />
              Statistiques par Source
            </h1>
            <p className="text-secondary-600">
              Analyse des performances par influenceur et source de trafic
            </p>
            {teamName && (
              <p className="text-secondary-500 text-sm mt-1">
                Équipe: <span className="font-semibold">{teamName}</span> ({userCodeAgence})
              </p>
            )}
            {/* Show date filter info if active */}
            {dateFilter.startDate && dateFilter.endDate && (
              <div className="mt-2 p-2 bg-blue-100 border border-blue-300 rounded-md inline-block">
                <p className="text-blue-800 text-sm flex items-center gap-2">
                  <Calendar className="h-4 w-4" />
                  <strong>Période: {dateFilter.label}</strong>
                </p>
              </div>
            )}
          </div>
          
          <div className="mt-4 md:mt-0 flex flex-col sm:flex-row gap-3">
            <div className="relative">
              <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <Search className="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                placeholder="Rechercher un graphique..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input pl-10 w-full sm:w-60 bg-white/80 backdrop-blur-sm"
              />
            </div>
            
            <DateFilter 
              onFilterChange={handleDateFilterChange}
              onReset={handleResetDateFilter}
            />
            
            <ViewModeToggle 
              viewMode={viewMode} 
              onChange={handleViewModeChange} 
            />
            
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

      {/* Source Statistics Description */}
      <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <h3 className="font-medium text-blue-900 mb-2 flex items-center gap-2">
          <Filter className="h-5 w-5 text-blue-600" />
          Analyse des sources de trafic
        </h3>
        <p className="text-blue-800 text-sm">
          Ces tableaux de bord vous permettent d'analyser les performances des différentes sources de trafic, 
          notamment les influenceurs, les publicités et les campagnes marketing. Vous pouvez identifier quelles 
          sources génèrent le plus de contacts, de commandes et de revenus.
        </p>
        <div className="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
          <div className="bg-white p-3 rounded-md border border-blue-100">
            <h4 className="font-medium text-blue-800 mb-1">Contacts et Conversion</h4>
            <p className="text-blue-700">
              Identifiez les sources qui génèrent le plus de contacts et celles qui ont les meilleurs taux de conversion.
            </p>
          </div>
          <div className="bg-white p-3 rounded-md border border-blue-100">
            <h4 className="font-medium text-blue-800 mb-1">Types de demandes</h4>
            <p className="text-blue-700">
              Analysez quels types de demandes sont générés par chaque source pour mieux cibler vos campagnes.
            </p>
          </div>
          <div className="bg-white p-3 rounded-md border border-blue-100">
            <h4 className="font-medium text-blue-800 mb-1">Valeur des commandes</h4>
            <p className="text-blue-700">
              Comparez le panier moyen par source pour identifier les plus rentables.
            </p>
          </div>
        </div>
      </div>

      {/* Charts Grid */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {filteredCharts.map((chart) => {
          const data = chartData[chart.component];
          const isLoading = loadingStates[chart.component];
          
          return (
            <div key={chart.component} className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div className="flex items-center justify-between mb-4">
                <div>
                  <h3 className="text-lg font-semibold text-secondary-800">{chart.title}</h3>
                  <p className="text-sm text-secondary-600 mb-2">{chart.description}</p>
                  
                  {/* Show date filter badge if active */}
                  {dateFilter.startDate && dateFilter.endDate && (
                    <div className="inline-block px-3 py-1 bg-blue-50 border border-blue-200 rounded-full">
                      <p className="text-xs text-blue-700 flex items-center gap-1">
                        <Calendar className="h-3 w-3" />
                        Période: {dateFilter.label}
                      </p>
                    </div>
                  )}
                </div>
                
                <ViewModeToggle 
                  viewMode={viewMode} 
                  onChange={handleViewModeChange} 
                />
              </div>
              
              {data ? (
                viewMode === 'chart' ? (
                  <DashboardChart
                    data={data}
                    type={chart.type}
                    loading={isLoading}
                    height={350}
                  />
                ) : (
                  <div className="p-4 border bg-white rounded">
                    <h3 className="font-medium text-gray-900 mb-3">{chart.title}</h3>
                    
                    {chart.type === 'pie' || chart.type === 'doughnut' ? (
                      // For pie/doughnut charts, show percentage distribution
                      <div className="space-y-2">
                        {data.labels.map((label: string, index: number) => {
                          const value = data.datasets[0].data[index];
                          const total = data.datasets[0].data.reduce((sum: number, val: number) => sum + val, 0);
                          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                          
                          return (
                            <div key={label} className="flex justify-between items-center">
                              <span>{label}</span>
                              <span className="font-medium">{value} ({percentage}%)</span>
                            </div>
                          );
                        })}
                        <div className="mt-3 pt-3 border-t border-gray-200">
                          <div className="flex justify-between font-medium">
                            <span>Total</span>
                            <span>
                              {data.datasets[0].data.reduce((sum: number, val: number) => sum + val, 0)}
                            </span>
                          </div>
                        </div>
                      </div>
                    ) : (
                      // For bar/line charts, show data for each dataset
                      <div className="space-y-4">
                        {data.datasets.map((dataset: any, datasetIndex: number) => (
                          <div key={datasetIndex}>
                            <p className="font-medium text-gray-800 mb-2">{dataset.label}</p>
                            <div className="space-y-2">
                              {data.labels.map((label: string, index: number) => (
                                <div key={`${datasetIndex}-${index}`} className="flex justify-between items-center">
                                  <span>{label}</span>
                                  <span className="font-medium">{dataset.data[index]}</span>
                                </div>
                              ))}
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                )
              ) : (
                <div className="h-[350px] flex items-center justify-center">
                  <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full"></div>
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* No Results Message */}
      {filteredCharts.length === 0 && (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <div className="text-secondary-500 mb-2">
            Aucun graphique trouvé
          </div>
          <p className="text-sm text-secondary-400">
            Essayez de modifier vos critères de recherche
          </p>
        </div>
      )}

      {/* Source Details Section */}
      {selectedSource && (
        <div className="mt-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
          <h3 className="text-lg font-semibold text-secondary-800 mb-4 flex items-center gap-2">
            <Users className="h-5 w-5 text-primary-600" />
            Détails de la source: {selectedSource}
          </h3>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* This would be populated with detailed stats for the selected source */}
            <div className="bg-gray-50 p-4 rounded-lg border border-gray-200">
              <p className="text-gray-500 text-center">
                Sélectionnez une source dans les graphiques ci-dessus pour voir ses détails
              </p>
            </div>
          </div>
        </div>
      )}

      {/* Usage Instructions */}
      <div className="mt-8 bg-gradient-to-r from-gray-50 to-blue-50 border border-gray-200 rounded-lg p-6">
        <h3 className="font-medium text-secondary-900 mb-4">
          Comment utiliser ces statistiques
        </h3>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
          <div className="space-y-2">
            <div className="flex items-start gap-2">
              <Target className="h-5 w-5 text-primary-600 mt-0.5" />
              <div>
                <p className="font-medium text-secondary-800">Identifier les meilleures sources</p>
                <p className="text-secondary-600">
                  Utilisez le graphique "Taux de conversion par source" pour identifier les sources qui convertissent le mieux.
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2">
              <ShoppingBag className="h-5 w-5 text-primary-600 mt-0.5" />
              <div>
                <p className="font-medium text-secondary-800">Optimiser le budget marketing</p>
                <p className="text-secondary-600">
                  Concentrez vos investissements sur les sources qui génèrent le plus de commandes et les paniers les plus élevés.
                </p>
              </div>
            </div>
          </div>
          <div className="space-y-2">
            <div className="flex items-start gap-2">
              <MessageCircle className="h-5 w-5 text-primary-600 mt-0.5" />
              <div>
                <p className="font-medium text-secondary-800">Adapter votre communication</p>
                <p className="text-secondary-600">
                  Analysez les types de demandes par source pour adapter votre message selon l'audience de chaque influenceur.
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2">
              <Activity className="h-5 w-5 text-primary-600 mt-0.5" />
              <div>
                <p className="font-medium text-secondary-800">Suivre l'évolution</p>
                <p className="text-secondary-600">
                  Utilisez les filtres de date pour comparer les performances sur différentes périodes et suivre l'évolution.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default SourceStatsTab;