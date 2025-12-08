<?php

if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);
if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);

$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");       // For root directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php"); // For "custom" 
global $conf;
if (!$conf->paiedolibarr->enabled) {
	accessforbidden();
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
$paiedolibarr = new paiedolibarr($db);
$res = $paiedolibarr->upgradeTheModule();

dol_include_once('/paiedolibarr/class/paiedolibarr_rules.class.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_paies.class.php');
dol_include_once('/core/class/html.form.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('paiedolibarr@paiedolibarr');
$langs->load('salaries');

$modname = $langs->trans("paierules");

$rules  	= new paiedolibarr_rules($db);
$rules2  	= new paiedolibarr_rules($db);
$paies   = new paiedolibarr_paies($db);

$form        	= new Form($db);
$formother 		= new FormOther($db);

$var 				= true;
$sortfield 			= ($_GET['sortfield']) ? $_GET['sortfield'] : "category,rowid";
$sortorder 			= ($_GET['sortorder']) ? $_GET['sortorder'] : "ASC";
$id 				= $_GET['id'];
$action   			= $_GET['action'];


if (!$user->rights->paiedolibarr->lire) {
	accessforbidden();
}

$srch_numero 		= GETPOST('srch_numero');
$srch_code 			= GETPOST('srch_code');
$srch_taux 			= GETPOST('srch_taux');
$srch_amount 		= GETPOST('srch_amount');
$srch_label 		= GETPOST('srch_label');
$srch_fk_user 		= GETPOST('srch_fk_user');
$srch_category 		= GETPOST('srch_category');

$periodyear 	= GETPOST('periodyear','int');
$periodmonth 	= GETPOST('periodmonth','int');

if (!$periodyear){
	$periodyear = date('Y');
}
if (!$periodmonth){
	$periodmonth = date('m') + 0;
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter") || $page < 0) {
	$filter = "";
	$offset = 0;
	$filter = "";
	$srch_numero = "";
	$srch_code = "";
	$srch_taux = "";
	$srch_amount = "";
	$srch_label = "";
	$srch_category = "";
	$periodyear = date('Y');
	$periodmonth = date('m') + 0;
}

$formule = GETPOST('formule', 'alpha');
if($formule == 'update'){
	if(!dolibarr_set_const($db, "PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT", trim(GETPOST('formsalairebrut')),'chaine',0,'',$conf->entity))
		$error++;
	if(!dolibarr_set_const($db, "PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO", trim(GETPOST('formsalairebimpo')),'chaine',0,'',$conf->entity))
		$error++;
	if(!dolibarr_set_const($db, "PAIEDOLIBARR_FORM_CALCUL_SALAIRENET", trim(GETPOST('formsalairenet')),'chaine',0,'',$conf->entity))
		$error++;
	if(!dolibarr_set_const($db, "PAIEDOLIBARR_FORM_CALCUL_RETENUS", trim(GETPOST('formretenus')),'chaine',0,'',$conf->entity))
		$error++;
	if(!dolibarr_set_const($db, "PAIEDOLIBARR_FORM_CALCUL_NETAPAYER", trim(GETPOST('formnetapayer')),'chaine',0,'',$conf->entity))
		$error++;
}

// $date = explode('/', $srch_date);
// $date = $date[2]."-".$date[1]."-".$date[0];
$filter .= (!empty($srch_code)) ? " AND code like '%".$srch_code."%'" : "";
$filter .= (!empty($srch_numero)) ? " AND numero like '%".$srch_numero."%'" : "";
$filter .= (!empty($srch_taux)) ? " AND taux = '".$srch_taux."'" : "";
$filter .= (!empty($srch_amount)) ? " AND amount = '".$srch_amount."'" : "";
$filter .= (!empty($srch_label)) ? " AND label like '%".$srch_label."%'" : "";
$filter .= (!empty($srch_category)) ? " AND category  = '".$srch_category."'" : "";
// $filter .= (!empty($srch_date)) ? " AND CAST(date as date) = '".$date."' " : "";

// $filter .= ' AND entity='.$conf->entity;
// echo $filter;

$limit 	= $conf->liste_limit+1;
$page 	= GETPOST("page",'int');
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;



$nbrtotal = $rules->fetchAll($sortorder, $sortfield, $limit+1, $offset, $filter);
$nbrtotalnofiltr = $rules2->fetchAll("", "", "", "", $filter);
$morejs  = array();
llxHeader(array(), $modname,'','','','',$morejs,0,0);

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('NewPaieRule'), '', 'fa fa-plus-circle', 'card.php?action=add');
print_barre_liste($modname, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $nbrtotal, $nbrtotalnofiltr, 'user', 0, $newcardbutton, '', $limit, 1, 0, 1);

print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';


print '<table id="table-1" class="noborder" style="width: 100%;" >';
print '<thead>';

print '<tr class="liste_titre">';

	print_liste_field_titre($langs->trans('Numero'),$_SERVER["PHP_SELF"], "numero", '', '', '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre($langs->trans('paieDesignation'),$_SERVER["PHP_SELF"], "label", '', '', '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre($langs->trans('paieCode'),$_SERVER["PHP_SELF"], "code", '', '', '', $sortfield, $sortorder, 'left ');
	// print_liste_field_titre($langs->trans('paieTaux'),$_SERVER["PHP_SELF"], "taux", '', '', 'align="center"', $sortfield, $sortorder, 'left ');
	// print_liste_field_titre($langs->trans('paieMontant_de_base'),$_SERVER["PHP_SELF"], "amount", '', '', 'align="center"', $sortfield, $sortorder, 'left ');
	// print_liste_field_titre($langs->trans('paiesouselementde'),$_SERVER["PHP_SELF"], "ruletitle", '', '', 'align="center"', $sortfield, $sortorder, 'left ');
	print_liste_field_titre($langs->trans('paieCategorie'),$_SERVER["PHP_SELF"], "category", '', '', '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre($langs->trans('paieCalcule_automatiquement'),$_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center ');

print '<th align="center"></th>';


print '</tr>';

print '<tr class="liste_titre nc_filtrage_tr">';

print '<td class="left width75"><input style="" class="width75" type="text" class="" id="srch_numero" name="srch_numero" value="'.$srch_numero.'"/></td>';
print '<td class="left"><input style="" class="" type="text" class="" id="srch_label" name="srch_label" value="'.$srch_label.'"/></td>';
print '<td class="left"><input style="" class="" type="text" class="" id="srch_code" name="srch_code" value="'.$srch_code.'"/></td>';
// print '<td align="center"><input class="" type="text" class="" id="srch_taux" name="srch_taux" value="'.$srch_taux.'"/></td>';
// print '<td align="center"><input class="" type="text" class="" id="srch_amount" name="srch_amount" value="'.$srch_amount.'"/></td>';

// print '<td align="center">';
// print $rules2->selectSousElementRule($srch_ruletitle,'srch_ruletitle',1);
// // print_r($rulescateg);
// print '</td>';
print '<td class="left">';
print $paies->selectCategories($srch_category,'srch_category',1);
// print_r($rulescateg);
print '</td>';
print '<td class="center"></td>';

print '<td class="center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
print '</td>';
print '</tr>';


print '</thead><tbody>';
	$colspn = 5;

	if (count($rules->rows) > 0) {
	for ($i=0; $i < count($rules->rows) ; $i++) {
		$var = !$var;
		$item = $rules->rows[$i];

		// echo "UPDATE `llx_paiedolibarr_paiesrules` SET `formule_base` = '".addslashes($item->formule_base)."' WHERE `code` = '".$item->code."';<br>";
		
		print '<tr '.$bc[$var].' >';

			print '<td class="left" style="">'; 
    		print '<a href="'.dol_buildpath('/paiedolibarr/rules/card.php?id='.$item->rowid,1).'" >';
    		print $item->numero ? $item->numero : $item->rowid;
    		print '</a>';
    		print '</td>';

			print '<td class="left" style="">'; 
    		print '<a href="'.dol_buildpath('/paiedolibarr/rules/card.php?id='.$item->rowid,1).'" >';
    		print trim($item->label);
    		print '</a>';
    		print '</td>';

    		print '<td class="left" style="">'; 
    		// print '<a href="'.dol_buildpath('/paiedolibarr/rules/card.php?id='.$item->rowid,1).'" >';
    		print trim($item->code);
    		// print '</a>';
    		print '</td>';
    		// print '<td align="center" style="">';
    		// print $paiedolibarr->number_format($item->taux,2,',',' ').' %';
    		// print '</td>';
    		// print '<td align="center" style="">';
    		// // print $paiedolibarr->number_format($item->amount,2,',',' ');
    		// if($item->category == 'BASIQUE')
    		// 	print $langs->trans('Salary');
    		// else if($item->amounttype != 'FIX'){
			//     print '<span class="amounttype">';
			//     print $rules->amounttypes[$item->amounttype];
			//     print '</span>';
			// }else
			//     print $paiedolibarr->number_format($item->amount,2,',',' ');
			// print '</td>';
			// print '<td align="center" style="">';
		
			// print '</td>';
    		print '<td class="left" style="">';
    		print $paies->rulescategory[$item->category];
    		print '</td>';

    		print '<td class="center" style="">';
    			// if($item->amounttype == 'CALCULATED' && $item->code != 'SALARY'){
    			if($item->amounttype == 'CALCULATED'){
    				print $langs->trans('Yes');
    			} else {
    				print '-';
    			}
    		print '</td>';

    		print '<td class="center" style="">';
    			// if($item->code != 'SALARY'){
	    			print '<a href="'.dol_buildpath('/paiedolibarr/rules/card.php?id='.$item->rowid, 1).'&action=edit" >';
					print img_edit($langs->trans("Edit").' - '.$langs->trans("View"), 0, 'class="valignmiddle opacitymedium"');
		    		print '</a>';
    			// }
    		print '</td>';
		print '</tr>';
	}
	}else{
		print '<tr><td class="center" colspan="'.$colspn.'">'.$langs->trans("NoResults").'</td></tr>';
	}

print '</tbody></table></form>';












print '<br><br>';

print load_fiche_titre($langs->trans("paieSBNetAPayer"), '');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" class="competcpaiedolibarr card_paiedolibarr paiepaierulesdiv">';

print '<input type="hidden" name="formule" value="update" />';
print '<input type="hidden" name="id" value="'.$id.'" />';
print '<input type="hidden" name="page" value="'.$page.'" />';
print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';


print '<table class="border dstable_" width="100%">';
print '<tr>';
    print '<th class="titlefieldcreate left">'.$langs->trans('paieDesignation').'</th>';
    print '<th class="left" >'.$langs->trans('paieForm').'</th>';
    print '</th>';
print '</tr>';

// preg_replace("/[^0-9.\/+*()-- ]/", '0', $str);
$formsalairebrut 	= $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBRUT;
$formsalairebimpo 	= $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIREBIMPO;
$formsalairenet 	= $conf->global->PAIEDOLIBARR_FORM_CALCUL_SALAIRENET;
$formnetapayer 		= $conf->global->PAIEDOLIBARR_FORM_CALCUL_NETAPAYER;
$formretenus 	    = $conf->global->PAIEDOLIBARR_FORM_CALCUL_RETENUS;

print '<tr>';
    print '<td class="titlefieldcreate">'.$langs->trans('paieSalaireBrut').' = <b>S_B_G</b></td>';
    print '<td><input type="text" class="width100percent" id="formsalairebrut" name="formsalairebrut"  value="'.trim($formsalairebrut).'"/>';
    print '</td>';
print '</tr>';
print '<tr>';
    print '<td class="titlefieldcreate">'.$langs->trans('SalaireBrutImposable').' = <b>S_B_I</b></td>';
    print '<td><input type="text" class="width100percent" id="formsalairebimpo" name="formsalairebimpo"  value="'.trim($formsalairebimpo).'"/>';
    print '</td>';
print '</tr>';
print '<tr>';
    print '<td class="titlefieldcreate">'.$langs->trans('paieNet_imposable').' = <b>S_N_I</b></td>';
    print '<td><input type="text" class="width100percent" id="formsalairenet" name="formsalairenet"  value="'.trim($formsalairenet).'"/>';
    print '</td>';
print '</tr>';
// print '<tr>';
//     print '<td class="titlefieldcreate">'.$langs->trans('retenus').' = <b>RETENUS</b></td>';
//     print '<td><input type="text" class="width100percent" id="formretenus" name="formretenus"  value="'.trim($formretenus).'"/>';
//     print '</td>';
// print '</tr>';
print '<tr>';
    print '<td class="titlefieldcreate">'.$langs->trans('paieNet_a_payer').'</td>';
    print '<td><input type="text" class="width100percent" id="formnetapayer" name="formnetapayer"  value="'.trim($formnetapayer).'"/>';
    print '</td>';
print '</tr>';
print '</table>';

print '<br>';
// Actions
print '<table class="" width="100%">';
print '<tr>';
    print '<td colspan="2" align="center">';
    print '<input type="submit" value="'.$langs->trans('Validate').'" name="bouton" class="button" />';
    print '<input type="button" class="button" value="'.$langs->trans('Cancel').'" onclick="javascript:history.go(-1)">';
    print '</td>';
print '</tr>';
print '</table>';
print '<br>';




print '</form>';

?>
<script>
	$( function() {
	$('.select_srch_category').select2();
	} );
</script>

<?php

llxFooter();