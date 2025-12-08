import React, { useState } from 'react';
import { Search, Phone, MessageCircle, Calendar, MapPin, Edit, Eye, ChevronDown, ChevronUp } from 'lucide-react';
import { Contact } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import { useNavigate } from 'react-router-dom';

interface ClientTableProps {
  clients: Contact[];
}

const ClientTable: React.FC<ClientTableProps> = ({ clients }) => {
  const navigate = useNavigate();
  const [searchTerm, setSearchTerm] = useState('');
  const [expandedClient, setExpandedClient] = useState<string | null>(null);

  const filteredClients = clients.filter(client =>
    client.nom.toLowerCase().includes(searchTerm.toLowerCase()) ||
    client.telephone.includes(searchTerm) ||
    (client.message || '').toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getRequestTypeColor = (type: string) => {
    switch (type) {
      case 'Information':
        return 'bg-blue-100 text-blue-800';
      case 'En attente de traitement':
        return 'bg-yellow-100 text-yellow-800';
      case 'Orientation Para':
        return 'bg-purple-100 text-purple-800';
      case 'Sans réponse':
        return 'bg-red-100 text-red-800';
      case 'En attente de réponse':
        return 'bg-orange-100 text-orange-800';
      case 'Annulee':
        return 'bg-gray-100 text-gray-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  const getPlatformIcon = (platform: string) => {
    switch (platform) {
      case 'Facebook':
        return <div className="w-4 h-4 bg-blue-600 rounded"></div>;
      case 'Instagram':
        return <div className="w-4 h-4 bg-pink-600 rounded"></div>;
      case 'WhatsApp':
        return <div className="w-4 h-4 bg-green-600 rounded"></div>;
      default:
        return <MessageCircle className="h-4 w-4 text-gray-600" />;
    }
  };

  const handleEdit = (client: Contact) => {
    navigate(`/dashboard/contacts/edit/${client.id}`);
  };

  const toggleExpanded = (clientId: string) => {
    setExpandedClient(expandedClient === clientId ? null : clientId);
  };

  if (filteredClients.length === 0) {
    return (
      <div className="p-8 text-center">
        <div className="text-secondary-500 mb-2">
          Aucun client trouvé
        </div>
        <p className="text-sm text-secondary-400">
          {searchTerm ? 
            "Essayez de modifier vos critères de recherche" : 
            "Vous n'avez pas encore de clients avec ce type de demande"}
        </p>
      </div>
    );
  }

  return (
    <div className="p-6">
      {/* Search */}
      <div className="mb-4">
        <div className="relative">
          <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <Search className="h-5 w-5 text-gray-400" />
          </div>
          <input
            type="text"
            placeholder="Rechercher par nom, téléphone ou message..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="input pl-10 w-full"
          />
        </div>
      </div>

      {/* Client List */}
      <div className="space-y-3">
        {filteredClients.map((client) => (
          <div key={client.id} className="border border-gray-200 rounded-lg p-4 hover:border-primary-200 transition-colors duration-200">
            {/* Client Header */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                  {getPlatformIcon(client.plateforme)}
                  <span className={`badge ${getRequestTypeColor(client.typeDeDemande)}`}>
                    {client.typeDeDemande}
                  </span>
                </div>
                
                <div>
                  <h3 className="font-semibold text-secondary-800">{client.nom}</h3>
                  <div className="flex items-center gap-4 text-sm text-secondary-600">
                    <span className="flex items-center gap-1">
                      <Phone className="h-3 w-3" />
                      {client.telephone}
                    </span>
                    <span className="flex items-center gap-1">
                      <Calendar className="h-3 w-3" />
                      {formatDateTime(client.createdAt)}
                    </span>
                    {client.ville && (
                      <span className="flex items-center gap-1">
                        <MapPin className="h-3 w-3" />
                        {client.ville}
                      </span>
                    )}
                  </div>
                </div>
              </div>

              <div className="flex items-center gap-2">
                <button
                  onClick={() => handleEdit(client)}
                  className="btn btn-outline py-1 px-3 text-sm flex items-center gap-1"
                >
                  <Edit className="h-4 w-4" />
                  Modifier
                </button>
                <button
                  onClick={() => toggleExpanded(client.id)}
                  className="btn btn-outline py-1 px-3 text-sm flex items-center gap-1"
                >
                  {expandedClient === client.id ? (
                    <>
                      <ChevronUp className="h-4 w-4" />
                      Réduire
                    </>
                  ) : (
                    <>
                      <Eye className="h-4 w-4" />
                      Détails
                    </>
                  )}
                </button>
              </div>
            </div>

            {/* Expanded Details */}
            {expandedClient === client.id && (
              <div className="mt-4 pt-4 border-t border-gray-200 animate-fade-in">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-medium text-secondary-700 mb-2">Informations</h4>
                    <div className="space-y-1 text-sm">
                      <p><span className="text-secondary-600">Type de demande:</span> <span className="font-medium">{client.typeDeDemande}</span></p>
                      <p><span className="text-secondary-600">Sexe:</span> <span className="font-medium">{client.sexe}</span></p>
                      <p><span className="text-secondary-600">Plateforme:</span> <span className="font-medium">{client.plateforme}</span></p>
                      {client.source && (
                        <p><span className="text-secondary-600">Source:</span> <span className="font-medium">{client.source}</span></p>
                      )}
                      {client.marque && (
                        <p><span className="text-secondary-600">Marque d'intérêt:</span> <span className="font-medium">{client.marque}</span></p>
                      )}
                      {client.fromAds && (
                        <span className="inline-block bg-orange-100 text-orange-800 text-xs px-2 py-1 rounded">
                          Provient d'une publicité
                        </span>
                      )}
                    </div>
                  </div>
                  
                  <div>
                    <h4 className="font-medium text-secondary-700 mb-2">Message</h4>
                    <div className="bg-gray-50 p-3 rounded-md text-sm">
                      {client.message || "Aucun message"}
                    </div>
                  </div>
                </div>
              </div>
            )}
          </div>
        ))}
      </div>
    </div>
  );
};

export default ClientTable;