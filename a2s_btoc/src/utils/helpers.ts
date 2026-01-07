import { ContactStatus, Platform, RequestType } from '../types';

export function generateId(): string {
  return Math.random().toString(36).substring(2) + Date.now().toString(36);
}

export function formatDate(date: Date | string | number | null | undefined): string {
  if (!date) return 'N/A';
  
  const dateObj = new Date(date);
  if (isNaN(dateObj.getTime())) return 'N/A';
  
  return dateObj.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

export function formatTime(date: Date | string | number | null | undefined): string {
  if (!date) return 'N/A';
  
  const dateObj = new Date(date);
  if (isNaN(dateObj.getTime())) return 'N/A';
  
  return dateObj.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function formatDateTime(date: Date | string | number | null | undefined): string {
  if (!date) return 'N/A';
  
  const dateObj = new Date(date);
  if (isNaN(dateObj.getTime())) return 'N/A';
  
  return `${formatDate(dateObj)} à ${formatTime(dateObj)}`;
}

export function getStatusColor(status: ContactStatus): string {
  switch (status) {
    case 'nouveau':
      return 'bg-primary-100 text-primary-800';
    case 'à rappeler':
      return 'bg-warning-100 text-warning-800';
    case 'converti':
      return 'bg-success-100 text-success-800';
    case 'abandonné':
      return 'bg-gray-100 text-gray-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

export function getPlatformIcon(platform: Platform): string {
  switch (platform) {
    case 'Facebook':
      return 'facebook';
    case 'Instagram':
      return 'instagram';
    case 'WhatsApp':
      return 'message-circle';
    default:
      return 'message-square';
  }
}

export function getStatusStats(contacts: any[]): Record<ContactStatus, number> {
  return {
    nouveau: contacts.filter((contact) => contact.status === 'nouveau').length,
    'à rappeler': contacts.filter((contact) => contact.status === 'à rappeler').length,
    converti: contacts.filter((contact) => contact.status === 'converti').length,
    abandonné: contacts.filter((contact) => contact.status === 'abandonné').length,
  };
}

export function getPlatformStats(contacts: any[]): Record<string, number> {
  return {
    Facebook: contacts.filter((contact) => contact.plateforme === 'Facebook').length,
    Instagram: contacts.filter((contact) => contact.plateforme === 'Instagram').length,
    WhatsApp: contacts.filter((contact) => contact.plateforme === 'WhatsApp').length,
    Clients: contacts.filter((contact) => contact.plateforme === 'Clients').length,
    "Centre d'appel": contacts.filter((contact) => contact.plateforme === "Centre d'appel").length,
  };
}

export function calculateConversionRate(contacts: any[]): number {
  const total = contacts.length;
  if (total === 0) return 0;
  
  // Count orders (contacts with typeDeDemande === 'Commande')
  const orders = contacts.filter((contact) => contact.typeDeDemande === 'Commande').length;
  
  // Count converted contacts (status === 'converti')
  const converted = contacts.filter((contact) => contact.status === 'converti').length;
  
  // Use orders count for conversion rate calculation
  return (orders / total) * 100;
}

export function filterContacts(contacts: any[], filters: Record<string, any>): any[] {
  return contacts.filter((contact) => {
    for (const [key, value] of Object.entries(filters)) {
      if (value && contact[key] !== value) {
        return false;
      }
    }
    return true;
  });
}