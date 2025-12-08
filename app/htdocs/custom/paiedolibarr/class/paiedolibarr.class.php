<?php 
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_paies.class.php');
require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
class paiedolibarr
{
    public $situation_f;
    public $nbrenfants;
    public $partigr;
    public $categorie;
    public $qualification;
    public $zone;
    public $echelon;
    public $niveau;
    public $matricule;

    public function __construct($db)
    {   
        global $langs;

        $this->db = $db;
        $this->paiedolibarrmodel = [
            'maroc'        => $langs->trans('Bydefault'),
            'globetudes'   => $langs->trans('paieGlobetudesModel'),
            'ghodbane'   => $langs->trans('paieGhodbaneModel'),
        ];
    }
    
    public function upgradeTheModule()
    {
        global $conf, $langs;

        dol_include_once('/paiedolibarr/core/modules/modpaiedolibarr.class.php');
        $modpaiedolibarr = new modpaiedolibarr($this->db);

        $error = 0;
        $lastversion    = $modpaiedolibarr->version;
        $currentversion = dolibarr_get_const($this->db, 'PAIEDOLIBARR_LAST_VERSION_OF_MODULE', 0);

        // if(!$conf->global->PAIEDOLIBARR_CLEAN_TABLE){
        //     $res = $this->db->query('DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'paiedolibarr_paiesrules');
        //     $res = $this->db->query('DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'paiedolibarr_rules');
        //     $res = $this->db->query('DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'paiedolibarr_paies');
        //     $res = $this->db->query('DROP TABLE IF EXISTS '.MAIN_DB_PREFIX.'paiedolibarr_paies_extrafields');
        //     if($res>0){
        //     }
            
        //     dolibarr_del_const($this->db, 'PAIEDOLIBARR_FORM_CALCUL_RETENUS', $conf->entity);
        //     dolibarr_del_const($this->db, 'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER', $conf->entity);
        //     dolibarr_del_const($this->db, 'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET', $conf->entity);
        //     dolibarr_del_const($this->db, 'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT', $conf->entity);
        //     dolibarr_del_const($this->db, 'PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO', $conf->entity);

        //     dolibarr_set_const($this->db, 'PAIEDOLIBARR_CLEAN_TABLE', 1, 'int', 0, $conf->entity);
        //     $res = $this->initpaiedolibarrModule($lastversion);
        // }

        if (!$currentversion || ($currentversion && $lastversion != $currentversion)){
            $res = $this->initpaiedolibarrModule($lastversion);
            $error += $modpaiedolibarr->delete_menus();
            $error += $modpaiedolibarr->insert_menus();
            if($res)
                dolibarr_set_const($this->db, 'PAIEDOLIBARR_LAST_VERSION_OF_MODULE', $lastversion, 'chaine', 0, '', 0);
            return 1;
        }
        return 0;
    }
    
