import React, { useState, useEffect } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { useNavigate, useParams } from 'react-router-dom';
import { Plus, Minus, Save, X, Package, Calculator, RefreshCw, Eye, Trash2 } from 'lucide-react';
import { Order, OrderItem, Brand } from '../../types';
import { useOrderStore, OrderItem as OrderItemType } from '../../store/orderStore';
import { useProductStore } from '../../store/productStore';
import { useAuthStore } from '../../store/authStore';
import { moroccanCities, getShippingCost } from '../../data/cities';
import { supabase } from '../../lib/supabase';
import toast from 'react-hot-toast';
import { SearchableSelect } from '../../components/common/SearchableSelect';

interface OrderEditFormData {
  orderItems: OrderItem[];
  ville: string;
  address: string;
  telephone: string;
  telephone2?: string;
  paymentMethod: 'Espèce' | 'Virement';
  transferNumber?: string;
}

interface OrderPreview {
  items: OrderItem[];
  subtotal: number;
  shippingCost: number;
  total: number;
  ville: string;
  freeShipping: boolean;
}

const OrderEditForm: React.FC = () => {
  const navigate = useNavigate();
  const params = useParams();
  const user = useAuthStore((state) => state.user);
  const { orders, fetchOrders } = useOrderStore();
  const { products, getProductsByBrand, fetchProducts } = useProductStore();
  
  const [order, setOrder] = useState<Order | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [orderPreview, setOrderPreview] = useState<OrderPreview | null>(null);
  const [showPreview, setShowPreview] = useState(false);
  const [originalOrderId, setOriginalOrderId] = useState<string | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);
  const [selectedBrand, setSelectedBrand] = useState<Brand>('D-WHITE');
  const [deleteSuccess, setDeleteSuccess] = useState(false);

  const { register, handleSubmit, control, watch, setValue, formState: { errors } } = useForm<OrderEditFormData>();
  const { fields, append, remove, replace, update } = useFieldArray({
    control,
    name: "orderItems",
  });

  const watchedItems = watch('orderItems');
  const watchedVille = watch('ville');

  // Fetch products on component mount
  useEffect(() => {
    fetchProducts();
    fetchOrders();
  }, [fetchProducts, fetchOrders]);

  // Load order data and initialize form with existing items
  useEffect(() => {
    if (params.id && orders.length > 0) {
      const foundOrder = orders.find(o => o.id === params.id);
      if (foundOrder) {
        setIsLoading(true);
        console.log('📋 Loading order for edit:', foundOrder);
        setOrder(foundOrder);
        setOriginalOrderId(foundOrder.orderSupabaseId);
        
        // Initialize form with order data
        setValue('ville', foundOrder.ville || 'Casablanca');
        setValue('address', foundOrder.address || '');
        setValue('telephone', foundOrder.telephone);
        setValue('telephone2', foundOrder.telephone2 || '');
        setValue('paymentMethod', foundOrder.paymentMethod);
        setValue('transferNumber', foundOrder.transferNumber || '');
        
        // Clear the field array completely
        replace([]);
        
        // Fetch the latest order items directly from Supabase
        const fetchOrderItems = async () => {
          try {
            console.log('🔄 [DIAGNOSTIC] Fetching latest order items from database for order:', foundOrder.orderSupabaseId);
            
            const { data: orderItems, error } = await supabase
              .from('order_items')
              .select('*')
              .eq('order_id', foundOrder.orderSupabaseId)
              .order('created_at', { ascending: true });
            
            if (error) {
              console.error('❌ [DIAGNOSTIC] Error fetching order items:', error);
              toast.error('Erreur lors du chargement des produits');
              setIsLoading(false);
              return;
            }
            
            console.log('✅ [DIAGNOSTIC] Fetched order items from database:', orderItems);
            console.log('🔍 [DIAGNOSTIC] Number of items found:', orderItems?.length || 0);
            console.log('🔍 [DIAGNOSTIC] Raw items data:', JSON.stringify(orderItems, null, 2));
            
            if (orderItems && orderItems.length > 0) {
              // Map database items to form items
              const formItems = orderItems.map(item => ({
                brand: item.brand as Brand,
                productCode: item.product_id || '',
                productName: item.product_name || '',
                quantity: item.quantity || 1,
                priceHT: (item.unit_price / 1.2) || 0, // Calculate HT price from TTC
                unitPrice: item.unit_price || 0
              }));
              
              // Add each item individually to the form
              formItems.forEach(item => {
                console.log('➕ [DIAGNOSTIC] Adding item to form:', item);
                append(item);
              });
              
              toast.success(`📦 ${formItems.length} produit(s) chargé(s) depuis la base de données`);
            } else {
              // If no items found, add one empty item
              append({
                brand: 'D-WHITE',
                productCode: '',
                productName: '',
                quantity: 1,
                priceHT: 0,
                unitPrice: 0
              });
              toast.info('Aucun produit trouvé, ajoutez des produits à la commande');
            }
            
            setIsLoading(false);
          } catch (err) {
            console.error('❌ Error in fetchOrderItems:', err);
            toast.error('Erreur lors du chargement des produits');
            setIsLoading(false);
          }
        };
        
        fetchOrderItems();
        
        // Clear any existing preview
        setOrderPreview(null);
        setShowPreview(false);
      }
    }
  }, [params.id, orders, setValue, replace, append]);

  // Update order preview
  const updateOrderPreview = () => {
    if (!watchedItems || !watchedVille) {
      console.log('⚠️ Missing data for preview calculation');
      return;
    }

    console.log('🔄 Updating order preview...');
    
    // Filter valid items (with product selected)
    const validItems = watchedItems.filter(item => 
      item && item.productCode && item.quantity > 0
    );

    if (validItems.length === 0) {
      console.log('⚠️ No valid items for preview');
      setOrderPreview(null);
      setShowPreview(false);
      toast.error('Ajoutez au moins un produit pour voir l\'aperçu');
      return;
    }

    // Validation: Ensure all items have valid brands before preview
    const validatedItems = validItems.map(item => {
      const validBrands: Brand[] = ['D-WHITE', 'D-CAP', 'SENSILIS', 'CUMLAUDE', 'BABE', 'BUCCOTHERM', 'CASMARA RETAIL'];
      const brand = validBrands.includes(item.brand as Brand) ? item.brand : 'D-WHITE';
      
      return {
        ...item,
        brand: brand as Brand
      };
    });

    // Calculate subtotal
    const subtotal = validatedItems.reduce((sum, item) => {
      return sum + (item.unitPrice * item.quantity);
    }, 0);

    // Use the getShippingCost function for correct calculation
    const baseShippingCost = getShippingCost(watchedVille);
    
    // Free shipping if ≥ 300 DH
    const freeShipping = subtotal >= 300;
    const shippingCost = freeShipping ? 0 : baseShippingCost;

    // Calculate total
    const total = subtotal + shippingCost;

    const preview: OrderPreview = {
      items: validatedItems,
      subtotal,
      shippingCost,
      total,
      ville: watchedVille,
      freeShipping
    };

    console.log('✅ Order preview calculated:', preview);
    setOrderPreview(preview);
    setShowPreview(true);
    
    toast.success('🔄 Aperçu de la commande généré');
  };

  const handleProductSelect = (index: number, productCode: string) => {
    const product = products.find(p => p.code === productCode);
    if (product) {
      console.log('🔍 [DIAGNOSTIC] Product selected for index', index, ':', product);
      
      // Validation: Ensure brand is valid
      const validBrands: Brand[] = ['D-WHITE', 'D-CAP', 'SENSILIS', 'CUMLAUDE', 'BABE', 'BUCCOTHERM', 'CASMARA RETAIL'];
      const brand = validBrands.includes(product.brand as Brand) ? product.brand : 'D-WHITE';
      
      // Get the current item to preserve its unique ID (managed by useFieldArray)
      const currentItem = fields[index];
      
      // Construct the updated item object
      const updatedItem = {
        ...currentItem, // Keep existing properties, especially the 'id' from useFieldArray
        brand: brand as Brand,
        productCode: product.code,
        productName: product.name,
        priceHT: product.priceHT,
        unitPrice: product.priceHT * 1.2 // Add 20% TVA
      };
      
      // Use the update function to notify react-hook-form about the change
      update(index, updatedItem);
      
      console.log('✅ [DIAGNOSTIC] Product set with validated brand:', { brand, productCode: product.code, productName: product.name });
      console.log('🔍 [DIAGNOSTIC] Updated item object:', updatedItem);
      console.log('🔍 [DIAGNOSTIC] Current fields after update:', JSON.stringify(fields, null, 2));
    }
  };

  const addNewItem = () => {
    console.log('➕ [DIAGNOSTIC] Adding new item with brand:', selectedBrand);
    const newItem = {
      brand: selectedBrand || 'D-WHITE', // Use currently selected brand or default to D-WHITE
      productCode: '',
      productName: '',
      quantity: 1,
      priceHT: 0,
      unitPrice: 0
    };
    console.log('🔍 [DIAGNOSTIC] New item object:', newItem);
    append(newItem);
    console.log('🔍 [DIAGNOSTIC] Fields after append:', JSON.stringify(fields, null, 2));
  };

  const removeItem = (index: number) => {
    if (fields.length > 1) {
      console.log('➖ [DIAGNOSTIC] Removing item at index:', index);
      remove(index);
      // Update preview after removal
      setTimeout(updateOrderPreview, 100);
    } else {
      toast.error('Une commande doit contenir au moins un produit');
    }
  };

  // Enhanced sync with comprehensive validation
  const onSubmit = async (data: OrderEditFormData) => {
    if (!order || !user || !orderPreview) {
      toast.error('Veuillez d\'abord mettre à jour la commande');
      console.error('❌ Missing required data for submission:', { order, user, orderPreview });
      return;
    }

    setIsSubmitting(true);
    try {
      console.log('💾 [DIAGNOSTIC] Saving order with enhanced validation:', {
        contact_id: order.id,
        order_id: order.orderSupabaseId,
        items_count: data.orderItems.length
      });

      // Update contact information
      const { error: contactError } = await supabase
        .from('contacts')
        .update({
          ville: data.ville,
          address: data.address || '',
          telephone: data.telephone,
          telephone2: data.telephone2,
          payment_method: data.paymentMethod,
          transfer_number: data.transferNumber,
          updated_at: new Date().toISOString()
        })
        .eq('id', order.id);

      if (contactError) {
        console.error('❌ [DIAGNOSTIC] Contact update error:', contactError);
        throw contactError;
      }

      console.log('✅ Contact updated successfully');
      
      // First, delete existing order items
      console.log('🗑️ Deleting existing order items for order:', order.orderSupabaseId);
      console.log('🔍 [DIAGNOSTIC] Order ID for deletion:', order.orderSupabaseId);
      
      const { error: deleteItemsError } = await supabase
        .from('order_items')
        .delete()
        .eq('order_id', order.orderSupabaseId);

      if (deleteItemsError) {
        console.error('❌ [DIAGNOSTIC] Error deleting existing order items:', deleteItemsError);
        throw deleteItemsError;
      }

      console.log('✅ [DIAGNOSTIC] Existing order items deleted successfully');
      setDeleteSuccess(true);
      
      // Update order record with new total and shipping cost
      const { error: updateOrderError } = await supabase
        .from('orders')
        .update({
          shipping_cost: orderPreview.shippingCost,
          total: orderPreview.total,
          updated_at: new Date().toISOString()
        })
        .eq('id', order.orderSupabaseId);

      if (updateOrderError) {
        console.error('❌ [DIAGNOSTIC] Error updating order:', updateOrderError);
        throw updateOrderError;
      }

      console.log('✅ Order updated successfully');
      
      // Insert new order items if there are any
      if (data.orderItems && data.orderItems.length > 0) {
        console.log('🔍 [DIAGNOSTIC] Current form items to insert:', JSON.stringify(data.orderItems, null, 2));
        const insertData = data.orderItems.map(item => ({
          order_id: order.orderSupabaseId,
          brand: item.brand,
          product_id: item.product_id ?? item.productCode, // ✅ fallback to code only if needed
          product_name: item.productName,
          quantity: item.quantity || 1, // Ensure quantity is at least 1
          unit_price: item.unitPrice,
        }));

        console.log('📤 [DIAGNOSTIC] Inserting new order items:', JSON.stringify(insertData, null, 2));

        const { error: insertItemsError } = await supabase
          .from('order_items')
          .insert(insertData);

        if (insertItemsError) {
          console.error('❌ [DIAGNOSTIC] Error inserting new order items:', insertItemsError);
          throw insertItemsError;
        }
        console.log('✅ [DIAGNOSTIC] Insert operation completed successfully');

        console.log('✅ New order items inserted successfully');
      }
      
      // Force refresh and navigate with replace to prevent duplicate toasts
      console.log('🔄 Force refreshing all order data...');
      await fetchOrders();

      // Verify the order items after refresh
      const { data: verifyItems, error: verifyError } = await supabase
        .from('order_items')
        .select('*')
        .eq('order_id', order.orderSupabaseId);
      
      console.log('🔍 [DIAGNOSTIC] Verification after save - Items in database:', JSON.stringify(verifyItems, null, 2));
      
      // Wait a moment before navigating to ensure the store is updated
      setTimeout(() => {
        toast.success('✅ Commande sauvegardée avec succès');
        navigate('/dashboard/orders');
      }, 500);

    } catch (error) {
      console.error('❌ [DIAGNOSTIC] Error updating order:', error);
      toast.error(`Erreur lors de la mise à jour: ${error.message}`);
    } finally {
      setIsSubmitting(false);
    }
  };

  // Delete the entire order and its items
  const handleDeleteOrder = async () => {
    if (!order || !order.orderSupabaseId) {
      toast.error('Impossible de supprimer la commande: ID manquant');
      return;
    }
    
    // Confirm deletion
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer définitivement cette commande et tous ses produits? Cette action est irréversible.')) {
      return;
    }
    
    setIsDeleting(true);
    try {
      console.log('🗑️ Suppression de la commande:', order.orderSupabaseId);
      
      // First delete all order items
      const { error: deleteItemsError } = await supabase
        .from('order_items')
        .delete()
        .eq('order_id', order.orderSupabaseId);
        
      if (deleteItemsError) {
        console.error('❌ Erreur lors de la suppression des produits:', deleteItemsError);
        throw deleteItemsError;
      }
      
      console.log('✅ Produits supprimés avec succès');
      
      // Then delete the order itself
      const { error: deleteOrderError } = await supabase
        .from('orders')
        .delete()
        .eq('id', order.orderSupabaseId);
        
      if (deleteOrderError) {
        console.error('❌ Erreur lors de la suppression de la commande:', deleteOrderError);
        throw deleteOrderError;
      }
      
      console.log('✅ Commande supprimée avec succès');
      
      // Update the contact to remove order status
      const { error: updateContactError } = await supabase
        .from('contacts')
        .update({
          status: 'nouveau', // Reset status
          type_de_demande: 'Information', // Reset type
          updated_at: new Date().toISOString()
        })
        .eq('id', order.id);
        
      if (updateContactError) {
        console.error('❌ Erreur lors de la mise à jour du contact:', updateContactError);
        // Continue even if contact update fails
      }
      
      // Refresh orders and navigate back
      await fetchOrders();
      toast.success('Commande supprimée avec succès');
      navigate('/dashboard/orders');
      
    } catch (error) {
      console.error('❌ Erreur lors de la suppression:', error);
      toast.error(`Erreur lors de la suppression: ${error.message}`);
    } finally {
      setIsDeleting(false);
    }
  };

  if (!order || isLoading) {
    return (
      <div className="py-6">
        <div className="text-center">
          <div className="animate-spin h-8 w-8 border-4 border-primary-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="mt-2 text-secondary-600">Chargement de la commande...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="py-6">
      <div className="max-w-4xl mx-auto">
        {/* Header with order information */}
        <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-secondary-800 mb-2">
                Modifier la commande {order.orderNumber}
              </h1>
              <p className="text-secondary-600">
                Client: {order.nom} • {order.plateforme}
              </p>
              <p className="text-sm text-blue-600 mt-2">
                📦 {order.items.length} produit(s) existant(s) chargé(s)
              </p>
            </div>
            <div className="flex items-center gap-2">
              <Package className="h-6 w-6 text-primary-600" />
              {orderPreview ? (
                <span className="text-lg font-semibold text-primary-600">
                  {orderPreview.total.toFixed(2)} DH
                </span>
              ) : (
                <span className="text-lg font-semibold text-gray-400">
                  {order.total.toFixed(2)} DH
                </span>
              )}
              {orderPreview?.freeShipping && (
                <span className="ml-2 px-2 py-1 bg-success-100 text-success-800 text-xs rounded-full">
                  Livraison gratuite!
                </span>
              )}
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
          {/* Customer Information Section */}
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-lg font-semibold text-secondary-800 mb-4">Informations Client</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="label">Téléphone principal</label>
                <input
                  type="tel"
                  className="input"
                  {...register('telephone', { required: 'Le téléphone est obligatoire' })}
                />
                {errors.telephone && (
                  <p className="mt-1 text-sm text-danger-600">{errors.telephone.message}</p>
                )}
              </div>

              <div>
                <label className="label">Téléphone secondaire (optionnel)</label>
                <input
                  type="tel"
                  className="input"
                  {...register('telephone2')}
                />
              </div>

              <div>
                <label className="label">Ville</label>
                <select
                  className="input"
                  {...register('ville', { required: 'La ville est obligatoire' })}
                >
                  {moroccanCities.map(city => (
                    <option key={city} value={city}>{city}</option>
                  ))}
                </select>
                {errors.ville && (
                  <p className="mt-1 text-sm text-danger-600">{errors.ville.message}</p>
                )}
                <p className="mt-1 text-sm text-gray-500">
                  {getShippingCost(watchedVille) === 20 ? 
                    'Frais de livraison: 20 DH' : 
                    'Frais de livraison: 35 DH'
                  }
                  {orderPreview?.freeShipping && ' → Livraison gratuite (≥ 300 DH)'}
                </p>
              </div>

              <div>
                <label className="label">Adresse</label>
                <input
                  type="text"
                  className="input"
                  {...register('address', { required: 'L\'adresse est obligatoire' })}
                />
                {errors.address && (
                  <p className="mt-1 text-sm text-danger-600">{errors.address.message}</p>
                )}
              </div>
            </div>
          </div>

          {/* Payment Information Section */}
          <div className="bg-white rounded-lg shadow-sm p-6">
            <h2 className="text-lg font-semibold text-secondary-800 mb-4">Informations de Paiement</h2>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="label">Méthode de paiement</label>
                <select
                  className="input"
                  {...register('paymentMethod')}
                >
                  <option value="Espèce">Espèce</option>
                  <option value="Virement">Virement</option>
                </select>
              </div>

              {watch('paymentMethod') === 'Virement' && (
                <div>
                  <label className="label">Numéro de transfert</label>
                  <input
                    type="text"
                    className="input"
                    {...register('transferNumber')}
                  />
                </div>
              )}
            </div>
          </div>

          {/* Order Items Section */}
          <div className="bg-white rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-lg font-semibold text-secondary-800">Produits de la commande</h2>
              <button
                type="button"
                onClick={addNewItem}
                className="btn btn-primary flex items-center gap-2"
              >
                <Plus className="h-4 w-4" />
                Ajouter un produit
              </button>
            </div>

            <div className="space-y-4">
              {isLoading ? (
                <div className="p-4 bg-gray-50 rounded-lg text-center">
                  <p className="text-gray-500">Aucun produit dans cette commande</p>
                  <p className="text-sm text-gray-400 mt-1">
                    Cliquez sur "Ajouter un produit" pour commencer
                  </p>
                </div>
              ) : (
                fields.map((field, index) => (
                <div key={field.id} className="p-4 rounded-lg bg-gray-50">
                  <div className="grid grid-cols-1 sm:grid-cols-12 gap-4">
                    {/* Brand - 15% width */}
                    <div className="sm:col-span-2">
                      <label className="label">Marque</label>
                      <select
                        className="input"
                        {...register(`orderItems.${index}.brand` as const, { required: true })}
                        onChange={(e) => {
                          const selectedBrand = e.target.value as Brand;
                          console.log('🏷️ Brand changed to:', selectedBrand);
                          setValue(`orderItems.${index}.brand`, selectedBrand);
                          setValue(`orderItems.${index}.productCode`, '');
                          setValue(`orderItems.${index}.productName`, '');
                          setValue(`orderItems.${index}.priceHT`, 0);
                          setValue(`orderItems.${index}.unitPrice`, 0);
                          setSelectedBrand(selectedBrand);
                        }}
                      >
                        <option value="D-WHITE">D-WHITE</option>
                        <option value="D-CAP">D-CAP</option>
                        <option value="SENSILIS">SENSILIS</option>
                        <option value="CUMLAUDE">CUMLAUDE</option>
                        <option value="BABE">BABE</option>
                        <option value="BUCCOTHERM">BUCCOTHERM</option>
                        <option value="CASMARA RETAIL">CASMARA RETAIL</option>
                      </select>
                    </div>

                    {/* Product - 70% width */}
                    <div className="sm:col-span-8">
                      <label className="label">Produit</label>
                      <SearchableSelect
                        options={getProductsByBrand(watch(`orderItems.${index}.brand`)).map(product => 
                          `${product.name} - ${(product.priceHT * 1.2).toFixed(2)} DH`
                        )}
                        value={watch(`orderItems.${index}.productCode`) ? 
                          getProductsByBrand(watch(`orderItems.${index}.brand`))
                            .find(p => p.code === watch(`orderItems.${index}.productCode`))
                            ?.name + ` - ${(watch(`orderItems.${index}.unitPrice`)).toFixed(2)} DH TTC` || '' : 
                          ''}
                        onChange={(selectedOption) => {
                          // Extract product code from the selected option
                          const productName = selectedOption.split(' - ')[0];
                          const product = getProductsByBrand(watch(`orderItems.${index}.brand`))
                            .find(p => p.name === productName);
                          
                          if (product) {
                            handleProductSelect(index, product.code);
                          }
                        }}
                        placeholder="Rechercher un produit..."
                      />
                    </div>

                    {/* Quantity - 15% width */}
                    <div className="sm:col-span-2">
                      <label className="label">Quantité</label>
                      <input
                        type="number"
                        min="1"
                        className="input"
                        {...register(`orderItems.${index}.quantity` as const, {
                          valueAsNumber: true,
                          min: 1
                        })}
                      />
                    </div>
                  </div>
                  
                  {/* Total and Remove Button */}
                  <div className="flex justify-end mt-3">
                    <div className="flex items-center gap-3">
                      <div className="text-sm text-gray-700">
                        Total: <span className="font-medium">{((watch(`orderItems.${index}.unitPrice`) || 0) * (watch(`orderItems.${index}.quantity`) || 0)).toFixed(2)} DH</span>
                      </div>
                      <button
                        type="button"
                        onClick={() => removeItem(index)}
                        className="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded transition-colors"
                        disabled={fields.length === 1}
                      >
                        <Minus className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>
              )))}
            </div>
              
            <button
              type="button" 
              onClick={addNewItem}
              className="btn btn-outline flex items-center gap-2 w-full justify-center"
            >
              <Plus className="h-4 w-4" />
              Ajouter un autre produit
            </button>
          </div>

          {/* Update Preview Button */}
          <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-sm p-6">
            <div className="flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-blue-800 mb-2">
                  🔄 Aperçu de la commande mise à jour
                </h3>
                <p className="text-blue-600 text-sm">
                  Cliquez pour calculer et prévisualiser la commande avant de sauvegarder
                </p>
              </div>
              <button
                type="button"
                onClick={updateOrderPreview}
                className="btn btn-primary flex items-center gap-2 bg-blue-600 hover:bg-blue-700"
              >
                <RefreshCw className="h-4 w-4" />
                Calculer la commande
              </button>
            </div>
          </div>

          {/* Order Preview */}
          {showPreview && orderPreview && (
            <div className="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg shadow-sm p-6">
              <h2 className="text-lg font-semibold text-green-800 mb-4 flex items-center gap-2">
                <Eye className="h-5 w-5" />
                Aperçu de la commande mise à jour
              </h2>
              
              <div className="space-y-3">
                {/* Items Preview */}
                <div className="bg-white p-4 rounded-lg shadow-sm">
                  <h4 className="font-medium text-green-700 mb-2">Produits:</h4>
                  {orderPreview.items.map((item, index) => (
                    <div key={index} className="flex justify-between items-center py-1 text-sm">
                      <span className="text-green-700">
                        {item.brand} - {item.productName} x{item.quantity}
                      </span>
                      <span className="font-medium text-green-800">
                        {(item.unitPrice * item.quantity).toFixed(2)} DH
                      </span>
                    </div>
                  ))}
                </div>

                {/* Totals Preview */}
                <div className="bg-white p-4 rounded-lg shadow-sm">
                  <div className="space-y-1 text-sm">
                    <div className="flex justify-between">
                      <span className="text-green-600">Sous-total:</span>
                      <span className="font-medium">{orderPreview.subtotal.toFixed(2)} DH</span>
                    </div>
                    
                    <div className="flex justify-between">
                      <span className="text-green-600">
                        Livraison ({orderPreview.ville}):
                        {orderPreview.freeShipping && (
                          <span className="text-green-700 font-medium ml-1">Offerte!</span>
                        )}
                      </span>
                      <span className="font-medium">{orderPreview.shippingCost.toFixed(2)} DH</span>
                    </div>
                    
                    <div className="flex justify-between text-lg font-bold border-t border-green-200 pt-2 mt-2">
                      <span className="text-green-800">Total:</span>
                      <span className="text-green-800">{orderPreview.total.toFixed(2)} DH</span>
                    </div>
                  </div>
                </div>

                {orderPreview.freeShipping && (
                  <div className="bg-green-100 border border-green-200 rounded-md p-2 text-center">
                    <span className="text-green-800 text-sm font-medium">
                      ✨ Livraison gratuite (commande ≥ 300 DH)
                    </span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Form Action Buttons */}
          <div className="flex justify-end gap-4 pt-4">
            <button
              type="button"
              onClick={handleDeleteOrder}
              disabled={isDeleting}
              className="btn btn-danger flex items-center gap-2 mr-auto"
            >
              {isDeleting ? (
                <>
                  <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                  Suppression...
                </>
              ) : (
                <>
                  <Trash2 className="h-4 w-4" />
                  Supprimer la commande
                </>
              )}
            </button>
            
            <button
              type="button"
              onClick={() => navigate('/dashboard/orders')}
              className="btn btn-outline flex items-center gap-2"
            >
              <X className="h-4 w-4" />
              Annuler
            </button>
            
            <button
              type="submit"
              disabled={isSubmitting || !orderPreview}
              className="btn btn-primary flex items-center gap-2 min-w-[200px] disabled:opacity-50"
            >
              {isSubmitting ? (
                <>
                  <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                  Sauvegarde...
                </>
              ) : (
                <>
                  <Save className="h-4 w-4" />
                  Sauvegarder la commande
                  {!orderPreview && (
                    <span className="text-xs opacity-75 ml-1">(Calculer d'abord)</span>
                  )}
                </>
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default OrderEditForm;