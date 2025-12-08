import { useEffect, useState } from 'react';
import { ArrowLeft, Loader2, Package, Scan, MessageSquare, User, AlertCircle } from 'lucide-react';
import { productService, selloutService } from '../../services';
import BarcodeScanner from '../BarcodeScanner';

function AlertMessage({ type, message }) {
  if (!message) return null;

  const styles = {
    error: 'bg-red-50 border-red-200 text-red-700',
    success: 'bg-green-50 border-green-200 text-green-700'
  };

  return (
    <div className={`${styles[type]} border px-4 py-3 rounded-xl text-sm flex items-start gap-2`}>
      <AlertCircle className="w-5 h-5 flex-shrink-0 mt-0.5" />
      <span>{message}</span>
    </div>
  );
}

function Section({ icon: Icon, title, children }) {
  return (
    <div className="bg-white rounded-xl p-4 shadow-sm">
      <div className="flex items-center gap-2 mb-3">
        {Icon && <Icon className="w-5 h-5 text-blue-600" />}
        <h2 className="font-semibold text-gray-900">{title}</h2>
      </div>
      {children}
    </div>
  );
}

function ProductRow({ product, index, onRemove }) {
  const subtotal = (product.quantity * product.price).toFixed(2);
  return (
    <div className="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
      <div className="flex-1">
        <p className="font-medium text-sm text-gray-900">
          {product.label || product.ref}
        </p>
        <p className="text-xs text-gray-600">
          {product.quantity} x {product.price.toFixed(2)}€ = {subtotal}€
        </p>
      </div>
      <button
        onClick={() => onRemove(index)}
        className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
      >
        ✕
      </button>
    </div>
  );
}

