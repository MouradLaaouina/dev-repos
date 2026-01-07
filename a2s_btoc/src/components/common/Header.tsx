import React from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { LogOut, Bell, Search, Menu, User } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';

interface HeaderProps {
  toggleSidebar: () => void;
}

const Header: React.FC<HeaderProps> = ({ toggleSidebar }) => {
  const navigate = useNavigate();
  const location = useLocation();
  const { user, logout } = useAuthStore();
  
  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  const getPageTitle = () => {
    const path = location.pathname;
    if (path.includes('stats')) return 'Tableau de bord';
    if (path.includes('contacts/new')) return 'Nouveau Contact';
    if (path.includes('clients')) return 'Base Clients';
    if (path.includes('orders')) return 'Gestion des Commandes';
    if (path.includes('whatsapp')) return 'Centre WhatsApp';
    if (path.includes('confirmation')) return 'Espace Confirmation';
    if (path.includes('call-center')) return 'Centre d\'appel';
    return 'CRM Digital';
  };

  return (
    <header className="bg-white border-b border-gray-100 h-16 flex items-center justify-between px-4 sm:px-6 lg:px-8 sticky top-0 z-30">
      <div className="flex items-center gap-4">
        <button 
          onClick={toggleSidebar}
          className="p-2 rounded-lg text-secondary-500 hover:bg-secondary-50 md:hidden transition-colors"
        >
          <Menu className="h-6 w-6" />
        </button>
        
        <div className="hidden md:block">
          <h2 className="text-lg font-bold text-secondary-900">{getPageTitle()}</h2>
        </div>
      </div>
      
      <div className="flex items-center gap-2 sm:gap-4">
        <div className="hidden sm:flex items-center bg-secondary-50 px-3 py-1.5 rounded-xl border border-secondary-100 group focus-within:bg-white focus-within:ring-2 focus-within:ring-primary-500/20 transition-all">
          <Search className="h-4 w-4 text-secondary-400 group-focus-within:text-primary-500" />
          <input 
            type="text" 
            placeholder="Rechercher..." 
            className="bg-transparent border-none focus:ring-0 text-sm w-40 lg:w-64 placeholder:text-secondary-400"
          />
        </div>

        <button className="p-2 rounded-lg text-secondary-400 hover:bg-secondary-50 hover:text-secondary-600 transition-all relative">
          <Bell className="h-5 w-5" />
          <span className="absolute top-2 right-2 w-2 h-2 bg-danger-500 rounded-full border-2 border-white"></span>
        </button>

        <div className="h-8 w-[1px] bg-secondary-100 mx-1 sm:mx-2"></div>

        <button
          onClick={handleLogout}
          className="flex items-center gap-2 px-3 py-1.5 rounded-xl text-secondary-500 hover:bg-danger-50 hover:text-danger-600 transition-all"
        >
          <span className="text-sm font-medium hidden sm:block">Déconnexion</span>
          <LogOut className="h-5 w-5" />
        </button>
      </div>
    </header>
  );
};

export default Header;
