import React, { useEffect, useRef } from 'react';
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  TimeScale
} from 'chart.js';
import { Bar, Line, Pie, Doughnut } from 'react-chartjs-2';
import { DashboardData } from '../../types';

// Register Chart.js components
ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement,
  ArcElement,
  Title,
  Tooltip,
  Legend,
  Filler,
  TimeScale
);

interface DashboardChartProps {
  title?: string;
  description?: string;
  data: DashboardData;
  type?: 'bar' | 'line' | 'pie' | 'doughnut';
  loading?: boolean;
  height?: number;
}

const DashboardChart: React.FC<DashboardChartProps> = ({
  title,
  description,
  data,
  type = 'bar',
  loading = false,
  height = 300
}) => {
  const chartRef = useRef<ChartJS>(null);

  // Cleanup chart on unmount
  useEffect(() => {
    return () => {
      if (chartRef.current) {
        chartRef.current.destroy();
      }
    };
  }, []);

  if (loading) {
    return (
      <div className="p-6" style={{ height }}>
        <div className="animate-pulse">
          <div className="h-4 bg-gray-200 rounded w-3/4 mb-2"></div>
          <div className="h-3 bg-gray-200 rounded w-1/2 mb-4"></div>
          <div className="h-64 bg-gray-200 rounded"></div>
        </div>
      </div>
    );
  }

  // Common chart options
  const commonOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'top' as const,
        labels: {
          usePointStyle: true,
          padding: 20,
          font: {
            size: 12,
            family: 'Inter, sans-serif'
          }
        }
      },
      tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.8)',
        titleColor: 'white',
        bodyColor: 'white',
        borderColor: 'rgba(255, 255, 255, 0.1)',
        borderWidth: 1,
        cornerRadius: 8,
        displayColors: true,
        titleFont: {
          size: 14,
          weight: 'bold' as const
        },
        bodyFont: {
          size: 13
        },
        padding: 12
      }
    }
  };

  // Chart-specific options
  const getChartOptions = () => {
    switch (type) {
      case 'bar':
        return {
          ...commonOptions,
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                maxRotation: 45,
                minRotation: 0,
                font: {
                  size: 11
                },
                callback: function(value: any, index: number) {
                  const label = data.labels[index];
                  // Truncate long labels
                  return label && label.length > 15 ? label.substring(0, 15) + '...' : label;
                }
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                font: {
                  size: 11
                }
              }
            }
          },
          plugins: {
            ...commonOptions.plugins,
            tooltip: {
              ...commonOptions.plugins.tooltip,
              callbacks: {
                title: function(context: any) {
                  return data.labels[context[0].dataIndex];
                },
                label: function(context: any) {
                  const label = context.dataset.label || '';
                  const value = context.parsed.y;
                  return `${label}: ${value.toLocaleString()}`;
                }
              }
            }
          }
        };

      case 'line':
        return {
          ...commonOptions,
          scales: {
            x: {
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                maxRotation: 45,
                minRotation: 0,
                font: {
                  size: 11
                },
                callback: function(value: any, index: number) {
                  const label = data.labels[index];
                  return label && label.length > 10 ? label.substring(0, 10) + '...' : label;
                }
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                font: {
                  size: 11
                }
              }
            }
          },
          elements: {
            point: {
              radius: 4,
              hoverRadius: 6
            },
            line: {
              tension: 0.3
            }
          },
          plugins: {
            ...commonOptions.plugins,
            tooltip: {
              ...commonOptions.plugins.tooltip,
              callbacks: {
                title: function(context: any) {
                  return data.labels[context[0].dataIndex];
                },
                label: function(context: any) {
                  const label = context.dataset.label || '';
                  const value = context.parsed.y;
                  return `${label}: ${value.toLocaleString()}`;
                }
              }
            }
          }
        };

      case 'pie':
      case 'doughnut':
        return {
          ...commonOptions,
          plugins: {
            ...commonOptions.plugins,
            legend: {
              position: 'right' as const,
              labels: {
                usePointStyle: true,
                padding: 15,
                font: {
                  size: 12,
                  family: 'Inter, sans-serif'
                },
                generateLabels: function(chart: any) {
                  const data = chart.data;
                  if (data.labels.length && data.datasets.length) {
                    const dataset = data.datasets[0];
                    const total = dataset.data.reduce((sum: number, value: number) => sum + value, 0);
                    
                    return data.labels.map((label: string, index: number) => {
                      const value = dataset.data[index];
                      const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                      const color = Array.isArray(dataset.backgroundColor) 
                        ? dataset.backgroundColor[index] 
                        : dataset.backgroundColor;
                      
                      return {
                        text: `${label}: ${value} (${percentage}%)`,
                        fillStyle: color,
                        strokeStyle: color,
                        lineWidth: 0,
                        pointStyle: 'circle',
                        hidden: false,
                        index: index
                      };
                    });
                  }
                  return [];
                }
              }
            },
            tooltip: {
              ...commonOptions.plugins.tooltip,
              callbacks: {
                label: function(context: any) {
                  const label = data.labels[context.dataIndex];
                  const value = context.parsed;
                  const total = context.dataset.data.reduce((sum: number, val: number) => sum + val, 0);
                  const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : '0';
                  return `${label}: ${value.toLocaleString()} (${percentage}%)`;
                }
              }
            }
          }
        };

      default:
        return commonOptions;
    }
  };

  // Enhanced color palettes
  const getEnhancedColors = (count: number) => {
    const colors = [
      '#3B82F6', // Blue
      '#10B981', // Green
      '#F59E0B', // Amber
      '#EF4444', // Red
      '#8B5CF6', // Purple
      '#06B6D4', // Cyan
      '#EC4899', // Pink
      '#84CC16', // Lime
      '#F97316', // Orange
      '#6366F1', // Indigo
      '#14B8A6', // Teal
      '#F43F5E', // Rose
    ];
    
    // If we need more colors than available, generate variations
    if (count > colors.length) {
      const extraColors = [];
      for (let i = 0; i < count - colors.length; i++) {
        const baseColor = colors[i % colors.length];
        // Create lighter/darker variations
        extraColors.push(baseColor + '80'); // Add transparency
      }
      return [...colors, ...extraColors];
    }
    
    return colors.slice(0, count);
  };

  // Enhance the data with better colors and formatting
  const enhancedData = {
    ...data,
    datasets: data.datasets.map((dataset, index) => ({
      ...dataset,
      backgroundColor: dataset.backgroundColor || 
        (type === 'pie' || type === 'doughnut' 
          ? getEnhancedColors(data.labels.length)
          : getEnhancedColors(data.datasets.length)[index]),
      borderColor: dataset.borderColor || 
        (type === 'line' 
          ? getEnhancedColors(data.datasets.length)[index]
          : undefined),
      borderWidth: dataset.borderWidth || (type === 'bar' ? 0 : 2),
      borderRadius: type === 'bar' ? 4 : undefined,
      tension: type === 'line' ? 0.3 : undefined,
      fill: dataset.fill !== undefined ? dataset.fill : (type === 'line' ? false : undefined),
      pointBackgroundColor: type === 'line' ? (dataset.borderColor || getEnhancedColors(data.datasets.length)[index]) : undefined,
      pointBorderColor: type === 'line' ? '#ffffff' : undefined,
      pointBorderWidth: type === 'line' ? 2 : undefined,
      pointRadius: type === 'line' ? 4 : undefined,
      pointHoverRadius: type === 'line' ? 6 : undefined,
    }))
  };

  const renderChart = () => {
    const chartOptions = getChartOptions();
    const chartProps = {
      ref: chartRef,
      data: enhancedData,
      options: chartOptions,
      height: height - 60
    };

    switch (type) {
      case 'bar':
        return <Bar {...chartProps} />;
      case 'line':
        return <Line {...chartProps} />;
      case 'pie':
        return <Pie {...chartProps} />;
      case 'doughnut':
        return <Doughnut {...chartProps} />;
      default:
        return <Bar {...chartProps} />;
    }
  };

  return (
    <div className="w-full">
      {title && (
        <div className="mb-4">
          <h3 className="text-lg font-semibold text-secondary-800">{title}</h3>
          {description && (
            <p className="text-sm text-secondary-600 mt-1">{description}</p>
          )}
        </div>
      )}

      <div className="relative bg-white rounded-lg p-4" style={{ height: height - 60 }}>
        {data.labels.length === 0 || data.datasets.length === 0 || 
         (data.labels.length === 1 && data.labels[0] === 'Pas de données') ? (
          <div className="flex items-center justify-center h-full text-secondary-500">
            <div className="text-center">
              <div className="text-4xl mb-2">📊</div>
              <p>Aucune donnée disponible</p>
            </div>
          </div>
        ) : (
          renderChart()
        )}
      </div>

      {/* Data summary for accessibility */}
      {data.labels.length > 0 && (
        <div className="mt-3 text-xs text-secondary-500">
          {data.datasets.length} série{data.datasets.length > 1 ? 's' : ''} • {data.labels.length} point{data.labels.length > 1 ? 's' : ''} de données
        </div>
      )}
    </div>
  );
}

export default DashboardChart;