export const API_BASE_URL = import.meta.env.VITE_API_URL || 'https://A-COMPLETER-LOL/api';

export const PAYMENT_METHODS = [
  'Carte Bancaire',
  'Virement',
  'Chèque',
  'Prélèvement',
];

export const USER_TYPES = {
  DERMO: 'dermo',
  TELE: 'tele',
  DELEGUE: 'delegue',
};

export const USER_TYPE_LABELS = {
  [USER_TYPES.DERMO]: 'Dermo-conseillère',
  [USER_TYPES.TELE]: 'Télé dermo-conseillère',
  [USER_TYPES.DELEGUE]: 'Délégué',
};