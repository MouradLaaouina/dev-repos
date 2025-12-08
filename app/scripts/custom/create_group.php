<?php

#!/usr/bin/env php

// Exécutable en CLI sans session, sans HTML
define('NOLOGIN', 1);
define('NOREQUIREMENU', 1);
define('NOREQUIREHTML', 1);
define('NOTOKENRENEWAL', 1);

require_once '/var/www/htdocs/conf/conf.php';

$dbPrefix = "llx_";     // vérifiez dans htdocs/conf/conf.php -> $dolibarr_main_db_prefix
$entity   = 1;          // multi-sociétés: 1 par défaut
$dryRun   = false;      // true = n'applique rien, affiche uniquement

/* ===================== GROUPES & MODULES ===================== */
/**
 * Groupes à créer (clé technique => libellé affiché)
 */
$groups = [
    'dermo_conseillere_b2b' => 'dermo_conseillere_b2b',
    'tele_conseillere_b2c'  => 'tele_conseillere_b2c',
    'responsable'           => 'responsable',
    'direction'             => 'direction'
];

/**
 * Modules à attribuer par groupe (liste des modules internes tels que présents dans llx_rights_def.module)
 * Vous pouvez lister les modules dispo avec la requête:
 *   SELECT DISTINCT module FROM llx_rights_def ORDER BY module;
 *
 * NB: pour donner "tous les droits" -> utilisez ['*'].
 */
$moduleSets = [
    'dermo_conseillere_b2b' => ['societe','propal','commande','facture','product','agenda'],
    'tele_conseillere_b2c'  => ['societe','commande','facture','product','ticket','agenda'],
    'responsable'           => ['societe','propal','commande','facture','product','agenda','categorie','resource'],
    'direction'             => ['*']  // Tous les droits de tous les modules
];

/**
 * Filtrage “droits sensibles admin”
 * - Par défaut on EXCLUT les droits admin pour tout le monde SAUF 'direction'
 * - Le critère ci-dessous exclut:
 *    - module 'user' (gestion comptes/permissions)
 *    - libellé contenant 'admin' ou 'setup' (heuristique)
 * Ajustez selon vos besoins.
 */
function sqlAdminExclusionClause($includeAdmin, $tPrefix) {
    if ($includeAdmin) return ""; // aucun filtre
    // Heuristique courante pour éviter les droits d'admin/config
    return " AND rd.module <> 'user' AND (rd.perms NOT LIKE 'admin%' AND rd.perms NOT LIKE 'setup%') ";
}

/* ===================== OUTILS ===================== */
function fatal($msg) { fwrite(STDERR, "[ERROR] $msg\n"); exit(1); }
function info($msg)  { echo "[INFO] $msg\n"; }
function done($msg)  { echo "[OK]   $msg\n";  }

/* ===================== START ===================== */
$mysqli = new mysqli($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name);
if ($mysqli->connect_errno) fatal("Connexion MySQL: ".$mysqli->connect_error);
$mysqli->set_charset("utf8mb4");

if (!$dryRun) {
    $mysqli->begin_transaction();
    info("Transaction démarrée");
} else {
    info("Mode DRY-RUN (aucune modification en base)");
}

/* Prépare: trouver/insérer un groupe */
$sqlFindGroup = "SELECT rowid FROM {$dbPrefix}usergroup WHERE entity=? AND nom=?";
$stmtFindGroup = $mysqli->prepare($sqlFindGroup) ?: fatal($mysqli->error);

$sqlInsertGroup = "INSERT INTO {$dbPrefix}usergroup (entity, nom, note, datec, tms) VALUES (?, ?, '', NOW(), NOW())";
$stmtInsertGroup = $mysqli->prepare($sqlInsertGroup) ?: fatal($mysqli->error);

/* Prépare: lier un droit à un groupe (éviter doublons) */
$sqlCheckRight = "SELECT 1 FROM {$dbPrefix}usergroup_rights WHERE entity=? AND fk_usergroup=? AND fk_id=?";
$stmtCheckRight = $mysqli->prepare($sqlCheckRight) ?: fatal($mysqli->error);

$sqlInsertRight = "INSERT INTO {$dbPrefix}usergroup_rights (entity, fk_usergroup, fk_id) VALUES (?, ?, ?)";
$stmtInsertRight = $mysqli->prepare($sqlInsertRight) ?: fatal($mysqli->error);

/* Itération groupes */
$totalGroupsCreated = 0;
$totalRightsLinked   = 0;

