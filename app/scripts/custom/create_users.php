<?php
#!/usr/bin/env php


if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

require_once '/var/www/htdocs/conf/conf.php';

$dbPrefix = "llx_";  
$entity   = 1;          // multi-sociétés: 1 par défaut
$dryRun   = false;

/* Groupes & modules */
$groups = [
    'dermo_conseillere_b2b' => 'dermo_conseillere_b2b',
    'tele_conseillere_b2c'  => 'tele_conseillere_b2c',
    'responsable'           => 'responsable',
    'direction'             => 'direction'
];
$moduleSets = [
    'dermo_conseillere_b2b' => ['societe','propal','commande','facture','product','agenda'],
    'tele_conseillere_b2c'  => ['societe','commande','facture','product','ticket','agenda'],
    'responsable'           => ['societe','propal','commande','facture','product','agenda','categorie','resource'],
    'direction'             => ['*']
];

/* Un utilisateur par groupe avec données RH complètes */
$usersByGroup = [
    'dermo_conseillere_b2b' => [
        'login'=>'marie.b2b',
        'firstname'=>'Marie',
        'lastname'=>'Durand',
        'email'=>'marie.b2b@example.com',
        'password'=>'test123',
        'employee'=>1,
        // RH Data
        'job'=>'Dermo-Conseillère B2B',
        'weeklyhours'=>40,
        'salary'=>45000,
        'thm'=>28.85,  // Tarif horaire moyen
        'tjm'=>230.77, // Tarif journalier moyen
        'dateemployment'=>'2023-01-15',
        'birth'=>'1990-05-20',
        'color'=>'FF6B9D',
        // Échelle salariale & identification
        'fk_salary_scale'=>1,
        'employee_number'=>'EMP001',
        'situation_familiale'=>'Mariée',
        'nbre_enfants'=>2,
        'num_cnss'=>'123456789',
        'num_cimr'=>'CIMR987654',
        'num_cin'=>'AB123456',
        'nombre_jours_travail'=>22,
        // Bancaire
        'bank_account'=>'FR7612345678901234567890123',
        'bank_name'=>'Banque Populaire',
        'bank_code'=>'12345',
        'bank_branch'=>'67890',
        'bank_iban'=>'FR7612345678901234567890123',
        'bank_bic'=>'CCBPFRPPXXX'
    ],
    'tele_conseillere_b2c'  => [
        'login'=>'sophie.b2c',
        'firstname'=>'Sophie',
        'lastname'=>'Martin',
        'email'=>'sophie.b2c@example.com',
        'password'=>'test123',
        'employee'=>1,
        // RH Data
        'job'=>'Télé-Conseillère B2C',
        'weeklyhours'=>35,
        'salary'=>38000,
        'thm'=>24.79,
        'tjm'=>198.32,
        'dateemployment'=>'2023-03-10',
        'birth'=>'1992-08-15',
        'color'=>'4ECDC4',
        // Échelle salariale & identification
        'fk_salary_scale'=>1,
        'employee_number'=>'EMP002',
        'situation_familiale'=>'Célibataire',
        'nbre_enfants'=>0,
        'num_cnss'=>'223456780',
        'num_cimr'=>'CIMR876543',
        'num_cin'=>'CD234567',
        'nombre_jours_travail'=>22,
        // Bancaire
        'bank_account'=>'FR7698765432109876543210987',
        'bank_name'=>'Crédit Agricole',
        'bank_code'=>'98765',
        'bank_branch'=>'43210',
        'bank_iban'=>'FR7698765432109876543210987',
        'bank_bic'=>'AGRIFRPPXXX'
    ],
    'responsable'           => [
        'login'=>'pierre.manager',
        'firstname'=>'Pierre',
        'lastname'=>'Dupont',
        'email'=>'pierre.manager@example.com',
        'password'=>'test123',
        'employee'=>1,
        // RH Data
        'job'=>'Responsable Commercial',
        'weeklyhours'=>40,
        'salary'=>65000,
        'thm'=>41.67,
        'tjm'=>333.33,
        'dateemployment'=>'2021-06-01',
        'birth'=>'1985-03-12',
        'color'=>'F7B731',
        // Échelle salariale & identification
        'fk_salary_scale'=>2,
        'employee_number'=>'EMP003',
        'situation_familiale'=>'Marié',
        'nbre_enfants'=>3,
        'num_cnss'=>'323456781',
        'num_cimr'=>'CIMR765432',
        'num_cin'=>'EF345678',
        'nombre_jours_travail'=>22,
        // Bancaire
        'bank_account'=>'FR7611111111111111111111111',
        'bank_name'=>'BNP Paribas',
        'bank_code'=>'11111',
        'bank_branch'=>'11111',
        'bank_iban'=>'FR7611111111111111111111111',
        'bank_bic'=>'BNPAFRPPXXX'
    ],
    'direction'             => [
        'login'=>'jean.dir',
        'firstname'=>'Jean',
        'lastname'=>'Bernard',
        'email'=>'jean.dir@example.com',
        'password'=>'test123',
        'employee'=>1,
        // RH Data
        'job'=>'Directeur Général',
        'weeklyhours'=>45,
        'salary'=>95000,
        'thm'=>54.05,
        'tjm'=>432.43,
        'dateemployment'=>'2020-01-01',
        'birth'=>'1978-11-25',
        'color'=>'5F27CD',
        // Échelle salariale & identification
        'fk_salary_scale'=>3,
        'employee_number'=>'EMP004',
        'situation_familiale'=>'Marié',
        'nbre_enfants'=>2,
        'num_cnss'=>'423456782',
        'num_cimr'=>'CIMR654321',
        'num_cin'=>'GH456789',
        'nombre_jours_travail'=>22,
        // Bancaire
        'bank_account'=>'FR7622222222222222222222222',
        'bank_name'=>'Société Générale',
        'bank_code'=>'22222',
        'bank_branch'=>'22222',
        'bank_iban'=>'FR7622222222222222222222222',
        'bank_bic'=>'SOGEFRPPXXX'
    ],
];

