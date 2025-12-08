<?php
/**
 * Customer Financial Status Page
 * Shows complete financial overview: balance, payments, credit notes, returns, outstanding
 */

// Load Dolibarr environment
$res = 0;
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
dol_include_once('/customerbalance/class/customerbalance.class.php');

// Initialize module conf
if (!isset($conf->customerbalance) || !is_object($conf->customerbalance)) {
    $conf->customerbalance = new stdClass();
}
$conf->customerbalance->enabled = 1;
$conf->customerbalance->dir_output = DOL_DATA_ROOT.'/customerbalance';

// Load translations
$langs->loadLangs(array('customerbalance@customerbalance', 'companies', 'bills', 'banks', 'other'));

// Get parameters
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$date_start = GETPOST('date_start', 'alpha');
$date_end = GETPOST('date_end', 'alpha');

// Security check
if (!$user->hasRight('customerbalance', 'read')) {
    accessforbidden();
}

// Load customer
$object = new Societe($db);
if ($socid > 0) {
    $object->fetch($socid);
}

if (empty($object->id)) {
    accessforbidden('Customer not found');
}

// Load financial data
$customerBalance = new CustomerBalance($db);
$customerBalance->fetch($socid, $date_start, $date_end);
$summary = $customerBalance->getSummary();

/*
 * Actions
 */

// Generate PDF
if ($action == 'builddoc' && $user->hasRight('customerbalance', 'export')) {
    $outputlangs = $langs;

    dol_include_once('/customerbalance/core/modules/customerbalance/doc/pdf_standard_customerbalance.modules.php');

    $pdfGenerator = new pdf_standard_customerbalance($db);
    $result = $pdfGenerator->write_file($customerBalance, $outputlangs);

    if ($result > 0) {
        setEventMessages($langs->trans('PDFGenerated'), null, 'mesgs');
    } else {
        setEventMessages($pdfGenerator->error, $pdfGenerator->errors, 'errors');
    }

    header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
    exit;
}

// Delete PDF file
if ($action == 'remove_file') {
    $file = GETPOST('file', 'alpha');
    $upload_dir = $conf->customerbalance->dir_output;
    $fullpath = $upload_dir.'/'.$file;

    if (file_exists($fullpath)) {
        dol_delete_file($fullpath);
        setEventMessages($langs->trans("FileWasRemoved", basename($file)), null, 'mesgs');
    }

    header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid);
    exit;
}

/*
 * View
 */

$title = $langs->trans('FinancialStatus').' - '.$object->name;
llxHeader('', $title);

// Prepare tabs
$head = societe_prepare_head($object);

print dol_get_fiche_head($head, 'financialstatus', $langs->trans("ThirdParty"), -1, 'company');

// Customer card header
$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

// Date filter form
print '<div class="div-table-responsive-no-min">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="socid" value="'.$socid.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("DateFilter").'</td>';
print '</tr>';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DateStart").'</td>';
print '<td><input type="date" name="date_start" value="'.dol_escape_htmltag($date_start).'"></td>';
print '<td>'.$langs->trans("DateEnd").'</td>';
print '<td><input type="date" name="date_end" value="'.dol_escape_htmltag($date_end).'">';
print ' <input type="submit" class="button small" value="'.$langs->trans("Filter").'">';
print ' <a class="button small" href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'">'.$langs->trans("Reset").'</a>';
print '</td>';
print '</tr>';
print '</table>';
print '</form>';
print '</div>';

print '<br>';

/*
 * Summary Box
 */
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("FinancialSummary").'</td>';
print '</tr>';

