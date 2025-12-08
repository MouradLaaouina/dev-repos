import { useState, useEffect } from 'react';
import { ArrowLeft, Truck, Clock, CheckCircle, Loader2, AlertCircle, Package, RefreshCw } from 'lucide-react';
import { shipmentService } from '../../services';

const STATUS_CONFIG = {
  0: { label: 'Brouillon', color: 'bg-gray-100 text-gray-700', icon: Clock },
  1: { label: 'Validé', color: 'bg-blue-100 text-blue-700', icon: Package },
  2: { label: 'Livré', color: 'bg-green-100 text-green-700', icon: CheckCircle },
};

function formatDate(timestamp) {
  if (!timestamp) return 'N/A';
  const date = typeof timestamp === 'number' ? new Date(timestamp * 1000) : new Date(timestamp);
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
}

function formatAmount(value) {
  const n = Number(value);
  return Number.isFinite(n) ? n.toFixed(2) : 'N/A';
}

function ShipmentCard({ shipment, onView }) {
  const status = Number(shipment.statut || shipment.fk_statut || 0);
  const config = STATUS_CONFIG[status] || STATUS_CONFIG[0];
  const StatusIcon = config.icon;

  return (
    <button
      onClick={() => onView(shipment)}
      className="w-full bg-white rounded-xl p-4 shadow-sm border border-gray-200 hover:shadow-md hover:border-purple-300 transition-all text-left"
    >
      <div className="flex items-start justify-between mb-3">
        <div className="flex-1">
          <div className="flex items-center gap-2 mb-1">
            <span className="text-xs font-mono bg-gray-100 text-gray-700 px-2 py-1 rounded">
              {shipment.ref}
            </span>
            <span className={`text-xs font-medium px-2 py-1 rounded ${config.color} flex items-center gap-1`}>
              <StatusIcon className="w-3 h-3" />
              {config.label}
            </span>
          </div>
          <h3 className="font-semibold text-gray-900">
            {shipment.client_name || `Client #${shipment.socid}`}
          </h3>
          <p className="text-sm text-gray-600 mt-1">
            Créé le {formatDate(shipment.date_creation)}
          </p>
          {shipment.date_delivery && (
            <p className="text-xs text-gray-500 mt-1">
              Livraison prévue : {formatDate(shipment.date_delivery)}
            </p>
          )}
        </div>
        <div className="text-right">
          <p className="text-lg font-bold text-purple-600">
            {formatAmount(shipment.total_ttc)} €
          </p>
          <p className="text-xs text-gray-500">
            {shipment.lines?.length || 0} produit(s)
          </p>
        </div>
      </div>
    </button>
  );
}

export default function ShipmentListPage({ user, onBack, onShipmentSelect }) {
  const [shipments, setShipments] = useState([]);
  const [filter, setFilter] = useState('all');
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState('');

  useEffect(() => {
    loadShipments();
  }, []);

  const loadShipments = async (isRefresh = false) => {
    try {
      if (isRefresh) {
        setRefreshing(true);
      } else {
        setLoading(true);
      }
      const data = await shipmentService.getShipments();
      setShipments(Array.isArray(data) ? data : []);
      setError('');
    } catch (err) {
      console.error('Error loading shipments:', err);
      setError('Impossible de charger les livraisons');
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  const handleRefresh = () => {
    loadShipments(true);
  };

  const statusOf = (s) => Number(s.statut || s.fk_statut || 0);

  const filteredShipments = shipments.filter((s) => {
    const status = statusOf(s);
    if (filter === 'all') return true;
    if (filter === 'draft') return status === 0;
    if (filter === 'validated') return status === 1;
    if (filter === 'delivered') return status === 2;
    return true;
  });

  const draftCount = shipments.filter((s) => statusOf(s) === 0).length;
  const validatedCount = shipments.filter((s) => statusOf(s) === 1).length;
  const deliveredCount = shipments.filter((s) => statusOf(s) === 2).length;

  const handleViewShipment = (shipment) => {
    if (onShipmentSelect && shipment.id) {
      onShipmentSelect(shipment.id);
    }
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-purple-600 text-white px-6 py-4 shadow-lg sticky top-0 z-10">
        <div className="flex items-center justify-between mb-3">
          <div className="flex items-center gap-3">
            <button
              onClick={onBack}
              className="flex items-center gap-2 bg-purple-700 px-3 py-2 rounded-lg hover:bg-purple-800 transition-colors"
            >
              <ArrowLeft className="w-5 h-5" />
              <span className="text-sm font-medium">Retour</span>
            </button>
            <div className="flex items-center gap-2">
              <Truck className="w-6 h-6" />
              <h1 className="text-xl font-bold">Mes Livraisons</h1>
            </div>
          </div>
          <button
            onClick={handleRefresh}
            disabled={refreshing}
            className="p-2 hover:bg-purple-700 rounded-lg transition disabled:opacity-50"
            title="Actualiser les livraisons"
          >
            <RefreshCw className={`w-5 h-5 ${refreshing ? 'animate-spin' : ''}`} />
          </button>
        </div>
        <p className="text-purple-100 text-sm">
          Connecté : {user.login || user.email}
        </p>
      </div>

      <div className="px-6 py-4">
        <div className="flex gap-2 mb-6 overflow-x-auto pb-2">
          <button
            onClick={() => setFilter('all')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition ${
              filter === 'all'
                ? 'bg-purple-600 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            Tous ({shipments.length})
          </button>

          <button
            onClick={() => setFilter('draft')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
              filter === 'draft'
                ? 'bg-gray-700 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <Clock className="w-4 h-4" />
            Brouillons ({draftCount})
          </button>

          <button
            onClick={() => setFilter('validated')}
            className={`px-4 py-2 rounded-lg font-medium whitespace-nowrap transition flex items-center gap-2 ${
              filter === 'validated'
                ? 'bg-blue-600 text-white'
                : 'bg-white text-gray-700 border border-gray-300'
            }`}
          >
            <Package className="w-4 h-4" />
            Validés ({validatedCount})
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
            <Loader2 className="w-12 h-12 text-purple-600 animate-spin mb-4" />
            <p className="text-gray-500">Chargement des livraisons...</p>
          </div>
        ) : (
          <div className="space-y-3">
            {filteredShipments.length > 0 ? (
              filteredShipments.map((shipment) => (
                <ShipmentCard
                  key={shipment.id}
                  shipment={shipment}
                  onView={handleViewShipment}
                />
              ))
            ) : (
              <div className="text-center py-12">
                <Truck className="w-16 h-16 text-gray-300 mx-auto mb-4" />
                <p className="text-gray-500 mb-2">Aucune livraison trouvée</p>
                <p className="text-sm text-gray-400">
                  {filter === 'all'
                    ? 'Aucune livraison disponible'
                    : filter === 'draft'
                    ? 'Aucune livraison en brouillon'
                    : filter === 'validated'
                    ? 'Aucune livraison validée'
                    : 'Aucune livraison effectuée'}
                </p>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}
