<?php 
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_paiesrules.class.php');


class paiedolibarr_paies extends Commonobject{ 

    public $error = '';
    public $errors = array();
    public $rowid;
    public $period;
    public $fk_user;
    public $ref;
    public $label;
    public $comment;
    public $amounttype;
    public $amount;
    public $formule;
    public $total;
    public $salairebrut;
    public $salairenet;
    public $retenus;
    public $netapayer;
    public $entity;


    public $datepay;
    public $matricule;
    public $mode_reglement_id;
    public $nbdaywork;
    public $nbdayabsence;
    public $nbrenfants;
    public $situation_f;
    public $certificat;
    public $worksite;
    public $fk_author;
    public $fk_lastedit;
    public $tms;

    public $partigr;
    public $categorie;
    public $qualification;
    public $zone;
    public $echelon;
    public $niveau;
    
    public $element='paiedolibarr_paies';
    public $table_element='paiedolibarr_paies';

    public $picto = 'order';

    public $afterretenus;

    public function __construct($db){ 
        global $langs;

        $this->db = $db;

        $this->gains = [
            '0BASIQUE'          => 1,
            '1BRUT'             => 1,
            '4CHARGEF'          => 1,
            '8NET'              => 1,
            '99ARRONDI'         => 1,
        ];
        $this->retenues = [
            '2RETENUES'         => 1,
            '3IRBRUT'           => 1,
            '5IRNET'            => 1,
            '6TAXSOC'           => 1,
            '7AvanceRet'        => 1,
        ];

        $this->rulescategory = [
            '0BASIQUE'          => $langs->trans('BASIQUE'),
            '1BRUT'             => $langs->trans('BRUT'),
            '2RETENUES'         => $langs->trans('RETENUES'),
            '3IRBRUT'           => $langs->trans('IRBrut'),
            '4CHARGEF'          => $langs->trans('ChargeFamille'),
            '5IRNET'            => $langs->trans('IRNet'),
            '6TAXSOC'           => $langs->trans('TaxSociale'),
            '7AvanceRet'        => $langs->trans('AvanceRetenue'),
            '8NET'              => $langs->trans('NET'),
            '99ARRONDI'         => $langs->trans('Arrondi'),
            // '7OTHER'             => $langs->trans('Other'),
        ];

        $this->afterretenus = ['3IRBRUT' => 1,'4CHARGEF' => 1,'5IRNET' => 1,'6TAXSOC' => 1,'8NET' => 1,'99ARRONDI' => 1];

        // $this->rulescategory = [
        //     'BASIQUE'           => $langs->trans('BASIQUE'),
        //     'BRUT'              => $langs->trans('BRUT'),
        //     'RETENUES'          => $langs->trans('RETENUES'),
        //     'INDEMNITES'        => $langs->trans('INDEMNITES'),
        //     'AUTRESRETENUES'    => $langs->trans('AUTRESRETENUES')
        //     'OTHER'             => $langs->trans('Other'),
        // ];

        $this->situation_fs = array( 'M' => $langs->trans('paieMarie'), 'C' => $langs->trans('paieCelibataire'), 'D' => $langs->trans('paieDivorce') );
        
        return 1;
    }