$fk_user_creat_default = 1;  // ID admin créateur
$forcePassChange       = 1;  // on l’activera uniquement si la colonne existe

/* Utils */
function fatal($m){fwrite(STDERR,"[ERROR] $m\n");exit(1);}
function info($m){echo "[INFO] $m\n";}
function done($m){echo "[OK]   $m\n";}
function randomPassword($n=16){
  $a='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789#@!$%^&*';
  $p=''; for($i=0;$i<$n;$i++){$p.=$a[random_int(0,strlen($a)-1)];} return $p;
}
function sqlAdminExclusionClause($includeAdmin){ return $includeAdmin ? "" : " AND rd.module <> 'user' AND (rd.perms NOT LIKE 'admin%' AND rd.perms NOT LIKE 'setup%') "; }

/* Connexion */
$mysqli = new mysqli($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name);
if ($mysqli->connect_errno) fatal("Connexion MySQL: ".$mysqli->connect_error);
$mysqli->set_charset("utf8mb4");

if (!$dryRun) { $mysqli->begin_transaction(); info("Transaction démarrée"); }
else { info("Mode DRY-RUN (aucune écriture)"); }

/* Préparations */
$sqlFindGroup       = "SELECT rowid FROM {$dbPrefix}usergroup WHERE entity=? AND nom=?";
$stmtFindGroup      = $mysqli->prepare($sqlFindGroup) ?: fatal($mysqli->error);
$sqlInsertGroup     = "INSERT INTO {$dbPrefix}usergroup (entity, nom, note, datec, tms) VALUES (?, ?, '', NOW(), NOW())";
$stmtInsertGroup    = $mysqli->prepare($sqlInsertGroup) ?: fatal($mysqli->error);

$sqlCheckRight      = "SELECT 1 FROM {$dbPrefix}usergroup_rights WHERE entity=? AND fk_usergroup=? AND fk_id=?";
$stmtCheckRight     = $mysqli->prepare($sqlCheckRight) ?: fatal($mysqli->error);
$sqlInsertRight     = "INSERT INTO {$dbPrefix}usergroup_rights (entity, fk_usergroup, fk_id) VALUES (?, ?, ?)";
$stmtInsertRight    = $mysqli->prepare($sqlInsertRight) ?: fatal($mysqli->error);

