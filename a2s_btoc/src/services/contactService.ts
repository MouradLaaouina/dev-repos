import { apiClient } from './apiClient';
import { Contact, ContactStatus, Platform, RequestType, City, Gender, Source, Brand } from '../types';

const mapDolibarrToContact = (d: any): Contact => ({
  id: d.id,
  nom: d.name || d.nom || '',
  telephone: d.phone || '',
  telephone2: d.phone_perso || '',
  address: d.address || '',
  plateforme: (d.array_options?.options_plateforme as Platform) || 'Clients',
  message: d.array_options?.options_message || '',
  typeDeDemande: (d.array_options?.options_type_demande as RequestType) || 'Information',
  ville: (d.town as City) || '',
  sexe: (d.array_options?.options_sexe as Gender) || 'Homme',
  fromAds: d.array_options?.options_from_ads === '1',
  status: (d.array_options?.options_status as ContactStatus) || 'nouveau',
  createdAt: d.date_creation ? new Date(d.date_creation * 1000) : new Date(),
  updatedAt: d.date_modification ? new Date(d.date_modification * 1000) : new Date(),
  agentId: d.array_options?.options_agent_id || '',
  agentCode: d.array_options?.options_commerciale || '',
  agentName: d.array_options?.options_commerciale || '',
  codeAgence: d.array_options?.options_code_agence || '',
  source: (d.array_options?.options_source as Source) || 'PAS DÉFINI',
  commerciale: d.array_options?.options_commerciale || '',
  marque: (d.array_options?.options_marque as Brand) || 'D-WHITE',
  interet: d.array_options?.options_interet || '',
  clientCode: d.code_client || '',
});

const mapContactToDolibarr = (c: Partial<Contact>): any => ({
  name: c.nom,
  phone: c.telephone,
  phone_perso: c.telephone2,
  address: c.address,
  town: c.ville,
  code_client: c.clientCode,
  client: 1, // It's a customer
  array_options: {
    options_plateforme: c.plateforme,
    options_message: c.message,
    options_type_demande: c.typeDeDemande,
    options_sexe: c.sexe,
    options_from_ads: c.fromAds ? '1' : '0',
    options_status: c.status,
    options_agent_id: c.agentId,
    options_code_agence: c.codeAgence,
    options_source: c.source,
    options_commerciale: c.commerciale,
    options_marque: c.marque,
    options_interet: c.interet,
  }
});

export const contactService = {
  async fetchContacts(page = 0, limit = 100): Promise<Contact[]> {
    const response = await apiClient.get<any>(`/thirdparties?page=${page}&limit=${limit}`);
    return (response.result || []).map(mapDolibarrToContact);
  },

  async getContactById(id: string): Promise<Contact> {
    const response = await apiClient.get<any>(`/thirdparties/${id}`);
    return mapDolibarrToContact(response.result);
  },

  async createContact(contact: Partial<Contact>): Promise<Contact> {
    const body = mapContactToDolibarr(contact);
    const response = await apiClient.post<any>('/thirdparties', body);
    return mapDolibarrToContact(response.result);
  },

  async updateContact(id: string, contact: Partial<Contact>): Promise<void> {
    const body = mapContactToDolibarr(contact);
    await apiClient.put<any>(`/thirdparties/${id}`, body);
  },

  async deleteContact(id: string): Promise<void> {
    await apiClient.delete<any>(`/thirdparties/${id}`);
  }
};
