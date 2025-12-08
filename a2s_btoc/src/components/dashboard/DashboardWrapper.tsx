import React, { useState, useEffect } from 'react';
import { useAuthStore } from '../../store/authStore';
import { useDashboardStore } from '../../store/dashboardStore';
import ChartContainer from './ChartContainer';
import { DateRange } from '../common/DateFilter';

interface DashboardWrapperProps {
  component: string;
  title: string;
  description?: string;
  type?: 'bar' | 'line' | 'pie' | 'doughnut';
  userRole?: string;
  userId?: string;
  userCodeAgence?: string;
  filter?: string;
  startDate?: Date | null;
  endDate?: Date | null;
  dateLabel?: string;
}

const DashboardWrapper: React.FC<DashboardWrapperProps> = ({
  component,
  title,
  description,
  type = 'bar',
  userRole,
  userId,
  userCodeAgence,
  filter,
  startDate,
  endDate,
  dateLabel
}) => {
  const user = useAuthStore((state) => state.user);
  const { fetchDashboardData } = useDashboardStore();
  const [data, setData] = useState<any>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [viewMode, setViewMode] = useState<'chart' | 'text'>('chart');

  const loadData = async () => {
    setLoading(true);
    setError(null);
    
    try {
      const result = await fetchDashboardData(
        component,
        userRole || user?.role || 'agent',
        userId || user?.id,
        userCodeAgence || user?.codeAgence,
        startDate,
        endDate
      );
      
      setData(result);
    } catch (err) {
      console.error(`Error loading ${component}:`, err);
      setError(`Failed to load ${title}`);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (user) {
      loadData();
    }
  }, [component, user, userRole, userId, userCodeAgence, startDate, endDate]);

  if (error) {
    return (
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 className="text-lg font-semibold text-secondary-800 mb-2">{title}</h3>
        <div className="p-4 bg-red-50 text-red-700 rounded-md">
          {error}
        </div>
      </div>
    );
  }

  return (
    <ChartContainer
      title={title}
      description={description}
      component={component}
      data={data}
      type={type}
      loading={loading}
      onRefresh={loadData}
      dateFilter={startDate && endDate ? { startDate, endDate, label: dateLabel || '' } : undefined}
      viewMode={viewMode}
      onViewModeChange={setViewMode}
    />
  );
};

export default DashboardWrapper;