    public function initpaiedolibarrModule($lastversion = '')
    {
        global $conf, $langs;

        // if (!dolibarr_get_const($this->db,'PAIE_FIRST_USE_NEW_VERSION',$conf->entity)){
        //     $error = 0;
        //     $sql = "DROP TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies";
        //     $resql = $this->db->query($sql);
        //     if(!$resql) $error++;

        //     $sql = "DROP TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules";
        //     $resql = $this->db->query($sql);
        //     if(!$resql) $error++;
            
        //     $sql = "DROP TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules";
        //     $resql = $this->db->query($sql);
        //     if(!$resql) $error++;

        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT','SALAIRE_BASE + CERTIFICAT + POSTE_DIRECTION + CHARGE' ,'chaine',0,'',$conf->entity);
        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET','(SALAIRE_BASE + IND_LOG + ALL_FAM) - MFP_EMPLOYE - INSS_EMPLOYE - BHB - IRE - AUTRES_RETENUS' ,'chaine',0,'',$conf->entity);
        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER','(SALAIRE_BASE + IND_LOG + ALL_FAM) - MFP_EMPLOYE - INSS_EMPLOYE - BHB - IRE - AUTRES_RETENUS' ,'chaine',0,'',$conf->entity);


        //     if(!$error){
        //         dolibarr_set_const($this->db,'PAIE_FIRST_USE_NEW_VERSION','1' ,'chaine',0,'',$conf->entity);
        //     } else {
        //         return false;
        //     }
        // }

        // dolibarr_set_const($this->db,'PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT','0' ,'chaine',0,'',$conf->entity);

        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."paiedolibarr_paies (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY
            ,fk_user int DEFAULT 0
            ,ref varchar(255) DEFAULT NULL
            ,label varchar(255) DEFAULT NULL
            ,period date DEFAULT NULL
            ,salairebrut DOUBLE(24,4) NULL DEFAULT 0
            ,salairebimpo DOUBLE(24,4) NULL DEFAULT 0
            ,retenus DOUBLE(24,4) NULL DEFAULT 0
            ,salairenet DOUBLE(24,4) NULL DEFAULT 0
            ,netapayer DOUBLE(24,4) NULL DEFAULT 0
            ,comment varchar(755) NULL
            ,datepay date DEFAULT NULL
            ,mode_reglement_id int DEFAULT NULL
            ,matricule varchar(10) NULL
            ,situation_f varchar(5) NULL DEFAULT '0'
            ,certificat varchar(255) NULL DEFAULT NULL
            ,nbrenfants int(3) NULL
            ,nbdaywork int(3) NULL
            ,fk_author int NULL
            ,fk_lastedit int NULL
            ,tms timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ,entity int NOT NULL DEFAULT ".$conf->entity."
        )";
        $resql = $this->db->query($sql);

        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD salairebrut DOUBLE(24,4) NULL DEFAULT 0 AFTER period");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD salairebimpo DOUBLE(24,4) NULL DEFAULT 0 AFTER salairebrut");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD retenus DOUBLE(24,4) NULL DEFAULT 0 AFTER salairebimpo");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD salairenet DOUBLE(24,4) NULL DEFAULT 0 AFTER salairebimpo");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD matricule varchar(10) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD situation_f varchar(5) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD certificat varchar(255) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD worksite varchar(255) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD nbrenfants int(3) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD nbdaywork int(3) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD nbdayabsence int(3) NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD datepay date DEFAULT NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD mode_reglement_id int DEFAULT NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD CONSTRAINT unique_paie UNIQUE (fk_user, period);");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD fk_author int NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD fk_lastedit int NULL");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD tms timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paies ADD fk_salary int NULL");



        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."paiedolibarr_rules (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY
            ,numero varchar(255) DEFAULT NULL
            ,code varchar(255) DEFAULT NULL
            ,label varchar(255) DEFAULT NULL
            ,category varchar(40) DEFAULT NULL
            ,amounttype varchar(10) NULL DEFAULT 'FIX'
            ,showdefault smallint(1) NULL DEFAULT 0
            ,amount DOUBLE(24,4) NULL DEFAULT 0
            ,taux DOUBLE(24,4) NULL DEFAULT 0
            ,ordercalcul int DEFAULT 0
            ,formule_base TEXT NULL
            ,formule_taux TEXT NULL
            ,formule TEXT NULL
            ,total DOUBLE(24,4) NULL DEFAULT 0
            ,showcumul int(2) NULL DEFAULT 0
            ,entity int NOT NULL DEFAULT ".$conf->entity."
        )";
        $resql = $this->db->query($sql);

        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD CONSTRAINT unique_code UNIQUE (code);");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules CHANGE formule formule TEXT NULL;");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD amount DOUBLE(24,4) NULL DEFAULT 0 AFTER amounttype");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD taux DOUBLE(24,4) NULL DEFAULT 0 AFTER amount");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD formule_base TEXT NULL AFTER taux");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD formule_taux TEXT NULL AFTER amounttype");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD showcumul int(2) NULL DEFAULT 0");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD showdefault smallint(1) NULL DEFAULT 0 AFTER amounttype");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_rules ADD numero varchar(255) DEFAULT NULL AFTER rowid");

        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."paiedolibarr_paiesrules (
            rowid int NOT NULL AUTO_INCREMENT PRIMARY KEY
            ,fk_paie int DEFAULT NULL
            ,numero varchar(255) DEFAULT NULL
            ,code varchar(255) DEFAULT NULL
            ,label varchar(255) DEFAULT NULL
            ,category varchar(40) DEFAULT NULL
            ,amounttype varchar(10) NULL DEFAULT 'FIX'
            ,amount DOUBLE(24,4) NULL DEFAULT 0
            ,taux DOUBLE(24,4) NULL DEFAULT 0
            ,formule_base TEXT NULL
            ,formule_taux TEXT NULL
            ,total DOUBLE(24,4) NULL DEFAULT 0
            ,formule TEXT NULL
        )";
        $resql = $this->db->query($sql);

        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules CHANGE formule formule TEXT NULL;");

        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD amount DOUBLE(24,4) NULL DEFAULT 0 AFTER amounttype");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD taux DOUBLE(24,4) NULL DEFAULT 0 AFTER amount");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD formule_base TEXT NULL AFTER taux");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD formule_taux TEXT NULL AFTER formule_base");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD numero varchar(255) DEFAULT NULL AFTER fk_paie");
        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD CONSTRAINT unique_paiecode UNIQUE (fk_paie,code);");

        $resql = $this->db->query("ALTER TABLE ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ADD CONSTRAINT uk_fk_paie FOREIGN KEY (fk_paie) REFERENCES ".MAIN_DB_PREFIX."paiedolibarr_paies (rowid) ON DELETE CASCADE;");

        
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);


        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."paiedolibarr_paies_extrafields (
          rowid        integer AUTO_INCREMENT PRIMARY KEY,
          tms          timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          fk_object    integer NOT NULL,
          import_key   varchar(14) 
        ) ";
        $resql = $this->db->query($sql);
        
        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."paiedolibarr_salaryscall (
            rowid   integer AUTO_INCREMENT PRIMARY KEY
            ,degre  int NOT NULL
            ,year   int NOT NULL
            ,amount DOUBLE(24,4) NULL DEFAULT 0
        ) ";
        $resql = $this->db->query($sql);

        for ($i=1; $i <= 10 ; $i++) { 
            $degres[$i] = $i; 
        }
        $arr = array('options' => $degres);
        $extrafields->addExtraField('paiedolibarr_scall', 'salaryscall', "select", "988", '', "user",0,0,'',$arr,1, '', 1);
        // $arr = array('options'=>array('M'=>$langs->trans('paieMarie'),'C'=>$langs->trans('paieCelibataire'),'CPLE'=>$langs->trans('paieCouple')));
        $arr = array('options'=>array('M'=>$langs->trans('paieMarie'),'C'=>$langs->trans('paieCelibataire'),'D'=>$langs->trans('paieDivorce')));
        // $extrafields->addExtraField('paiedolibarrunderline1', 'GRH', "separate", "987", 500, "user");
        $extrafields->addExtraField('paiedolibarrmatricule', 'paiematricule', "varchar", "988", 40, "user");
        $extrafields->addExtraField('paiedolibarrsituation_f', 'paiesituation_f', "select", "988", '', "user",0,0,'',$arr,1);
        $extrafields->addExtraField('paiedolibarrnbrenfants', 'paienbrenfants', "varchar", "988", 5, "user");
        $extrafields->addExtraField('paiedolibarrcnss', 'paiecnss', "varchar", "988", 40, "user");
        $extrafields->addExtraField('paiedolibarrcimr', 'paiecimr', "varchar", "988", 40, "user");
        $extrafields->addExtraField('paiedolibarrcin', 'paiecin', "varchar", "988", 40, "user");
        $extrafields->addExtraField('paiedolibarrnbdaywork', 'paienbdaywork', "int", "988", 40, "user");

        $params = serialize(array('options' => array('paiedolibarr_paies:paiedolibarr/class/paiedolibarr_paies.class.php' => null)));
        $res = $extrafields->addExtraField('paiedolibarr_fk_paie', 'paieBulletin_de_paie', "link", 100, '', "salary",  0, 0, '', $params, 0, '', 5, '', '', '','', '1');

        $position = 30;
        
        // $extrafields->addExtraField('paiedolibarr_exceptions', 'Exceptions', "double", $position++, '24,4', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_worksite', 'Work Site', "boolean", $position++, 100, "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_occupationengineer', 'Occupation (Engineer) %', "double", $position++, '3,3', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_percent_position', 'Position %', "double", $position++, '3,3', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_percent_certificat', 'Certificat %', "double", $position++, '3,3', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_protection', 'Protection', "boolean", $position++, 100, "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_absence', 'Absence', "boolean", $position++, 100, "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_vacations', 'Vacations', "boolean", $position++, 100, "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_authoritycompensation', 'Authority compensation', "boolean", $position++, 100, "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_retirement', 'Retirement', "double", $position++, '3,3', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_loans', 'Loans total', "double", $position++, '3,3', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_loans', 'Loans total', "double", $position++, '24,4', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_loans', 'Loans total', "double", $position++, '24,4', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_supportamount', 'Support Amount', "double", $position++, '24,4', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_percent_tax', 'Tax %', "double", $position++, '3,3', "paiedolibarr_paies");
        // $extrafields->addExtraField('paiedolibarr_servicestotal', 'Services Total', "double", $position++, '24,4', "paiedolibarr_paies");

        // $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiedolibarr_rules (rowid, code, label, category, amounttype, showdefault, formule_taux, amount, taux, formule_base, ordercalcul, formule, total, entity, showcumul) VALUES
        //     (1, 'SALARY', 'Salaire de base', '0BASIQUE', 'CALCULATED', 1, '100', 0.0000, 0.0000, 'USER_SALARY', 0, NULL, 0.0000, ".$conf->entity.", 0),
        //     (2, 'WORK_SITE', 'Work site', '1BRUT', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_WORKSITE\') ? 60000 : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (3, 'EXCEPTIONS', 'Exceptions', '1BRUT', 'CALCULATED', 1, '100', 0.0000, 0.0000, 'PAIEDOLIBARR_EXCEPTIONS', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (4, 'OCCUPATION', 'Occupation (Engineer) %', '1BRUT', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_OCCUPATIONENGINEER\') ? (SALARY * \'PAIEDOLIBARR_OCCUPATIONENGINEER\') : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (5, 'POSITION', 'Position %', '1BRUT', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_PERCENT_CERTIFICAT\') ? (SALARY * \'PAIEDOLIBARR_PERCENT_CERTIFICAT\') : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (6, 'CERTIFICATE', 'Certificate', '1BRUT', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_PERCENT_POSITION\') ? (\'PAIEDOLIBARR_PERCENT_POSITION\' * SALARY) : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (7, 'DEPENDENTCHILDREN', 'Dependent family - Children', '4CHARGEF', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'NB_ENFANTS\' > 0) ? ((\'NB_ENFANTS\' < 4) ? \'NB_ENFANTS\'*20000 : 4*20000 ) : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (8, 'DEPENDENTSITUFAMIL', 'Dependent family - Family situation', '4CHARGEF', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'SITU_FAMILIALE\'==\'M\') ? 5000 : ( (\'SITU_FAMILIALE\'==\'D\') ? 10000 : ((\'SITU_FAMILIALE\'==\'C\') ? 2500 : 0))', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (9, 'PROTECTION', 'Protection', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_PROTECTION\') ? 4231 : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (10, 'ABSENCE', 'Absence', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_ABSENCE\') ? 1500 : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (11, 'VACATIONS', 'Vacations', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_VACATIONS\') ? 2000 : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (12, 'AUTHORITYCOMPENSATION', 'Authority Compensation', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_AUTHORITYCOMPENSATION\') ? 1500 : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (13, 'RETIREMENT', 'Retirement', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_RETIREMENT\') ? (\'PAIEDOLIBARR_RETIREMENT\' * SALARY) : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (14, 'LOANS', 'Loans', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, 'PAIEDOLIBARR_LOANS', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (15, 'SUPPORTAMOUNT', 'Support Amount', '2RETENUES', 'CALCULATED', 1, '100', 0.0000, 0.0000, 'PAIEDOLIBARR_SUPPORTAMOUNT', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (16, 'TAX', 'Tax', '6TAXSOC', 'CALCULATED', 1, '100', 0.0000, 0.0000, '(\'PAIEDOLIBARR_PERCENT_TAX\')  ? \'PAIEDOLIBARR_PERCENT_TAX\' * SALARY : 0', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        //     (17, 'SERVICE', 'Service', '7AvanceRet', 'CALCULATED', 1, '100', 0.0000, 0.0000, 'PAIEDOLIBARR_SERVICESTOTAL', 0, NULL, 0.0000, ".$conf->entity.", NULL);";
        // // d($sql);
        // $resql = $this->db->query($sql);

        // $S_B_G = 'SALARY+(CERTIFICATE+WORK_SITE+EXCEPTIONS+OCCUPATION+POSITION+DEPENDENTCHILDREN+DEPENDENTMARRIED)';
        // $S_B_I = 'SALARY + PR_A + PR_B + PR_C + PR_D + PR_G + PR_I + PR_J + IND_C + IND_D + IND_E';
        // $S_N_I = '(S_B_I) - (AMO + CNSS + CIMR + FRAIS + COT01 + COT02)';
        // $RETENUS = 'PROTECTION+ABSENCE+VACATIONS+AUTHORITYCOMPENSATION+LOANS+RETIREMENT+SUPPORTAMOUNT+TAX+SERVICE';
        // $N_A_P = '(S_B_G) - (PROTECTION+ABSENCE+VACATIONS+AUTHORITYCOMPENSATION+LOANS+RETIREMENT+SUPPORTAMOUNT+TAX+SERVICE)';



        $S_B_G = 'SALARY + PR_A + PR_B + PR_C + PR_D + PR_E + PR_F + PR_G + PR_H + PR_I + PR_J + PR_K + PR_P + IF + IND_A + IND_B + IND_C + IND_D + IND_E + TR';
        $S_B_I = 'SALARY + PR_A + PR_C + PR_D + PR_G + PR_I + PR_J + IND_C + IND_D + IND_E';
        $S_N_I = 'S_B_G - CNSS';
        $N_A_P = '(S_N_I) - (IRPP+ CSS)';
        

        if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT',$conf->entity))
            dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT', $S_B_G, 'chaine',0,'',$conf->entity);


        if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO',$conf->entity))
            dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO', $S_B_I, 'chaine',0,'',$conf->entity);


        if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET',$conf->entity))
            dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET', $S_N_I, 'chaine',0,'',$conf->entity);


        // if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_RETENUS',$conf->entity))
        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_RETENUS', $RETENUS, 'chaine',0,'',$conf->entity);


        if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER',$conf->entity))
            dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER', $N_A_P, 'chaine',0,'',$conf->entity);


        if (!dolibarr_get_const($this->db,'PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT',$conf->entity))
            dolibarr_set_const($this->db,'PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT','2' ,'chaine',0,'',$conf->entity);

        if (dolibarr_get_const($this->db,'PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO',$conf->entity) == '')
            dolibarr_set_const($this->db,'PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO','1' ,'chaine',0,'',$conf->entity);


        // dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT','SALAIRE_BASE + CERTIFICAT + POSTE_DIRECTION + CHARGE' ,'chaine',0,'',$conf->entity);
        // dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET','(SALAIRE_BASE + CERTIFICAT + POSTE_DIRECTION + CHARGE) - MFP_EMPLOYE - INSS_EMPLOYE - BHB - IRE - AUTRES_RETENUS' ,'chaine',0,'',$conf->entity);
        // dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER','(SALAIRE_BASE + CERTIFICAT + POSTE_DIRECTION + CHARGE) - MFP_EMPLOYE - INSS_EMPLOYE - BHB - IRE - AUTRES_RETENUS' ,'chaine',0,'',$conf->entity);



        // // Version 1
        // $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiedolibarr_rules (rowid, code, label, category, amounttype, amount, ordercalcul, formule, total, entity) VALUES
        // (1, 'SALARY', 'Salaire de base', 'BRUT', 'CALCULATED', 0.0000, 1, 'USER_SALARY', 0.0000, ".$conf->entity."),
        // (22, 'ANCIENNETE', 'Ancienneté', 'BRUT', 'CALCULATED', 0.0000, 2, 'SALARY * (YEARS_IN_COMPANY/100)', 0.0000, ".$conf->entity."),
        // (23, 'PRIME_CAISSE', 'Prime de caisse', 'BRUT', 'FIX', 0.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (33, 'DIPLOME', 'Prime de Diplôme ', 'BRUT', 'FIX', 8000.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (44, 'P_ENCOURAGEMENT', 'Prime d’encouragement', 'BRUT', 'FIX', 70104.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (45, 'CONGES', 'Congés', 'BRUT', 'FIX', 0.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (55, 'CNSS', 'CNSS (Caisse nationale de sécurité sociale)', 'RETENUES', 'CALCULATED', 0.0000, 3, '0.04 * (SALARY + ANCIENNETE + DIPLOME + P_ENCOURAGEMENT + PRIME_CAISSE + CONGES)', 0.0000, ".$conf->entity."),
        // (66, 'BASE_IMPOSABLE', 'Base imposable', 'RETENUES', 'CALCULATED', 0.0000, 4, '((SALARY + ANCIENNETE + DIPLOME + P_ENCOURAGEMENT + PRIME_CAISSE + CONGES) - CNSS) * 0.8', 0.0000, ".$conf->entity."),
        // (77, 'IRPP', 'IRPP', 'RETENUES', 'FIX', 10731.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (88, 'INDE_TRANSPORT', 'Indemnité de transport', 'INDEMNITES', 'FIX', 25000.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (99, 'INDE_PANIER', 'Indemnité de panier', 'INDEMNITES', 'FIX', 60000.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (100, 'AUTRES_PRIMES', 'Autres primes', 'INDEMNITES', 'FIX', 90000.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (110, 'AVANCES', 'Avances', 'AUTRESRETENUES', 'FIX', 0.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (120, 'ACOMPTES', 'Acomptes', 'AUTRESRETENUES', 'FIX', 0.0000, 0, '0', 0.0000, ".$conf->entity."),
        // (130, 'PRET', 'Prêt', 'AUTRESRETENUES', 'FIX', 0.0000, 0, '0', 0.0000, ".$conf->entity.");";
        // $resql = $this->db->query($sql);

        // if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT',$conf->entity))
        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT','SALARY + ANCIENNETE + DIPLOME + P_ENCOURAGEMENT + PRIME_CAISSE + CONGES' ,'chaine',0,'',$conf->entity);
        // if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET',$conf->entity))
        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_SALAIRENET','SALARY + ANCIENNETE + DIPLOME + P_ENCOURAGEMENT + PRIME_CAISSE + CONGES' ,'chaine',0,'',$conf->entity);
        // if (!dolibarr_get_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER',$conf->entity))
        //     dolibarr_set_const($this->db,'PAIEDOLIBARR_FORM_CALCUL_NETAPAYER','((SALARY + ANCIENNETE + DIPLOME + P_ENCOURAGEMENT + PRIME_CAISSE + CONGES) + INDE_TRANSPORT + INDE_PANIER + AUTRES_PRIMES) - CNSS - IRPP - AVANCES - ACOMPTES - PRET' ,'chaine',0,'',$conf->entity);


        // $res = $this->db->query('DELETE FROM '.MAIN_DB_PREFIX.'paiedolibarr_rules');
        // Version 2
        // $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiedolibarr_rules (rowid, numero, code, label, category, amounttype, showdefault, formule_base, formule_taux, amount, taux, ordercalcul, formule, total, entity, showcumul) VALUES
        // (1, 1, 'SALARY', 'Salaire de base', '0BASIQUE', 'CALCULATED', 1, 'USER_SALARY', '100', 0.0000, 100.0000, 1, '', 0.0000, ".$conf->entity.", 0),
        // (2, 2, 'PR_A', 'Prime d\'ancienneté', '1BRUT', 'CALCULATED', 1, 'SALARY', '((YEARS_IN_COMPANY < 2) ? 0 : \n    ((YEARS_IN_COMPANY >= 2 && YEARS_IN_COMPANY < 5) ? 5 : \n           ((YEARS_IN_COMPANY >= 5 && YEARS_IN_COMPANY < 12) ? 10 : \n             ((YEARS_IN_COMPANY >= 12 && YEARS_IN_COMPANY < 20) ? 15 : ((YEARS_IN_COMPANY >= 20 && YEARS_IN_COMPANY < 25) ? 20 : 25))\n          )\n )\n)', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", NULL),
        // (3, 3, 'PR_B', 'Solde de tout compte', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (4, 4, 'PR_C', 'Prime de rendement', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (5, 5, 'PR_D', 'Prime de 13e mois', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (6, 6, 'PR_E', 'Prime de transport', '1BRUT', 'FIX', 1, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (7, 7, 'PR_F', 'Prime de panier', '1BRUT', 'FIX', 1, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (8, 8, 'PR_G', 'Prime Aid El Adha', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (9, 9, 'PR_H', 'Prime de salissure', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // -- (65, 65, 'CERTIFICAT', 'Prime de Certificat', '1BRUT', 'CALCULATED', 1, '(\'USER_CERTIFICAT\' == \'Bac+5\') ? SALAIRE_BASE * 0.5/100 : 0', 100, 0.0000, '', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        // -- (66, 66, 'POSTE_DIRECTION', 'Directeur ou sous-directeur', '0BASIQUE', 'CALCULATED', 1, '( \'USER_JOB\' == \'JOB_PATRONAL\' ) ? SALAIRE_BASE * 1.2/100 : 0', 100, 0.0000, '', 0, NULL, 0.0000, ".$conf->entity.", NULL),
        // (10, 10, 'PR_I', 'Commissions', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (11, 11, 'PR_J', 'Prime de production', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (12, 12, 'PR_K', 'Primes diverses', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 2, '', 0.0000, ".$conf->entity.", 0),
        // (23, 23, 'IND_A', 'Indemnité de déplacement', '1BRUT', 'FIX', 1, '', '', 0.0000, 100.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (24, 24, 'IND_B', 'Indemnité de representation', '1BRUT', 'FIX', NULL, '', '', 0.0000, 10.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (25, 25, 'IND_C', 'Indemnité kilométrique', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (26, 26, 'IND_D', 'Indemnité maladie', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (27, 27, 'IND_E', 'Indemnité rentrée scolaire', '1BRUT', 'FIX', 0, '', '', 0.0000, 100.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (55, 55, 'CNSS', 'CNSS', '2RETENUES', 'CALCULATED', 1, '\$result = (S_B_I > 6000) ? 6000 : S_B_I;', '4.48', 0.0000, 100.0000, 5, 'SALAIRE_BASE * (4.48/100)', 0.0000, ".$conf->entity.", 1),
        // (56, 56, 'AMO', 'AMO', '2RETENUES', 'CALCULATED', 1, 'S_B_I', '2.26', 0.0000, 100.0000, 6, 'SALAIRE_BASE * (2.26/100)', 0.0000, ".$conf->entity.", NULL),
        // (57, 57, 'CIMR', 'CIMR', '2RETENUES', 'CALCULATED', NULL, 'S_B_I', '3', 0.0000, 100.0000, 6, '', 0.0000, ".$conf->entity.", NULL),
        // (58, 58, 'COT01', 'Indemnité de perte d\'emploi', '2RETENUES', 'CALCULATED', NULL, '\$result = (S_B_I > 6000) ? 6000 : S_B_I;', '0.19', 0.0000, 100.0000, 6, '', 0.0000, ".$conf->entity.", NULL),
        // (59, 59, 'COT02', 'Assurance maladie (mutuelle)', '2RETENUES', 'CALCULATED', NULL, 'S_B_I', '2.592', 0.0000, 100.0000, 6, '', 0.0000, ".$conf->entity.", NULL),
        // (60, 60, 'FRAIS', 'Frais professionnels', '2RETENUES', 'CALCULATED', 1, '\$result = (S_B_I * 20/100 > 2500) ? 2500 : S_B_I;', '\$result = (S_B_I  * 20/100 > 2500) ? 100 : 20;', 0.0000, 100.0000, 6, '', 0.0000, ".$conf->entity.", NULL),
        // (77, 77, 'IR_B', 'IR avant charge familiale', '3IRBRUT', 'CALCULATED', 1, '\$SNI = S_N_I;\r\n\$result = ((2501<\$SNI && \$SNI<4166.99) ? (\$SNI*10/100)-250 : \r\n\r\n  ((4167<\$SNI && \$SNI<5000.99) ? (\$SNI*20/100)-666.67 : \r\n\r\n       ((5001<\$SNI && \$SNI<6666.99) ? (\$SNI*30/100)-1166.67 : \r\n\r\n          ((6667<\$SNI && \$SNI<15000.99) ? (\$SNI*34/100)-1433.33 : \r\n\r\n             (15000<\$SNI ? (\$SNI*38/100)-2033.33: 0)\r\n           )\r\n       )\r\n   )\r\n);', '100', 0.0000, 100.0000, 7, '0', 0.0000, ".$conf->entity.", NULL),
        // (90, 90, 'CHARGE', 'Charges de famille', '4CHARGEF', 'CALCULATED', 1, '\$s_marie = (\'SITU_FAMILIALE\' == \'M\') ? 30 : 0;\r\n\$s_enfant = (NB_ENFANTS > 5 ? 150 : (30*NB_ENFANTS));\r\n\$result = \$s_marie + \$s_enfant;', '100', 0.0000, 100.0000, 7, '0', 0.0000, ".$conf->entity.", NULL),
        // (95, 95, 'IR_N', 'IR NET', '5IRNET', 'CALCULATED', 1, '(IR_B>CHARGE) ? (IR_B-CHARGE) : 0', '100', 0.0000, 100.0000, 7, '0', 0.0000, ".$conf->entity.", 1),
        // (100, 100, 'TAX', 'Taxe sociale', '6TAXSOC', 'CALCULATED', NULL, '\$tot = (S_B_G) - (AMO + CNSS + CIMR + COT01 + COT02) - IR_N;\r\n\$result = (\$tot>20000 ? \$tot*0.015 : 0);', '100', 0.0000, 100.0000, 7, '0', 0.0000, ".$conf->entity.", NULL),
        // (110, 110, 'AVANCE', 'Avance sur salaire', '7AvanceRet', 'FIX', 1, '', '', 0.0000, 100.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (111, 111, 'RETEN', 'Retenue', '7AvanceRet', 'FIX', NULL, '', '', 0.0000, 100.0000, 3, '', 0.0000, ".$conf->entity.", NULL),
        // (133, 133, 'ARRONDI', 'ARRONDI', '99ARRONDI', 'FIX', 1, '0', '0', 0.0000, 100.0000, 0, '0', 0.0000, ".$conf->entity.", NULL);";


        $sql = "INSERT INTO ".MAIN_DB_PREFIX."paiedolibarr_rules (rowid, numero, code, label, category, amounttype, showdefault, formule_base, formule_taux, amount, ordercalcul, formule, total, entity, showcumul) VALUES

        (1,'1', 'SALARY', 'Salaire de base', '0BASIQUE', 'CALCULATED', 1, 'USER_SALARY ? (USER_SALARY - ((NB_DAYWORK>0) ? (USER_SALARY *NB_DAYABSENCE/NB_DAYWORK) : 0)) : 0', '100', 0, 1, '', 0, ".$conf->entity.", NULL),

        (2,'2', 'PR_A', 'Prime d\'ancienneté', '1BRUT', 'CALCULATED', 0, 'SALARY', '((YEARS_IN_COMPANY < 2) ? 0 : \r\n ((YEARS_IN_COMPANY >= 2 && YEARS_IN_COMPANY < 5) ? 5 : \r\n ((YEARS_IN_COMPANY >= 5 && YEARS_IN_COMPANY < 12) ? 10 : \r\n ((YEARS_IN_COMPANY >= 12 && YEARS_IN_COMPANY < 20) ? 15 : ((YEARS_IN_COMPANY >= 20 && YEARS_IN_COMPANY < 25) ? 20 : 25))\r\n )\r\n )\r\n)', 0, 2, '', 0, ".$conf->entity.", NULL),
        (3,'3', 'PR_B', 'Solde de tout compte', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (4,'4', 'PR_C', 'Prime de rendement', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (5,'5', 'PR_D', 'Prime de 13e mois', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (6,'6', 'PR_E', 'Prime de transport', '1BRUT', 'CALCULATED', 1, '36.112 - ((NB_DAYWORK>0) ? (36.112*NB_DAYABSENCE/NB_DAYWORK) : 0)', '100', 36, 2, '', 0, ".$conf->entity.", NULL),
        (7,'7', 'PR_F', 'Prime de panier', '1BRUT', 'FIX', 1, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (8,'8', 'PR_G', 'Prime Aid El Adha', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (9,'9', 'PR_H', 'Prime de salissure', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (10,'10', 'PR_I', 'Commissions', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (11,'11', 'PR_J', 'Prime de production', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (12,'12', 'PR_K', 'Primes diverses', '1BRUT', 'FIX', 0, '', '', 0, 2, '', 0, ".$conf->entity.", NULL),
        (13,'23', 'IND_A', 'Indemnité de déplacement', '1BRUT', 'FIX', 1, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (14,'24', 'IND_B', 'Indemnité de representation', '1BRUT', 'FIX', 0, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (15,'25', 'IND_C', 'Indemnité kilométrique', '1BRUT', 'FIX', 0, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (16,'26', 'IND_D', 'Indemnité maladie', '1BRUT', 'FIX', 0, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (17,'27', 'IND_E', 'Indemnité rentrée scolaire', '1BRUT', 'FIX', 0, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (18,'55', 'CNSS', 'Retenue CNSS', '2RETENUES', 'CALCULATED', 1, 'S_B_G', '9.18', 0, 5, 'SALAIRE_BASE * (9.18/100)', 0, 1, 1),
        (19,'56', 'AMO', 'AMO', '2RETENUES', 'CALCULATED', 0, 'S_B_I', '2.26', 0, 6, 'SALAIRE_BASE * (2.26/100)', 0, ".$conf->entity.", NULL),
        (20,'57', 'CIMR', 'CIMR', '2RETENUES', 'CALCULATED', 0, 'S_B_I', '3', 0, 6, '', 0, ".$conf->entity.", NULL),
        (21,'58', 'COT01', 'Indemnité de perte d\'emploi', '2RETENUES', 'CALCULATED', 0, '\$result = (S_B_I > 6000) ? 6000 : S_B_I;', '0.19', 0, 6, '', 0, ".$conf->entity.", NULL),
        (22,'59', 'COT02', 'Assurance maladie (mutuelle)', '2RETENUES', 'CALCULATED', 0, '\$result = S_B_I', '2.592', 0, 6, '', 0, ".$conf->entity.", NULL),
        (23,'60', 'FRAIS', 'Frais professionnels', '2RETENUES', 'CALCULATED', 0, '\$result = (S_B_I * 20/100 > 2500) ? 2500 : S_B_I;', '\$result = (S_B_I * 20/100 > 2500) ? 100 : 20;', 0, 6, '', 0, ".$conf->entity.", NULL),
        (24,'77', 'IR_B', 'IR avant charge familiale', '3IRBRUT', 'CALCULATED', 0, '\$SNI = S_N_I;\r\n\$result = ((2501<\$SNI && \$SNI<4166.99) ? (\$SNI*10/100)-250 : \r\n\r\n ((4167<\$SNI && $\SNI<5000.99) ? (\$SNI*20/100)-666.67 : \r\n\r\n ((5001<\$SNI && \$SNI<6666.99) ? (\$SNI*30/100)-1166.67 : \r\n\r\n ((6667<\$SNI && \$SNI<15000.99) ? (\$SNI*34/100)-1433.33 : \r\n\r\n (15000<\$SNI ? (\$SNI*38/100)-2033.33: 0)\r\n )\r\n )\r\n )\r\n);', '100', 0, 7, '0', 0, ".$conf->entity.", NULL),
        (25,'90', 'CHARGE', 'Charges de famille', '4CHARGEF', 'CALCULATED', 0, '\$s_marie = (\'SITU_FAMILIALE\' == \'M\') ? 30 : 0;\r\n\$s_enfant = (NB_ENFANTS > 5 ? 150 : (30*NB_ENFANTS));\r\n\$result = \$s_marie + \$s_enfant;', '100', 0, 7, '0', 0, ".$conf->entity.", NULL),
        (26,'95', 'IR_N', 'IR NET', '5IRNET', 'CALCULATED', 0, '(IR_B>CHARGE) ? (IR_B-CHARGE) : 0', '100', 0, 7, '0', 0, ".$conf->entity.", NULL),
        (27,'100', 'TAX', 'Taxe sociale', '6TAXSOC', 'CALCULATED', 0, '\$tot = (S_B_G) - (AMO + CNSS + CIMR + COT01 + COT02) - IR_N;\r\n\$result = (\$tot>20000 ? $tot*0.015 : 0);', '100', 0, 7, '0', 0, ".$conf->entity.", NULL),
        (28,'110', 'AVANCE', 'Avance sur salaire', '7AvanceRet', 'FIX', 0, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (29,'111', 'RETEN', 'Retenue', '7AvanceRet', 'FIX', 0, '', '', 0, 3, '', 0, ".$conf->entity.", NULL),
        (30,'133', 'ARRONDI', 'ARRONDI', '99ARRONDI', 'CALCULATED', 0, '0', '0', 0, 0, '0', 0, ".$conf->entity.", NULL),
        (31,'100', 'PR_P', 'Prime de présence', '1BRUT', 'CALCULATED', 1, '2.080 - ((NB_DAYWORK>0) ? (2.080*NB_DAYABSENCE/NB_DAYWORK) : 0)', '100', 0, 0, '', 0, ".$conf->entity.", NULL),
        (32,'120', 'IF', 'Indemnité de fonction', '1BRUT', 'CALCULATED', 1, '2712.697 - ((NB_DAYWORK>0) ? (2712.697*NB_DAYABSENCE/NB_DAYWORK) : 0)', '100', 0, 0, '', 0, ".$conf->entity.", NULL),
        (33,'130', 'TR', 'Ticket de restaurant', '1BRUT', 'CALCULATED', 1, '', '100', 0, 0, '', 0, ".$conf->entity.", NULL),
        (34,'30', 'IRPP', 'Retenue Impôt', '5IRNET', 'CALCULATED', 1, '\$retenu1= ((\'SITU_FAMILIALE\' != \'C\') ? 300 : 0)+((NB_ENFANTS <= 4) ? NB_ENFANTS*100 : 0);\r\n\$imposable1 = (4210.389*NB_MONTHINYEAR) - ((4210.389*NB_MONTHINYEAR)*9.18/100);\r\n\$abbat = (\$imposable1>0 && (\$imposable1*0.1) < 2000) ? (\$imposable1*0.1) : 2000;\r\n\$imposable2 = \$imposable1 - (\$retenu1 + \$abbat);\r\n\$totba = ((\$imposable2 > 5000) ? ((\$imposable2 < 20000) ? ((\$imposable2 - 5000) * 0.26) : 3900) : 0);\r\n\$totbb = \$totba + ((\$imposable2 > 20000) ? ((\$imposable2 < 30000) ? ((\$imposable2 - 20000) * 0.28) : 2800) : 0);\r\n\$totbc = \$totbb + ((\$imposable2 > 30000) ? ((\$imposable2 < 50000) ? ((\$imposable2 - 30000) * 0.32) : 6200) : 0);\r\n\$totb = \$totbc + ((\$imposable2 > 50000) ? ((\$imposable2 - 50000) * 0.35) : 0);\r\n \$result = NB_MONTHINYEAR ? (\$totb/NB_MONTHINYEAR) : (\$totb/9);', '100', 0, 0, '', 0, ".$conf->entity.", NULL),
        (35,'140', 'CSS', 'Contribution Sociale Solidaire', '7AvanceRet', 'CALCULATED', 1, '\$retenu1= ((\'SITU_FAMILIALE\' != \'C\') ? 300 : 0)+((NB_ENFANTS <= 4) ? NB_ENFANTS*100 : 0);\r\n\$imposable1 = (4210.389*NB_MONTHINYEAR) - ((4210.389*NB_MONTHINYEAR)*9.18/100);\r\n\$abbat = ((\$imposable1*0.1) < 2000) ? (\$imposable1*0.1) : 2000;\r\n\$imposable2 = \$imposable1 - (\$retenu1 + \$abbat);\r\n\$totaa = (\$imposable2 < 5000) ? 0 : 50;\r\n\$totab = \$totaa+((\$imposable2 > 5000) ? ((\$imposable2 < 20000) ? ((\$imposable2 - 5000) * 0.27) : 4050) : 0);\r\n\$totac = \$totab+((\$imposable2 > 20000) ? ((\$imposable2 < 30000) ? ((\$imposable2 - 20000) * 0.29) : 2900) : 0);\r\n\$totad = \$totac+((\$imposable2 > 30000) ? ((\$imposable2 < 50000) ? ((\$imposable2 - 30000) * 0.33) : 6600) : 0);\r\n\$tota = \$totad+((\$imposable2 > 50000) ? ((\$imposable2 - 50000) * 0.36) : 0);\r\n\r\n\$totba = ((\$imposable2 > 5000) ? ((\$imposable2 < 20000) ? ((\$imposable2 - 5000) * 0.26) : 3900) : 0);\r\n\$totbb = \$totba + ((\$imposable2 > 20000) ? ((\$imposable2 < 30000) ? ((\$imposable2 - 20000) * 0.28) : 2800) : 0);\r\n\$totbc = \$totbb + ((\$imposable2 > 30000) ? ((\$imposable2 < 50000) ? ((\$imposable2 - 30000) * 0.32) : 6200) : 0);\r\n\$totb = \$totbc + ((\$imposable2 > 50000) ? ((\$imposable2 - 50000) * 0.35) : 0);\r\n\r\n\$result = NB_MONTHINYEAR ? ((\$tota - \$totb)/2)/NB_MONTHINYEAR : ((\$tota - \$totb)/2)/9;', '100', 0, 0, '', 0, ".$conf->entity.", NULL);";


        $resql = $this->db->query($sql);
        // if(!$resql){
        //     print $this->db->lasterror();
        //     die();
        // }
        return 1;

    }

    public function showNavigations($object, $linkback, $paramid = 'id', $fieldid = 'rowid', $moreparam = '')
    {

        global $langs, $conf;

        $ret = $result = '';
        $previous_ref = $next_ref = '';

        $fieldref = $fieldid;

        $oldref = ''; if($object->ref) $oldref = $object->ref;

        $object->ref = $object->rowid;

        $object->load_previous_next_ref(' AND entity ='.$conf->entity, $fieldid, 0);

        $navurl = $_SERVER["PHP_SELF"];

        $page = GETPOST('page');

        $stringforfirstkey = '';
        // // accesskey is for Windows or Linux:  ALT + key for chrome, ALT + SHIFT + KEY for firefox
        // // accesskey is for Mac:               CTRL + key for all browsers
        // $stringforfirstkey = $langs->trans("KeyboardShortcut");
        // if ($conf->browser->name == 'chrome')
        // {
        //     $stringforfirstkey .= ' ALT +';
        // }
        // elseif ($conf->browser->name == 'firefox')
        // {
        //     $stringforfirstkey .= ' ALT + SHIFT +';
        // }
        // else
        // {
        //     $stringforfirstkey .= ' CTL +';
        // }
        // $stringforfirstkeyp .= $stringforfirstkey.' p';
        // $stringforfirstkeyn .= $stringforfirstkey.' n';

        $stringforfirstkeyp = '';
        $stringforfirstkeyn = '';

        $previous_ref = $object->ref_previous ? '<a accesskey="p" title="'.$stringforfirstkeyp.'" class="classfortooltip" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_previous).$moreparam.'"><i class="fa fa-chevron-left"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-left opacitymedium"></i></span>';
        
        $next_ref     = $object->ref_next ? '<a accesskey="n" title="'.$stringforfirstkeyn.'" class="classfortooltip" href="'.$navurl.'?'.$paramid.'='.urlencode($object->ref_next).$moreparam.'"><i class="fa fa-chevron-right"></i></a>' : '<span class="inactive"><i class="fa fa-chevron-right opacitymedium"></i></span>';

        $ret = '';
        // print "xx".$previous_ref."x".$next_ref;
        $ret .= '<!-- Start banner content --><div style="vertical-align: middle;width:100%;display:inline-block;">';


        if ($previous_ref || $next_ref || $linkback)
        {
            $ret .= '<div class="pagination paginationref"><ul class="right">';
        }
        if ($linkback)
        {
            $ret .= '<li class="noborder litext">'.$linkback.'</li>';
        }
        if (($previous_ref || $next_ref))
        {
            $ret .= '<li class="pagination">'.$previous_ref.'</li>';
            $ret .= '<li class="pagination">'.$next_ref.'</li>';
        }
        if ($previous_ref || $next_ref || $linkback)
        {
            $ret .= '</ul></div>';
        }

        // $result .= '<div>';
        $result .= $ret;
        // $result .= '</div>';
        $result .= '</div>';

        if($oldref) $object->ref = $oldref;

        return $result;
    }

    
    public function getSelectPaieDolibarrModel($slctd='',$name='paiedolibarrmodel', $showempty=0, $disabled='', $morecss = 'width200')
    {
        global $langs;
        $paiedolibarrmodel = $this->paiedolibarrmodel;
        $select ='<select class="select_'.$name.' '.$morecss.'" name="'.$name.'" '.$disabled.'>';
            foreach ($paiedolibarrmodel as $keyr => $namer) {

                $slctdt = ($keyr == $slctd) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$namer.'</option>';
            }
        $select .='</select>';

        return $select;
    }

    function number_format($number, $aftercomma=2, $comma=',',$space=' ')
    {
        global $conf, $langs;

        $outlangs = $langs;

        $dec = $comma;
        $thousand = ' ';
        // $aftercomma = 2;
        $aftercomma = isset($conf->global->PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT) ? (int) $conf->global->PAIE_NUMBER_OF_DIGITS_AFTER_THE_DECIMAL_POINT : 2;

        $number = $number ? str_replace(',', '.', $number) : 0;
        if ($outlangs->transnoentitiesnoconv("SeparatorDecimal") != "SeparatorDecimal") {
            $dec = $outlangs->transnoentitiesnoconv("SeparatorDecimal");
        }
        if ($outlangs->transnoentitiesnoconv("SeparatorThousand") != "SeparatorThousand") {
            $space = $outlangs->transnoentitiesnoconv("SeparatorThousand");
        }
        if ($space == 'None') {
            $space = ' ';
        } elseif ($space == 'Space') {
            $space = ' ';
        }

        $nbrform = number_format($number, $aftercomma, $dec, $space);
        $nbrform = preg_replace('/\s/', '&nbsp;', $nbrform);
        $nbrform = preg_replace('/\'/', '&#039;', $nbrform);

        return $nbrform;
    }

    function employeeinfo($fk_paie, $lastday=null)
    {
        global $langs;

        $paie  = new paiedolibarr_paies($this->db);
        $paie->fetch($fk_paie);

        $employee = new User($this->db);
        $employee->fetch($paie->fk_user);

        $account = new UserBankAccount($this->db);
        $account->fetch(0, '', $paie->fk_user);

        $results = array();

        $results['situation_f']  = $paie->situation_f;
        $results['nbrenfants']   = $paie->nbrenfants;
        $results['partigr']      = $paie->partigr;
        $results['categorie']    = $paie->categorie;
        $results['qualification']= $paie->qualification;
        $results['zone']         = $paie->zone;
        $results['echelon']      = $paie->echelon;
        $results['niveau']       = $paie->niveau;
        $results['matricule']    = $paie->matricule;

        $results['service']      = '';
        
        $results['ibanrib']     = '';
        $results['anciennete']  = '';
        $results['job']         = '';
        $results['adresse']     = '';
        $results['cnss']        = '';
        $results['cimr']        = '';
        $results['cin']         = '';
        $results['nd']          = 0;
        
        $results['name'] = $employee->lastname.' '.$employee->firstname;
        $results['birth'] = $employee->birth;
        $results['dateemployment'] = $employee->dateemployment;
        $results['departement'] = $employee->state;

        if ($account->id > 0)
            $results['ibanrib'] = $account->iban;

        $results['adresse'] = $langs->convToOutputCharset(dol_format_address($employee, 1, "<br>", $langs));

        $results['phone_pro'] = $employee->phone_pro;
        $results['phone_perso'] = $employee->phone_perso;
        $results['phone_mobile'] = $employee->phone_mobile;

        $results['entree'] = date('d/m/Y');

        if($paie->situation_f == 'M') $results['nd']++;

        $results['nd'] += $paie->nbrenfants;

        if($employee->dateemployment){

            $date1 = strtotime($lastday);  
            $date2 = $employee->dateemployment;  
              
            $diff = abs($date2 - $date1);
            $years = floor($diff / (365*60*60*24));
            $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
            $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)); 

     
            $ancie = '';
            $ancie .= $years.' ';

            $yt = strtolower($langs->trans('Years'));
            $ancie .= substr(($yt), 0, 2).'(s)';

            if($months > 0){
                $ancie .= ', '.$months.' ';
                if($months > 1)
                    $ancie .= strtolower($langs->trans('Months'));
                else
                    $ancie .= strtolower($langs->trans('Month'));
            }

            if($days > 0){
                $ancie .= ', '.$days.' ';
                if($days > 1)
                    $ancie .= strtolower($langs->trans('Days'));
                else
                    $ancie .= strtolower($langs->trans('Day'));
            }

            $results['anciennete'] = $ancie;
        }

        if(isset($employee->array_options['options_paiedolibarrcnss'])) {
            $results['cnss'] = $employee->array_options['options_paiedolibarrcnss'];
        }
        if(isset($employee->array_options['options_paiedolibarrcimr'])) {
            $results['cimr'] = $employee->array_options['options_paiedolibarrcimr'];
        }
        if(isset($employee->array_options['options_paiedolibarrcin'])) {
            $results['cin'] = $employee->array_options['options_paiedolibarrcin'];
        }
        if(isset($employee->array_options['options_paiedolibarrmatricule'])) {
            $results['matricule'] = $employee->array_options['options_paiedolibarrmatricule'];
        }
        if($employee->job) {
            $results['job'] = $employee->job;
        }

        return $results;
    }

    function employeeinfo_old($fk_user, $lastday=null)
    {
        global $langs;
        $employee = new User($this->db);
        $employee->fetch($fk_user);


        $account = new UserBankAccount($this->db);
        $account->fetch(0, '', $fk_user);


        $results = array();
        $results['matricule'] = $results['zone'] = $results['categorie'] = $results['situation_f'] = $results['echelon'] = $results['cnss'] = $results['niveau'] = $results['anciennete'] = $results['job'] = $results['adresse'] = $results['nbrenfants'] = $results['partigr'] = $results['qualification'] = $results['ibanrib'] = '';
        $results['name'] = $employee->lastname.' '.$employee->firstname;
        $results['birth'] = $employee->birth;
        $results['dateemployment'] = $employee->dateemployment;

        $results['situation_f'] = 'C';
        if($employee->array_options){
            $dt = $employee->array_options;
            if(isset($dt['options_paiedolibarrmatricule'])){
                $results['matricule'] = $dt['options_paiedolibarrmatricule'];
            }
            if(isset($dt['options_paiedolibarrzone'])){
                $results['zone'] = $dt['options_paiedolibarrzone'];
            }
            if(isset($dt['options_paiedolibarrcategorie'])){
                $results['categorie'] = $dt['options_paiedolibarrcategorie'];
            }
            if(isset($dt['options_paiedolibarrechelon'])){
                $results['echelon'] = $dt['options_paiedolibarrechelon'];
            }
            if(isset($dt['options_paiecnss'])){
                $results['cnss'] = $dt['options_paiecnss'];
            }
            if(isset($dt['options_paieniveau'])){
                $results['niveau'] = $dt['options_paieniveau'];
            }
            if(isset($dt['options_paiedolibarrsituation_f']) && !empty($dt['options_paiedolibarrsituation_f'])){
                $results['situation_f'] = $dt['options_paiedolibarrsituation_f'];
            }
            if(isset($dt['options_paiedolibarrnbrenfants'])){
                $results['nbrenfants'] = $dt['options_paiedolibarrnbrenfants'];
            }
            if(isset($dt['options_paiedolibarrpartigr'])){
                $results['partigr'] = $dt['options_paiedolibarrpartigr'];
            }
            if(isset($dt['options_paiedolibarrqualification'])){
                $results['qualification'] = $dt['options_paiedolibarrqualification'];
            }
        }

        if ($account->id > 0)
            $results['ibanrib'] = $account->iban;

        $results['adresse'] = $langs->convToOutputCharset(dol_format_address($employee, 1, "<br>", $langs));


        $results['entree'] = date('d/m/Y');


        if($employee->dateemployment){

            $date1 = strtotime($lastday);  
            $date2 = $employee->dateemployment;  
              
            $diff = abs($date2 - $date1);
            $years = floor($diff / (365*60*60*24));
            $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));
            $days = floor(($diff - $years * 365*60*60*24 - $months*30*60*60*24)/ (60*60*24)); 

     
            $ancie = '';
            $ancie .= $years.' ';

            $yt = strtolower($langs->trans('Years'));
            $ancie .= substr(($yt), 0, 2).'(s)';

            if($months > 0){
                $ancie .= ', '.$months.' ';
                if($months > 1)
                    $ancie .= strtolower($langs->trans('Months'));
                else
                    $ancie .= strtolower($langs->trans('Month'));
            }

            if($days > 0){
                $ancie .= ', '.$days.' ';
                if($days > 1)
                    $ancie .= strtolower($langs->trans('Days'));
                else
                    $ancie .= strtolower($langs->trans('Day'));
            }

            $results['anciennete'] = $ancie;
        }

        if($employee->job)
            $results['job'] = $employee->job;





        // print_r($employee);die;

        return $results;
    }

    public function getExcludedUsers($period)
    {
      global $conf;
        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."paiedolibarr_paies WHERE 1>0";
        $sql .= " AND period = '".$period."'";
        $sql .= " AND entity = '".$conf->entity."'";
        $resql = $this->db->query($sql);
        // echo $sql;
        $users = array();
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                if($obj->fk_user)
                    $users[] = $obj->fk_user;
            }
        }

        
        return $users;
    }

    /**
     *      Return a string with full address formated for output on documents
     *
     *      @param  Translate             $outputlangs          Output langs object
     *      @param  Societe               $sourcecompany        Source company object
     *      @param  Societe|string|null   $targetcompany        Target company object
     *      @param  Contact|string|null   $targetcontact        Target contact object
     *      @param  int                   $usecontact           Use contact instead of company
     *      @param  string                $mode                 Address type ('source', 'target', 'targetwithdetails', 'targetwithdetails_xxx': target but include also phone/fax/email/url)
     *      @param  Object                $object               Object we want to build document for
     *      @return string                                      String with full address
     */
    function pdf_build_address($outputlangs, $sourcecompany, $targetcompany = '', $targetcontact = '', $usecontact = 0, $mode = 'source', $object = null)
    {
        global $conf, $hookmanager;

        $outputlangs->loadLangs(array("main", "propal", "companies", "bills"));

        if ($mode == 'source' && !is_object($sourcecompany)) return -1;
        if ($mode == 'target' && !is_object($targetcompany)) return -1;

        if (!empty($sourcecompany->state_id) && empty($sourcecompany->state))             $sourcecompany->state = getState($sourcecompany->state_id);
        if (!empty($targetcompany->state_id) && empty($targetcompany->state))             $targetcompany->state = getState($targetcompany->state_id);

        $reshook = 0;
        $stringaddress = '';
        
        if ($mode == 'source')
        {
            $withCountry = 0;
            if (!empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) $withCountry = 1;

            $stringaddress .= ($stringaddress ? "<br>" : '').$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, ", ", $outputlangs))."<br>";

            // if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS))
            // {
            //     // Phone
            //     if ($sourcecompany->phone) $stringaddress .= ($stringaddress ? "<br>" : '').$outputlangs->transnoentities("PhoneShort").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
            //     // Fax
            //     if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? ($sourcecompany->phone ? " - " : "<br>") : '').$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
            //     // EMail
            //     if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "<br>" : '').$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
            //     // Web
            //     if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "<br>" : '').$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
            // }
            // Intra VAT
            // if (!empty($conf->global->MAIN_TVAINTRA_IN_SOURCE_ADDRESS))
            // {
                if ($sourcecompany->tva_intra) $stringaddress .= ($stringaddress ? "<br>" : '').$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($sourcecompany->tva_intra);
            // }
            // Professionnal Ids
            $reg = array();
            if ((!empty($conf->global->MAIN_PROFID1_IN_SOURCE_ADDRESS) || 1>0) && !empty($sourcecompany->idprof1))
            {
                $tmp = $outputlangs->transcountrynoentities("ProfId1", $sourcecompany->country_code);
                if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
                $stringaddress .= ($stringaddress ? "<br>" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof1);
            }
            if ((!empty($conf->global->MAIN_PROFID2_IN_SOURCE_ADDRESS) || 1>0) && !empty($sourcecompany->idprof2))
            {
                $tmp = $outputlangs->transcountrynoentities("ProfId2", $sourcecompany->country_code);
                if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
                $stringaddress .= ($stringaddress ? "<br>" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof2);
            }
            if ((!empty($conf->global->MAIN_PROFID3_IN_SOURCE_ADDRESS) || 1>0) && !empty($sourcecompany->idprof3))
            {
                $tmp = $outputlangs->transcountrynoentities("ProfId3", $sourcecompany->country_code);
                if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
                $stringaddress .= ($stringaddress ? "<br>" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof3);
            }
            if ((!empty($conf->global->MAIN_PROFID4_IN_SOURCE_ADDRESS) || 1>0) && !empty($sourcecompany->idprof4))
            {
                $tmp = $outputlangs->transcountrynoentities("ProfId4", $sourcecompany->country_code);
                if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
                $stringaddress .= ($stringaddress ? "<br>" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof4);
            }
            if ((!empty($conf->global->MAIN_PROFID5_IN_SOURCE_ADDRESS) || 1>0) && !empty($sourcecompany->idprof5))
            {
                $tmp = $outputlangs->transcountrynoentities("ProfId5", $sourcecompany->country_code);
                if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
                $stringaddress .= ($stringaddress ? "<br>" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof5);
            }
            if ((!empty($conf->global->MAIN_PROFID6_IN_SOURCE_ADDRESS) || 1>0) && !empty($sourcecompany->idprof6))
            {
                $tmp = $outputlangs->transcountrynoentities("ProfId6", $sourcecompany->country_code);
                if (preg_match('/\((.+)\)/', $tmp, $reg)) $tmp = $reg[1];
                $stringaddress .= ($stringaddress ? "<br>" : '').$tmp.': '.$outputlangs->convToOutputCharset($sourcecompany->idprof6);
            }
            if (!empty($conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS)) {
                $stringaddress .= ($stringaddress ? "<br>" : '').$conf->global->PDF_ADD_MORE_AFTER_SOURCE_ADDRESS;
            }
        }

        return $stringaddress;
    }
   


    public function getDataToSend2($link, $token, $decodejsn = true){
        $ch = curl_init();
        $link = trim($link, '/');
        curl_setopt($ch, CURLOPT_URL, $link.'/api/index.php/dolibarrmobileapi/'.$toget);
        // print_r($result);die();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $headers = array();
        $headers[] = 'Accept: application/json';
        $headers[] = 'Dolapikey: '.$token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $result = curl_exec($ch);
        // if (curl_errno($ch)) return -1;
        if($decodejsn) $result = json_decode($result, true);
        // print_r($result);die();
        curl_close($ch);
        return $result;
    }


    public function selectdegres($name="degre", $value='', $id="", $showempty=0)
    {
        $select = '<select name="'.$name.'" id="'.($id ? $id : $name).'">';
            if($showempty) $select .= '<option value="0"></option>';
            for ($i=1; $i <= 10; $i++) { 
                $seleted = ($value == $i) ? 'selected' : '';
                $select .= '<option value="'.$i.'">'.$i.'</option>';
            }
        $select .= '</select>';

        return $select;
    }

    public function selectyears($name="year", $value='', $showempty=0)
    {
        global $conf, $langs;

        $max = ($conf->global->PAIEDOLIBARR_YEARSOFPOSITION ? $conf->global->PAIEDOLIBARR_YEARSOFPOSITION : 8);
        
        $select = '<select name="'.$name.'" id="'.($id ? $id : $name).'">';
            if($showempty) $select .= '<option value="0"></option>';

            for ($i=1; $i <= $max; $i++) { 
                $seleted = ($value == $i) ? 'selected' : '';
                $select .= '<option value="'.$i.'">'.$langs->trans('Year').' '.$i.'</option>';
            }
        $select .= '</select>';

        return $select;
    }

    public function getSalaryByDegre($fk_user)
    {
        $salary=0;

        $sql = 'SELECT TIMESTAMPDIFF(month, u.dateemployment, now()) as duree, s.amount ';
        $sql .= ' FROM llx_paiedolibarr_salaryscall as s , llx_user as u';
        $sql .= ' LEFT JOIN llx_user_extrafields as ef ON ef.fk_object=u.rowid';
        $sql .= ' WHERE u.rowid='.$fk_user.' AND (TIMESTAMPDIFF(month, u.dateemployment, now()) > (s.year-1)*12 ) AND (TIMESTAMPDIFF(month, u.dateemployment, now()) <= (s.year*12)) AND s.degre=ef.paiedolibarr_scall';
        $res = $this->db->query($sql);

        if($res){
            while ($obj = $this->db->fetch_object($res)) {
                $salary = $obj->amount;
            }
        }

        return $salary;
    }

}

?>