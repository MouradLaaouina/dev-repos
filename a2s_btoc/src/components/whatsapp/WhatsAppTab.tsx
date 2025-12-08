import React, { useState, useEffect } from 'react';
import { MessageCircle, Send, UserPlus, Phone, Clock, Search, Filter, RefreshCw, Bug } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { formatDateTime } from '../../utils/helpers';
import { supabase } from '../../lib/supabase';
import toast from 'react-hot-toast';
import DebugPanel from './DebugPanel';

interface Conversation {
  id: string;
  user_phone: string;
  message_text: string;
  timestamp: number;
  direction: 'in' | 'out';
  agent_id: string | null;
  created_at: string;
  is_read: boolean;
}

const WhatsAppTab: React.FC = () => {
  const navigate = useNavigate();
  const user = useAuthStore((state) => state.user);
  const [conversations, setConversations] = useState<Conversation[]>([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedConversation, setSelectedConversation] = useState<string | null>(null);
  const [showDebug, setShowDebug] = useState(false);

  const fetchConversations = async () => {
    setLoading(true);
    setError(null);
    
    try {
      console.log('🔄 Fetching ALL conversations without limit...');
      
      // 🔥 FIXED: Fetch ALL conversations without limit using range
      const { data, error } = await supabase
        .from('conversations')
        .select('*')
        .range(0, 99999) // 🔥 FIXED: Remove 1000 limit by setting high range
        .order('timestamp', { ascending: false });

      if (error) throw error;

      console.log('✅ Fetched ALL conversations:', data?.length, '(no 1000 limit)');
      setConversations(data || []);
    } catch (error) {
      console.error('Error fetching conversations:', error);
      setError('Erreur lors du chargement des conversations');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchConversations();
  }, []);

  // Group conversations by phone number
  const groupedConversations = conversations.reduce((acc, conversation) => {
    const phone = conversation.user_phone;
    if (!acc[phone]) {
      acc[phone] = [];
    }
    acc[phone].push(conversation);
    return acc;
  }, {} as Record<string, Conversation[]>);

  // Filter conversations
  const filteredGroups = Object.entries(groupedConversations).filter(([phone, msgs]) => {
    const matchesSearch = phone.includes(searchTerm) || 
                         msgs.some(msg => msg.message_text.toLowerCase().includes(searchTerm.toLowerCase()));
    return matchesSearch;
  });

  const handleReply = (phone: string) => {
    // Clean phone number and open WhatsApp Web
    const cleanPhone = phone.replace(/\D/g, '');
    const whatsappUrl = `https://wa.me/${cleanPhone}`;
    window.open(whatsappUrl, '_blank');
  };

  // Function to format phone number for form
  const formatPhoneForForm = (phone: string) => {
    // Remove all non-digit characters
    let cleanPhone = phone.replace(/\D/g, '');
    
    // If it starts with 212 (Morocco country code), replace with 0
    if (cleanPhone.startsWith('212')) {
      cleanPhone = '0' + cleanPhone.substring(3);
    }
    
    // If it doesn't start with 0 and is 9 digits, add 0 at the beginning
    if (!cleanPhone.startsWith('0') && cleanPhone.length === 9) {
      cleanPhone = '0' + cleanPhone;
    }
    
    return cleanPhone;
  };

  const handleCreateContact = (conversation: Conversation) => {
    const formattedPhone = formatPhoneForForm(conversation.user_phone);
    
    navigate('/dashboard/contacts/new', {
      state: {
        nom: '', // Will be filled by user
        telephone: formattedPhone,
        plateforme: 'WhatsApp',
        message: conversation.message_text,
        dateMessage: new Date(conversation.timestamp * 1000),
        source: 'ADS WHATSAPP',
      }
    });
  };

  const handleMarkAsRead = async (conversationId: string) => {
    try {
      const { error } = await supabase
        .from('conversations')
        .update({ is_read: true })
        .eq('id', conversationId);

      if (error) throw error;

      setConversations(prev => 
        prev.map(conv => 
          conv.id === conversationId ? { ...conv, is_read: true } : conv
        )
      );
      
      toast.success('Message marqué comme lu');
    } catch (error) {
      console.error('Error marking as read:', error);
      toast.error('Erreur lors de la mise à jour');
    }
  };

  if (loading) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-secondary-600">Chargement des messages WhatsApp...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="py-6">
        <div className="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded">
          Erreur: {error}
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      {/* Header with gradient banner */}
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 border border-secondary-100">
        <div className="flex flex-col md:flex-row justify-between items-start md:items-center">
          <div>
            <h1 className="text-2xl font-bold text-secondary-800 mb-2">
              Messages WhatsApp
            </h1>
            <p className="text-secondary-600">
              {Object.keys(groupedConversations).length} conversation{Object.keys(groupedConversations).length > 1 ? 's' : ''} active{Object.keys(groupedConversations).length > 1 ? 's' : ''}
            </p>
            <p className="text-sm text-secondary-500 mt-1">
              📊 Total messages: {conversations.length.toLocaleString()} (sans limite)
            </p>
          </div>
          
          <div className="w-full md:w-auto flex flex-col sm:flex-row gap-3 mt-4 md:mt-0">
            <div className="relative">
              <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <Search className="h-5 w-5 text-gray-400" />
              </div>
              <input
                type="text"
                placeholder="Rechercher par téléphone ou message..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input pl-10 w-full sm:w-80 bg-white/80 backdrop-blur-sm"
              />
            </div>
            
            <button
              onClick={fetchConversations}
              className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white"
            >
              <RefreshCw className="h-4 w-4" />
              Actualiser
            </button>

            <button
              onClick={() => setShowDebug(!showDebug)}
              className="btn btn-outline flex items-center gap-2 bg-white/80 backdrop-blur-sm hover:bg-white"
            >
              <Bug className="h-4 w-4" />
              Debug
            </button>
          </div>
        </div>
      </div>

      {/* Debug Panel */}
      {showDebug && (
        <div className="mb-6">
          <DebugPanel />
        </div>
      )}

      {filteredGroups.length === 0 ? (
        <div className="bg-white rounded-lg shadow-sm p-8 text-center border border-gray-100">
          <MessageCircle className="h-12 w-12 text-secondary-400 mx-auto mb-4" />
          <div className="text-secondary-500 mb-2">
            Aucune conversation WhatsApp trouvée
          </div>
          <p className="text-sm text-secondary-400">
            {searchTerm ? 
              "Essayez de modifier vos critères de recherche" : 
              "Les nouveaux messages WhatsApp apparaîtront ici"}
          </p>
          <div className="mt-4 text-xs text-secondary-500">
            Total conversations in database: {conversations.length.toLocaleString()}
          </div>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredGroups.map(([phone, msgs]) => {
            const latestMessage = msgs[0]; // Messages are sorted by timestamp desc
            const unreadCount = msgs.filter(msg => !msg.is_read).length;
            
            return (
              <div key={phone} className="card border border-gray-100 hover:border-primary-200 transition-colors duration-200">
                <div className="flex items-start justify-between">
                  <div className="flex items-start space-x-4 flex-1">
                    <div className={`p-3 rounded-full ${unreadCount > 0 ? 'bg-primary-100' : 'bg-secondary-100'}`}>
                      <MessageCircle className={`h-6 w-6 ${unreadCount > 0 ? 'text-primary-600' : 'text-secondary-600'}`} />
                    </div>
                    
                    <div className="flex-1">
                      <div className="flex items-center justify-between mb-2">
                        <h3 className="font-medium text-secondary-900 flex items-center gap-2">
                          <Phone className="h-4 w-4 text-secondary-500" />
                          {phone}
                          {unreadCount > 0 && (
                            <span className="bg-primary-500 text-white text-xs px-2 py-1 rounded-full">
                              {unreadCount} nouveau{unreadCount > 1 ? 'x' : ''}
                            </span>
                          )}
                        </h3>
                        <div className="flex items-center text-sm text-secondary-500">
                          <Clock className="h-4 w-4 mr-1" />
                          {formatDateTime(new Date(latestMessage.timestamp * 1000))}
                        </div>
                      </div>
                      
                      <p className="text-secondary-700 mb-3">
                        <span className="inline-block w-2 h-2 rounded-full mr-2 bg-primary-500"></span>
                        {latestMessage.message_text}
                      </p>
                      
                      {selectedConversation === phone && (
                        <div className="mt-4 p-4 bg-gradient-to-r from-secondary-25 to-primary-25 rounded-lg max-h-60 overflow-y-auto border border-secondary-100">
                          <h4 className="font-medium text-secondary-700 mb-3">Historique des messages</h4>
                          <div className="space-y-2">
                            {msgs.slice().reverse().map((msg) => (
                              <div
                                key={msg.id}
                                className="p-2 rounded-lg text-sm bg-primary-100 text-primary-900 ml-0 mr-8"
                              >
                                <p>{msg.message_text}</p>
                                <p className="text-xs opacity-75 mt-1">
                                  {formatDateTime(new Date(msg.timestamp * 1000))}
                                </p>
                              </div>
                            ))}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                  
                  <div className="flex flex-col gap-2 ml-4">
                    <button
                      onClick={() => handleReply(phone)}
                      className="btn btn-primary py-1 px-3 text-sm flex items-center gap-1"
                    >
                      <Send className="h-4 w-4" />
                      Répondre
                    </button>
                    
                    <button
                      onClick={() => handleCreateContact(latestMessage)}
                      className="btn btn-outline py-1 px-3 text-sm flex items-center gap-1"
                    >
                      <UserPlus className="h-4 w-4" />
                      Créer Contact
                    </button>
                    
                    <button
                      onClick={() => setSelectedConversation(
                        selectedConversation === phone ? null : phone
                      )}
                      className="btn btn-outline py-1 px-3 text-sm"
                    >
                      {selectedConversation === phone ? 'Masquer' : 'Voir tout'}
                    </button>
                    
                    {unreadCount > 0 && (
                      <button
                        onClick={() => {
                          msgs.forEach(msg => {
                            if (!msg.is_read) {
                              handleMarkAsRead(msg.id);
                            }
                          });
                        }}
                        className="btn btn-outline py-1 px-3 text-sm text-primary-600 border-primary-300 hover:bg-primary-50"
                      >
                        Marquer lu
                      </button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

export default WhatsAppTab;