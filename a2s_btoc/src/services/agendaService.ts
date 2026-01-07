import { apiClient } from './apiClient';
import { CallLog, CallStatus } from '../types';

const mapDolibarrToCallLog = (d: any): CallLog => ({
  id: d.id,
  clientId: d.socid,
  callStatus: (d.array_options?.options_call_status as CallStatus) || 'À rappeler',
  satisfactionLevel: parseInt(d.array_options?.options_satisfaction_level) || 0,
  interested: d.array_options?.options_interested || 'Peut-être',
  callDate: d.datep ? new Date(d.datep * 1000) : new Date(),
  nextCallDate: d.datef ? new Date(d.datef * 1000) : undefined,
  notes: d.note || '',
  agentId: d.userownerid || '',
  createdAt: d.date_creation ? new Date(d.date_creation * 1000) : new Date(),
});

const mapCallLogToDolibarr = (c: Partial<CallLog>): any => ({
  socid: c.clientId,
  label: `Call Status: ${c.callStatus}`,
  note: c.notes,
  datep: c.callDate ? Math.floor(c.callDate.getTime() / 1000) : Math.floor(Date.now() / 1000),
  datef: c.nextCallDate ? Math.floor(c.nextCallDate.getTime() / 1000) : undefined,
  userownerid: c.agentId,
  array_options: {
    options_call_status: c.callStatus,
    options_satisfaction_level: String(c.satisfactionLevel),
    options_interested: c.interested,
  }
});

export const agendaService = {
  async fetchEvents(page = 0, limit = 100): Promise<CallLog[]> {
    const response = await apiClient.get<any>(`/agenda?page=${page}&limit=${limit}`);
    return (response.result || []).map(mapDolibarrToCallLog);
  },

  async createEvent(event: Partial<CallLog>): Promise<CallLog> {
    const body = mapCallLogToDolibarr(event);
    const response = await apiClient.post<any>('/agenda', body);
    return mapDolibarrToCallLog(response.result);
  },

  async updateEvent(id: string, event: Partial<CallLog>): Promise<void> {
    const body = mapCallLogToDolibarr(event);
    await apiClient.put<any>(`/agenda/${id}`, body);
  },

  async deleteEvent(id: string): Promise<void> {
    await apiClient.delete<any>(`/agenda/${id}`);
  }
};
