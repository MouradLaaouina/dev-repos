import { useState, useEffect } from 'react';
import { ArrowLeft, FileText, Clock, CheckCircle, Loader2, AlertCircle, Truck, Bell, RefreshCw, Edit3 } from 'lucide-react';
import { orderService } from '../../services';

function normalizeStatus(proposal) {
  const propStatus = Number(proposal?.status ?? proposal?.statut);
  const shipmentStatus = Number(proposal?.shipment_status);

  if (propStatus === 0) return 'draft';
  if (propStatus === 1) return 'open';

  if (propStatus === 2) {
    if (shipmentStatus === 2) return 'delivered';
    if (shipmentStatus === 1) return 'shipping';
    return 'open';
  }

  return 'unknown';
}

const STATUS_CONFIG = {
  draft:     { label: 'Envoyé',              color: 'bg-gray-100 text-gray-700',     icon: Clock },
  open:      { label: 'Validé',              color: 'bg-blue-100 text-blue-700',     icon: FileText },
  shipping:  { label: 'En cours de livraison', color: 'bg-orange-100 text-orange-700', icon: Truck },
  delivered: { label: 'Livré',               color: 'bg-green-100 text-green-700',   icon: CheckCircle },
  unknown:   { label: 'Inconnu',             color: 'bg-zinc-100 text-zinc-700',     icon: AlertCircle },
};

function formatAmount(value) {
  const n = Number(value);
  return Number.isFinite(n) ? n.toFixed(2) : 'N/A';
}

function ProposalCard({ proposal, onView, onAcknowledgeModification }) {
  const statusKey = normalizeStatus(proposal);
  const config = STATUS_CONFIG[statusKey] ?? STATUS_CONFIG.unknown;
  const StatusIcon = config.icon;

  // Check if modifications have been acknowledged
  const acknowledged = JSON.parse(localStorage.getItem('a2s_acknowledged_proposals') || '{}');
  const isAcknowledged = !!acknowledged[proposal.id];

  const hasModifications = proposal.modified_by_other === true && !isAcknowledged;

  return (
    <div className="w-full">
      {hasModifications && (
        <div className="bg-orange-50 border border-orange-300 rounded-t-xl p-3 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Bell className="w-5 h-5 text-orange-600" />
            <div>
              <p className="text-sm font-medium text-orange-900">Devis modifié</p>
              <p className="text-xs text-orange-700">Ce devis a été modifié dans Dolibarr</p>
            </div>
          </div>
          <div className="flex gap-2">
            <button
              onClick={(e) => {
                e.stopPropagation();
                onAcknowledgeModification(proposal.id, 'refuse');
              }}
              className="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700"
            >
              Refuser
            </button>
            <button
              onClick={(e) => {
                e.stopPropagation();
                onAcknowledgeModification(proposal.id, 'accept');
              }}
              className="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700"
            >
              Accepter
            </button>
          </div>
        </div>
      )}
      <button
        onClick={() => onView(proposal)}
        className={`w-full bg-white p-4 shadow-sm border border-gray-200 hover:shadow-md hover:border-green-300 transition-all text-left ${
          hasModifications ? 'rounded-b-xl border-t-0' : 'rounded-xl'
        }`}
      >
        <div className="flex items-start justify-between mb-3">
          <div className="flex-1">
            <div className="flex items-center gap-2 mb-1">
              <span className="text-xs font-mono bg-gray-100 text-gray-700 px-2 py-1 rounded">
                {proposal.ref}
              </span>
              <span className={`text-xs font-medium px-2 py-1 rounded ${config.color} flex items-center gap-1`}>
                <StatusIcon className="w-3 h-3" />
                {config.label}
              </span>
            </div>
            <h3 className="font-semibold text-gray-900">
              {proposal.client_name || `Client #${proposal.socid}`}
            </h3>
            <p className="text-sm text-gray-600 mt-1">
              {new Date(proposal.created_at).toLocaleDateString('fr-FR', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
              })}
            </p>
          </div>
          <div className="text-right">
            <p className="text-lg font-bold text-green-600">
              {formatAmount(proposal.total_ttc)} €
            </p>
            <p className="text-xs text-gray-500">
              {proposal.lines?.length || 0} produit(s)
            </p>
          </div>
        </div>
      </button>
    </div>
  );
}

