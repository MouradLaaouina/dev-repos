<?php
$output .= '<meta charset="utf-8"/>';
$output .= '<h1 align="center">'.$langs->trans('paiegestiondepaie').' - '.$period.'</h1>';
global $langs;
$langs->load('admin');

$output .= '<table border="1" width="100%" cellpadding="5px" >';


$totsalairebrut = 0;
$totsalairenet 	= 0;
$totnetapayer 	= 0;

$rules = $paies2->getRulesByCateg();

// --------------------------------------------------------------------------------------- Header
$output .= '<thead>';
	$output .= '<tr>';
		if($exporter == 'xls')
			$output .= '<th align="center">'.$langs->trans('N').'</th>'; 
		$output .= '<th align="center">'.$langs->trans('paieFullname').'</th>'; 
		$output .= '<th align="center">'.$langs->trans('paieQualification').'</th>';
		// $output .= '<th align="center">'.$langs->trans('paieParts').'</th>';

		if(count($rules['1BRUT'])) {
			foreach ($rules['1BRUT'] as $key => $rule) {
				$output .= '<th align="center">'.$rule->label.'</th>';
			}
		}

		$output .= '<th align="center"><b>'.$langs->trans('paieSalaire_brut').'</b></th>';

		// $output .= '<th align="center" colspan="'.count($rules['RETENUES']).'"><b>'.$langs->trans('RETENUES').'</b></th>';
		// $output .= '<th align="center" colspan="'.count($rules['INDEMNITES']).'"><b>'.$langs->trans('INDEMNITES').'</b></th>';

		if(count($rules['RETENUES'])) {
			foreach ($rules['RETENUES'] as $key => $rule) {
				$output .= '<th align="center">'.$rule->label.'</th>';
			}
		}
		if($rules['INDEMNITES']){

			if(count($rules['INDEMNITES'])) {
				foreach ($rules['INDEMNITES'] as $key => $rule) {
					$output .= '<th align="center">'.$rule->label.'</th>';
				}
			}
		}

		// $output .= '<th align="center"><b>'.$langs->trans('paieSalaireNet').'</b></th>';

		if(is_array($rules['AUTRESRETENUES']) && count($rules['AUTRESRETENUES'])) {
			foreach ($rules['AUTRESRETENUES'] as $key => $rule) {
				$output .= '<th align="center">'.$rule->label.'</th>';
			}
		}
		// $output .= '<th align="center" colspan="'.count($rules['AUTRESRETENUES']).'"><b>'.$langs->trans('AUTRESRETENUES').'</b></th>';

		$output .= '<th align="center"><b>'.$langs->trans('paieNet_a_payer').'</b></th>';
	$output .= '</tr>';

	// $output .= '<tr>';

	// 	foreach ($rules['RETENUES'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}
	// 	foreach ($rules['INDEMNITES'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}
	// 	foreach ($rules['AUTRESRETENUES'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}

	// $output .= '</tr>';
$output .= '</thead>';

$nbbrut = ($rules['1BRUT']?count($rules['1BRUT']):0);
$nbretenues = ($rules['RETENUES']?count($rules['RETENUES']):0);
$nbindemnites = ($rules['INDEMNITES']?count($rules['INDEMNITES']):0);
$nbautresretenus = ($rules['AUTRESRETENUES']?count($rules['AUTRESRETENUES']):0);


   $colspn = $nbbrut + $nbretenues + $nbindemnites + $nbautresretenus + 7; 


