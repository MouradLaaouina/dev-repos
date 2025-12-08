import React, { useState } from 'react';
import { Contact } from '../../types';
import { Facebook, Instagram, MessageCircle, Phone, Calendar, MapPin, Edit } from 'lucide-react';
import { formatDateTime } from '../../utils/helpers';
import { useNavigate } from 'react-router-dom';

interface ClientCardProps {
  client: Contact;
}

const ClientCard: React.FC<ClientCardProps> = ({ client }) => {
  const [isExpanded, setIsExpanded] = useState(false);
  const navigate = useNavigate();

  const handleEdit = () => {
    navigate(`/dashboard/contacts/edit/${client.id}`);
  };

  const getPlatformIcon = () => {
    switch (client.plateforme) {
      case 'Facebook':
        return <Facebook className="h-5 w-5 text-blue-600" />;
      case 'Instagram':
        return <Instagram className="h-5 w-5 text-pink-600" />;
      case 'WhatsApp':
        return <MessageCircle className="h-5 w-5 text-green-600" />;
      default:
        return <MessageCircle className="h-5 w-5 text-gray-600" />;
    }
  };

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

  return (
    <div className={`card card-hoverable transition-all duration-300 ${isExpanded ? 'ring-2 ring-primary-200' : ''}`}>
      <div className="flex flex-col sm:flex-row sm:items-center justify-between">
        <div className="flex flex-col sm:flex-row sm:items-center">
          <div className="mb-2 sm:mb-0">
            <span className={`badge ${getRequestTypeColor(client.typeDeDemande)}`}>
              {client.typeDeDemande}
            </span>
          </div>
          <h3 className="text-lg font-semibold sm:ml-3">{client.nom}</h3>
          <div className="flex items-center mt-1 sm:mt-0 sm:ml-3 text-gray-500 text-sm">
            <Phone className="h-4 w-4 mr-1" />
            <span>{client.telephone}</span>
          </div>
        </div>
        
        <div className="flex items-center space-x-2 mt-2 sm:mt-0">
          {getPlatformIcon()}
          <span className="text-sm">{client.fromAds && "Ad"}</span>
          <button
            onClick={handleEdit}
            className="btn btn-outline py-1 px-3 text-sm flex items-center gap-1"
          >
            <Edit className="h-4 w-4" />
            Modifier
          </button>
          <button 
            className={`btn ${isExpanded ? 'btn-primary' : 'btn-outline'} py-1 text-sm`}
            onClick={() => setIsExpanded(!isExpanded)}
          >
            {isExpanded ? 'Réduire' : 'Détails'}
          </button>
        </div>
      </div>
      
      {isExpanded && (
        <div className="mt-4 pt-4 border-t border-gray-200 animate-fade-in">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <h4 className="font-medium text-gray-700 mb-2">Informations</h4>
              <div className="space-y-2 text-sm">
                <div className="flex items-start">
                  <MapPin className="h-4 w-4 text-gray-400 mr-2 mt-0.5" />
                  <div>
                    <p className="text-gray-600">Ville: <span className="text-gray-900">{client.ville || "Non spécifié"}</span></p>
                    <p className="text-gray-600">Adresse: <span className="text-gray-900">{client.address || "Non spécifié"}</span></p>
                    <p className="text-gray-600">Sexe: <span className="text-gray-900">{client.sexe}</span></p>
                    <p className="text-gray-600">Type: <span className="text-gray-900">{client.typeDeDemande}</span></p>
                    {client.telephone2 && (
                      <p className="text-gray-600">Téléphone 2: <span className="text-gray-900">{client.telephone2}</span></p>
                    )}
                    {client.source && (
                      <p className="text-gray-600">Source: <span className="text-gray-900">{client.source}</span></p>
                    )}
                  </div>
                </div>
                <div className="flex items-start">
                  <Calendar className="h-4 w-4 text-gray-400 mr-2 mt-0.5" />
                  <p className="text-gray-600">Créé le {formatDateTime(new Date(client.createdAt))}</p>
                </div>
              </div>
            </div>
            
            <div>
              <h4 className="font-medium text-gray-700 mb-2">Message</h4>
              <div className="bg-gray-50 p-3 rounded-md text-sm">
                {client.message || "Aucun message"}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default ClientCard;