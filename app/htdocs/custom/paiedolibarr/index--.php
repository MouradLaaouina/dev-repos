<?php
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 
global $conf;

if (!$conf->paiedolibarr->enabled) {
	accessforbidden();
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
$paiedolibarr = new paiedolibarr($db);
$res = $paiedolibarr->upgradeTheModule();

dol_include_once('/paiedolibarr/class/paiedolibarr_paies.class.php');
dol_include_once('/core/class/html.form.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('paiedolibarr@paiedolibarr');

$modname = $langs->trans("paiesemployee");


$paies 	= new paiedolibarr_paies($db);
$paies2 = new paiedolibarr_paies($db);

// $paies->calculatePaieRules(16,1);

$form        	= new Form($db);
$formother 		= new FormOther($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "DESC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];

if (!$user->rights->paiedolibarr->lire && !$user->admin) {
	accessforbidden();
}

$srch_ref 		= GETPOST('srch_ref');
$srch_label 	= GETPOST('srch_label');
$srch_fk_user 	= GETPOST('srch_fk_user');
$srch_salairebrut = GETPOST('srch_salairebrut');
$srch_salairenet= GETPOST('srch_salairenet');
$srch_netapayer = GETPOST('srch_netapayer');
$periodyear 	= GETPOST('periodyear','int');
$periodmonth 	= GETPOST('periodmonth','int');

if (!$periodyear){
	$periodyear = date('Y');
}
if (!$periodmonth){
	// $periodmonth = date('m') + 0;
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) {
	$filter = "";
	$offset = 0;
	$filter = "";
	$srch_ref = "";
	$srch_label = "";
	$srch_salairebrut = "";
	$srch_salairenet = "";
	$srch_netapayer = "";
	$srch_fk_user = "";
	$periodyear = date('Y');
	// $periodmonth = date('m') + 0;
	$periodmonth = "";
}

// $date = explode('/', $srch_date);
// $date = $date[2]."-".$date[1]."-".$date[0];
$filter .= (!empty($srch_ref)) ? " AND ref like '%".$srch_ref."%'" : "";
$filter .= (!empty($srch_label)) ? " AND label like '%".$srch_label."%'" : "";
$filter .= (!empty($srch_salairebrut)) ? " AND salairebrut like '%".$srch_salairebrut."%'" : "";
$filter .= (!empty($srch_salairenet)) ? " AND salairenet like '%".$srch_salairenet."%'" : "";
$filter .= (!empty($srch_netapayer)) ? " AND netapayer like '%".$srch_netapayer."%'" : "";
$filter .= (!empty($srch_fk_user) && ($srch_fk_user != -1)) ? " AND fk_user = '".$srch_fk_user."'" : "";
// $filter .= (!empty($srch_date)) ? " AND CAST(date as date) = '".$date."' " : "";

$srchperiod = $periodyear.'-'.sprintf("%02d", $periodmonth).'-01';

if($periodmonth)
	$filter .= " AND period = '".$srchperiod."'";
else
	$filter .= " AND YEAR(period) = '".$periodyear."'";


$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
// $limit = $limit+1;
$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;



$num = $paies->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
$nbtotalofrecords = $paies2->fetchAll($sortorder, $sortfield, "", "", $filter);

if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
} 
$num = $nbtotalofrecords;

if($periodmonth){
	$period = $langs->trans("Month".sprintf("%02d", $periodmonth))." ".$periodyear;
}else{
	$period = $periodyear;
}

$exporter = GETPOST('exporter');
if($exporter){
	$filename = $langs->trans('paiegestiondepaie').' '.$period.".xls";
	if($exporter == 'pdf') {
		$filename=$langs->trans('paiegestiondepaie').' '.$period.".pdf";

	    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	    require_once dol_buildpath('/paiedolibarr/pdf/pdf.lib.php');

	    $pdf->SetMargins(7, 2, 7, false);
	    $pdf->SetFooterMargin(10);
	    $pdf->setPrintFooter(true);
	    $pdf->SetAutoPageBreak(TRUE,10);

	    $pdf->SetFont('times', '', 9, '', true);
        // $pdf->AddPage('L');
        $pdf->AddPage('P');

        $formatarray = pdf_getFormat();

        $marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
        $marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;
        $margin = $marge_haute+$marge_basse+45;

        $page_largeur = $formatarray['width'];
        $page_hauteur = $formatarray['height'];
        $format = array($page_largeur,$page_hauteur);

        $marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:8;
        $marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:8;
        $marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:7;
        $marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:5;
        $emetteur = $mysoc;

        $default_font_size = pdf_getPDFFontSize($langs);

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('helvetica','', $default_font_size + 2);

        $posx=$marge_gauche;
        $posy=$marge_haute;

        $height = 15;
        // Logo
        $logo=$conf->mycompany->dir_output.'/logos/'.$emetteur->logo;

        if ($emetteur->logo)
        {
            if (is_readable($logo))
            {
                // $height=pdf_getHeightForLogo($logo);
                $pdf->Image($logo, $marge_gauche, $posy, 0, $height); // width=0 (auto)
            }
            else
            {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('helvetica','B', $default_font_size -2);
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        }
        else
        {
            $text=$emetteur->name;
            $pdf->MultiCell(40, 2, $langs->convToOutputCharset($text), 0, 'L');
        }

        $posy += $height+5;

        $pdf->SetXY($posx,$posy);

	}

    require_once dol_buildpath('/paiedolibarr/tpl/gestion_paie.php');


	if($exporter == 'pdf') {
		$pdf->writeHTML($output, true, false, true, false, '');
	    ob_start();
	    $pdf->Output($filename, 'I');
	    // ob_end_clean();
	    die();
	}
}

$morejs  = array();
llxHeader(array(), $modname,'');

$param = '';
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($srch_ref) $param .= '&srch_ref='.urlencode($srch_ref);
if ($srch_label) $param .= '&srch_label='.urlencode($srch_label);
if ($srch_fk_user) $param .= '&srch_fk_user='.urlencode($srch_fk_user);
if ($srch_salairebrut) $param .= '&srch_salairebrut='.urlencode($srch_salairebrut);
if ($srch_salairenet) $param .= '&srch_salairenet='.urlencode($srch_salairenet);
if ($srch_netapayer) $param .= '&srch_netapayer='.urlencode($srch_netapayer);
if ($periodyear) $param .= '&periodyear='.urlencode($periodyear);
if ($periodmonth) $param .= '&periodmonth='.urlencode($periodmonth);

print '<div id="paiedolibarrpage">';
print '<form name="selectperiod" method="POST" action="'.$_SERVER["PHP_SELF"].'?mainmenu=hrm" class="paiedolibarrform">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="selectperiod">';
print '<input type="hidden" name="id" value="'.$proejectid.'">';
print '<input name="filter" type="hidden" value="'.$filter.'">';
$newcardbutton .= dolGetButtonTitle($langs->trans('NewPaie'), '', 'fa fa-plus-circle', 'card.php?action=create');
print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num, $nbtotalofrecords, 'user', 0, $newcardbutton, '', $limit, 0, 0, 1);

print '<table width="100%">';

print '<tr >';
print '<td align="center">'.$formother->selectyear($periodyear,'periodyear',0, 20, 1, 0, 0, 'onchange="this.form.submit()"').$formother->select_month($periodmonth,'periodmonth',1,1,'maxwidth100imp');
print '<span class="paiesearchbutton">';
print '<a href="./index.php?exporter=pdf&periodyear='.$periodyear.'&periodmonth='.$periodmonth.'" target="_blank" class="butAction">'.$langs->trans('PDF').'</a>';
print '<a href="./index.php?exporter=xls&periodyear='.$periodyear.'&periodmonth='.$periodmonth.'" class="butAction">'.$langs->trans('Excel').'</a>';
// $searchpicto = $form->showFilterButtons();
// print $searchpicto;
print '</span>';
print '</td>';

print "</table>";

print '</form>';
print '</div>';
print '<br>';


$currency = $conf->currency;

print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input name="pagem" type="hidden" value="'.$page.'">';
print '<input name="limit" type="hidden" value="'.$limit.'">';
print '<input name="periodyear" type="hidden" value="'.$periodyear.'">';
print '<input name="periodmonth" type="hidden" value="'.$periodmonth.'">';


print '<table id="table-1" class="noborder" style="width: 100%;" >';
print '<thead>';

print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"], "ref", '', '', 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("paiedolibarremploye"),$_SERVER["PHP_SELF"], "fk_user", '', '', 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("paiesname"),$_SERVER["PHP_SELF"], "label", '', '', 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("paieofmonth"),$_SERVER["PHP_SELF"], "", '', '', 'align="left"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("paieSalaireBrut").' ',$_SERVER["PHP_SELF"], "", '', '', 'align="right"', $sortfield, $sortorder);
	// print_liste_field_titre($langs->trans("paieSalaireNet").' ',$_SERVER["PHP_SELF"], "", '', '', 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("paieNet_a_payer").' ',$_SERVER["PHP_SELF"], "", '', '', 'align="right"', $sortfield, $sortorder);
	print '<th align="center"></th>';

print '</tr>';

print '<tr class="liste_titre nc_filtrage_tr">';

print '<td align="left"><input style="max-width: 129px;" class="" type="text" class="" id="srch_ref" name="srch_ref" value="'.$srch_ref.'"/></td>';

print '<td align="left">';
print $form->select_dolusers($srch_fk_user, 'srch_fk_user', 1, array(), 0, '', 0, 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td>';
print '<td align="left"><input style="width:80%;" class="" type="text" class="" id="srch_label" name="srch_label" value="'.$srch_label.'"/></td>';
print '<td align="left"></td>';
print '<td align="right"><input style="width:100px;" class="" step="0.01" type="number" class="" id="srch_salairebrut" name="srch_salairebrut" value="'.$srch_salairebrut.'"/></td>';
// print '<td align="right"><input style="width:100px;" class="" step="0.01" type="number" class="" id="srch_salairenet" name="srch_salairenet" value="'.$srch_salairenet.'"/></td>';
print '<td align="right"><input style="width:100px;" class="" step="0.01" type="number" class="" id="srch_netapayer" name="srch_netapayer" value="'.$srch_netapayer.'"/></td>';

print '<td align="center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
print '</td>';
print '</tr>';


print '</thead><tbody>';
	$colspn = 8;

	$totsalairebrut = 0;
	$totsalairenet 	= 0;
	$totnetapayer 	= 0;

	if (count($paies->rows) > 0) {
		for ($i=0; $i < count($paies->rows) ; $i++) {
			$var = !$var;
			$item = $paies->rows[$i];

	    	$userpay = new User($db);
	    	$userpay->fetch($item->fk_user);

			print '<tr '.$bc[$var].' >';
	    		print '<td align="left" style="">'; 
	    		print '<a href="'.dol_buildpath('/paiedolibarr/card.php?id='.$item->rowid,2).'" >';
	    		print trim($item->ref);
	    		print '</a>';
	    		print '</td>';
	    		print '<td align="left" style="">'.$userpay->getNomUrl(1).'</td>';
	    		print '<td align="left" style="">'.trim($item->label).'</td>';
	    		print '<td align="left" style="">';
	    		$periods = explode('-', $item->period);
		        $periodyear = $periods[0] + 0;
		        $periodmonth = $periods[1];
		        $period = $langs->trans("Month".sprintf("%02d", $periodmonth))." ".$periodyear;
		        print $period;
	    		print '</td>';
	    		print '<td align="right" style="">';
	    		print $paiedolibarr->number_format($item->salairebrut, 2, ',',' ');
	    		$totsalairebrut = $totsalairebrut + $item->salairebrut;
	    		print '</td>';
	    		// print '<td align="right" style="">';
	    		// print $paiedolibarr->number_format($item->salairenet, 2, ',',' ');
	    		// $totsalairenet = $totsalairenet + $item->salairenet;
	    		// print '</td>';
	    		print '<td align="right" style="">';
	    		print $paiedolibarr->number_format($item->netapayer, 2, ',',' ');
	    		$totnetapayer = $totnetapayer + $item->netapayer;
	    		print '</td>';
				print '<td align="center">';
				print '<a  href="./card.php?id='.$item->rowid.'&export=pdf" target="_blank" >'.img_mime('test.pdf',$langs->trans('paiePrintFile')).'</a>';
	    		print '</td>';
			print '</tr>';
		}

		print '<tr class="liste_total">';
		print '<td colspan="4">';
		print $langs->trans("Total");
		print '</td>';
		print '<td align="right">';
		print $paiedolibarr->number_format($totsalairebrut, 2, ',',' ');
		print '</td>';
		// print '<td align="right">';
		// print $paiedolibarr->number_format($totsalairenet, 2, ',',' ');
		// print '</td>';
		print '<td align="right">';
		print $paiedolibarr->number_format($totnetapayer, 2, ',',' ');
		print '</td>';
		print '<td align="left" style="padding-left:0;">';
		print $currency;
		print '</td>';
		print '</tr>';
	}else{
		print '<tr><td align="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
	}

print '</tbody></table></form>';


?>
<script>
	$( function() {
	$( ".datepicker55" ).datepicker({
    	dateFormat: 'dd/mm/yy'
	});
	$('#srch_fk_user').select2();
	$('#select_onechambre>select').select2();
	$('#paiedolibarrpage select#periodmonth').on('change', function(){
	    $(this).closest('form').submit();
	});
	} );
</script>

<?php

llxFooter();