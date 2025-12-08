<?php

$empty = '';
$nbrtest = 0;

$left = 'left';
$right = 'right';
$dir = 'direction: ltr';

if ($langs->trans("DIRECTION") == 'rtl') {
	$left = 'right';
	$right = 'left';
	$dir = 'direction: rtl';
    $pdf->SetFont('freeserif', '', 9);
}
		

$html='<style>';
    $html .= 'td.td-1{width:30%;  border-bottom:2px solid #000; border-right:2px solid #000; text-align:center}';
    $html .= 'td.td-2{width:70%;  border-bottom:2px solid #000; border-left:2px solid #000;}';
    $html .= 'td.td-3{width:30%;  border-top:2px solid #000; border-right:2px solid #000; text-align:center}';
    $html .= 'td.td-4{width:70%;  border-top:2px solid #000; border-left:2px solid #000;}';
    // $html .= 'table{width:100%; height:100%;border:1px solid #000;}';
    $html .= '.smallsize td{font-size:8px;}';
    $html .= '.footertable th{background-color:#d9e1f2;font-size:10px;}';
    $html .= '.bodytable td{font-size:11px;}';

    // $html .= '.bodytable tr.row td{background-color:#fff;}';
    // $html .= '.bodytable tr.row1 td{background-color:#d9e1f2;}';
    // $html .= '.bodytable tr.row1 td{background-color:#d9e1f2;}';
    $html .= '.bodytable tr td{border-left:1px solid #000;border-right:1px solid #000;}';
    // $html .= '.bodytable tr.totalligne td{border-top:1px solid #000;border-bottom:1px solid #000;}';


    $html .= '.bodytable {border:1px solid #000;}';
    $html .= '.bodytable th{border:1px solid #000;font-size:10px;font-weight:bold;}';
    $html .= '.bodytable tr.totallignebold td{border-top:2px solid #000;}';

    $html .= '.cumultable {border:0px solid #d9e1f2;}';
    $html .= '.cumultable tr{border:0px solid #d9e1f2;}';
    $html .= '.cumultable tr td{border-left:1px solid #000;}';
    $html .= '.cumultable tr td.firsttd1{border-left:0px solid #d9e1f2;}';

    $html .= '.footertable td{font-size:10px;}';
    $html .= '.headertable td{font-size:10px;}';
    $html .= '.reposcomptable td.brdlft{border-left:1px solid #000;}';
    $html .= '.reposcomptable td.brdbtm{border-bottom:1px solid #000;}';
    $html .= 'td.bggray{background-color:#d9e1f2;}';
    $html .= 'tr.bggreen td{background-color:#d9e1f2;}';
    $html .= '.totalrow td{background-color:#d9e1f2;border-top:1px solid #000;border-bottom:1px solid #000;}';
    $html .= '.engras1{font-weight:bold;}';
    $html .= '.footertabledown td{font-size:9px;}';
    $html .= '.footertabledownsmall td{font-size:9px;}';
$html .= '</style>';
$object->fetch($id);




$currency = $conf->currency;
$currency = $langs->transnoentitiesnoconv("Currency".$currency);

$payedwith = '';
if($item->mode_reglement_id){
	$form->load_cache_types_paiements();
	$form->load_cache_conditions_paiements();
	$payedwith = $form->cache_types_paiements[$item->mode_reglement_id]['label'];
}

$datepay = str_repeat('&nbsp;',25);
if(!empty($item->datepay) && $item->datepay != '0000-00-00')
    $datepay = dol_print_date($item->datepay, 'day');

$periods = explode('-', $item->period);
$periodyear = $periods[0] + 0;
$periodmonth = $periods[1];
$countdays = days_in_month($periodmonth,$periodyear);

// $query_date = $periodyear.'-'.$periodmonth.'-01';
$query_date = $item->period;
$first = date('01/m/Y', strtotime($query_date));
$last = date('t/m/Y', strtotime($query_date));
$lastday = date('Y-m-t', strtotime($query_date));


$employeeinfo = $paiedolibarr->employeeinfo($item->rowid, $lastday);

$datebirth = str_repeat('&nbsp;',25);
if(!empty($employeeinfo['birth']))
    $datebirth = dol_print_date($employeeinfo['birth'], 'day');

$dateemployment = str_repeat('&nbsp;',25);
if(!empty($employeeinfo['dateemployment']))
    $dateemployment = dol_print_date($employeeinfo['dateemployment'], 'day');


global $mysoc;
$carac_soci = $langs->convToOutputCharset($mysoc->name)."<br>";
$html .= str_repeat('<br>',2); 



