import React, { useState, useEffect } from 'react';
import { MessageCircle, Send, UserPlus, Phone, Clock, Search, RefreshCw } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '../../store/authStore';
import { formatDateTime } from '../../utils/helpers';
import { agendaService } from '../../services/agendaService';
import toast from 'react-hot-toast';

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

  const fetchConversations = async () => {
    setLoading(true);
    setError(null);
    
    try {
      console.log('🔄 Fetching WhatsApp messages from Dolibarr...');
      const events = await agendaService.fetchEvents(0, 1000);
      
      const convs: Conversation[] = events
        .filter(e => e.notes && e.notes.includes('WhatsApp'))
        .map(e => ({
          id: e.id,
          user_phone: e.clientId || '',
          message_text: e.notes,
          timestamp: Math.floor(e.callDate.getTime() / 1000),
          direction: 'in',
          agent_id: e.agentId,
          created_at: e.createdAt.toISOString(),
          is_read: true
        }));

      setConversations(convs);
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

  const groupedConversations = conversations.reduce((acc, conversation) => {
    const phone = conversation.user_phone;
    if (!acc[phone]) acc[phone] = [];
    acc[phone].push(conversation);
    return acc;
  }, {} as Record<string, Conversation[]>);

  const filteredGroups = Object.entries(groupedConversations).filter(([phone, msgs]) => {
    return phone.includes(searchTerm) || 
           msgs.some(msg => msg.message_text.toLowerCase().includes(searchTerm.toLowerCase()));
  });

  const handleReply = (phone: string) => {
    const cleanPhone = phone.replace(/\D/g, '');
    window.open(`https://wa.me/${cleanPhone}`, '_blank');
  };

  const handleCreateContact = (conversation: Conversation) => {
    navigate('/dashboard/contacts/new', {
      state: {
        nom: '',
        telephone: conversation.user_phone,
        plateforme: 'WhatsApp',
        message: conversation.message_text,
        dateMessage: new Date(conversation.timestamp * 1000),
        source: 'WHATSAPP',
      }
    });
  };

  if (loading) {
    return (
      <div className="py-6 text-center">
        <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
        <p className="mt-2 text-secondary-600">Chargement des messages WhatsApp...</p>
      </div>
    );
  }

  return (
    <div className="py-6">
      <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 border border-secondary-100 flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-secondary-800">Messages WhatsApp</h1>
          <p className="text-secondary-600">{Object.keys(groupedConversations).length} conversations actives</p>
        </div>
        <div className="flex gap-3">
          <input
            type="text"
            placeholder="Rechercher..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="input w-64 bg-white/80"
          />
          <button onClick={fetchConversations} className="btn btn-outline flex items-center gap-2 bg-white/80">
            <RefreshCw className="h-4 w-4" /> Actualiser
          </button>
        </div>
      </div>

      <div className="space-y-4">
        {filteredGroups.map(([phone, msgs]) => (
          <div key={phone} className="card p-4 border border-gray-100 hover:border-primary-200">
            <div className="flex justify-between items-start">
              <div className="flex gap-4">
                <div className="p-3 rounded-full bg-secondary-100"><MessageCircle className="h-6 w-6 text-secondary-600" /></div>
                <div>
                  <h3 className="font-medium text-secondary-900">{phone}</h3>
                  <p className="text-secondary-700">{msgs[0].message_text}</p>
                  <p className="text-xs text-secondary-500 mt-1">{formatDateTime(new Date(msgs[0].timestamp * 1000))}</p>
                </div>
              </div>
              <div className="flex flex-col gap-2">
                <button onClick={() => handleReply(phone)} className="btn btn-primary py-1 px-3 text-sm">Répondre</button>
                <button onClick={() => handleCreateContact(msgs[0])} className="btn btn-outline py-1 px-3 text-sm">Créer Contact</button>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default WhatsAppTab;
