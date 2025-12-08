export type User = {
  id: string;
  name: string;
  email: string;
  role: 'admin' | 'agent' | 'superviseur' | 'confirmation' | 'dispatching' | 'callcenter';
  code?: string;
  codeAgence?: string; // Team code for filtering
  team?: string; // Team name for call center
  createdAt: Date;
};

export type Platform = 'Facebook' | 'Instagram' | 'WhatsApp' | 'Clients' | 'Centre d\'appel';

export type RequestType = 
  | 'En attente de traitement'
  | 'Information'
  | 'Orientation Para'
  | 'Sans réponse'
  | 'En attente de réponse'
  | 'Annulee'
  | 'Commande';

export type Gender = 'Homme' | 'Femme';

export type ContactStatus = 'nouveau' | 'à rappeler' | 'converti' | 'abandonné';

export type OrderStatus = 'À confirmer' | 'Prête à être livrée' | 'Livrée' | 'Retournée' | 'Annulée';

export type PaymentMethod = 'Espèce' | 'Virement';

export type Brand = 'D-WHITE' | 'SENSILIS' | 'CUMLAUDE' | 'BABE' | 'BUCCOTHERM' | 'D-CAP' | 'CASMARA RETAIL';

export type City = typeof moroccanCities[number];

export type Source = 
  | 'PAS DÉFINI'
  | 'ALOUA LIFE STYLE'
  | 'MARWA LAHLOU'
  | 'MARWA LAHLOU'
  | 'CHAIMAA MOAD'
  | 'GHIZLANE CHLIKHATE'
  | 'HAFSSA ACHRAF'
  | 'HAJAR ARSALANE'
  | 'HIBA NAYRAS'
  | 'MARWA AOUB'
  | 'CORITA'
  | 'WAHIBA BOUYA'
  | 'YOUSSRA'
  | 'JAD BELGAID'
  | 'HIBA LAMANE'
  | 'WYDAD SERRI'
  | 'MOUNIA JAIDAR'
  | 'RAJAA'
  | 'SABAH BENSEDDIK'
  | 'SARA BOUBBAD'
  | 'LAMIE CONSEIL'
  | 'GHAZAL TIKTOK'
  | 'SARA REGRAGUI'
  | 'AFLATONA'
  | 'SARA ASTERI'
  | 'Maria LAZRAK'
  | 'RECOMMANDATION'
  | 'GHIZLANE ELOTMANI'
  | 'QUEEN BY IMANE'
  | 'KAOUTAR BERRANI'
  | 'SARAYATALK'
  | 'LINA ELYAHYAOUI'
  | 'OUIDAD LEMNIAI'
  | 'LEILA BOULKADDAT'
  | 'CHEKORS'
  | 'NOUHAILA BARBIE'
  | 'FATIYASS'
  | 'OUMAIMA FARAH'
  | 'ADS'
  | 'ADS WHATSAPP'
  | 'ADS BALI'
  | 'R-S'
  | 'HANANE KHAYATI'
  | 'CHAMAMOUST'
  | 'LAMIAA AHMADI'
  | 'JIHAD SABIR'
  | 'YASMINERIE'
  | 'SALMA CHOKOLATI'
  | 'NIRMINE BEAUTY'
  | 'KHADIJA SAKHI'
  | 'MIMI MICROBLADING'
  | 'HAMAKA'
  | 'ZINEB LAALAMI'
  | 'FADIL SALMA'
  | 'KAOUTAR RAGHAY'
  | 'TOUHA'
  | 'THOURAYA'
  | 'AHMED KABBAJ'
  | 'KHAWLA QUEEN'
  | 'MERIEM ASOUAB'
  | 'KHAOULA NAOUM'
  | 'AFRAH'
  | 'CLIENT EXISTANT'
  | 'En attente de réponse'
  | 'Sans réponse'
  | 'Ghita MOUHIB'
  | 'SARA EL BAKKAL'
  | 'YOUSSRA QUEEN'
  | 'CENTRE APPEL'
  | 'WHATSAPP'
  | 'Démarchage'
  | 'Parrainage'
  | 'Imported Client List';

