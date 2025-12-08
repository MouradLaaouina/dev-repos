import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { SocialMessage, Platform } from '../types';
import { supabase } from '../lib/supabase';

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
          const { data, error } = await supabase
            .from('messages')
            .select('*')
            .order('timestamp', { ascending: false });

          if (error) throw error;

          const messages: SocialMessage[] = data.map(msg => ({
            id: msg.id,
            platform: msg.platform as Platform,
            senderId: msg.sender_id,
            senderName: msg.sender_name || msg.sender_id,
            message: msg.message,
            timestamp: new Date(msg.timestamp),
            profilePicture: msg.profile_picture,
            isRead: msg.is_read,
            isConverted: msg.is_converted,
            pageId: msg.page_id,
            pageName: msg.page_name,
            conversationId: msg.conversation_id
          }));

          set({ messages, loading: false });
        } catch (error) {
          console.error('Error fetching messages:', error);
          set({ error: 'Failed to fetch messages', loading: false });
        }
      },

      markAsRead: async (id: string) => {
        try {
          const { error } = await supabase
            .from('messages')
            .update({ is_read: true })
            .eq('id', id);

          if (error) throw error;

          set(state => ({
            messages: state.messages.map(msg =>
              msg.id === id ? { ...msg, isRead: true } : msg
            )
          }));
        } catch (error) {
          console.error('Error marking message as read:', error);
          set({ error: 'Failed to mark message as read' });
        }
      },

      markAsConverted: async (id: string) => {
        try {
          const { error } = await supabase
            .from('messages')
            .update({ is_converted: true })
            .eq('id', id);

          if (error) throw error;

          set(state => ({
            messages: state.messages.map(msg =>
              msg.id === id ? { ...msg, isConverted: true } : msg
            )
          }));
        } catch (error) {
          console.error('Error marking message as converted:', error);
          set({ error: 'Failed to mark message as converted' });
        }
      },

      replyToMessage: async (messageId: string, reply: string) => {
        try {
          const message = get().messages.find(msg => msg.id === messageId);
          if (!message) throw new Error('Message not found');

          // In a real implementation, this would send the reply through the appropriate platform's API
          console.log(`Replying to ${message.platform} message:`, reply);
          
          // Mark as read after replying
          await get().markAsRead(messageId);
        } catch (error) {
          console.error('Error sending reply:', error);
          set({ error: 'Failed to send reply' });
        }
      }
    }),
    {
      name: 'message-storage'
    }
  )
);