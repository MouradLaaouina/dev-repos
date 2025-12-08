<?php 

if (!defined('NOCSRFCHECK'))     define('NOCSRFCHECK', 1);
if (!defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', 1);

$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");       // For root directory
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php"); // For "custom" 

// ini_set('display_startup_errors', 1);
// ini_set('display_errors', 1);
// error_reporting(-1);

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';

$usercanread           = $user->rights->paiedolibarr->lire;
$usercancreate         = $user->rights->paiedolibarr->creer;
$usercandelete         = $user->rights->paiedolibarr->supprimer;
$usercandcreatesalary  = $user->rights->paiedolibarr->creer_salaire;
// $usercandelete       = $user->admin;

if (empty($conf->paiedolibarr->enabled) || !$usercanread || $user->socid > 0) accessforbidden();


dol_include_once('/paiedolibarr/class/paiedolibarr.class.php');
$paiedolibarr = new paiedolibarr($db);
$res = $paiedolibarr->upgradeTheModule();

dol_include_once('/paiedolibarr/class/paiedolibarr_paies.class.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_paiesrules.class.php');
dol_include_once('/paiedolibarr/class/paiedolibarr_rules.class.php');



$langs->load('paiedolibarr@paiedolibarr');
$langs->loadLangs(array('bills'));

$title = html_entity_decode($langs->trans("paiedolibarr2"));

// Initial Objects
$object         = new paiedolibarr_paies($db);
$paierule       = new paiedolibarr_paiesrules($db);
$form        	= new Form($db);
$formother 		= new FormOther($db);
$userpay 		= new User($db);
$tmpuser        = new User($db);
$rules          = new paiedolibarr_rules($db);

$extrafields    = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

// Get parameters
$request_method = $_SERVER['REQUEST_METHOD'];
$action         = GETPOST('action', 'alpha');
$action_rule    = GETPOST('rule', 'alpha');
$page           = GETPOST('page');
$id             = (int) ( (!empty($_GET['id'])) ? $_GET['id'] : GETPOST('id') ) ;
if($id){
    $object->fetch($id);
    // if($object->entity != $conf->entity)
    //     $result = restrictedArea($user, 'paiedolibarr_paies', $id);
}
$error  = false;
if (!$user->rights->paiedolibarr->lire) {
    // accessforbidden();
}


if(in_array($action, ["create","edit","add","update"])) {
    if (!$user->rights->paiedolibarr->creer) {
      // accessforbidden();
    }
}
if($action == "delete") {
    if (!$user->rights->paiedolibarr->supprimer) {
      // accessforbidden();
    }
}

if ($action_rule == 'add' && $request_method === 'POST') {
    $newrules  = GETPOST('newrules','array');

    if(is_array($newrules) && count($newrules) > 0) {
        $tmprules = implode(',', $newrules);

        $payrules = $object->mergeNewAndOldPaieRules($id, $tmprules);

        $otherdata = $object->editcalculatePaieRules($payrules);
        
        // $data = $object->getNetBrutNetAPayer($id);
        // $object->fetch($id);
        // $isvalid = $object->update($id, $data);

        // d($tmprules);
    }
    $action_rule = '';
}

$datepay = dol_mktime(0,0,0, GETPOST('datepaymonth','int'), GETPOST('datepayday','int'), GETPOST('datepayyear','int'));

if($id>0 && $conf->global->PAIEDOLIBARR_ENABLE_ADDSALARY && ($user->rights->paiedolibarr->creer_salaire) && $action == "confirm_createsalary" && GETPOST('confirm') == 'yes' ){
    $salary = new Salary($db);
    
    $modpayment = GETPOST("modpayment");
    $accountid = GETPOST("accountid", 'int');
    $salary->accountid = $accountid > 0 ? $accountid : 0;
    $salary->fk_user = $object->fk_user;
    $salary->datev = dol_now();
    $salary->datep = dol_now();
    $salary->amount = $object->netapayer;
    $salary->label = $langs->trans("Salary");
    $salary->datesp = $db->jdate($object->period);
    $salary->dateep = $object->datepay;
    $salary->type_payment = (!empty($modpayment) ? $modpayment : 0);
    $salary->fk_user_author = $user->id;
    $salary->array_options['options_paiedolibarr_fk_paie'] = $object->id;
    $res = $salary->create($user);
    
    if($res>0){
        $paiement = new PaymentSalary($db);
        $paiement->chid         = $salary->id;
        $paiement->datepaye     = $salary->datep;
        $paiement->datev        = $salary->datev;
        $paiement->amounts      = array($salary->id => $object->netapayer); // Tableau de montant
        $paiement->paiementtype = (!empty($modpayment) ? $modpayment : 0);

        $paymentid = $paiement->create($user, (int) GETPOST('closepaidsalary'));
        if ($paymentid > 0) {
            $result = $paiement->addPaymentToBank($user, 'payment_salary', '(SalaryPayment)', $accountid, '', '');
            if (!($result > 0)) {
                setEventMessages($paiement->error, null, 'errors');
            }
        }

        $object->update($id, ['fk_salary' => $res]);

    }else{
        setEventMessages($salary->error, $salary->errors, 'errors');
    }

    header('Location: card.php?id='.$id);
    exit;
}
// ------------------------------------------------------------------------- Actions "Create/Update/Delete"
if ($action == 'add' && $request_method === 'POST') {
    require_once 'ucard/create.php';
}

if ($action == 'update' && $request_method === 'POST') {
    require_once 'ucard/edit.php';
}

// If delete of request
if ($action == 'confirm_delete' && GETPOST('confirm') == 'yes' ) {
    require_once 'ucard/show.php';
}

$export = GETPOST('export');

$paiedolibarrmodel   = isset($conf->global->PAIEDOLIBARR_PAIE_MODEL) ? $conf->global->PAIEDOLIBARR_PAIE_MODEL : 'maroc';

// if(!isset($paiedolibarr->paiedolibarrmodel[$paiedolibarrmodel])) 
//     $paiedolibarrmodel = 'maroc';


if (!empty($id) && $export == "pdf") {

    global $conf, $langs, $mysoc;

    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
    require_once dol_buildpath('/paiedolibarr/pdf/pdf.lib.php');

    $pdf->SetMargins(7, 2, 7, false);
    $pdf->SetFooterMargin(17);
    $pdf->setPrintFooter(true);
    $pdf->SetAutoPageBreak(TRUE,10);

    $height=$pdf->getPageHeight();

    // $pdf->SetFont('helvetica', '', 9, '', true);
    $pdf->SetFont('freeserif', '', 9, '', true);
    // $pdf->AddPage('L');
    // if($paiedolibarrmodel == 'globetudes'){
    //     $pdf->AddPage('L');
    // }else{
    //     $pdf->AddPage('P');
    // }
    $pdf->AddPage('P');

    $margint = $pdf->getMargins()['top'];
    $marginb = $pdf->getMargins()['bottom'];
    $marginl = $pdf->getMargins()['left'];
    $object->fetch($id);
    $item = $object;

    $pdf->SetTextColor(0, 0, 60);

    $default_font_size = 10;
    $pdf->SetFont('', '', $default_font_size);
    $posy   = $margint;
    $posx   = $pdf->page_largeur-$pdf->getMargins()['right']-100;

    $pdf->SetXY($marginl, $posy);

    // Logo or company name
    $logo = '';
    if (!empty($mysoc->logo)) {
        $logodir = $conf->mycompany->dir_output;
        if (empty($conf->global->MAIN_PDF_USE_LARGE_LOGO)) {
            $logo = $logodir.'/logos/thumbs/'.$mysoc->logo_small;
        } else {
            $logo = $logodir.'/logos/'.$mysoc->logo;
        }
    }

    $maxLogoWidth = 45; // slightly larger but capped
    if ($logo && is_readable($logo)) {
        $logoHeight = 0;
        $imgInfo = @getimagesize($logo);
        if ($imgInfo && !empty($imgInfo[0]) && !empty($imgInfo[1])) {
            $ratio = $imgInfo[1] / $imgInfo[0];
            $logoHeight = round($maxLogoWidth * $ratio, 2);
        }
        // Draw logo without shifting main content down
        $pdf->Image($logo, $marginl, $posy, $maxLogoWidth, 0);
        // Keep posy at top so the rest of the layout starts at the same height
        $posy = $margint;
    } else {
        $pdf->SetTextColor(0, 0, 60);
        $pdf->SetFont('', 'B', $default_font_size);
        $pdf->MultiCell(100, 4, $langs->convToOutputCharset($mysoc->name), 0, 'L');
        $posy += 8;
    }

    // if($paiedolibarrel != 'france'){
    //     $pdf->SetXY($marginl, $posy);

    //     $heightimg = 15;
    if($paiedolibarrmodel == 'globetudes'){
        $pdf->SetXY($marginl, $posy);

        $pdf->SetTextColor(0, 0, 60);
        // $currentwate    = $conf->global->PAIEDOLIBARR_WATERMARK_IMG;
        // if($currentwate){
        //     $bMargin = $pdf->getBreakMargin();
        //     $auto_page_break = $pdf->getAutoPageBreak();
        //     $pdf->SetAutoPageBreak(false, 0);
        //     $img_file = $conf->mycompany->dir_output.'/watermark/'.$currentwate;
        //     $pdf->SetAlpha(0.1);
        //     $pdf->Image($img_file, 35, 100, 140, '', '', '', '', false, 300, '', false, false, 0);
        //     $pdf->SetAlpha(1);
        //     $pdf->SetAutoPageBreak(true, $bMargin);
        //     $pdf->setPageMark();
        // }
    }

    $pdf->SetFont('', '', $default_font_size + 1);

    $pdf->SetXY($posx, $posy);

    require_once dol_buildpath('/paiedolibarr/tpl/paie/paie-'.$paiedolibarrmodel.'.php');
 
    $pdf->writeHTML($html, true, false, true, false, '');
    ob_start();
    $pdf->Output($object->ref.'.pdf', 'I');
    // ob_end_clean();
    die();

}


/* ------------------------ View ------------------------------ */

$morejs  = array();

$morejs  = array();
llxHeader(array(), $title,'','','','',$morejs,0,0);

$head = '';

// $newcardbutton .= '<a href="index.php">'.$langs->trans('BackToList').'</a>';
$newcardbutton = '';
// print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param = '', $sortfield, $sortorder, "", $num, $nbrtotal, 'user', 0, $newcardbutton, '', $limit, 0, 0, 1);
print_fiche_titre($title, $htmlright = '', 'user');

// ------------------------------------------------------------------------- Views
print '<div class="paiepaiediv">';
if($action == "create")
    require_once 'ucard/create.php';

if($action == "edit")
    require_once 'ucard/edit.php';

if( $id && (empty($action) || $action == "delete" || $action == "createsalary")){
    require_once 'ucard/show.php';
}
print '</div>';

?>
<script type="text/javascript">
    $(document).ready(function () {
        $(".confirmvalidatebutton").on("click", function() {
            console.log("We click on button");
            $(this).attr("disabled", "disabled");
            setTimeout('$(".confirmvalidatebutton").removeAttr("disabled")', 3000);
            //console.log($(this).closest("form"));
            $(this).closest("form").submit();
        });
        $("td.selectWarehouses select").select2();
        $('.paie_select_rules').select2();

        <?php if($action_rule)  { ?>
            $('html, body').animate({
                scrollTop: ($("#newrule_formconfirm").offset().top - 80)
            }, 800);
        <?php } ?>

    });

    function remove_tr_datesconges(x){
        var y = $(x).parent('td').parent('tr');
        y.remove();
    }
    function remove_tr_paie(x){
        var $row = $(x).closest('tr');
        var ruleId = $row.data('ruleid');
        var $form = $row.closest('form');

        if ($form.length && ruleId && !isNaN(ruleId)) {
            var selector = 'input[name="deleted_rules[]"][value="' + ruleId + '"]';
            if ($form.find(selector).length === 0) {
                $('<input>', {type: 'hidden', name: 'deleted_rules[]', value: ruleId}).appendTo($form);
            }
        }

        $row.remove();
    }
</script>
<?php
llxFooter();

if (is_object($db)) $db->close();
?>
