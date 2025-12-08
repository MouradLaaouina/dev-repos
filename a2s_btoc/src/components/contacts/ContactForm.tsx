import React, { useState, useEffect } from 'react';
import { toast } from 'react-hot-toast';
import { useForm, SubmitHandler, useFieldArray, Controller } from 'react-hook-form';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import { User, MessageSquare, MapPin, Phone, Users, Plus, Minus, Calendar, Package, Search, Check, X } from 'lucide-react';
import { Contact, ContactStatus, Platform, RequestType, Gender, Brand, OrderItem, Source, PaymentMethod, City, Order } from '../../types';
import { useContactStore } from '../../store/contactStore';
import { useAuthStore } from '../../store/authStore';
import { useProductStore } from '../../store/productStore';
import { useOrderStore } from '../../store/orderStore';
import { moroccanCities, getShippingCost } from '../../data/cities';
import { generateNextClientCode } from '../../utils/clientCodeGenerator';
import Flatpickr from 'react-flatpickr';
import 'flatpickr/dist/themes/light.css';
import { French } from 'flatpickr/dist/l10n/fr';
import { SearchableSelect } from '../common/SearchableSelect';
import { supabase } from '../../lib/supabase';

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
  const [originalTypeDeDemande, setOriginalTypeDeDemande] = useState<RequestType | null>(null);
  const [hasExistingOrder, setHasExistingOrder] = useState(false);
  const user = useAuthStore((state) => state.user);
  const { addContact, updateContact, getContactById, searchContactsByPhoneNumber } = useContactStore();
  const createOrderForNewContact = useOrderStore((state) => state.createOrderForNewContact);
  const { products, getProductsByBrand, fetchProducts } = useProductStore();
  const [shippingCost, setShippingCost] = useState(35);
  
  // Client lookup state
  const [phoneSearchTerm, setPhoneSearchTerm] = useState('');
  const [searchResults, setSearchResults] = useState<Contact[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [selectedExistingContact, setSelectedExistingContact] = useState<Contact | null>(null);
  const [showSearchResults, setShowSearchResults] = useState(false);

  // Get data from navigation state (from WhatsApp or Call Center)
  const navigationState = location.state as any;

  // Get team-based platform restrictions - Call center has no platform field
  const getTeamPlatforms = () => {
    if (user?.role === 'admin') {
      return ['Facebook', 'Instagram', 'WhatsApp', 'Clients'];
    }
    
    switch (user?.codeAgence) {
      case '000001': // Réseaux sociaux - only Facebook and Instagram
        return ['Facebook', 'Instagram'];
      case '000002': // Centre d'appel - No platform selection (they work with existing clients)
        return null; // No platform selection for call center
      case '000003': // WhatsApp - only WhatsApp
        return ['WhatsApp'];
      default:
        return ['Facebook', 'Instagram', 'WhatsApp', 'Clients'];
    }
  };

  // Get team-based source options
  const getTeamSources = () => {
    if (user?.role === 'admin') {
      // Admin sees all sources
      return [
        'R-S',
        'ALOUA LIFE STYLE',
        'WHATSAPP',
        'Démarchage',
        'Parrainage',
        'CLIENT EXISTANT',
        'CHAIMAA MOAD',
        'GHIZLANE CHLIKHATE',
        'HAFSSA ACHRAF',
        'HIBA NAYRAS',
        'SARA ASTERI',
        'Maria LAZRAK',
        'QUEEN BY IMANE',
        'OUMAIMA FARAH',
        'HANANE KHAYATI',
        'NIRMINE BEAUTY',
        'KHADIJA SAKHI',
        'ZINEB LAALAMI',
        'FADIL SALMA',
        'TOUHA',
        'THOURAYA',
        'AHMED KABBAJ',
        'MERIEM ASOUAB',
        'KHAOULA NAOUM',
        'AFRAH',
        'Ghita MOUHIB',
        'SARA EL BAKKAL',
        'YOUSSRA QUEEN',
        'MARWA LAHLOU',
        'Imported Client List'
      ];
    }
    
    switch (user?.codeAgence) {
      case '000002': // Centre d'appel - Only Démarchage and Parrainage (optional)
        return ['PAS DÉFINI', 'Démarchage', 'Parrainage', 'CLIENT EXISTANT', 'Imported Client List']; // Use 'PAS DÉFINI' instead of empty string
      case '000003': // WhatsApp - No source selection
        return null; // No source selection for WhatsApp team
      default:
        // Other teams get the full list
        return [
          'R-S',
          'ALOUA LIFE STYLE',
          'WHATSAPP',
          'CLIENT EXISTANT',
          'CHAIMAA MOAD',
          'GHIZLANE CHLIKHATE',
          'HAFSSA ACHRAF',
          'HIBA NAYRAS',
          'SARA ASTERI',
          'Maria LAZRAK',
          'QUEEN BY IMANE',
          'OUMAIMA FARAH',
          'HANANE KHAYATI',
          'NIRMINE BEAUTY',
          'KHADIJA SAKHI',
          'ZINEB LAALAMI',
          'FADIL SALMA',
          'TOUHA',
          'THOURAYA',
          'AHMED KABBAJ',
          'MERIEM ASOUAB',
          'KHAOULA NAOUM',
          'AFRAH',
          'Ghita MOUHIB',
          'SARA EL BAKKAL',
          'YOUSSRA QUEEN',
          'MARWA LAHLOU',
          'Imported Client List'
        ];
    }
  };

  const teamPlatforms = getTeamPlatforms();
  const teamSources = getTeamSources();
  
  // Set default platform based on team
  const getDefaultPlatform = (): Platform => {
    // If coming from navigation state, use that platform
    if (navigationState?.plateforme) {
      return navigationState.plateforme;
    }
    
    // Otherwise, set default based on team
    if (teamPlatforms) {
      if (teamPlatforms.length === 1) {
        // If only one platform is available, use that
        return teamPlatforms[0] as Platform;
      } else if (user?.codeAgence === '000001') {
        // For Réseaux sociaux team (000001), default to Instagram
        return 'Instagram';
      } else {
        // Default to WhatsApp for other teams
        return 'WhatsApp';
      }
    }
    
    // Fallback default
    return 'WhatsApp';
  };
  
  const defaultPlatform = getDefaultPlatform();
  const defaultSource = user?.codeAgence === '000002' ? 'PAS DÉFINI' : 'R-S'; // Use 'PAS DÉFINI' for call center, R-S for others

  const { register, handleSubmit, reset, watch, control, setValue, formState: { errors } } = useForm<ContactFormInputs>({
    defaultValues: {
      nom: navigationState?.nom || '',
      telephone: navigationState?.telephone || '',
      telephone2: '',
      plateforme: navigationState?.plateforme || defaultPlatform,
      message: navigationState?.message || '',
      typeDeDemande: navigationState?.typeDeDemande || 'Information',
      ville: 'Casablanca',
      sexe: 'Femme',
      fromAds: false,
      dateMessage: navigationState?.dateMessage || new Date(),
      source: navigationState?.source || defaultSource,
      address: '',
      marque: '',
      orderItems: [{ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 }],
      paymentMethod: 'Espèce',
    },
  });

  const typeDeDemande = watch('typeDeDemande');
  const selectedCity = watch('ville');
  const paymentMethod = watch('paymentMethod');
  const orderItems = watch('orderItems');
  const selectedPlatform = watch('plateforme');
  const selectedBrand = watch('marque');
  const phoneNumber = watch('telephone');

  // Fetch products on component mount
  useEffect(() => {
    fetchProducts();
  }, [fetchProducts]);

  // Check if we're editing an existing contact
  useEffect(() => {
    if (params.id) {
      console.log('🔍 Checking contact details for ID:', params.id);
      const contact = getContactById(params.id);
      if (contact) {
        setIsEditing(true);
        setOriginalTypeDeDemande(contact.typeDeDemande);
        reset({
          nom: contact.nom,
          telephone: contact.telephone,
          telephone2: contact.telephone2 || '',
          plateforme: contact.plateforme,
          message: contact.message || '',
          typeDeDemande: contact.typeDeDemande,
          ville: contact.ville || 'Casablanca',
          sexe: contact.sexe,
          fromAds: contact.fromAds,
          dateMessage: contact.dateMessage ? new Date(contact.dateMessage) : new Date(),
          source: contact.source || defaultSource,
          address: contact.address,
          marque: contact.marque as Brand || '',
          orderItems: [{ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 }],
          paymentMethod: 'Espèce',
        });
      }
      
      // Check if this contact already has an order
      const checkExistingOrder = async () => {
        try {
          console.log('🔍 Checking for existing orders for contact:', params.id);
          const { data, error } = await supabase
            .from('orders')
            .select('id')
            .eq('contact_id', params.id);
            
          if (error) {
            console.error('❌ Error checking for existing orders:', error);
            return;
          }
          
          const orderExists = data && data.length > 0;
          console.log(`${orderExists ? '✅' : '❌'} Contact ${params.id} ${orderExists ? 'has' : 'does not have'} existing orders:`, data);
          setHasExistingOrder(orderExists);
        } catch (err) {
          console.error('❌ Error in checkExistingOrder:', err);
        }
      };
      
      checkExistingOrder();
    }
  }, [params.id, getContactById, reset, defaultSource]);

  useEffect(() => {
    const baseShippingCost = getShippingCost(selectedCity);
    const orderTotal = orderItems?.reduce((sum, item) => sum + (item.unitPrice * item.quantity), 0) || 0;
    
    // Free shipping if order total >= 300 DH
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
      setValue(`orderItems.${index}.unitPrice`, product.priceHT * 1.2); // Add 20% TVA
    }
  };

  const total = orderItems?.reduce((sum, item) => {
    return sum + (item.unitPrice * item.quantity);
  }, 0) + shippingCost;

  const totalHT = orderItems?.reduce((sum, item) => {
    return sum + (item.priceHT * item.quantity);
  }, 0) + (shippingCost / 1.2); // Remove TVA from shipping cost

  // Determine if phone is required based on platform or if it's a Commande
  const isPhoneRequired = selectedPlatform === 'WhatsApp' || typeDeDemande === 'Commande';

  // Show order form when Type de Demande is "Commande"
  const showOrderForm = typeDeDemande === 'Commande';

  // Check if this is from call center
  const isFromCallCenter = navigationState?.fromCallCenter;

  // Check if platform field should be hidden (call center team)
  const hidePlatformField = user?.codeAgence === '000002';

  // Check if source field should be hidden (WhatsApp team)
  const hideSourceField = user?.codeAgence === '000003';

  // Handle phone number search
  const handlePhoneSearch = async () => {
    if (phoneSearchTerm.length < 3) {
      setSearchResults([]);
      setShowSearchResults(false);
      return;
    }

    setIsSearching(true);
    try {
      const results = await searchContactsByPhoneNumber(phoneSearchTerm);
      setSearchResults(results);
      setShowSearchResults(results.length > 0);
    } catch (error) {
      console.error('Error searching contacts:', error);
      toast.error('Erreur lors de la recherche des contacts');
    } finally {
      setIsSearching(false);
    }
  };

  // Select an existing contact
  const handleSelectContact = (contact: Contact) => {
    setSelectedExistingContact(contact);
    setShowSearchResults(false);
    
    // Fill form with contact data
    setValue('nom', contact.nom);
    setValue('telephone', contact.telephone);
    setValue('telephone2', contact.telephone2 || '');
    setValue('plateforme', contact.plateforme);
    setValue('ville', contact.ville);
    setValue('sexe', contact.sexe);
    setValue('address', contact.address);
    setValue('source', contact.source || defaultSource);
    setValue('marque', contact.marque as Brand || '');
    
    // If the contact has a message, keep it but don't overwrite any existing message
    if (contact.message && !watch('message')) {
      setValue('message', contact.message);
    }
    
    // Preserve the current typeDeDemande if it's already set (e.g., from navigation state)
    if (!watch('typeDeDemande')) { // Only set if not already set
      setValue('typeDeDemande', contact.typeDeDemande);
    }
    
    toast.success(`Client existant sélectionné: ${contact.nom}`);
  };

  // Clear selected contact
  const handleClearSelectedContact = () => {
    setSelectedExistingContact(null);
    
    // Reset form to default values except for the current type of request
    const currentType = watch('typeDeDemande');
    reset({
      nom: '',
      telephone: phoneSearchTerm, // Keep the search term
      telephone2: '',
      plateforme: defaultPlatform,
      message: '',
      typeDeDemande: currentType, // Keep the current type
      ville: 'Casablanca',
      sexe: 'Femme',
      fromAds: false,
      dateMessage: new Date(),
      source: defaultSource,
      address: '',
      marque: '',
      orderItems: [{ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 }],
      paymentMethod: 'Espèce',
    });
  };

  const onSubmit: SubmitHandler<ContactFormInputs> = async (data) => {
    if (!user) return;
    
    setIsSubmitting(true);
    
    try {
      console.log('📝 Submitting contact form with data:', data);
      console.log('Original type de demande:', originalTypeDeDemande);

      const clientCode = selectedExistingContact?.clientCode;

      // Prepare contact data
      const contactData = {
        ...data, 
        agentId: user.id,
        agentCode: user.code,
        agentName: user.name,
        codeAgence: user.codeAgence,
        status: data.typeDeDemande === 'Commande' ? 'À confirmer' as const : 'nouveau' as const,
        clientCode: clientCode,
      };
      
      if (isEditing && params.id) {
        // Handle editing with potential conversion to order
        // Only convert to order if the original type was not "Commande" and the new type is "Commande"
        if (data.typeDeDemande === 'Commande' && originalTypeDeDemande !== 'Commande' && showOrderForm && !hasExistingOrder) {
          try {
            // Convert existing contact to order using upsertOrderForContact
            console.log('🔄 Converting existing contact to order:', params.id);
            const orderData = { 
              ...contactData,
              items: data.orderItems || [],
              shippingCost,
              total,
              totalHT,
              status: 'À confirmer' as const,
              paymentMethod: data.paymentMethod!,
              transferNumber: data.transferNumber,
              clientCode: clientCode,
            };
            
            const result = await useOrderStore.getState().createOrderForExistingContact(params.id, orderData);
            console.log('✅ Contact converted to order successfully:', result);
            setSuccessMessage('Contact converti en commande avec succès!');
          } catch (error) {
            console.error('❌ Error converting contact to order:', error);
            throw error;
          }
        } else if (data.typeDeDemande === 'Commande' && hasExistingOrder) {
          // If contact already has an order, just update the contact
          console.log('⚠️ Contact already has an order, updating contact only');
          // Create a new order for this existing contact
          const orderData = {
            ...contactData,
            items: data.orderItems || [],
            shippingCost,
            total,
            totalHT,
            status: 'À confirmer' as const,
            paymentMethod: data.paymentMethod!,
            transferNumber: data.transferNumber,
            clientCode: clientCode,
          };
          
          try {
            const result = await useOrderStore.getState().createOrderForExistingContact(params.id, orderData);
            console.log('✅ New order created for existing contact with existing order:', result);
            setSuccessMessage('Nouvelle commande créée avec succès pour ce client!');
          } catch (error) {
            console.error('❌ Error creating new order for existing contact:', error);
            throw error;
          }
        } else {
          // Regular contact update
          console.log('📝 Updating contact:', contactData);
          await updateContact(params.id, contactData);
          setSuccessMessage('Contact modifié avec succès!');
        }
      } else {
        // Create new contact or order using existing contact if selected
        if (data.typeDeDemande === 'Commande') {
          try {
            const orderData = {
              ...contactData,
              items: data.orderItems || [],
              shippingCost,
              total,
              totalHT,
              status: 'À confirmer' as const,
              paymentMethod: data.paymentMethod!,
              transferNumber: data.transferNumber,
              // If we have a selected existing contact, pass its clientCode
              clientCode: clientCode || selectedExistingContact?.clientCode,
            };
            console.log('🛒 Creating order with data:', orderData);
            
            // If we have a selected existing contact, use its ID
            if (selectedExistingContact) {
              // Use upsertOrderForContact for existing contacts
              await useOrderStore.getState().createOrderForExistingContact(selectedExistingContact.id, orderData);
              setSuccessMessage('Nouvelle commande ajoutée pour client existant avec succès!');
            } else {
              // For brand new contacts, use the regular addOrder
              await createOrderForNewContact(orderData);
              setSuccessMessage('Commande ajoutée avec succès!');
            }
          } catch (error) {
            console.error('❌ Error creating order:', error);
            throw error;
          }
        } else {
          // For non-order contacts
          if (selectedExistingContact) {
            // Update existing contact
            console.log('🔄 Updating existing contact:', selectedExistingContact.id, contactData);
            await addContact(contactData, selectedExistingContact.id);
            setSuccessMessage('Contact existant mis à jour avec succès!');
          } else {
            // Create new contact
            console.log('👤 Creating new contact:', contactData);
            await addContact(contactData);
            setSuccessMessage('Contact ajouté avec succès!');
          }
        }
      }
      
      setTimeout(() => {
  setSuccessMessage('');
  if (data.typeDeDemande === 'Commande') {
    navigate('/dashboard/orders', { replace: true });
  } else if (isFromCallCenter) {
    navigate('/dashboard/call-center', { replace: true });
  } else {
    navigate('/dashboard/clients', { replace: true });
  }
      }, 1500);
      
    } catch (error) {
      console.error('Error saving contact:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="py-6">
      <div className="max-w-5xl mx-auto">
        {/* Header */}
        <div className="bg-gradient-to-r from-secondary-50 to-primary-50 rounded-lg p-6 mb-6 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-secondary-800 mb-2">
                {isEditing ? 'Modifier le contact' : 
                 isFromCallCenter ? 'Créer une commande depuis le centre d\'appel' :
                 selectedExistingContact ? 'Utiliser un client existant' : 'Ajouter un nouveau message'}
              </h1>
              {/* Show team platform access info - Hide for call center */}
              {user?.codeAgence && user.role !== 'admin' && !hidePlatformField && teamPlatforms && (
                <div className="bg-blue-100 border border-blue-300 rounded-md p-3 mt-3">
                  <p className="text-blue-800 text-sm">
                    🔒 Votre équipe ({user.codeAgence}) a accès aux plateformes: <span className="font-semibold">{teamPlatforms.join(', ')}</span>
                  </p>
                </div>
              )}
              {/* Show source restrictions for call center */}
              {user?.codeAgence === '000002' && (
                <div className="bg-green-100 border border-green-300 rounded-md p-3 mt-3">
                  <p className="text-green-800 text-sm">
                    📋 Sources disponibles: <span className="font-semibold">Pas défini (optionnel), Démarchage, Parrainage, Client Existant</span>
                  </p>
                  <p className="text-green-800 text-sm mt-1">
                    📞 Équipe centre d'appel - Travaille avec base clients existante (toutes plateformes)
                  </p>
                </div>
              )}
              
              {/* Show warning if contact has existing order */}
              {isEditing && hasExistingOrder && (
                <div className="bg-yellow-100 border border-yellow-300 rounded-md p-3 mt-3">
                  <p className="text-yellow-800 text-sm font-semibold">
                    ⚠️ Ce contact a déjà une commande associée. Pour modifier la commande, utilisez l'éditeur de commande.
                  </p>
                </div>
              )}
              {isFromCallCenter && (
                <div className="bg-green-100 border border-green-300 rounded-md p-3 mt-3">
                  <p className="text-green-800 text-sm">
                    📞 Données pré-remplies depuis le centre d'appel. Vérifiez et complétez les informations nécessaires.
                  </p>
                </div>
              )}
              {selectedExistingContact && (
                <div className="bg-blue-100 border border-blue-300 rounded-md p-3 mt-3">
                  <div className="flex justify-between items-center">
                    <div>
                      <p className="text-blue-800 text-sm font-semibold">
                        ✅ Client existant sélectionné: {selectedExistingContact.nom}
                      </p>
                      <p className="text-blue-700 text-xs mt-1">
                        ID: {selectedExistingContact.id} 
                        {selectedExistingContact.clientCode && ` • Code client: ${selectedExistingContact.clientCode}`}
                      </p>
                    </div>
                    <button 
                      onClick={handleClearSelectedContact}
                      className="text-blue-700 hover:text-blue-900 text-sm flex items-center gap-1"
                    >
                      <X className="h-4 w-4" />
                      Annuler
                    </button>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
        
        {successMessage && (
          <div className="mb-6 p-4 bg-success-50 border border-success-200 text-success-700 rounded-md animate-pulse-once">
            {successMessage}
          </div>
        )}
        
        {/* Client Search Section */}
        {!isEditing && (
          <div className="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg shadow-sm p-6 mb-6">
            <h2 className="text-lg font-semibold text-blue-800 mb-4">Rechercher un client existant</h2>
            <div className="flex flex-col md:flex-row gap-4">
              <div className="flex-1">
                <label className="label text-blue-700">Numéro de téléphone</label>
                <div className="relative">
                  <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <Search className="h-5 w-5 text-gray-400" />
                  </div>
                  <input
                    type="text"
                    placeholder="Rechercher par numéro de téléphone..."
                    value={phoneSearchTerm}
                    onChange={(e) => setPhoneSearchTerm(e.target.value)}
                    className="input pl-10 w-full"
                  />
                </div>
                <p className="text-xs text-blue-600 mt-1">
                  Entrez au moins 3 caractères pour rechercher un client existant
                </p>
              </div>
              <div className="flex items-end">
                <button
                  type="button"
                  onClick={handlePhoneSearch}
                  disabled={phoneSearchTerm.length < 3 || isSearching}
                  className="btn btn-primary h-10 flex items-center gap-2 disabled:opacity-50"
                >
                  {isSearching ? (
                    <>
                      <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                      Recherche...
                    </>
                  ) : (
                    <>
                      <Search className="h-4 w-4" />
                      Rechercher
                    </>
                  )}
                </button>
              </div>
            </div>

            {/* Search Results */}
            {showSearchResults && (
              <div className="mt-4 border border-blue-200 rounded-md bg-white">
                <div className="p-3 border-b border-blue-200 bg-blue-50">
                  <h3 className="font-medium text-blue-800">
                    {searchResults.length} résultat{searchResults.length !== 1 ? 's' : ''} trouvé{searchResults.length !== 1 ? 's' : ''}
                  </h3>
                </div>
                <div className="max-h-60 overflow-y-auto">
                  {searchResults.map(contact => (
                    <div 
                      key={contact.id} 
                      className="p-3 border-b border-gray-100 hover:bg-blue-50 cursor-pointer transition-colors duration-150"
                      onClick={() => handleSelectContact(contact)}
                    >
                      <div className="flex justify-between">
                        <div>
                          <p className="font-medium text-gray-900">{contact.nom}</p>
                          <div className="flex items-center gap-4 text-sm text-gray-600">
                            <span className="flex items-center gap-1">
                              <Phone className="h-3 w-3" />
                              {contact.telephone}
                            </span>
                            {contact.ville && (
                              <span className="flex items-center gap-1">
                                <MapPin className="h-3 w-3" />
                                {contact.ville}
                              </span>
                            )}
                          </div>
                        </div>
                        <div className="text-right">
                          <div className="text-xs text-gray-500">
                            ID: {contact.id.substring(0, 8)}...
                          </div>
                          {contact.clientCode && (
                            <div className="text-xs text-blue-600">
                              Code client: {contact.clientCode}
                            </div>
                          )}
                          <div className="mt-1">
                            <span className="inline-block px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                              {contact.plateforme}
                            </span>
                          </div>
                        </div>
                      </div>
                      <div className="mt-2 flex justify-end">
                        <button
                          type="button"
                          className="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1"
                        >
                          <Check className="h-3 w-3" />
                          Sélectionner
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
        
        <form onSubmit={handleSubmit(onSubmit)} className="bg-white rounded-lg shadow-sm p-6 space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Nom */}
            <div>
              <label htmlFor="nom" className="label">Nom</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <User className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="nom"
                  type="text"
                  className={`input pl-10 ${errors.nom ? 'border-danger-300' : ''}`}
                  {...register('nom', { required: 'Le nom est obligatoire' })}
                />
              </div>
              {errors.nom && (
                <p className="mt-1 text-sm text-danger-600">{errors.nom.message}</p>
              )}
            </div>

            {/* Platform selection based on team - Hide for call center */}
            {!hidePlatformField && teamPlatforms && (
              <div>
                <label htmlFor="plateforme" className="label">Plateforme</label>
                {teamPlatforms.length === 1 ? (
                  // If only one platform available, show as read-only
                  <div className="input bg-gray-50 text-gray-700 flex items-center">
                    {teamPlatforms[0]}
                    <input type="hidden" {...register('plateforme')} value={teamPlatforms[0]} />
                  </div>
                ) : (
                  // If multiple platforms available, show select
                  <select
                    id="plateforme"
                    className="input"
                    {...register('plateforme')}
                  >
                    {teamPlatforms.map(platform => (
                      <option key={platform} value={platform}>{platform}</option>
                    ))}
                  </select>
                )}
                {teamPlatforms.length === 1 && (
                  <p className="mt-1 text-xs text-gray-500">
                    Plateforme fixée pour votre équipe
                  </p>
                )}
              </div>
            )}

            {/* Hidden platform field for call center - set default value */}
            {hidePlatformField && (
              <input type="hidden" {...register('plateforme')} value="WhatsApp" />
            )}
            
            {/* Téléphone */}
            <div>
              <label htmlFor="telephone" className="label">
                Téléphone {isPhoneRequired && <span className="text-danger-500">*</span>}
                {!isPhoneRequired && <span className="text-gray-500 text-sm">(optionnel)</span>}
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <Phone className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="telephone"
                  type="tel"
                  className={`input pl-10 ${errors.telephone ? 'border-danger-300' : ''}`}
                  {...register('telephone', { 
                    required: isPhoneRequired ? 'Le téléphone est obligatoire pour WhatsApp ou pour une commande' : false,
                    pattern: {
                      value: /^[0-9+()\-\s]{10,20}$/,
                      message: 'Le numéro doit être un format de téléphone valide (10-20 caractères, chiffres, +, -, (, ) )'
                    }
                  })}
                  placeholder="0612345678 ou +212612345678"
                />
              </div>
              {errors.telephone && (
                <p className="mt-1 text-sm text-danger-600">{errors.telephone.message}</p>
              )}
              <p className="mt-1 text-xs text-gray-500">
                Format: 10-20 caractères, chiffres, +, -, (, ) autorisés
              </p>
            </div>

            {/* Téléphone 2 - Updated with more flexible pattern */}
            <div>
              <label htmlFor="telephone2" className="label">Téléphone 2 (optionnel)</label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <Phone className="h-5 w-5 text-gray-400" />
                </div>
                <input
                  id="telephone2"
                  type="tel"
                  className={`input pl-10 ${errors.telephone2 ? 'border-danger-300' : ''}`}
                  {...register('telephone2', {
                    pattern: {
                      value: /^[0-9+()\-\s]{10,20}$/,
                      message: 'Le numéro doit être un format de téléphone valide (10-20 caractères, chiffres, +, -, (, ) )'
                    }
                  })}
                  placeholder="0612345678 ou +212612345678"
                />
              </div>
              {errors.telephone2 && (
                <p className="mt-1 text-sm text-danger-600">{errors.telephone2.message}</p>
              )}
              <p className="mt-1 text-xs text-gray-500">
                Format: 10-20 caractères, chiffres, +, -, (, ) autorisés
              </p>
            </div>
            
            {/* Type de Demande */}
            <div>
              <label htmlFor="typeDeDemande" className="label">Type de Demande</label>
              <select
                id="typeDeDemande"
                className="input"
                {...register('typeDeDemande')}
              >
                <option value="Information">Information</option>
                <option value="En attente de traitement">En attente de traitement</option>
                <option value="Orientation Para">Orientation Para</option>
                <option value="Sans réponse">Sans réponse</option>
                <option value="En attente de réponse">En attente de réponse</option>
                <option value="Annulee">Annulée</option>
                <option value="Commande">Commande</option>
              </select>
            </div>

            {/* Brand Dropdown */}
            <div>
              <label htmlFor="marque" className="label">Marque d'intérêt <span className="text-danger-500">*</span></label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                  <Package className="h-5 w-5 text-gray-400" />
                </div>
                <select
                  id="marque"
                  className="input pl-10"
                  {...register('marque', { required: 'Veuillez sélectionner une marque.' })}
                >
                  <option value="">Sélectionner une Marque</option>
                  <option value="D-WHITE">D-WHITE</option>
                  <option value="D-CAP">D-CAP</option>
                  <option value="SENSILIS">SENSILIS</option>
                  <option value="CUMLAUDE">CUMLAUDE</option>
                  <option value="BABE">BABE</option>
                  <option value="BUCCOTHERM">BUCCOTHERM</option>
                  <option value="CASMARA RETAIL">CASMARA RETAIL</option>
                </select>
              </div>
              <p className="mt-1 text-xs text-gray-500">
                Sélectionnez la marque qui intéresse le client
              </p>
            </div>
            
            {/* Show Ville and Address when Type de Demande is "Commande" */}
            {showOrderForm && (
              <>
                {/* Ville */}
                <div>
                  <label htmlFor="ville" className="label">Ville</label>
                  <select
                    id="ville"
                    className="input"
                    {...register('ville')}
                    onChange={(e) => {
                      setValue('ville', e.target.value as City);
                    }}
                  >
                    {moroccanCities.map(city => (
                      <option key={city} value={city}>{city}</option>
                    ))}
                  </select>
                  <p className="mt-1 text-sm text-gray-500">
                    {shippingCost === 0 ? (
                      <span className="text-success-600 font-medium">Livraison gratuite (commande ≥ 300 DH)</span>
                    ) : (
                      `Frais de livraison: ${shippingCost} DH`
                    )}
                  </p>
                </div>
                
                {/* Adresse */}
                <div>
                  <label htmlFor="address" className="label">Adresse</label>
                  <input
                    id="address"
                    type="text"
                    className={`input ${errors.address ? 'border-danger-300' : ''}`}
                    {...register('address', { 
                      required: showOrderForm ? 'L\'adresse est obligatoire pour les commandes' : false 
                    })}
                  />
                  {errors.address && (
                    <p className="mt-1 text-sm text-danger-600">{errors.address.message}</p>
                  )}
                </div>
              </>
            )}
            
            {/* Sexe and Source */}
            <div>
              <label htmlFor="sexe" className="label">Sexe</label>
              <select
                id="sexe"
                className="input"
                {...register('sexe')}
              >
                <option value="Femme">Femme</option>
                <option value="Homme">Homme</option>
              </select>
            </div>

            {/* Source with team-based restrictions and optional for call center */}
            {!hideSourceField && teamSources && (
              <div>
                <label htmlFor="source" className="label">
                  Source
                  {user?.codeAgence === '000002' && (
                    <span className="text-gray-500 text-sm ml-1">(optionnel)</span>
                  )}
                </label>
                <select
                  id="source"
                  className="input"
                  {...register('source')}
                >
                  {teamSources.map(source => (
                    <option key={source} value={source}>
                      {source === 'PAS DÉFINI' ? 'Pas défini' : source}
                    </option>
                  ))}
                </select>
                {user?.codeAgence === '000002' && (
                  <p className="mt-1 text-xs text-blue-600">
                    Source optionnelle pour le centre d'appel - vous pouvez sélectionner "Pas défini"
                  </p>
                )}
              </div>
            )}

            {/* Hidden source field for WhatsApp team - set default value */}
            {hideSourceField && (
              <input type="hidden" {...register('source')} value="R-S" />
            )}
          </div>
          
          {/* Message (optional) */}
          <div>
            <label htmlFor="message" className="label">Message (optionnel)</label>
            <textarea
              id="message"
              rows={4}
              className="input"
              {...register('message')}
            ></textarea>
          </div>
          
          {/* Order Section - Show when Type de Demande is "Commande" */}
          {showOrderForm && (
            <div className="space-y-6 pt-6 border-t border-gray-200">
              <div className="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg shadow-sm">
                <h3 className="text-lg font-medium text-blue-900 mb-2 flex items-center gap-2">
                  <Package className="h-5 w-5" />
                  Détails de la commande
                </h3>
                <p className="text-blue-700 text-sm">
                  Ajoutez les produits commandés par le client
                </p>
              </div>
              
              {/* Product Items */}
              <div className="space-y-4">
                {fields.map((field, index) => (
                  <div key={field.id} className="flex gap-4 items-start bg-gray-50 p-4 rounded-lg">
                    <div className="flex-1 grid grid-cols-1 sm:grid-cols-12 gap-4">
                      {/* Brand - 15% width */}
                      <div className="sm:col-span-2">
                        <label className="label">Marque</label>
                        <select
                          className="input"
                          {...register(`orderItems.${index}.brand` as const)}
                          onChange={(e) => {
                            setValue(`orderItems.${index}.brand`, e.target.value as Brand);
                            setValue(`orderItems.${index}.productCode`, '');
                            setValue(`orderItems.${index}.productName`, '');
                            setValue(`orderItems.${index}.priceHT`, 0);
                            setValue(`orderItems.${index}.unitPrice`, 0);
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
                            `${product.name} - ${(product.priceHT * 1.2).toFixed(2)} DH TTC`
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
                    
                    <button
                      type="button"
                      className="mt-8 p-2 text-gray-500 hover:text-danger-500"
                      onClick={() => remove(index)}
                    >
                      <Minus className="h-5 w-5" />
                    </button>
                  </div>
                ))}
              </div>
              
              <button
                type="button"
                className="btn btn-outline flex items-center gap-2 w-full justify-center"
                onClick={() => append({ brand: 'D-WHITE', productCode: '', productName: '', quantity: 1, priceHT: 0, unitPrice: 0 })}
              >
                <Plus className="h-4 w-4" />
                Ajouter un produit
              </button>

              {/* Payment Method */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
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

                {paymentMethod === 'Virement' && (
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
              
              {/* Order Summary */}
              <div className="pt-4 border-t border-gray-200 bg-gray-50 p-4 rounded-lg">
                <div className="text-right">
                  <p className="text-sm text-gray-500">Total HT</p>
                  <p className="text-xl font-bold text-gray-900">{totalHT.toFixed(2)} DH</p>
                  <p className="text-sm text-gray-500">TVA (20%)</p>
                  <p className="text-lg font-semibold text-gray-700">{(total - totalHT).toFixed(2)} DH</p>
                  <p className="text-sm text-gray-500">Total TTC</p>
                  <p className="text-2xl font-bold text-primary-600">{total.toFixed(2)} DH</p>
                  <p className="text-sm text-gray-500">
                    {shippingCost === 0 ? (
                      <span className="text-success-600 font-medium">Livraison gratuite</span>
                    ) : (
                      `Livraison: ${shippingCost} DH TTC (${selectedCity})`
                    )}
                  </p>
                </div>
              </div>
            </div>
          )}
          
          {/* From Ads */}
          <div className="flex items-start">
            <div className="flex items-center h-5">
              <input
                id="fromAds"
                type="checkbox"
                className="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                {...register('fromAds')}
              />
            </div>
            <div className="ml-3 text-sm">
              <label htmlFor="fromAds" className="font-medium text-gray-700">
                Provenant d'une publicité
              </label>
              <p className="text-gray-500">Cochez si le contact provient d'une campagne publicitaire.</p>
            </div>
          </div>
          
          <div className="flex justify-end pt-4 border-t border-gray-200 mt-6">
            <button
              type="button"
              className="btn btn-outline mr-4"
              onClick={() => {
                if (isFromCallCenter) {
                  navigate('/dashboard/call-center');
                } else {
                  navigate('/dashboard/clients');
                }
              }}
            >
              Annuler
            </button>
            <button
              type="submit"
              className="btn btn-primary min-w-[200px]"
              disabled={isSubmitting}
            >
              {isSubmitting ? (
                <span className="inline-flex items-center">
                  <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {isEditing ? 'Modification...' : 'Enregistrement...'}
                </span>
              ) : (
                isEditing && showOrderForm ? (
                  hasExistingOrder ? 
                  'Mettre à jour le contact' : 
                  'Convertir en commande'
                ) : (
                  isEditing ? 'Modifier' : 'Enregistrer'
                )
              )}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ContactForm;