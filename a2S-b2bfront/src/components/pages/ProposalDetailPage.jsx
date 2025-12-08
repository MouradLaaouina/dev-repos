import { useState, useEffect } from 'react';
import { ArrowLeft, FileText, Calendar, User, Package, Loader2, AlertCircle, Truck } from 'lucide-react';
import { proposalService } from '../../services';

function ProposalHeader({ proposal, onBack }) {
  const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(parseInt(dateStr) * 1000);
    return date.toLocaleDateString('fr-FR');
  };

  const getStatusLabel = (proposal) => {
    const propStatus = Number(proposal.statut);
    const shipmentStatus = Number(proposal.shipment?.statut);

    if (propStatus === 0) return 'Brouillon';
    if (propStatus === 1) return 'Validée';
    if (propStatus === 2) {
      if (shipmentStatus === 2) return 'Livré';
      if (shipmentStatus === 1) return 'En cours de livraison';
      return 'Validée';
    }
    return 'Inconnu';
  };

  const getStatusColor = (proposal) => {
    const propStatus = Number(proposal.statut);
    const shipmentStatus = Number(proposal.shipment?.statut);

    if (propStatus === 0) return 'bg-gray-100 text-gray-800';
    if (propStatus === 1) return 'bg-blue-100 text-blue-800';
    if (propStatus === 2) {
      if (shipmentStatus === 2) return 'bg-green-100 text-green-800';
      if (shipmentStatus === 1) return 'bg-orange-100 text-orange-800';
      return 'bg-blue-100 text-blue-800';
    }
    return 'bg-gray-100 text-gray-800';
  };

  return (
    <div className="bg-green-600 text-white px-6 py-4 shadow-lg sticky top-0 z-10">
      <div className="flex items-center gap-3 mb-3">
        <button
          onClick={onBack}
          className="flex items-center gap-2 bg-green-700 px-3 py-2 rounded-lg hover:bg-green-800 transition-colors"
        >
          <ArrowLeft className="w-5 h-5" />
          <span className="text-sm font-medium">Retour</span>
        </button>
        <h1 className="text-xl font-bold">Devis {proposal.ref}</h1>
      </div>
      <div className="bg-green-700 rounded-lg p-3 space-y-2">
        <div className="flex items-center justify-between">
          <div>
            <p className="font-medium">{proposal.socname || 'Client'}</p>
            <p className="text-sm text-green-200">
              {formatDate(proposal.date)} {proposal.date_livraison && `• Livraison: ${formatDate(proposal.date_livraison)}`}
            </p>
          </div>
          <span className={`px-3 py-1 rounded-full text-xs font-medium ${getStatusColor(proposal)}`}>
            {getStatusLabel(proposal)}
          </span>
        </div>
      </div>
    </div>
  );
}

function ProposalLine({ line }) {
  const subtotal = (parseFloat(line.qty || 0) * parseFloat(line.subprice || 0)).toFixed(2);
  const remise = parseFloat(line.remise_percent || 0);
  const totalAfterDiscount = remise > 0
    ? (subtotal * (1 - remise / 100)).toFixed(2)
    : subtotal;

  return (
    <div className="bg-gray-50 p-4 rounded-lg">
      <div className="flex justify-between items-start mb-2">
        <div className="flex-1">
          <p className="font-medium text-gray-900">{line.product_label || line.desc || 'Produit'}</p>
          {line.product_ref && (
            <p className="text-xs text-gray-500">Réf: {line.product_ref}</p>
          )}
        </div>
      </div>
      <div className="flex justify-between items-center text-sm">
        <div className="text-gray-600">
          <span>{line.qty} x {parseFloat(line.subprice).toFixed(2)}€</span>
          {remise > 0 && (
            <span className="ml-2 text-green-600">(-{remise}%)</span>
          )}
        </div>
        <div className="font-medium text-gray-900">
          {remise > 0 && (
            <span className="line-through text-gray-400 mr-2">{subtotal}€</span>
          )}
          <span>{totalAfterDiscount}€</span>
        </div>
      </div>
      {line.desc && line.desc !== line.product_label && (
        <p className="text-xs text-gray-500 mt-2">{line.desc}</p>
      )}
    </div>
  );
}

