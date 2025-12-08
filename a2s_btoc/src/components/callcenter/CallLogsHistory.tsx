import React from 'react';
import { CallLog } from '../../types';
import { formatDateTime } from '../../utils/helpers';
import { MessageSquare, Star, Calendar, Clock } from 'lucide-react';

interface CallLogsHistoryProps {
  logs: CallLog[];
  onViewNotes: (log: CallLog) => void;
}

const CallLogsHistory: React.FC<CallLogsHistoryProps> = ({ logs, onViewNotes }) => {
  if (logs.length === 0) {
    return (
      <div className="p-4 bg-gray-50 rounded-md text-center">
        <p className="text-gray-500">Aucun historique d'appel disponible</p>
      </div>
    );
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'À rappeler':
        return 'bg-yellow-100 text-yellow-800';
      case 'Intéressé(e)':
        return 'bg-blue-100 text-blue-800';
      case 'Pas intéressé(e)':
        return 'bg-red-100 text-red-800';
      case 'Ne réponds jamais':
        return 'bg-gray-100 text-gray-800';
      case 'Faux numéro':
        return 'bg-orange-100 text-orange-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="space-y-3 max-h-96 overflow-y-auto p-2">
      {logs.map((log) => (
        <div key={log.id} className="bg-white rounded-lg border border-gray-200 p-3 shadow-sm">
          <div className="flex justify-between items-start">
            <div className="flex items-center gap-2">
              <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${getStatusColor(log.callStatus)}`}>
                {log.callStatus}
              </span>
              <div className="flex">
                {[1, 2, 3, 4, 5].map((star) => (
                  <Star 
                    key={star} 
                    className={`h-3 w-3 ${star <= log.satisfactionLevel ? 'text-yellow-500 fill-current' : 'text-gray-300'}`} 
                  />
                ))}
              </div>
            </div>
            <div className="text-xs text-gray-500 flex items-center gap-1">
              <Calendar className="h-3 w-3" />
              {formatDateTime(log.callDate)}
            </div>
          </div>
          
          <div className="mt-2 flex justify-between items-center">
            <div className="text-sm">
              <span className="text-gray-600">Intéressé(e): </span>
              <span className={`font-medium ${
                log.interested === 'Oui' ? 'text-green-600' : 
                log.interested === 'Non' ? 'text-red-600' : 
                'text-orange-600'
              }`}>
                {log.interested}
              </span>
            </div>
            
            {log.nextCallDate && (
              <div className="text-xs text-blue-600 flex items-center gap-1">
                <Clock className="h-3 w-3" />
                Rappel: {formatDateTime(log.nextCallDate)} {log.nextCallTime}
              </div>
            )}
          </div>
          
          {log.notes && (
            <div className="mt-2">
              <button
                onClick={() => onViewNotes(log)}
                className="text-xs flex items-center gap-1 text-blue-600 hover:text-blue-800"
              >
                <MessageSquare className="h-3 w-3" />
                Voir les notes complètes
              </button>
            </div>
          )}
        </div>
      ))}
    </div>
  );
};

export default CallLogsHistory;