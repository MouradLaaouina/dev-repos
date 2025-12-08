<?php
$output .= '<meta charset="utf-8"/>';
$output .= '<h1 align="center">'.$langs->trans('paiegestiondepaie').' - '.$period.'</h1>';
global $langs;
$langs->load('admin');

$output .='<style>';
    $output .= '.thead th{background-color:#d9e1f2;}';
$output .= '</style>';
$output .= '<table border="1" width="100%" cellpadding="5px" >';


$totsalairebrut = 0;
$totsalairenet = 0;
$totnetapayer 	= 0;
$totnetapayer 	= 0;

// $rules = $paies2->getRulesByCateg();

// --------------------------------------------------------------------------------------- Header
$output .= '<thead>';
	$output .= '<tr class="thead">';
		if($exporter == 'xls')
			$output .= '<th align="center">'.$langs->trans('N').'</th>'; 
		$output .= '<th align="center">'.$langs->trans('paieFullname').'</th>'; 
		$output .= '<th align="center">'.$langs->trans('paieQualification').'</th>';

		if(!$periodmonth)
			$output .= '<th align="left">'.$langs->trans('paieofmonth').'</th>';

		$output .= '<th align="right"><b>'.$langs->trans('paieSalaire_brut').'</b></th>';

		$output .= '<th align="right"><b>'.$langs->trans('paieNet_imposable').'</b></th>';

		$output .= '<th align="right"><b>'.$langs->trans('paieNet_a_payer').'</b></th>';
	$output .= '</tr>';

	// $output .= '<tr>';

	// 	foreach ($rules['2RETENUES'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}
	// 	foreach ($rules['4CHARGEF'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}
	// 	foreach ($rules['6TAXSOC'] as $key => $rule) {
	// 		$output .= '<td align="center">'.$rule->label.'</td>';
	// 	}

	// $output .= '</tr>';
$output .= '</thead>';

// $nbbrut = ($rules['1BRUT']?count($rules['1BRUT']):0);
// $nb2retenues = ($rules['2RETENUES']?count($rules['2RETENUES']):0);
// $nb4CHARGEF = ($rules['4CHARGEF']?count($rules['4CHARGEF']):0);
// $nbautresretenus = ($rules['6TAXSOC']?count($rules['6TAXSOC']):0);


$colspn = $nbbrut + $nb2retenues + $nb4CHARGEF + $nbautresretenus + 7; 

$colspan = ($exporter == 'xls') ? 3 : 2;

$resql = $db->query($sql);

// --------------------------------------------------------------------------------------- Body
if ($resql) {
	$count = 1;

	$num = $db->num_rows($resql);

	$i = 0;
	
	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		$var = !$var;

		// $rulesofpaie = $paies2->getRulesOfPaieByCateg($obj->rowid);
    	$userpay = new User($db);
    	$userpay->fetch($obj->fk_user);

		$output .= '<tr '.$bc[$var].' >';
			// N°
    		// $output .= '<td align="center">'.trim($obj->ref).'</td>'; 
			if($exporter == 'xls')
    			$output .= '<td align="center">'.trim($count).'</td>'; 
    		
    		// Nom et Prénom (s)
    		$output .= '<td align="left">'.$userpay->getFullName($langs).'</td>';

    		// Fonction
    		$job = $userpay->job ? $userpay->job : '';
    		$output .= '<td align="left">'.trim($job).'</td>';

    		if(!$periodmonth) {
	    		$output .= '<td align="left">';
		    		$periods = explode('-', $obj->period);
			        $periodyear = $periods[0] + 0;
			        $tmpmonth = $periods[1];
			        $period = $langs->trans("Month".sprintf("%02d", $tmpmonth))." ".$periodyear;
			        $output .= $period;
	    		$output .= '</td>';
    		}

    		// Salaire Brut
    		$output .= '<td align="right">';
    		$output .= $paiedolibarr->number_format($obj->salairebrut, 2, '.',' ');
    		$totsalairebrut = $totsalairebrut + $obj->salairebrut;
    		$output .= '</td>';

    		// Salaire Net
    		$output .= '<td align="right">';
    		$output .= $paiedolibarr->number_format($obj->salairenet, 2, '.',' ');
    		$totsalairenet = $totsalairenet + $obj->salairenet;
    		$output .= '</td>';

			// NET A PAYER
    		$netapayer = 0;
    		$output .= '<td align="right">';
    		// round($obj->netapayer,0)
    		$output .= $paiedolibarr->number_format($obj->netapayer, 2, '.',' ');
    		$totnetapayer = $totnetapayer + $obj->netapayer;
    		$output .= '</td>';

		$output .= '</tr>';

		$count++;
		$i++;
	}

	if(!$periodmonth) $colspan++;

	$output .= '<tr class="total">';

		$output .= '<td align="left" colspan="'.$colspan.'"><b>'.$langs->trans("Total").'</b></td>';
		$output .= '<td align="right"><b>'.$paiedolibarr->number_format($totsalairebrut, 2, '.',' ').'</b></td>';
		$output .= '<td align="right"><b>'.$paiedolibarr->number_format($totsalairenet, 2, '.',' ').'</b></td>';
		$output .= '<td align="right"><b>'.$paiedolibarr->number_format($totnetapayer, 2, '.',' ').'</b></td>';

	$output .= '</tr>';

}else{
	$output .= '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
}

$output .= "</table>";

$output .= '
<style>
	td{font-size:9px;}
	th{color:#263c5c;font-weight: bold;font-size:9px;}
	.total{background-color:#d9e1f2;}
</style>';
// d($output);

if($exporter == 'xls') {
	header("Content-Type: application/xls");
	header("Content-Disposition: attachment; filename=".$filename."");
	echo $output;
	die;
}
