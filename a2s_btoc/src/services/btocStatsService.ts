import { apiClient } from './apiClient';

export interface BtocStat {
  rowid?: number;
  date_creation?: string;
  action: string;
  user_id?: number;
  details?: string;
}

export const btocStatsService = {
  async trackAction(action: string, details?: any): Promise<void> {
    try {
      const payload = {
        action,
        details: typeof details === 'object' ? JSON.stringify(details) : details,
      };
      await apiClient.post('/btoc-stats', payload);
    } catch (error) {
      console.error('Failed to track BToC action:', error);
    }
  },

  async getStats(params?: { action?: string; user_id?: number; limit?: number; page?: number }): Promise<BtocStat[]> {
    const response = await apiClient.get<{ status: string; result: BtocStat[] }>('/btoc-stats', { params });
    return response.result;
  }
};
