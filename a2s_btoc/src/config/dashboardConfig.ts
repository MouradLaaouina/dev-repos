import { DashboardConfig } from '../types';

export const TEAM_CODES = {
  RESEAUX_SOCIAUX: '000001',
  CENTRE_APPEL: '000002',
  WHATSAPP: '000003'
} as const;

export const TEAM_NAMES = {
  [TEAM_CODES.RESEAUX_SOCIAUX]: 'Réseaux sociaux',
  [TEAM_CODES.CENTRE_APPEL]: 'Centre d\'appel',
  [TEAM_CODES.WHATSAPP]: 'WhatsApp'
} as const;

export const TEAM_SUPERVISORS = {
  [TEAM_CODES.RESEAUX_SOCIAUX]: 'SAMIRA MOUSTAKIMI',
  [TEAM_CODES.WHATSAPP]: 'GHITA AJJAL',
  [TEAM_CODES.CENTRE_APPEL]: 'À définir'
} as const;

export const dashboardConfigs: DashboardConfig[] = [
  // Performance Dashboards
  {
    title: "Commandes par jour",
    access: ["admin", "superviseur", "agent", "callcenter"],
    component: "DailyOrdersChart",
    description: "Évolution quotidienne des commandes",
    category: "Performance"
  },
  {
    title: "Messages par jour",
    access: ["admin", "superviseur", "agent"],
    component: "DailyMessagesChart",
    description: "Évolution quotidienne des messages reçus",
    category: "Performance"
  },
  {
    title: "Appels par jour",
    access: ["admin", "superviseur", "callcenter"],
    component: "DailyCallsChart",
    description: "Évolution quotidienne des appels effectués",
    category: "Performance"
  },
  {
    title: "Taux de conversion (Commandes / Messages)",
    access: ["admin", "superviseur", "agent"],
    component: "ConversionRateChart",
    description: "Ratio commandes sur messages par période",
    category: "Performance"
  },
  {
    title: "Panier moyen par commande",
    access: ["admin", "superviseur", "agent", "callcenter"],
    component: "AverageBasketChart",
    description: "Valeur moyenne des commandes",
    category: "Performance"
  },

  // Volume Dashboards
  {
    title: "Total commandes par agent",
    access: ["agent", "superviseur", "admin", "callcenter"],
    filter: "user.code_agence",
    component: "OrdersByAgentChart",
    description: "Nombre total de commandes par agent de votre équipe",
    category: "Volume"
  },
  {
    title: "Total messages par agent",
    access: ["agent", "superviseur", "admin"],
    filter: "user.code_agence",
    component: "MessagesByAgentChart",
    description: "Nombre total de messages traités par agent de votre équipe",
    category: "Volume"
  },
  {
    title: "Nombre d'appels par agent",
    access: ["superviseur", "admin", "callcenter"],
    filter: "user.code_agence",
    component: "CallsByAgentChart",
    description: "Nombre d'appels effectués par agent de votre équipe",
    category: "Volume"
  },

  // Canal & Marque Dashboards
  {
    title: "Messages par canal (Instagram / Facebook / WhatsApp)",
    access: ["superviseur", "admin"],
    filter: "user.code_agence",
    component: "MessagesByChannelChart",
    description: "Répartition des messages par plateforme dans votre équipe",
    category: "Communication"
  },
  {
    title: "Commandes par marque",
    access: ["superviseur", "admin", "callcenter"],
    filter: "user.code_agence",
    component: "OrdersByBrandChart",
    description: "Répartition des commandes par marque dans votre équipe",
    category: "Volume"
  },

  // Call Center Specific Dashboards
  {
    title: "Satisfaction client",
    access: ["callcenter", "superviseur", "admin"],
    filter: "user.code_agence",
    component: "SatisfactionChart",
    description: "Niveau de satisfaction client par agent",
    category: "Communication"
  },

  // Suivi Agent Dashboards
  {
    title: "Nombre de types de demandes traitées",
    access: ["agent", "superviseur"],
    filter: "user.code_agence",
    component: "RequestTypesByAgentChart",
    description: "Types de demandes traitées par agent",
    category: "Volume"
  },
  {
    title: "Numéros de téléphone saisis",
    access: ["agent", "superviseur", "callcenter"],
    filter: "user.code_agence",
    component: "PhoneNumbersByAgentChart",
    description: "Nombre de numéros collectés par agent",
    category: "Volume"
  },
  {
    title: "Détail des appels",
    access: ["superviseur", "admin", "callcenter"],
    filter: "user.code_agence",
    component: "CallDetailsChart",
    description: "Détail des appels avec durée et résultats de votre équipe",
    category: "Communication"
  },

  // Agence Dashboards (Admin only)
  {
    title: "Total commandes par agence",
    access: ["admin"],
    component: "OrdersByTeamChart",
    description: "Répartition des commandes par équipe",
    category: "Agence"
  },
  {
    title: "Nombre de messages total / agence",
    access: ["admin"],
    component: "MessagesByTeamChart",
    description: "Répartition des messages par équipe",
    category: "Agence"
  },
  {
    title: "Panier moyen total et par agence",
    access: ["admin"],
    component: "AverageBasketByTeamChart",
    description: "Panier moyen global et par équipe",
    category: "Agence"
  },
  {
    title: "Taux de conversion / Agent / Marque",
    access: ["admin"],
    component: "ConversionByAgentBrandChart",
    description: "Taux de conversion détaillé par agent et marque",
    category: "Conversion"
  },
  
  // Source Statistics Dashboards (NEW)
  {
    title: "Contacts par source (Influenceurs)",
    access: ["admin", "superviseur"],
    component: "ContactsBySourceChart",
    description: "Répartition des contacts par source (influenceurs, publicités, etc.)",
    category: "Sources"
  },
  {
    title: "Types de demandes par source",
    access: ["admin", "superviseur"],
    component: "RequestTypesBySourceChart",
    description: "Types de demandes générées par chaque source",
    category: "Sources"
  },
  {
    title: "Taux de conversion par source",
    access: ["admin", "superviseur"],
    component: "ConversionRateBySourceChart",
    description: "Taux de conversion (commandes/contacts) par source",
    category: "Sources"
  },
  {
    title: "Commandes par source",
    access: ["admin", "superviseur"],
    component: "OrdersBySourceChart",
    description: "Nombre de commandes générées par chaque source",
    category: "Sources"
  },
  {
    title: "Panier moyen par source",
    access: ["admin", "superviseur"],
    component: "AverageBasketBySourceChart",
    description: "Valeur moyenne des commandes par source",
    category: "Sources"
  }
];