// Total invoiced
print '<tr class="oddeven">';
print '<td>'.$langs->trans("TotalInvoiced").' ('.$summary['nb_invoices'].' '.$langs->trans("Invoices").')</td>';
print '<td class="right amount">'.price($summary['total_invoiced'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
print '</tr>';

// Total paid
print '<tr class="oddeven">';
print '<td>'.$langs->trans("TotalPaid").' ('.$summary['nb_payments'].' '.$langs->trans("Payments").')</td>';
print '<td class="right amount" style="color: green;">'.price($summary['total_paid'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
print '</tr>';

// Total credit notes
print '<tr class="oddeven">';
print '<td>'.$langs->trans("TotalCreditNotes").' ('.$summary['nb_creditnotes'].' '.$langs->trans("CreditNotes").')</td>';
print '<td class="right amount" style="color: orange;">'.price($summary['total_creditnotes'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
print '</tr>';

// Total returns (if module enabled)
if (isModEnabled('customerreturn')) {
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("TotalReturns").' ('.$summary['nb_returns'].' '.$langs->trans("Returns").')</td>';
    print '<td class="right amount" style="color: orange;">'.price($summary['total_returns'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
    print '</tr>';
}

// Balance
print '<tr class="liste_titre">';
print '<td><strong>'.$langs->trans("Balance").' ('.$langs->trans("RemainToPay").')</strong></td>';
$balanceColor = $summary['balance'] > 0 ? 'red' : 'green';
print '<td class="right amount" style="color: '.$balanceColor.'; font-weight: bold; font-size: 1.2em;">'.price($summary['balance'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
print '</tr>';

print '</table>';
print '</div>';

/*
 * Outstanding (Encours)
 */
print '<br>';
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("OutstandingBill").'</td>';
print '</tr>';

// Outstanding limit
print '<tr class="oddeven">';
print '<td>'.$langs->trans("OutstandingLimit").'</td>';
if ($summary['outstanding'] > 0) {
    print '<td class="right amount">'.price($summary['outstanding'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
} else {
    print '<td class="right opacitymedium">'.$langs->trans("NoLimit").'</td>';
}
print '</tr>';

// Outstanding used
print '<tr class="oddeven">';
print '<td>'.$langs->trans("OutstandingUsed").'</td>';
print '<td class="right amount">'.price($summary['outstanding_used'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
print '</tr>';

// Outstanding available
if ($summary['outstanding'] > 0) {
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("OutstandingAvailable").'</td>';
    $availableColor = $summary['outstanding_available'] >= 0 ? 'green' : 'red';
    print '<td class="right amount" style="color: '.$availableColor.';">'.price($summary['outstanding_available'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
    print '</tr>';

    // Progress bar
    $percent = $summary['outstanding'] > 0 ? min(100, ($summary['outstanding_used'] / $summary['outstanding']) * 100) : 0;
    $progressColor = $percent < 70 ? 'green' : ($percent < 90 ? 'orange' : 'red');
    print '<tr class="oddeven">';
    print '<td>'.$langs->trans("Usage").'</td>';
    print '<td class="right">';
    print '<div style="background: #eee; border-radius: 4px; height: 20px; width: 200px; display: inline-block;">';
    print '<div style="background: '.$progressColor.'; border-radius: 4px; height: 20px; width: '.round($percent).'%;"></div>';
    print '</div>';
    print ' <span style="color: '.$progressColor.';">'.round($percent, 1).'%</span>';
    print '</td>';
    print '</tr>';
}

print '</table>';
print '</div>';

/*
 * Unpaid Invoices
 */
$unpaidInvoices = $customerBalance->getUnpaidInvoices();
if (!empty($unpaidInvoices)) {
    print '<br>';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("UnpaidInvoices").' ('.count($unpaidInvoices).')</td>';
    print '<td class="center">'.$langs->trans("Date").'</td>';
    print '<td class="center">'.$langs->trans("DateDue").'</td>';
    print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
    print '<td class="right">'.$langs->trans("AlreadyPaid").'</td>';
    print '<td class="right">'.$langs->trans("RemainderToPay").'</td>';
    print '</tr>';

    $now = dol_now();
    foreach ($unpaidInvoices as $invoice) {
        $isOverdue = ($invoice['date_due'] && $invoice['date_due'] < $now);

        print '<tr class="oddeven">';
        print '<td>';
        print '<a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$invoice['id'].'">'.$invoice['ref'].'</a>';
        if ($isOverdue) {
            print ' <span class="badge badge-danger">'.$langs->trans("Late").'</span>';
        }
        print '</td>';
        print '<td class="center">'.dol_print_date($invoice['date'], 'day').'</td>';
        print '<td class="center">';
        if ($invoice['date_due']) {
            print dol_print_date($invoice['date_due'], 'day');
        }
        print '</td>';
        print '<td class="right amount">'.price($invoice['total_ttc'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '<td class="right amount" style="color: green;">'.price($invoice['paid'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '<td class="right amount" style="color: '.($isOverdue ? 'red' : 'inherit').';">'.price($invoice['remain'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

/*
 * Recent Payments
 */
if (!empty($customerBalance->payments)) {
    print '<br>';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("RecentPayments").' ('.count($customerBalance->payments).')</td>';
    print '<td class="center">'.$langs->trans("Date").'</td>';
    print '<td>'.$langs->trans("PaymentMode").'</td>';
    print '<td>'.$langs->trans("Numero").'</td>';
    print '<td class="right">'.$langs->trans("Amount").'</td>';
    print '</tr>';

    $maxPayments = 10;
    $i = 0;
    foreach ($customerBalance->payments as $payment) {
        if ($i >= $maxPayments) {
            break;
        }

        print '<tr class="oddeven">';
        print '<td>'.$payment['ref'].'</td>';
        print '<td class="center">'.dol_print_date($payment['date'], 'day').'</td>';
        print '<td>'.$payment['payment_label'].'</td>';
        print '<td>'.$payment['num_paiement'].'</td>';
        print '<td class="right amount" style="color: green;">'.price($payment['amount'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '</tr>';
        $i++;
    }

    if (count($customerBalance->payments) > $maxPayments) {
        print '<tr class="oddeven">';
        print '<td colspan="5" class="center opacitymedium">... '.(count($customerBalance->payments) - $maxPayments).' '.$langs->trans("MorePayments").'</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

/*
 * Credit Notes
 */
if (!empty($customerBalance->creditnotes)) {
    print '<br>';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("CreditNotes").' ('.count($customerBalance->creditnotes).')</td>';
    print '<td class="center">'.$langs->trans("Date").'</td>';
    print '<td class="right">'.$langs->trans("AmountHT").'</td>';
    print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
    print '</tr>';

    foreach ($customerBalance->creditnotes as $creditnote) {
        print '<tr class="oddeven">';
        print '<td><a href="'.DOL_URL_ROOT.'/compta/facture/card.php?facid='.$creditnote['id'].'">'.$creditnote['ref'].'</a></td>';
        print '<td class="center">'.dol_print_date($creditnote['date'], 'day').'</td>';
        print '<td class="right amount">'.price($creditnote['total_ht'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '<td class="right amount" style="color: orange;">'.price($creditnote['total_ttc'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

/*
 * Customer Returns
 */
if (isModEnabled('customerreturn') && !empty($customerBalance->customerreturns)) {
    print '<br>';
    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("CustomerReturns").' ('.count($customerBalance->customerreturns).')</td>';
    print '<td class="center">'.$langs->trans("Date").'</td>';
    print '<td class="right">'.$langs->trans("AmountHT").'</td>';
    print '<td class="right">'.$langs->trans("AmountTTC").'</td>';
    print '</tr>';

    foreach ($customerBalance->customerreturns as $return) {
        print '<tr class="oddeven">';
        print '<td><a href="'.dol_buildpath('/custom/customerreturn/card.php', 1).'?id='.$return['id'].'">'.$return['ref'].'</a></td>';
        print '<td class="center">'.dol_print_date($return['date'], 'day').'</td>';
        print '<td class="right amount">'.price($return['total_ht'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '<td class="right amount" style="color: orange;">'.price($return['total_ttc'], 0, $langs, 1, -1, -1, $conf->currency).'</td>';
        print '</tr>';
    }

    print '</table>';
    print '</div>';
}

print '</div>';

print dol_get_fiche_end();

/*
 * PDF Export Section
 */
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';

// Generate PDF button
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("Documents").'</td>';
print '</tr>';

if ($user->hasRight('customerbalance', 'export')) {
    print '<tr class="oddeven">';
    print '<td colspan="4">';
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'" style="display:inline;">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="builddoc">';
    print '<input type="submit" class="button" value="'.$langs->trans("GeneratePDF").'">';
    print '</form>';
    print '</td>';
    print '</tr>';
}

// List existing PDF files
$diroutput = $conf->customerbalance->dir_output;
$filename = 'etat_financier_'.$object->id.'_'.dol_sanitizeFileName($object->name);
$filedir = $diroutput;

if (is_dir($filedir)) {
    $filearray = dol_dir_list($filedir, "files", 0, $filename.'.*\.pdf$', '', 'date', SORT_DESC);

    if (!empty($filearray)) {
        foreach ($filearray as $file) {
            print '<tr class="oddeven">';
            print '<td class="minwidth200">';
            print '<a class="documentdownload" href="'.dol_buildpath('/custom/customerbalance/document.php', 1).'?file='.urlencode($file['name']).'&action=download">';
            print img_mime($file['name'], $langs->trans("File").': '.$file['name']);
            print ' '.$file['name'];
            print '</a>';
            print '</td>';
            print '<td class="right nowraponall">'.dol_print_size($file['size'], 1).'</td>';
            print '<td class="right nowraponall">'.dol_print_date($file['date'], 'dayhour').'</td>';
            print '<td class="right">';
            print '<a class="deletefilelink" href="'.$_SERVER['PHP_SELF'].'?socid='.$socid.'&action=remove_file&token='.newToken().'&file='.urlencode($file['name']).'">';
            print img_picto($langs->trans("Delete"), 'delete');
            print '</a>';
            print '</td>';
            print '</tr>';
        }
    } else {
        print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("NoDocuments").'</td></tr>';
    }
} else {
    print '<tr class="oddeven"><td colspan="4" class="opacitymedium">'.$langs->trans("NoDocuments").'</td></tr>';
}

print '</table>';
print '</div>';
print '</div>';

// Right side - PDF Preview
print '<div class="fichehalfright">';

if (is_dir($filedir)) {
    $filearray = dol_dir_list($filedir, "files", 0, $filename.'.*\.pdf$', '', 'date', SORT_DESC);

    if (!empty($filearray)) {
        $lastfile = $filearray[0];

        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder centpercent">';
        print '<tr class="liste_titre">';
        print '<td>'.$langs->trans("Preview").' - '.$lastfile['name'].'</td>';
        print '</tr>';
        print '<tr class="oddeven">';
        print '<td class="center">';

        $urlfile = dol_buildpath('/custom/customerbalance/document.php', 1).'?file='.urlencode($lastfile['name']);

        print '<div style="width:100%; min-height:500px;">';
        print '<iframe src="'.$urlfile.'" style="width:100%; height:500px; border:1px solid #ccc;"></iframe>';
        print '</div>';

        print '<div class="margintoponlyshort">';
        print '<a class="button" href="'.$urlfile.'" target="_blank">'.$langs->trans("OpenInNewTab").'</a>';
        print '</div>';

        print '</td>';
        print '</tr>';
        print '</table>';
        print '</div>';
    }
}

print '</div>';
print '</div>';

llxFooter();
$db->close();
