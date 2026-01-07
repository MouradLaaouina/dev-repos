import React, { useState, useEffect } from 'react';
import { toast } from 'react-hot-toast';
import { useForm, SubmitHandler, useFieldArray } from 'react-hook-form';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { User, MessageSquare, MapPin, Phone, Users, Plus, Minus, Calendar, Package, Search, Check, X } from 'lucide-react';
import { Contact, ContactStatus, Platform, RequestType, Gender, Brand, OrderItem, Source, PaymentMethod, City } from '../../types';
import { useContactStore } from '../../store/contactStore';
import { useAuthStore } from '../../store/authStore';
import { useProductStore } from '../../store/productStore';
import { useOrderStore } from '../../store/orderStore';
import { moroccanCities, getShippingCost } from '../../data/cities';
import 'flatpickr/dist/themes/light.css';

type ContactFormInputs = Omit<Contact, 'id' | 'createdAt' | 'updatedAt' | 'status' | 'agentId' | 'clientCode'> & {
  orderItems?: OrderItem[];
  paymentMethod?: PaymentMethod;
  transferNumber?: string;
  marque?: Brand;
};

const ContactForm: React.FC = () => {
  const location = useLocation();
  const navigate = useNavigate();
  const params = useParams();
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');
  const [isEditing, setIsEditing] = useState(false);
  const user = useAuthStore((state) => state.user);
  const { addContact, updateContact, getContactById, searchContactsByPhoneNumber } = useContactStore();
  const createOrderForNewContact = useOrderStore((state) => state.createOrderForNewContact);
  const { products, fetchProducts } = useProductStore();
  const [shippingCost, setShippingCost] = useState(35);
  
  const [phoneSearchTerm, setPhoneSearchTerm] = useState('');
  const [selectedExistingContact, setSelectedExistingContact] = useState<Contact | null>(null);

  const navigationState = location.state as any;

  const { register, handleSubmit, reset, watch, control, setValue, formState: { errors } } = useForm<ContactFormInputs>({
    defaultValues: {
      nom: navigationState?.nom || '',
      telephone: navigationState?.telephone || '',
      telephone2: '',
      plateforme: navigationState?.plateforme || 'WhatsApp',
      message: navigationState?.message || '',
      typeDeDemande: navigationState?.typeDeDemande || 'Information',
      ville: 'Casablanca',
      sexe: 'Femme',
      fromAds: false,
      dateMessage: navigationState?.dateMessage || new Date(),
      source: 'R-S',
      address: '',
      marque: '',
      orderItems: [{ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 }],
      paymentMethod: 'Espèce',
    },
  });

  const typeDeDemande = watch('typeDeDemande');
  const selectedCity = watch('ville');
  const orderItems = watch('orderItems');

  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  useEffect(() => {
    if (params.id) {
      const contact = getContactById(params.id);
      if (contact) {
        setIsEditing(true);
        reset({
          ...contact,
          orderItems: [{ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 }],
          paymentMethod: 'Espèce',
        } as any);
      }
    }
  }, [params.id, getContactById, reset]);

  useEffect(() => {
    const baseShippingCost = getShippingCost(selectedCity as City);
    const orderTotal = orderItems?.reduce((sum, item) => sum + (item.unitPrice * item.quantity), 0) || 0;
    setShippingCost(orderTotal >= 300 ? 0 : baseShippingCost);
  }, [selectedCity, orderItems]);

  const { fields, append, remove } = useFieldArray({
    control,
    name: "orderItems",
  });

  const handleProductSelect = (index: number, productCode: string) => {
    const product = products.find(p => p.code === productCode);
    if (product) {
      setValue(`orderItems.${index}.productCode`, product.code);
      setValue(`orderItems.${index}.productName`, product.name);
      setValue(`orderItems.${index}.priceHT`, product.priceHT);
      setValue(`orderItems.${index}.unitPrice`, product.priceHT * 1.2);
    }
  };

  const total = (orderItems?.reduce((sum, item) => sum + (item.unitPrice * item.quantity), 0) || 0) + shippingCost;

  const onSubmit: SubmitHandler<ContactFormInputs> = async (data) => {
    if (!user) return;
    setIsSubmitting(true);
    
    try {
      const contactData = {
        ...data,
        agentId: user.id,
        codeAgence: user.codeAgence,
      };
      
      if (data.typeDeDemande === 'Commande') {
        await createOrderForNewContact({
          ...contactData,
          items: data.orderItems || [],
          total,
          shippingCost
        } as any, selectedExistingContact?.id);
      } else {
        if (isEditing && params.id) {
          await updateContact(params.id, contactData as any);
        } else {
          await addContact(contactData as any, selectedExistingContact?.id);
        }
      }
      
      setSuccessMessage('Opération réussie !');
      setTimeout(() => navigate('/dashboard/clients'), 2000);
    } catch (error) {
      console.error('Error submitting form:', error);
      toast.error('Une erreur est survenue');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="max-w-4xl mx-auto py-8 px-4">
      <h1 className="text-2xl font-bold mb-6">{isEditing ? 'Modifier le contact' : 'Nouveau contact'}</h1>
      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6 bg-white p-6 rounded-lg shadow">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label className="block text-sm font-medium text-gray-700">Nom complet</label>
            <input {...register('nom', { required: true })} className="input mt-1 block w-full" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700">Téléphone</label>
            <input {...register('telephone', { required: true })} className="input mt-1 block w-full" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700">Ville</label>
            <select {...register('ville')} className="input mt-1 block w-full">
              {moroccanCities.map(city => <option key={city} value={city}>{city}</option>)}
            </select>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700">Type de demande</label>
            <select {...register('typeDeDemande')} className="input mt-1 block w-full">
              <option value="Information">Information</option>
              <option value="Commande">Commande</option>
            </select>
          </div>
        </div>
        
        {typeDeDemande === 'Commande' && (
          <div className="border-t pt-6">
            <h2 className="text-lg font-medium mb-4">Produits</h2>
            {fields.map((field, index) => (
              <div key={field.id} className="flex gap-4 mb-4 items-end">
                <div className="flex-1">
                  <select 
                    onChange={(e) => handleProductSelect(index, e.target.value)}
                    className="input w-full"
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
            <button type="button" onClick={() => append({ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 })} className="btn btn-secondary flex items-center gap-2">
              <Plus className="h-4 w-4" /> Ajouter un produit
            </button>
            <div className="mt-4 text-right font-bold">Total: {total.toFixed(2)} DH</div>
          </div>
        )}

        <div className="flex justify-end gap-4">
          <button type="button" onClick={() => navigate(-1)} className="btn btn-secondary">Annuler</button>
          <button type="submit" disabled={isSubmitting} className="btn btn-primary">
            {isSubmitting ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </div>
      </form>
      {successMessage && <div className="mt-4 p-4 bg-green-100 text-green-700 rounded">{successMessage}</div>}
    </div>
  );
};

export default ContactForm;
