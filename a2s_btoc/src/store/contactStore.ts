import { create } from 'zustand';
import { Contact, ContactStatus, Platform, RequestType } from '../types';
import { supabase } from '../lib/supabase';
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
      console.log('🔄 Fetching ALL contacts without limit...');
      
      // 🔥 FIXED: Get the exact total count first
      const { count: totalCount, error: countError } = await supabase
        .from('contacts')
        .select('*', { count: 'exact', head: true });
        // Don't exclude 'Clients' platform here to get the true total count

      if (countError) {
        console.error('❌ Error getting total count:', countError);
        throw countError;
      }

      console.log('📊 Total contacts in database:', totalCount);

      // 🔥 FIXED: Fetch ALL contacts without any limit using range
      const { data, error } = await supabase
        .from('contacts')
        .select('*')
        .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform from general fetching
        .range(0, 99999) // Set a very high range to get all records
        .order('created_at', { ascending: false });

      if (error) {
        console.error('❌ Error fetching contacts:', error);
        throw error;
      }

      console.log(`✅ Fetched ALL ${data?.length || 0} contacts from database (no 1000 limit)`);

      const allContacts: Contact[] = data?.map(contact => ({
        id: contact.id,
        nom: contact.nom,
        telephone: contact.telephone,
        telephone2: contact.telephone2,
        address: contact.address || '',
        plateforme: contact.plateforme,
        message: contact.message,
        typeDeDemande: contact.type_de_demande,
        ville: contact.ville,
        sexe: contact.sexe,
        fromAds: contact.from_ads,
        status: contact.status,
        createdAt: new Date(contact.created_at),
        agentId: contact.agent_id,
        agentCode: contact.commerciale,
        agentName: contact.commerciale,
        codeAgence: contact.code_agence,
        updatedAt: new Date(contact.updated_at),
        dateMessage: contact.date_message ? new Date(contact.date_message) : undefined,
        source: contact.source,
        commerciale: contact.commerciale,
        marque: contact.marque,
        interet: contact.interet,
        clientCode: contact.client_code || undefined,
      })) || [];

      console.log(`📊 Contact summary:`, {
        totalInDatabase: totalCount,
        loaded: allContacts.length,
        percentage: totalCount ? ((allContacts.length / totalCount) * 100).toFixed(1) + '%' : '0%'
      });

      set({ 
        contacts: allContacts, 
        loading: false, 
        totalCount: totalCount || 0
      });

      // Show success message with exact counts
      toast.success(`📊 ${allContacts.length.toLocaleString()} contacts chargés (${totalCount ? ((allContacts.length / totalCount) * 100).toFixed(1) : '0'}%)`);

    } catch (error) {
      console.error('❌ Error fetching contacts:', error);
      set({ error: 'Failed to fetch contacts', loading: false });
      toast.error('Erreur lors du chargement des contacts');
    }
  },

  fetchContactsWithPagination: async (page = 1, pageSize = 100, filters = {}) => {
    set({ loading: true, error: null });

    try {
      console.log(`🔄 Fetching contacts with pagination: page ${page}, size ${pageSize}`);
      
      // Special handling for call center agents (code_agence 000002)
      let relevantContactIds: string[] = [];
      if (filters.userRole === 'agent' && filters.userCodeAgence === '000002' && filters.userId) {
        console.log('🔍 Call center agent detected, fetching assigned leads...');
        
        // Get all leads assigned to this agent
        const { data: assignedLeads, error: leadsError } = await supabase
          .from('call_center_leads')
          .select('code_client')
          .eq('assigned_agent', filters.userId)
          .not('code_client', 'is', null);
        
        if (leadsError) {
          console.error('❌ Error fetching assigned leads:', leadsError);
          throw leadsError;
        }
        
        // Extract the code_client values (which are contact IDs)
        relevantContactIds = assignedLeads?.map(lead => lead.code_client).filter(Boolean) || [];
        console.log(`✅ Found ${relevantContactIds.length} assigned leads for call center agent`);
      }
      
      // Calculate the range for pagination
      const from = (page - 1) * pageSize;
      const to = from + pageSize - 1;
      
      // Get the exact total count first
      let countQuery = supabase
        .from('contacts')
        .select('*', { count: 'exact', head: true })
        .not('plateforme', 'eq', 'Clients'); // Exclude 'Clients' platform from count
      
      // Apply filters to count query
      if (filters.searchTerm) {
        countQuery = countQuery.or(
          `nom.ilike.%${filters.searchTerm}%,` +
          `telephone.ilike.%${filters.searchTerm}%,` +
          `message.ilike.%${filters.searchTerm}%,` +
          `ville.ilike.%${filters.searchTerm}%`
        );
      }
      
      if (filters.platformFilter) {
        countQuery = countQuery.eq('plateforme', filters.platformFilter);
      }
      
      if (filters.requestTypeFilter) {
        countQuery = countQuery.eq('type_de_demande', filters.requestTypeFilter);
      }
      
      if (filters.dateFilter?.startDate && filters.dateFilter?.endDate) {
        countQuery = countQuery
          .gte('created_at', filters.dateFilter.startDate.toISOString())
          .lte('created_at', filters.dateFilter.endDate.toISOString());
      }
      
      // Apply team/role filters
      if (filters.userRole === 'superviseur' && filters.userCodeAgence) {
        countQuery = countQuery.eq('code_agence', filters.userCodeAgence);
      } else if (filters.userRole === 'agent') {
        if (filters.userCodeAgence === '000002' && relevantContactIds.length > 0) {
          // For call center agents, filter by assigned leads
          countQuery = countQuery.in('id', relevantContactIds);
        } else if (filters.userId) {
          // For other agents, filter by agent_id (contacts they created)
          countQuery = countQuery.eq('agent_id', filters.userId);
        }
      }
      
      const { count: totalCount, error: countError } = await countQuery;

      if (countError) {
        console.error('❌ Error getting total count:', countError);
        throw countError;
      }

      console.log('📊 Total filtered contacts in database:', totalCount);

      // Build the data query with the same filters
      let dataQuery = supabase
        .from('contacts')
        .select('*')
        .not('plateforme', 'eq', 'Clients') // Exclude 'Clients' platform from data query
        .order('created_at', { ascending: false })
        .range(from, to);
      
      // Apply the same filters to data query
      if (filters.searchTerm) {
        dataQuery = dataQuery.or(
          `nom.ilike.%${filters.searchTerm}%,` +
          `telephone.ilike.%${filters.searchTerm}%,` +
          `message.ilike.%${filters.searchTerm}%,` +
          `ville.ilike.%${filters.searchTerm}%`
        );
      }
      
      if (filters.platformFilter) {
        dataQuery = dataQuery.eq('plateforme', filters.platformFilter);
      }
      
      if (filters.requestTypeFilter) {
        dataQuery = dataQuery.eq('type_de_demande', filters.requestTypeFilter);
      }
      
      if (filters.dateFilter?.startDate && filters.dateFilter?.endDate) {
        dataQuery = dataQuery
          .gte('created_at', filters.dateFilter.startDate.toISOString())
          .lte('created_at', filters.dateFilter.endDate.toISOString());
      }
      
      // Apply team/role filters
      if (filters.userRole === 'superviseur' && filters.userCodeAgence) {
        dataQuery = dataQuery.eq('code_agence', filters.userCodeAgence);
      } else if (filters.userRole === 'agent') {
        if (filters.userCodeAgence === '000002' && relevantContactIds.length > 0) {
          // For call center agents, filter by assigned leads
          dataQuery = dataQuery.in('id', relevantContactIds);
        } else if (filters.userId) {
          // For other agents, filter by agent_id (contacts they created)
          dataQuery = dataQuery.eq('agent_id', filters.userId);
        }
      }
      
      const { data, error } = await dataQuery;

      if (error) {
        console.error('❌ Error fetching contacts:', error);
        throw error;
      }

      console.log(`✅ Fetched ${data?.length || 0} contacts for page ${page}`);

      const paginatedContacts: Contact[] = data?.map(contact => ({
        id: contact.id,
        nom: contact.nom,
        telephone: contact.telephone,
        telephone2: contact.telephone2,
        address: contact.address || '',
        plateforme: contact.plateforme,
        message: contact.message,
        typeDeDemande: contact.type_de_demande,
        ville: contact.ville,
        sexe: contact.sexe,
        fromAds: contact.from_ads,
        status: contact.status,
        createdAt: new Date(contact.created_at),
        agentId: contact.agent_id,
        agentCode: contact.commerciale,
        agentName: contact.commerciale,
        codeAgence: contact.code_agence,
        updatedAt: new Date(contact.updated_at),
        dateMessage: contact.date_message ? new Date(contact.date_message) : undefined,
        source: contact.source,
        commerciale: contact.commerciale,
        marque: contact.marque,
        interet: contact.interet,
        clientCode: contact.client_code || undefined,
      })) || [];

      set({ 
        contacts: paginatedContacts, 
        loading: false, 
        totalCount: totalCount || 0
      });

    } catch (error) {
      console.error('❌ Error fetching contacts with pagination:', error);
      set({ error: 'Failed to fetch contacts', loading: false });
      toast.error('Erreur lors du chargement des contacts');
    }
  },

  addContact: async (contactData, existingContactId) => {
    try {
      if (existingContactId) {
        // Update existing contact
        console.log('🔄 Updating existing contact:', existingContactId, contactData);
        
        const updateData: any = {
          nom: contactData.nom,
          telephone: contactData.telephone,
          telephone2: contactData.telephone2,
          address: contactData.address,
          plateforme: contactData.plateforme,
          message: contactData.message,
          type_de_demande: contactData.typeDeDemande,
          ville: contactData.ville,
          sexe: contactData.sexe,
          from_ads: contactData.fromAds,
          agent_id: contactData.agentId,
          date_message: contactData.dateMessage,
          source: contactData.source,
          commerciale: contactData.agentCode,
          code_agence: contactData.codeAgence,
          marque: contactData.marque,
          interet: contactData.interet,
          payment_method: contactData.paymentMethod,
          transfer_number: contactData.transferNumber,
          updated_at: new Date().toISOString()
        };

        const { data, error } = await supabase
          .from('contacts')
          .update(updateData)
          .eq('id', existingContactId)
          .select()
          .single();

        if (error) throw error;

        const updatedContact: Contact = {
          id: data.id,
          nom: data.nom,
          telephone: data.telephone,
          telephone2: data.telephone2,
          address: data.address || '',
          plateforme: data.plateforme,
          message: data.message,
          typeDeDemande: data.type_de_demande,
          ville: data.ville,
          sexe: data.sexe,
          fromAds: data.from_ads,
          status: data.status,
          createdAt: new Date(data.created_at),
          agentId: data.agent_id,
          agentCode: data.commerciale,
          agentName: data.commerciale,
          codeAgence: data.code_agence,
          updatedAt: new Date(data.updated_at),
          dateMessage: data.date_message ? new Date(data.date_message) : undefined,
          source: data.source,
          commerciale: data.commerciale,
          marque: data.marque,
          interet: data.interet,
          clientCode: data.client_code || undefined,
        };

        set((state) => ({
          contacts: state.contacts.map(contact => 
            contact.id === existingContactId ? updatedContact : contact
          ),
        }));

        console.log('✅ Contact updated successfully:', data.id);
        toast.success(`Contact mis à jour avec succès`);
        return updatedContact;
      } else {
        // Add new contact
        console.log('👤 Adding new contact (client code will be database ID):', contactData);

        const { data, error } = await supabase
          .from('contacts')
          .insert([
            {
              nom: contactData.nom,
              telephone: contactData.telephone,
              telephone2: contactData.telephone2,
              address: contactData.address,
              plateforme: contactData.plateforme,
              message: contactData.message,
              type_de_demande: contactData.typeDeDemande,
              ville: contactData.ville,
              sexe: contactData.sexe,
              from_ads: contactData.fromAds,
              status: 'À confirmer',
              agent_id: contactData.agentId,
              date_message: contactData.dateMessage,
              source: contactData.source,
              commerciale: contactData.agentCode,
              code_agence: contactData.codeAgence,
              marque: contactData.marque,
              interet: contactData.interet,
              payment_method: contactData.paymentMethod,
              transfer_number: contactData.transferNumber,
            }
          ])
          .select()
          .single();

        if (error) throw error;

        const newContact: Contact = {
          id: data.id,
          nom: data.nom,
          telephone: data.telephone,
          telephone2: data.telephone2,
          address: data.address || '',
          plateforme: data.plateforme,
          message: data.message,
          typeDeDemande: data.type_de_demande,
          ville: data.ville,
          sexe: data.sexe,
          fromAds: data.from_ads,
          status: data.status,
          createdAt: new Date(data.created_at),
          agentId: data.agent_id,
          agentCode: data.commerciale,
          agentName: data.commerciale,
          codeAgence: data.code_agence,
          updatedAt: new Date(data.updated_at),
          dateMessage: data.date_message ? new Date(data.date_message) : undefined,
          source: data.source,
          commerciale: data.commerciale,
          marque: data.marque,
          interet: data.interet,
          clientCode: data.client_code || undefined,
        };

        set((state) => ({
          contacts: [newContact, ...state.contacts],
          totalCount: state.totalCount + 1,
        }));

        console.log('✅ Contact added successfully with database ID as client code:', data.id);
        toast.success(`Contact ajouté avec succès (Code client: ${data.id})`);
        return newContact;
      }
    } catch (error) {
      console.error('Error adding/updating contact:', error);
      toast.error('Erreur lors de l\'ajout/modification du contact');
      return null;
    }
  },

  updateContact: async (id, contactData) => {
    try {
      const updateData: any = {};
      
      if (contactData.nom) updateData.nom = contactData.nom;
      if (contactData.telephone) updateData.telephone = contactData.telephone;
      if (contactData.telephone2) updateData.telephone2 = contactData.telephone2;
      if (contactData.address) updateData.address = contactData.address;
      if (contactData.plateforme) updateData.plateforme = contactData.plateforme;
      if (contactData.message) updateData.message = contactData.message;
      if (contactData.typeDeDemande) updateData.type_de_demande = contactData.typeDeDemande;
      if (contactData.ville) updateData.ville = contactData.ville;
      if (contactData.sexe) updateData.sexe = contactData.sexe;
      if (contactData.fromAds !== undefined) updateData.from_ads = contactData.fromAds;
      if (contactData.status) updateData.status = contactData.status;
      if (contactData.source) updateData.source = contactData.source;
      if (contactData.agentCode) updateData.commerciale = contactData.agentCode;
      if (contactData.codeAgence) updateData.code_agence = contactData.codeAgence;
      if (contactData.marque) updateData.marque = contactData.marque;
      if (contactData.interet) updateData.interet = contactData.interet;
      if (contactData.clientCode) updateData.client_code = contactData.clientCode;

      const { error } = await supabase
        .from('contacts')
        .update(updateData)
        .eq('id', id);

      if (error) throw error;

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

  updateContactStatus: async (id, status) => {
    try {
      const { error } = await supabase
        .from('contacts')
        .update({ status })
        .eq('id', id);

      if (error) throw error;

      set((state) => ({
        contacts: state.contacts.map((contact) =>
          contact.id === id
            ? { ...contact, status, updatedAt: new Date() }
            : contact
        ),
      }));

      toast.success('Statut mis à jour avec succès');
    } catch (error) {
      console.error('Error updating contact status:', error);
      toast.error('Erreur lors de la mise à jour du statut');
    }
  },

  getContactsByAgentId: (agentId) => {
    return get().contacts.filter((contact) => contact.agentId === agentId);
  },

  getContactsByType: (type) => {
    return get().contacts.filter((contact) => contact.typeDeDemande === type);
  },

  getClientContacts: () => {
    // Return all contacts regardless of typeDeDemande
    return get().contacts;
  },

  getContactById: (id) => {
    return get().contacts.find((contact) => contact.id === id);
  },

  deleteContact: async (id) => {
    try {
      const { error } = await supabase
        .from('contacts')
        .delete()
        .eq('id', id);

      if (error) throw error;

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

  // Filter contacts based on team platform access
  getFilteredContactsByTeam: (userRole: string, userCodeAgence?: string) => {
    const allContacts = get().contacts;
    // Note: 'Clients' platform is already excluded at the data fetching level
    
    // Admins see all data
    if (userRole === 'admin') {
      return allContacts;
    }

    // For supervisors, filter by code_agence
    if (userRole === 'superviseur' && userCodeAgence) {
      console.log(`🔍 Filtering contacts for supervisor of team: ${userCodeAgence}`);
      return allContacts.filter(contact => contact.codeAgence === userCodeAgence);
    }

    // For regular agents, filter by platform access based on team
    if (userRole === 'agent') {
      switch (userCodeAgence) {
        case '000001': // Réseaux sociaux team - only Facebook and Instagram
          return allContacts.filter(contact => 
            contact.plateforme === 'Facebook' || contact.plateforme === 'Instagram'
          );
        case '000002': // Centre d'appel team - all platforms
          return allContacts; // No platform filtering for call center
        case '000003': // WhatsApp team - only WhatsApp
          return allContacts.filter(contact => contact.plateforme === 'WhatsApp');
        default:
          return allContacts; // Default: show all if no team code
      }
    }

    // Default fallback - return all contacts
    return allContacts;
  },

  // New function to search contacts by phone number
  searchContactsByPhoneNumber: async (phoneNumber: string) => {
    if (!phoneNumber || phoneNumber.length < 3) {
      return [];
    }

    try {
      console.log('🔍 Searching contacts by phone number:', phoneNumber);
      
      // Search for contacts with matching phone number (primary or secondary)
      const { data, error } = await supabase
        .from('contacts')
        .select('*')
        .or(`telephone.ilike.%${phoneNumber}%,telephone2.ilike.%${phoneNumber}%`)
        // Don't exclude 'Clients' platform here to allow searching for them
        .order('created_at', { ascending: false })
        .limit(10);

      if (error) {
        console.error('❌ Error searching contacts:', error);
        throw error;
      }

      console.log(`✅ Found ${data?.length || 0} contacts matching phone number`);

      // Map database results to Contact objects
      const matchingContacts: Contact[] = data?.map(contact => ({
        id: contact.id,
        nom: contact.nom,
        telephone: contact.telephone,
        telephone2: contact.telephone2,
        address: contact.address || '',
        plateforme: contact.plateforme,
        message: contact.message,
        typeDeDemande: contact.type_de_demande,
        ville: contact.ville,
        sexe: contact.sexe,
        fromAds: contact.from_ads,
        status: contact.status,
        createdAt: new Date(contact.created_at),
        agentId: contact.agent_id,
        agentCode: contact.commerciale,
        agentName: contact.commerciale,
        codeAgence: contact.code_agence,
        updatedAt: new Date(contact.updated_at),
        dateMessage: contact.date_message ? new Date(contact.date_message) : undefined,
        source: contact.source,
        commerciale: contact.commerciale,
        marque: contact.marque,
        interet: contact.interet,
        clientCode: contact.client_code || undefined,
      })) || [];

      return matchingContacts;
    } catch (error) {
      console.error('Error searching contacts by phone number:', error);
      toast.error('Erreur lors de la recherche des contacts');
      return [];
    }
  }
}));