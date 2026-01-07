import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { SocialMessage, Platform } from '../types';
import { agendaService } from '../services/agendaService';

interface MessageState {
  messages: SocialMessage[];
  loading: boolean;
  error: string | null;
  fetchMessages: () => Promise<void>;
  markAsRead: (id: string) => Promise<void>;
  markAsConverted: (id: string) => Promise<void>;
  replyToMessage: (messageId: string, reply: string) => Promise<void>;
}

export const useMessageStore = create<MessageState>()(
  persist(
    (set, get) => ({
      messages: [],
      loading: false,
      error: null,

      fetchMessages: async () => {
        set({ loading: true, error: null });
        try {
          const events = await agendaService.fetchEvents(0, 1000);
          
          const messages: SocialMessage[] = events
            .filter(e => e.notes && (e.notes.includes('Facebook') || e.notes.includes('Instagram') || e.notes.includes('WhatsApp')))
            .map(e => ({
              id: e.id,
              platform: 'Facebook', // Placeholder, should be parsed from notes
              senderId: e.clientId,
              senderName: e.clientId,
              message: e.notes,
              timestamp: e.callDate,
              isRead: true,
              isConverted: false,
            }));

          set({ messages, loading: false });
        } catch (error) {
          console.error('Error fetching messages:', error);
          set({ error: 'Failed to fetch messages', loading: false });
        }
      },

      markAsRead: async (id: string) => {
        set(state => ({
          messages: state.messages.map(msg =>
            msg.id === id ? { ...msg, isRead: true } : msg
          )
        }));
      },

      markAsConverted: async (id: string) => {
        set(state => ({
          messages: state.messages.map(msg =>
            msg.id === id ? { ...msg, isConverted: true } : msg
          )
        }));
      },

      replyToMessage: async (messageId: string, reply: string) => {
        console.log(`Replying to message ${messageId}:`, reply);
        await get().markAsRead(messageId);
      }
    }),
    {
      name: 'message-storage'
    }
  )
);