export default function SelloutFormPage({ user, client, onBack }) {
  const [availableProducts, setAvailableProducts] = useState([]);
  const [products, setProducts] = useState([]);
  const [selectedProduct, setSelectedProduct] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [note, setNote] = useState('');
  const [locationLat, setLocationLat] = useState(null);
  const [locationLng, setLocationLng] = useState(null);
  const [locationStatus, setLocationStatus] = useState('pending'); // pending | ok | error | unsupported
  const [locationMessage, setLocationMessage] = useState('');
  const [showScanner, setShowScanner] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  useEffect(() => {
    const load = async () => {
      try {
        setLoading(true);
        const prodRes = await productService.getProducts({ page: 0 });
        setAvailableProducts(Array.isArray(prodRes) ? prodRes : []);
      } catch (err) {
        console.error('Erreur chargement produits:', err);
        setError('Impossible de charger les produits');
        setAvailableProducts([]);
      } finally {
        setLoading(false);
      }
    };
    load();
  }, [client.id]);

  // Localisation automatique (sans formulaire)
  useEffect(() => {
    if (!navigator.geolocation) {
      setLocationStatus('unsupported');
      setLocationMessage('Géolocalisation non supportée par ce navigateur');
      return;
    }

    setLocationStatus('pending');
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        setLocationLat(pos.coords.latitude);
        setLocationLng(pos.coords.longitude);
        setLocationStatus('ok');
        setLocationMessage('Localisation obtenue automatiquement');
      },
      (err) => {
        console.error('Geolocation error', err);
        setLocationStatus('error');
        setLocationMessage('Localisation indisponible (permissions refusées ou erreur)');
      },
      {
        enableHighAccuracy: true,
        timeout: 8000,
        maximumAge: 60000,
      }
    );
  }, [client.id]);

  const addProduct = () => {
    if (selectedProduct === '' || quantity < 1) return;
    const product = availableProducts.find(p => String(p.id) === selectedProduct);
    if (!product) return;

    const price = Number(product.price ?? product.price_ttc ?? 0);

    setProducts(prev => {
      const existingIndex = prev.findIndex(item => item.id === product.id);
      if (existingIndex !== -1) {
        const updated = [...prev];
        updated[existingIndex] = {
          ...updated[existingIndex],
          quantity: updated[existingIndex].quantity + quantity
        };
        return updated;
      }
      return [...prev, { ...product, quantity, price }];
    });

    setSelectedProduct('');
    setQuantity(1);
  };

  const removeProduct = (index) => {
    setProducts(products.filter((_, i) => i !== index));
  };

  const handleScan = async (barcode) => {
    try {
      setError('');
      const product = await productService.getProductByBarcode(barcode);
      if (product) {
        setProducts(prev => [...prev, {
          ...product,
          quantity: 1,
          price: product.price || product.price_ttc || 0
        }]);
        setSuccess(`✓ Produit ajouté : ${product.label || product.ref}`);
        setTimeout(() => setSuccess(''), 2000);
      }
    } catch (err) {
      setError(`✗ Code ${barcode} : Produit non trouvé`);
      setTimeout(() => setError(''), 3000);
    }
  };

  const handleSubmit = async () => {
    if (products.length === 0) {
      setError('Veuillez ajouter au moins un produit');
      return;
    }

    setSubmitting(true);
    setError('');
    setSuccess('');

    try {
      for (const line of products) {
        const latValue = locationLat !== null ? Number(locationLat) : undefined;
        const lngValue = locationLng !== null ? Number(locationLng) : undefined;
        await selloutService.createSale({
          thirdparty_id: client.id,
          product_id: line.id,
          qty: line.quantity,
          unit_price: line.price,
          salesrep_id: user?.id || user?.rowid,
          note,
          source: 'b2b-front',
          location_label: locationStatus === 'ok' ? 'geolocation' : undefined,
          location_latitude: latValue,
          location_longitude: lngValue,
        });
      }

      setSuccess('Vente sellout envoyée avec succès');
      setProducts([]);
      setNote('');
      setShowScanner(false);
      setTimeout(() => onBack(), 1500);
    } catch (err) {
      console.error('Sellout error', err);
      setError(err.message || 'Erreur lors de l\'envoi du sellout');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-blue-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Chargement...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 pb-24">
      <div className="bg-blue-600 text-white px-6 py-4 shadow-lg sticky top-0 z-10">
        <div className="flex items-center gap-3 mb-3">
          <button
            onClick={onBack}
            className="flex items-center gap-2 bg-blue-700 px-3 py-2 rounded-lg hover:bg-blue-800 transition-colors"
          >
            <ArrowLeft className="w-5 h-5" />
            <span className="text-sm font-medium">Retour</span>
          </button>
          <h1 className="text-xl font-bold">Sellout</h1>
        </div>
        <div className="bg-blue-700 rounded-lg p-3 text-sm">
          <p className="font-medium">{client.name || client.nom}</p>
          <p className="text-blue-200 text-xs">
            {client.email || ''} - {client.code_client || client.id}
          </p>
        </div>
      </div>

      <div className="px-6 py-4 space-y-6">
        <AlertMessage type="error" message={error} />
        <AlertMessage type="success" message={success} />

        <Section icon={User} title="Conseiller">
          <input
            type="text"
            value={user.login || user.email}
            disabled
            className="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-gray-700"
          />
        </Section>

        <Section icon={Package} title="Produits vendus">
          <div className="flex items-center justify-end mb-3">
            <button
              onClick={() => setShowScanner(!showScanner)}
              className="p-2 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100"
              title="Scanner code-barres"
            >
              <Scan className="w-5 h-5" />
            </button>
          </div>

          {showScanner && (
            <BarcodeScanner
              onScanSuccess={handleScan}
              onClose={() => setShowScanner(false)}
              successMessage={success}
              errorMessage={error}
            />
          )}

          <div className="space-y-3 mb-4">
            <select
              value={selectedProduct}
              onChange={(e) => setSelectedProduct(e.target.value)}
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
              disabled={availableProducts.length === 0}
            >
              <option value="">
                {availableProducts.length === 0 ? 'Aucun produit' : 'Sélectionner un produit'}
              </option>
              {availableProducts.map(p => (
                <option key={p.id} value={p.id}>
                  {p.label || p.ref} - {(Number(p.price ?? p.price_ttc ?? 0)).toFixed(2)}€
                </option>
              ))}
            </select>

            <div className="flex gap-3">
              <input
                type="number"
                min="1"
                value={quantity}
                onChange={(e) => setQuantity(parseInt(e.target.value) || 1)}
                className="w-24 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                placeholder="Qté"
              />
              <button
                onClick={addProduct}
                disabled={!selectedProduct || quantity < 1}
                className="flex-1 bg-blue-600 text-white py-3 rounded-lg font-medium hover:bg-blue-700 disabled:bg-gray-300"
              >
                Ajouter
              </button>
            </div>
          </div>

          {products.length > 0 && (
            <div className="space-y-2">
              {products.map((product, index) => (
                <ProductRow
                  key={index}
                  product={product}
                  index={index}
                  onRemove={removeProduct}
                />
              ))}
            </div>
          )}
        </Section>


        <Section icon={MessageSquare} title="Commentaire">
          <textarea
            value={note}
            onChange={(e) => setNote(e.target.value)}
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 h-24 resize-none"
            placeholder="Informations complémentaires..."
          />
        </Section>
      </div>

      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg">
        <button
          onClick={handleSubmit}
          disabled={products.length === 0 || submitting}
          className="w-full bg-blue-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-blue-700 disabled:bg-gray-300 flex items-center justify-center gap-2"
        >
          {submitting ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" />
              Envoi...
            </>
          ) : (
            'Envoyer le Sellout'
          )}
        </button>
      </div>
    </div>
  );
}
