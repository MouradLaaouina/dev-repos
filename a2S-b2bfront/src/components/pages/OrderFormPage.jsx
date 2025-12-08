import { useState, useEffect } from 'react';
import { User, Package, CreditCard, MessageSquare, Scan, X, Loader2, AlertCircle, ArrowLeft, Tag } from 'lucide-react';
import { productService, orderService, clientService } from '../../services';
import { discountService } from '../../services/discountService';
import BarcodeScanner from '../BarcodeScanner';

// Composant Header avec bouton retour visible
function OrderHeader({ client, onBack }) {
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
        <h1 className="text-xl font-bold">Nouvelle Commande</h1>
      </div>
      <div className="bg-green-700 rounded-lg p-3 text-sm">
        <p className="font-medium">{client.name || client.nom}</p>
        <p className="text-green-200 text-xs">
          {client.town || ''}
        </p>
      </div>
    </div>
  );
}

// Composant Message d'alerte
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

// Composant Liste de produits avec affichage réduction
function ProductListItem({ product, index, onRemove }) {
  const hasDiscount = product.discountPercent && product.discountPercent > 0;
  const originalPrice = product.originalPrice || product.price;
  const finalPrice = hasDiscount
    ? discountService.calculateDiscountedPrice(originalPrice, product.discountPercent)
    : product.price;
  const subtotal = (product.quantity * finalPrice).toFixed(2);

  return (
    <div className="flex items-center justify-between bg-gray-50 p-3 rounded-lg">
      <div className="flex-1">
        <div className="flex items-center gap-2">
          <p className="font-medium text-sm text-gray-900">
            {product.label || product.ref}
          </p>
          {hasDiscount && (
            <span className="inline-flex items-center gap-1 px-2 py-0.5 bg-green-100 text-green-700 text-xs font-semibold rounded-full">
              <Tag className="w-3 h-3" />
              -{product.discountPercent}%
            </span>
          )}
        </div>
        <div className="text-xs text-gray-600">
          {hasDiscount ? (
            <>
              <span className="line-through text-gray-400 mr-1">{originalPrice.toFixed(2)}€</span>
              <span className="text-green-600 font-medium">{finalPrice.toFixed(2)}€</span>
              <span> x {product.quantity} = {subtotal}€</span>
            </>
          ) : (
            <span>{product.quantity} x {product.price.toFixed(2)}€ = {subtotal}€</span>
          )}
        </div>
      </div>
      <button
        onClick={() => onRemove(index)}
        className="p-2 text-red-600 hover:bg-red-50 rounded-lg"
      >
        <X className="w-5 h-5" />
      </button>
    </div>
  );
}

// Composant Section (wrapper réutilisable)
function FormSection({ icon, title, children }) {
  const IconComponent = icon;
  
  return (
    <div className="bg-white rounded-xl p-4 shadow-sm">
      <div className="flex items-center gap-2 mb-3">
        {IconComponent && <IconComponent className="w-5 h-5 text-green-600" />}
        <h2 className="font-semibold text-gray-900">{title}</h2>
      </div>
      {children}
    </div>
  );
}

