import React from 'react';
import { useNavigate } from 'react-router-dom';
import { LogOut, User, Menu } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';

interface HeaderProps {
  toggleSidebar: () => void;
}

const Header: React.FC<HeaderProps> = ({ toggleSidebar }) => {
  const navigate = useNavigate();
  const { user, logout } = useAuthStore();
  
  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <header className="bg-white border-b border-gray-200 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex items-center">
            <button 
              onClick={toggleSidebar}
              className="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 md:hidden"
            >
              <Menu className="h-6 w-6" />
            </button>
            <div className="flex-shrink-0 flex items-center ml-2 md:ml-0">
              <img 
                src="/logo-removebg-preview.png" 
                alt="Alliance Synergie Santé" 
                className="h-12 w-auto"
              />
              <div className="ml-4 hidden sm:block">
                <h1 className="text-xl font-bold text-secondary-800">CRM Digital Leads</h1>
                <p className="text-sm text-primary-600 font-medium">Alliance Synergie Santé</p>
              </div>
            </div>
          </div>
          
          <div className="flex items-center">
            <div className="hidden md:flex md:items-center md:ml-6">
              <div className="flex items-center">
                <div className="text-right mr-3">
                  <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                  <p className="text-xs text-gray-500 capitalize">{user?.role}</p>
                </div>
                <div className="h-8 w-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700">
                  <User className="h-5 w-5" />
                </div>
              </div>
            </div>
            
            <div className="ml-4 flex items-center md:ml-6">
              <button
                onClick={handleLogout}
                className="p-1 rounded-full text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
              >
                <LogOut className="h-6 w-6" />
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>
  );
};

export default Header;