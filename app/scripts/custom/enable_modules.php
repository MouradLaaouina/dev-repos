<?php

$dolibarr_main_document_root = '/var/www/html';

require_once $dolibarr_main_document_root . '/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

echo "=== Activation de tous les modules Dolibarr ===\n";

$modules = array(
    // Modules de base
    'modSociete',           // Tiers/Clients/Fournisseurs
    'modProduit',           // Produits/Services
    'modService',           // Services
    'modContrat',           // Contrats
    'modFacture',           // Factures clients
    'modCommande',          // Commandes clients
    'modPropal',            // Propositions commerciales
    'modFournisseur',       // Fournisseurs
    'modSupplierProposal',  // Propositions fournisseurs
    'modSupplierOrder',     // Commandes fournisseurs
    'modSupplierInvoice',   // Factures fournisseurs
    
    // Comptabilité et finance
    'modComptabilite',      // Comptabilité
    'modAccounting',        // Comptabilité avancée
    'modBanque',            // Banques/Caisses
    'modPrelevement',       // Prélèvements
    'modCheque',            // Chèques
    'modTax',               // Taxes/TVA
    'modExpenseReport',     // Notes de frais
    'modSalaries',          // Salaires
    'modLoan',              // Emprunts
    'modDon',               // Dons
    
    // Projets et temps
    'modProjet',            // Projets
    'modFicheinter',        // Interventions
    'modTicket',            // Tickets/Support
    'modAgenda',            // Agenda
    
    // Stock et logistique
    'modStock',             // Stocks
    'modExpedition',        // Expéditions
    'modReception',         // Réceptions
    'modProductBatch',      // Lots/Numéros de série
    'modMrp',               // MRP/Nomenclature
    
    // Ressources humaines
    'modUser',              // Utilisateurs
    'modAdherent',          // Adhérents
    'modHoliday',           // Congés/Absences
    
    // Communication et marketing
    'modMailing',           // Mailings
    'modEmailCollector',    // Collecteur d'emails
    'modWebsite',           // Sites web
    'modBlockedLog',        // Logs inaltérables
    
    // Outils
    'modExport',            // Exports
    'modImport',            // Imports
    'modBarcode',           // Codes-barres
    'modCategory',          // Catégories/Tags
    'modClickToDial',       // Click to dial
    'modECM',               // GED/ECM
    'modBookmark',          // Signets
    'modNotification',      // Notifications
    'modFckeditor',         // Éditeur WYSIWYG
    'modNumberWords',       // Nombres en lettres
    'modApi',               // API REST
    'modWebServices',       // Web Services SOAP
    'modOAuth',             // OAuth
    'modDebugBar',          // Barre de debug
    
    // Commerce
    'modStripe',            // Stripe
    'modPaypal',            // PayPal
    'modPaybox',            // Paybox
    
    // Autres modules
    'modResource',          // Ressources
    'modAsset',             // Actifs
    'modRecruitment',       // Recrutement
    'modEventOrganization', // Organisation d'événements
    'modKnowledgeManagement', // Gestion des connaissances
    'modPartnership',       // Partenariats
);

$activated = 0;
$already_active = 0;
$errors = 0;

foreach ($modules as $module) {
    try {
        // Vérifier si le module existe
        $file = DOL_DOCUMENT_ROOT . '/core/modules/' . strtolower($module) . '.class.php';
        $file_alt = DOL_DOCUMENT_ROOT . '/core/modules/modails/' . strtolower($module) . '.class.php';
        
        if (!file_exists($file) && !file_exists($file_alt)) {
            echo "⚠ Module $module non trouvé\n";
            continue;
        }
        
        // Activer le module
        $result = activateModule($module);
        
        if ($result > 0) {
            echo "✓ Module $module activé\n";
            $activated++;
        } elseif ($result == 0) {
            echo "ℹ Module $module déjà actif\n";
            $already_active++;
        } else {
            echo "✗ Erreur lors de l'activation du module $module\n";
            $errors++;
        }
        
    } catch (Exception $e) {
        echo "✗ Exception pour le module $module : " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== Résumé ===\n";
echo "Modules activés : $activated\n";
echo "Modules déjà actifs : $already_active\n";
echo "Erreurs : $errors\n";
echo "Total traité : " . count($modules) . "\n";

// Vider le cache
if (function_exists('clearstatcache')) {
    clearstatcache();
}

echo "\n✓ Script terminé avec succès\n";

?>