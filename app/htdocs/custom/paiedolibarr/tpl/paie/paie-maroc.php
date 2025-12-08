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
    $html .= 'table{width:100%; height:100%;border:1px solid #000;}';
    $html .= '.smallsize td{font-size:8px;}';
    $html .= '.bodytable th{background-color:#d9e1f2;font-size:10px;font-weight:bold;}';
    $html .= '.footertable th{background-color:#d9e1f2;font-size:10px;}';
    $html .= '.bodytable td{font-size:10px;}';
    // $html .= '.bodytable tr.row td{background-color:#fff;}';
    // $html .= '.bodytable tr.row1 td{background-color:#d9e1f2;}';
    // $html .= '.bodytable tr.row1 td{background-color:#d9e1f2;}';
    $html .= '.bodytable tr td{border-left:1px solid #000;border-right:1px solid #000;}';
    $html .= '.bodytable tr.totalligne td{border-top:1px solid #000;border-bottom:1px solid #000;background-color:#e6e6e6;}';
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
	$html .= '<table border="1" cellpadding="5" cellspacing="0" class="headertable" style="width:100%;">'; 

	$html .= '<tr class="bggreen">';
	$html .= '<td style="width:42%;" colspan="3" align="center"><h3><b>'.$carac_soci.'</b></h3></td>'; 
	$html .= '<td style="width:16%;" colspan="3" align="center">'; 
		$html .= '<b>'.$langs->trans('Aff.  CNSS').'</b><br>'; 
		if (!empty($mysoc->idprof2))
        {
            $html .= $langs->convToOutputCharset($mysoc->idprof2);
        }
	$html .= '</td>';  
	$html .= '<td style="width:24%;" align="center">'; 
		$stringaddress = $langs->convToOutputCharset(dol_format_address($mysoc, 0, ", ", $langs))."<br>";
		$html .= $stringaddress; 
	$html .= '</td>';  
	$html .= '<td style="width:18%;" align="center"><h3><b>'.$langs->trans('paieBulletin_de_paie').'</b></h3></td>';  

	$html .= '</tr>';

	$html .= '<tr>';
	$html .= '<td style="" align="center"> '.$langs->trans('paieMatricule').' </td>'; 
	$html .= '<td style="" colspan="5" align="center"> '.$langs->trans('paieFullname').'</td>'; 
	$html .= '<td colspan="2" align="center"> '.$langs->trans('paiePeriode').'</td>'; 
	$html .= '</tr>';

	$html .= '<tr>';
	$html .= '<td align="center"><b>'.$employeeinfo['matricule'].'</b></td>'; 
	$html .= '<td colspan="5" align="center"><b>'.$employeeinfo['name'].'</b></td>'; 
	$html .= '<td colspan="2" align="center">';
	$html .= '<b>'.$first.' au '.$last.'</b>'; 
	$html .= '</td>'; 
	$html .= '</tr>';

	$html .= '<tr>';
	$html .= '<td align="center">'.$langs->trans('paieDate_d_embauche').'</td>'; 
	$html .= '<td style="" align="center">'.$langs->trans('paiecin').'</td>'; 
	$html .= '<td style="" align="center">'.$langs->trans('DateOfBirth').'</td>'; 
	$html .= '<td style="" align="center">'.$langs->trans('SF').'</td>';  
	$html .= '<td style="" align="center">'.$langs->trans('NE').'</td>';  
	$html .= '<td style="" align="center">'.$langs->trans('ND').'</td>';  
	$html .= '<td colspan="2" align="center">'.$langs->trans('Adressedusalarie').'</td>';
	$html .= '</tr>';

	$html .= '<tr>';
	$html .= '<td align="center"><b>'.$dateemployment.'</b></td>'; 
	$html .= '<td align="center"><b>'.$employeeinfo['cin'].'</b></td>';  
	$html .= '<td align="center"><b>'.$datebirth.'</b></td>'; 
	$html .= '<td align="center"><b>'.$employeeinfo['situation_f'].'</b></td>';  
	$html .= '<td align="center"><b>'.$employeeinfo['nbrenfants'].'</b></td>';  
	$html .= '<td align="center"><b>'.$employeeinfo['nd'].'</b></td>';  
	$html .= '<td colspan="2" align="center"><b>'.$employeeinfo['adresse'].'</b></td>'; 
	$html .= '</tr>';

	$html .= '<tr>';
	$html .= '<td align="center">'.$langs->trans('paiecnss').'</td>';
	$html .= '<td align="center">'.$langs->trans('paiecimr').'</td>';
	$html .= '<td align="center">'.$langs->trans('Service').'</td>'; 
	$html .= '<td colspan="3" align="center">'.$langs->trans('paiePaymentRule').'</td>'; 
	$html .= '<td colspan="2" align="center">'.$langs->trans('paieEmploi_occupe').'</td>'; 
	$html .= '</tr>';

	$html .= '<tr>';
	$html .= '<td align="center"><b>'.$employeeinfo['cnss'].'</b></td>';
	$html .= '<td align="center"><b>'.$employeeinfo['cimr'].'</b></td>';
	$html .= '<td align="center"><b>'.$employeeinfo['service'].'</b></td>';
	$html .= '<td colspan="3" align="center"><b>'.$payedwith.'</b></td>'; 
	$html .= '<td colspan="2" align="center"><b>'.$employeeinfo['job'].'</b></td>'; 
	$html .= '</tr>';

	$html .= '</table>'; 
	$html .= '<br><br>'; 
