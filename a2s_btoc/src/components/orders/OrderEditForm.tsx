import React, { useState, useEffect } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';
import { Plus, X, Package, Save, Trash2 } from 'lucide-react';
import { Order, OrderItem, Brand, City } from '../../types';
import { useOrderStore } from '../../store/orderStore';
import { useProductStore } from '../../store/productStore';
import { useAuthStore } from '../../store/authStore';
import { moroccanCities, getShippingCost } from '../../data/cities';
import toast from 'react-hot-toast';

interface OrderEditFormData {
  orderItems: OrderItem[];
  ville: string;
  address: string;
  telephone: string;
  telephone2?: string;
  paymentMethod: 'Espèce' | 'Virement';
  transferNumber?: string;
}

const OrderEditForm: React.FC = () => {
  const navigate = useNavigate();
  const params = useParams();
  const user = useAuthStore((state) => state.user);
  const { orders, fetchOrders, updateOrder, deleteOrder } = useOrderStore();
  const { products, fetchProducts } = useProductStore();
  
  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const { register, handleSubmit, control, watch, setValue, reset } = useForm<OrderEditFormData>();
  const { fields, append, remove, replace } = useFieldArray({
    control,
    name: "orderItems",
  });

  const watchedItems = watch('orderItems');
  const watchedVille = watch('ville');

  useEffect(() => {
    fetchProducts();
    fetchOrders();
  }, [fetchProducts, fetchOrders]);

  useEffect(() => {
    if (params.id && orders.length > 0) {
      const foundOrder = orders.find(o => o.id === params.id);
      if (foundOrder) {
        setOrder(foundOrder);
        reset({
          ville: foundOrder.ville || 'Casablanca',
          address: foundOrder.address || '',
          telephone: foundOrder.telephone,
          telephone2: foundOrder.telephone2 || '',
          paymentMethod: (foundOrder.paymentMethod as any) || 'Espèce',
          transferNumber: foundOrder.transferNumber || '',
          orderItems: foundOrder.items || []
        });
        setIsLoading(false);
      }
    }
  }, [params.id, orders, reset]);

  const handleProductSelect = (index: number, productCode: string) => {
    const product = products.find(p => p.code === productCode);
    if (product) {
      setValue(`orderItems.${index}.productCode`, product.code);
      setValue(`orderItems.${index}.productName`, product.name);
      setValue(`orderItems.${index}.unitPrice`, product.priceHT * 1.2);
    }
  };

  const onSubmit = async (data: OrderEditFormData) => {
    if (!order) return;
    setIsSubmitting(true);
    try {
      await updateOrder(order.id, data as any);
      toast.success('Commande mise à jour');
      navigate('/dashboard/orders');
    } catch (error) {
      console.error('Error updating order:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDelete = async () => {
    if (!order) return;
    if (window.confirm('Supprimer cette commande ?')) {
      await deleteOrder(order.id);
      navigate('/dashboard/orders');
    }
  };

  if (isLoading || !order) return <div className="text-center py-10">Chargement...</div>;

  return (
    <div className="max-w-4xl mx-auto py-8 px-4">
      <h1 className="text-2xl font-bold mb-6">Modifier la commande {order.orderNumber}</h1>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 bg-white p-6 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium">Ville</label>
            <select {...register('ville')} className="input w-full">
              {moroccanCities.map(city => <option key={city} value={city}>{city}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium">Adresse</label>
            <input {...register('address')} className="input w-full" />
          </div>
        </div>

        <div className="border-t pt-6">
          <h2 className="text-lg font-medium mb-4">Produits</h2>
          {fields.map((field, index) => (
            <div key={field.id} className="flex gap-4 mb-4 items-end">
              <div className="flex-1">
                <select 
                  onChange={(e) => handleProductSelect(index, e.target.value)}
                  className="input w-full"
                  defaultValue={field.productCode}
                >
                  <option value="">Sélectionner un produit</option>
                  {products.map(p => <option key={p.id} value={p.code}>{p.name}</option>)}
                </select>
              </div>
              <div className="w-20">
                <input type="number" {...register(`orderItems.${index}.quantity` as const)} className="input w-full" placeholder="Qté" />
              </div>
              <button type="button" onClick={() => remove(index)} className="text-red-600 p-2"><X /></button>
            </div>
          ))}
          <button type="button" onClick={() => append({ brand: 'D-WHITE' as any, productCode: '', productName: '', quantity: 1, unitPrice: 0, priceHT: 0 })} className="btn btn-secondary">
            Ajouter un produit
          </button>
        </div>

        <div className="flex justify-between gap-4 pt-6 border-t">
          <button type="button" onClick={handleDelete} className="btn bg-red-600 text-white flex items-center gap-2">
            <Trash2 className="h-4 w-4" /> Supprimer
          </button>
          <div className="flex gap-4">
            <button type="button" onClick={() => navigate(-1)} className="btn btn-secondary">Annuler</button>
            <button type="submit" disabled={isSubmitting} className="btn btn-primary flex items-center gap-2">
              <Save className="h-4 w-4" /> {isSubmitting ? 'Sauvegarde...' : 'Sauvegarder'}
            </button>
          </div>
        </div>
      </form>
    </div>
  );
};

export default OrderEditForm;