export const getAccessibleDashboards = (userRole: string, userCodeAgence?: string): DashboardConfig[] => {
  return dashboardConfigs.filter(config => {
    // Check role access
    if (!config.access.includes(userRole as any)) {
      return false;
    }

    // For agent role with team filter, ensure they have a team code
    if (userRole === 'agent' && config.filter === 'user.code_agence' && !userCodeAgence) {
      return false;
    }

    // For superviseur role with team filter, ensure they have a team code
    if (userRole === 'superviseur' && config.filter === 'user.code_agence' && !userCodeAgence) {
      return false;
    }

    // For callcenter role, only show relevant dashboards
    if (userRole === 'callcenter' && !config.access.includes('callcenter')) {
      return false;
    }

    return true;
  });
};

export const getDashboardsByCategory = (dashboards: DashboardConfig[]) => {
  return dashboards.reduce((acc, dashboard) => {
    if (!acc[dashboard.category]) {
      acc[dashboard.category] = [];
    }
    acc[dashboard.category].push(dashboard);
    return acc;
  }, {} as Record<string, DashboardConfig[]>);
};

export const getTeamName = (codeAgence: string): string => {
  return TEAM_NAMES[codeAgence as keyof typeof TEAM_NAMES] || `Équipe ${codeAgence}`;
};

export const getTeamSupervisor = (codeAgence: string): string => {
  return TEAM_SUPERVISORS[codeAgence as keyof typeof TEAM_SUPERVISORS] || 'Superviseur non défini';
};

export const getFilterDescription = (filter?: string, userRole?: string): string => {
  if (!filter) return 'Données globales';
  
  switch (filter) {
    case 'user.code_agence':
      return userRole === 'admin' ? 'Filtré par équipe' : 'Votre équipe uniquement';
    case 'user.id':
      return 'Vos données personnelles';
    default:
      return 'Données filtrées';
  }
};

export const getTeamInfo = (codeAgence: string) => {
  return {
    code: codeAgence,
    name: getTeamName(codeAgence),
    supervisor: getTeamSupervisor(codeAgence),
    color: codeAgence === TEAM_CODES.WHATSAPP ? 'green' : 
           codeAgence === TEAM_CODES.RESEAUX_SOCIAUX ? 'pink' : 
           codeAgence === TEAM_CODES.CENTRE_APPEL ? 'blue' : 'gray'
  };
};