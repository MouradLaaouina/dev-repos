import { create } from 'zustand';
import { CallCenterLead, CallLog, CallStatus } from '../types';
import { supabase } from '../lib/supabase';
import toast from 'react-hot-toast';

interface CallCenterState {
  leads: CallCenterLead[];
  callLogs: { [leadId: string]: CallLog[] };
  loading: boolean;
  error: string | null;
  fetchLeads: (userId?: string, userRole?: string, userCodeAgence?: string) => Promise<void>;
  loadCallLogs: () => Promise<void>;
  saveCallLog: (callLog: Omit<CallLog, 'id' | 'createdAt'>) => Promise<void>;
  getLeadsByStatus: (status: CallStatus) => CallCenterLead[];
  getAgentStats: (agentId: string) => {
    totalCalls: number;
    todayCalls: number;
    averageSatisfaction: number;
    ordersConfirmed: number;
  };
}

export const useCallCenterStore = create<CallCenterState>()((set, get) => ({
  leads: [],
  callLogs: {},
  loading: false,
  error: null,

  fetchLeads: async (userId?: string, userRole?: string, userCodeAgence?: string) => {
    set({ loading: true, error: null });
    
    try {
      console.log('🔍 DEBUG: fetchLeads called with params:', { userId, userRole, userCodeAgence });
      console.log('🔄 Fetching call center leads from database...', { userId, userRole, userCodeAgence });
      
      // If user is a supervisor, first get all agents in their team
      let agentIds: string[] = [];
      if (userRole === 'superviseur' && userCodeAgence) {
        const { data: teamAgents, error: teamError } = await supabase
          .from('users')
          .select('id')
          .eq('code_agence', userCodeAgence);
        
        if (teamError) {
          console.error('❌ Error fetching team agents:', teamError);
          throw teamError;
        }
        
        agentIds = teamAgents?.map(agent => agent.id) || [];
        console.log(`✅ Found ${agentIds.length} agents in team ${userCodeAgence}`);
      }
      
      // Fetch leads from call_center_leads table with assigned agent name
      let query = supabase
        .from('call_center_leads')
        .select(`
          *,
          assigned_agent_user:assigned_agent (
            id, name, email, role, code, code_agence
          )
        `)
        .order('purchase_date', { ascending: false });

      // Filter by agent if specified (for non-admin users)
      if (userRole === 'agent' && userId) {
        console.log('🔍 DEBUG: Filtering leads by agent ID:', userId);
        query = query.eq('assigned_agent', userId);
      } else if (userRole === 'superviseur' && agentIds.length > 0) {
        // For supervisors, filter by all agents in their team
        console.log('🔍 DEBUG: Filtering leads by team agent IDs:', agentIds);
        query = query.in('assigned_agent', agentIds);
      }

      console.log('🔍 DEBUG: Final query before execution:', query);
      const { data: leadsData, error: leadsError } = await query;

      if (leadsError) {
        console.error('❌ Error fetching call center leads:', leadsError);
        throw leadsError;
      }

      console.log('🔍 DEBUG: Raw leads data from database:', leadsData);
      console.log('✅ Fetched call center leads:', leadsData?.length);

      // Fetch contacts separately if we have code_client values
      const codeClients = leadsData?.filter(lead => lead.code_client).map(lead => lead.code_client) || [];
      console.log('🔍 DEBUG: Code clients found:', codeClients);
      let contactsMap: { [key: string]: any } = {};

      if (codeClients.length > 0) {
        const { data: contactsData, error: contactsError } = await supabase
          .from('contacts')
          .select('id, nom, telephone, status, client_code')
          .in('client_code', codeClients);

        if (contactsError) {
          console.warn('⚠️ Error fetching contacts:', contactsError);
        } else {
          console.log('🔍 DEBUG: Contacts data fetched:', contactsData);
          // Create a map of client_code to contact data
          contactsData?.forEach(contact => {
            if (contact.client_code) {
              contactsMap[contact.client_code] = contact;
            }
          });
          console.log('🔍 DEBUG: Contacts map created:', contactsMap);
        }
      }

      // Transform database data into CallCenterLead objects
      const leads: CallCenterLead[] = leadsData?.map(leadData => {
        const contact = leadData.code_client ? contactsMap[leadData.code_client] : null;
        
        // Debug specific lead
        if (leadData.phone_number === '0662154605' || leadData.id === 'c0c8895a-d13a-4ad6-a451-d206ca65a48d') {
          console.log('🔍 DEBUG: Found target lead:', {
            id: leadData.id,
            phone: leadData.phone_number,
            assignedTo: leadData.assigned_agent,
            assignedName: leadData.assigned_agent_user?.name,
            contact: contact
          });
        }
        
        return {
          id: leadData.id,
          purchaseDate: new Date(leadData.purchase_date),
          name: leadData.name,
          phoneNumber: leadData.phone_number,
          commercialAgent: leadData.commercial_agent,
          productBought: leadData.product_bought,
          brand: 'D-WHITE', // Default brand, can be extracted from product_bought if needed
          status: 'nouveau', // Default status
          assignedTo: leadData.assigned_agent,
          // Get the agent name from the joined user data
          assignedName: leadData.assigned_agent_user?.name || null,
          callLogs: [], // Will be populated separately
          lastCallDate: undefined,
          nextCallDate: undefined,
          codeClient: leadData.code_client,
          contactId: contact?.id // Store the actual contact ID for foreign key reference
        };
      }) || [];

      // Debug the final leads array
      console.log('🔍 DEBUG: Final leads array:', leads.map(lead => ({
        id: lead.id,
        phone: lead.phoneNumber,
        assignedTo: lead.assignedTo,
        assignedName: lead.assignedName
      })));
      
      // Check if the specific lead we're looking for is in the final array
      const targetLead = leads.find(lead => 
        lead.phoneNumber === '0662154605' || 
        lead.id === 'c0c8895a-d13a-4ad6-a451-d206ca65a48d'
      );
      console.log('🔍 DEBUG: Target lead in final array:', targetLead);
      set({ leads, loading: false });
      console.log('✅ Call center leads processed:', leads.length);
      
      // Load call logs after leads are loaded to ensure proper association
      await get().loadCallLogs();
      
    } catch (error) {
      console.error('❌ Error fetching call center leads:', error);
      set({ error: 'Failed to fetch leads', loading: false });
      toast.error('Erreur lors du chargement des leads');
    }
  },

  loadCallLogs: async () => {
    try {
      console.log('🔄 Loading call logs...');
      // Get all leads from the store
      const { leads } = get();      
      // Create a map of leads by ID for efficient lookup
      const leadsById = new Map(leads.map(lead => [lead.id, lead]));      
      console.log(`🔍 DEBUG: Created lookup map - leads by ID: ${leadsById.size}`);
      
      const { data, error } = await supabase
        .from('call_logs')
        .select('*')
        .order('created_at', { ascending: false });

      if (error) {
        console.error('❌ Error loading call logs:', error);
        throw error;
      }

      console.log(`🔍 DEBUG: Fetched ${data?.length || 0} call logs from database`);
      
      // Group call logs by lead ID (using lead_id if available, fallback to client_id)
      const groupedLogs: { [leadId: string]: CallLog[] } = {};
      
      console.log('🔍 DEBUG: Processing call logs for association with leads...');
      
      data?.forEach(log => {
        // STRICT MATCHING: Only associate call logs that have a valid lead_id
        // that matches a lead in our store
        if (log.lead_id && leadsById.has(log.lead_id)) {
          const leadId = log.lead_id;
          console.log(`🔍 DEBUG: Found matching lead_id ${leadId} for call log ${log.id}`);
        
          if (!groupedLogs[leadId]) {
            groupedLogs[leadId] = [];
          }
          
          groupedLogs[leadId].push({
            id: log.id,
            clientId: log.client_id,
            leadId: log.lead_id,
            callStatus: log.call_status,
            satisfactionLevel: log.satisfaction_level,
            interested: log.interested,
            callDate: new Date(log.call_date),
            nextCallDate: log.next_call_date ? new Date(log.next_call_date) : undefined,
            nextCallTime: log.next_call_time,
            notes: log.notes,
            agentId: log.agent_id,
            createdAt: new Date(log.created_at)
          });
        }
      });
      
      // Sort all logs by date (newest first) for each lead
      Object.keys(groupedLogs).forEach(leadId => {
        groupedLogs[leadId].sort((a, b) => b.callDate.getTime() - a.callDate.getTime());
      });

      // Log summary of grouped logs
      console.log(`🔍 DEBUG: Grouped logs for ${Object.keys(groupedLogs).length} leads`);
      Object.keys(groupedLogs).forEach(leadId => {
        console.log(`🔍 DEBUG: Lead ${leadId} has ${groupedLogs[leadId].length} call logs`);
      });

      // Debug the specific lead's call logs
      const targetLeadId = 'c0c8895a-d13a-4ad6-a451-d206ca65a48d';
      console.log(`🔍 DEBUG: Call logs for target lead (${targetLeadId}):`, groupedLogs[targetLeadId]);
      
      // Count total call logs processed
      const totalCallLogs = Object.values(groupedLogs).reduce((sum, logs) => sum + logs.length, 0);
      console.log(`🔍 DEBUG: Total call logs successfully associated with leads: ${totalCallLogs}`);
      
      // Update leads with call log information
      set(state => {
        const updatedLeads = state.leads.map(lead => {
          // Get logs for this lead (strict matching by lead.id)
          const logs = groupedLogs[lead.id] || [];          
          const lastLog = logs[0]; // Most recent
          
          if (logs.length > 0) {
            console.log(`🔍 DEBUG: Lead ${lead.id} (${lead.phoneNumber}) has ${logs.length} logs`);
          }
          
          return {
            ...lead,
            callLogs: logs,
            lastCallDate: lastLog?.callDate,
            nextCallDate: lastLog?.nextCallDate
          };
        });

        return {
          callLogs: groupedLogs,
          leads: updatedLeads
        };
      });

      console.log('✅ Call logs loaded and integrated');
      console.log('📊 Summary: Loaded call logs for', Object.keys(groupedLogs).length, 'leads');
      
    } catch (error) {
      console.error('❌ Error loading call logs:', error);
      toast.error('Erreur lors du chargement des logs d\'appel');
    }
  },

  saveCallLog: async (callLogData) => {
    try {
      console.log('💾 Saving call log:', callLogData);
      console.log('🔍 DEBUG: Lead ID for this call log:', callLogData.leadId);
      
      // Ensure we have a valid lead ID
      if (!callLogData.leadId) {
        console.error('❌ Missing lead ID for call log');
        throw new Error('Missing lead ID for call log');
      }
      
      let clientId = callLogData.clientId;
      
      // First, check if the provided clientId exists in contacts table
      if (clientId) {
        const { data: existingContacts, error: checkError } = await supabase
          .from('contacts')
          .select('id')
          .eq('id', clientId);

        if (checkError) {
          console.error('❌ Error checking contact existence:', checkError);
          throw checkError;
        }

        if (!existingContacts || existingContacts.length === 0) {
          console.log('⚠️ Provided clientId does not exist in contacts table, creating new contact');
          clientId = null; // Reset to create a new contact
        } else {
          console.log('✅ Contact exists, using existing clientId');
        }
      }

      // If no valid clientId, create a new contact
      if (!clientId) {
        const lead = get().leads.find(l => l.id === callLogData.leadId);
        if (!lead) {
          throw new Error('Lead not found');
        }

        console.log('🔄 Creating new contact for call log...');
        
        // Create a contact for this lead
        const { data: contactData, error: contactError } = await supabase
          .from('contacts')
          .insert([
            {
              nom: lead.name,
              telephone: lead.phoneNumber,
              plateforme: 'WhatsApp', // Default for call center
              message: `Contact créé depuis le centre d'appel - Produit: ${lead.productBought}`,
              type_de_demande: 'Information',
              ville: 'Casablanca',
              sexe: 'Femme',
              from_ads: false,
              status: 'À confirmer',
              agent_id: callLogData.agentId,
              source: 'CENTRE APPEL',
              commerciale: lead.commercialAgent,
              code_agence: '000002'
            }
          ])
          .select()
          .single();

        if (contactError) {
          console.error('❌ Error creating contact for call log:', contactError);
          throw contactError;
        }

        clientId = contactData.id;
        console.log('✅ Created contact for call log:', clientId);

        // Update the contact with its UUID as the client_code to ensure uniqueness
        const { error: updateContactError } = await supabase
          .from('contacts')
          .update({ client_code: clientId })
          .eq('id', clientId);

        if (updateContactError) {
          console.error('❌ Error updating contact with client_code:', updateContactError);
          throw updateContactError;
        }

        // Update the lead with the new contact code
        const { error: updateLeadError } = await supabase
          .from('call_center_leads')
          .update({ code_client: clientId })
          .eq('id', callLogData.leadId);

        if (updateLeadError) {
          console.warn('⚠️ Could not update lead with contact code:', updateLeadError);
        }

        // Update local state
        set(state => ({
          leads: state.leads.map(l => 
            l.id === callLogData.leadId 
              ? { ...l, contactId: clientId, codeClient: clientId }
              : l
          )
        }));
      }

      // Verify the clientId exists before inserting call log
      const { data: finalContactCheck, error: finalCheckError } = await supabase
        .from('contacts')
        .select('id')
        .eq('id', clientId);

      if (finalCheckError) {
        console.error('❌ Error in final contact check:', finalCheckError);
        throw finalCheckError;
      }

      if (!finalContactCheck || finalContactCheck.length === 0) {
        throw new Error(`Contact with ID ${clientId} does not exist in contacts table`);
      }

      console.log('✅ Verified contact exists, proceeding with call log insertion');

      // Now save the call log with the valid client_id
      const { data, error } = await supabase
        .from('call_logs')
        .insert({
          lead_id: callLogData.leadId, // Ensure lead_id is always set
          client_id: clientId,
          call_status: callLogData.callStatus,
          satisfaction_level: callLogData.satisfactionLevel,
          interested: callLogData.interested,
          call_date: callLogData.callDate.toISOString(),
          next_call_date: callLogData.nextCallDate?.toISOString(),
          next_call_time: callLogData.nextCallTime,
          notes: callLogData.notes,
          agent_id: callLogData.agentId
        })
        .select()
        .single();

      if (error) {
        console.error('❌ Error saving call log:', error);
        throw error;
      }
      
      console.log('✅ Call log saved successfully with lead_id:', data.lead_id);

      // Update local state
      const newCallLog: CallLog = {
        id: data.id,
        clientId: data.client_id,
        leadId: data.lead_id,
        callStatus: data.call_status,
        satisfactionLevel: data.satisfaction_level,
        interested: data.interested,
        callDate: new Date(data.call_date),
        nextCallDate: data.next_call_date ? new Date(data.next_call_date) : undefined,
        nextCallTime: data.next_call_time,
        notes: data.notes,
        agentId: data.agent_id,
        createdAt: new Date(data.created_at)
      };

      set(state => {
        const leadId = newCallLog.leadId || newCallLog.clientId;
        const updatedCallLogs = { ...state.callLogs };
        if (!updatedCallLogs[leadId]) {
          updatedCallLogs[leadId] = [];
        }
        updatedCallLogs[leadId].unshift(newCallLog);

        // Update the corresponding lead
        const updatedLeads = state.leads.map(lead => {
          if (lead.id === leadId) {
            return {
              ...lead,
              callLogs: updatedCallLogs[lead.id],
              lastCallDate: newCallLog.callDate,
              nextCallDate: newCallLog.nextCallDate
            };
          }
          return lead;
        });

        return {
          callLogs: updatedCallLogs,
          leads: updatedLeads
        };
      });

      console.log('✅ Call log saved successfully');
      
      // Reload call logs to ensure the UI is updated with the latest data
      await get().loadCallLogs();
      
      toast.success('Suivi d\'appel enregistré avec succès');
      
    } catch (error) {
      console.error('❌ Error saving call log:', error);
      toast.error('Erreur lors de l\'enregistrement du suivi');
      throw error;
    }
  },

  getLeadsByStatus: (status: CallStatus) => {
    const { leads, callLogs } = get();
    
    console.log('🔍 DEBUG: getLeadsByStatus called with status:', status);
    console.log('🔍 DEBUG: Total leads before filtering:', leads.length);
    
    // Modified to only return leads whose MOST RECENT call log matches the status
    const filteredLeads = leads.filter(lead => {
      const logs = callLogs[lead.id] || [];
      
      // If there are no logs, this lead doesn't match any status
      if (logs.length === 0) return false;
      
      // Check if the most recent log (first in the array) has the requested status
      return logs[0].callStatus === status;
    });
    
    console.log('🔍 DEBUG: Filtered leads by status:', {
      status,
      count: filteredLeads.length,
      leadIds: filteredLeads.map(l => l.id)
    });
    
    // Check if our target lead is in the filtered results
    const targetLeadId = 'c0c8895a-d13a-4ad6-a451-d206ca65a48d';
    const targetInResults = filteredLeads.some(lead => lead.id === targetLeadId);
    console.log(`🔍 DEBUG: Target lead (${targetLeadId}) in filtered results:`, targetInResults);
    
    return filteredLeads;
  },

  getAgentStats: (agentId: string) => {
    const { callLogs } = get();
    const allLogs = Object.values(callLogs).flat().filter(log => log.agentId === agentId);
    
    console.log('🔍 DEBUG: getAgentStats for agent:', agentId);
    console.log('🔍 DEBUG: Total logs for this agent:', allLogs.length);
    
    const today = new Date().toDateString();
    const todayLogs = allLogs.filter(log => log.callDate.toDateString() === today);
    
    const totalSatisfaction = allLogs.reduce((sum, log) => sum + log.satisfactionLevel, 0);
    const averageSatisfaction = allLogs.length > 0 ? totalSatisfaction / allLogs.length : 0;
    
    const ordersConfirmed = allLogs.filter(log => log.callStatus === 'Commande').length;

    return {
      totalCalls: allLogs.length,
      todayCalls: todayLogs.length,
      averageSatisfaction,
      ordersConfirmed
    };
  }
}));