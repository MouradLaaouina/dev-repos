<?php
#!/usr/bin/env php

/**
 * Script CLI pour créer des clients fictifs avec différents niveaux de configuration.
 *
 * Usage :
 *   php create_fictive_clients.php
 *
 * Ce script crée des clients fictifs (B2B et B2C) avec:
 *   - Différents modes de paiement
 *   - Différentes conditions de paiement
 *   - Différents niveaux de créance autorisée
 *   - Différents remises commerciales
 *   - Différents types (particulier, pharmacie, clinique, hôpital, etc.)
 */

if (!defined('NOSESSION')) {
    define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit(1);
}

require_once '/var/www/html/master.inc.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

/** @var DoliDB $db */
/** @var Conf $conf */

global $db, $conf, $langs, $user;

$langs->load("companies");

// Récupérer l'utilisateur admin
$user = new User($db);
$defaultUserId = !empty($conf->global->MAIN_IMPORT_DEFAULT_USER) ? (int) $conf->global->MAIN_IMPORT_DEFAULT_USER : 1;
if ($user->fetch($defaultUserId) <= 0) {
    fwrite(STDERR, "Impossible de récupérer l'utilisateur ID $defaultUserId\n");
    exit(1);
}

/* Définition des clients fictifs */
$clients = [
    // === PHARMACIES (B2B) ===
    [
        'type' => 'B2B',
        'name' => 'Pharmacie Al Amal',
        'name_alias' => 'Pharmacie de l\'Espoir',
        'code_client' => 'CL-PHAR-001',
        'address' => 'Boulevard Mohamed V',
        'zip' => '20000',
        'town' => 'Casablanca',
        'country_id' => 12, // Maroc
        'phone' => '+212 522 234 567',
        'email' => 'contact@pharmacie-alamal.ma',
        'client' => 1, // Client
        'fournisseur' => 0,
        'tva_intra' => 'MA123456789',
        'forme_juridique_code' => 200, // SARL
        'remise_percent' => 5.00,
        'mode_reglement_id' => 2, // Virement
        'cond_reglement_id' => 2, // 30 jours
        'outstanding_limit' => 50000.00, // Créance autorisée: 50 000 DH
        'contact' => [
            'firstname' => 'Dr. Fatima',
            'lastname' => 'Bennani',
            'email' => 'f.bennani@pharmacie-alamal.ma',
            'phone_pro' => '+212 522 234 567',
            'poste' => 'Pharmacienne responsable',
        ],
    ],
    [
        'type' => 'B2B',
        'name' => 'Pharmacie Centrale Rabat',
        'name_alias' => 'Grande Pharmacie Centrale',
        'code_client' => 'CL-PHAR-002',
        'address' => 'Avenue Hassan II',
        'zip' => '10000',
        'town' => 'Rabat',
        'country_id' => 12,
        'phone' => '+212 537 123 456',
        'email' => 'info@pharmarabat.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA234567890',
        'forme_juridique_code' => 200,
        'remise_percent' => 8.00,
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 3, // 45 jours
        'outstanding_limit' => 100000.00, // Créance autorisée: 100 000 DH
        'contact' => [
            'firstname' => 'Dr. Ahmed',
            'lastname' => 'Alaoui',
            'email' => 'a.alaoui@pharmarabat.ma',
            'phone_pro' => '+212 537 123 456',
            'poste' => 'Directeur',
        ],
    ],
    [
        'type' => 'B2B',
        'name' => 'Pharmacie du Nord',
        'name_alias' => 'Pharmacie Tanger',
        'code_client' => 'CL-PHAR-003',
        'address' => 'Rue de la Liberté',
        'zip' => '90000',
        'town' => 'Tanger',
        'country_id' => 12,
        'phone' => '+212 539 345 678',
        'email' => 'contact@pharmanord.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA345678901',
        'forme_juridique_code' => 200,
        'remise_percent' => 3.50,
        'mode_reglement_id' => 3, // Chèque
        'cond_reglement_id' => 2,
        'outstanding_limit' => 30000.00,
        'contact' => [
            'firstname' => 'Dr. Samira',
            'lastname' => 'Tazi',
            'email' => 's.tazi@pharmanord.ma',
            'phone_pro' => '+212 539 345 678',
            'poste' => 'Pharmacienne',
        ],
    ],

    // === CLINIQUES / CENTRES MÉDICAUX (B2B) ===
    [
        'type' => 'B2B',
        'name' => 'Clinique Al Amal Casablanca',
        'name_alias' => 'Clinique de l\'Espoir',
        'code_client' => 'CL-CLIN-010',
        'address' => 'Boulevard Zerktouni',
        'zip' => '20100',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 789 012',
        'email' => 'administration@clinique-alamal.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA456789012',
        'forme_juridique_code' => 300, // SA
        'remise_percent' => 12.00,
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 4, // 60 jours
        'outstanding_limit' => 300000.00, // Créance autorisée: 300 000 DH
        'contact' => [
            'firstname' => 'Dr. Karim',
            'lastname' => 'Moussaoui',
            'email' => 'k.moussaoui@clinique-alamal.ma',
            'phone_pro' => '+212 522 789 012',
            'poste' => 'Directeur Médical',
        ],
    ],
    [
        'type' => 'B2B',
        'name' => 'Centre Médical Agdal',
        'name_alias' => 'Centre Santé Agdal',
        'code_client' => 'CL-CLIN-011',
        'address' => 'Avenue Mehdi Ben Barka',
        'zip' => '10080',
        'town' => 'Rabat',
        'country_id' => 12,
        'phone' => '+212 537 654 321',
        'email' => 'contact@centre-agdal.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA567890123',
        'forme_juridique_code' => 200,
        'remise_percent' => 10.00,
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 3,
        'outstanding_limit' => 150000.00,
        'contact' => [
            'firstname' => 'Dr. Leila',
            'lastname' => 'Bensaid',
            'email' => 'l.bensaid@centre-agdal.ma',
            'phone_pro' => '+212 537 654 321',
            'poste' => 'Responsable Achats',
        ],
    ],

    // === HÔPITAUX (B2B) - Gros volumes ===
    [
        'type' => 'B2B',
        'name' => 'CHU Ibn Rochd Casablanca',
        'name_alias' => 'Hôpital Universitaire Ibn Rochd',
        'code_client' => 'CL-HOP-020',
        'address' => 'Rue des Hôpitaux',
        'zip' => '20100',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 480 000',
        'email' => 'approvisionnement@chu-ibnrochd.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA678901234',
        'forme_juridique_code' => 1000, // Administration publique
        'remise_percent' => 18.00, // Grosse remise pour gros volumes
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 5, // 90 jours
        'outstanding_limit' => 1000000.00, // 1 million DH
        'contact' => [
            'firstname' => 'M. Hassan',
            'lastname' => 'Idrissi',
            'email' => 'h.idrissi@chu-ibnrochd.ma',
            'phone_pro' => '+212 522 480 100',
            'poste' => 'Chef Service Approvisionnement',
        ],
    ],
    [
        'type' => 'B2B',
        'name' => 'Hôpital Cheikh Khalifa Casablanca',
        'name_alias' => 'Hôpital CK',
        'code_client' => 'CL-HOP-021',
        'address' => 'Boulevard de la Corniche',
        'zip' => '20180',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 977 777',
        'email' => 'achats@hopital-ck.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA789012345',
        'forme_juridique_code' => 200,
        'remise_percent' => 15.00,
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 4,
        'outstanding_limit' => 800000.00,
        'contact' => [
            'firstname' => 'Mme Nadia',
            'lastname' => 'Chakiri',
            'email' => 'n.chakiri@hopital-ck.ma',
            'phone_pro' => '+212 522 977 888',
            'poste' => 'Responsable Achats Médicaux',
        ],
    ],

    // === DISTRIBUTEURS (B2B) ===
    [
        'type' => 'B2B',
        'name' => 'Distri Pharma Maroc',
        'name_alias' => 'DPM Distribution',
        'code_client' => 'CL-DIST-030',
        'address' => 'Zone Industrielle Aïn Sebaâ',
        'zip' => '20250',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 600 700',
        'email' => 'commercial@distripharma.ma',
        'client' => 1,
        'fournisseur' => 1, // Aussi fournisseur
        'tva_intra' => 'MA890123456',
        'forme_juridique_code' => 300,
        'remise_percent' => 20.00, // Grosse remise distributeur
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 3,
        'outstanding_limit' => 500000.00,
        'contact' => [
            'firstname' => 'M. Youssef',
            'lastname' => 'El Amrani',
            'email' => 'y.elamrani@distripharma.ma',
            'phone_pro' => '+212 522 600 701',
            'poste' => 'Directeur Commercial',
        ],
    ],

    // === CLIENTS PARTICULIERS (B2C) ===
    [
        'type' => 'B2C',
        'name' => 'El Fassi Amina',
        'name_alias' => 'Cliente VIP',
        'code_client' => 'CL-PART-100',
        'address' => 'Résidence Anfa, Apt 45',
        'zip' => '20100',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 661 234 567',
        'email' => 'amina.elfassi@gmail.com',
        'client' => 1,
        'fournisseur' => 0,
        'particulier' => 1,
        'remise_percent' => 0.00,
        'mode_reglement_id' => 4, // Espèces
        'cond_reglement_id' => 1, // Paiement immédiat
        'outstanding_limit' => 0.00, // Pas de créance pour particuliers
    ],
    [
        'type' => 'B2C',
        'name' => 'Benkirane Mohammed',
        'name_alias' => 'Client régulier',
        'code_client' => 'CL-PART-101',
        'address' => '123 Rue Allal Ben Abdellah',
        'zip' => '10000',
        'town' => 'Rabat',
        'country_id' => 12,
        'phone' => '+212 662 345 678',
        'email' => 'mohammed.benkirane@outlook.com',
        'client' => 1,
        'fournisseur' => 0,
        'particulier' => 1,
        'remise_percent' => 0.00,
        'mode_reglement_id' => 6, // Carte bancaire
        'cond_reglement_id' => 1,
        'outstanding_limit' => 0.00,
    ],
    [
        'type' => 'B2C',
        'name' => 'Tazi Samira',
        'name_alias' => 'Cliente fidèle',
        'code_client' => 'CL-PART-102',
        'address' => '67 Boulevard Mohammed VI',
        'zip' => '90000',
        'town' => 'Tanger',
        'country_id' => 12,
        'phone' => '+212 663 456 789',
        'email' => 'samira.tazi@yahoo.fr',
        'client' => 1,
        'fournisseur' => 0,
        'particulier' => 1,
        'remise_percent' => 2.00, // Petite remise fidélité
        'mode_reglement_id' => 6,
        'cond_reglement_id' => 1,
        'outstanding_limit' => 0.00,
    ],

    // === PETITES ENTREPRISES ===
    [
        'type' => 'B2B',
        'name' => 'Cabinet Dentaire Dr. Benjelloun',
        'name_alias' => 'Dentiste Benjelloun',
        'code_client' => 'CL-CAB-200',
        'address' => 'Résidence Les Jardins',
        'zip' => '20100',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 987 654',
        'email' => 'cabinet@dr-benjelloun.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA901234567',
        'forme_juridique_code' => 100, // Entreprise individuelle
        'remise_percent' => 5.00,
        'mode_reglement_id' => 3,
        'cond_reglement_id' => 2,
        'outstanding_limit' => 20000.00,
        'contact' => [
            'firstname' => 'Dr. Nabil',
            'lastname' => 'Benjelloun',
            'email' => 'n.benjelloun@dr-benjelloun.ma',
            'phone_pro' => '+212 522 987 654',
            'poste' => 'Chirurgien-Dentiste',
        ],
    ],
    [
        'type' => 'B2B',
        'name' => 'Laboratoire d\'Analyses Bio Santé',
        'name_alias' => 'Labo Bio Santé',
        'code_client' => 'CL-LAB-201',
        'address' => 'Avenue des FAR',
        'zip' => '20000',
        'town' => 'Casablanca',
        'country_id' => 12,
        'phone' => '+212 522 765 432',
        'email' => 'contact@biosante.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA012345678',
        'forme_juridique_code' => 200,
        'remise_percent' => 7.00,
        'mode_reglement_id' => 2,
        'cond_reglement_id' => 2,
        'outstanding_limit' => 40000.00,
        'contact' => [
            'firstname' => 'Dr. Zineb',
            'lastname' => 'Lahlou',
            'email' => 'z.lahlou@biosante.ma',
            'phone_pro' => '+212 522 765 433',
            'poste' => 'Biologiste Responsable',
        ],
    ],

    // === CLIENT AVEC CRÉANCE DÉPASSÉE (pour tests) ===
    [
        'type' => 'B2B',
        'name' => 'Pharmacie du Sud',
        'name_alias' => 'Pharma Sud (impayés)',
        'code_client' => 'CL-PHAR-999',
        'address' => 'Avenue Hassan II',
        'zip' => '80000',
        'town' => 'Agadir',
        'country_id' => 12,
        'phone' => '+212 528 123 456',
        'email' => 'contact@pharmasud.ma',
        'client' => 1,
        'fournisseur' => 0,
        'tva_intra' => 'MA123450987',
        'forme_juridique_code' => 200,
        'remise_percent' => 3.00,
        'mode_reglement_id' => 3,
        'cond_reglement_id' => 2,
        'outstanding_limit' => 10000.00, // Créance faible car retards de paiement
        'contact' => [
            'firstname' => 'Dr. Omar',
            'lastname' => 'Ziani',
            'email' => 'o.ziani@pharmasud.ma',
            'phone_pro' => '+212 528 123 456',
            'poste' => 'Pharmacien',
        ],
    ],
];