// Composant principal
export default function OrderFormPage({ user, client, onBack }) {
  // States groupés par domaine
  const [products, setProducts] = useState([]);
  const [availableProducts, setAvailableProducts] = useState([]);
  const [allProducts, setAllProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [paymentMethods, setPaymentMethods] = useState([]);

  const [selectedProduct, setSelectedProduct] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('');
  const [quantity, setQuantity] = useState(1);
  const [paymentMethod, setPaymentMethod] = useState('');
  const [comment, setComment] = useState('');

  const [showScanner, setShowScanner] = useState(false);
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');
  const [stockWarnings, setStockWarnings] = useState({});

  // Chargement des données
  useEffect(() => {
    loadFormData();
  }, [client.id]);

  useEffect(() => {
    const loadProductsByCategory = async () => {
      try {
        if (!selectedCategory) {
          setAvailableProducts(allProducts);
        } else {
          const filteredProducts = await productService.getProducts({
            page: 0,
            category: selectedCategory
          });
          setAvailableProducts(Array.isArray(filteredProducts) ? filteredProducts : []);
        }
      } catch (error) {
        setAvailableProducts([]);
      }
    };

    loadProductsByCategory();
  }, [selectedCategory, allProducts]);

  const loadFormData = async () => {
    try {
      setLoading(true);

      const [productsRes, paymentsRes, categoriesRes, clientDetails] = await Promise.all([
        productService.getProducts({ page: 0 }),
        orderService.getPaymentMethods(),
        productService.getCategories(),
        clientService.getClientById(client.id)
      ]);

      const productsArray = Array.isArray(productsRes) ? productsRes : [];
      setAllProducts(productsArray);
      setAvailableProducts(productsArray);
      setPaymentMethods(Array.isArray(paymentsRes) ? paymentsRes : []);
      setCategories(Array.isArray(categoriesRes) ? categoriesRes : []);

      if (clientDetails && clientDetails.mode_reglement_id) {
        setPaymentMethod(String(clientDetails.mode_reglement_id));
      }
    } catch (err) {
      console.error('Erreur chargement:', err);
      setError('Erreur lors du chargement des données');
      setAvailableProducts([]);
      setPaymentMethods([]);
      setCategories([]);
    } finally {
      setLoading(false);
    }
  };

  // Gestion des produits
  const addProduct = async () => {
    if (selectedProduct === '' || quantity < 1) return;

    const product = availableProducts.find(p => String(p.id) === selectedProduct);

    if (!product) return;

    const originalPrice = Number(product.price ?? product.price_ttc ?? 0);
    let discountPercent = 0;
    let finalPrice = originalPrice;

    // Récupérer la réduction pour ce produit et ce client
    try {
      const discountInfo = await discountService.getDiscount(product.id, client.id);
      if (discountInfo && discountInfo.has_discount) {
        discountPercent = discountInfo.discount_percent;
        finalPrice = discountService.calculateDiscountedPrice(originalPrice, discountPercent);
      }
    } catch (err) {
      console.error('Erreur récupération réduction:', err);
      // Continue sans réduction en cas d'erreur
    }

    // Vérifier le stock disponible
    try {
      const stockData = await productService.getProductStock(product.id);
      const totalStock = stockData?.stock_reel || 0;

      // Calculer la quantité déjà commandée pour ce produit
      const existingProduct = products.find(item => item.id === product.id);
      const alreadyOrdered = existingProduct ? existingProduct.quantity : 0;
      const newTotalQuantity = alreadyOrdered + quantity;

      // Afficher un avertissement si stock insuffisant (mais ne pas bloquer)
      if (newTotalQuantity > totalStock) {
        setStockWarnings(prev => ({
          ...prev,
          [product.id]: `Stock insuffisant ! Disponible: ${totalStock}, Commandé: ${newTotalQuantity}`
        }));
        setError(`⚠️ Attention : stock insuffisant pour ${product.label || product.ref} (disponible: ${totalStock})`);
        setTimeout(() => setError(''), 5000);
      } else {
        // Retirer l'avertissement si le stock est suffisant
        setStockWarnings(prev => {
          const updated = { ...prev };
          delete updated[product.id];
          return updated;
        });
      }
    } catch (err) {
      console.error('Erreur vérification stock:', err);
      // Continue même en cas d'erreur de vérification du stock
    }

    setProducts(prev => {
      const existingIndex = prev.findIndex(item => item.id === product.id);

      if (existingIndex !== -1) {
        // Produit déjà présent → mise à jour de la quantité
        const updated = [...prev];
        updated[existingIndex] = {
          ...updated[existingIndex],
          quantity: updated[existingIndex].quantity + quantity,
        };
        return updated;
      }

      // Nouveau produit → ajout dans la liste avec info réduction
      return [...prev, {
        ...product,
        quantity,
        price: finalPrice,
        originalPrice: originalPrice,
        discountPercent: discountPercent
      }];
    });

    setSelectedProduct('');
    setQuantity(1);
  };

  const removeProduct = (index) => {
    setProducts(products.filter((_, i) => i !== index));
  };

  // Scanner
  const handleScan = async (barcode) => {
    try {
      setError('');
      const product = await productService.getProductByBarcode(barcode);

      if (product) {
        const originalPrice = Number(product.price || product.price_ttc || 0);
        let discountPercent = 0;
        let finalPrice = originalPrice;

        // Récupérer la réduction pour ce produit scanné
        try {
          const discountInfo = await discountService.getDiscount(product.id, client.id);
          if (discountInfo && discountInfo.has_discount) {
            discountPercent = discountInfo.discount_percent;
            finalPrice = discountService.calculateDiscountedPrice(originalPrice, discountPercent);
          }
        } catch (discountErr) {
          console.error('Erreur récupération réduction:', discountErr);
        }

        setProducts(prev => [...prev, {
          ...product,
          quantity: 1,
          price: finalPrice,
          originalPrice: originalPrice,
          discountPercent: discountPercent
        }]);

        const discountMsg = discountPercent > 0 ? ` (-${discountPercent}%)` : '';
        setSuccess(`✓ Produit ajouté : ${product.label || product.ref}${discountMsg}`);
        setTimeout(() => setSuccess(''), 2000);
      }
    } catch (err) { // eslint-disable-line no-unused-vars
      setError(`✗ Code ${barcode} : Produit non trouvé`);
      setTimeout(() => setError(''), 3000);
    }
  };

  // Calcul du total
  const calculateTotal = () => {
    const subtotal = products.reduce((sum, p) => sum + (p.price * p.quantity), 0);
    return subtotal.toFixed(2);
  };

  // Soumission
  const handleSubmit = async () => {
    if (products.length === 0) {
      setError('Veuillez ajouter au moins un produit');
      return;
    }
    
    if (!paymentMethod) {
      setError('Veuillez sélectionner un mode de paiement');
      return;
    }

    setSubmitting(true);
    setError('');

    try {
      const proposalData = {
        socid: client.id,
        client_name: client.name || client.nom, 
        note_private: comment || '',
        mode_reglement_id: parseInt(paymentMethod),
        lines: products.map(p => ({
          product_type: 0,
          product_id: p.id,
          label: p.label || p.ref,
          qty: p.quantity,
          subprice: p.originalPrice || p.price,
          tva_tx: p.tva_tx || 20,
        }))
      };

      const result = await orderService.createProposal(proposalData);
      
      if (result.status === 'ok' || result.succes === 'ok') {
        setSuccess(`Devis ${result.ref} créé avec succès !`);
        setTimeout(() => onBack(), 2000);
      } else {
        throw new Error('Erreur lors de la création du devis');
      }
    } catch (err) {
      setError(err.message || 'Erreur lors de la création du devis');
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <Loader2 className="w-12 h-12 text-green-600 animate-spin mx-auto mb-4" />
          <p className="text-gray-600">Chargement...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50 pb-24">
      <OrderHeader client={client} onBack={onBack} />

      <div className="px-6 py-4 space-y-6">
        <AlertMessage type="error" message={error} />
        <AlertMessage type="success" message={success} />

        {/* Conseillère */}
        <FormSection icon={User} title="Conseillère">
          <input
            type="text"
            value={user.login || user.email}
            disabled
            className="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-gray-700"
          />
        </FormSection>

        {/* Produits */}
        <FormSection icon={Package} title="Produits">
          <div className="flex items-center justify-end mb-3">
            <button
              onClick={() => setShowScanner(!showScanner)}
              className="p-2 bg-green-50 text-green-600 rounded-lg hover:bg-green-100"
              title="Scanner code-barres EAN-13"
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
            {/* Filtre par catégorie/famille */}
            {categories.length > 0 && (
              <select
                value={selectedCategory}
                onChange={(e) => setSelectedCategory(e.target.value)}
                className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
              >
                <option value="">Toutes les familles</option>
                {categories.map(cat => (
                  <option key={cat.id} value={cat.id}>
                    {cat.label || cat.libelle}
                  </option>
                ))}
              </select>
            )}

            <select
              value={selectedProduct}
              onChange={(e) => setSelectedProduct(e.target.value)}
              className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
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
                className="w-24 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
                placeholder="Qté"
              />
              <button
                onClick={addProduct}
                disabled={!selectedProduct || quantity < 1}
                className="flex-1 bg-green-600 text-white py-3 rounded-lg font-medium hover:bg-green-700 disabled:bg-gray-300"
              >
                Ajouter
              </button>
            </div>
          </div>

          {products.length > 0 && (
            <div className="space-y-2">
              {products.map((product, index) => (
                <div key={index}>
                  <ProductListItem
                    product={product}
                    index={index}
                    onRemove={removeProduct}
                  />
                  {/* Afficher avertissement stock si nécessaire */}
                  {stockWarnings[product.id] && (
                    <div className="mt-1 text-xs text-orange-600 bg-orange-50 px-3 py-1 rounded">
                      ⚠️ {stockWarnings[product.id]}
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </FormSection>

        {/* Mode de paiement */}
        <FormSection icon={CreditCard} title="Mode de Paiement">
          <select
            value={paymentMethod}
            onChange={(e) => setPaymentMethod(e.target.value)}
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
          >
            <option value="">Sélectionner un mode</option>
            {Array.isArray(paymentMethods) && paymentMethods.map(method => (
              <option key={method.id} value={method.id}>
                {method.label || method.libelle || method.code}
              </option>
            ))}
          </select>
        </FormSection>

        {/* Commentaire */}
        <FormSection icon={MessageSquare} title="Commentaire">
          <textarea
            value={comment}
            onChange={(e) => setComment(e.target.value)}
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 h-24 resize-none"
            placeholder="Informations complémentaires..."
          />
        </FormSection>

        {/* Total */}
        {products.length > 0 && (
          <div className="bg-green-600 text-white rounded-xl p-4 shadow-lg">
            <div className="flex justify-between items-center text-lg font-bold">
              <span>Total</span>
              <span>{calculateTotal()} €</span>
            </div>
          </div>
        )}
      </div>

      {/* Bouton submit fixe */}
      <div className="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4 shadow-lg">
        <button
          onClick={handleSubmit}
          disabled={products.length === 0 || !paymentMethod || submitting}
          className="w-full bg-green-600 text-white py-4 rounded-xl font-bold text-lg hover:bg-green-700 disabled:bg-gray-300 flex items-center justify-center gap-2"
        >
          {submitting ? (
            <>
              <Loader2 className="w-5 h-5 animate-spin" />
              Génération en cours...
            </>
          ) : (
            'Générer le Devis'
          )}
        </button>
      </div>
    </div>
  );
}