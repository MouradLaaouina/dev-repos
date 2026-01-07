import { create } from 'zustand';
import { CallCenterLead, CallLog, CallStatus } from '../types';
import { contactService } from '../services/contactService';
import { agendaService } from '../services/agendaService';
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

  fetchLeads: async (userId, userRole, userCodeAgence) => {
    set({ loading: true, error: null });
    try {
      // In Dolibarr, we fetch all third parties and filter them for the call center
      const contacts = await contactService.fetchContacts(0, 1000);
      
      const leads: CallCenterLead[] = contacts
        .filter(c => c.plateforme === 'Centre d\'appel' || c.status === 'à rappeler')
        .map(c => ({
          id: c.id,
          purchaseDate: c.createdAt,
          name: c.nom,
          phoneNumber: c.telephone,
          commercialAgent: c.agentName || '',
          productBought: c.interet || '',
          brand: c.marque || 'D-WHITE',
          status: c.status,
          contactId: c.id,
          callLogs: []
        }));

      set({ leads, loading: false });
    } catch (error) {
      console.error('❌ Error fetching leads:', error);
      set({ error: 'Failed to fetch leads', loading: false });
    }
  },

  loadCallLogs: async () => {
    try {
      const logs = await agendaService.fetchEvents(0, 1000);
      const callLogsMap: { [leadId: string]: CallLog[] } = {};
      
      logs.forEach(log => {
        if (!callLogsMap[log.clientId]) {
          callLogsMap[log.clientId] = [];
        }
        callLogsMap[log.clientId].push(log);
      });

      set({ callLogs: callLogsMap });
    } catch (error) {
      console.error('❌ Error loading call logs:', error);
    }
  },

  saveCallLog: async (callLogData) => {
    try {
      const log = await agendaService.createEvent(callLogData as any);
      
      // Update contact status in Dolibarr
      await contactService.updateContact(callLogData.clientId, {
        status: callLogData.callStatus === 'Commande' ? 'converti' : 'à rappeler'
      } as any);

      const { callLogs } = get();
      const leadLogs = callLogs[callLogData.clientId] || [];
      set({
        callLogs: {
          ...callLogs,
          [callLogData.clientId]: [log, ...leadLogs]
        }
      });
      toast.success('Suivi d\'appel enregistré');
    } catch (error) {
      console.error('❌ Error saving call log:', error);
      toast.error('Erreur lors de l\'enregistrement');
    }
  },

  getLeadsByStatus: (status) => {
    const { leads, callLogs } = get();
    return leads.filter(lead => {
      const logs = callLogs[lead.id] || [];
      const lastStatus = logs.length > 0 ? logs[0].callStatus : 'À rappeler';
      return lastStatus === status;
    });
  },

  getAgentStats: (agentId) => {
    const { callLogs } = get();
    const allLogs = Object.values(callLogs).flat().filter(log => log.agentId === agentId);
    
    const today = new Date().toDateString();
    const todayLogs = allLogs.filter(log => log.callDate.toDateString() === today);
    
    const satisfactionLogs = allLogs.filter(log => log.satisfactionLevel > 0);
    const avgSatisfaction = satisfactionLogs.length > 0 
      ? satisfactionLogs.reduce((acc, log) => acc + log.satisfactionLevel, 0) / satisfactionLogs.length 
      : 0;
      
    const ordersConfirmed = allLogs.filter(log => log.callStatus === 'Commande').length;

    return {
      totalCalls: allLogs.length,
      todayCalls: todayLogs.length,
      averageSatisfaction: avgSatisfaction,
      ordersConfirmed
    };
  }
}));
