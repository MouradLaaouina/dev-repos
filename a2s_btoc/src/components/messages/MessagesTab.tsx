import React, { useEffect, useState } from 'react';
import { Filter, Search, RefreshCw, MessageSquare, UserPlus, Send } from 'lucide-react';
import { useNavigate } from 'react-router-dom';
import { useMessageStore } from '../../store/messageStore';
import { useContactStore } from '../../store/contactStore';
import { Platform } from '../../types';
import { formatDateTime } from '../../utils/helpers';

const MessagesTab: React.FC = () => {
  const navigate = useNavigate();
  const { messages, loading, error, fetchMessages, markAsRead } = useMessageStore();
  const [searchTerm, setSearchTerm] = useState('');
  const [platformFilter, setPlatformFilter] = useState<Platform | ''>('');
  const [showFilters, setShowFilters] = useState(false);

  useEffect(() => {
    fetchMessages();
  }, [fetchMessages]);

  const filteredMessages = messages.filter((message) => {
    const matchesSearch = 
      message.senderName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      message.message.toLowerCase().includes(searchTerm.toLowerCase());
    
    const matchesPlatform = platformFilter ? message.platform === platformFilter : true;
    
    return matchesSearch && matchesPlatform;
  });

  const handleReply = (message: typeof messages[0]) => {
    let url = '';
    switch (message.platform) {
      case 'Facebook':
        url = `https://www.facebook.com/messages/t/${message.senderId}`;
        break;
      case 'Instagram':
        url = `https://www.instagram.com/direct/t/${message.senderId}/`;
        break;
      case 'WhatsApp':
        // Remove any non-numeric characters from the phone number
        const phone = message.senderName.replace(/\D/g, '');
        url = `https://wa.me/${phone}`;
        break;
    }
    window.open(url, '_blank');
  };

  const handleConvertToContact = (message: typeof messages[0]) => {
    navigate('/dashboard/contacts/new', {
      state: {
        nom: message.senderName,
        telephone: message.platform === 'WhatsApp' ? message.senderName.replace(/\D/g, '') : '',
        plateforme: message.platform,
        message: message.message,
        dateMessage: message.timestamp,
        source: message.pageName || 'PAS DÉFINI',
      }
    });
  };

  const getPlatformIcon = (platform: Platform) => {
    switch (platform) {
      case 'Facebook':
        return <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/b8/2021_Facebook_icon.svg/64px-2021_Facebook_icon.svg.png" alt="Facebook" className="h-5 w-5" />;
      case 'Instagram':
        return <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/e/e7/Instagram_logo_2016.svg/64px-Instagram_logo_2016.svg.png" alt="Instagram" className="h-5 w-5" />;
      case 'WhatsApp':
        return <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/6/6b/WhatsApp.svg/64px-WhatsApp.svg.png" alt="WhatsApp" className="h-5 w-5" />;
      default:
        return <MessageSquare className="h-5 w-5 text-gray-600" />;
    }
  };

  if (loading) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-gray-600">Chargement des messages...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="py-6">
        <div className="bg-danger-50 border border-danger-200 text-danger-700 px-4 py-3 rounded">
          {error}
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
        <h1 className="text-2xl font-bold text-gray-900 mb-4 md:mb-0">
          Messages ({filteredMessages.length})
        </h1>
        
        <div className="w-full md:w-auto flex flex-col sm:flex-row gap-3">
          <div className="relative">
            <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <Search className="h-5 w-5 text-gray-400" />
            </div>
            <input
              type="text"
              placeholder="Rechercher..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10 w-full sm:w-60"
            />
          </div>
          
          <button 
            onClick={() => setShowFilters(!showFilters)}
            className="btn btn-outline flex items-center justify-center gap-2"
          >
            <Filter className="h-4 w-4" />
            Filtres
          </button>
        </div>
      </div>
      
      {showFilters && (
        <div className="bg-white p-4 rounded-lg shadow-sm mb-6 border border-gray-200 animate-slide-up">
          <div className="flex flex-wrap gap-4">
            <div className="w-full sm:w-auto">
              <label htmlFor="platformFilter" className="label">
                Plateforme
              </label>
              <select
                id="platformFilter"
                value={platformFilter}
                onChange={(e) => setPlatformFilter(e.target.value as Platform | '')}
                className="input"
              >
                <option value="">Toutes les plateformes</option>
                <option value="Facebook">Facebook</option>
                <option value="Instagram">Instagram</option>
                <option value="WhatsApp">WhatsApp</option>
              </select>
            </div>
            
            <div className="w-full sm:w-auto flex items-end">
              <button
                onClick={() => {
                  setSearchTerm('');
                  setPlatformFilter('');
                }}
                className="btn btn-outline flex items-center gap-2"
              >
                <RefreshCw className="h-4 w-4" />
                Réinitialiser
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="space-y-4">
        {filteredMessages.map((message) => (
          <div key={message.id} className="card">
            <div className="flex items-start justify-between">
              <div className="flex items-start space-x-4">
                <div className={`p-2 rounded-full ${message.isRead ? 'bg-gray-100' : 'bg-primary-100'}`}>
                  {getPlatformIcon(message.platform)}
                </div>
                <div>
                  <h3 className="font-medium text-gray-900">
                    {message.senderName}
                    {message.pageName && (
                      <span className="ml-2 text-sm text-gray-500">
                        via {message.pageName}
                      </span>
                    )}
                  </h3>
                  <p className="text-sm text-gray-500">{formatDateTime(new Date(message.timestamp))}</p>
                  <p className="mt-2 text-gray-700">{message.message}</p>
                </div>
              </div>
              <div className="flex space-x-2">
                <button
                  onClick={() => handleReply(message)}
                  className="btn btn-outline py-1 px-3 text-sm"
                >
                  <Send className="h-4 w-4" />
                </button>
                {!message.isConverted && (
                  <button
                    onClick={() => handleConvertToContact(message)}
                    className="btn btn-primary py-1 px-3 text-sm"
                  >
                    <UserPlus className="h-4 w-4" />
                  </button>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default MessagesTab;