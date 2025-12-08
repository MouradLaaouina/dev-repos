// 🎨 Données mockées pour l'application

export const mockUsers = [
  { 
    id: 1, 
    login: 'admin', 
    password: 'admin', 
    email: 'admin@a2s.com',
    token: 'mock_token_admin_123'
  },
  { 
    id: 2, 
    login: 'marie.dubois', 
    password: 'demo', 
    email: 'marie.dubois@a2s.com',
    token: 'mock_token_marie_456'
  },
  { 
    id: 3, 
    login: 'demo', 
    password: 'demo', 
    email: 'demo@a2s.com',
    token: 'mock_token_demo_789'
  },
];

export const mockClients = [
  { 
    id: 1, 
    code_client: 'C001', 
    name: 'Pharmacie du Centre',
    nom: 'Pharmacie du Centre',
    email: 'contact@pharma-centre.fr',
    town: 'Paris',
    address: '15 rue de la République',
    zip: '75001',
    client: 1
  },
  { 
    id: 2, 
    code_client: 'C002', 
    name: 'Pharmacie Principale',
    nom: 'Pharmacie Principale',
    email: 'info@pharma-principale.fr',
    town: 'Lyon',
    address: '8 avenue Victor Hugo',
    zip: '69001',
    client: 1
  },
  { 
    id: 3, 
    code_client: 'C003', 
    name: 'Pharmacie de la Mairie',
    nom: 'Pharmacie de la Mairie',
    email: 'contact@pharma-mairie.fr',
    town: 'Marseille',
    address: '23 place de la Mairie',
    zip: '13001',
    client: 1
  },
  { 
    id: 4, 
    code_client: 'C004', 
    name: 'Pharmacie du Parc',
    nom: 'Pharmacie du Parc',
    email: 'contact@pharma-parc.fr',
    town: 'Toulouse',
    address: '45 avenue du Parc',
    zip: '31000',
    client: 1
  },
  { 
    id: 5, 
    code_client: 'C005', 
    name: 'Pharmacie Centrale',
    nom: 'Pharmacie Centrale',
    email: 'info@pharma-centrale.fr',
    town: 'Nice',
    address: '12 promenade des Anglais',
    zip: '06000',
    client: 1
  },
  { 
    id: 6, 
    code_client: 'C006', 
    name: 'Pharmacie des Champs',
    nom: 'Pharmacie des Champs',
    email: 'contact@pharma-champs.fr',
    town: 'Bordeaux',
    address: '78 cours de l\'Intendance',
    zip: '33000',
    client: 1
  },
];

export const mockProducts = [
  { 
    id: 1, 
    ref: 'P001',
    label: 'Crème Hydratante Visage 50ml',
    price: 29.90,
    price_ttc: 29.90,
    tva_tx: 20,
    barcode: '3401597456123',
    stock_reel: 45,
    description: 'Crème hydratante enrichie en acide hyaluronique'
  },
  { 
    id: 2, 
    ref: 'P002',
    label: 'Sérum Anti-Âge 30ml',
    price: 45.50,
    price_ttc: 45.50,
    tva_tx: 20,
    barcode: '3401597456124',
    stock_reel: 32,
    description: 'Sérum concentré anti-rides'
  },
  { 
    id: 3, 
    ref: 'P003',
    label: 'Lotion Tonique 200ml',
    price: 22.00,
    price_ttc: 22.00,
    tva_tx: 20,
    barcode: '3401597456125',
    stock_reel: 58,
    description: 'Lotion purifiante et tonifiante'
  },
  { 
    id: 4, 
    ref: 'P004',
    label: 'Masque Purifiant 75ml',
    price: 35.00,
    price_ttc: 35.00,
    tva_tx: 20,
    barcode: '3401597456126',
    stock_reel: 28,
    description: 'Masque à l\'argile verte'
  },
  { 
    id: 5, 
    ref: 'P005',
    label: 'Contour des Yeux 15ml',
    price: 38.90,
    price_ttc: 38.90,
    tva_tx: 20,
    barcode: '3401597456127',
    stock_reel: 41,
    description: 'Soin anti-cernes et anti-poches'
  },
  { 
    id: 6, 
    ref: 'P006',
    label: 'Gel Nettoyant Doux 150ml',
    price: 18.50,
    price_ttc: 18.50,
    tva_tx: 20,
    barcode: '3401597456128',
    stock_reel: 67,
    description: 'Gel nettoyant sans savon'
  },
  { 
    id: 7, 
    ref: 'P007',
    label: 'Crème de Nuit Régénérante 50ml',
    price: 42.00,
    price_ttc: 42.00,
    tva_tx: 20,
    barcode: '3401597456129',
    stock_reel: 23,
    description: 'Crème régénérante pour la nuit'
  },
  { 
    id: 8, 
    ref: 'P008',
    label: 'Eau Micellaire 250ml',
    price: 15.90,
    price_ttc: 15.90,
    tva_tx: 20,
    barcode: '3401597456130',
    stock_reel: 89,
    description: 'Eau micellaire démaquillante'
  },
  { 
    id: 9, 
    ref: 'P009',
    label: 'Crème Solaire SPF50 100ml',
    price: 24.90,
    price_ttc: 24.90,
    tva_tx: 20,
    barcode: '3401597456131',
    stock_reel: 54,
    description: 'Protection solaire très haute'
  },
  { 
    id: 10, 
    ref: 'P010',
    label: 'Baume à Lèvres 15ml',
    price: 8.50,
    price_ttc: 8.50,
    tva_tx: 20,
    barcode: '3401597456132',
    stock_reel: 120,
    description: 'Baume hydratant et réparateur'
  },
];

