<?php
/**
 * Hooks for OrderPayment module
 * Adds payment buttons and info on order cards
 */

class ActionsOrderpayment
{
    /**
     * @var DoliDB Database handler
     */
    private $db;

    /**
     * @var string Error message
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Results from hooks
     */
    public $results = array();

    /**
     * @var string Return string
     */
    public $resprints = '';

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Add more buttons to actions on order card and payment card
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = isset($parameters['context']) ? $parameters['context'] : '';

        // Handle invoice payment card - add cancel buttons
        if (strpos($context, 'paiementcard') !== false || strpos($context, 'paymentcard') !== false) {
            return $this->addPaymentCancelButtons($object, $action);
        }

        // Only on order card (check multiple possible context values)
        if (strpos($context, 'ordercard') === false && strpos($context, 'ordersuppliercard') === false) {
            return 0;
        }

        // Only for customer orders
        if (strpos($context, 'ordersuppliercard') !== false) {
            return 0;
        }

        // Check module and permissions
        if (empty($conf->orderpayment) || empty($conf->orderpayment->enabled)) {
            return 0;
        }
        if (!$user->hasRight('orderpayment', 'write')) {
            return 0;
        }

        // Check if object is a Commande
        if (!is_object($object) || get_class($object) != 'Commande') {
            return 0;
        }

        // Load translations
        $langs->load('orderpayment@orderpayment');
        $langs->load('bills');

        // Get payment info
        dol_include_once('/orderpayment/class/orderpayment.class.php');
        $orderpayment = new OrderPayment($this->db);
        $totalPaid = $orderpayment->getSumPaymentsForOrder($object->id);
        $remainsToPay = $object->total_ttc - $totalPaid;

        // Show payment button only if order is validated and not fully paid
        // Commande::STATUS_VALIDATED = 1, STATUS_SHIPMENTONPROCESS = 2, etc.
        if ($object->statut >= 1 && $remainsToPay > 0.01) {
            $url = dol_buildpath('/orderpayment/card.php', 1).'?action=create&ordid='.$object->id;
            print '<a class="butAction" href="'.$url.'">'.$langs->trans('RegisterOrderPayment').'</a>';
        }

