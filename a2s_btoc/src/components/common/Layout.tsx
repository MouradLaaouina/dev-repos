import React, { useState, useEffect } from 'react';
import { Outlet, Navigate, useLocation } from 'react-router-dom';
import Header from './Header';
import Sidebar from './Sidebar';
import { useAuthStore } from '../../store/authStore';

const Layout: React.FC = () => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const { isAuthenticated, loading, checkAuth } = useAuthStore();
  const location = useLocation();
  
  const toggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  // Check if current route is the call center dashboard
  const isCallCenterDashboard = location.pathname === '/dashboard/call-center';

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-secondary-600">Chargement...</p>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return (
    <div className="h-screen flex flex-col">
      <Header toggleSidebar={toggleSidebar} />
      
      <div className="flex flex-1 overflow-hidden">
        <Sidebar isOpen={sidebarOpen} toggleSidebar={toggleSidebar} />
        
        <main className="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 md:p-8">
          <div className={isCallCenterDashboard ? "w-full" : "max-w-7xl mx-auto"}>
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
};

export default Layout;