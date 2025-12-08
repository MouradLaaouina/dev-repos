<?php
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom"

$results = array();

dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
dol_include_once('/core/class/html.form.class.php');

global $langs;
$employee 		= new User($db);
$paiedolibarr   = new paiedolibarr($db);
$form        	= new Form($db);

$action 		= GETPOST('action');
$fk_user 		= GETPOST('fk_user');
$periodyear 	= GETPOST('periodyear');
$periodmonth 	= GETPOST('periodmonth');


if($action == 'details'){

	$employee->fetch($fk_user);

	$mountyear = $langs->trans("Month".sprintf("%02d", $periodmonth))." ".$periodyear;

	$name = '';
	if($employee->id){
		$name = $employee->firstname.' '.$employee->lastname.' - ';
		if($conf->global->MAIN_FIRSTNAME_NAME_POSITION)
			$name = $employee->lastname.' '.$employee->firstname.' - ';
	}

	$results['label'] = $langs->trans('Fiche_de_salaire').' - '.html_entity_decode($name).html_entity_decode($mountyear);

	// $results['ref'] = $langs->trans('PAIESLIP').''.sprintf("%02d", $fk_user).'-'.sprintf("%02d", $periodmonth).'/'.$periodyear;
    $results['ref'] = 'P'.substr( $periodyear, -2).''.sprintf("%02d", $periodmonth).'-'.sprintf("%03d", $fk_user);
	$results['salary'] = 0;
	if($employee->salary)
		$results['salary'] = number_format($employee->salary,2,'.','');


    $query_date = $periodyear.'-'.$periodmonth.'-01';
    // $first = date('01/m/Y', strtotime($query_date));

    $lastday = date('Y-m-t', strtotime($query_date));

    $datepay_date = dol_print_date($lastday, 'dayinputnoreduce', 'tzuserrel');
    $datepay_day = dol_print_date($lastday, '%d', 'tzuserrel');

    // d($datepay_date, 0);
    // d($datepay_day, 0);

    
    $results['datepay_date'] 		= $datepay_date;
    $results['datepay_day']         = $datepay_day;

    $results['matricule']       = '';
    $results['situation_f'] 	= 0;
	$results['nbrenfants'] 		= 0;
	$results['partigr'] 		= '';
	$results['cnss'] 			= '';
	$results['categorie'] 		= '';
	$results['qualification'] 	= '';
	$results['zone'] 			= '';
	$results['echelon'] 		= '';
	$results['niveau'] 			= '';

    if($employee->array_options){
        $dt = $employee->array_options;
        if(isset($dt['options_paiedolibarrmatricule'])){
            $results['matricule'] = $dt['options_paiedolibarrmatricule'];
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
        if(isset($dt['options_paiecnss'])){
            $results['cnss'] = $dt['options_paiecnss'];
        }
        if(isset($dt['options_paiedolibarrcategorie'])){
            $results['categorie'] = $dt['options_paiedolibarrcategorie'];
        }
        if(isset($dt['options_paiedolibarrqualification'])){
            $results['qualification'] = $dt['options_paiedolibarrqualification'];
        }
        if(isset($dt['options_paiedolibarrzone'])){
            $results['zone'] = $dt['options_paiedolibarrzone'];
        }
        if(isset($dt['options_paiedolibarrechelon'])){
            $results['echelon'] = $dt['options_paiedolibarrechelon'];
        }
        if(isset($dt['options_paieniveau'])){
            $results['niveau'] = $dt['options_paieniveau'];
        }
    }
}
elseif($action == 'users'){
	$excludes = array();
	$excludes = $paiedolibarr->getExcludedUsers($periodyear.'-'.$periodmonth.'-01'); 
	$results['users'] = $form->select_dolusers($fk_user, 'fk_user', 0, $excludes, 0, '', 0, 0, 0, 0, '', 0, '', 'minwidth300imp');
	// $results['users'] = $form->select_dolusers($fk_user, 'fk_user', 0, $excludes, 0, '', 0, 0, 0, 0, '', 0, '', 'quatrevingtseizepercent');
}


echo json_encode($results);

?>