foreach ($groups as $groupKey => $groupLabel) {
    $modules = $moduleSets[$groupKey] ?? [];
    if (empty($modules)) {
        info("Groupe '$groupKey' n'a pas de modules configurés — ignoré.");
        continue;
    }

    // 1) Trouver ou créer le groupe
    $groupId = null;
    $stmtFindGroup->bind_param("is", $entity, $groupLabel);
    $stmtFindGroup->execute();
    $stmtFindGroup->bind_result($foundId);
    if ($stmtFindGroup->fetch()) {
        $groupId = (int)$foundId;
        info("Groupe existant: '$groupLabel' (ID=$groupId)");
    }
    $stmtFindGroup->free_result();

    if ($groupId === null) {
        info("Création du groupe: '$groupLabel'");
        if ($dryRun) {
            $groupId = -1; // placeholder
        } else {
            $stmtInsertGroup->bind_param("is", $entity, $groupLabel);
            if (!$stmtInsertGroup->execute()) {
                if (!$dryRun) $mysqli->rollback();
                fatal("Insertion groupe '$groupLabel' échouée: ".$stmtInsertGroup->error);
            }
            $groupId = $stmtInsertGroup->insert_id;
            $totalGroupsCreated++;
            done("Groupe créé: '$groupLabel' (ID=$groupId)");
        }
    }

    // 2) Récupérer la liste des droits depuis llx_rights_def
    $includeAdmin = ($groupKey === 'direction');
    $adminExcl = sqlAdminExclusionClause($includeAdmin, $dbPrefix);

    if ($modules === ['*']) {
        $sqlRights = "SELECT rd.id, rd.module, rd.perms, rd.subperms
                      FROM {$dbPrefix}rights_def rd
                      WHERE 1=1 $adminExcl
                      ORDER BY rd.module, rd.perms, rd.subperms";
        $res = $mysqli->query($sqlRights);
    } else {
        // Construction sécurisée de la clause IN
        $placeholders = implode(',', array_fill(0, count($modules), '?'));
        $sqlRights = "SELECT rd.id, rd.module, rd.perms, rd.subperms
                      FROM {$dbPrefix}rights_def rd
                      WHERE rd.module IN ($placeholders) $adminExcl
                      ORDER BY rd.module, rd.perms, rd.subperms";
        $stmtRights = $mysqli->prepare($sqlRights) ?: fatal($mysqli->error);
        // bind dynamic params
        $types = str_repeat('s', count($modules));
        $stmtRights->bind_param($types, ...$modules);
        $stmtRights->execute();
        $res = $stmtRights->get_result();
    }

    if (!$res) {
        if (!$dryRun) $mysqli->rollback();
        fatal("Lecture des droits échouée (groupe '$groupLabel'): ".$mysqli->error);
    }

    $rightsCount = 0; $linkedForGroup = 0;
    while ($row = $res->fetch_assoc()) {
        $rightsCount++;
        $rightId = (int)$row['id'];
        $desc    = $row['module'] . ':' . $row['perms'] . (strlen($row['subperms']) ? ':'.$row['subperms'] : '');

        // 3) Lier le droit au groupe si pas déjà présent
        if ($dryRun) {
            echo "  ~ Lier droit #$rightId ($desc) au groupe '$groupLabel'\n";
            $linkedForGroup++;
            continue;
        }

        $stmtCheckRight->bind_param("iii", $entity, $groupId, $rightId);
        $stmtCheckRight->execute();
        $stmtCheckRight->store_result();
        if ($stmtCheckRight->num_rows === 0) {
            $stmtInsertRight->bind_param("iii", $entity, $groupId, $rightId);
            if (!$stmtInsertRight->execute()) {
                if (!$dryRun) $mysqli->rollback();
                fatal("Insertion droit #$rightId pour groupe '$groupLabel' échouée: ".$stmtInsertRight->error);
            }
            $linkedForGroup++;
            echo "  + Droit lié: #$rightId ($desc)\n";
        } else {
            echo "  = Déjà présent: #$rightId ($desc)\n";
        }
        $stmtCheckRight->free_result();
    }
    $res->free();

    info("Groupe '$groupLabel': $linkedForGroup liens de droits (sur $rightsCount droits listés).");
    $totalRightsLinked += $linkedForGroup;
}

/* ===================== COMMIT ===================== */
if ($dryRun) {
    info("DRY-RUN terminé. Aucune modification écrite.");
} else {
    $mysqli->commit();
    done("Transaction validée.");
}

done("RÉSUMÉ: Groupes créés: $totalGroupsCreated — Droits liés: $totalRightsLinked");

$mysqli->close();

/* ===================== NOTES ===================== *
 * - Si un module n’attribue aucun droit, vérifiez son nom exact dans llx_rights_def.module
 *   Exemple rapide:
 *     SELECT DISTINCT module FROM {$dbPrefix}rights_def ORDER BY module;
 *
 * - Pour forcer tous les droits à un groupe, mettez ['*'] dans $moduleSets pour ce groupe.
 *
 * - Le filtre admin est heuristique pour éviter de donner des droits de configuration
 *   aux profils non admin. Ajustez la fonction sqlAdminExclusionClause() si nécessaire.
 *
 * - Si votre préfixe n’est pas 'llx_', adaptez $dbPrefix.
 *
 * - Pensez aux entités (multi-sociétés) : $entity doit correspondre à vos données.
 */
