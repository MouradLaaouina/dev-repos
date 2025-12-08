import React from 'react';
import { Users, ShoppingBag, TrendingUp, Target, DollarSign, BarChart3 } from 'lucide-react';

interface StatsViewProps {
  stats: {
    totalClients: number;
    totalOrders: number;
    totalRevenue: number;
    conversionRate: number;
    requestTypeBreakdown: {
      'Information': number;
      'En attente de traitement': number;
      'Orientation Para': number;
      'Sans réponse': number;
      'En attente de réponse': number;
      'Annulee': number;
    };
  };
}

const StatsView: React.FC<StatsViewProps> = ({ stats }) => {
  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
      {/* Total Clients */}
      <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
        <div className="p-3 rounded-full bg-blue-100 text-blue-700 mr-4">
          <Users className="h-6 w-6" />
        </div>
        <div>
          <p className="text-sm font-medium text-secondary-500">Total Clients</p>
          <p className="text-2xl font-bold text-secondary-900">{stats.totalClients}</p>
        </div>
      </div>

      {/* Total Orders */}
      <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
        <div className="p-3 rounded-full bg-green-100 text-green-700 mr-4">
          <ShoppingBag className="h-6 w-6" />
        </div>
        <div>
          <p className="text-sm font-medium text-secondary-500">Commandes</p>
          <p className="text-2xl font-bold text-secondary-900">{stats.totalOrders}</p>
        </div>
      </div>

      {/* Revenue */}
      <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
        <div className="p-3 rounded-full bg-yellow-100 text-yellow-700 mr-4">
          <DollarSign className="h-6 w-6" />
        </div>
        <div>
          <p className="text-sm font-medium text-secondary-500">Chiffre d'Affaires</p>
          <p className="text-2xl font-bold text-secondary-900">{stats.totalRevenue.toFixed(0)} DH</p>
        </div>
      </div>

      {/* Conversion Rate */}
      <div className="card flex items-center border border-gray-100 hover:border-primary-200 transition-colors duration-200">
        <div className="p-3 rounded-full bg-purple-100 text-purple-700 mr-4">
          <Target className="h-6 w-6" />
        </div>
        <div>
          <p className="text-sm font-medium text-secondary-500">Taux de Conversion</p>
          <p className="text-2xl font-bold text-secondary-900">{stats.conversionRate.toFixed(1)}%</p>
        </div>
      </div>

      {/* Request Type Breakdown */}
      <div className="card border border-gray-100 lg:col-span-4">
        <h3 className="text-lg font-semibold text-secondary-800 mb-4 flex items-center gap-2">
          <BarChart3 className="h-5 w-5 text-primary-600" />
          Répartition par type de demande
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <div className="text-center p-4 bg-blue-50 rounded-lg">
            <div className="text-2xl font-bold text-blue-600">{stats.requestTypeBreakdown.Information}</div>
            <div className="text-sm text-blue-700">Information</div>
          </div>
          <div className="text-center p-4 bg-yellow-50 rounded-lg">
            <div className="text-2xl font-bold text-yellow-600">{stats.requestTypeBreakdown['En attente de traitement']}</div>
            <div className="text-sm text-yellow-700">En attente</div>
          </div>
          <div className="text-center p-4 bg-purple-50 rounded-lg">
            <div className="text-2xl font-bold text-purple-600">{stats.requestTypeBreakdown['Orientation Para']}</div>
            <div className="text-sm text-purple-700">Orientation</div>
          </div>
          <div className="text-center p-4 bg-red-50 rounded-lg">
            <div className="text-2xl font-bold text-red-600">{stats.requestTypeBreakdown['Sans réponse']}</div>
            <div className="text-sm text-red-700">Sans réponse</div>
          </div>
          <div className="text-center p-4 bg-orange-50 rounded-lg">
            <div className="text-2xl font-bold text-orange-600">{stats.requestTypeBreakdown['En attente de réponse']}</div>
            <div className="text-sm text-orange-700">En attente réponse</div>
          </div>
          <div className="text-center p-4 bg-gray-50 rounded-lg">
            <div className="text-2xl font-bold text-gray-600">{stats.requestTypeBreakdown.Annulee}</div>
            <div className="text-sm text-gray-700">Annulée</div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default StatsView;