/* Utils */
function fatal($m) { fwrite(STDERR,"[ERROR] $m\n"); exit(1); }
function info($m) { echo "[INFO] $m\n"; }
function done($m) { echo "[OK]   $m\n"; }

/* Compteurs */
$totalCreated = 0;
$totalSkipped = 0;
$totalErrors = 0;
$totalContactsCreated = 0;

echo "=================================================================\n";
echo "Création des clients fictifs\n";
echo "=================================================================\n\n";

foreach ($clients as $clientData) {
    $code = $clientData['code_client'];
    $name = $clientData['name'];

    // Vérifier si le client existe déjà
    $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe WHERE code_client = '" . $db->escape($code) . "' LIMIT 1";
    $resql = $db->query($sql);

    if ($resql) {
        $obj = $db->fetch_object($resql);
        if ($obj) {
            info("Client '$name' ($code) existe déjà (ID: $obj->rowid), ignoré");
            $totalSkipped++;
            continue;
        }
    }

    $db->begin();

    // Créer le client
    $societe = new Societe($db);
    $societe->name = $clientData['name'];
    $societe->name_alias = $clientData['name_alias'] ?? '';
    $societe->code_client = $clientData['code_client'];
    $societe->address = $clientData['address'];
    $societe->zip = $clientData['zip'];
    $societe->town = $clientData['town'];
    $societe->country_id = $clientData['country_id'];
    $societe->phone = $clientData['phone'];
    $societe->email = $clientData['email'];
    $societe->client = $clientData['client'];
    $societe->fournisseur = $clientData['fournisseur'] ?? 0;

    if (isset($clientData['particulier']) && $clientData['particulier'] == 1) {
        $societe->typent_id = 8; // Particulier
    } elseif (isset($clientData['forme_juridique_code'])) {
        $societe->forme_juridique_code = $clientData['forme_juridique_code'];
    }

    if (isset($clientData['tva_intra'])) {
        $societe->tva_intra = $clientData['tva_intra'];
    }

    $societe->remise_percent = $clientData['remise_percent'] ?? 0;
    $societe->mode_reglement_id = $clientData['mode_reglement_id'] ?? 0;
    $societe->cond_reglement_id = $clientData['cond_reglement_id'] ?? 0;
    $societe->outstanding_limit = $clientData['outstanding_limit'] ?? 0;

    $result = $societe->create($user);

    if ($result > 0) {
        done("Client créé: '$name' ($code) - ID: $result");
        $totalCreated++;

        // Créer le contact si spécifié (pour B2B)
        if (isset($clientData['contact']) && !empty($clientData['contact'])) {
            $contactData = $clientData['contact'];

            $contact = new Contact($db);
            $contact->socid = $societe->id;
            $contact->lastname = $contactData['lastname'];
            $contact->firstname = $contactData['firstname'];
            $contact->email = $contactData['email'];
            $contact->phone_pro = $contactData['phone_pro'];
            $contact->poste = $contactData['poste'];
            $contact->statut = 1; // Actif

            $contactResult = $contact->create($user);

            if ($contactResult > 0) {
                info("  + Contact créé: {$contactData['firstname']} {$contactData['lastname']}");
                $totalContactsCreated++;
            } else {
                fwrite(STDERR, "  ! Erreur création contact pour client ID: $result\n");
            }
        }

        $db->commit();
    } else {
        $db->rollback();
        $errorMsg = $societe->error ?: 'Erreur inconnue';
        if (!empty($societe->errors)) {
            $errorMsg .= ' | ' . implode(' ; ', $societe->errors);
        }
        fwrite(STDERR, "[ERROR] Échec création client '$name' ($code): $errorMsg\n");
        $totalErrors++;
    }
}

echo "\n=================================================================\n";
echo "RÉSUMÉ:\n";
echo "  - Clients créés: $totalCreated\n";
echo "  - Contacts créés: $totalContactsCreated\n";
echo "  - Clients existants (ignorés): $totalSkipped\n";
echo "  - Erreurs: $totalErrors\n";
echo "=================================================================\n";

if ($totalCreated > 0) {
    echo "\n✓ Les clients fictifs ont été créés avec succès.\n";
    echo "\nRécapitulatif des configurations:\n";
    echo "  - B2B: Pharmacies, Cliniques, Hôpitaux, Distributeurs, Cabinets\n";
    echo "  - B2C: Particuliers (clients finaux)\n";
    echo "  - Créances autorisées: de 0 à 1 000 000 DH selon le type\n";
    echo "  - Remises: de 0% à 20% selon le type et volume\n";
    echo "  - Délais de paiement: de immédiat à 90 jours\n";
    echo "  - Modes de paiement: Virement, Chèque, Espèces, Carte bancaire\n";
}

exit(0);
