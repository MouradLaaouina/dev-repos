import React from 'react';
import { Users, ShoppingBag, TrendingUp, Target, DollarSign, BarChart3, ArrowUpRight } from 'lucide-react';
import { cn } from '../../utils/cn';

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
  const mainStats = [
    { label: 'Clients total', value: stats.totalClients, icon: Users, color: 'text-blue-600', bg: 'bg-blue-50' },
    { label: 'Commandes', value: stats.totalOrders, icon: ShoppingBag, color: 'text-green-600', bg: 'bg-green-50' },
    { label: 'Chiffre d\'Affaires', value: `${stats.totalRevenue.toFixed(0)} DH`, icon: DollarSign, color: 'text-amber-600', bg: 'bg-amber-50' },
    { label: 'Conversion', value: `${stats.conversionRate.toFixed(1)}%`, icon: Target, color: 'text-indigo-600', bg: 'bg-indigo-50' },
  ];

  return (
    <div className="space-y-6 mb-8">
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {mainStats.map((stat, idx) => (
          <div key={idx} className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
            <div className="flex justify-between items-start mb-4">
              <div className={cn("p-3 rounded-xl", stat.bg)}>
                <stat.icon className={cn("h-6 w-6", stat.color)} />
              </div>
              <div className="flex items-center text-xs font-bold text-success-600 bg-success-50 px-2 py-1 rounded-lg">
                <ArrowUpRight className="h-3 w-3 mr-1" />
                <span>+0%</span>
              </div>
            </div>
            <div>
              <p className="text-sm font-medium text-secondary-500 mb-1">{stat.label}</p>
              <p className="text-2xl font-bold text-secondary-900 group-hover:text-primary-600 transition-colors">{stat.value}</p>
            </div>
          </div>
        ))}
      </div>

      <div className="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm">
        <h3 className="text-sm font-bold text-secondary-900 mb-6 flex items-center gap-2 uppercase tracking-wider">
          <BarChart3 className="h-4 w-4 text-primary-600" />
          Répartition de l'activité
        </h3>
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          {[
            { label: 'Information', value: stats.requestTypeBreakdown.Information, color: 'bg-blue-500' },
            { label: 'En attente', value: stats.requestTypeBreakdown['En attente de traitement'], color: 'bg-amber-500' },
            { label: 'Orientation', value: stats.requestTypeBreakdown['Orientation Para'], color: 'bg-indigo-500' },
            { label: 'Sans réponse', value: stats.requestTypeBreakdown['Sans réponse'], color: 'bg-rose-500' },
            { label: 'En attente rép.', value: stats.requestTypeBreakdown['En attente de réponse'], color: 'bg-orange-500' },
            { label: 'Annulée', value: stats.requestTypeBreakdown.Annulee, color: 'bg-secondary-400' },
          ].map((item, idx) => (
            <div key={idx} className="bg-secondary-50 rounded-xl p-4 flex flex-col items-center justify-center border border-secondary-100/50">
              <div className={cn("w-1.5 h-1.5 rounded-full mb-2", item.color)}></div>
              <div className="text-xl font-bold text-secondary-900">{item.value}</div>
              <div className="text-[10px] font-bold text-secondary-500 uppercase tracking-tighter mt-1">{item.label}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};

export default StatsView;