    public function mergeNewAndOldPaieRules($fk_paie, $newrules=0)
    {
        $payrules = array();


        $sql = "SELECT rowid, label, amount, taux, amounttype, numero, code, formule_base, formule_taux, category, 'old' as type FROM ".MAIN_DB_PREFIX."paiedolibarr_paiesrules ";
        $sql .= " WHERE 1 = 1";
        $sql .= " AND fk_paie = ".$fk_paie;

        $sql .= " UNION ";

        $sql .= "SELECT rowid, label, amount, taux, amounttype, numero, code, formule_base, formule_taux, category, 'new' as type FROM ".MAIN_DB_PREFIX."paiedolibarr_rules ";
        $sql .= " WHERE rowid IN (".$newrules.") ";
        $sql .= " ORDER BY category,rowid ASC";

        // echo $sql;
        $resql = $this->db->query($sql);

        $rows = array();

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass;
                $line->rowid            = $obj->rowid;
                // $line->fk_paie          = $obj->fk_paie;
                $line->numero           = $obj->numero;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amounttype       = $obj->amounttype;
                $line->amount           = $obj->amount;
                $line->taux             = $obj->taux;
                $line->formule          = $obj->formule;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                $line->total            = $obj->total;

                $line = (array) $line;

                $tmpid = ($obj->type == 'new') ? 'new_'.$obj->rowid : $obj->rowid;

                $payrules[$tmpid] = $line;
            }
        }

        return $payrules;
    }

    public function editcalculatePaieRules($payrules=array(), $deletedRules = array())
    {
        global $user, $conf;

        $arrondiauto = $conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO;
        $paierule = new paiedolibarr_paiesrules($this->db);

        // ------------------------------------------------------------------------------------------------------------
        global $substitutionarray, $payrules, $checkifcodeexist;
        // ------------------------------------------------------------------------------------------------------------
        
        $payrules = is_array($payrules) ? $payrules : array();

        $substitutionarray = array();
        $this->getOtherCodesValues($substitutionarray, 'edit');


        // ------------------------------------------------------------------------------------------------------------
        $checkifcodeexist = true;
        $this->getRules($filter = '', );
        // ------------------------------------------------------------------------------------------------------------

        $fk_paie = $this->id;


        $arrondiexist = false;



        $sbi_calc = false;
        $sni_calc = false;

        $deletedRules = is_array($deletedRules) ? $deletedRules : array();
        $deletedRules = array_filter(array_map('intval', $deletedRules));
        $deletedRulesMap = $deletedRules ? array_flip($deletedRules) : array();

        $idsnottodelete = '0';
        foreach ($payrules as $key => $rule) {

            if(is_numeric($key) && isset($deletedRulesMap[(int) $key])) {
                continue;
            }

            $rule = (object) $rule;

            if(is_numeric($key)) $idsnottodelete .= ','.$key;

            if($rule->code == 'SALARY') {
                // $substitutionarray['USER_SALARY'] = $rule->total;
            }
            
            $this->calc_SBG_SBI_SNI($substitutionarray, $rule->category, $sbi_calc, $sni_calc);

            // $total = round($rule->total, 4);
            $amount = $rule->amount;
            $taux   = $rule->taux;


            if($rule->amounttype == 'CALCULATED') {
                // Base
                $result = 0;
                $str = make_substitutions($rule->formule_base, $substitutionarray);
                $amount = $this->executeEvalFunction($this, $str);
                $result = 0;
                $str = make_substitutions($rule->formule_taux, $substitutionarray);
                $taux = $this->executeEvalFunction($this, $str);
            }


            if($rule->code == 'SALARY') {
                $total = (float)$amount;
            } else {
                if($taux>0){
                    $total = (float)$amount*((float)($taux/100));
                }
            }

            $substitutionarray[$rule->code] = $total;

            $dr = array(
                'label'             => addslashes($rule->label)
                ,'amount'           => $amount
                ,'taux'             => $taux
                ,'total'            => $total
            );

            if($arrondiauto && $rule->category == '99ARRONDI') {
                $dr['amount'] = 0; $dr['taux'] = 0; $dr['total'] = 0;
                $substitutionarray[$rule->code] = 0;
                $arrondiexist = true;
            }

            if(is_numeric($key)) {
                $isr = $paierule->update($key, $dr);

            } else {
                $dr['numero']       = $rule->numero;
                $dr['code']         = $rule->code;
                $dr['amounttype']   = $rule->amounttype;
                $dr['formule_base'] = $this->db->escape($rule->formule_base);
                $dr['formule_taux'] = $this->db->escape($rule->formule_taux);
                $dr['fk_paie']      = $this->id;
                $dr['category']     = $rule->category;

                $lastid = $paierule->create($dr);
                $idsnottodelete .= ','.$lastid;
            }
        }
        $sql = 'DELETE FROM `'.MAIN_DB_PREFIX.'paiedolibarr_paiesrules` WHERE fk_paie = '.(int)$this->id.' AND rowid NOT IN ('.$idsnottodelete.')';
        $resql = $this->db->query($sql);

        if($payrules || $deletedRules){
            $data = $this->getNetBrutNetAPayer($fk_paie, $arrondiexist, $substitutionarray);
            $isvalid = $this->update($fk_paie, $data);
        }

    }

    public function calc_SBG_SBI_SNI(&$substitutionarray, $category, &$sbi_calc, &$sni_calc)
    {
        global $conf;
        $formsalairebrut    = isset($conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT) ? $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT : ''; // S_B_G
        $formsalairebimpo   = isset($conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO) ? $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO : ''; // S_B_I : Brut imposable
        $formsalairenet     = isset($conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIRENET) ? $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIRENET : ''; // S_N_I : Net imposable
        $formretenus        = isset($conf->global->PAIEDOLIBARR_FORM_CALCUL_RETENUS) ? $conf->global->PAIEDOLIBARR_FORM_CALCUL_RETENUS : '';
        $formnetapayer      = isset($conf->global->PAIEDOLIBARR_FORM_CALCUL_NETAPAYER) ? $conf->global->PAIEDOLIBARR_FORM_CALCUL_NETAPAYER : '';

        // $str = make_substitutions($formretenus, $substitutionarray);
        // $substitutionarray['RETENUES'] = (float) $this->executeEvalFunction($this, $str);

        if($category == '2RETENUES' && !$sbi_calc) {
            $str = make_substitutions($formsalairebrut, $substitutionarray);
            // echo('str 1: '.$str."<br>");
            $substitutionarray['S_B_G'] = (float) $this->executeEvalFunction($this, $str);

            $str = make_substitutions($formsalairebimpo, $substitutionarray);
            // echo('str 2: '.$str."<br>");
            $substitutionarray['S_B_I'] = (float) $this->executeEvalFunction($this, $str);
            $sbi_calc = true;
        }
        elseif($category != '2RETENUES' && $sbi_calc && !$sni_calc) {
            $str = make_substitutions($formsalairenet, $substitutionarray);
            // echo('str 3: '.$str."<br>");

            $substitutionarray['S_N_I'] = (float) $this->executeEvalFunction($this, $str);
                
            // d($substitutionarray);
            $sni_calc = true;
        }
    }

    public function getOtherCodesValues(&$substitutionarray, $action = 'create')
    {
        global $conf;
        $substitutionarray['YEARS_IN_COMPANY'] = 0;

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields    = new ExtraFields($this->db);
        $extrafields->fetch_name_optionals_label($this->table_element);

        $employee = new User($this->db);
        $employee->fetch($this->fk_user);

        $nbdaywork = GETPOST('nbdaywork', 'int');
        $nbdayabsence = GETPOST('nbdayabsence', 'int');
        $nbrenfants = GETPOST('nbrenfants', 'int');
        $situation_f = GETPOST('situation_f', 'alphanohtml');
        // $jobpatronal = $conf->global->PAIEDOLIBARR_JOB_PATRONAL;
        // $certificat = GETPOST('certificat', 'alpha');
        // $worksite = GETPOST('worksite', 'alpha');

        if($employee){
            $userjob = $employee->job;

            $salary = $employee->salary ? $employee->salary : ($employee->tjm ? ($employee->tjm*26) : 0);
            $substitutionarray['USER_SALARY'] = (float) $salary;
            if($action == 'create') {
                // $substitutionarray['USER_JOB'] = $employee->job;

                if(isset($employee->array_options['options_paiedolibarrnbrenfants'])) {
                    $nbrenfants = $employee->array_options['options_paiedolibarrnbrenfants'];
                }if(isset($employee->array_options['options_paiedolibarrnbdaywork'])) {
                    $nbdaywork = $employee->array_options['options_paiedolibarrnbdaywork'];
                }
                if(isset($employee->array_options['options_paiedolibarrsituation_f'])) {
                    $situation_f = $employee->array_options['options_paiedolibarrsituation_f'];
                }
                // if(isset($employee->array_options['options_paiedolibarrworksite'])) {
                //     $worksite = $employee->array_options['options_paiedolibarrworksite'];
                // }

            }
            elseif($action == 'duplicate') {
                $nbdayabsence = $this->nbdayabsence;
                $nbdaywork = $this->nbdaywork;
                $nbrenfants = $this->nbrenfants;
                $situation_f = $this->situation_f;
                $certificat = $this->certificat;
                $worksite = $this->worksite;
            }

            $yearincomp = $this->getYearsInCompany($this, $employee->dateemployment);
            $substitutionarray['YEARS_IN_COMPANY'] = $yearincomp;
        }
        $paiedolibarrnbmonth = $conf->global->PAIEDOLIBARR_PAIE_NBMONTH ? $conf->global->PAIEDOLIBARR_PAIE_NBMONTH : '9';

        $substitutionarray['NB_MONTHINYEAR'] = (int) $paiedolibarrnbmonth;
        $substitutionarray['NB_DAYABSENCE'] = (int) $nbdayabsence;
        $substitutionarray['NB_DAYWORK'] = (int) $nbdaywork;
        $substitutionarray['NB_ENFANTS'] = (int) $nbrenfants;
        $substitutionarray['SITU_FAMILIALE'] = $situation_f ? $situation_f : 'C';
        // $substitutionarray['JOB_PATRONAL'] = $jobpatronal;
        // $substitutionarray['USER_JOB'] = $userjob;
        // $substitutionarray['USER_CERTIFICAT'] = $certificat;
        // $substitutionarray['USER_WORKSITE'] = $worksite;

        if (!empty($extrafields->attributes[$this->table_element]['label'])) {
            foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
                if($extrafields->attributes[$this->table_element]['type'][$key] != 'separate'){
                    $value = $this->array_options['options_'.$key] ? $this->array_options['options_'.$key] : GETPOST('options_'.$key);

                    $substitutionarray[strtoupper($key)] = $value;
                }
            }
        }
    }

    public function calculatePaieRules($fk_paie=0, $action="create")
    {
        global $user, $conf;

        $arrondiauto = $conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO;

        // ------------------------------------------------------------------------------------------------------------
        // global $substitutionarray, $payrules, $checkifcodeexist;
        // ------------------------------------------------------------------------------------------------------------

        $substitutionarray = array();
        $this->getOtherCodesValues($substitutionarray, $action);


        $gotlatest = false;
        $arrlastusedrules = $this->getLastUserRules($this->fk_user, $this->period);

        if(is_array($arrlastusedrules) && count($arrlastusedrules) > 0) {
            // $payrules = $lastused;
            $gotlatest = true;
        }

        $payrules = $this->getRules($filter = '');

        // // ------------------------------------------------------------------------------------------------------------
        // $checkifcodeexist = true;
        // $this->getRules($filter = '');
        // // ------------------------------------------------------------------------------------------------------------

        $sbi_calc       = false;
        $sni_calc       = false;
        $arrondiexist   = false;

        if($payrules){

            // $this->setTotalFixedRules($payrules, $substitutionarray);
            $tmpsubstitutiarr = array();

            // $this->setTotalCalculatedRules($fk_paie, $substitutionarray, $tmpsubstitutiarr, $gotlatest, $payrules);

            foreach ($payrules as $key => $rule) {

                $dr = array(
                    'numero'            => $rule->numero,
                    'code'              => $rule->code,
                    'label'             => addslashes($rule->label)
                    ,'amounttype'       => $rule->amounttype
                    ,'formule_base'     => $this->db->escape($rule->formule_base)
                    ,'formule_taux'     => $this->db->escape($rule->formule_taux)
                    ,'fk_paie'          => $fk_paie
                    ,'category'         => $rule->category
                );

                $this->calc_SBG_SBI_SNI($substitutionarray, $rule->category, $sbi_calc, $sni_calc);
                // if($rule->code == 'IR_B') d($substitutionarray);


                if(($gotlatest && !isset($arrlastusedrules[$rule->code])) || (!$gotlatest && !$rule->showdefault)) {
                    $substitutionarray[$rule->code] = 0;
                    continue;
                }

                if(($gotlatest && isset($arrlastusedrules[$rule->code])) || !$gotlatest) {

                }
                $amount = $rule->amount;
                $taux   = $rule->taux;

                // -------------------------------------------------------------------------------------------------- IF CALCULATED
                if($rule->amounttype == 'CALCULATED') {

                    // Base
                    $result = 0;
                    $str = make_substitutions($rule->formule_base, $substitutionarray);
                    $amount = $this->executeEvalFunction($this, $str);


                    // Taux
                    $result = 0;
                    $str = make_substitutions($rule->formule_taux, $substitutionarray);
                    $taux = $this->executeEvalFunction($this, $str);

                    $total = 0;

                    if($rule->code == 'SALARY') {
                        $taux = 100;
                        if($gotlatest && isset($arrlastusedrules[$rule->code])) {
                            $amount = (float) $arrlastusedrules[$rule->code]->amount;
                        }
                    }


                // -------------------------------------------------------------------------------------------------- IF NOT CALCULATED
                } else {

                    if($gotlatest && isset($arrlastusedrules[$rule->code])) {
                        $amount = (float) $arrlastusedrules[$rule->code]->amount;
                        $taux = (float) $arrlastusedrules[$rule->code]->taux;
                    }
                }

                $total = $amount;
                if($taux > 0) {
                    $total = (float) $amount*($taux/100);
                }

                $substitutionarray[$rule->code] = (float) $total;


                $dr['amount']   = (float) $amount;
                $dr['taux']     = (float) $taux;
                $dr['total']    = (float) $total;

                if($arrondiauto && $rule->category == '99ARRONDI') {
                    $dr['amount'] = 0; $dr['taux'] = 0; $dr['total'] = 0;
                    $substitutionarray[$rule->code] = 0;
                    $arrondiexist = true;
                }

                if(($gotlatest && isset($arrlastusedrules[$rule->code])) || (!$gotlatest && $rule->showdefault)) {
                    $paierule = new paiedolibarr_paiesrules($this->db);
                    $isr = $paierule->create($dr);
                }

                // d($dr,0);
            }

            // die;

            $data = $this->getNetBrutNetAPayer($fk_paie, $arrondiexist, $substitutionarray);

            $isvalid = $this->update($fk_paie, $data);
        }
    }

    // public function setTotalFixedRules($payrules, &$substitutionarray=array())
    // {
    //     foreach ($payrules as $key => $rule) {
    //         // $substitutionarray[$rule->code] = round($rule->amount,4);
    //         $substitutionarray[$rule->code] = $rule->amount*($rule->taux/100);
    //     }

    //     return 1;
    // }

    public function setTotalCalculatedRules($fk_paie=0, &$substitutionarray, &$tmpsubstitutiarr, $gotlatest, $payrules)
    {
        global $conf;

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."paiedolibarr_rules ";
        $sql .= " WHERE entity=".$conf->entity;
        // $sql .= " AND amounttype = 'CALCULATED'";
        
        // if(!$gotlatest) $sql .= ' AND showdefault = 1';

        $sql .= " ORDER BY category,rowid ASC";

        $resql = $this->db->query($sql);

        $arrondiauto = $conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO;

        $sbi_calc = false;
        $sni_calc = false;

        if($resql){
            while ($rule = $this->db->fetch_object($resql)) {

                $amount = 0;
                $taux   = 0;

                if($gotlatest && !isset($payrules[$rule->code])) continue;

                $this->calc_SBG_SBI_SNI($substitutionarray, $rule->category, $sbi_calc, $sni_calc);

                if($rule->amounttype == 'CALCULATED') {

                    // Base
                    $result = 0;
                    $str = make_substitutions($rule->formule_base, $substitutionarray);
                    $amount = $this->executeEvalFunction($this, $str);


                    // Taux
                    $result = 0;
                    $str = make_substitutions($rule->formule_taux, $substitutionarray);
                    $taux = $this->executeEvalFunction($this, $str);

                    if($rule->code == 'SALARY') {
                        
                        if($gotlatest && isset($payrules[$rule->code]))
                            $substitutionarray[$rule->code] = (float) $payrules[$rule->code]->amount;
                        else
                            $substitutionarray[$rule->code] = (float) $amount;

                    } else {
                        $substitutionarray[$rule->code] = (float) $amount*($taux/100);
                    }

                    $tmpsubstitutiarr[$rule->code]['amount'] = (float) $amount;
                    $tmpsubstitutiarr[$rule->code]['taux'] = (float) $taux;

                } else {

                    // if($arrondiauto && $rule->category == '99ARRONDI') continue;

                    if($gotlatest && isset($payrules[$rule->code])) {
                        $substitutionarray[$rule->code] = (float) $payrules[$rule->code]->amount*($payrules[$rule->code]->taux/100);
                    } else {
                        $substitutionarray[$rule->code] = (float) $rule->amount*($rule->taux/100);
                    }

                }

            }
        }
        return $substitutionarray;
    }

    public function getNetBrutNetAPayer($fk_paie=0, $arrondiexist = false, $substitutionarray = array())
    {
        global $conf;

        $arrondiauto = $conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO;

        $returned = array();


        $returned['retenus'] = (float) $substitutionarray['RETENUES'];
        $returned['salairebrut'] = (float) $substitutionarray['S_B_G'];
        $returned['salairebimpo'] = (float) $substitutionarray['S_B_I'];
        $returned['salairenet'] = (float) $substitutionarray['S_N_I'];

        // Calculate NET
        $formnetapayer = $conf->global->PAIEDOLIBARR_FORM_CALCUL_NETAPAYER;
        $str = make_substitutions($formnetapayer, $substitutionarray);
        $netapayer = (float) $this->executeEvalFunction($this, $str);
        

        $beforearrondi = number_format($netapayer, 2, '.','');
        $tmpnum = explode('.', $beforearrondi);
            // d('formnetapayer : '.$formnetapayer,0);
            // d('str2 : '.$str,0);
            // d('netapayer : '.$netapayer, 0);

        if($arrondiauto && $arrondiexist) {

            $tmparrondi = 0;

            if(isset($tmpnum[1]) && $tmpnum[1] > 0) {

                $aftercom = ($tmpnum[1]);

                if($aftercom >= 50) {
                    $tmparrondi = ((int) ($beforearrondi+1) - $beforearrondi);
                } else {
                    // $numlength = strlen((string)$aftercom);
                    // $tmpdivi = ($numlength == 2) ? 100 : 10; 
                    $tmparrondi = (-1)*($aftercom/100);
                }
            }

            if($tmparrondi != 0) {
                $amountarrondi = number_format($tmparrondi, 2, '.','');
                
                $sql = 'UPDATE `'.MAIN_DB_PREFIX.'paiedolibarr_paiesrules` SET `amount` = '.(float)$amountarrondi.', `taux` = 100, `total` = '.(float)$amountarrondi.' WHERE `fk_paie` = '.(int)$fk_paie.' AND category = "99ARRONDI";';
                $resql = $this->db->query($sql);

                $netapayer = (float)($beforearrondi + $amountarrondi);
            } 


            // $returned = $this->getNetBrutNetAPayer($fk_paie, false, $substitutionarray);
            // $isvalid = $this->update($fk_paie, $returned);
        }

        $returned['netapayer'] = number_format($netapayer, 2, '.','');

        return $returned;
    }

    // public function getNetBrutNetAPayer_OLD($fk_paie=0, $arrondiexist = false, $substitutionarray = array())
    // {
    //     global $conf;

    //     $arrondiauto = $conf->global->PAIEDOLIBARR_PAIE_ENABLE_ARRONDI_AUTO;

    //     $returned = array();

    //     $payrules = $this->getRulesOfPaie($this->id);

    //     $substitutionarray = array();

    //     foreach ($payrules as $key => $rule) {
    //         if($arrondiauto && $arrondiexist && $rule->category == '99ARRONDI') continue;

    //         $substitutionarray[$rule->code] = round($rule->total,2);
    //     }

    //     $formsalairebrut    = $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT;
    //     $formsalairebimpo   = $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO; // Brut imposable
    //     $formsalairenet     = $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIRENET; // Net imposable
    //     $formnetapayer      = $conf->global->PAIEDOLIBARR_FORM_CALCUL_NETAPAYER;


    //     $str = make_substitutions($formsalairebrut, $substitutionarray);
    //     $returned['salairebrut'] = (float) $this->executeEvalFunction($this, $str);

    //     $str = make_substitutions($formsalairebimpo, $substitutionarray);
    //     $returned['salairebimpo'] = (float) $this->executeEvalFunction($this, $str);

    //     $str = make_substitutions($formsalairenet, $substitutionarray);
    //     $returned['salairenet'] = (float) $this->executeEvalFunction($this, $str);

    //     $str = make_substitutions($formnetapayer, $substitutionarray);
    //     // d($str,0);
    //     $netapayer = (float) $this->executeEvalFunction($this, $str);

    //     $returned['netapayer'] = round($netapayer,2);

    //     $beforearrondi = round($netapayer,2);

    //     $tmpnum = explode('.', $beforearrondi);

    //     if($arrondiauto && $arrondiexist) {

    //         $tmparrondi = 0;

    //         if(isset($tmpnum[1]) && $tmpnum[1] > 0) {

    //             $aftercom = $tmpnum[1];

    //             if($aftercom >= 50) {
    //                 $tmparrondi = ((int)$beforearrondi+1) - $beforearrondi;
    //             } else {
    //                 $numlength = strlen((string)$aftercom);
    //                 $tmpdivi = ($numlength == 2) ? 100 : 10; 
    //                 $aftercom = $aftercom/$tmpdivi;
    //                 $tmparrondi = (-1)*$aftercom;
    //             }
    //         }

    //         $amountarrondi = round($tmparrondi,2);

    //         $sql = 'UPDATE `'.MAIN_DB_PREFIX.'paiedolibarr_paiesrules` SET `amount` = '.(float)$amountarrondi.', `taux` = 100, `total` = '.(float)$amountarrondi.' WHERE `fk_paie` = '.(int)$fk_paie.' AND category = "99ARRONDI";';
    //         $resql = $this->db->query($sql);


    //         $returned = $this->getNetBrutNetAPayer($fk_paie, false, $substitutionarray);
    //         $isvalid = $this->update($fk_paie, $returned);
    //     }

    //     return $returned;
    // }

    public function getYearsInCompany($paieobj, $dateemployment)
    {
        $years = 0;
        if($paieobj->period && $dateemployment){
            $periods = explode('-', $paieobj->period);

            $periodyear = (($periods && is_array($periods)) ? $periods[0] : 0) + 0;
            $periodmonth = $periods[1];
            $countdays = $this->days_in_month($periodmonth,$periodyear);

            $query_date = $paieobj->period;
            $first = date('01/m/Y', strtotime($query_date));
            $last = date('t/m/Y', strtotime($query_date));
            $lastday = date('Y-m-t', strtotime($query_date));

            $date1 = strtotime($lastday);  
            $date2 = $dateemployment;  

            $diff = @abs($date2 - $date1);
            $years = floor($diff / (365*60*60*24));
        }

        return $years;
    }
    
    
    public function getAvailableSubstitKey($filter = 0, $object = null)
    {
        global $langs, $conf;

        $langs->loadLangs(array('salaries'));

        $tmparray = array();

        $helpsubstit = '';

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields    = new ExtraFields($this->db);
        $extrafields->fetch_name_optionals_label($this->table_element);


        $helpsubstit .= '<div class="paievariablesdisponibles">';

        $helpsubstit .= '<hr>';
        $helpsubstit .= '<b>'.$langs->trans("ExempleFormuleCalcul").'</b>:<br><br>';
        $helpsubstit .= '<span class="exemplecondition classfortooltip">';
        $helpsubstit .= ' 1 :  SALARY * 3/100';
        $helpsubstit .= '<br>';
        $helpsubstit .= ' 2 :  (SALARY > 80000) ? 2400 : SALARY * 3/100'.' '.info_admin($langs->trans('conditionexemple'), 1);;
        $helpsubstit .= '<br>';
        $helpsubstit .= ' 3 :  (SALARY > 80000) ? ((SALARY < 90000) ? 2400 : 3400) : SALARY * 3/100'.' '.info_admin($langs->trans('conditionexemple'), 1);
        $helpsubstit .= '</span>';
        $helpsubstit .= '</br>';
        $helpsubstit .= '<hr>';
        $helpsubstit .= '</br>';

        $helpsubstit .= '<b>'.$langs->trans("paieAvailableVariables").'</b>:<br><br>';

        $helpsubstit .= '<table class="noborder">';

        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">USER_SALARY</td>  <td>'.$langs->trans("Salary").'</td>';
        $helpsubstit .= '</tr>';
        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">YEARS_IN_COMPANY</td>  <td>'.$langs->trans("paieyearsincompany").'</td>';
        $helpsubstit .= '</tr>';
        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">NB_ENFANTS</td>  <td>'.$langs->trans("paieNbre_enfants").'</td>';
        $helpsubstit .= '</tr>';
        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">NB_DAYWORK</td>  <td>'.$langs->trans("paienbdaywork").'</td>';
        $helpsubstit .= '</tr>';
        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">NB_DAYABSENCE</td>  <td>'.$langs->trans("paienbdayabsence").'</td>';
        $helpsubstit .= '</tr>';
        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">SITU_FAMILIALE</td>  <td>'.$langs->trans("paieSituation_F").' (M / C) </td>';
        $helpsubstit .= '</tr>';
        $helpsubstit .= '<tr class="impair">';
        $helpsubstit .= '<td class="nowrap">NB_MONTHINYEAR</td>  <td>'.$langs->trans("paiedolibarrnbmonth").'</td>';
        $helpsubstit .= '</tr>';
        // $helpsubstit .= '<tr class="impair">';
        // $helpsubstit .= '<td class="nowrap">JOB_PATRONAL</td>  <td>'.$conf->global->PAIEDOLIBARR_JOB_PATRONAL.' </td>';
        // $helpsubstit .= '</tr>';
        // $helpsubstit .= '<tr class="impair">';
        // $helpsubstit .= '<td class="nowrap">USER_JOB</td>  <td>'.$langs->trans('PostOrFunction').' </td>';
        // $helpsubstit .= '</tr>';
        // $helpsubstit .= '<tr class="impair">';
        // $helpsubstit .= '<td class="nowrap">USER_CERTIFICAT</td>  <td>'.$langs->trans('Certificat').' </td>';
        // $helpsubstit .= '</tr>';
        // $helpsubstit .= '<tr class="impair">';
        // $helpsubstit .= '<td class="nowrap">USER_WORKSITE</td>  <td>'.$langs->trans('worksite').' </td>';
        // $helpsubstit .= '</tr>';

        if (!empty($extrafields->attributes[$this->table_element]['label'])) {
            foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
                if($extrafields->attributes[$this->table_element]['type'][$key] != 'separate'){
                    $helpsubstit .= '<tr class="impair">';
                    $helpsubstit .= '<td class="nowrap">'.strtoupper($key).'</td>  <td>'.$langs->trans($val).' </td>';
                    $helpsubstit .= '</tr>';
                }
            }
        }
        $helpsubstit .= '<tr><td colspan="2"></td></tr>';


        $helpsubstit .= '<tr><td colspan="2"><b>'.$langs->trans("paieOthersAvailableVariables").'</b>:</td></tr>';

        $payrules = $this->getRules($filter);

        foreach ($payrules as $key => $rule)
        {
            $helpsubstit .= '<tr class="impair">';
            $helpsubstit .= '<td class="nowrap">'.$rule->code.'</td>';
            $helpsubstit .= '<td>';
            $helpsubstit .= '<a target="_blank" href="'.dol_buildpath('/paiedolibarr/rules/card.php?id='.$rule->rowid, 1).'" >';
            $helpsubstit .= trim($rule->label);
            $helpsubstit .= '</a>';
            if($rule->amounttype == 'CALCULATED')
                $helpsubstit .= ' = '.$rule->formule_base;
            $helpsubstit .= '</td>';

            $helpsubstit .= '</tr>';
        }

        $helpsubstit .= '</table>';

        $helpsubstit .= '</div>';

        return $helpsubstit;
    }

    public function executeEvalFunction($object, $str = '', $info = '')
    {
        $result = 0;

        // $result = preg_replace("/[^0-9.\/+*()-- ]/", '0', $str);


        if($str && substr($str,0,1) != '$') {
            @eval('$result = ' . $str . ';');
        } else {
            @eval('' . $str . ';');
        }


        $amount = $result ? $result : $amount;

       return $amount;
    }

    public function getRulesOfPaie($fk_paie=0)
    {
        global $conf;
        $ftable = 'paiedolibarr_paiesrules';

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$ftable." ";
        $sql .= " WHERE 1 = 1";

        $sql .= " AND fk_paie = ".$fk_paie;

        $sql .= " ORDER BY category,rowid ASC";
        $resql = $this->db->query($sql);

        $rows = array();
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass;
                $line->rowid            = $obj->rowid;
                $line->fk_paie          = $obj->fk_paie;
                $line->numero           = $obj->numero;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amount           = $obj->amount;
                $line->taux             = $obj->taux;
                $line->amounttype       = $obj->amounttype;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                $line->total            = $obj->total;

                $rows[] = $line;
            }
        }

        return $rows;
    }

    public function getRulesOfPaieByCateg($fk_paie=0)
    {
        global $conf;
        $ftable = 'paiedolibarr_paiesrules';

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$ftable." ";
        $sql .= " WHERE 1=1";
        $sql .= " AND fk_paie = ".$fk_paie;

        $sql .= " ORDER BY category,rowid ASC";
        $resql = $this->db->query($sql);
        // echo $sql;
        $rows = array();

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass;
                $line->rowid            = $obj->rowid;

                if(!empty($fk_paie))
                    $line->fk_paie      = $obj->fk_paie;

                $line->numero           = $obj->numero;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amounttype       = $obj->amounttype;
                $line->amount           = $obj->amount;
                $line->taux             = $obj->taux;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                $line->formule          = $obj->formule;
                $line->total            = $obj->total;

                
                $rows[$obj->category][$obj->code] = $line;
            }
        }

        return $rows;
    }

    public function getRulesByCateg()
    {
        global $conf;
        $ftable = 'paiedolibarr_rules';

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$ftable." ";
        $sql .= " WHERE 1=1";

        $sql .= " ORDER BY category,rowid ASC";
        $resql = $this->db->query($sql);
        // echo $sql;
        $rows = array();

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass;
                $line->rowid            = $obj->rowid;

                if(!empty($fk_paie))
                    $line->fk_paie      = $obj->fk_paie;

                $line->numero           = $obj->numero;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amounttype       = $obj->amounttype;
                $line->formule          = $obj->formule;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                
                $rows[$obj->category][] = $line;
            }
        }

        return $rows;
    }

    public function getRules($filter = null, $return_codes_sql = false)
    {
        global $conf;

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."paiedolibarr_rules ";
        $sql .= " WHERE 1 = 1";

        if($filter) $sql .= $filter;

        $sql .= " ORDER BY category,rowid ASC";
        $resql = $this->db->query($sql);

        $rows = array();

        // $lastfixedrules = array();
        // if(empty($filter)) {
        //     $lastfixedrules = $this->getLAstUserStaticRules($this->fk_user, $this->period);
        // }
        $txt_sql = '';

        // $brut_showed = false;
        // $impo_showed = false;

        $arraykeysrules = array();

        global $substitutionarray, $payrules, $checkifcodeexist, $originrulesids;

        if($checkifcodeexist && $payrules && is_array($payrules)) {
            foreach ($payrules as $key => $rule) {
                $arraykeysrules[$rule['code']] = $rule['code'];
            }
        }

        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                
                if($return_codes_sql) {
                    // $txt_sql .= $txt_sql ? ',' : '';
                    $txt_sql .= ", MAX(CASE WHEN code = '".$obj->code."' THEN total END) ".$obj->code."";
                }

                $line = new stdClass;
                $line->rowid            = $obj->rowid;
                // $line->fk_paie          = $obj->fk_paie;
                $line->numero           = $obj->numero;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amounttype       = $obj->amounttype;
                $line->amount           = $obj->amount;
                $line->taux             = $obj->taux;
                $line->formule          = $obj->formule;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                $line->total            = $obj->total;
                $line->showdefault      = $obj->showdefault;

                if($obj->amounttype == 'FIX' && isset($lastfixedrules[$obj->code])) {
                    $line->amount   = $lastfixedrules[$obj->code]['amount'];
                    $line->taux     = $lastfixedrules[$obj->code]['taux'];
                }

                $rows[] = $line;

                if($checkifcodeexist && !isset($arraykeysrules[$obj->code])) {
                    $substitutionarray[$obj->code] = 0;
                }

                $originrulesids[$obj->code] = $obj->rowid;

            }
        }

        if($return_codes_sql) {
            $results['sql'] = $txt_sql;
            $results['rows'] = $rows;

            return $results;
        }

        return $rows;
    }

    public function getNewRules($fk_paie)
    {
        global $conf;

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX."paiedolibarr_rules ";
        $sql .= " WHERE 1 = 1";

        
        $sql .= " AND code NOT IN (SELECT code FROM ".MAIN_DB_PREFIX."paiedolibarr_paiesrules WHERE fk_paie = '".(int)$fk_paie."') ";

        $sql .= " ORDER BY category,rowid ASC";
        $resql = $this->db->query($sql);

        $arrayresult = array();
        
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $arrayresult[$obj->rowid] = $obj->label;
            }
        }

        return $arrayresult;
    }

    public function getLastUserRules($fk_user, $period)
    {
        global $conf;
        $ftable = 'paiedolibarr_paiesrules';

        $newdate = date('Y-m', strtotime('-1 months', strtotime($period))). '-01';

        if(!$newdate) return 0;

        // d($newdate, 0);

        $sql = "SELECT * FROM ".MAIN_DB_PREFIX.$ftable." ";
        $sql .= " WHERE 1 = 1";
        $sql .= " AND fk_paie IN ";
        $sql .= " ( ";
            $sql .= "SELECT rowid FROM ".MAIN_DB_PREFIX."paiedolibarr_paies ";
            $sql .= " WHERE 1 = 1";
            $sql .= " AND fk_user = ". (int)$fk_user;
            // $sql .= " AND amounttype = 'FIX'";
            $sql .= " AND period = '". $newdate . "'";
        $sql .= " ) ";


        $sql .= " ORDER BY category,rowid ASC";

        // d($sql);

        $resql = $this->db->query($sql);

        $lastrules = array();
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
               
                // $lastfixedrules[$obj->code]['amount'] = $obj->amount;
                // $lastfixedrules[$obj->code]['taux'] = $obj->taux;

                $line = new stdClass;
                $line->rowid            = $obj->rowid;
                // $line->fk_paie          = $obj->fk_paie;
                $line->numero           = $obj->numero;
                $line->code             = $obj->code;
                $line->label            = $obj->label;
                $line->category         = $obj->category;
                $line->amounttype       = $obj->amounttype;
                $line->amount           = $obj->amount;
                $line->taux             = $obj->taux;
                $line->formule          = $obj->formule;
                $line->formule_base     = $obj->formule_base;
                $line->formule_taux     = $obj->formule_taux;
                $line->total            = $obj->total;

                $lastrules[$obj->code] = $line;

            }
        }

        return $lastrules;
    }

    public function create($echo_sql=0,$insert)
    {

        $sql_column = '';
        $sql_value = '';
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX .get_class($this)." ( ";

        $notint = ['code'=>'code'];

        foreach ($insert as $column => $value) {
            $alias = (!isset($notint[$column]) && is_numeric($value)) ? "" : "'";
            if($value != ""){
                $sql_column .= " , ".$column."";
                $sql_value .= " , ".$alias.$value.$alias;
            }
        }

        $sql .= substr($sql_column, 2)." ) VALUES ( ".substr($sql_value, 2)." )";
        $resql = $this->db->query($sql);
        if($resql){
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX.'paiedolibarr_paies');
            $this->rowid = $id;
            $this->id = $id;
            $result = $this->insertExtraFields();
        }
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' '. $this->db->lasterror();
            
            return 0;
        } 
        // return $this->db->db->insert_id;
        return $id;
    }

    public function update($id, array $data,$echo_sql=0)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        if (!$id || $id <= 0)
            return false;

        if($this->fk_salary){
            $sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE doc_type = 'bank' AND fk_doc IN ( SELECT fk_bank FROM ".MAIN_DB_PREFIX."payment_salary as ps WHERE ps.fk_salary=".$this->fk_salary.")";
            
            // $resql = $this->db->query($sql);
            // if ($resql) {
            //     $obj = $this->db->fetch_object($resql);
            //     if ($obj && $obj->nb) {
            //         $this->error = 'ErrorRecordAlreadyInAccountingDeletionNotPossible';
            //         $this->errors[] = 'ErrorRecordAlreadyInAccountingDeletionNotPossible';
            //         return -1;
            //     }
            // } 
        }

        $sql = 'UPDATE ' . MAIN_DB_PREFIX .get_class($this). ' SET ';

        $notint = ['code'=>'code'];

        if (count($data) && is_array($data))
            foreach ($data as $key => $val) {
                $val = (!isset($notint[$key]) && is_numeric($val)) ? $val : '"'. $val .'"';
                $val = ($val == '') ? 'NULL' : $val;
                $sql .= '`'. $key. '` = '. $val .',';
            }

        $sql  = substr($sql, 0, -1);
        $sql .= ' WHERE rowid = ' . $id;

        // d($sql, 0);
        $resql = $this->db->query($sql);
        if($resql){
            $result = $this->insertExtraFields();
        }
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '. $this->db->lasterror();
            print_r($this->errors);die();
            return -1;
        } 
        return 1;
    }

    public function delete($echo_sql=0)
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);

        $fk_paie = $this->rowid;
        $fk_salary = $this->fk_salary;

        if($fk_salary){
            $sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE doc_type = 'bank' AND fk_doc IN ( SELECT fk_bank FROM ".MAIN_DB_PREFIX."payment_salary as ps WHERE ps.fk_salary=".$fk_salary.")";
            $resql2 = $this->db->query($sql);
            if ($resql2) {
                $obj = $this->db->fetch_object($resql2);
                if ($obj && $obj->nb) {
                    $this->error = 'ErrorRecordAlreadyInAccountingDeletionNotPossible';
                    $this->errors[] = 'ErrorRecordAlreadyInAccountingDeletionNotPossible';
                    $this->db->rollback();
                    return -1;
                }
            } 
        }
        
        $sql   = 'DELETE FROM ' . MAIN_DB_PREFIX .get_class($this).' WHERE rowid = ' . $this->rowid;
        $resql = $this->db->query($sql);

        if($resql){
            $result = $this->deleteExtraFields();
            $sql    = 'DELETE FROM ' . MAIN_DB_PREFIX .'paiedolibarr_paiesrules WHERE fk_paie = ' . $fk_paie;
            $resql  = $this->db->query($sql);
        }
        if (!$resql) {
            $this->db->rollback();
            $this->errors[] = 'Error '.get_class($this).' : '.$this->db->lasterror();
            return -1;
        } 


        return 1;
    }

    
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND')
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);
        $sql = "SELECT * FROM ";
        $sql .= MAIN_DB_PREFIX .get_class($this);
        $sql .= " WHERE entity = ".$conf->entity;
        if (!empty($filter)) {
            $sql .= " ".$filter;
        }
        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            if($offset==1)
                $sql .= " limit ".$limit;
            else
                $sql .= " limit ".$offset.",".$limit;               
        }

        // echo $sql;
        // die;
        $this->rows = array();
        $resql = $this->db->query($sql);

        if ($resql) {
            $num = $this->db->num_rows($resql);

            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass;
                $line->id               = $obj->rowid;
                $line->rowid            = $obj->rowid;
                $line->period           = $obj->period;
                $line->fk_user          = $obj->fk_user;
                $line->ref              = $obj->ref;
                $line->label            = $obj->label;
                $line->salairebrut      = $obj->salairebrut;
                $line->salairenet       = $obj->salairenet;
                $line->netapayer        = $obj->netapayer;
                $line->comment          = $obj->comment;
                $line->entity           = $obj->entity;

                $line->fetch_optionals();

                $this->rows[]   = $line;
            }
            $this->db->free($resql);

            return $num;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }


    public function fetch($id)
    {
        global $conf;
        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = 'SELECT * FROM ' . MAIN_DB_PREFIX .get_class($this). ' WHERE rowid = ' . $id;
        $sql .= ' AND entity = '.$conf->entity;
        $resql = $this->db->query($sql);
        if ($resql) {
            $numrows = $this->db->num_rows($resql);
            
            if ($numrows) {
                $obj                    = $this->db->fetch_object($resql);
                $this->id               = $obj->rowid;
                $this->rowid            = $obj->rowid;
                $this->period           = $obj->period;
                $this->fk_user          = $obj->fk_user;
                $this->ref              = $obj->ref;
                $this->label            = $obj->label;
                $this->salairebrut      = $obj->salairebrut;
                $this->salairenet       = $obj->salairenet;
                $this->retenus          = $obj->retenus;
                $this->netapayer        = $obj->netapayer;
                $this->comment          = $obj->comment;
                $this->entity           = $obj->entity;

                $this->datepay          = $this->db->jdate($obj->datepay);
                $this->matricule        = $obj->matricule;
                $this->nbdaywork        = $obj->nbdaywork;
                $this->nbdayabsence     = $obj->nbdayabsence;
                $this->nbrenfants       = $obj->nbrenfants;
                $this->situation_f      = $obj->situation_f;
                $this->certificat       = $obj->certificat;
                $this->worksite         = $obj->worksite;
                $this->mode_reglement_id = $obj->mode_reglement_id;
                $this->fk_author        = $obj->fk_author;
                $this->fk_lastedit      = $obj->fk_lastedit;
                $this->tms              = $obj->tms;
                $this->fk_salary        = $obj->fk_salary;

                $this->fetch_optionals();
            }

            $this->db->free($resql);

            if ($numrows) {
                return 1 ;
            } else {
                return 0;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }
    }


    /**
     *  Return clickable name (with picto eventually)
     *
     *  @param  int     $withpicto                0=No picto, 1=Include picto into link, 2=Only picto
     *  @param  string  $option                   Variant where the link point to ('', 'nolink')
     *  @param  int     $addlabel                 0=Default, 1=Add label into string, >1=Add first chars into string
     *  @param  string  $moreinpopup              Text to add into popup
     *  @param  string  $sep                      Separator between ref and label if option addlabel is set
     *  @param  int     $notooltip                1=Disable tooltip
     *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @param  string  $morecss                  More css on a link
     *  @return string                            String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
    {
        global $conf, $langs, $user, $hookmanager;

        $userstatic     = new User($this->db);

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        $label = '';
      
        $label .= ($label ? '<br>' : '').'<b>'.$langs->trans('Ref').': </b>'.$this->ref;
        if (!empty($this->period)) {
            $label .= ($label ? '<br>' : '').'<b>'.$langs->trans('Month').': </b>'.dol_print_date($this->period, 'day').' - '.dol_print_date($this->datepay, 'day');
        }

        $url = '';
        if ($option != 'nolink') {
            $url = dol_buildpath('/paiedolibarr/card.php?id='.$this->id, 1);
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
                $add_save_lastsearch_values = 1;
            }
            if ($add_save_lastsearch_values) {
                $url .= '&save_lastsearch_values=1';
            }
        }

        $linkclose = '';
        if (empty($notooltip) && $user->rights->paiedolibarr->lire) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("Show");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="paddingright classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        } else {
            $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
        }

        $picto = $this->picto;

        $linkclose .= ($option == 'target') ? ' target="_blank" ' : '';
        $linkstart = '<a href="'.$url.'" ';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip "'), 0, 0, $notooltip ? 0 : 1);
        }
        if ($withpicto != 2) {
            $result .= $this->ref;
        }
        $result .= $linkend;
        
        // Aperçu
        $linktoshow = dol_buildpath('/paiedolibarr/card.php?id='.$this->id.'&export=pdf',1);
        // $result .= '<a class="pictopreview " href="javascript:document_preview(\''.$linktoshow.'\',\'text/html\',\''.dol_escape_js($langs->trans("Module")).'\')">'.img_picto($langs->trans("ClickToShowDescription"), $imginfo = '');
        $result .= '<a class="pictopreview " href="'.$linktoshow.'" target="_blank">'.img_picto($langs->trans("ClickToShowDescription"), $imginfo = '');
        $result .= '<span class="fa fa-search-plus pictofixedwidth" style="color: gray"></span>';
        $result .= '</a>';

        return $result;
    }

    public function selectCategories($slctd='',$name='category',$showempty=0,$withbasic=true)
    {
        global $langs;
        $rulescateg = $this->rulescategory;
        $select ='<select class="select_'.$name.'" name="'.$name.'" >';

            if($showempty) $select .='<option value="0"></option>';

            foreach ($rulescateg as $keyr => $namer) {
                $slctdt = ($keyr == $slctd) ? 'selected' : '';
                $select .='<option value="'.$keyr.'" '.$slctdt.'>'.$namer.'</option>';
            }
        $select .='</select>';
        return $select;
    }

    public function days_in_month($month, $year)
    {
        return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
    }

    public function getCumulOfRules($paie=array())
    {
        global $conf;

        $cumulsrules = array();
        $confrules = $this->getRulesOfpaie(0);

        $periods = explode('-', $paie->period); 
        $periodyear = $periods[0] + 0;

        $cumulsrules['brutcumul'] = 0;
        $cumulsrules['salairenetcumul'] = 0; // Net Imposable
        $cumulsrules['netapayercumul'] = 0;
        $cumulsrules['retenuscumul'] = 0;
        $cumulsrules['byrules'] = array();


        $filtersql = '';
        $filtersql .= ' WHERE p.entity = '.$conf->entity;
        $filtersql .= ' AND p.period <= "'.$paie->period.'"';
        $filtersql .= ' AND YEAR(p.period) = "'.$periodyear.'"';
        $filtersql .= ' AND p.fk_user = '.$paie->fk_user;


        $sql = 'SELECT'; 
        $sql .= ' SUM(p.salairebrut) as brutcumul';
        $sql .= ' , SUM(p.salairenet) as salairenetcumul';
        $sql .= ' , SUM(p.netapayer) as netapayercumul';
        $sql .= ' , SUM(p.retenus) as retenuscumul';

        $sql .= ' , (';
            $sql .= 'SELECT SUM(case when pr.category = "5IRNET" then pr.total else 0 end) as totaligrcumul FROM '.MAIN_DB_PREFIX.'paiedolibarr_paiesrules as pr ';
            $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiedolibarr_paies as p ON (pr.fk_paie = p.rowid) ';
            $sql .= $filtersql;
        $sql .= ' ) as totaligrcumul';

        $sql .= ' FROM '.MAIN_DB_PREFIX.'paiedolibarr_paies as p';

        $sql .= $filtersql;

        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $cumulsrules['brutcumul'] = $obj->brutcumul; 
                $cumulsrules['salairenetcumul'] = $obj->salairenetcumul; 
                $cumulsrules['netapayercumul'] = $obj->netapayercumul; 
                $cumulsrules['retenuscumul'] = $obj->retenuscumul; 
                $cumulsrules['totaligrcumul'] = $obj->totaligrcumul; 
            }
        }
        // d($sql);

      
        $sql = 'SELECT';
        $sql .= ' r.rowid as ruleid, r.label as rulelabel';
        $sql .= ' , SUM(pr.total) as totalcumul';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'paiedolibarr_paiesrules as pr';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiedolibarr_rules as r ON r.code = pr.code';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiedolibarr_paies as p ON p.rowid = pr.fk_paie';
        $sql .= ' WHERE p.entity = '.$conf->entity;
        $sql .= ' AND p.period <= "'.$paie->period.'"';
        $sql .= ' AND YEAR(p.period) = "'.$periodyear.'"';
        $sql .= ' AND p.fk_user = '.$paie->fk_user;
        $sql .= ' AND r.showcumul > 0 ';

        $sql .= ' GROUP BY r.rowid';

        // d($sql);

        $resql = $this->db->query($sql);
        if($resql){
            while ($obj = $this->db->fetch_object($resql)) {
                $cumulsrules['byrules'][$obj->ruleid]['rulelabel'] = $obj->rulelabel; 
                $cumulsrules['byrules'][$obj->ruleid]['totalcumul'] = $obj->totalcumul; 
            }
        }

        // d($cumulsrules);

        return $cumulsrules;

    }
}
