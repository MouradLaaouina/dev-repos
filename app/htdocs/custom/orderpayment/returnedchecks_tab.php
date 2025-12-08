<?php
/**
 * Tab to show returned checks on thirdparty card
 */

// Load Dolibarr environment
$res = 0;
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--;
    $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
dol_include_once('/orderpayment/class/orderpayment.class.php');

// Load translation files
$langs->loadLangs(array('bills', 'companies', 'orderpayment@orderpayment'));

// Get parameters
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->hasRight('orderpayment', 'read')) {
    accessforbidden();
}
if (!$user->hasRight('societe', 'lire')) {
    accessforbidden();
}

// Initialize objects
$form = new Form($db);
$object = new Societe($db);
$orderpayment = new OrderPayment($db);

// Load thirdparty
if ($socid > 0) {
    $result = $object->fetch($socid);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
}

// Get returned checks for this thirdparty
$returnedChecks = $orderpayment->getReturnedChecks($socid);
$returnedCount = $orderpayment->getReturnedChecksCount($socid);
$returnedTotal = $orderpayment->getReturnedChecksTotal($socid);

/*
 * View
 */

$title = $langs->trans('ThirdParty').' - '.$langs->trans('ReturnedChecks');
$help_url = '';

llxHeader('', $title, $help_url);

if ($object->id > 0) {
    $head = societe_prepare_head($object);
    print dol_get_fiche_head($head, 'tabreturnedchecks', $langs->trans("ThirdParty"), -1, 'company');

    // Thirdparty card
    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    // Summary table
    print '<table class="border tableforfield centpercent">';

    // Number of returned checks
    print '<tr><td class="titlefield">'.$langs->trans("ReturnedChecksCount").'</td>';
    print '<td>';
    if ($returnedCount > 0) {
        print '<span class="badge badge-status8">'.$returnedCount.'</span>';
    } else {
        print '<span class="opacitymedium">0</span>';
    }
    print '</td></tr>';

    // Total amount
    print '<tr><td>'.$langs->trans("ReturnedChecksTotal").'</td>';
    print '<td>';
    if ($returnedTotal > 0) {
        print '<span class="amount" style="color: #c00;">'.price($returnedTotal, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
    } else {
        print '<span class="opacitymedium">'.price(0, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
    }
    print '</td></tr>';

    print '</table>';
    print '</div>';

    print dol_get_fiche_end();

    // List of returned checks
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Ref").'</th>';
    print '<th>'.$langs->trans("DatePayment").'</th>';
    print '<th>'.$langs->trans("DateCancellation").'</th>';
    print '<th>'.$langs->trans("PaymentMode").'</th>';
    print '<th>'.$langs->trans("Numero").'</th>';
    print '<th class="right">'.$langs->trans("Amount").'</th>';
    print '<th>'.$langs->trans("CancelReason").'</th>';
    print '</tr>';

    if (empty($returnedChecks)) {
        print '<tr class="oddeven"><td colspan="7" class="opacitymedium">'.$langs->trans("NoReturnedChecks").'</td></tr>';
    } else {
        foreach ($returnedChecks as $check) {
            $paymentobj = new OrderPayment($db);
            $paymentobj->id = $check->rowid;
            $paymentobj->ref = $check->ref;
            $paymentobj->status = OrderPayment::STATUS_CANCELLED;

            print '<tr class="oddeven">';

            // Ref
            print '<td>'.$paymentobj->getNomUrl(1).'</td>';

            // Payment date
            print '<td>'.dol_print_date($db->jdate($check->datep), 'day').'</td>';

            // Cancel date
            print '<td>'.dol_print_date($db->jdate($check->date_cancel), 'day').'</td>';

            // Payment mode
            $paymentmode = '';
            // Get from payment object if we need it later
            print '<td>'.($check->num_payment ? 'Chèque' : '-').'</td>';

            // Num payment
            print '<td>'.dol_escape_htmltag($check->num_payment).'</td>';

            // Amount
            print '<td class="right" style="color: #c00;">'.price($check->amount, 0, $langs, 1, -1, -1, $conf->currency).'</td>';

            // Cancel reason
            print '<td>'.dol_escape_htmltag($check->cancel_reason).'</td>';

            print '</tr>';
        }
    }

    print '</table>';
    print '</div>';
}

llxFooter();
$db->close();
