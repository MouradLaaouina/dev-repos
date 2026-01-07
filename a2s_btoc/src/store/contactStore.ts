import { create } from 'zustand';
import { Contact, ContactStatus, RequestType } from '../types';
import { contactService } from '../services/contactService';
import toast from 'react-hot-toast';

interface ContactState {
  contacts: Contact[];
  loading: boolean;
  error: string | null;
  totalCount: number;
  fetchContacts: (reset?: boolean) => Promise<void>;
  fetchContactsWithPagination: (page: number, pageSize: number, filters?: any) => Promise<void>;
  addContact: (contact: Omit<Contact, 'id' | 'createdAt' | 'updatedAt' | 'status'>, existingContactId?: string) => Promise<Contact | null>;
  updateContact: (id: string, contact: Partial<Contact>) => Promise<void>;
  updateContactStatus: (id: string, status: ContactStatus) => Promise<void>;
  getContactsByAgentId: (agentId: string) => Contact[];
  getContactsByType: (type: RequestType) => Contact[];
  getClientContacts: () => Contact[];
  getContactById: (id: string) => Contact | undefined;
  deleteContact: (id: string) => Promise<void>;
  getFilteredContactsByTeam: (userRole: string, userCodeAgence?: string) => Contact[];
  searchContactsByPhoneNumber: (phoneNumber: string) => Promise<Contact[]>;
}

export const useContactStore = create<ContactState>()((set, get) => ({
  contacts: [],
  loading: false,
  error: null,
  totalCount: 0,

  fetchContacts: async (reset = true) => {
    set({ loading: true, error: null });

    try {
      console.log('🔄 Fetching contacts from Dolibarr...');
      const allContacts = await contactService.fetchContacts(0, 1000);
      
      set({ 
        contacts: allContacts, 
        loading: false, 
        totalCount: allContacts.length
      });

      toast.success(`📊 ${allContacts.length.toLocaleString()} contacts chargés`);

    } catch (error) {
      console.error('❌ Error fetching contacts:', error);
      set({ error: 'Failed to fetch contacts', loading: false });
      toast.error('Erreur lors du chargement des contacts');
    }
  },

  fetchContactsWithPagination: async (page = 1, pageSize = 100, filters = {}) => {
    set({ loading: true, error: null });

    try {
      console.log(`🔄 Fetching contacts from Dolibarr with pagination: page ${page}, size ${pageSize}`);
      
      const allContacts = await contactService.fetchContacts(page - 1, pageSize);
      
      set({ 
        contacts: allContacts,
        loading: false,
        totalCount: allContacts.length // Dolibarr API doesn't easily return total count without another call
      });
    } catch (error) {
      console.error('❌ Error fetching contacts:', error);
      set({ error: 'Failed to fetch contacts', loading: false });
      toast.error('Erreur lors du chargement des contacts');
    }
  },

  addContact: async (contactData, existingContactId) => {
    try {
      let contact;
      if (existingContactId) {
        await contactService.updateContact(existingContactId, contactData);
        contact = await contactService.getContactById(existingContactId);
        toast.success('Contact mis à jour');
      } else {
        contact = await contactService.createContact({
          ...contactData,
          status: 'nouveau'
        } as Partial<Contact>);
        toast.success('Contact ajouté avec succès');
      }

      const { contacts } = get();
      if (existingContactId) {
        set({
          contacts: contacts.map(c => c.id === existingContactId ? contact : c)
        });
      } else {
        set({ contacts: [contact, ...contacts] });
      }

      return contact;
    } catch (error) {
      console.error('❌ Error adding/updating contact:', error);
      toast.error('Erreur lors de l\'enregistrement du contact');
      return null;
    }
  },

  updateContact: async (id, contactUpdates) => {
    try {
      await contactService.updateContact(id, contactUpdates);
      const updatedContact = await contactService.getContactById(id);
      
      const { contacts } = get();
      set({
        contacts: contacts.map(c => c.id === id ? updatedContact : c)
      });
      toast.success('Contact mis à jour');
    } catch (error) {
      console.error('❌ Error updating contact:', error);
      toast.error('Erreur lors de la mise à jour du contact');
    }
  },

  updateContactStatus: async (id, status) => {
    try {
      await contactService.updateContact(id, { status });
      const { contacts } = get();
      set({
        contacts: contacts.map(c => c.id === id ? { ...c, status } : c)
      });
    } catch (error) {
      console.error('❌ Error updating status:', error);
      toast.error('Erreur lors de la mise à jour du statut');
    }
  },

  getContactsByAgentId: (agentId) => {
    return get().contacts.filter(c => c.agentId === agentId);
  },

  getContactsByType: (type) => {
    return get().contacts.filter(c => c.typeDeDemande === type);
  },

  getClientContacts: () => {
    return get().contacts.filter(c => c.plateforme === 'Clients');
  },

  getContactById: (id) => {
    return get().contacts.find(c => c.id === id);
  },

  deleteContact: async (id) => {
    try {
      await contactService.deleteContact(id);
      const { contacts } = get();
      set({
        contacts: contacts.filter(c => c.id !== id),
        totalCount: get().totalCount - 1
      });
      toast.success('Contact supprimé');
    } catch (error) {
      console.error('❌ Error deleting contact:', error);
      toast.error('Erreur lors de la suppression du contact');
    }
  },

  getFilteredContactsByTeam: (userRole, userCodeAgence) => {
    const { contacts } = get();
    if (userRole === 'admin') return contacts;
    if (userRole === 'superviseur' && userCodeAgence) {
      return contacts.filter(c => c.codeAgence === userCodeAgence);
    }
    return contacts;
  },

  searchContactsByPhoneNumber: async (phoneNumber) => {
    // In a real migration, we'd use a search endpoint in Dolibarr
    // For now, we search in the loaded contacts
    return get().contacts.filter(c => c.telephone.includes(phoneNumber) || c.telephone2?.includes(phoneNumber));
  }
}));
