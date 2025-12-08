import React, { useState } from 'react';
import { BarChart2, RefreshCw, Calendar } from 'lucide-react';
import DashboardChart from './DashboardChart';
import ViewModeToggle from './ViewModeToggle';
import CallResultsChart from './charts/CallResultsChart';
import DailyCallsChart from './charts/DailyCallsChart';

interface ChartContainerProps {
  title: string;
  description?: string;
  component: string;
  data: any;
  type?: 'bar' | 'line' | 'pie' | 'doughnut';
  loading?: boolean;
  height?: number;
  onRefresh?: () => void;
  dateFilter?: {
    startDate: Date | null;
    endDate: Date | null;
    label: string;
  };
  viewMode?: 'chart' | 'text';
  onViewModeChange?: (mode: 'chart' | 'text') => void;
}

const ChartContainer: React.FC<ChartContainerProps> = ({
  title,
  description,
  component,
  data,
  type = 'bar',
  loading = false,
  height = 300,
  onRefresh,
  dateFilter,
  viewMode = 'chart',
  onViewModeChange
}) => {
  const [localViewMode, setLocalViewMode] = useState<'chart' | 'text'>(viewMode);
  
  // Use either the prop-based view mode or the local state
  const currentViewMode = onViewModeChange ? viewMode : localViewMode;
  
  const handleViewModeChange = (mode: 'chart' | 'text') => {
    if (onViewModeChange) {
      onViewModeChange(mode);
    } else {
      setLocalViewMode(mode);
    }
  };

  // Render the appropriate chart component based on the component name
  const renderChart = () => {
    if (loading) {
      return (
        <div className="h-[250px] flex items-center justify-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full"></div>
        </div>
      );
    }

    if (component === 'CallResultsChart') {
      return <CallResultsChart data={data} viewMode={currentViewMode} />;
    }

    if (component === 'DailyCallsChart') {
      return <DailyCallsChart data={data} viewMode={currentViewMode} />;
    }

    // If in text mode, render a generic text representation
    if (currentViewMode === 'text') {
      return renderTextView();
    }

    // Default to DashboardChart for other components
    return (
      <DashboardChart
        data={data}
        type={type}
        loading={loading}
        height={height - 60}
      />
    );
  };

  // Generic text representation for charts
  const renderTextView = () => {
    // Check if data is empty or not available
    if (!data || !data.labels || data.labels.length === 0 || 
        data.labels[0] === 'Pas de données' || !data.datasets || data.datasets.length === 0) {
      return (
        <div className="p-4 border bg-gray-50 rounded text-gray-700">
          <p className="font-medium mb-2">{title}</p>
          <p>Aucune donnée disponible pour le moment.</p>
        </div>
      );
    }

    return (
      <div className="p-4 border bg-white rounded">
        <h3 className="font-medium text-gray-900 mb-3">{title}</h3>
        
        {type === 'pie' || type === 'doughnut' ? (
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
    );
  };

  return (
    <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div className="flex items-start justify-between mb-4">
        <div>
          <h3 className="text-lg font-semibold text-secondary-800">{title}</h3>
          {description && (
            <p className="text-sm text-secondary-600 mt-1">{description}</p>
          )}
          
          {/* Show date filter badge if active */}
          {dateFilter?.startDate && dateFilter?.endDate && (
            <div className="mt-2 inline-block px-3 py-1 bg-blue-50 border border-blue-200 rounded-full">
              <p className="text-xs text-blue-700 flex items-center gap-1">
                <Calendar className="h-3 w-3" />
                Période: {dateFilter.label}
              </p>
            </div>
          )}
        </div>
        
        <div className="flex items-center gap-2">
          <ViewModeToggle 
            viewMode={currentViewMode} 
            onChange={handleViewModeChange} 
          />
          
          {onRefresh && (
            <button
              onClick={onRefresh}
              className="p-1 text-gray-400 hover:text-gray-600 rounded-full hover:bg-gray-100"
              title="Actualiser"
            >
              <RefreshCw className="h-4 w-4" />
            </button>
          )}
        </div>
      </div>
      
      {renderChart()}
    </div>
  );
};

export default ChartContainer;