export default function ProposalListPage({ user, onBack, onProposalSelect }) {
  const [proposals, setProposals] = useState([]);
  const [filter, setFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    loadProposals();
  }, []);

  const loadProposals = async (isRefresh = false) => {
    try {
      if (isRefresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }
      const data = await orderService.getProposals();
      setProposals(Array.isArray(data) ? data : []);
      setError('');
    } catch (err) {
      setError('Impossible de charger les devis');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleRefresh = () => {
    loadProposals(true);
  };

  // Helper pour vérifier si un devis est modifié et non acquitté
  const isModifiedPending = (proposal) => {
    const acknowledged = JSON.parse(localStorage.getItem('a2s_acknowledged_proposals') || '{}');
    return proposal.modified_by_other === true && !acknowledged[proposal.id];
  };

  const statusOf = (p) => normalizeStatus(p);

  const filteredProposals = proposals.filter((p) => {
    const s = statusOf(p);
    if (filter === 'all') return true;
    if (filter === 'modified') return isModifiedPending(p);
    if (filter === 'draft') return s === 'draft';
    if (filter === 'open') return s === 'open';
    if (filter === 'shipping') return s === 'shipping';
    if (filter === 'delivered') return s === 'delivered';
    return true;
  });

  const modifiedCount  = proposals.filter((p) => isModifiedPending(p)).length;
  const draftCount     = proposals.filter((p) => statusOf(p) === 'draft').length;
  const openCount      = proposals.filter((p) => statusOf(p) === 'open').length;
  const shippingCount  = proposals.filter((p) => statusOf(p) === 'shipping').length;
  const deliveredCount = proposals.filter((p) => statusOf(p) === 'delivered').length;

  const handleViewProposal = (proposal) => {
    if (onProposalSelect && proposal.id) {
      onProposalSelect(proposal.id);
    }
  };

  const handleAcknowledgeModification = async (proposalId, action) => {
    try {
      // Store acknowledgement in localStorage
      const acknowledged = JSON.parse(localStorage.getItem('a2s_acknowledged_proposals') || '{}');
      acknowledged[proposalId] = {
        action,
        timestamp: Date.now()
      };
      localStorage.setItem('a2s_acknowledged_proposals', JSON.stringify(acknowledged));

      // Reload proposals to update UI
      await loadProposals();
    } catch (err) {
      console.error('Error acknowledging modification:', err);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-green-600 text-white px-6 py-4 shadow-lg sticky top-0 z-10">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-3">
            <button
              onClick={onBack}
              className="flex items-center gap-2 bg-green-700 px-3 py-2 rounded-lg hover:bg-green-800 transition-colors"
            >
              <ArrowLeft className="w-5 h-5" />
              <span className="text-sm font-medium">Retour</span>
            </button>
            <div className="flex items-center gap-2">
              <FileText className="w-6 h-6" />
              <h1 className="text-xl font-bold">Mes Devis</h1>
            </div>
          </div>
          <button
            onClick={handleRefresh}
            disabled={refreshing}
            className="p-2 hover:bg-green-700 rounded-lg transition disabled:opacity-50"
            title="Actualiser les devis"
          >
            <RefreshCw className={`w-5 h-5 ${refreshing ? 'animate-spin' : ''}`} />
          </button>
        </div>
        <p className="text-green-100 text-sm">
          Connecté : {user.login || user.email}
        </p>
      </div>

      <div className="px-6 py-4">
        <div className="flex gap-2 mb-6 overflow-x-auto pb-2">
          <button
            onClick={() => setFilter('all')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition ${
              filter === 'all'
                ? 'bg-green-600 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            Tous ({proposals.length})
          </button>

          {modifiedCount > 0 && (
            <button
              onClick={() => setFilter('modified')}
              className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
                filter === 'modified'
                  ? 'bg-orange-600 text-white'
                  : 'bg-orange-50 text-orange-700 border border-orange-300'
              }`}
            >
              <Edit3 className="w-4 h-4" />
              Modifiés ({modifiedCount})
            </button>
          )}

          <button
            onClick={() => setFilter('draft')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
              filter === 'draft'
                ? 'bg-gray-700 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <Clock className="w-4 h-4" />
            Envoyés ({draftCount})
          </button>

          <button
            onClick={() => setFilter('open')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
              filter === 'open'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <FileText className="w-4 h-4" />
            Validés ({openCount})
          </button>

          <button
            onClick={() => setFilter('shipping')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
              filter === 'shipping'
                ? 'bg-orange-600 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <Truck className="w-4 h-4" />
            En cours de livraison ({shippingCount})
          </button>

          <button
            onClick={() => setFilter('delivered')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
              filter === 'delivered'
                ? 'bg-green-600 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <CheckCircle className="w-4 h-4" />
            Livrés ({deliveredCount})
          </button>
        </div>

        {error && (
          <div className="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
            <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>{error}</span>
          </div>
        )}

        {loading ? (
          <div className="flex flex-col items-center justify-center py-12">
            <Loader2 className="w-12 h-12 text-green-600 animate-spin mb-4" />
            <p className="text-gray-500">Chargement des devis...</p>
          </div>
        ) : (
          <div className="space-y-3">
            {filteredProposals.length > 0 ? (
              filteredProposals.map((proposal) => (
                <ProposalCard
                  key={proposal.id}
                  proposal={proposal}
                  onView={handleViewProposal}
                  onAcknowledgeModification={handleAcknowledgeModification}
                />
              ))
            ) : (
              <div className="text-center py-12">
                <FileText className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <p className="text-gray-500 mb-2">Aucun devis trouvé</p>
                <p className="text-sm text-gray-400">
                  {filter === 'all'
                    ? 'Créez votre premier devis'
                    : filter === 'modified'
                    ? 'Aucun devis modifié en attente'
                    : filter === 'draft'
                    ? 'Aucun devis envoyé'
                    : filter === 'open'
                    ? 'Aucun devis validé'
                    : filter === 'shipping'
                    ? 'Aucun devis en cours de livraison'
                    : 'Aucun devis livré'}
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
