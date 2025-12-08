import React, { useState } from 'react';
import { X, Phone, MessageCircle, FileText, ShoppingCart, User, Calendar, Package, Clock } from 'lucide-react';
import { CallCenterLead, CallLog } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import CallLogsHistory from './CallLogsHistory';
import NotesViewer from './NotesViewer';

interface LeadDetailsModalProps {
  lead: CallCenterLead;
  onClose: () => void;
  onCall: (phoneNumber: string) => void;
  onWhatsApp: (phoneNumber: string) => void;
  onOpenForm: (lead: CallCenterLead) => void;
  onCreateOrder: (lead: CallCenterLead) => void;
}

const LeadDetailsModal: React.FC<LeadDetailsModalProps> = ({
  lead,
  onClose,
  onCall,
  onWhatsApp,
  onOpenForm,
  onCreateOrder
}) => {
  const [selectedLog, setSelectedLog] = useState<CallLog | null>(null);

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200 bg-blue-50">
          <div>
            <h2 className="text-xl font-semibold text-blue-900 flex items-center gap-2">
              <User className="h-5 w-5 text-blue-600" />
              {lead.name}
            </h2>
            <p className="text-blue-700 text-sm mt-1">
              {lead.phoneNumber} • {lead.brand}
            </p>
          </div>
          <button
            onClick={onClose}
            className="p-2 hover:bg-blue-100 rounded-full transition-colors duration-200"
          >
            <X className="h-5 w-5 text-blue-500" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Lead Information */}
            <div>
              <h3 className="font-medium text-gray-900 mb-3 flex items-center gap-2">
                <User className="h-4 w-4 text-gray-600" />
                Informations du lead
              </h3>
              <div className="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-600">Date d'achat:</span>
                    <span className="font-medium">{formatDateTime(lead.purchaseDate)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Agent commercial:</span>
                    <span className="font-medium">{lead.commercialAgent}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Produit acheté:</span>
                    <span className="font-medium">{lead.productBought}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-600">Marque:</span>
                    <span className="font-medium">{lead.brand}</span>
                  </div>
                  {lead.codeClient && (
                    <div className="flex justify-between">
                      <span className="text-gray-600">Code client:</span>
                      <span className="font-medium">{lead.codeClient}</span>
                    </div>
                  )}
                </div>
              </div>

              {/* Quick Actions */}
              <div className="mt-4">
                <h3 className="font-medium text-gray-900 mb-3 flex items-center gap-2">
                  <Clock className="h-4 w-4 text-gray-600" />
                  Actions rapides
                </h3>
                <div className="flex flex-wrap gap-2">
                  <button
                    onClick={() => onCall(lead.phoneNumber)}
                    className="btn btn-primary py-2 px-4 flex items-center gap-2"
                  >
                    <Phone className="h-4 w-4" />
                    Appeler
                  </button>
                  <button
                    onClick={() => onWhatsApp(lead.phoneNumber)}
                    className="btn btn-outline py-2 px-4 flex items-center gap-2 text-green-600 border-green-300 hover:bg-green-50"
                  >
                    <MessageCircle className="h-4 w-4" />
                    WhatsApp
                  </button>
                  <button
                    onClick={() => onOpenForm(lead)}
                    className="btn btn-outline py-2 px-4 flex items-center gap-2"
                  >
                    <FileText className="h-4 w-4" />
                    Suivi d'appel
                  </button>
                  <button
                    onClick={() => onCreateOrder(lead)}
                    className="btn btn-outline py-2 px-4 flex items-center gap-2 text-purple-600 border-purple-300 hover:bg-purple-50"
                  >
                    <ShoppingCart className="h-4 w-4" />
                    Créer commande
                  </button>
                </div>
              </div>
            </div>

            {/* Call History */}
            <div>
              <h3 className="font-medium text-gray-900 mb-3 flex items-center gap-2">
                <Calendar className="h-4 w-4 text-gray-600" />
                Historique des appels
              </h3>
              {lead.callLogs.length > 0 ? (
                <CallLogsHistory 
                  logs={lead.callLogs} 
                  onViewNotes={(log) => setSelectedLog(log)} 
                />
              ) : (
                <div className="bg-gray-50 rounded-lg p-4 border border-gray-200 text-center">
                  <p className="text-gray-500">Aucun historique d'appel disponible</p>
                  <p className="text-sm text-gray-400 mt-1">
                    Utilisez le bouton "Suivi d'appel" pour enregistrer votre premier appel
                  </p>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Notes Viewer Modal */}
      {selectedLog && (
        <NotesViewer 
          callLog={selectedLog} 
          onClose={() => setSelectedLog(null)} 
        />
      )}
    </div>
  );
};

export default LeadDetailsModal;