import { useState, useEffect, useRef } from 'react';
import { ArrowLeft, Truck, Package, User, MapPin, Phone, Mail, Loader2, AlertCircle, CheckCircle, Clock, PenTool, Trash2 } from 'lucide-react';
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
  return Number.isFinite(n) ? n.toFixed(2) : '0.00';
}

function Section({ icon: Icon, title, children }) {
  return (
    <div className="bg-white rounded-xl p-4 shadow-sm">
      <div className="flex items-center gap-2 mb-3">
        {Icon && <Icon className="w-5 h-5 text-purple-600" />}
        <h2 className="font-semibold text-gray-900">{title}</h2>
      </div>
      {children}
    </div>
  );
}

function SignatureCanvas({ onSign, onClear }) {
  const canvasRef = useRef(null);
  const [isDrawing, setIsDrawing] = useState(false);
  const [hasSignature, setHasSignature] = useState(false);

  const getCoordinates = (e) => {
    const canvas = canvasRef.current;
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;

    if (e.touches) {
      return {
        x: (e.touches[0].clientX - rect.left) * scaleX,
        y: (e.touches[0].clientY - rect.top) * scaleY
      };
    }
    return {
      x: (e.clientX - rect.left) * scaleX,
      y: (e.clientY - rect.top) * scaleY
    };
  };

  const startDrawing = (e) => {
    e.preventDefault();
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    const coords = getCoordinates(e);

    ctx.beginPath();
    ctx.moveTo(coords.x, coords.y);
    setIsDrawing(true);
  };

  const draw = (e) => {
    if (!isDrawing) return;
    e.preventDefault();

    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    const coords = getCoordinates(e);

    ctx.lineTo(coords.x, coords.y);
    ctx.strokeStyle = '#1e293b';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.stroke();
    setHasSignature(true);
  };

  const stopDrawing = () => {
    setIsDrawing(false);
    if (hasSignature) {
      onSign(canvasRef.current.toDataURL());
    }
  };

  const clearCanvas = () => {
    const canvas = canvasRef.current;
    const ctx = canvas.getContext('2d');
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    setHasSignature(false);
    onClear();
  };

  return (
    <div className="space-y-3">
      <div className="border-2 border-dashed border-gray-300 rounded-lg bg-gray-50 relative">
        <canvas
          ref={canvasRef}
          width={400}
          height={200}
          className="w-full touch-none"
          onMouseDown={startDrawing}
          onMouseMove={draw}
          onMouseUp={stopDrawing}
          onMouseLeave={stopDrawing}
          onTouchStart={startDrawing}
          onTouchMove={draw}
          onTouchEnd={stopDrawing}
        />
        {!hasSignature && (
          <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
            <p className="text-gray-400 text-sm">Signez ici</p>
          </div>
        )}
      </div>
      <button
        onClick={clearCanvas}
        className="flex items-center gap-2 px-4 py-2 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition"
      >
        <Trash2 className="w-4 h-4" />
        Effacer la signature
      </button>
    </div>
  );
}

