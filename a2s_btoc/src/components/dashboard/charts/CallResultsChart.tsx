import React from 'react';
import { Phone, MessageSquare, CheckCircle, XCircle, Clock } from 'lucide-react';

interface CallResultsChartProps {
  data: any;
  viewMode?: 'chart' | 'text';
}

const CallResultsChart: React.FC<CallResultsChartProps> = ({ data, viewMode = 'chart' }) => {
  // Check if data is empty or not available
  const hasData = data && data.labels && data.labels.length > 0 && 
                 data.labels[0] !== 'Pas de données';

  // If text view is selected, render a simple text representation
  if (viewMode === 'text') {
    if (!hasData) {
      return (
        <div className="p-4 border bg-gray-50 rounded text-gray-700">
          <p className="font-medium mb-2">Résultats des appels</p>
          <p>Aucune donnée disponible pour le moment.</p>
        </div>
      );
    }

    return (
      <div className="p-4 border bg-white rounded">
        <h3 className="font-medium text-gray-900 mb-3">Résultats des appels</h3>
        <div className="space-y-2">
          {data.labels.map((label: string, index: number) => (
            <div key={label} className="flex justify-between items-center">
              <div className="flex items-center gap-2">
                {label === 'À rappeler' && <Clock className="h-4 w-4 text-yellow-500" />}
                {label === 'Pas intéressé(e)' && <XCircle className="h-4 w-4 text-red-500" />}
                {label === 'Intéressé(e)' && <CheckCircle className="h-4 w-4 text-green-500" />}
                {label === 'Ne réponds jamais' && <Phone className="h-4 w-4 text-gray-500" />}
                {label === 'Faux numéro' && <MessageSquare className="h-4 w-4 text-orange-500" />}
                <span>{label}</span>
              </div>
              <span className="font-medium">{data.datasets[0].data[index]}</span>
            </div>
          ))}
        </div>
      </div>
    );
  }

  // Default chart view - placeholder with styled representation
  return (
    <div className="p-4 border bg-white rounded">
      <div className="flex items-center justify-between mb-4">
        <h3 className="font-medium text-gray-900">Résultats des appels</h3>
        <div className="text-xs text-gray-500">Centre d'appel</div>
      </div>
      
      {!hasData ? (
        <div className="p-6 bg-gray-50 rounded text-center text-gray-500">
          <Phone className="h-8 w-8 mx-auto mb-2 text-gray-400" />
          <p>Aucune donnée disponible pour le moment.</p>
          <p className="text-xs mt-1">Les résultats d'appels s'afficheront ici une fois disponibles.</p>
        </div>
      ) : (
        <div className="space-y-3">
          {data.labels.map((label: string, index: number) => {
            const value = data.datasets[0].data[index];
            const maxValue = Math.max(...data.datasets[0].data);
            const percentage = maxValue > 0 ? (value / maxValue) * 100 : 0;
            
            let bgColor = '';
            let textColor = '';
            let icon = null;
            
            switch (label) {
              case 'À rappeler':
                bgColor = 'bg-yellow-100';
                textColor = 'text-yellow-800';
                icon = <Clock className="h-4 w-4 text-yellow-500" />;
                break;
              case 'Pas intéressé(e)':
                bgColor = 'bg-red-100';
                textColor = 'text-red-800';
                icon = <XCircle className="h-4 w-4 text-red-500" />;
                break;
              case 'Intéressé(e)':
                bgColor = 'bg-green-100';
                textColor = 'text-green-800';
                icon = <CheckCircle className="h-4 w-4 text-green-500" />;
                break;
              case 'Ne réponds jamais':
                bgColor = 'bg-gray-100';
                textColor = 'text-gray-800';
                icon = <Phone className="h-4 w-4 text-gray-500" />;
                break;
              case 'Faux numéro':
                bgColor = 'bg-orange-100';
                textColor = 'text-orange-800';
                icon = <MessageSquare className="h-4 w-4 text-orange-500" />;
                break;
              default:
                bgColor = 'bg-blue-100';
                textColor = 'text-blue-800';
                icon = <Phone className="h-4 w-4 text-blue-500" />;
            }
            
            return (
              <div key={label} className="relative">
                <div className="flex items-center justify-between mb-1">
                  <div className="flex items-center gap-2">
                    {icon}
                    <span className={`text-sm ${textColor}`}>{label}</span>
                  </div>
                  <span className="text-sm font-medium">{value}</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5">
                  <div 
                    className={`${bgColor} h-2.5 rounded-full`} 
                    style={{ width: `${percentage}%` }}
                  ></div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

export default CallResultsChart;