export const mockPaymentMethods = [
  { id: 1, code: 'CB', label: 'Carte Bancaire', libelle: 'Carte Bancaire' },
  { id: 2, code: 'VIR', label: 'Virement', libelle: 'Virement' },
  { id: 3, code: 'CHQ', label: 'Chèque', libelle: 'Chèque' },
  { id: 4, code: 'PRE', label: 'Prélèvement', libelle: 'Prélèvement' },
  { id: 5, code: 'ESP', label: 'Espèces', libelle: 'Espèces' },
];

export const mockClientDiscounts = {
  1: [{ id: 1, amount_ht: 5, remise_percent: 5, description: 'Remise fidélité' }],
  2: [{ id: 2, amount_ht: 10, remise_percent: 10, description: 'Remise gros client' }],
  3: [{ id: 3, amount_ht: 3, remise_percent: 3, description: 'Remise standard' }],
  4: [{ id: 4, amount_ht: 7, remise_percent: 7, description: 'Remise partenaire' }],
  5: [{ id: 5, amount_ht: 5, remise_percent: 5, description: 'Remise fidélité' }],
  6: [{ id: 6, amount_ht: 8, remise_percent: 8, description: 'Remise premium' }],
};

// Stockage des devis créés (simulé)
export const mockProposals = [
  {
    id: 1,
    ref: 'PR2024-001',
    socid: 1,
    client_name: 'Pharmacie du Centre',
    created_at: '2024-10-15T10:30:00',
    status: 'pending',
    total_ttc: 287.50,
    lines: [
      { product_id: 1, label: 'Crème Hydratante', qty: 3, price: 29.90 },
      { product_id: 2, label: 'Sérum Anti-Âge', qty: 2, price: 45.50 },
    ]
  },
  {
    id: 2,
    ref: 'PR2024-002',
    socid: 2,
    client_name: 'Pharmacie Principale',
    created_at: '2024-10-14T14:20:00',
    status: 'validated',
    total_ttc: 156.80,
    lines: [
      { product_id: 3, label: 'Lotion Tonique', qty: 4, price: 22.00 },
      { product_id: 6, label: 'Gel Nettoyant', qty: 3, price: 18.50 },
    ]
  },
  {
    id: 3,
    ref: 'PR2024-003',
    socid: 3,
    client_name: 'Pharmacie de la Mairie',
    created_at: '2024-10-13T09:15:00',
    status: 'pending',
    total_ttc: 420.00,
    lines: [
      { product_id: 4, label: 'Masque Purifiant', qty: 5, price: 35.00 },
      { product_id: 5, label: 'Contour des Yeux', qty: 6, price: 38.90 },
    ]
  },
  {
    id: 4,
    ref: 'PR2024-004',
    socid: 4,
    client_name: 'Pharmacie du Parc',
    created_at: '2024-10-12T16:45:00',
    status: 'validated',
    total_ttc: 198.00,
    lines: [
      { product_id: 7, label: 'Crème de Nuit', qty: 3, price: 42.00 },
      { product_id: 8, label: 'Eau Micellaire', qty: 4, price: 15.90 },
    ]
  },
  {
    id: 5,
    ref: 'PR2024-005',
    socid: 5,
    client_name: 'Pharmacie Centrale',
    created_at: '2024-10-11T11:00:00',
    status: 'draft',
    total_ttc: 324.50,
    lines: [
      { product_id: 9, label: 'Crème Solaire SPF50', qty: 8, price: 24.90 },
      { product_id: 10, label: 'Baume à Lèvres', qty: 15, price: 8.50 },
    ]
  },
];

// Helper pour générer un ID unique
let proposalIdCounter = 1;
export const generateProposalId = () => proposalIdCounter++;

// Helper pour simuler un délai réseau
export const mockDelay = (ms = 300) => new Promise(resolve => setTimeout(resolve, ms));