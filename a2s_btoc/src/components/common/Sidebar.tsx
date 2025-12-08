import React from 'react';
import { NavLink } from 'react-router-dom';
import { UserPlus, Users, ShoppingBag, BarChart2, MessageCircle, CheckCircle, BarChart3, Phone, Headphones, PieChart } from 'lucide-react';
import { useAuthStore } from '../../store/authStore';

interface SidebarProps {
  isOpen: boolean;
  toggleSidebar: () => void;
}

const Sidebar: React.FC<SidebarProps> = ({ isOpen, toggleSidebar }) => {
  const user = useAuthStore((state) => state.user);

  // Define navigation based on user role and team
  const getNavigation = () => {
    const baseNavigation = [
      { name: 'Statistiques', href: '/dashboard/stats', icon: BarChart2, roles: ['admin', 'agent', 'confirmation', 'superviseur'] },
      // Tableaux de Bord Avancés only for superviseurs and admin
      { name: 'Tableaux de Bord', href: '/dashboard/advanced-stats', icon: BarChart3, roles: ['admin', 'superviseur'] },
      // Source Statistics only for admin and superviseurs
      { name: 'Stats par Source', href: '/dashboard/source-stats', icon: PieChart, roles: ['admin', 'superviseur'] },
    ];

    const roleSpecificNavigation = {
      admin: [
        { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
        { name: 'Clients', href: '/dashboard/clients', icon: Users },
        { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        { name: 'WhatsApp', href: '/dashboard/whatsapp', icon: MessageCircle },
        { name: 'Confirmation', href: '/dashboard/confirmation', icon: CheckCircle },
        { name: 'Centre d\'appel', href: '/dashboard/call-center', icon: Headphones },
      ],
      agent: [
        // Tableau de Bord (agent dashboard) only for agents
        { name: 'Tableau de Bord', href: '/dashboard/agent-dashboard', icon: BarChart2 },
        // For call center agents (000002), only show specific tabs
        ...(user?.codeAgence === '000002' ? [
          { name: 'Centre d\'appel', href: '/dashboard/call-center', icon: Headphones },
          { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
          { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        ] : [
          // For other agents, show all tabs except call center
          { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
          { name: 'Clients', href: '/dashboard/clients', icon: Users },
          { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
          // Only show WhatsApp tab for WhatsApp team (000003)
          ...(user?.codeAgence === '000003' ? [
            { name: 'WhatsApp', href: '/dashboard/whatsapp', icon: MessageCircle }
          ] : [])
        ])
      ],
      superviseur: [
        // Add "Ajouter Contact" for supervisors (especially GHITA AJJAL)
        { name: 'Ajouter Contact', href: '/dashboard/contacts/new', icon: UserPlus },
        // Tableau de Bord (agent dashboard) only for agents - superviseurs get advanced stats
        { name: 'Clients', href: '/dashboard/clients', icon: Users },
        { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
        // Only show WhatsApp tab for WhatsApp team supervisors
        ...(user?.codeAgence === '000003' ? [
          { name: 'WhatsApp', href: '/dashboard/whatsapp', icon: MessageCircle }
        ] : []),
        // Add Confirmation tab for supervisors
        { name: 'Confirmation', href: '/dashboard/confirmation', icon: CheckCircle }
      ],
      confirmation: [
        { name: 'Confirmation', href: '/dashboard/confirmation', icon: CheckCircle },
        { name: 'Commandes', href: '/dashboard/orders', icon: ShoppingBag },
      ],
    };

    const userRoleNavigation = roleSpecificNavigation[user?.role as keyof typeof roleSpecificNavigation] || [];
    
    // Filter base navigation based on user role
    const filteredBaseNavigation = baseNavigation.filter(nav => {
      return nav.roles.includes(user?.role as any);
    });
    
    return [...filteredBaseNavigation, ...userRoleNavigation];
  };

  const navigation = getNavigation();

  return (
    <>
      {/* Mobile sidebar overlay */}
      {isOpen && (
        <div 
          className="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 md:hidden" 
          onClick={toggleSidebar}
        ></div>
      )}

      {/* Sidebar */}
      <aside 
        className={`
          fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:z-0
          ${isOpen ? 'translate-x-0' : '-translate-x-full'}
        `}
      >
        <div className="h-full flex flex-col">
          <div className="h-16 flex items-center justify-between px-4 md:hidden border-b border-gray-200">
            <div className="flex items-center">
              <img 
                src="/logo-removebg-preview.png" 
                alt="Alliance Synergie Santé" 
                className="h-10 w-auto"
              />
              <div className="ml-3">
                <h1 className="text-sm font-bold text-secondary-800">CRM Digital Leads</h1>
                <p className="text-xs text-primary-600 font-medium">Alliance Synergie Santé</p>
              </div>
            </div>
            <button 
              onClick={toggleSidebar} 
              className="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100"
            >
              <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div className="flex-1 flex flex-col overflow-y-auto pt-5 pb-4">
            <div className="px-4 mb-6 md:hidden">
              <div className="flex flex-col items-center">
                <div className="h-12 w-12 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 mb-2">
                  <Users className="h-6 w-6" />
                </div>
                <div className="text-center">
                  <p className="text-sm font-medium text-gray-900">{user?.name}</p>
                  <p className="text-xs text-gray-500 capitalize">{user?.role}</p>
                  {user?.codeAgence && (
                    <p className="text-xs text-blue-600 mt-1">Équipe: {user.codeAgence}</p>
                  )}
                  {user?.codeAgence === '000002' && (
                    <p className="text-xs text-green-600 mt-1">Centre d'appel</p>
                  )}
                </div>
              </div>
            </div>

            <div className="px-2 space-y-1">
              {navigation.map((item) => (
                <NavLink
                  key={item.name}
                  to={item.href}
                  className={({ isActive }) => `
                    group flex items-center px-2 py-2 text-base font-medium rounded-md transition-colors duration-200
                    ${isActive
                      ? 'bg-primary-50 text-primary-700 border-r-2 border-primary-500'
                      : 'text-secondary-600 hover:bg-gray-50 hover:text-secondary-900'
                    }
                  `}
                  end={item.href === '/dashboard'}
                >
                  <item.icon
                    className={`
                      mr-3 flex-shrink-0 h-6 w-6 transition-colors duration-200
                      ${location.pathname === item.href
                        ? 'text-primary-600'
                        : 'text-secondary-400 group-hover:text-secondary-500'
                      }
                    `}
                  />
                  {item.name}
                </NavLink>
              ))}
            </div>
          </div>
          
          <div className="px-4 py-4 border-t border-gray-200">
            <div className="flex items-center mb-2">
              <img 
                src="/logo-removebg-preview.png" 
                alt="Alliance Synergie Santé" 
                className="h-8 w-auto"
              />
              <span className="ml-3 text-xs font-medium text-secondary-700">Alliance Synergie Santé</span>
            </div>
            <div className="text-xs text-secondary-500">
              &copy; {new Date().getFullYear()} Tous droits réservés
            </div>
            {/* Show team information */}
            {user?.codeAgence && (
              <div className="mt-2 text-xs text-secondary-600">
                <div className="flex items-center gap-2">
                  <div className={`w-2 h-2 rounded-full ${
                    user.codeAgence === '000001' ? 'bg-pink-500' :
                    user.codeAgence === '000002' ? 'bg-blue-500' :
                    user.codeAgence === '000003' ? 'bg-green-500' :
                    'bg-gray-500'
                  }`}></div>
                  <span>
                    {user.codeAgence === '000001' ? 'Réseaux sociaux' :
                     user.codeAgence === '000002' ? 'Centre d\'appel' :
                     user.codeAgence === '000003' ? 'WhatsApp' :
                     `Équipe ${user.codeAgence}`}
                  </span>
                </div>
              </div>
            )}
          </div>
        </div>
      </aside>
    </>
  );
};

export default Sidebar;