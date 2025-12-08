export interface DashboardConfig {
  title: string;
  access: ('admin' | 'superviseur' | 'agent')[];
  filter?: 'user.code_agence';
  component: string;
  description?: string;
}

export interface DashboardData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string[];
    borderColor?: string;
    borderWidth?: number;
  }[];
}

export interface TeamStats {
  whatsapp: number;
  reseauxSociaux: number;
  centreAppel: number;
}

export interface AgentPerformance {
  agentName: string;
  agentCode: string;
  codeAgence: string;
  messages: number;
  orders: number;
  calls: number;
  conversionRate: number;
}