$sqlFindUserByLogin = "SELECT rowid FROM {$dbPrefix}user WHERE entity=? AND login=?";
$stmtFindUserByLogin= $mysqli->prepare($sqlFindUserByLogin) ?: fatal($mysqli->error);

$sqlCheckUserGroup  = "SELECT 1 FROM {$dbPrefix}usergroup_user WHERE fk_usergroup=? AND fk_user=?";
$stmtCheckUserGroup = $mysqli->prepare($sqlCheckUserGroup) ?: fatal($mysqli->error);
$sqlInsertUserGroup = "INSERT INTO {$dbPrefix}usergroup_user (fk_usergroup, fk_user) VALUES (?, ?)";
$stmtInsertUserGroup= $mysqli->prepare($sqlInsertUserGroup) ?: fatal($mysqli->error);

/* --- Détecter les colonnes de llx_user --- */
$colsRes = $mysqli->query("SHOW COLUMNS FROM {$dbPrefix}user");
if (!$colsRes) { if(!$dryRun)$mysqli->rollback(); fatal("SHOW COLUMNS échoué: ".$mysqli->error); }
$userCols = [];
while ($c = $colsRes->fetch_assoc()) { $userCols[strtolower($c['Field'])]=true; }
$colsRes->free();

// Colonnes de base
$hasPassCrypted = isset($userCols['pass_crypted']);
$hasPass        = isset($userCols['pass']);
$hasPassExpired = isset($userCols['pass_expired']);
$hasEmployee    = isset($userCols['employee']);
$hasAdmin       = isset($userCols['admin']);
$hasEmail       = isset($userCols['email']);
$hasFirstname   = isset($userCols['firstname']);
$hasLastname    = isset($userCols['lastname']);
$hasTms         = isset($userCols['tms']);
$hasDatec       = isset($userCols['datec']);
$hasFkUserCreat = isset($userCols['fk_user_creat']);
$hasStatut      = isset($userCols['statut']);
$hasEntity      = isset($userCols['entity']);

// Colonnes RH
$hasJob         = isset($userCols['job']);
$hasWeeklyhours = isset($userCols['weeklyhours']);
$hasSalary      = isset($userCols['salary']);
$hasThm         = isset($userCols['thm']);
$hasTjm         = isset($userCols['tjm']);
$hasDateEmployment = isset($userCols['dateemployment']);
$hasDateEmploymentEnd = isset($userCols['dateemploymentend']);
$hasBirth       = isset($userCols['birth']);
$hasColor       = isset($userCols['color']);

// Colonnes identification & échelle salariale
$hasFkSalaryScale = isset($userCols['fk_salary_scale']);
$hasEmployeeNumber = isset($userCols['employee_number']) || isset($userCols['ref_employee']);
$hasSituationFamiliale = isset($userCols['situation_familiale']);
$hasNbreEnfants = isset($userCols['nbre_enfants']);
$hasNumCnss     = isset($userCols['num_cnss']);
$hasNumCimr     = isset($userCols['num_cimr']);
$hasNumCin      = isset($userCols['num_cin']);
$hasNombreJoursTravail = isset($userCols['nombre_jours_travail']);

// Note: Les données bancaires sont généralement stockées dans llx_user_rib
// et non directement dans llx_user

/* Compteurs */
$totalGroupsCreated=0; $totalRightsLinked=0; $totalUsersCreated=0; $totalUserLinks=0;

