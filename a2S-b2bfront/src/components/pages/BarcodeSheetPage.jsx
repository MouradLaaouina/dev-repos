import { useEffect, useState } from 'react';
import { ArrowLeft, Barcode, Tags, Loader2 } from 'lucide-react';
import { productService } from '../../services';

export default function BarcodeSheetPage({ onBack }) {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    loadData();
  }, []);

  const loadData = async () => {
    try {
      setLoading(true);
      const [productsRes, categoriesRes] = await Promise.all([
        productService.getProducts({ page: 0, limit: 300 }),
        productService.getProductCategories()
      ]);
      setProducts(Array.isArray(productsRes) ? productsRes : []);
      setCategories(Array.isArray(categoriesRes) ? categoriesRes : []);
      setError('');
    } catch (err) {
      console.error(err);
      setError('Impossible de charger les produits');
      setProducts([]);
    } finally {
      setLoading(false);
    }
  };

  const filterByCategory = async (categoryValue = '') => {
    try {
      setLoading(true);
      const productsRes = await productService.getProducts({ page: 0, category: categoryValue || undefined, limit: 300 });
      setProducts(Array.isArray(productsRes) ? productsRes : []);
    } catch (err) {
      console.error(err);
      setError('Filtrage impossible pour cette gamme');
      setProducts([]);
    } finally {
      setLoading(false);
    }
  };

  const handleCategoryChange = (value) => {
    setSelectedCategory(value);
    filterByCategory(value);
  };

  return (
    <div className="min-h-screen bg-gray-50">
      <div className="bg-green-600 text-white px-6 py-4 shadow-lg">
        <div className="flex items-center gap-3">
          <button
            onClick={onBack}
            className="flex items-center gap-2 bg-green-700 px-3 py-2 rounded-lg hover:bg-green-800 transition-colors"
          >
            <ArrowLeft className="w-5 h-5" />
            <span className="text-sm font-medium">Retour</span>
          </button>
          <div>
            <h1 className="text-xl font-bold">Fiche codes-barres</h1>
            <p className="text-sm text-green-100">EAN-13 par produit</p>
          </div>
        </div>
      </div>

      <div className="px-6 py-4 space-y-4">
        {error && (
          <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
            {error}
          </div>
        )}

        <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-200 flex items-center gap-3">
          <Tags className="w-5 h-5 text-gray-500" />
          <select
            value={selectedCategory}
            onChange={(e) => handleCategoryChange(e.target.value)}
            className="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500"
          >
            <option value="">Toutes les gammes</option>
            {categories.map(cat => (
              <option key={cat.id} value={cat.id}>
                {cat.label || cat.ref}
              </option>
            ))}
          </select>
        </div>

        {loading ? (
          <div className="flex flex-col items-center justify-center py-12">
            <Loader2 className="w-10 h-10 text-green-600 animate-spin mb-3" />
            <p className="text-gray-600">Chargement des produits...</p>
          </div>
        ) : (
          <div className="space-y-3">
            {products.map(product => {
              const barcodeValue = product.barcode || product.ean13 || product.code_barre || 'Aucun code barre';
              return (
                <div key={product.id} className="bg-white rounded-xl p-4 shadow-sm border border-gray-200 flex items-center justify-between">
                  <div>
                    <p className="font-semibold text-gray-900">{product.label || product.ref}</p>
                    <p className="text-xs text-gray-500">{product.ref}</p>
                    <p className="text-sm text-gray-700 mt-1">EAN-13 : {barcodeValue}</p>
                  </div>
                  <Barcode className="w-8 h-8 text-green-600" />
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
