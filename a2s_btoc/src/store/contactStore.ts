import { create } from 'zustand';
import { Contact, ContactStatus, Platform, RequestType } from '../types';
import { apiFetch } from '../lib/api';
import toast from 'react-hot-toast';

interface ContactState {
  contacts: Contact[];
  loading: boolean;
  error: string | null;
  totalCount: number;
  fetchContacts: (reset?: boolean) => Promise<void>;
  addContact: (contact: Omit<Contact, 'id' | 'createdAt' | 'updatedAt' | 'status'>) => Promise<Contact | null>;
  updateContact: (id: string, contact: Partial<Contact>) => Promise<void>;
  deleteContact: (id: string) => Promise<void>;
}

export const useContactStore = create<ContactState>()((set, get) => ({
  contacts: [],
  loading: false,
  error: null,
  totalCount: 0,

  fetchContacts: async (reset = true) => {
    set({ loading: true, error: null });

    try {
      const data = await apiFetch('/contacts');
      const allContacts: Contact[] = data.map((contact: any) => ({
        ...contact,
        createdAt: new Date(contact.date_creation),
        updatedAt: new Date(contact.date_modification),
      }));

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

  addContact: async (contactData) => {
    try {
      const data = await apiFetch('/contacts', {
        method: 'POST',
        body: JSON.stringify(contactData),
      });

      const newContact: Contact = {
        ...data,
        createdAt: new Date(data.date_creation),
        updatedAt: new Date(data.date_modification),
      };

      set((state) => ({
        contacts: [newContact, ...state.contacts],
        totalCount: state.totalCount + 1,
      }));

      toast.success(`Contact ajouté avec succès (Code client: ${data.id})`);
      return newContact;
    } catch (error) {
      console.error('Error adding/updating contact:', error);
      toast.error('Erreur lors de l\'ajout/modification du contact');
      return null;
    }
  },

  updateContact: async (id, contactData) => {
    try {
      await apiFetch(`/contacts/${id}`, {
        method: 'PUT',
        body: JSON.stringify(contactData),
      });

      set((state) => ({
        contacts: state.contacts.map((contact) =>
          contact.id === id
            ? { ...contact, ...contactData, updatedAt: new Date() }
            : contact
        ),
      }));

      toast.success('Contact mis à jour avec succès');
    } catch (error) {
      console.error('Error updating contact:', error);
      toast.error('Erreur lors de la mise à jour du contact');
    }
  },

  deleteContact: async (id) => {
    try {
      await apiFetch(`/contacts/${id}`, {
        method: 'DELETE',
      });

      set((state) => ({
        contacts: state.contacts.filter((contact) => contact.id !== id),
        totalCount: Math.max(0, state.totalCount - 1),
      }));

      toast.success('Contact supprimé avec succès');
    } catch (error) {
      console.error('Error deleting contact:', error);
      toast.error('Erreur lors de la suppression du contact');
    }
  },
}));