export default function ProposalDetailPage({ proposalId, onBack }) {
  const [proposal, setProposal] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    loadProposal();
  }, [proposalId]);

  const loadProposal = async () => {
    try {
      setLoading(true);
      const data = await proposalService.getProposalById(proposalId);
      setProposal(data);
    } catch (err) {
      console.error('Erreur chargement devis:', err);
      setError('Impossible de charger le devis');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Chargement du devis...</p>
        </div>
      </div>
    );
  }

  if (error || !proposal) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div className="bg-red-50 border border-red-200 rounded-xl p-6 max-w-md">
          <AlertCircle className="w-12 h-12 text-red-600 mx-auto mb-4" />
          <p className="text-red-800 text-center">{error || 'Devis introuvable'}</p>
          <button
            onClick={onBack}
            className="mt-4 w-full bg-red-600 text-white py-2 rounded-lg hover:bg-red-700"
          >
            Retour
          </button>
        </div>
      </div>
    );
  }

  const lines = proposal.lines || [];
  const totalHT = parseFloat(proposal.total_ht || 0);
  const totalTTC = parseFloat(proposal.total_ttc || 0);
  const totalTVA = totalTTC - totalHT;

  return (
    <div className="min-h-screen bg-gray-50 pb-20">
      <ProposalHeader proposal={proposal} onBack={onBack} />

      <div className="px-6 py-4 space-y-4">
        {proposal.shipment && (
          <div className="bg-orange-50 border border-orange-200 rounded-lg p-4">
            <div className="flex items-center gap-2 mb-3">
              <Truck className="w-5 h-5 text-orange-600" />
              <p className="text-sm font-medium text-orange-900">Informations de livraison</p>
            </div>
            <div className="space-y-2 text-sm">
              {proposal.shipment.ref && (
                <div className="flex justify-between">
                  <span className="text-orange-700">Référence expédition:</span>
                  <span className="font-medium text-orange-900">{proposal.shipment.ref}</span>
                </div>
              )}
              {proposal.shipment.date_delivery && parseInt(proposal.shipment.date_delivery) > 0 && (
                <div className="flex justify-between">
                  <span className="text-orange-700">Date de livraison:</span>
                  <span className="font-medium text-orange-900">
                    {new Date(parseInt(proposal.shipment.date_delivery) * 1000).toLocaleDateString('fr-FR')}
                  </span>
                </div>
              )}
              {proposal.shipment.shipping_method && (
                <div className="flex justify-between">
                  <span className="text-orange-700">Méthode de livraison:</span>
                  <span className="font-medium text-orange-900">{proposal.shipment.shipping_method}</span>
                </div>
              )}
              {proposal.shipment.tracking_number && (
                <div className="flex justify-between">
                  <span className="text-orange-700">Numéro de suivi:</span>
                  <span className="font-medium text-orange-900">{proposal.shipment.tracking_number}</span>
                </div>
              )}
            </div>
          </div>
        )}

        {proposal.note_private && (
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <p className="text-sm font-medium text-blue-900 mb-1">Note interne</p>
            <p className="text-sm text-blue-800">{proposal.note_private}</p>
          </div>
        )}

        {proposal.note_public && (
          <div className="bg-gray-100 border border-gray-200 rounded-lg p-4">
            <p className="text-sm font-medium text-gray-900 mb-1">Note</p>
            <p className="text-sm text-gray-700">{proposal.note_public}</p>
          </div>
        )}

        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="flex items-center gap-2 mb-4">
            <Package className="w-5 h-5 text-green-600" />
            <h2 className="font-semibold text-gray-900">Produits ({lines.length})</h2>
          </div>

          <div className="space-y-3">
            {lines.map((line, index) => (
              <ProposalLine key={index} line={line} />
            ))}
          </div>

          {lines.length === 0 && (
            <p className="text-gray-500 text-center py-4">Aucun produit</p>
          )}
        </div>

        <div className="bg-white rounded-xl p-4 shadow-sm space-y-2">
          <div className="flex justify-between text-gray-700">
            <span>Total HT</span>
            <span className="font-medium">{totalHT.toFixed(2)} €</span>
          </div>
          <div className="flex justify-between text-gray-700">
            <span>TVA</span>
            <span className="font-medium">{totalTVA.toFixed(2)} €</span>
          </div>
          <div className="border-t pt-2 flex justify-between text-lg font-bold text-gray-900">
            <span>Total TTC</span>
            <span>{totalTTC.toFixed(2)} €</span>
          </div>
        </div>
      </div>
    </div>
  );
}
