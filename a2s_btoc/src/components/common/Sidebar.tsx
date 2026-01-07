import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { 
  UserPlus, 
  Users, 
  ShoppingBag, 
  BarChart2, 
  MessageCircle, 
  CheckCircle, 
  BarChart3, 
  Headphones, 
  PieChart,
  ChevronRight,
  LayoutDashboard
} from 'lucide-react';
import { useAuthStore } from '../../store/authStore';
import { cn } from '../../utils/cn';

interface SidebarProps {
  isOpen: boolean;
  toggleSidebar: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ isOpen, toggleSidebar }) => {
  const user = useAuthStore((state) => state.user);
  const location = useLocation();

  const getNavigation = () => {
    const baseNavigation = [
      { name: 'Tableau de bord', href: '/dashboard/stats', icon: LayoutDashboard, roles: ['admin', 'agent', 'confirmation', 'superviseur'] },
    ];

    const roleSpecificNavigation = {
      admin: [
        { name: 'Nouveau Contact', href: '/dashboard/contacts/new', icon: UserPlus },
        { name: 'Base Clients', href: '/dashboard/clients', icon: Users },
        { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        { name: 'Confirmation', href: '/dashboard/confirmation', icon: CheckCircle },
        { name: 'Centre d\'appel', href: '/dashboard/call-center', icon: Headphones },
        { name: 'Analyses Avancées', href: '/dashboard/advanced-stats', icon: BarChart3 },
        { name: 'Sources & Canaux', href: '/dashboard/source-stats', icon: PieChart },
      ],
      agent: [
        ...(user?.codeAgence === '000002' ? [
          { name: 'Centre d\'appel', href: '/dashboard/call-center', icon: Headphones },
          { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
          { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        ] : [
          { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
          { name: 'Clients', href: '/dashboard/clients', icon: Users },
          { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        ])
      ],
      superviseur: [
        { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
        { name: 'Clients', href: '/dashboard/clients', icon: Users },
        { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        { name: 'Confirmation', href: '/dashboard/confirmation', icon: CheckCircle },
        { name: 'Analyses Avancées', href: '/dashboard/advanced-stats', icon: BarChart3 },
        { name: 'Sources & Canaux', href: '/dashboard/source-stats', icon: PieChart },
      ],
      confirmation: [
        { name: 'Confirmation', href: '/dashboard/confirmation', icon: CheckCircle },
        { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
      ],
    };

    const userRoleNavigation = roleSpecificNavigation[user?.role as keyof typeof roleSpecificNavigation] || [];
    const filteredBaseNavigation = baseNavigation.filter(nav => nav.roles.includes(user?.role as any));
    
    return [...filteredBaseNavigation, ...userRoleNavigation];
  };

  const navigation = getNavigation();

  return (
    <>
      {/* Mobile overlay */}
      {isOpen && (
        <div 
          className="fixed inset-0 z-40 bg-secondary-900/50 backdrop-blur-sm md:hidden" 
          onClick={toggleSidebar}
        ></div>
      )}

      {/* Sidebar */}
      <aside 
        className={cn(
          "fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-100 shadow-xl transition-all duration-300 ease-in-out md:translate-x-0 md:static md:z-0 md:shadow-none",
          isOpen ? "translate-x-0" : "-translate-x-full"
        )}
      >
        <div className="h-full flex flex-col">
          {/* Header/Logo section */}
          <div className="p-6 flex items-center gap-3">
            <div className="h-10 w-10 bg-primary-600 rounded-xl flex items-center justify-center shadow-lg shadow-primary-200">
              <img 
                src="/logo-removebg-preview.png" 
                alt="Logo" 
                className="h-7 w-auto brightness-200"
              />
            </div>
            <div>
              <h1 className="text-sm font-bold text-secondary-900 leading-none">CRM Digital</h1>
              <p className="text-[10px] text-primary-600 font-bold uppercase tracking-wider mt-1">Alliance Synergie</p>
            </div>
          </div>

          {/* Navigation */}
          <nav className="flex-1 px-4 py-4 overflow-y-auto space-y-1">
            {navigation.map((item) => {
              const isActive = location.pathname === item.href;
              return (
                <NavLink
                  key={item.name}
                  to={item.href}
                  className={cn(
                    "group flex items-center justify-between px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200",
                    isActive
                      ? "bg-primary-50 text-primary-700 shadow-sm"
                      : "text-secondary-500 hover:bg-gray-50 hover:text-secondary-900"
                  )}
                >
                  <div className="flex items-center">
                    <item.icon
                      className={cn(
                        "mr-3 h-5 w-5 transition-colors duration-200",
                        isActive ? "text-primary-600" : "text-secondary-400 group-hover:text-secondary-500"
                      )}
                    />
                    <span>{item.name}</span>
                  </div>
                  {isActive && <ChevronRight className="h-4 w-4 text-primary-400" />}
                </NavLink>
              );
            })}
          </nav>
          
          {/* User Profile Mini Card */}
          <div className="p-4 m-4 bg-secondary-50 rounded-2xl border border-secondary-100">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 rounded-full bg-white flex items-center justify-center text-primary-600 font-bold shadow-sm border border-secondary-200">
                {user?.name?.charAt(0) || 'U'}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold text-secondary-900 truncate">{user?.name}</p>
                <p className="text-xs text-secondary-500 capitalize">{user?.role}</p>
              </div>
            </div>
            {user?.codeAgence && (
              <div className="mt-3 flex items-center gap-2 px-2 py-1 bg-white/50 rounded-lg border border-white/80">
                <div className={cn(
                  "w-1.5 h-1.5 rounded-full",
                  user.codeAgence === '000001' ? 'bg-pink-500' :
                  user.codeAgence === '000002' ? 'bg-blue-500' :
                  user.codeAgence === '000003' ? 'bg-green-500' :
                  'bg-gray-500'
                )}></div>
                <span className="text-[10px] font-medium text-secondary-600 uppercase tracking-tight">
                  {user.codeAgence === '000001' ? 'Social Media' :
                   user.codeAgence === '000002' ? 'Call Center' :
                   user.codeAgence === '000003' ? 'WhatsApp' :
                   `Team ${user.codeAgence}`}
                </span>
              </div>
            )}
          </div>

          <div className="px-6 py-4 border-t border-gray-50">
            <p className="text-[10px] text-secondary-400 font-medium">
              &copy; {new Date().getFullYear()} A2S CRM v2.0
            </p>
          </div>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;