/* ===== Boucle groupes ===== */
foreach ($groups as $groupKey=>$groupLabel) {
    $modules = $moduleSets[$groupKey] ?? [];

    /* 1) Groupe */
    $groupId = null;
    $stmtFindGroup->bind_param("is",$entity,$groupLabel);
    $stmtFindGroup->execute();
    $stmtFindGroup->bind_result($gid);
    if ($stmtFindGroup->fetch()) { $groupId=(int)$gid; info("Groupe existant: '$groupLabel' (ID=$groupId)"); }
    $stmtFindGroup->free_result();

    if ($groupId===null) {
        info("Création groupe: '$groupLabel'");
        if ($dryRun) { $groupId = -1; }
        else {
            $stmtInsertGroup->bind_param("is",$entity,$groupLabel);
            if (!$stmtInsertGroup->execute()) { $mysqli->rollback(); fatal("Insertion groupe '$groupLabel' : ".$stmtInsertGroup->error); }
            $groupId = $stmtInsertGroup->insert_id; $totalGroupsCreated++;
            done("Groupe créé: '$groupLabel' (ID=$groupId)");
        }
    }

    /* 2) Droits */
    if (!empty($modules)) {
        $includeAdmin = ($groupKey==='direction');
        $adminExcl = sqlAdminExclusionClause($includeAdmin);

        if ($modules===['*']) {
            $sqlRights="SELECT rd.id FROM {$dbPrefix}rights_def rd WHERE 1=1 $adminExcl ORDER BY rd.module, rd.perms, rd.subperms";
            $res = $mysqli->query($sqlRights);
        } else {
            $ph=implode(',',array_fill(0,count($modules),'?'));
            $sqlRights="SELECT rd.id FROM {$dbPrefix}rights_def rd WHERE rd.module IN ($ph) $adminExcl ORDER BY rd.module, rd.perms, rd.subperms";
            $stmtRights=$mysqli->prepare($sqlRights) ?: fatal($mysqli->error);
            $types=str_repeat('s',count($modules));
            $stmtRights->bind_param($types,...$modules);
            $stmtRights->execute();
            $res=$stmtRights->get_result();
        }
        if(!$res){ $mysqli->rollback(); fatal("Lecture droits (groupe '$groupLabel'): ".$mysqli->error); }

        $linked=0;
        while($row=$res->fetch_assoc()){
            $rid=(int)$row['id'];
            if ($dryRun) { echo "  ~ (DRY) Lier droit #$rid au groupe '$groupLabel'\n"; $linked++; continue; }
            $stmtCheckRight->bind_param("iii",$entity,$groupId,$rid);
            $stmtCheckRight->execute(); $stmtCheckRight->store_result();
            if ($stmtCheckRight->num_rows===0){
                $stmtInsertRight->bind_param("iii",$entity,$groupId,$rid);
                if(!$stmtInsertRight->execute()){ $mysqli->rollback(); fatal("Lien droit #$rid -> groupe '$groupLabel' : ".$stmtInsertRight->error); }
                echo "  + Droit lié: #$rid\n"; $linked++;
            } else { echo "  = Droit déjà présent: #$rid\n"; }
            $stmtCheckRight->free_result();
        }
        $res->free();
        info("Groupe '$groupLabel': $linked droits liés."); $totalRightsLinked+=$linked;
    }

    /* 3) Utilisateur par groupe */
    if (!isset($usersByGroup[$groupKey])) { info("Aucun utilisateur pour '$groupKey'"); continue; }
    $u=$usersByGroup[$groupKey];

    $login     = strtolower(trim($u['login']));
    $firstname = $u['firstname'];
    $lastname  = $u['lastname'];
    $email     = $u['email'];
    $employee  = isset($u['employee'])?(int)$u['employee']:1;

    $isAdmin   = ($groupKey==='direction' && $hasAdmin) ? 1 : 0;
    $isActive  = $hasStatut ? 1 : null;
    $fk_user_creat = $hasFkUserCreat ? $fk_user_creat_default : null;

    /* Existe déjà ? */
    $userId=null;
    $stmtFindUserByLogin->bind_param("is",$entity,$login);
    $stmtFindUserByLogin->execute();
    $stmtFindUserByLogin->bind_result($uid);
    if ($stmtFindUserByLogin->fetch()){ $userId=(int)$uid; info("Utilisateur existant: $login (ID=$userId)"); }
    $stmtFindUserByLogin->free_result();

    /* Créer si absent */
    if ($userId===null){
        $plainPassword = $u['password'] ?? null;
        if ($plainPassword===null){ $plainPassword=randomPassword(16); info("Mot de passe généré pour $login : $plainPassword"); }
        $hash = password_hash($plainPassword,PASSWORD_BCRYPT);

        // Construire dynamiquement l’INSERT selon colonnes existantes
        $cols = []; $placeholders=[]; $vals=[]; $types='';

        if($hasEntity){ $cols[]='entity'; $placeholders[]='?'; $vals[]=$entity; $types.='i'; }
        if($hasDatec){ $cols[]='datec'; $placeholders[]='NOW()'; }
        if($hasTms){ $cols[]='tms'; $placeholders[]='NOW()'; }

        $cols[]='login';     $placeholders[]='?'; $vals[]=$login;     $types.='s';
        if($hasLastname){ $cols[]='lastname'; $placeholders[]='?'; $vals[]=$lastname; $types.='s'; }
        if($hasFirstname){ $cols[]='firstname'; $placeholders[]='?'; $vals[]=$firstname; $types.='s'; }
        if($hasEmail){ $cols[]='email'; $placeholders[]='?'; $vals[]=$email; $types.='s'; }

        if($hasPassCrypted){ $cols[]='pass_crypted'; $placeholders[]='?'; $vals[]=$hash; $types.='s'; }
        elseif($hasPass){    $cols[]='pass';         $placeholders[]='?'; $vals[]=$hash; $types.='s'; }
        else { if(!$dryRun)$mysqli->rollback(); fatal("Aucune colonne mot de passe ('pass_crypted' ou 'pass') dans {$dbPrefix}user"); }

        if($hasStatut){ $cols[]='statut'; $placeholders[]='?'; $vals[]=$isActive; $types.='i'; }
        if($hasAdmin){  $cols[]='admin';  $placeholders[]='?'; $vals[]=$isAdmin;  $types.='i'; }
        if($hasFkUserCreat){ $cols[]='fk_user_creat'; $placeholders[]='?'; $vals[]=$fk_user_creat; $types.='i'; }
        if($hasEmployee){    $cols[]='employee';      $placeholders[]='?'; $vals[]=$employee;       $types.='i'; }
        if($hasPassExpired){ $cols[]='pass_expired';  $placeholders[]='?'; $vals[]=$forcePassChange; $types.='i'; }

        // Champs RH
        if($hasJob && isset($u['job'])){ $cols[]='job'; $placeholders[]='?'; $vals[]=$u['job']; $types.='s'; }
        if($hasWeeklyhours && isset($u['weeklyhours'])){ $cols[]='weeklyhours'; $placeholders[]='?'; $vals[]=$u['weeklyhours']; $types.='d'; }
        if($hasSalary && isset($u['salary'])){ $cols[]='salary'; $placeholders[]='?'; $vals[]=$u['salary']; $types.='d'; }
        if($hasThm && isset($u['thm'])){ $cols[]='thm'; $placeholders[]='?'; $vals[]=$u['thm']; $types.='d'; }
        if($hasTjm && isset($u['tjm'])){ $cols[]='tjm'; $placeholders[]='?'; $vals[]=$u['tjm']; $types.='d'; }
        if($hasDateEmployment && isset($u['dateemployment'])){ $cols[]='dateemployment'; $placeholders[]='?'; $vals[]=$u['dateemployment']; $types.='s'; }
        if($hasBirth && isset($u['birth'])){ $cols[]='birth'; $placeholders[]='?'; $vals[]=$u['birth']; $types.='s'; }
        if($hasColor && isset($u['color'])){ $cols[]='color'; $placeholders[]='?'; $vals[]=$u['color']; $types.='s'; }

        // Échelle salariale & identification
        if($hasFkSalaryScale && isset($u['fk_salary_scale'])){ $cols[]='fk_salary_scale'; $placeholders[]='?'; $vals[]=$u['fk_salary_scale']; $types.='i'; }
        if($hasEmployeeNumber && isset($u['employee_number'])){
            $colName = isset($userCols['employee_number']) ? 'employee_number' : 'ref_employee';
            $cols[]=$colName; $placeholders[]='?'; $vals[]=$u['employee_number']; $types.='s';
        }
        if($hasSituationFamiliale && isset($u['situation_familiale'])){ $cols[]='situation_familiale'; $placeholders[]='?'; $vals[]=$u['situation_familiale']; $types.='s'; }
        if($hasNbreEnfants && isset($u['nbre_enfants'])){ $cols[]='nbre_enfants'; $placeholders[]='?'; $vals[]=$u['nbre_enfants']; $types.='i'; }
        if($hasNumCnss && isset($u['num_cnss'])){ $cols[]='num_cnss'; $placeholders[]='?'; $vals[]=$u['num_cnss']; $types.='s'; }
        if($hasNumCimr && isset($u['num_cimr'])){ $cols[]='num_cimr'; $placeholders[]='?'; $vals[]=$u['num_cimr']; $types.='s'; }
        if($hasNumCin && isset($u['num_cin'])){ $cols[]='num_cin'; $placeholders[]='?'; $vals[]=$u['num_cin']; $types.='s'; }
        if($hasNombreJoursTravail && isset($u['nombre_jours_travail'])){ $cols[]='nombre_jours_travail'; $placeholders[]='?'; $vals[]=$u['nombre_jours_travail']; $types.='i'; }

        $colsList = implode(', ',$cols);
        $phList   = implode(', ',$placeholders);
        $sql = "INSERT INTO {$dbPrefix}user ($colsList) VALUES ($phList)";

        if ($dryRun) {
            echo "  ~ (DRY) INSERT user $login avec colonnes: $colsList\n";
            $userId = -1;
        } else {
            $stmt = $mysqli->prepare($sql) ?: ($mysqli->rollback()||fatal("Prepare INSERT user: ".$mysqli->error));
            if ($types!==''){ $stmt->bind_param($types, ...$vals); }
            if (!$stmt->execute()){ $mysqli->rollback(); fatal("Insertion utilisateur '$login' : ".$stmt->error); }
            $userId = $stmt->insert_id; $stmt->close();
            $totalUsersCreated++; done("Utilisateur créé: $login (ID=$userId)");
        }
    }

    /* Lier user -> group */
    if($groupId!==-1 && $userId!==-1){
        if($dryRun){ echo "  ~ (DRY) Lier user $userId -> group $groupId\n"; $totalUserLinks++; }
        else {
            $stmtCheckUserGroup->bind_param("ii",$groupId,$userId);
            $stmtCheckUserGroup->execute(); $stmtCheckUserGroup->store_result();
            if ($stmtCheckUserGroup->num_rows===0){
                $stmtInsertUserGroup->bind_param("ii",$groupId,$userId);
                if(!$stmtInsertUserGroup->execute()){ $mysqli->rollback(); fatal("Lien user->group: ".$stmtInsertUserGroup->error); }
                echo "  + Lien user->group créé (user=$userId, group=$groupId)\n"; $totalUserLinks++;
            } else { echo "  = Lien user->group déjà présent (user=$userId, group=$groupId)\n"; }
            $stmtCheckUserGroup->free_result();
        }
    }

    /* Ajouter les informations bancaires (llx_user_rib) */
    if ($userId > 0 && !$dryRun && isset($u['bank_account'])) {
        // Vérifier si un RIB existe déjà pour cet utilisateur
        $sqlCheckRib = "SELECT rowid FROM {$dbPrefix}user_rib WHERE fk_user=? LIMIT 1";
        $stmtCheckRib = $mysqli->prepare($sqlCheckRib);
        if ($stmtCheckRib) {
            $stmtCheckRib->bind_param("i", $userId);
            $stmtCheckRib->execute();
            $stmtCheckRib->store_result();

            if ($stmtCheckRib->num_rows === 0) {
                // Détecter les colonnes de llx_user_rib
                $ribColsRes = $mysqli->query("SHOW COLUMNS FROM {$dbPrefix}user_rib");
                $ribCols = [];
                if ($ribColsRes) {
                    while ($c = $ribColsRes->fetch_assoc()) { $ribCols[strtolower($c['Field'])]=true; }
                    $ribColsRes->free();
                }

                // Construire l'INSERT dynamiquement selon les colonnes disponibles
                $ribInsertCols = []; $ribInsertPh = []; $ribInsertVals = []; $ribInsertTypes = '';

                if(isset($ribCols['fk_user'])){ $ribInsertCols[]='fk_user'; $ribInsertPh[]='?'; $ribInsertVals[]=$userId; $ribInsertTypes.='i'; }
                if(isset($ribCols['entity'])){ $ribInsertCols[]='entity'; $ribInsertPh[]='?'; $ribInsertVals[]=$entity; $ribInsertTypes.='i'; }
                if(isset($ribCols['datec'])){ $ribInsertCols[]='datec'; $ribInsertPh[]='NOW()'; }
                if(isset($ribCols['tms'])){ $ribInsertCols[]='tms'; $ribInsertPh[]='NOW()'; }

                $bankName = $u['bank_name'] ?? '';
                $bankCode = $u['bank_code'] ?? '';
                $bankBranch = $u['bank_branch'] ?? '';
                $bankAccount = $u['bank_account'] ?? '';
                $bankBic = $u['bank_bic'] ?? '';
                $bankIban = $u['bank_iban'] ?? '';
                $ownerName = $firstname . ' ' . $lastname;

                if(isset($ribCols['bank'])){ $ribInsertCols[]='bank'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankName; $ribInsertTypes.='s'; }
                if(isset($ribCols['code_bank'])){ $ribInsertCols[]='code_bank'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankCode; $ribInsertTypes.='s'; }
                if(isset($ribCols['code_guichet'])){ $ribInsertCols[]='code_guichet'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankBranch; $ribInsertTypes.='s'; }
                if(isset($ribCols['number'])){ $ribInsertCols[]='number'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankAccount; $ribInsertTypes.='s'; }
                if(isset($ribCols['bic'])){ $ribInsertCols[]='bic'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankBic; $ribInsertTypes.='s'; }
                if(isset($ribCols['iban_prefix'])){ $ribInsertCols[]='iban_prefix'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankIban; $ribInsertTypes.='s'; }
                if(isset($ribCols['domiciliation'])){ $ribInsertCols[]='domiciliation'; $ribInsertPh[]='?'; $ribInsertVals[]=$bankName; $ribInsertTypes.='s'; }
                if(isset($ribCols['proprio'])){ $ribInsertCols[]='proprio'; $ribInsertPh[]='?'; $ribInsertVals[]=$ownerName; $ribInsertTypes.='s'; }

                $ribColsList = implode(', ', $ribInsertCols);
                $ribPhList = implode(', ', $ribInsertPh);
                $sqlInsertRib = "INSERT INTO {$dbPrefix}user_rib ($ribColsList) VALUES ($ribPhList)";

                $stmtInsertRib = $mysqli->prepare($sqlInsertRib);
                if ($stmtInsertRib) {
                    if ($ribInsertTypes !== '') { $stmtInsertRib->bind_param($ribInsertTypes, ...$ribInsertVals); }
                    if ($stmtInsertRib->execute()) {
                        done("  + Informations bancaires ajoutées pour $login");
                    } else {
                        echo "  ! Erreur lors de l'ajout du RIB pour $login: " . $stmtInsertRib->error . "\n";
                    }
                    $stmtInsertRib->close();
                } else {
                    echo "  ! Erreur prepare INSERT RIB: " . $mysqli->error . "\n";
                }
            } else {
                echo "  = RIB déjà existant pour $login\n";
            }
            $stmtCheckRib->free_result();
            $stmtCheckRib->close();
        }
    }
}

/* Commit */
if ($dryRun) info("DRY-RUN terminé. Aucune écriture.");
else { $mysqli->commit(); done("Transaction validée."); }

done("RÉSUMÉ: Groupes créés: $totalGroupsCreated — Droits liés: $totalRightsLinked — Users créés: $totalUsersCreated — Liens user->groupe: $totalUserLinks");
$mysqli->close();