$html .= '<div>'; 
// -------------------------------- HEADER
	// $html .= '<table border="0" cellpadding="0" cellspacing="0" class="footertabledown" style="width:100%;">'; 
	// 	$html .= '<tr>';
	// 		$html .= '<td style="width:85%;">';
	// 			$html .= '<table border="1" cellpadding="4" cellspacing="0" class="" style="width:100%;">'; 
	// 				$html .= '<tr>';
	// 					$html .= '<td style="width:21%;" align="center"> '.$langs->trans('paieMatricule').' </td>'; 
	// 					$html .= '<td style="width:40%;" colspan="4" align="center"> '.$langs->trans('paieFullname').'</td>'; 
	// 					$html .= '<td style="width:19.5%;" colspan="2" align="center"> '.$langs->trans('Function').'</td>'; 
	// 					$html .= '<td style="width:19.5%;" colspan="2" align="center"> '.$langs->trans('Department').'</td>'; 
	// 				$html .= '</tr>';

	// 				$html .= '<tr>';
	// 					$html .= '<td align="center"><b>'.$employeeinfo['matricule'].'</b></td>';
	// 					$html .= '<td colspan="4" align="center"><b>'.$employeeinfo['name'].'</b></td>'; 
	// 					$html .= '<td colspan="2" align="center"><b>'.$employeeinfo['job'].'</b></td>'; 
	// 					$html .= '<td colspan="2" align="center"><b>'.$employeeinfo['departement'].'</b></td>'; 
	// 				$html .= '</tr>';

	// 				$html .= '<tr>';
	// 					$html .= '<td style="" align="center">'.$langs->trans('paiecin').'</td>'; 
	// 					$html .= '<td style="" align="center">'.$langs->trans('D_ateOfBirth').'</td>'; 
	// 					$html .= '<td style="" align="center">'.$langs->trans('S.F').'</td>';  
	// 					$html .= '<td style="" align="center">'.$langs->trans('ENF.').'</td>';  
	// 					$html .= '<td style="" align="center">'.$langs->trans('DED').'</td>';  
	// 					$html .= '<td align="center">'.$langs->trans('paieDateembauche').'</td>'; 
	// 					$html .= '<td align="center">'.$langs->trans('N_cnss').'</td>';
	// 					$html .= '<td align="center">'.$langs->trans('N_cimr').'</td>';
	// 					$html .= '<td align="center">'.$langs->trans('paie_Paiement').'</td>';
	// 				$html .= '</tr>';

	// 				$html .= '<tr>';
	// 					$html .= '<td align="center"><b>'.$employeeinfo['cin'].'</b></td>';  
	// 					$html .= '<td align="center"><b>'.$datebirth.'</b></td>'; 
	// 					$html .= '<td align="center"><b>'.$employeeinfo['situation_f'].'</b></td>';  
	// 					$html .= '<td align="center"><b>'.$employeeinfo['nbrenfants'].'</b></td>';  
	// 					$html .= '<td align="center"><b>'.$employeeinfo['nd'].'</b></td>';  
	// 					$html .= '<td align="center"><b>'.$dateemployment.'</b></td>'; 
	// 					$html .= '<td align="center"><b>'.$employeeinfo['cnss'].'</b></td>'; 
	// 					$html .= '<td align="center"><b>'.$employeeinfo['cimr'].'</b></td>'; 
	// 					$html .= '<td align="center"><b>'.$payedwith.'</b></td>'; 
	// 				$html .= '</tr>';

	// 				$html .= '<tr>';
	// 					$html .= '<td colspan="9" align="center"></td>';
	// 				$html .= '</tr>';

	// 			$html .= '</table>'; 
	// 		$html .= '</td>';
	// 		$html .= '<td style="width:15%;">';
	// 			$html .= '<table border="1" cellpadding="4" cellspacing="0" class="" style="width:100%;">'; 
	// 				$html .= '<tr>';
	// 					$html .= '<td style="width:100%;" align="center"> '.$langs->trans('PaymentDate').'</td>'; 
	// 				$html .= '</tr>';
	// 				$html .= '<tr>';
	// 					$html .= '<td align="center"><b>'.$last.'</b></td>'; 
	// 				$html .= '</tr>';
	// 				$html .= '<tr>';
	// 					$html .= '<td align="center">'.$langs->trans('Pay_period').'</td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr>';
	// 					$html .= '<td align="center" style="line-height: 30px;"><b>'.$langs->trans('From').'  '.$first.' <br>'.$langs->trans('To').'  '.$last.'</b></td>';
	// 				$html .= '</tr>';
	// 			$html .= '</table>'; 
	// 		$html .= '</td>';
	// 	$html .= '</tr>';
	// $html .= '</table>'; 

	$html .= '<table border="1" cellpadding="4" cellspacing="0" class="headertable" style="width:100%;">'; 
		$html .= '<tr>';
			$html .= '<td style="" align="center"> '.$langs->trans('paieMatricule').' </td>'; 
			$html .= '<td style="" colspan="4" align="center"> '.$langs->trans('paieFullname').'</td>'; 
			$html .= '<td style="" colspan="2" align="center"> '.$langs->trans('Function').'</td>'; 
			$html .= '<td style="" colspan="2" align="center"> '.$langs->trans('Department').'</td>'; 
			$html .= '<td style="" align="center"> '.$langs->trans('PaymentDate').'</td>'; 
		$html .= '</tr>';

		$html .= '<tr>';
			$html .= '<td align="center"><b>'.$employeeinfo['matricule'].'</b></td>';
			$html .= '<td colspan="4" align="center"><b>'.$employeeinfo['name'].'</b></td>'; 
			$html .= '<td colspan="2" align="center"><b>'.$employeeinfo['job'].'</b></td>'; 
			$html .= '<td colspan="2" align="center"><b>'.$employeeinfo['departement'].'</b></td>'; 
			$html .= '<td align="center"><b>'.$last.'</b></td>'; 
		$html .= '</tr>';

		$html .= '<tr>';
			$html .= '<td style="" align="center">'.$langs->trans('paiecin').'</td>'; 
			$html .= '<td style="" align="center">'.$langs->trans('D_ateOfBirth').'</td>'; 
			$html .= '<td style="" align="center">'.$langs->trans('S.F').'</td>';  
			$html .= '<td style="" align="center">'.$langs->trans('ENF.').'</td>';  
			$html .= '<td style="" align="center">'.$langs->trans('DED').'</td>';  
			$html .= '<td align="center">'.$langs->trans('paieDateembauche').'</td>'; 
			$html .= '<td align="center">'.$langs->trans('N_cnss').'</td>';
			$html .= '<td align="center">'.$langs->trans('N_cimr').'</td>';
			$html .= '<td align="center">'.$langs->trans('Pay_period').'</td>';
			$html .= '<td align="center">'.$langs->trans('paie_Paiement').'</td>';
		$html .= '</tr>';

		$html .= '<tr>';
			$html .= '<td align="center"><b>'.$employeeinfo['cin'].'</b></td>';  
			$html .= '<td align="center"><b>'.$datebirth.'</b></td>'; 
			$html .= '<td align="center"><b>'.$employeeinfo['situation_f'].'</b></td>';  
			$html .= '<td align="center"><b>'.$employeeinfo['nbrenfants'].'</b></td>';  
			$html .= '<td align="center"><b>'.$employeeinfo['nd'].'</b></td>';  
			$html .= '<td align="center"><b>'.$dateemployment.'</b></td>'; 
			$html .= '<td align="center"><b>'.$employeeinfo['cnss'].'</b></td>'; 
			$html .= '<td align="center"><b>'.$employeeinfo['cimr'].'</b></td>'; 
			$html .= '<td align="center"><b></b></td>'; 
			$html .= '<td align="center" rowspan="2" style="line-height: 17px;"><b>'.$langs->trans('From').'  '.$first.' <br>'.$langs->trans('To').'  '.$last.'</b></td>';
		$html .= '</tr>';

		$html .= '<tr>';
			$html .= '<td colspan="9" align="center"></td>';
		$html .= '</tr>';

	$html .= '</table>';
