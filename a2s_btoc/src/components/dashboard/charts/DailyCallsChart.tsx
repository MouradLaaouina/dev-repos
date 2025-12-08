import React from 'react';
import { Phone, TrendingUp, Calendar } from 'lucide-react';

interface DailyCallsChartProps {
  data: any;
  viewMode?: 'chart' | 'text';
}

const DailyCallsChart: React.FC<DailyCallsChartProps> = ({ data, viewMode = 'chart' }) => {
  // Check if data is empty or not available
  const hasData = data && data.labels && data.labels.length > 0 && 
                 data.labels[0] !== 'Pas de données';

  // If text view is selected, render a simple text representation
  if (viewMode === 'text') {
    if (!hasData) {
      return (
        <div className="p-4 border bg-gray-50 rounded text-gray-700">
          <p className="font-medium mb-2">Appels par jour</p>
          <p>Aucune donnée disponible pour le moment.</p>
        </div>
      );
    }

    // Find the day with most calls
    const maxCallsIndex = data.datasets[0].data.indexOf(Math.max(...data.datasets[0].data));
    const maxCallsDay = data.labels[maxCallsIndex];
    const maxCallsValue = data.datasets[0].data[maxCallsIndex];

    // Calculate average calls per day
    const totalCalls = data.datasets[0].data.reduce((sum: number, value: number) => sum + value, 0);
    const averageCalls = totalCalls / data.datasets[0].data.length;

    return (
      <div className="p-4 border bg-white rounded">
        <h3 className="font-medium text-gray-900 mb-3">Appels par jour</h3>
        
        <div className="space-y-4">
          <div className="bg-blue-50 p-3 rounded-md">
            <div className="flex justify-between items-center">
              <span className="text-blue-800">Jour le plus actif:</span>
              <span className="font-medium text-blue-900">{maxCallsDay} ({maxCallsValue} appels)</span>
            </div>
          </div>
          
          <div className="bg-green-50 p-3 rounded-md">
            <div className="flex justify-between items-center">
              <span className="text-green-800">Moyenne quotidienne:</span>
              <span className="font-medium text-green-900">{averageCalls.toFixed(1)} appels</span>
            </div>
          </div>
          
          <div className="bg-gray-50 p-3 rounded-md">
            <div className="flex justify-between items-center">
              <span className="text-gray-800">Total sur la période:</span>
              <span className="font-medium text-gray-900">{totalCalls} appels</span>
            </div>
          </div>
          
          <div className="text-xs text-gray-500 mt-2">
            Période: {data.labels[0]} à {data.labels[data.labels.length - 1]}
          </div>
        </div>
      </div>
    );
  }

  // Default chart view - placeholder with styled representation
  return (
    <div className="p-4 border bg-white rounded">
      <div className="flex items-center justify-between mb-4">
        <h3 className="font-medium text-gray-900">Appels par jour</h3>
        <div className="text-xs text-gray-500">Centre d'appel</div>
      </div>
      
      {!hasData ? (
        <div className="p-6 bg-gray-50 rounded text-center text-gray-500">
          <Phone className="h-8 w-8 mx-auto mb-2 text-gray-400" />
          <p>Aucune donnée disponible pour le moment.</p>
          <p className="text-xs mt-1">Les statistiques d'appels quotidiens s'afficheront ici une fois disponibles.</p>
        </div>
      ) : (
        <div>
          {/* Stylized line chart representation */}
          <div className="relative h-40 mt-4">
            <div className="absolute inset-0 flex items-end">
              {data.datasets[0].data.map((value: number, index: number) => {
                const maxValue = Math.max(...data.datasets[0].data);
                const height = maxValue > 0 ? (value / maxValue) * 100 : 0;
                
                return (
                  <div 
                    key={index} 
                    className="flex-1 mx-0.5 flex flex-col items-center"
                  >
                    <div 
                      className="w-full bg-blue-500 rounded-t"
                      style={{ height: `${height}%` }}
                    ></div>
                    {index % 3 === 0 && (
                      <div className="text-xs text-gray-500 mt-1 transform -rotate-45 origin-top-left">
                        {data.labels[index]}
                      </div>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
          
          <div className="flex justify-between items-center mt-8 pt-4 border-t border-gray-200">
            <div className="flex items-center gap-2 text-sm text-gray-600">
              <Calendar className="h-4 w-4" />
              <span>Période: {data.labels[0]} à {data.labels[data.labels.length - 1]}</span>
            </div>
            
            <div className="flex items-center gap-2 text-sm text-blue-600">
              <TrendingUp className="h-4 w-4" />
              <span>
                {data.datasets[0].data.reduce((sum: number, value: number) => sum + value, 0)} appels au total
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default DailyCallsChart;