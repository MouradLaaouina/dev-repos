<?php
$output .= '<meta charset="utf-8"/>';
$output .= '<h1 align="center">'.$langs->trans('PayrollJournal').' - '.$period.'</h1>';
global $langs;
$langs->load('admin');

$output .='<style>';
    $output .= '.thead th{background-color:#d9e1f2;}';
$output .= '</style>';
$output .= '<table border="1" width="100%" cellpadding="5px" >';


$totalarray = array();
$totalarray['nbfield'] = 0;
$totalduration = 0;

// --------------------------------------------------------------------------------------- Header
$output .= '<thead>';
	$output .= '<tr class="thead">';

		// First day
		$totalarray['nbfield']++;
		$output .= '<th align="center"><b>'.$langs->trans($arrayfields['firstday']['label']).'</b></th>';

		// Last day
		$totalarray['nbfield']++;
		$output .= '<th align="center"><b>'.$langs->trans($arrayfields['lastday']['label']).'</b></th>';

		// Matricule
		$totalarray['nbfield']++;
		$output .= '<th align="right"><b>'.$langs->trans($arrayfields['o.matricule']['label']).'</b></th>';

		// Lastname
		$totalarray['nbfield']++;
		$output .= '<th align="left"><b>'.$langs->trans('LastName').'</b></th>';

		// Firstname
		$totalarray['nbfield']++;
		$output .= '<th align="left"><b>'.$langs->trans('FirstName').'</b></th>';

		// DateOfEmployment
		$totalarray['nbfield']++;
		$output .= '<th align="center"><b>'.$langs->trans($arrayfields['u.dateemployment']['label']).'</b></th>';

		// Fonction
		$totalarray['nbfield']++;
		$output .= '<th align="left"><b>'.$langs->trans($arrayfields['u.job']['label']).'</b></th>';

		// NE
		$nd = 0;
		$totalarray['nbfield']++;
		$output .= '<th align="right"><b>'.$langs->trans($arrayfields['o.nbrenfants']['label']).'</b></th>';

		// ND
		$totalarray['nbfield']++;
		$output .= '<th align="right"><b>'.$langs->trans($arrayfields['nd']['label']).'</b></th>';

		// CIN
		$totalarray['nbfield']++;
		$output .= '<th align="left"><b>'.$langs->trans($arrayfields['eu.paiedolibarrcin']['label']).'</b></th>';

		// CNSS
		$totalarray['nbfield']++;
		$output .= '<th align="left"><b>'.$langs->trans($arrayfields['eu.paiedolibarrcnss']['label']).'</b></th>';




		$totalarray['val']['salairebrut'] = 0;  
		$totalarray['val']['salairenet'] = 0;
		$totalarray['val']['netapayer']	= 0;

		$brut_showed = false;
		$impo_showed = false;

		foreach ($txt_sql['rows'] as $key => $rule) {

			$totalarray['nbfield']++;
			$totalarray['pos'][$totalarray['nbfield']] = $rule->code;
			$output .= '<th align="right"><b>'.$rule->label.'</b></th>';

			if(!$brut_showed && $rule->category == '2RETENUES') {
				$totalarray['nbfield']++;
				$totalarray['pos'][$totalarray['nbfield']] = 'salairebrut';
				$output .= '<th align="right"><b>'.$langs->trans($arrayfields['o.salairebrut']['label']).'</b></th>';
				$brut_showed = true;
			}
			elseif(!$impo_showed && isset($afterretenus[$rule->category])) {
				$totalarray['nbfield']++;
				$totalarray['pos'][$totalarray['nbfield']] = 'salairenet';
				$output .= '<th align="right"><b>'.$langs->trans($arrayfields['o.salairenet']['label']).'</b></th>';
				$impo_showed = true;
			}

		}
		$output .= '<th align="right"><b>'.$langs->trans($arrayfields['o.netapayer']['label']).'</b></th>';
		$totalarray['nbfield']++;
		$totalarray['pos'][$totalarray['nbfield']] = 'netapayer';

	$output .= '</tr>';

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

    	$firstday = date('01/m/Y', strtotime($obj->period));
		$lastday = date('t/m/Y', strtotime($obj->period));

		$output .= '<tr '.$bc[$var].' >';
			// First day
		$output .= '<td class="nowrap" align="center">';
		$output .= $firstday;
		$output .= '</td>';
		

		// Last day
		$output .= '<td class="nowrap" align="center">'.$lastday.'</td>';
		

		// Matricule
		$output .= '<td class="nowrap" align="right">'.$obj->matricule.'</td>';
		

		$output .= '<td class="nowrap" align="left">'.$obj->lastname.'</td>';
		$output .= '<td class="nowrap" align="left">'.$obj->firstname.'</td>';


		// DateOfEmployment
		$output .= '<td align="center">';
			// $output .= dol_print_date($db->jdate($obj->datepay), 'day');
		if($obj->dateemployment)
			$output .= dol_print_date($obj->dateemployment, 'day');
		$output .= '</td>';
		

		// Fonction
		$output .= '<td class="nowrap">';
			$output .= $obj->job;
		$output .= '</td>';
		

		// NE
		$output .= '<td class="nowrap" align="right">';
		if($obj->nbrenfants) $output .= $obj->nbrenfants;
		$output .= '</td>';
		

		// ND
		$nd = 0;
		$output .= '<td class="nowrap" align="right">';
		$nd += (int) $obj->nbrenfants;
		if($obj->situation_f == 'M') $nd++;
		if($nd) $output .= $nd;
		$output .= '</td>';
		

		// CIN
		$output .= '<td class="nowrap" align="left">';
		$output .= $obj->paiedolibarrcin;
		$output .= '</td>';
		

		// CNSS
		$output .= '<td class="nowrap" align="left">';
		$output .= $obj->paiedolibarrcnss;
		$output .= '</td>';
		

		$brut_showed = false;
		$impo_showed = false;
		foreach ($txt_sql['rows'] as $key => $rule) {
			
			$coderule = $rule->code;

				$clscol = $obj->$coderule < 0 ? 'error' : '';
				$output .= '<td align="right" class="nowrap '.$clscol.'">';
					if($obj->$coderule)
					$output .= $paiedolibarr->number_format($obj->$coderule, 2, '.',' ');
		    		$totalarray['val'][$coderule] += $obj->$coderule;
				$output .= '</td>';
			

			if(!$brut_showed && $rule->category == '2RETENUES') {
				// Brut
				$clscol = $obj->salairebrut < 0 ? 'error' : '';
				$output .= '<td align="right" class="nowrap '.$clscol.'">';
					if($obj->salairebrut)
					$output .= $paiedolibarr->number_format($obj->salairebrut, 2, '.',' ');
		    		$totalarray['val']['salairebrut'] += $obj->salairebrut;
				$output .= '</td>';
				

				$brut_showed = true;
			}
			elseif(!$impo_showed && isset($afterretenus[$rule->category])) {
				// Net Impo
				$clscol = $obj->salairenet < 0 ? 'error' : '';
				$output .= '<td align="right" class="nowrap '.$clscol.'">';
					if($obj->salairenet)
					$output .= $paiedolibarr->number_format($obj->salairenet, 2, '.',' ');
					$totalarray['val']['salairenet'] += $obj->salairenet;
				$output .= '</td>';
				

				$impo_showed = true;
			}

		}
		
		
		
		// Net à payer
		$clscol = $obj->netapayer < 0 ? 'error' : '';
		$output .= '<td align="right" class="nowrap '.$clscol.'">';
			$output .= $paiedolibarr->number_format($obj->netapayer, 2, '.',' ');
			$totalarray['val']['netapayer']	+= $obj->netapayer;
		$output .= '</td>';

		$output .= '</tr>';

		$count++;
		$i++;
	}

	if(!$periodmonth) $colspan++;

	if ($num == 0) {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		$output .= '<tr class="total"><td colspan="'.$colspan.'" class="opacitymedium" align="center">'.$langs->trans("NoRecordFound").'</td></tr>';
	} else {

		$output .= '<tr class="total">';
		$i = 0;
		while ($i < $totalarray['nbfield']) {
			$i++;
			if (!empty($totalarray['pos'][$i])) {
				$clscol = ($totalarray['val'][$totalarray['pos'][$i]]) < 0 ? 'error' : '';
				$tmptotal = !empty($totalarray['val'][$totalarray['pos'][$i]])?$totalarray['val'][$totalarray['pos'][$i]]:0;
				$output .= '<td align="right" class="'.$clscol.'"><b>'.$paiedolibarr->number_format($tmptotal, 2, '.',' ').'</b></td>';
			} else {
				if ($i == 1) {
					$output .= '<td><b>'.$langs->trans("Total").'</b></td>';
				} else {
					$output .= '<td></td>';
				}
			}
		}
		$output .= '</tr>';

	}

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