// --------------------------------------------------------------------------------------- Body
if (count($paies2->rows) > 0) {
	$count = 1;
	for ($i=0; $i < count($paies2->rows) ; $i++) {
		$var = !$var;
		$item = $paies2->rows[$i];

		$rulesofpaie = $paies2->getRulesOfPaieByCateg($item->rowid);
    	$userpay = new User($db);
    	$userpay->fetch($item->fk_user);

		$output .= '<tr '.$bc[$var].' >';
			// N°
    		// $output .= '<td align="center">'.trim($item->ref).'</td>'; 
			if($exporter == 'xls')
    			$output .= '<td align="center">'.trim($count).'</td>'; 
    		
    		// Nom et Prénom (s)
    		$output .= '<td align="left">'.$userpay->getFullName($langs).'</td>';

    		// Fonction
    		$job = $userpay->job ? $userpay->job : '';
    		$output .= '<td align="left">'.trim($job).'</td>';

			// // Parts
			// $parts = '';
			// $dt = $userpay->array_options;
			// if(isset($dt['options_paiedolibarrparts'])){
			//     $parts = $dt['options_paiedolibarrparts'];
			// }
			// $output .= '<td align="right">'.$parts.'</td>';

    		// BRUT COLUMNS
    		if(count($rules['1BRUT'])) {
	    		foreach ($rules['1BRUT'] as $key => $rule) {
	    			$obrp = isset($rulesofpaie['1BRUT'][$rule->code]) ? $rulesofpaie['1BRUT'][$rule->code] : 0;
		    		$output .= '<td align="right">';
		    		if($obrp)
		    			$output .= $paiedolibarr->number_format($obrp->total, 2, '.',' ');
		    		$output .= '</td>';
				}
			}
    		// Salaire Brut
    		$salairebrut = 0;
    		$output .= '<td align="right">';
    		$output .= $paiedolibarr->number_format($item->salairebrut, 2, '.',' ');
    		$totsalairebrut = $totsalairebrut + $item->salairebrut;
    		$output .= '</td>';


    		// RETENUES COLUMNS
    		if(count($rules['RETENUES'])) {
	    		foreach ($rules['RETENUES'] as $key => $rule) {
		    		$obrp = isset($rulesofpaie['RETENUES'][$rule->code]) ? $rulesofpaie['RETENUES'][$rule->code] : 0;
		    		$output .= '<td align="right">';
		    		if($obrp)
		    			$output .= $paiedolibarr->number_format($obrp->total, 2, '.',' ');
		    		$output .= '</td>';
				}
			}
    		// INDEMNITES COLUMNS
    		if($rules['INDEMNITES']){
    			
	    		if(count($rules['INDEMNITES'])) {
		    		foreach ($rules['INDEMNITES'] as $key => $rule) {
			    		$obrp = isset($rulesofpaie['INDEMNITES'][$rule->code]) ? $rulesofpaie['INDEMNITES'][$rule->code] : 0;
			    		$output .= '<td align="right">';
			    		if($obrp)
			    			$output .= $paiedolibarr->number_format($obrp->total, 2, '.',' ');
			    		$output .= '</td>';
					}
				}
    		}
			// // Salaire Net
   //  		$salairenet = 0;
   //  		$output .= '<td align="right">';
   //  		$output .= $paiedolibarr->number_format($item->salairenet, 2, '.',' ');
   //  		$totsalairenet = $totsalairenet + $item->salairenet;
   //  		$output .= '</td>';

    		// AUTRESRETENUES COLUMNS
    		if(is_array($rules['AUTRESRETENUES']) && count($rules['AUTRESRETENUES'])) {
	    		foreach ($rules['AUTRESRETENUES'] as $key => $rule) {
		    		$obrp = isset($rulesofpaie['AUTRESRETENUES'][$rule->code]) ? $rulesofpaie['AUTRESRETENUES'][$rule->code] : 0;
		    		$output .= '<td align="right">';
		    		if($obrp)
		    			$output .= $paiedolibarr->number_format($obrp->total, 2, '.',' ');
		    		$output .= '</td>';
				}
			}

			// NET A PAYER
    		$netapayer = 0;
    		$output .= '<td align="right">';
    		// round($item->netapayer,0)
    		$output .= $paiedolibarr->number_format($item->netapayer, 2, '.',' ');
    		$totnetapayer = $totnetapayer + $item->netapayer;
    		$output .= '</td>';

		$output .= '</tr>';

		$count++;
	}

	// $output .= '<tr>';

	// 	$output .= '<td align="left" colspan="'.$colspn.'">'.$langs->trans("Total").'</td>';
	// 	$output .= '<td align="center">'.$rule->label.'</td>';
	// 	foreach ($rules['INDEMNITES'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}
	// 	foreach ($rules['AUTRESRETENUES'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}

	// $output .= '</tr>';

}else{
	$output .= '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
}

$output .= "</table>";

$output .= '
<style>
	td{font-size:9px;}
	th{color:#263c5c;font-weight: bold;font-size:9px;}
</style>';
// d($output);

if($exporter == 'xls') {
	header("Content-Type: application/xls");
	header("Content-Disposition: attachment; filename=".$filename."");
	echo $output;
	die;
}