export type Contact = {
  id: string;
  nom: string;
  telephone: string;
  telephone2?: string;
  address: string;
  plateforme: Platform;
  message?: string;
  typeDeDemande: RequestType;
  ville: City;
  sexe: Gender;
  fromAds: boolean;
  status: ContactStatus;
  createdAt: Date;
  agentId: string;
  agentCode?: string;
  agentName?: string;
  codeAgence?: string; // Team code
  updatedAt: Date;
  dateMessage?: Date;
  source?: Source;
  commerciale?: string;
  marque?: Brand;
  interet?: string;
  remarque?: string;
  clientCode?: string; // External client ID for integration with management software
};

export type OrderItem = {
  id?: string;
  brand: Brand;
  productCode: string;
  productName: string;
  quantity: number;
  priceHT: number;
  unitPrice: number;
};

export type Order = Contact & {
  orderSupabaseId?: string;
  orderNumber: string;
  deliveryDate?: Date;
  items: OrderItem[];
  shippingCost: number;
  total: number;
  totalHT: number;
  status: OrderStatus;
  paymentMethod: PaymentMethod;
  transferNumber?: string;
  cancellationNote?: string;
  confirmationNote?: string;
  confirmedBy?: string;
  confirmedAt?: Date;
  dispatchedBy?: string;
  dispatchedAt?: Date;
  trackingNumber?: string;
  deliveryNote?: string;
  exportedAt?: Date; // New field
  exportedBy?: string; // New field (UUID of the user)
  clientCode?: string; // External client ID for integration with management software
};

export type Product = {
  id: string;
  code: string;
  name: string;
  brand: Brand;
  priceHT: number;
};

export type SocialMessage = {
  id: string;
  platform: Platform;
  senderId: string;
  senderName: string;
  message: string;
  timestamp: Date;
  profilePicture?: string;
  isRead: boolean;
  isConverted: boolean;
  pageId?: string;
  pageName?: string;
  conversationId?: string;
};

// Call Center Types
export type CallStatus = 
  | 'À rappeler'
  | 'Pas intéressé(e)'
  | 'Ne réponds jamais'
  | 'Faux numéro'
  | 'Intéressé(e)'
  | 'Commande'; // Add this line

export type CallLog = {
  id: string;
  clientId: string;
  leadId?: string; // New field for call_center_leads reference
  callStatus: CallStatus;
  satisfactionLevel: number; // 1-5 stars
  interested: 'Oui' | 'Non' | 'Peut-être';
  callDate: Date;
  nextCallDate?: Date;
  nextCallTime?: string;
  notes?: string;
  agentId: string;
  createdAt: Date;
};

export type CallCenterLead = {
  id: string;
  purchaseDate: Date;
  name: string;
  phoneNumber: string;
  commercialAgent: string;
  productBought: string;
  brand: Brand;
  status: ContactStatus;
  lastCallDate?: Date;
  nextCallDate?: Date;
  callLogs: CallLog[];
  assignedTo?: string;
  assignedName?: string; // Added field to store the agent's name
  codeClient?: string; // Client code for ATLAS integration
  contactId?: string; // Actual contact ID for foreign key reference
};

// Dashboard types
export interface DashboardConfig {
  title: string;
  access: ('admin' | 'superviseur' | 'agent' | 'callcenter')[];
  filter?: 'user.code_agence' | 'user.id';
  component: string;
  description?: string;
  category: 'Performance' | 'Volume' | 'Conversion' | 'Communication' | 'Agence' | 'Sources';
}

export interface DashboardData {
  labels: string[];
  datasets: {
    label: string;
    data: number[];
    backgroundColor?: string | string[];
    borderColor?: string;
    borderWidth?: number;
    fill?: boolean;
  }[];
}

export interface TeamStats {
  whatsapp: number;
  reseauxSociaux: number;
  centreAppel: number;
}

export interface AgentPerformance {
  agentId: string;
  agentName: string;
  agentCode: string;
  codeAgence: string;
  messages: number;
  orders: number;
  calls: number;
  conversionRate: number;
  averageBasket: number;
}

// Import from data/cities.ts
declare const moroccanCities: string[];