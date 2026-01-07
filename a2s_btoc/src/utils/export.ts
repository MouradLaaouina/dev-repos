import { useAuthStore } from '../store/authStore';
import { useOrderStore } from '../store/orderStore';
import { Order } from '../types';
import * as XLSX from 'xlsx';

export const exportOrders = async (orders: Order[], startDate?: Date, endDate?: Date): Promise<{success: boolean, message: string}> => {
  const currentUser = useAuthStore.getState().user;
  const userId = currentUser?.id;
  const userCodeAgence = currentUser?.codeAgence || '';

  console.log('📊 Starting export with', orders.length, 'orders');
  
  const filteredOrders = orders.filter(order => {
    if (!startDate || !endDate) return true;
    const orderDate = new Date(order.createdAt);
    return orderDate >= startDate && orderDate <= endDate;
  });

  console.log('📊 After date filtering:', filteredOrders.length, 'orders');
  
  if (filteredOrders.length === 0) {
    return { success: false, message: 'Aucune commande à exporter après filtrage' };
  }
  
  const excelData: any[] = [];
  const clientsData: any[] = [];

  filteredOrders.forEach(order => {
    const clientCode = order.clientCode || '';
    const finalCodeAgence = order.codeAgence || userCodeAgence || '';
    const orderDate = new Date(order.createdAt).toLocaleDateString('fr-FR');

    let totalTTC = 0;
    let totalHT = 0;

    const validItems = (order.items || []).filter(item =>
      item.productName &&
      item.productName.toLowerCase() !== 'product' &&
      item.productCode &&
      item.productCode.trim() !== ''
    );

    validItems.forEach(item => {
      const itemQuantity = item.quantity ?? 0;
      const unitPrice = item.unitPrice ?? 0;
      const priceHT = item.priceHT ?? 0;

      totalTTC += unitPrice * itemQuantity;
      totalHT += priceHT * itemQuantity;
    });

    if (order.shippingCost > 0) {
      totalTTC += order.shippingCost;
      totalHT += order.shippingCost / 1.2;
    }

    // Add valid product rows
    validItems.forEach(item => {
      const itemQuantity = item.quantity ?? 0;
      const unitPrice = item.unitPrice ?? 0;

      excelData.push({
        'NR DE BON': order.orderNumber,
        'DATE': orderDate,
        'CODE_DU_CLIENT': clientCode,
        'MONTANT_TTC': totalTTC.toFixed(2),
        'MONTANT_HT': totalHT.toFixed(2),
        'VALIDE': '.T.',
        'COMMERCIAL': order.agentCode || '',
        'CODE AGENCE': finalCodeAgence,
        'CODE ARTICLE': item.productCode,
        'LIBELLE': item.productName,
        'QUANTITE': itemQuantity,
        'PRIX VENTE': unitPrice,
        'REMISE': 0,
        'TVA': 20
      });
    });

    // Add shipping row (TRS-20 or TRS-35)
    if (order.shippingCost > 0) {
      const shippingCode = order.shippingCost === 20 ? 'TRS-20' : 'TRS-35';

      excelData.push({
        'NR DE BON': order.orderNumber,
        'DATE': orderDate,
        'CODE_DU_CLIENT': clientCode,
        'MONTANT_TTC': totalTTC.toFixed(2),
        'MONTANT_HT': totalHT.toFixed(2),
        'VALIDE': '.T.',
        'COMMERCIAL': order.agentCode || '',
        'CODE AGENCE': finalCodeAgence,
        'CODE ARTICLE': shippingCode,
        'LIBELLE': 'FRAIS DE PORT',
        'QUANTITE': 1,
        'PRIX VENTE': order.shippingCost,
        'REMISE': 0,
        'TVA': 20
      });
    }

    // Add unique client data (no CODE AGENCE in clients export)
    if (!clientsData.find(c => c['CODE DU CLIENT'] === clientCode)) {
      clientsData.push({
        'CODE DU CLIENT': clientCode,
        'NOM COMPLET': order.nom || '',
        'TELEPHONE': order.telephone || '',
        'VILLE': order.ville || '',
        'ADRESSE': order.address || '',
        'CODE COMMERCIAL': order.agentCode || ''
      });
    }
  });

  // Export commandes
  const ordersSheet = XLSX.utils.json_to_sheet(excelData);
  const ordersWorkbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(ordersWorkbook, ordersSheet, 'Commandes');
  const date = new Date().toISOString().slice(0, 10);
  XLSX.writeFile(ordersWorkbook, `commandes-${date}.xlsx`);

  // Export clients
  const clientsSheet = XLSX.utils.json_to_sheet(clientsData);
  const clientsWorkbook = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(clientsWorkbook, clientsSheet, 'Clients');
  XLSX.writeFile(clientsWorkbook, `clients-${date}.xlsx`);
  
  console.log('✅ Excel files created successfully');

  // We no longer update exported status in Supabase as it's being removed.
  // Ideally this should be updated in Dolibarr via an extrafield.
  
  return { success: true, message: `${filteredOrders.length} commandes exportées avec succès` };
};