        return 0;
    }

    /**
     * Add cancel buttons on invoice payment card
     *
     * @param CommonObject $object Payment object
     * @param string $action Current action
     * @return int 0=OK, <0=KO
     */
    private function addPaymentCancelButtons(&$object, &$action)
    {
        global $conf, $langs, $user;

        // Check module
        if (empty($conf->orderpayment) || empty($conf->orderpayment->enabled)) {
            return 0;
        }

        // Check permission
        if (!$user->hasRight('facture', 'paiement')) {
            return 0;
        }

        // Check if it's a Paiement object
        if (!is_object($object) || get_class($object) != 'Paiement') {
            return 0;
        }

        // Load translations
        $langs->load('orderpayment@orderpayment');

        // Check if payment is already cancelled
        $sql = "SELECT statut FROM ".$this->db->prefix()."paiement WHERE rowid = ".((int) $object->id);
        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj->statut == 2) {
                // Already cancelled, don't show buttons
                return 0;
            }
        }

        // Add cancel buttons
        $url = dol_buildpath('/orderpayment/cancel_invoice_payment.php', 1).'?id='.$object->id;
        print '<a class="butActionDelete" href="'.$url.'&action=cancel&token='.newToken().'">'.$langs->trans('CancelPayment').'</a>';
        print '<a class="butActionDelete" href="'.$url.'&action=returned_check&token='.newToken().'">'.$langs->trans('MarkAsReturnedCheck').'</a>';

        return 0;
    }

    /**
     * Add info to order banner / form and cancel buttons on payment card
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = isset($parameters['context']) ? $parameters['context'] : '';

        // Handle payment card - add cancel buttons
        if (strpos($context, 'paymentcard') !== false) {
            return $this->addPaymentCancelButtonsInForm($object, $action);
        }

        // Only on order card
        if (strpos($context, 'ordercard') === false) {
            return 0;
        }

        // Check module and permissions
        if (empty($conf->orderpayment) || empty($conf->orderpayment->enabled)) {
            return 0;
        }
        if (!$user->hasRight('orderpayment', 'read')) {
            return 0;
        }

        // Check if object is a Commande
        if (!is_object($object) || get_class($object) != 'Commande') {
            return 0;
        }

        // Load translations
        $langs->load('orderpayment@orderpayment');
        $langs->load('bills');

        // Get payment info
        dol_include_once('/orderpayment/class/orderpayment.class.php');
        $orderpayment = new OrderPayment($this->db);
        $totalPaid = $orderpayment->getSumPaymentsForOrder($object->id);
        $remainsToPay = $object->total_ttc - $totalPaid;

        // Show payment summary in object form
        if ($totalPaid > 0 || $remainsToPay != $object->total_ttc) {
            $out = '<tr class="field_orderpayment_summary">';
            $out .= '<td class="titlefield">'.$langs->trans("TabOrderPayments").'</td>';
            $out .= '<td>';

            // Already paid
            $out .= '<span class="opacitymedium">'.$langs->trans("AlreadyPaidOnOrder").':</span> ';
            $out .= '<span class="amount">'.price($totalPaid, 0, $langs, 1, -1, -1, $conf->currency).'</span>';

            // Remains to pay
            if ($remainsToPay > 0.01) {
                $out .= ' - <span class="opacitymedium">'.$langs->trans("RemainsToPay").':</span> ';
                $out .= '<span class="amount amountremaintopay">'.price($remainsToPay, 0, $langs, 1, -1, -1, $conf->currency).'</span>';
            } elseif (abs($remainsToPay) < 0.01) {
                $out .= ' - <span class="badge badge-status4">'.$langs->trans("OrderFullyPaid").'</span>';
            }

            // Link to payments tab
            $url = dol_buildpath('/orderpayment/orderpayment_tab.php', 1).'?id='.$object->id;
            $out .= ' <a href="'.$url.'" class="paddingleft">'.img_picto($langs->trans("TabOrderPayments"), 'payment').'</a>';

            $out .= '</td>';
            $out .= '</tr>';

            $this->resprints = $out;
        }

        return 0;
    }

    /**
     * Add cancel buttons in form on invoice payment card
     *
     * @param CommonObject $object Payment object
     * @param string $action Current action
     * @return int 0=OK, <0=KO
     */
    private function addPaymentCancelButtonsInForm(&$object, &$action)
    {
        global $conf, $langs, $user;

        // Check module
        if (empty($conf->orderpayment) || empty($conf->orderpayment->enabled)) {
            return 0;
        }

        // Check permission
        if (!$user->hasRight('facture', 'paiement')) {
            return 0;
        }

        // Check if it's a Paiement object
        if (!is_object($object) || get_class($object) != 'Paiement') {
            return 0;
        }

        // Load translations
        $langs->load('orderpayment@orderpayment');

        // Check if payment is already cancelled
        $sql = "SELECT statut, is_returned_check FROM ".$this->db->prefix()."paiement WHERE rowid = ".((int) $object->id);
        $resql = $this->db->query($sql);
        $isCancelled = false;
        $isReturnedCheck = false;
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj && $obj->statut == 2) {
                $isCancelled = true;
                $isReturnedCheck = $obj->is_returned_check;
            }
        }

        // Add cancel buttons as a row in the table
        $out = '<tr class="field_cancel_payment">';
        $out .= '<td class="titlefield">'.$langs->trans("Actions").'</td>';
        $out .= '<td>';

        if ($isCancelled) {
            $out .= '<span class="badge badge-status8">'.$langs->trans("PaymentAlreadyCancelled").'</span>';
            if ($isReturnedCheck) {
                $out .= ' <span class="badge badge-status1">'.$langs->trans("ReturnedCheck").'</span>';
            }
        } else {
            $url = dol_buildpath('/orderpayment/cancel_invoice_payment.php', 1).'?id='.$object->id;
            $out .= '<a class="butActionDelete" href="'.$url.'&action=cancel&token='.newToken().'">'.$langs->trans('CancelPayment').'</a> ';
            $out .= '<a class="butActionDelete" href="'.$url.'&action=returned_check&token='.newToken().'">'.$langs->trans('MarkAsReturnedCheck').'</a>';
        }

        $out .= '</td>';
        $out .= '</tr>';

        $this->resprints = $out;

        return 0;
    }

    /**
     * Add extra info on thirdparty card - customer stats box
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object (Societe)
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function addMoreBoxStatsCustomer($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        // Check module and permissions
        if (empty($conf->orderpayment) || empty($conf->orderpayment->enabled)) {
            return 0;
        }
        if (!$user->hasRight('orderpayment', 'read')) {
            return 0;
        }

        if (empty($object->id)) {
            return 0;
        }

        // Load translations
        $langs->load('orderpayment@orderpayment');

        // Get total payments for this thirdparty
        dol_include_once('/orderpayment/class/orderpayment.class.php');
        $orderpayment = new OrderPayment($this->db);
        $total = $orderpayment->getSumPaymentsForThirdparty($object->id);

        $out = '';

        if ($total > 0) {
            $langs->load('bills');

            $out .= '<tr class="oddeven">';
            $out .= '<td class="titlefield">'.$langs->trans("TabOrderPayments").'</td>';
            $out .= '<td>';
            $out .= '<span class="amount">'.price($total, 0, $langs, 1, -1, -1, $conf->currency).'</span>';

            // Link to payments list filtered by this thirdparty
            $url = dol_buildpath('/orderpayment/list.php', 1).'?search_socid='.$object->id;
            $out .= ' <a href="'.$url.'" class="paddingleft">'.img_picto($langs->trans("TabOrderPayments"), 'payment').'</a>';

            $out .= '</td>';
            $out .= '</tr>';
        }

        // Returned checks warning is now handled by customercheck module
        // Don't show here to avoid duplicates

        if ($out) {
            $this->resprints = $out;
        }

        return 0;
    }

    /**
     * Hook formConfirm - Disabled, all returned checks warnings are handled by customercheck module
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        // All returned checks warnings (thirdparty card, order validation, etc.)
        // are now handled by the customercheck module to avoid duplicates
        // and centralize sanction management
        return 0;
    }

    /**
     * Hook called after thirdparty card banner
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function printObjectLine($parameters, &$object, &$action, $hookmanager)
    {
        return 0;
    }
}