// -------------------------------- END HEADER




















$cumulsrules = $object->getCumulOfRules($item);

$colspanfirst = 2;
$nbrlines = 0;

if(isset($cumulsrules['byrules'])) $colspanfirst += count($cumulsrules['byrules']);



// -------------------------------- BODY
	$html .= '<table border="1" cellpadding="5" cellspacing="0" class="bodytable" style="width:100%;">'; 

	$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<th align="center" colspan="'.$colspanfirst.'">'.$langs->trans('paieDesignation').'</th>'; 
		$html .= '<th align="center">'.$langs->trans('paieBase').'</th>'; 
		$html .= '<th align="center">'.$langs->trans('NbreOuTaux').'</th>'; 
		$html .= '<th align="center">'.$langs->trans('Gains').'</th>'; 
		$html .= '<th align="center">'.$langs->trans('Retenues').'</th>'; 

		$html .= '</tr>';
	$html .= '</thead>';

	$html .= '<tbody>';
	$payrules = $object->getRulesOfPaieByCateg($item->rowid);
	
	// d($payrules);die;

    $i = 1;
    $totbrut = 0; $totretenus = 0;
    $totcotisation = 0; $ptrtotcotisation = 0;

    $brut_showed = false;
    $impo_showed = false;

	$emptytr = '<tr><td colspan="'.$colspanfirst.'"></td><td></td><td></td><td></td><td></td></tr>';

    // $trscolored = ['3IRBRUT' => 1,'5IRNET' => 1,'6TAXSOC' => 1,'99ARRONDI' => 1];
    $trscolored = ['5IRNET' => 1,'6TAXSOC' => 1,'99ARRONDI' => 1];
    $afterretenus = $object->afterretenus;
    $notcalculed = ['FRAIS', 'CHARGE', 'IR_B'];

    $notcalculed = array_flip($notcalculed);

    $var = 0;

    foreach ($payrules as $key0 => $arrrules) {

    	foreach ($arrrules as $key => $rule) {

    		if(!(float)$rule->amount && !isset($trscolored[$key0]) || ($key0 == '99ARRONDI' && $rule->total == 0)) continue;

	    	$var = !$var;

	    	// ========================================================================================================================================================
	    	if(!$brut_showed && $key0 == '2RETENUES') {
	    		$html .= '<tr class="totalligne">';
					$html .= '<td align="'.$left.'" colspan="'.$colspanfirst.'">'; 
						$html .= $langs->trans('paieSalaire_Brut_Imposable');
					$html .= '</td>';
					
					$html .= '<td></td>';
					$html .= '<td></td>';

					$html .= '<td align="'.$right.'">';
					$html .= $paiedolibarr->number_format($totbrut, 2, '.',' ');
					$html .= '</td>';
					$html .= '<td align="'.$right.'">'; 
					$html .= '</td>';
				$html .= '</tr>';
				// $html .= $emptytr;
				$brut_showed = true;
	    	}
	    	// ========================================================================================================================================================

	    	elseif(!$impo_showed && isset($afterretenus[$key0])) {
				$html .= $emptytr;
	    		$html .= '<tr class="totalligne">';
					$html .= '<td align="'.$left.'" colspan="'.$colspanfirst.'">'; 
						$html .= $langs->trans('paieNet_imposable');
					$html .= '</td>';
					
					$html .= '<td></td>';
					$html .= '<td></td>';

					$html .= '<td align="'.$right.'">';
					$html .= $paiedolibarr->number_format($object->salairenet, 2, '.',' ');
					$html .= '</td>';
					$html .= '<td align="'.$right.'">'; 
					$html .= '</td>';
				$html .= '</tr>';
				$impo_showed = true;
	    	}

	    	// ========================================================================================================================================================

    		$othcls = '';
	    	if(isset($trscolored[$key0])) {
	    		$othcls = 'totalligne';
				$html .= $emptytr;
	    	}

	    	$html .= '<tr class="row'.$var.' '.$othcls.' engras'.(isset($rule->engras) ? $rule->engras : '').'">';

		        // Rubriques
		        $html .= '<td align="'.$left.'" colspan="'.$colspanfirst.'">'; 
		        $html .= $rule->label;
		        $html .= '</td>';
				
		        // Base
		     	$html .= '<td align="'.$right.'">';
		     		// if($rule->amount > 0 && (($rule->taux != 100 && $key0 != '1BRUT') || $key0 == '1BRUT'))

		     		if($key0 == '5IRNET' || $key0 == '99ARRONDI')
		     			$html .= '';
		     		elseif($key0 == '3IRBRUT')
		     			$html .= $paiedolibarr->number_format($object->salairenet, 2, '.',' ');
		     		elseif($rule->amount > 0)
		     			$html .= $paiedolibarr->number_format($rule->amount, 2, '.',' ');
		     		
		        $html .= '</td>';
		        // Nbre ou taux
		        $html .= '<td align="'.$right.'">'; 
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
		        $html .= '<td align="'.$right.'">'; 
		        	if($apayer > 0)
			        	$html .= $paiedolibarr->number_format($apayer, 2, '.',' ');
		        $html .= '</td>';

		        // Retenues
		        $html .= '<td align="'.$right.'">'; 
		        	if($adeduire > 0)
			        	$html .= $paiedolibarr->number_format($adeduire, 2, '.',' ');
		        $html .= '</td>';

	        $html .= '</tr>';
	    	// ========================================================================================================================================================
	        $nbrlines++;
    	}
    }

  	// $totnet = $totbrut - $totcotisation;
  	$maxlings = 20;
    if($nbrlines < $maxlings){
    	$rest = ($maxlings-$nbrlines);
    	for ($z=0; $z < $rest; $z++) { 
    		$var = !$var;
        	$html .= '<tr class="row'.$var.'">';
        	$html .= '<td colspan="'.$colspanfirst.'"></td>';
        	$html .= str_repeat('<td></td>',4);
        	$html .= '</tr>';
    	}
    }



	$html .= '<tr class="totalligne">';
		// $html .= '<td align="'.$right.'" colspan="'.$colspanfirst.'">'; 
		// 	$html .= '<table border="0" cellpadding="2" cellspacing="0" class="cumultable" style="width:100%">'; 
		// 		$html .= '<tr class="totalligne">';
		// 			$html .= '<td class="firsttd1" align="'.$right.'">';
		// 				$html .= $langs->trans('Cumul').' '.$langs->trans('paieBrut');
		// 			$html .= '</td>';
		// 			$html .= '<td class="" align="'.$right.'">';
		// 				$html .= $langs->trans('Cumul').' '.$langs->trans('paieNet_imposable');
		// 			$html .= '</td>';
		// 			foreach ($cumulsrules['byrules'] as $key7 => $datares) {
		// 				$html .= '<td class="" align="'.$right.'">';
		// 						$html .= $langs->trans('Cumul').' '.$datares['rulelabel'];
		// 				$html .= '</td>';
		// 			}
		// 		$html .= '</tr>';
		// 	$html .= '</table>'; 
		// $html .= '</td>';
		
		$html .= '<td class="" align="'.$right.'">';
			$html .= $langs->trans('Cumul').' '.$langs->trans('paieBrut');
		$html .= '</td>';
		$html .= '<td class="" align="'.$right.'">';
			$html .= $langs->trans('Cumul').' '.$langs->trans('paieNet_imposable');
		$html .= '</td>';
		foreach ($cumulsrules['byrules'] as $key7 => $datares) {
			$html .= '<td class="" align="'.$right.'">';
					$html .= $langs->trans('Cumul').' '.$datares['rulelabel'];
			$html .= '</td>';
		}

		$html .= '<td align="'.$right.'">';
			$html .= $langs->trans('Cumul').' NET';
		$html .= '</td>';
		$html .= '<td align="'.$right.'">';
		$html .= '</td>';

		$html .= '<td align="'.$right.'">';
		$html .= $paiedolibarr->number_format($totbrut, 2, '.',' ');
		$html .= '</td>';
		$html .= '<td align="'.$right.'">'; 
		$html .= $paiedolibarr->number_format($totretenus, 2, '.',' ');
		$html .= '</td>';
	$html .= '</tr>';

	// NET
	$html .= '<tr class="totalligne">';
		// $html .= '<td align="'.$right.'" colspan="'.$colspanfirst.'">'; 
		// $html .= '<table border="0" cellpadding="2" cellspacing="0" class="cumultable" style="width:100%">'; 
		// 		$html .= '<tr class="totalligne">';
		// 			$html .= '<td class="firsttd1" align="'.$right.'">';
		// 				$html .= $paiedolibarr->number_format($cumulsrules['brutcumul'], 2, '.',' ');
		// 			$html .= '</td>';
		// 			$html .= '<td class="" align="'.$right.'">';
		// 				$html .= $paiedolibarr->number_format($cumulsrules['salairenetcumul'], 2, '.',' ');
		// 			$html .= '</td>';
		// 			foreach ($cumulsrules['byrules'] as $key7 => $datares) {
		// 				$html .= '<td class="" align="'.$right.'">';
		// 						$html .= $paiedolibarr->number_format($datares['totalcumul'], 2, '.',' ');
		// 				$html .= '</td>';
		// 			}
		// 		$html .= '</tr>';
		// 	$html .= '</table>';
		// $html .= '</td>';
		$html .= '<td class="" align="'.$right.'">';
			$html .= $paiedolibarr->number_format($cumulsrules['brutcumul'], 2, '.',' ');
		$html .= '</td>';
		$html .= '<td class="" align="'.$right.'">';
			$html .= $paiedolibarr->number_format($cumulsrules['salairenetcumul'], 2, '.',' ');
		$html .= '</td>';
		foreach ($cumulsrules['byrules'] as $key7 => $datares) {
			$html .= '<td class="" align="'.$right.'">';
					$html .= $paiedolibarr->number_format($datares['totalcumul'], 2, '.',' ');
			$html .= '</td>';
		}
	
		$html .= '<td align="'.$right.'">';
		$html .= $paiedolibarr->number_format($cumulsrules['netapayercumul'], 2, '.',' ');
		$html .= '</td>';
		$html .= '<td align="'.$right.'">';
		$html .= '</td>';

		$html .= '<td align="'.$right.'"><b>'.$langs->trans('paieNet_a_payer').'</b></td>';
		$html .= '<td align="'.$right.'"><b>'; 
		$html .= $paiedolibarr->number_format($item->netapayer, 2, '.',' ');
		$html .= '</b></td>';
	$html .= '</tr>';




	$html .= '</tbody>';

	$html .= '</table>'; 
// -------------------------------- END BODY

	$html .= '<br><br>'; 

$html .= '</div>'; 

// die($html);

// Calculate number of days in a month
function days_in_month($month, $year)
{
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}