// -------------------------------- END HEADER



// ------------------------------------------------------------------------------------------

global $substitutionarray, $payrules, $checkifcodeexist, $originrulesids;
$checkifcodeexist 	= true;
$payrules 			= array();
$substitutionarray 	= array();
$object->getRules($filter = '');
$rowidoforiginrules = $originrulesids; // Only to get rules ids

// ------------------------------------------------------------------------------------------




// ---------------------------------------------------------------------------
$width_code 	= 8;
$width_rubrique = 28;
$width_base 	= 17;
$width_taux 	= 17;
$width_gains 	= 15;
$width_retenue 	= 15;

$width_numbers 	= ($width_code+$width_rubrique+$width_base+($width_taux/2));
$width_signatu 	= ($width_retenue+$width_gains+($width_taux/2));

// ---------------------------------------------------------------------------




$colspanfirst = 1;
$nbrlines = 0;

$cumulsrules = $object->getCumulOfRules($item);
if(isset($cumulsrules['byrules'])) $colspanfirst += count($cumulsrules['byrules']);



// -------------------------------- BODY
	$html .= '<table border="0" cellpadding="3" cellspacing="0" class="bodytable" style="width:100%;">'; 

	$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th style="width:'.$width_code.'%;" align="center" colspan="">'.$langs->trans('Code').'</th>'; 
		$html .= '<th style="width:'.$width_rubrique.'%;" align="center" colspan="">'.$langs->trans('Rubrique').'</th>'; 
		$html .= '<th style="width:'.$width_base.'%;" align="center">'.$langs->trans('paieBase').'</th>'; 
		$html .= '<th style="width:'.$width_taux.'%;" align="center">'.$langs->trans('Taux').'</th>'; 
		$html .= '<th style="width:'.$width_gains.'%;" align="center">'.$langs->trans('Gains').'</th>'; 
		$html .= '<th style="width:'.$width_retenue.'%;" align="center">'.$langs->trans('Retenues').'</th>'; 

		$html .= '</tr>';
	$html .= '</thead>';

	$html .= '<tbody>';
	$payrules = $object->getRulesOfPaieByCateg($item->rowid);
	
	// d($payrules);die;

    $i = 1;
    $totbrut = 0; $totretenus = 0;
    $totcotisation = 0; $ptrtotcotisation = 0;
    $totaligr = 0;


    $brut_showed = false;
    $impo_showed = false;

	$emptytr = '<tr><td colspan=""></td><td></td><td></td><td></td><td></td></tr>';

    $trscolored = ['5IRNET' => 1,'6TAXSOC' => 1,'99ARRONDI' => 1];
    $afterretenus = $object->afterretenus;
    $notcalculed = ['FRAIS', 'CHARGE', 'IR_B'];

    $notcalculed = array_flip($notcalculed);

    $var = 0;
    $var = 0;



    foreach ($payrules as $key0 => $arrrules) {

    	foreach ($arrrules as $key => $rule) {

    		if(!(float)$rule->amount && !isset($trscolored[$key0]) || ($key0 == '99ARRONDI' && $rule->total == 0)) continue;

	    	$var = !$var;

	    	// ========================================================================================================================================================
	    	if(!$brut_showed && $key0 == '2RETENUES') {
	    		$html .= '<tr class="engras1">';
					$html .= '<td style="width:'.$width_code.'%;"></td>';
					$html .= '<td style="width:'.$width_rubrique.'%;" align="'.$left.'" colspan="">'; 
						$html .= $langs->trans('paieSalaire_Brut_Imposable');
					$html .= '</td>';
					
					$html .= '<td style="width:'.$width_base.'%;"></td>';
					$html .= '<td style="width:'.$width_taux.'%;"></td>';

					$html .= '<td style="width:'.$width_gains.'%;" align="'.$right.'">';
					$html .= $paiedolibarr->number_format($totbrut, 2, '.',' ');
					$html .= '</td>';
					$html .= '<td style="width:'.$width_retenue.'%;" align="'.$right.'">'; 
					$html .= '</td>';
				$html .= '</tr>';
				// $html .= $emptytr;
				$brut_showed = true;
	    	}
	    	// ========================================================================================================================================================

	    	elseif(!$impo_showed && isset($afterretenus[$key0])) {
				// $html .= $emptytr;
	    		$html .= '<tr class="engras1">';
					$html .= '<td style="width:'.$width_code.'%;"></td>';
					$html .= '<td style="width:'.$width_rubrique.'%;" align="'.$left.'" colspan="">'; 
						$html .= $langs->trans('paieNet_imposable');
					$html .= '</td>';
					
					$html .= '<td style="width:'.$width_base.'%;"></td>';
					$html .= '<td style="width:'.$width_taux.'%;"></td>';

					$html .= '<td style="width:'.$width_gains.'%;" align="'.$right.'">';
					$html .= $paiedolibarr->number_format($object->salairenet, 2, '.',' ');
					$html .= '</td>';
					$html .= '<td style="width:'.$width_retenue.'%;" align="'.$right.'">'; 
					$html .= '</td>';
				$html .= '</tr>';
				$impo_showed = true;
	    	}

	    	// ========================================================================================================================================================

    		$othcls = '';
	    	// if(isset($trscolored[$key0])) {
	    	// 	$othcls = 'totalligne';
			// 	$html .= $emptytr;
	    	// }

	    	$html .= '<tr class="row'.$var.' '.$othcls.' engras'.(isset($rule->engras) ? $rule->engras : '').'">';

		        // Code
		        $html .= '<td style="width:'.$width_code.'%;" align="right" colspan="">'; 
		        $html .= $rule->numero ? $rule->numero : $rowidoforiginrules[$rule->code];
		        $html .= '</td>'; 

		        // Rubriques
		        $html .= '<td style="width:'.$width_rubrique.'%;" align="'.$left.'" colspan="">'; 
		        $html .= $rule->label;
		        $html .= '</td>';
				
		        // Base
		     	$html .= '<td style="width:'.$width_base.'%;" align="'.$right.'">';
		     		// if($rule->amount > 0 && (($rule->taux != 100 && $key0 != '1BRUT') || $key0 == '1BRUT'))

		     		if($key0 == '5IRNET' || $key0 == '99ARRONDI')
		     			$html .= '';
		     		elseif($key0 == '3IRBRUT')
		     			$html .= $paiedolibarr->number_format($object->salairenet, 2, '.',' ');
		     		elseif($rule->amount > 0)
		     			$html .= $paiedolibarr->number_format($rule->amount, 2, '.',' ');
		     		
		        $html .= '</td>';
		        // Nbre ou taux
		        $html .= '<td style="width:'.$width_taux.'%;" align="'.$right.'">'; 
			        if($rule->taux && $rule->taux > 0){

			        	if($rule->taux == 100) {
			        		if($rule->category == '0BASIQUE') {
			                	// $html .= '26';
			                	$html .= $paiedolibarr->number_format($rule->taux, 0, '.','');
			        		}
			            	else
			                	$html .= '';
			        	}
			            else
			                $html .= $paiedolibarr->number_format($rule->taux, 2, '.',' ');
				    }
		        $html .= '</td>';

		        $adeduire = 0;
		        $apayer = 0;

		        if(($rule->total < 0 || (isset($object->retenues[$key0])))) {
		        	$adeduire = abs($rule->total);

		        	if(!isset($notcalculed[$rule->code]))
	        			$totretenus = $totretenus + $rule->total;
		        }
		        elseif(isset($object->gains[$key0])){
	        		$apayer = $rule->total;

	        		if(!isset($notcalculed[$rule->code]))
	        			$totbrut = $totbrut + $rule->total;
		        } 
		        
		        // Gains
		        $html .= '<td style="width:'.$width_gains.'%;" align="'.$right.'">'; 
		        	if($apayer > 0)
			        	$html .= $paiedolibarr->number_format($apayer, 2, '.',' ');
		        $html .= '</td>';

		        // Retenues
		        $html .= '<td style="width:'.$width_retenue.'%;" align="'.$right.'">'; 
		        	if($adeduire > 0)
			        	$html .= $paiedolibarr->number_format($adeduire, 2, '.',' ');
			        	// $totalretenusmois += $adeduire;
		        $html .= '</td>';

	        $html .= '</tr>';


		        
	        if(!$totaligr && $key0 == '5IRNET'){
	        	$totaligr = $rule->total;
	        }
	    	// ========================================================================================================================================================
	        $nbrlines++;
    	}
    }

  	// $totnet = $totbrut - $totcotisation;
  	$maxlings = 30;
    if($nbrlines < $maxlings){
    	$rest = ($maxlings-$nbrlines);
    	for ($z=0; $z < $rest; $z++) { 
    		$var = !$var;
        	$html .= '<tr class="row'.$var.'">';
        	$html .= '<td style="width:'.$width_code.'%;" colspan=""></td>';
        	$html .= '<td style="width:'.$width_rubrique.'%;" colspan=""></td>';
        	$html .= '<td style="width:'.$width_base.'%;" colspan=""></td>';
			$html .= '<td style="width:'.$width_taux.'%;" colspan=""></td>';
			$html .= '<td style="width:'.$width_gains.'%;" colspan=""></td>';
			$html .= '<td style="width:'.$width_retenue.'%;" colspan=""></td>';
        	// $html .= str_repeat('<td style="width:'.$width_retenue.'%;"></td>',4);

        	$html .= '</tr>';
    	}
    }




	$html .= '</tbody>';

	$html .= '</table>'; 

	$html .= '<table border="1" cellpadding="4" cellspacing="0" class="footertabledown" style="width:100%;">'; 

		$html .= '<tr class="">';
			$html .= '<td style="width:'.$width_code.'%;" align="center" rowspan="2" colspan="2">'.$langs->trans('WorkDays').'</td>';
			$html .= '<td style="width:'.($width_rubrique/4).'%;" align="center" rowspan="2">'.$langs->trans('paieSalaire_brut').'</td>';
			$html .= '<td style="width:'.($width_rubrique/4).'%;" align="center" rowspan="2">'.$langs->trans('TaxableWages').'</td>';
			$html .= '<td style="width:'.($width_rubrique/4).'%;" align="center" rowspan="2">'.$langs->trans('SocialContributions').'</td>';
			$html .= '<td style="width:'.($width_rubrique/4).'%;" align="center" rowspan="2">'.$langs->trans('IGRDeduction').'</td>';
			$html .= '<td style="width:'.$width_base.'%;" align="center" colspan="2">'.$langs->trans('LoanStatus').'</td>';
			$html .= '<td style="width:'.$width_taux.'%;" align="center" colspan="2">'.$langs->trans('LeaveStatus').'</td>';
			$html .= '<td style="width:'.$width_gains.'%;" align="center">'.$langs->trans('totalWinnings').'</td>';
			$html .= '<td style="width:'.$width_retenue.'%;" align="center">'.$langs->trans('TotalWithheld').'</td>';
		$html .= '</tr>';


		$html .= '<tr class="footertabledownsmall">';
			$html .= '<td style="" align="center">'.$langs->trans('BeginningMonth').'</td>';
			$html .= '<td style="" align="center"></td>';

			$html .= '<td style="" align="center">'.$langs->trans('BeginningBalance').'</td>';

			$html .= '<td style="" align="center"></td>';

			// TOTAL GAINS (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($totbrut, 2, '.',' ').'</td>';

			// TOTAL RETENUES (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($totretenus, 2, '.',' ').'</td>';

		$html .= '</tr>';



		// Mois
		$html .= '<tr class="footertabledownsmall">';
			$html .= '<td style="" align="center">'.$langs->trans('Month').'</td>';

			// Mois (value)
			$html .= '<td align="center">26</td>'; // A definir

			// SALAIRE BRUT (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($totbrut, 2, '.',' ').'</td>';

			// Salaire imposable (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($object->salairenet, 2, '.',' ').'</td>';

			// Cotisations sociales (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($totretenus, 2, '.',' ').'</td>';

			// Retenue IGR (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($totaligr, 2, '.',' ').'</td>';

			// REMB. MOIS
			$html .= '<td style="" align="center">'.$langs->trans('REMBMonth').'</td>';

			// REMB. MOIS (value)
			$html .= '<td align="center"></td>';

			// Droit annuel
			$html .= '<td style="" align="center">'.$langs->trans('AnnualFee').'</td>';

			// Droit annuel (value)
			$html .= '<td align="center"></td>';

			// MODE REGLEMENT
			$html .= '<td style="" align="center">'.$langs->trans('PaymentMode').'</td>';

			// NET A PAYER
			$html .= '<td style="" align="center" class="engras1">'.$langs->trans('paieNet_a_payer').'</td>';

		$html .= '</tr>';



		// Cuml
		$html .= '<tr class="footertabledownsmall">';
			$html .= '<td style="" align="center">'.$langs->trans('Cum').'</td>';

			// Cumul (value)
			$html .= '<td align="center"></td>';

			// SALAIRE BRUT (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($cumulsrules['brutcumul'], 2, '.',' ').'</td>';

			// Salaire imposable (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($cumulsrules['salairenetcumul'], 2, '.',' ').'</td>';

			// Cotisations sociales (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($totretenus, 2, '.',' ').'</td>';

			// Retenue IGR (value)
			$html .= '<td align="center">'.$paiedolibarr->number_format($cumulsrules['totaligrcumul'], 2, '.',' ').'</td>';

			// FIN MOIS
			$html .= '<td style="" align="center">'.$langs->trans('EndMonth').'</td>';

			// FIN MOIS (value)
			$html .= '<td align="center"></td>';

			// CONGE ANNUEL
			$html .= '<td style="" align="center">'.$langs->trans('AnnualLeave').'</td>';

			// CONGE ANNUEL (value)
			$html .= '<td align="center"></td>';

			// MODE REGLEMENT (value)
			$html .= '<td style="" align="center" class="">'.$payedwith.'</td>';

			// NET A PAYER (value)
			$html .= '<td style="" align="center" class="engras1">'.$paiedolibarr->number_format($object->netapayer, 2, '.',' ').'</td>';

		$html .= '</tr>';



		// Down Cuml
		$html .= '<tr class="footertabledownsmall">';

			$html .= '<td align="center" colspan="8">';
			$html .= '</td>';

			// CONGE ANNUEL
			$html .= '<td style="" align="center">'.$langs->trans('EndBalance').'</td>';

			// CONGE ANNUEL (value)
			$html .= '<td align="center"></td>';

			$html .= '<td align="center" colspan="2"></td>';

		$html .= '</tr>';

	$html .= '</table>';

	// $html .= '<table border="0" cellpadding="0" cellspacing="0" class="footertabledown" style="width:100%;">'; 
	// 	$html .= '<tr>';
	// 		$html .= '<td style="width:40%;">';
	// 			$html .= '<table border="1" cellpadding="4" cellspacing="0" class="" style="width:100%;">'; 
	// 				$html .= '<tr class="">';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2" colspan="2">'.$langs->trans('WorkDays').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2">'.$langs->trans('paieSalaire_brut').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2">'.$langs->trans('TaxableWages').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2">'.$langs->trans('SocialContributions').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2">'.$langs->trans('IGRDeduction').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2" colspan="2">'.$langs->trans('LoanStatus').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2" colspan="2">'.$langs->trans('LeaveStatus').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2">'.$langs->trans('totalWinnings').'</td>';
	// 					$html .= '<td style="width:20%;" align="center" rowspan="2">'.$langs->trans('TotalWithheld').'</td>';
	// 				$html .= '</tr>';


	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="" align="center">'.$langs->trans('BeginningMonth').'</td>';
	// 					$html .= '<td style="" align="center"></td>';

	// 					$html .= '<td style="" align="center">'.$langs->trans('BeginningBalance').'</td>';
	// 					$html .= '<td style="" align="center"></td>';

	// 					// TOTAL GAINS (value)
	// 					$html .= '<td style="width:20%;" align="center">';
	// 					$html .= '</td>';

	// 					// TOTAL RETENUES (value)
	// 					$html .= '<td style="width:20%;" align="center">';
	// 					$html .= '</td>';

	// 				$html .= '</tr>';

	// 			$html .= '</table>';

	// 				// $html .= '<tr class="totalligne">';
	// 				// 	$html .= '<td style="width:12%;line-height: 24px;" align="center">'.$langs->trans('Month').'</td>';
	// 				// 	$html .= '<td style="width:'.$width_code.'%;" align="center">';
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 		$html .= $paiedolibarr->number_format($cumulsrules['brutmois'], 2, '.',' ');
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 		$html .= $paiedolibarr->number_format($cumulsrules['salairenetmois'], 2, '.',' ');
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 		$html .= $paiedolibarr->number_format($totalretenusmois, 2, '.',' ');
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 	$html .= '</td>';
	// 				// $html .= '</tr>';


	// 				// $html .= '<tr class="totalligne">';
	// 				// 	$html .= '<td style="width:12%; line-height: 24px;" align="center">'.$langs->trans('cum').'</td>';
	// 				// 	$html .= '<td style="width:'.$width_code.'%;" align="center">26';
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 		$html .= $paiedolibarr->number_format($cumulsrules['brutcumul'], 2, '.',' ');
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 		$html .= $paiedolibarr->number_format($cumulsrules['salairenetcumul'], 2, '.',' ');
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 		$html .= $paiedolibarr->number_format($cumulsrules['retenuscumul'], 2, '.',' ');
	// 				// 	$html .= '</td>';
	// 				// 	$html .= '<td style="width:20%;" align="center">';
	// 				// 	$html .= '</td>';
	// 				// $html .= '</tr>';
	// 			$html .= '</table>';
	// 		$html .= '</td>';
	// 		// Table SITUATION DU PRET
	// 		$html .= '<td style="width:17.5%;">';
	// 			$html .= '<table border="1" cellpadding="4" cellspacing="0" class="" style="width:100%;">'; 
	// 				$html .= '<tr class="">';
	// 					// $html .= '<td colspan="2" style="width:100%;" align="center">'.$langs->trans('LoanStatus').'</td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					// $html .= '<td style="width:50%;" align="left">'.$langs->trans('BeginningMonth').'</td>';
	// 					// $html .= '<td style="width:50%;" align="center"></td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%;" align="left">'.$langs->trans('REMBMonth').'</td>';
	// 					$html .= '<td style="width:50%;" align="center"></td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%;" align="left">'.$langs->trans('EndMonth').'</td>';
	// 					$html .= '<td style="width:50%;" align="center"></td>';
	// 				$html .= '</tr>';
	// 			$html .= '</table>';
	// 		$html .= '</td>';

	// 		// Table SITUATION CONGE
	// 		$html .= '<td style="width:17.5%;">';
	// 			$html .= '<table border="1" cellpadding="4" cellspacing="0" class="" style="width:100%;">'; 
	// 				$html .= '<tr class="">';
	// 					// $html .= '<td style="width:100%;" align="center">'.$langs->trans('LeaveStatus').'</td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					// $html .= '<td style="width:50%; line-height: 8px;" align="left">'.$langs->trans('BeginningBalance').'</td>';
	// 					// $html .= '<td style="width:50%; line-height: 8px;" align="center"></td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%; line-height: 8px;" align="left">'.$langs->trans('AnnualFee').'</td>';
	// 					$html .= '<td style="width:50%; line-height: 8px;" align="center"></td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%; line-height: 8px;" align="left">'.$langs->trans('AnnualLeave').'</td>';
	// 					$html .= '<td style="width:50%; line-height: 8px;" align="center"></td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%; line-height: 8px;" align="left">'.$langs->trans('EndBalance').'</td>';
	// 					$html .= '<td style="width:50%; line-height: 8px;" align="center"></td>';
	// 				$html .= '</tr>';
	// 			$html .= '</table>';
	// 		$html .= '</td>';
	// 		// Table TOTAL GAINS && TOTAL RETENUES
	// 		$html .= '<td style="width:25%;">';
	// 			$html .= '<table border="1" cellpadding="4" cellspacing="0" class="" style="width:100%;">'; 
	// 				$html .= '<tr class="totalligne">';
	// 					// $html .= '<td style="width:50%;" align="center">'.$langs->trans('totalWinnings').'</td>';
	// 					// $html .= '<td style="width:50%;" align="center">'.$langs->trans('TotalWithheld').'</td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%;" align="center"></td>';
	// 					$html .= '<td style="width:50%;" align="center"></td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%;" align="left">'.$langs->trans('PaymentMode').'</td>';
	// 					$html .= '<td style="width:50%;" align="center">'.$langs->trans('netPay').'</td>';
	// 				$html .= '</tr>';
	// 				$html .= '<tr class="totalligne">';
	// 					$html .= '<td style="width:50%;" align="left"></td>';
	// 					$html .= '<td style="width:50%;" align="center"></td>';
	// 				$html .= '</tr>';
	// 			$html .= '</table>';
	// 		$html .= '</td>';
	// 	$html .= '</tr>';
	// $html .= '</table>'; 

	// Table
	$html .= '<table border="0" cellpadding="0" cellspacing="0" class="footertabledown" style="width:100%;">'; 
		$html .= '<tr>';
			$html .= '<td style="width:'.$width_numbers.'%; line-height: 15px;">';
				$html .= '<table border="1" cellpadding="0" cellspacing="0" class="footertabledown" style="width:100%;">'; 
					$html .= '<tr class="totalligne">';
						$html .= '<td style="width:8.03%;" align="center">200</td>';
						$html .= '<td style="width:8.03%;" align="center">100</td>';
						$html .= '<td style="width:8.03%;" align="center">50</td>';
						$html .= '<td style="width:8.03%;" align="center">20</td>';
						$html .= '<td style="width:8.03%;" align="center">10</td>';
						$html .= '<td style="width:8.03%;" align="center">5</td>';
						$html .= '<td style="width:8.03%;" align="center">1</td>';
						$html .= '<td style="width:8.03%;" align="center">0.50</td>';
						$html .= '<td style="width:10.77%;" align="center">0.20</td>';
						$html .= '<td style="width:8.33%;" align="center">0.10</td>';
						$html .= '<td style="width:8.70%;" align="center">0.05</td>';
						$html .= '<td style="width:7.96%;" align="center">0.01</td>';
					$html .= '</tr>';
					
					$html .= '<tr class="totalligne">';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:8.03%;" align="center"></td>';
						$html .= '<td style="width:10.77%;" align="center"></td>';
						$html .= '<td style="width:8.33%;" align="center"></td>';
						$html .= '<td style="width:8.70%;" align="center"></td>';
						$html .= '<td style="width:7.96%;" align="center"></td>';
					$html .= '</tr>';
					$html .= '<tr class="totalligne">';
						$html .= '<td colspan="12" style="width:100%; line-height: 20px;" align="left"></td>';
					$html .= '</tr>';
				$html .= '</table>'; 
			$html .= '</td>'; 
			$html .= '<td style="width:'.$width_signatu.'%;">'; 
				$html .= '<table border="1" cellpadding="0" cellspacing="0" class="footertabledown" style="width:100%;">'; 
					$html .= '<tr>';
							$html .= '<td style="width:100%; line-height: 20px;" align="center">'.$langs->trans('Signature').'</td>';
					$html .= '</tr>';
					$html .= '<tr>';
							$html .= '<td style="width:100%; line-height: 30px;" align="center"></td>';
					$html .= '</tr>';
				$html .= '</table>'; 
			$html .= '</td>'; 
		$html .= '</tr>'; 
	$html .= '</table>'; 



// -------------------------------- END BODY

	// $html .= '<br><br>'; 

$html .= '</div>'; 

if(GETPOST('html') == 1)
die($html);

// Calculate number of days in a month
function days_in_month($month, $year)
{
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}