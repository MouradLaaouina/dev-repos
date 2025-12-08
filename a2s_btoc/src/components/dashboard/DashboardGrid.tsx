import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../../store/authStore';
import { useDashboardStore } from '../../store/dashboardStore';
import { getAccessibleDashboards, getTeamName } from '../../config/dashboardConfig';
import { DashboardData } from '../../types/dashboard';
import DashboardChart from './DashboardChart';
import { BarChart3, TrendingUp, Users, MessageSquare, Phone, ShoppingCart, Target, PieChart } from 'lucide-react';

const DashboardGrid: React.FC = () => {
  const user = useAuthStore((state) => state.user);
  const dashboardStore = useDashboardStore();
  const [chartData, setChartData] = useState<{ [key: string]: DashboardData }>({});
  const [loading, setLoading] = useState<{ [key: string]: boolean }>({});

  const accessibleDashboards = user ? getAccessibleDashboards(user.role) : [];
  const userCodeAgence = user?.codeAgence;
  const teamName = userCodeAgence ? getTeamName(userCodeAgence) : '';

  const loadChartData = async (component: string, filter?: string) => {
    const key = `${component}_${filter || 'global'}`;
    setLoading(prev => ({ ...prev, [key]: true }));

    try {
      let data: DashboardData;
      const codeAgence = filter === 'user.code_agence' ? userCodeAgence : undefined;

      switch (component) {
        case 'MessagesEvolutionChart':
          await dashboardStore.fetchMessagesEvolution(codeAgence);
          data = dashboardStore.messagesEvolution!;
          break;
        case 'OrdersEvolutionByAgentChart':
          await dashboardStore.fetchOrdersEvolution(codeAgence);
          data = dashboardStore.ordersEvolution!;
          break;
        case 'MessagesInfluencersChart':
          data = await dashboardStore.fetchMessagesInfluencers(codeAgence);
          break;
        case 'OrdersInfluencersChart':
          data = await dashboardStore.fetchOrdersInfluencers(codeAgence);
          break;
        case 'MessagesAgentChart':
          data = await dashboardStore.fetchMessagesAgent(userCodeAgence!);
          break;
        case 'OrdersAgentChart':
          data = await dashboardStore.fetchOrdersAgent(userCodeAgence!);
          break;
        case 'PhonesAgentChart':
          data = await dashboardStore.fetchPhonesAgent(userCodeAgence!);
          break;
        case 'RequestTypesAgentChart':
          data = await dashboardStore.fetchRequestTypesAgent(userCodeAgence!);
          break;
        case 'RequestTypesInfluencersChart':
          data = await dashboardStore.fetchRequestTypesInfluencers();
          break;
        case 'CallsPerAgentChart':
          data = await dashboardStore.fetchCallsPerAgent(userCodeAgence!);
          break;
        case 'OrdersPerAgentChart':
          data = await dashboardStore.fetchOrdersPerAgent(userCodeAgence!);
          break;
        case 'CallDetailsChart':
          data = await dashboardStore.fetchCallDetails(userCodeAgence!);
          break;
        case 'OrdersByTeamChart':
          data = await dashboardStore.fetchOrdersByTeam();
          break;
        case 'MessagesByTeamChart':
          data = await dashboardStore.fetchMessagesByTeam();
          break;
        case 'AverageBasketChart':
          data = await dashboardStore.fetchAverageBasket();
          break;
        case 'ConversionRateChart':
          data = await dashboardStore.fetchConversionRate();
          break;
        default:
          throw new Error(`Unknown component: ${component}`);
      }

      setChartData(prev => ({ ...prev, [key]: data }));
    } catch (error) {
      console.error(`Error loading ${component}:`, error);
    } finally {
      setLoading(prev => ({ ...prev, [key]: false }));
    }
  };

  useEffect(() => {
    // Load all accessible dashboards
    accessibleDashboards.forEach(dashboard => {
      loadChartData(dashboard.component, dashboard.filter);
    });
  }, [user?.role, userCodeAgence]);

  const getChartType = (component: string): 'bar' | 'line' | 'pie' | 'doughnut' => {
    if (component.includes('Evolution')) return 'line';
    if (component.includes('Influencers') || component.includes('ByTeam')) return 'pie';
    if (component.includes('Details')) return 'bar';
    return 'bar';
  };

  const getChartIcon = (component: string) => {
    if (component.includes('Messages')) return MessageSquare;
    if (component.includes('Orders')) return ShoppingCart;
    if (component.includes('Calls')) return Phone;
    if (component.includes('Conversion')) return Target;
    if (component.includes('Evolution')) return TrendingUp;
    if (component.includes('Influencers')) return Users;
    if (component.includes('Basket')) return PieChart;
    return BarChart3;
  };

  if (!user) {
    return (
      <div className="py-6">
        <div className="text-center">
          <p className="text-secondary-600">Veuillez vous connecter pour accéder aux tableaux de bord.</p>
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      {/* Header */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-8 border border-secondary-100">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2 flex items-center gap-3">
              <BarChart3 className="h-8 w-8 text-primary-600" />
              Tableaux de Bord Avancés
            </h1>
            <p className="text-secondary-600">
              Analyses détaillées basées sur votre rôle: <span className="font-semibold capitalize">{user.role}</span>
            </p>
            {teamName && (
              <p className="text-secondary-500 text-sm mt-1">
                Équipe: <span className="font-semibold">{teamName}</span> ({userCodeAgence})
              </p>
            )}
          </div>
          <div className="text-right">
            <div className="text-sm text-secondary-500">
              {accessibleDashboards.length} tableau{accessibleDashboards.length > 1 ? 'x' : ''} disponible{accessibleDashboards.length > 1 ? 's' : ''}
            </div>
            <div className="text-xs text-secondary-400 mt-1">
              Accès: {user.role === 'admin' ? 'Complet' : 'Filtré par équipe'}
            </div>
          </div>
        </div>
      </div>

      {/* Dashboard Grid */}
      {accessibleDashboards.length === 0 ? (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <div className="text-secondary-500 mb-2">
            Aucun tableau de bord disponible
          </div>
          <p className="text-sm text-secondary-400">
            Votre rôle ne donne accès à aucun tableau de bord pour le moment.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {accessibleDashboards.map((dashboard) => {
            const key = `${dashboard.component}_${dashboard.filter || 'global'}`;
            const data = chartData[key];
            const isLoading = loading[key];
            const Icon = getChartIcon(dashboard.component);
            const chartType = getChartType(dashboard.component);

            return (
              <div key={dashboard.component} className="relative">
                {/* Dashboard Header */}
                <div className="mb-4 flex items-start gap-3">
                  <div className="p-2 rounded-lg bg-primary-100 text-primary-700">
                    <Icon className="h-5 w-5" />
                  </div>
                  <div className="flex-1">
                    <h3 className="font-semibold text-secondary-800">{dashboard.title}</h3>
                    {dashboard.description && (
                      <p className="text-sm text-secondary-600 mt-1">{dashboard.description}</p>
                    )}
                    {dashboard.filter === 'user.code_agence' && teamName && (
                      <div className="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">
                        Filtré: {teamName}
                      </div>
                    )}
                  </div>
                </div>

                {/* Chart */}
                {data ? (
                  <DashboardChart
                    title=""
                    data={data}
                    type={chartType}
                    loading={isLoading}
                  />
                ) : (
                  <div className="card border border-gray-100 p-6">
                    <div className="animate-pulse">
                      <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
                      <div className="h-3 bg-gray-200 rounded w-1/2 mb-4"></div>
                      <div className="h-64 bg-gray-200 rounded"></div>
                    </div>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Role Information */}
      <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
        <h3 className="font-medium text-blue-900 mb-2">Informations d'accès</h3>
        <div className="text-sm text-blue-800 space-y-1">
          <p><strong>Rôle:</strong> {user.role}</p>
          {userCodeAgence && <p><strong>Équipe:</strong> {teamName} ({userCodeAgence})</p>}
          <p><strong>Tableaux accessibles:</strong> {accessibleDashboards.length}</p>
          <p><strong>Filtrage:</strong> {user.role === 'admin' ? 'Données globales' : 'Données de votre équipe uniquement'}</p>
        </div>
      </div>
    </div>
  );
};

export default DashboardGrid;