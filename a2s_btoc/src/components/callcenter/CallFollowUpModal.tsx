import React, { useState } from 'react';
import { X, Star, Calendar, Clock, FileText, Phone } from 'lucide-react';
import { CallCenterLead, CallStatus } from '../../types';
import Flatpickr from 'react-flatpickr';
import 'flatpickr/dist/themes/light.css';
import { French } from 'flatpickr/dist/l10n/fr';

interface CallFollowUpModalProps {
  lead: CallCenterLead;
  onSave: (data: any) => void;
  onClose: () => void;
  isMandatory?: boolean;
}

const CallFollowUpModal: React.FC<CallFollowUpModalProps> = ({ lead, onSave, onClose, isMandatory = false }) => {
  const [formData, setFormData] = useState({
    callStatus: '' as CallStatus,
    satisfactionLevel: 0,
    interested: 'Non' as 'Oui' | 'Non' | 'Peut-être',
    callDate: new Date(),
    nextCallDate: null as Date | null,
    nextCallTime: '',
    notes: ''
  });

  const [errors, setErrors] = useState<{ [key: string]: string }>({});

  const callStatusOptions: CallStatus[] = [
    'À rappeler',
    'Pas intéressé(e)',
    'Commande', // Added Commande status
    'Ne réponds jamais',
    'Faux numéro',
    'Intéressé(e)'
  ];

  const validateForm = () => {
    const newErrors: { [key: string]: string } = {};

    if (!formData.callStatus) {
      newErrors.callStatus = 'Le statut de l\'appel est obligatoire';
    }

    // Only validate satisfaction level if the status requires it
    const requiresSatisfaction = !['Ne réponds jamais', 'Faux numéro', 'Commande'].includes(formData.callStatus);
    if (requiresSatisfaction && formData.satisfactionLevel === 0) {
      newErrors.satisfactionLevel = 'Veuillez donner une note de satisfaction';
    }

    if (!formData.callDate) {
      newErrors.callDate = 'La date d\'appel est obligatoire';
    }

    if (formData.callStatus === 'À rappeler' && !formData.nextCallDate) {
      newErrors.nextCallDate = 'La date du prochain appel est obligatoire pour ce statut';
    }

    if (formData.callStatus === 'À rappeler' && !formData.nextCallTime) {
      newErrors.nextCallTime = 'L\'heure du prochain appel est obligatoire pour ce statut';
    }

    // 🆕 NEW: Make notes mandatory for "Intéressé(e)" and "Pas intéressé(e)" status
    if ((formData.callStatus === 'Intéressé(e)' || formData.callStatus === 'Pas intéressé(e)') && !formData.notes.trim()) {
      newErrors.notes = `Une note explicative est obligatoire pour le statut "${formData.callStatus}"`;
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (validateForm()) {
      // Fix: Set satisfaction level to 1 for statuses that don't require satisfaction rating
      // to avoid database constraint violation
      const dataToSave = { ...formData };
      const statusesWithoutSatisfaction = ['Ne réponds jamais', 'Faux numéro', 'Commande'];
      
      if (statusesWithoutSatisfaction.includes(formData.callStatus) && formData.satisfactionLevel === 0) {
        dataToSave.satisfactionLevel = 1;
      }
      
      onSave(dataToSave);
    }
  };

  const renderStars = () => {
    return (
      <div className="flex items-center gap-1">
        {[1, 2, 3, 4, 5].map((star) => (
          <button
            key={star}
            type="button"
            onClick={() => setFormData(prev => ({ ...prev, satisfactionLevel: star }))}
            className={`p-1 rounded transition-colors duration-200 ${
              star <= formData.satisfactionLevel
                ? 'text-yellow-500 hover:text-yellow-600'
                : 'text-gray-300 hover:text-gray-400'
            }`}
          >
            <Star className={`h-6 w-6 ${star <= formData.satisfactionLevel ? 'fill-current' : ''}`} />
          </button>
        ))}
        <span className="ml-2 text-sm text-gray-600">
          {formData.satisfactionLevel > 0 ? `${formData.satisfactionLevel}/5` : 'Cliquez pour noter'}
        </span>
      </div>
    );
  };

  // 🆕 NEW: Check if notes are required based on call status
  const isNotesRequired = formData.callStatus === 'Intéressé(e)' || formData.callStatus === 'Pas intéressé(e)';

  // Check if satisfaction and interest fields should be shown
  const showSatisfactionAndInterest = !['Ne réponds jamais', 'Faux numéro', 'Commande'].includes(formData.callStatus);

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b border-gray-200">
          <div>
            <h2 className="text-xl font-semibold text-secondary-800 flex items-center gap-2">
              <Phone className="h-5 w-5 text-primary-600" />
              Suivi d'appel
            </h2>
            <p className="text-secondary-600 text-sm mt-1">
              Client: <span className="font-medium">{lead.name}</span> • {lead.phoneNumber}
            </p>
            {isMandatory && (
              <div className="mt-2 px-3 py-1 bg-red-100 text-red-800 text-xs rounded-full inline-block">
                Ce formulaire doit être rempli et enregistré avant de pouvoir continuer
              </div>
            )}
          </div>
          {/* Only show close button if the form is not mandatory */}
          {!isMandatory && (
            <button
              onClick={onClose}
              className="p-2 hover:bg-gray-100 rounded-full transition-colors duration-200"
            >
              <X className="h-5 w-5 text-gray-500" />
            </button>
          )}
        </div>

        {/* Form */}
        <form onSubmit={handleSubmit} className="p-6 space-y-6">
          {/* Call Status */}
          <div>
            <label className="label">
              Statut de l'appel <span className="text-red-500">*</span>
            </label>
            <select
              value={formData.callStatus}
              onChange={(e) => setFormData(prev => ({ ...prev, callStatus: e.target.value as CallStatus }))}
              className={`input ${errors.callStatus ? 'border-red-300' : ''}`}
            >
              <option value="">Sélectionner un statut</option>
              {callStatusOptions.map(status => (
                <option key={status} value={status}>{status}</option>
              ))}
            </select>
            {errors.callStatus && (
              <p className="mt-1 text-sm text-red-600">{errors.callStatus}</p>
            )}
          </div>

          {/* Satisfaction Level - Only show for relevant statuses */}
          {showSatisfactionAndInterest && (
            <div>
              <label className="label">
                Niveau de satisfaction (1–5 étoiles) <span className="text-red-500">*</span>
              </label>
              {renderStars()}
              {errors.satisfactionLevel && (
                <p className="mt-1 text-sm text-red-600">{errors.satisfactionLevel}</p>
              )}
            </div>
          )}

          {/* Interested - Only show for relevant statuses */}
          {showSatisfactionAndInterest && (
            <div>
              <label className="label">Intéressé(e) par le produit ?</label>
              <div className="flex gap-4">
                {['Oui', 'Non', 'Peut-être'].map(option => (
                  <label key={option} className="flex items-center">
                    <input
                      type="radio"
                      name="interested"
                      value={option}
                      checked={formData.interested === option}
                      onChange={(e) => setFormData(prev => ({ ...prev, interested: e.target.value as any }))}
                      className="mr-2"
                    />
                    <span className="text-sm">{option}</span>
                  </label>
                ))}
              </div>
            </div>
          )}

          {/* Call Date */}
          <div>
            <label className="label">
              Date d'appel <span className="text-red-500">*</span>
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <Calendar className="h-5 w-5 text-gray-400" />
              </div>
              <Flatpickr
                value={formData.callDate}
                onChange={([date]) => setFormData(prev => ({ ...prev, callDate: date }))}
                options={{
                  locale: French,
                  dateFormat: "d/m/Y H:i",
                  enableTime: true,
                  time_24hr: true,
                  defaultDate: new Date()
                }}
                className={`input pl-10 ${errors.callDate ? 'border-red-300' : ''}`}
                placeholder="Sélectionner date et heure"
              />
            </div>
            {errors.callDate && (
              <p className="mt-1 text-sm text-red-600">{errors.callDate}</p>
            )}
          </div>

          {/* Next Call Date (conditional) */}
          {formData.callStatus === 'À rappeler' && (
            <>
              <div>
                <label className="label">
                  Date du prochain appel <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <Calendar className="h-5 w-5 text-gray-400" />
                  </div>
                  <Flatpickr
                    value={formData.nextCallDate || undefined}
                    onChange={([date]) => setFormData(prev => ({ ...prev, nextCallDate: date }))}
                    options={{
                      locale: French,
                      dateFormat: "d/m/Y",
                      minDate: "today"
                    }}
                    className={`input pl-10 ${errors.nextCallDate ? 'border-red-300' : ''}`}
                    placeholder="Sélectionner date"
                  />
                </div>
                {errors.nextCallDate && (
                  <p className="mt-1 text-sm text-red-600">{errors.nextCallDate}</p>
                )}
              </div>

              <div>
                <label className="label">
                  Heure du prochain appel <span className="text-red-500">*</span>
                </label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <Clock className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    type="time"
                    value={formData.nextCallTime}
                    onChange={(e) => setFormData(prev => ({ ...prev, nextCallTime: e.target.value }))}
                    className={`input pl-10 ${errors.nextCallTime ? 'border-red-300' : ''}`}
                  />
                </div>
                {errors.nextCallTime && (
                  <p className="mt-1 text-sm text-red-600">{errors.nextCallTime}</p>
                )}
              </div>
            </>
          )}

          {/* Notes - 🆕 NEW: Conditional requirement based on call status */}
          <div>
            <label className="label">
              Notes {isNotesRequired && <span className="text-red-500">*</span>}
              {!isNotesRequired && <span className="text-gray-500">(optionnel)</span>}
            </label>
            <div className="relative">
              <div className="absolute top-3 left-3 pointer-events-none">
                <FileText className="h-5 w-5 text-gray-400" />
              </div>
              <textarea
                value={formData.notes}
                onChange={(e) => setFormData(prev => ({ ...prev, notes: e.target.value }))}
                rows={4}
                className={`input pl-10 pt-3 ${errors.notes ? 'border-red-300' : ''}`}
                placeholder={
                  formData.callStatus === 'Intéressé(e)' 
                    ? "Expliquez pourquoi le client est intéressé (produits souhaités, besoins spécifiques, etc.)..."
                    : formData.callStatus === 'Pas intéressé(e)'
                    ? "Expliquez pourquoi le client n'est pas intéressé (prix, produit inadapté, timing, etc.)..."
                    : formData.callStatus === 'Commande'
                    ? "Détails de la commande (produits, quantités, etc.)..."
                    : "Ajouter des notes sur l'appel..."
                }
              />
            </div>
            {errors.notes && (
              <p className="mt-1 text-sm text-red-600">{errors.notes}</p>
            )}
            {/* 🆕 NEW: Show requirement notice for specific statuses */}
            {isNotesRequired && (
              <p className="mt-1 text-sm text-blue-600">
                📝 Une explication détaillée est requise pour ce statut afin d'améliorer le suivi client
              </p>
            )}
          </div>

          {/* Actions */}
          <div className="flex justify-end gap-3 pt-4 border-t border-gray-200">
            {/* Only show cancel button if the form is not mandatory */}
            {!isMandatory && (
              <button
                type="button"
                onClick={onClose}
                className="btn btn-outline"
              >
                Annuler
              </button>
            )}
            <button
              type="submit"
              className={`btn btn-primary ${isMandatory ? 'w-full' : ''}`}
            >
              {isMandatory ? "Enregistrer le suivi d'appel" : "Enregistrer le suivi"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default CallFollowUpModal;