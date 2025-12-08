import React, { useEffect } from 'react';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { Toaster } from 'react-hot-toast';
import Layout from './components/common/Layout';
import LoginForm from './components/auth/LoginForm';
import RegisterForm from './components/auth/RegisterForm';
import ContactForm from './components/contacts/ContactForm';
import ClientsTab from './components/clients/ClientsTab';
import OrdersTab from './components/orders/OrdersTab';
import WhatsAppTab from './components/whatsapp/WhatsAppTab';
import StatsTab from './components/stats/StatsTab';
import AdvancedStatsTab from './components/dashboard/AdvancedStatsTab';
import SourceStatsTab from './components/dashboard/SourceStatsTab';
import ConfirmationTab from './components/confirmation/ConfirmationTab';
import AgentDashboard from './components/agent/AgentDashboard';
import CallCenterDashboard from './components/callcenter/CallCenterDashboard';
import { useAuthStore } from './store/authStore';

function App() {
  const { checkAuth } = useAuthStore();

  useEffect(() => {
    checkAuth();
  }, [checkAuth]);

  return (
    <BrowserRouter>
      <Toaster position="top-right" />
      <Routes>
        <Route path="/login" element={<LoginForm />} />
        <Route path="/register" element={<RegisterForm />} />
        <Route path="/dashboard" element={<Layout />}>
          <Route index element={<Navigate to="/dashboard/stats" replace />} />
          <Route path="contacts/new" element={<ContactForm />} />
          <Route path="contacts/edit/:id" element={<ContactForm />} />
          <Route path="clients" element={<ClientsTab />} />
          <Route path="orders" element={<OrdersTab />} />
          <Route path="whatsapp" element={<WhatsAppTab />} />
          <Route path="stats" element={<StatsTab />} />
          <Route path="advanced-stats" element={<AdvancedStatsTab />} />
          <Route path="source-stats" element={<SourceStatsTab />} />
          <Route path="confirmation" element={<ConfirmationTab />} />
          <Route path="agent-dashboard" element={<AgentDashboard />} />
          <Route path="call-center" element={<CallCenterDashboard />} />
        </Route>
        <Route path="*" element={<Navigate to="/dashboard/stats" replace />} />
      </Routes>
    </BrowserRouter>
  );
}

export default App;