export default function ShipmentDetailPage({ shipmentId, onBack }) {
  const [shipment, setShipment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [signing, setSigning] = useState(false);
  const [signatureData, setSignatureData] = useState(null);
  const [showSignature, setShowSignature] = useState(false);

  useEffect(() => {
    loadShipment();
  }, [shipmentId]);

  const loadShipment = async () => {
    try {
      setLoading(true);
      setError('');
      const data = await shipmentService.getShipmentById(shipmentId);
      setShipment(data);
    } catch (err) {
      console.error('Error loading shipment:', err);
      setError('Impossible de charger la livraison');
    } finally {
      setLoading(false);
    }
  };

  const handleValidateAndClose = async () => {
    if (!signatureData) {
      setError('Veuillez signer avant de valider la livraison');
      return;
    }

    try {
      setSigning(true);
      setError('');

      const status = Number(shipment.statut || shipment.fk_statut || 0);

      // Si brouillon, d'abord valider
      if (status === 0) {
        await shipmentService.validateShipment(shipmentId);
      }

      // Puis clôturer (marquer comme livré)
      await shipmentService.closeShipment(shipmentId);

      setSuccess('Livraison signée et validée avec succès !');
      setShowSignature(false);

      // Recharger les données
      await loadShipment();

      // Retour après 2 secondes
      setTimeout(() => onBack(), 2000);
    } catch (err) {
      console.error('Error validating shipment:', err);
      setError(err.message || 'Erreur lors de la validation de la livraison');
    } finally {
      setSigning(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-purple-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Chargement de la livraison...</p>
        </div>
      </div>
    );
  }

  if (!shipment) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
          <p className="text-gray-600">Livraison non trouvée</p>
          <button
            onClick={onBack}
            className="mt-4 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700"
          >
            Retour
          </button>
        </div>
      </div>
    );
  }

  const status = Number(shipment.statut || shipment.fk_statut || 0);
  const config = STATUS_CONFIG[status] || STATUS_CONFIG[0];
  const StatusIcon = config.icon;
  const canSign = status < 2; // Peut signer si pas encore livré

  return (
    <div className="min-h-screen bg-gray-50 pb-24">
      {/* Header */}
      <div className="bg-purple-600 text-white px-6 py-4 shadow-lg sticky top-0 z-10">
        <div className="flex items-center gap-3 mb-3">
          <button
            onClick={onBack}
            className="flex items-center gap-2 bg-purple-700 px-3 py-2 rounded-lg hover:bg-purple-800 transition-colors"
          >
            <ArrowLeft className="w-5 h-5" />
            <span className="text-sm font-medium">Retour</span>
          </button>
          <div className="flex-1">
            <div className="flex items-center gap-2">
              <Truck className="w-5 h-5" />
              <h1 className="text-lg font-bold">{shipment.ref}</h1>
            </div>
            <span className={`inline-flex items-center gap-1 text-xs font-medium px-2 py-1 rounded mt-1 ${config.color}`}>
              <StatusIcon className="w-3 h-3" />
              {config.label}
            </span>
          </div>
        </div>
      </div>

      <div className="px-6 py-4 space-y-4">
        {/* Messages */}
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
            <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>{error}</span>
          </div>
        )}
        {success && (
          <div className="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm flex items-start gap-2">
            <CheckCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
            <span>{success}</span>
          </div>
        )}

        {/* Client Info */}
        <Section icon={User} title="Client">
          <div className="space-y-2">
            <p className="font-medium text-gray-900">
              {shipment.client_name || `Client #${shipment.socid}`}
            </p>
            {shipment.client_address && (
              <div className="flex items-start gap-2 text-sm text-gray-600">
                <MapPin className="w-4 h-4 mt-0.5 flex-shrink-0" />
                <span>
                  {shipment.client_address}
                  {shipment.client_zip && `, ${shipment.client_zip}`}
                  {shipment.client_town && ` ${shipment.client_town}`}
                </span>
              </div>
            )}
            {shipment.client_phone && (
              <div className="flex items-center gap-2 text-sm text-gray-600">
                <Phone className="w-4 h-4" />
                <a href={`tel:${shipment.client_phone}`} className="text-purple-600 hover:underline">
                  {shipment.client_phone}
                </a>
              </div>
            )}
            {shipment.client_email && (
              <div className="flex items-center gap-2 text-sm text-gray-600">
                <Mail className="w-4 h-4" />
                <a href={`mailto:${shipment.client_email}`} className="text-purple-600 hover:underline">
                  {shipment.client_email}
                </a>
              </div>
            )}
          </div>
        </Section>

        {/* Dates */}
        <Section icon={Clock} title="Dates">
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p className="text-gray-500">Créé le</p>
              <p className="font-medium">{formatDate(shipment.date_creation)}</p>
            </div>
            {shipment.date_delivery && (
              <div>
                <p className="text-gray-500">Livraison prévue</p>
                <p className="font-medium">{formatDate(shipment.date_delivery)}</p>
              </div>
            )}
            {shipment.date_valid && (
              <div>
                <p className="text-gray-500">Validé le</p>
                <p className="font-medium">{formatDate(shipment.date_valid)}</p>
              </div>
            )}
          </div>
        </Section>

        {/* Produits */}
        <Section icon={Package} title="Produits">
          {shipment.lines && shipment.lines.length > 0 ? (
            <div className="space-y-3">
              {shipment.lines.map((line, index) => (
                <div key={index} className="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
                  <div className="flex-1">
                    <p className="font-medium text-sm text-gray-900">
                      {line.label || line.product_label || line.desc || `Produit #${line.fk_product}`}
                    </p>
                    <p className="text-xs text-gray-600">
                      Réf: {line.product_ref || line.ref || 'N/A'}
                    </p>
                  </div>
                  <div className="text-right">
                    <p className="font-bold text-purple-600">
                      x {line.qty || line.qty_shipped || 1}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-gray-500 text-sm">Aucun produit</p>
          )}
        </Section>

        {/* Origine */}
        {shipment.origin_ref && (
          <Section icon={Truck} title="Commande d'origine">
            <p className="font-mono text-sm bg-gray-100 px-3 py-2 rounded">
              {shipment.origin_ref}
            </p>
          </Section>
        )}

        {/* Signature */}
        {canSign && showSignature && (
          <Section icon={PenTool} title="Signature du client">
            <SignatureCanvas
              onSign={(data) => setSignatureData(data)}
              onClear={() => setSignatureData(null)}
            />
          </Section>
        )}

        {/* Tracking */}
        {shipment.tracking_number && (
          <Section icon={Truck} title="Suivi">
            <p className="font-mono text-sm bg-gray-100 px-3 py-2 rounded">
              {shipment.tracking_number}
            </p>
          </Section>
        )}
      </div>

      {/* Footer Actions */}
      {canSign && (
        <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg">
          {!showSignature ? (
            <button
              onClick={() => setShowSignature(true)}
              className="w-full bg-purple-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-purple-700 flex items-center justify-center gap-2"
            >
              <PenTool className="w-5 h-5" />
              Signer la livraison
            </button>
          ) : (
            <div className="flex gap-3">
              <button
                onClick={() => {
                  setShowSignature(false);
                  setSignatureData(null);
                }}
                className="flex-1 bg-gray-200 text-gray-700 py-4 rounded-xl font-bold hover:bg-gray-300"
              >
                Annuler
              </button>
              <button
                onClick={handleValidateAndClose}
                disabled={!signatureData || signing}
                className="flex-1 bg-green-600 text-white py-4 rounded-xl font-bold hover:bg-green-700 disabled:bg-gray-300 flex items-center justify-center gap-2"
              >
                {signing ? (
                  <>
                    <Loader2 className="w-5 h-5 animate-spin" />
                    Validation...
                  </>
                ) : (
                  <>
                    <CheckCircle className="w-5 h-5" />
                    Valider
                  </>
                )}
              </button>
            </div>
          )}
        </div>
      )}

      {/* Déjà livré */}
      {status === 2 && (
        <div className="fixed bottom-0 left-0 right-0 bg-green-50 border-t border-green-200 p-4">
          <div className="flex items-center justify-center gap-2 text-green-700">
            <CheckCircle className="w-5 h-5" />
            <span className="font-medium">Cette livraison a été validée</span>
          </div>
        </div>
      )}
    </div>
  );
}
