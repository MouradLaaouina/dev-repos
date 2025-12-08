<?php

$empty = '';
$nbrtest = 0;

$html='<style>';
    $html .= 'td.td-1{width:30%;  border-bottom:2px solid #000; border-right:2px solid #000; text-align:center}';
    $html .= 'td.td-2{width:70%;  border-bottom:2px solid #000; border-left:2px solid #000;}';
    $html .= 'td.td-3{width:30%;  border-top:2px solid #000; border-right:2px solid #000; text-align:center}';
    $html .= 'td.td-4{width:70%;  border-top:2px solid #000; border-left:2px solid #000;}';
    $html .= 'table{width:100%; height:100%;border:1px solid #000;}';
    $html .= '.smallsize td{font-size:8px;}';
    $html .= '.bodytable th{background-color:#e6e6e6;font-size:11px;font-weight:bold;}';
    $html .= '.footertable th{background-color:#e6e6e6;font-size:11px;}';
    $html .= '.bodytable td{font-size:11px;}';
    // $html .= '.bodytable tr.row td{background-color:#fff;}';
    // $html .= '.bodytable tr.row1 td{background-color:#e6e6e6;}';
    // $html .= '.bodytable tr.row1 td{background-color:#e6e6e6;}';
    $html .= '.bodytable tr td{border-left:1px solid #000;border-right:1px solid #000;}';
    $html .= '.bodytable tr.totalligne td{border-top:1px solid #000;border-bottom:1px solid #000;background-color:#e6e6e6;}';
    $html .= '.bodytable tr.totallignebold td{border-top:2px solid #000;}';
    $html .= '.footertable td{font-size:11px;}';
    $html .= '.headertable td{font-size:9px;}';
    $html .= '.reposcomptable td.brdlft{border-left:1px solid #000;}';
    $html .= '.reposcomptable td.brdbtm{border-bottom:1px solid #000;}';
    $html .= 'td.bggray{background-color:#e6e6e6;}';
    $html .= 'tr.bggreen td{background-color:#e6e6e6;}';
    $html .= '.totalrow td{background-color:#e6e6e6;border-top:1px solid #000;border-bottom:1px solid #000;}';
$html .= '</style>';
$object->fetch($id);

$currency = $conf->currency;
$currency = $langs->transnoentitiesnoconv("Currency".$currency);

global $mysoc;
$html .= str_repeat('<br>',2); 

$html .= '<div>'; 

$payrules = $object->getRulesOfPaieByCateg($item->rowid);

$html .= '<table border="1" cellpadding="4" cellspaccing="4" class="headertable" style="width:100%">'; 

	$html .= '<thead>';
		$html .= '<tr>';
		$html .= '<td align="center"  rowspan="2">'.$langs->trans('N°').'</td>'; 
		$html .= '<td align="center" rowspan="2">'.$langs->trans('Nom et Prénom (s)').'</td>'; 
		$html .= '<td align="center" rowspan="2">'.$langs->trans('paieQualification').'</td>';

		foreach ($payrules['1BRUT'] as $key => $rule) {
			$html .= '<td align="center" rowspan="2">'.$rule->label.'</td>';
		}

		$html .= '<td align="center" rowspan="2">'.$langs->trans('paieBrut').'</td>';

		$html .= '<td align="center" colspan="'.count($payrules['RETENUES']).'">'.$langs->trans('Retenues').'</td>';
		$html .= '<td align="center" colspan="'.count($payrules['INDEMNITES']).'">'.$langs->trans('Indemnités').'</td>';

		$html .= '<td align="center" rowspan="2">'.$langs->trans('paieSalaireNet').'</td>';

		$html .= '<td align="center" colspan="'.count($payrules['AUTRESRETENUES']).'">'.$langs->trans('Autres retenues').'</td>';

		$html .= '<td align="center" rowspan="2">'.$langs->trans('paieNet_paye').'</td>';
		$html .= '</tr>';

		$html .= '<tr>';

		foreach ($payrules['RETENUES'] as $key => $rule) {
			$html .= '<td align="center">'.$rule->label.'</td>';
		}
		foreach ($payrules['INDEMNITES'] as $key => $rule) {
			$html .= '<td align="center">'.$rule->label.'</td>';
		}
		foreach ($payrules['AUTRESRETENUES'] as $key => $rule) {
			$html .= '<td align="center">'.$rule->label.'</td>';
		}

		$html .= '</tr>';

	$html .= '</thead>';

	// $html .= '<tbody>';
	// $payrules = $object->getRulesOfPaieByCateg($item->rowid);
	// // print_r($payrules);die;
 //    $i = 1;
 //    $totbrut = 0; $ptrtotbrut = 0;
 //    $totcotisation = 0; $ptrtotcotisation = 0;
 //    $basic = '';
 //    if(isset($payrules['BASIQUE'][0]))
 //    	$basic = $payrules['BASIQUE'][0];

 //    $nbrlines = 0;
 //    $var = true;

	// $var = !$var;
	// $rule = $basic;
	// $html .= '<tr class="row'.$var.'">';

	// $html .= '</tr>';
	// $html .= '</tbody>';
$html .= '</table>';


$html .= '</div>'; 





// die($html);

















// Calculate number of days in a month
function days_in_month($month, $year)
{
	return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
}