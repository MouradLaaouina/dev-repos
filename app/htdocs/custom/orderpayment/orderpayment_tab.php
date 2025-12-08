<?php
/**
 * Tab to show order payments on order card
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
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
dol_include_once('/orderpayment/class/orderpayment.class.php');

// Load translation files
$langs->loadLangs(array('orders', 'bills', 'companies', 'orderpayment@orderpayment'));

// Get parameters
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->hasRight('orderpayment', 'read')) {
    accessforbidden();
}

// Initialize objects
$form = new Form($db);
$object = new Commande($db);
$orderpayment = new OrderPayment($db);

// Load order
if ($id > 0 || !empty($ref)) {
    $result = $object->fetch($id, $ref);
    if ($result <= 0) {
        dol_print_error($db, $object->error);
        exit;
    }
    $id = $object->id;
}

// Get payments for this order
$payments = $orderpayment->getPaymentsForOrder($id);
$totalPaid = $orderpayment->getSumPaymentsForOrder($id);
$remainsToPay = $object->total_ttc - $totalPaid;

/*
 * View
 */

$title = $langs->trans('Order').' - '.$langs->trans('TabOrderPayments');
$help_url = '';

llxHeader('', $title, $help_url);

if ($object->id > 0) {
    $head = commande_prepare_head($object);
    print dol_get_fiche_head($head, 'taborderpayments', $langs->trans("CustomerOrder"), -1, 'order');

    // Order card
    $linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

    $morehtmlref = '<div class="refidno">';
    // Ref customer
    $morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
    $morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
    // Thirdparty
    $morehtmlref .= '<br>';
    $morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
    $morehtmlref .= '</div>';

    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    // Summary table
    print '<table class="border tableforfield centpercent">';

    // Total order amount
    print '<tr><td class="titlefield">'.$langs->trans("TotalOrderAmount").'</td>';
    print '<td class="nowrap">'.price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';

    // Already paid
    print '<tr><td>'.$langs->trans("AlreadyPaidOnOrder").'</td>';
    print '<td class="nowrap">'.price($totalPaid, 0, $langs, 1, -1, -1, $conf->currency).'</td></tr>';

    // Remains to pay
    print '<tr><td>'.$langs->trans("RemainsToPay").'</td>';
    if ($remainsToPay > 0) {
        print '<td class="nowrap amountremaintopay">'.price($remainsToPay, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
    } elseif ($remainsToPay == 0) {
        print '<td class="nowrap amountpaymentcomplete">'.price($remainsToPay, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
    } else {
        print '<td class="nowrap amountpaymentover">'.price($remainsToPay, 0, $langs, 1, -1, -1, $conf->currency).'</td>';
    }
    print '</tr>';

    print '</table>';
    print '</div>';

    print dol_get_fiche_end();

    // Buttons
    print '<div class="tabsAction">';
    if ($user->hasRight('orderpayment', 'write') && $remainsToPay > 0) {
        $url = dol_buildpath('/orderpayment/card.php', 1).'?action=create&ordid='.$object->id;
        print '<a class="butAction" href="'.$url.'">'.$langs->trans("RegisterOrderPayment").'</a>';
    }
    print '</div>';

    // List of payments
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';

    print '<tr class="liste_titre">';
    print '<th>'.$langs->trans("Ref").'</th>';
    print '<th>'.$langs->trans("Date").'</th>';
    print '<th>'.$langs->trans("PaymentMode").'</th>';
    print '<th>'.$langs->trans("Numero").'</th>';
    print '<th class="right">'.$langs->trans("Amount").'</th>';
    print '<th class="center">'.$langs->trans("Status").'</th>';
    print '</tr>';

    if (empty($payments)) {
        print '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("NoOrderPaymentYet").'</td></tr>';
    } else {
        foreach ($payments as $payment) {
            $paymentobj = new OrderPayment($db);
            $paymentobj->id = $payment->rowid;
            $paymentobj->ref = $payment->ref;
            $paymentobj->status = $payment->status;

            print '<tr class="oddeven">';

            // Ref
            print '<td>'.$paymentobj->getNomUrl(1).'</td>';

            // Date
            print '<td>'.dol_print_date($db->jdate($payment->datep), 'day').'</td>';

            // Payment mode
            $paymentmode = '';
            if ($payment->fk_mode_reglement > 0) {
                $sql = "SELECT code, libelle FROM ".$db->prefix()."c_paiement WHERE id = ".((int) $payment->fk_mode_reglement);
                $resmode = $db->query($sql);
                if ($resmode && $db->num_rows($resmode)) {
                    $objmode = $db->fetch_object($resmode);
                    $paymentmode = $langs->trans("PaymentType".$objmode->code) != "PaymentType".$objmode->code ? $langs->trans("PaymentType".$objmode->code) : $objmode->libelle;
                }
            }
            print '<td>'.dol_escape_htmltag($paymentmode).'</td>';

            // Num payment
            print '<td>'.dol_escape_htmltag($payment->num_payment).'</td>';

            // Amount
            print '<td class="right">'.price($payment->line_amount, 0, $langs, 1, -1, -1, $conf->currency).'</td>';

            // Status
            print '<td class="center">'.$paymentobj->getLibStatut(5).'</td>';

            print '</tr>';
        }
    }

    print '</table>';
    print '</div>';
}

llxFooter();
$db->close();
