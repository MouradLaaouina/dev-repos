import React from 'react';
import { MessageSquare, Star } from 'lucide-react';
import { CallLog } from '../../types';

interface NotesViewerProps {
  callLog: CallLog;
  onClose: () => void;
}

const NotesViewer: React.FC<NotesViewerProps> = ({ callLog, onClose }) => {
  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-4 border-b border-gray-200 bg-blue-50">
          <div className="flex items-center gap-2">
            <MessageSquare className="h-5 w-5 text-blue-600" />
            <h2 className="text-lg font-semibold text-blue-900">Notes d'appel</h2>
          </div>
          <div className="flex items-center gap-2">
            <div className="flex">
              {[1, 2, 3, 4, 5].map((star) => (
                <Star 
                  key={star} 
                  className={`h-4 w-4 ${star <= callLog.satisfactionLevel ? 'text-yellow-500 fill-current' : 'text-gray-300'}`} 
                />
              ))}
            </div>
            <span className="text-sm text-blue-700">
              Satisfaction: {callLog.satisfactionLevel}/5
            </span>
          </div>
        </div>

        {/* Content */}
        <div className="p-4">
          <div className="mb-4">
            <div className="flex justify-between items-center mb-2">
              <h3 className="font-medium text-gray-900">Statut de l'appel</h3>
              <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                callLog.callStatus === 'À rappeler' ? 'bg-yellow-100 text-yellow-800' :
                callLog.callStatus === 'Intéressé(e)' ? 'bg-blue-100 text-blue-800' :
                callLog.callStatus === 'Pas intéressé(e)' ? 'bg-red-100 text-red-800' :
                'bg-gray-100 text-gray-800'
              }`}>
                {callLog.callStatus}
              </span>
            </div>
            <div className="text-sm text-gray-600">
              <p>Intéressé(e) par le produit: <span className="font-medium">{callLog.interested}</span></p>
              <p>Date d'appel: <span className="font-medium">{callLog.callDate.toLocaleString()}</span></p>
              {callLog.nextCallDate && (
                <p>Prochain appel: <span className="font-medium">{callLog.nextCallDate.toLocaleDateString()} {callLog.nextCallTime}</span></p>
              )}
            </div>
          </div>

          <div className="mb-4">
            <h3 className="font-medium text-gray-900 mb-2">Notes détaillées</h3>
            <div className="bg-gray-50 p-4 rounded-md border border-gray-200 whitespace-pre-wrap">
              {callLog.notes || "Aucune note détaillée"}
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="p-4 border-t border-gray-200 flex justify-end">
          <button
            onClick={onClose}
            className="btn btn-primary py-1 px-4 text-sm"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  );
};

export default NotesViewer;