import { useState } from 'react';
import { AuthProvider } from './contexts/AuthProvider';
import { useAuthContext } from './hooks/useAuthContext';
import LoadingPage from './components/pages/LoadingPage';
import LoginPage from './components/pages/LoginPage';
import MenuPage from './components/pages/MenuPage';
import ClientSearchPage from './components/pages/ClientSearchPage';
import OrderFormPage from './components/pages/OrderFormPage';
import ProposalListPage from './components/pages/ProposalListPage';
import ProposalDetailPage from './components/pages/ProposalDetailPage';
import SelloutClientPage from './components/pages/SelloutClientPage';
import SelloutFormPage from './components/pages/SelloutFormPage';
import ShipmentListPage from './components/pages/ShipmentListPage';
import ShipmentDetailPage from './components/pages/ShipmentDetailPage';

function AppContent() {
  const { user, login, logout, loading: authLoading } = useAuthContext();
  const [currentPage, setCurrentPage] = useState('loading');
  const [selectedClient, setSelectedClient] = useState(null);
  const [selectedProposalId, setSelectedProposalId] = useState(null);
  const [selectedSelloutClient, setSelectedSelloutClient] = useState(null);
  const [selectedShipmentId, setSelectedShipmentId] = useState(null);

  const handleLoadComplete = () => {
    if (user) {
      setCurrentPage('menu');
    } else {
      setCurrentPage('login');
    }
  };

  const handleLogin = async (loginData) => {
    const result = await login(loginData);
    
    if (result.success) {
      setCurrentPage('menu');
    } else {
      throw new Error(result.error);
    }
  };

  const handleNavigate = (page) => {
    setCurrentPage(page);
  };

  const handleClientSelect = (client) => {
    setSelectedClient(client);
    setCurrentPage('orderForm');
  };

  const handleSelloutClientSelect = (client) => {
    setSelectedSelloutClient(client);
    setCurrentPage('selloutForm');
  };

  const handleBack = () => {
    setSelectedClient(null);
    setCurrentPage('menu');
  };

  const handleSelloutBack = () => {
    setSelectedSelloutClient(null);
    setCurrentPage('menu');
  };

  const handleBackToMenu = () => {
    setCurrentPage('menu');
  };

  const handleProposalSelect = (proposalId) => {
    setSelectedProposalId(proposalId);
    setCurrentPage('proposalDetail');
  };

  const handleBackToProposalList = () => {
    setSelectedProposalId(null);
    setCurrentPage('proposalList');
  };

  const handleShipmentSelect = (shipmentId) => {
    setSelectedShipmentId(shipmentId);
    setCurrentPage('shipmentDetail');
  };

  const handleBackToShipmentList = () => {
    setSelectedShipmentId(null);
    setCurrentPage('shipmentList');
  };

  const handleLogout = () => {
    logout();
    setSelectedClient(null);
    setSelectedProposalId(null);
    setSelectedSelloutClient(null);
    setSelectedShipmentId(null);
    setCurrentPage('login');
  };

  if (authLoading) {
    return <LoadingPage onLoadComplete={() => {}} />;
  };

  const renderPage = () => {
    switch(currentPage) {
      case 'loading':
        return <LoadingPage onLoadComplete={handleLoadComplete} />;
      
      case 'login':
        return <LoginPage onLogin={handleLogin} />;
      
      case 'menu':
        return user ? (
          <MenuPage 
            user={user}
            onNavigate={handleNavigate}
            onLogout={handleLogout}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );
      
      case 'clientSearch':
        return user ? (
          <ClientSearchPage
            user={user}
            onClientSelect={handleClientSelect}
            onBack={handleBackToMenu}
            onLogout={handleLogout}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      case 'selloutClientSearch':
        return user ? (
          <SelloutClientPage
            user={user}
            onClientSelect={handleSelloutClientSelect}
            onBack={handleBackToMenu}
            onLogout={handleLogout}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );
      
      case 'proposalList':
        return user ? (
          <ProposalListPage
            user={user}
            onBack={handleBackToMenu}
            onProposalSelect={handleProposalSelect}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      case 'proposalDetail':
        return user && selectedProposalId ? (
          <ProposalDetailPage
            proposalId={selectedProposalId}
            onBack={handleBackToProposalList}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      case 'selloutForm':
        return user && selectedSelloutClient ? (
          <SelloutFormPage
            user={user}
            client={selectedSelloutClient}
            onBack={handleSelloutBack}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      case 'orderForm':
        return user && selectedClient ? (
          <OrderFormPage
            user={user}
            client={selectedClient}
            onBack={handleBack}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      case 'shipmentList':
        return user ? (
          <ShipmentListPage
            user={user}
            onBack={handleBackToMenu}
            onShipmentSelect={handleShipmentSelect}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      case 'shipmentDetail':
        return user && selectedShipmentId ? (
          <ShipmentDetailPage
            shipmentId={selectedShipmentId}
            onBack={handleBackToShipmentList}
          />
        ) : (
          <LoginPage onLogin={handleLogin} />
        );

      default:
        return <LoadingPage onLoadComplete={handleLoadComplete} />;
    }
  };

  return <div className="font-sans">{renderPage()}</div>;
}

export default function App() {
  return (
    <AuthProvider>
      <AppContent />
    </AuthProvider>
  );
}
