<?php
/**
 * Hooks for Customer Check module
 * Checks for unpaid checks and credit limit before order/proposal validation
 */

class ActionsCustomercheck
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
     * @var string Return string for print
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
     * Hook formConfirm - Add warnings before validation of orders and proposals
     * Also displays warning on thirdparty card
     *
     * @param array       $parameters Hook parameters
     * @param CommonObject $object    Current object
     * @param string      $action     Current action
     * @param HookManager $hookmanager Hook manager
     * @return int                    0=OK, 1=Replace standard form
     */
    public function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = isset($parameters['context']) ? $parameters['context'] : '';

        // Handle thirdparty card - show informational warning
        if (strpos($context, 'thirdpartycard') !== false) {
            return $this->showThirdpartyWarning($object);
        }

        // Only process for validation action on orders and proposals
        if ($action != 'validate') {
            return 0;
        }

        // Check if we're on an order or proposal
        $isOrder = (isset($object->element) && $object->element == 'commande');
        $isProposal = (isset($object->element) && $object->element == 'propal');

        if (!$isOrder && !$isProposal) {
            return 0;
        }

        // Get the customer
        if (empty($object->socid)) {
            return 0;
        }

        $langs->load('customercheck@customercheck');

        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $societe = new Societe($this->db);
        if ($societe->fetch($object->socid) <= 0) {
            return 0;
        }

        $warnings = array();
        $hasUnpaidChecks = false;

        // Check 1: Returned/unpaid checks
        if (getDolGlobalString('CUSTOMERCHECK_ENABLE_RETURNED_CHECKS')) {
            $returnedChecks = $this->getReturnedChecks($object->socid);
            if (!empty($returnedChecks['count']) && $returnedChecks['count'] > 0) {
                $hasUnpaidChecks = true;
                $warnings[] = array(
                    'type' => 'returned_checks',
                    'message' => $langs->trans(
                        'WarningReturnedChecks',
                        $returnedChecks['count'],
                        price($returnedChecks['total'], 0, $langs, 1, -1, -1, $conf->currency)
                    )
                );
            }
        }

        // Check 2: Credit limit exceeded (encours)
        if (getDolGlobalString('CUSTOMERCHECK_ENABLE_CREDIT_LIMIT')) {
            if (!empty($societe->outstanding_limit) && $societe->outstanding_limit > 0) {
                $outstanding = $societe->getOutstandingBills('customer');
                $currentOutstanding = isset($outstanding['opened']) ? $outstanding['opened'] : 0;

                // Add the current document amount
                $totalWithDocument = $currentOutstanding + $object->total_ttc;

                if ($totalWithDocument > $societe->outstanding_limit) {
                    $exceededAmount = $totalWithDocument - $societe->outstanding_limit;
                    $warnings[] = array(
                        'type' => 'credit_limit',
                        'message' => $langs->trans(
                            'WarningCreditLimitExceeded',
                            price($societe->outstanding_limit, 0, $langs, 1, -1, -1, $conf->currency),
                            price($currentOutstanding, 0, $langs, 1, -1, -1, $conf->currency),
                            price($object->total_ttc, 0, $langs, 1, -1, -1, $conf->currency),
                            price($exceededAmount, 0, $langs, 1, -1, -1, $conf->currency)
                        )
                    );
                }
            }
        }

        // If we have warnings, add them to the confirmation form
        if (!empty($warnings)) {
            // Check if user has permission to bypass unpaid checks
            $canBypass = $user->hasRight('customercheck', 'validatealifornia');

            // Red error style
            $warningHtml = '<div class="error-validation-checks" style="margin-bottom: 15px;">';
            $warningHtml .= '<div style="background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 15px; margin-bottom: 10px;">';
            $warningHtml .= '<strong style="color: #721c24;"><i class="fas fa-exclamation-triangle"></i> '.$langs->trans('ValidationWarnings').'</strong>';
            $warningHtml .= '<ul style="margin: 10px 0 0 0; padding-left: 20px; color: #721c24;">';

            foreach ($warnings as $warning) {
                $warningHtml .= '<li style="margin-bottom: 5px;">'.$warning['message'].'</li>';
            }

            $warningHtml .= '</ul>';

            // If user has unpaid checks and NO permission to bypass, block validation
            if ($hasUnpaidChecks && !$canBypass) {
                $warningHtml .= '<p style="margin: 10px 0 0 0; color: #721c24; font-weight: bold;">'.$langs->trans('ValidationBlocked').'</p>';
                $warningHtml .= '</div>';
                $warningHtml .= '</div>';

                // Block the validation by changing action
                $action = '';

                $this->resprints = $warningHtml;
                return 1; // Return 1 to replace standard form (no confirm buttons)
            } else {
                $warningHtml .= '<p style="margin: 10px 0 0 0; color: #721c24; font-weight: bold;">'.$langs->trans('ConfirmContinueValidation').'</p>';
                $warningHtml .= '</div>';
                $warningHtml .= '</div>';

                $this->resprints = $warningHtml;
            }
        }

        return 0;
    }

    /**
     * Hook addMoreActionsButtons - Display warning banner on proposal/order card
     *
     * @param array       $parameters Hook parameters
     * @param CommonObject $object    Current object
     * @param string      $action     Current action
     * @param HookManager $hookmanager Hook manager
     * @return int                    0=OK
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs;

        $context = isset($parameters['context']) ? $parameters['context'] : '';

        // Only process for orders and proposals
        $isOrder = (strpos($context, 'ordercard') !== false);
        $isProposal = (strpos($context, 'propalcard') !== false);

        if (!$isOrder && !$isProposal) {
            return 0;
        }

        // Get the customer
        if (empty($object->socid)) {
            return 0;
        }

        // Check if returned checks warning is enabled
        if (!getDolGlobalString('CUSTOMERCHECK_ENABLE_RETURNED_CHECKS')) {
            return 0;
        }

        $langs->load('customercheck@customercheck');

        // Get returned checks with active sanction
        $returnedChecks = $this->getReturnedChecks($object->socid);

        // Show warning banner if there are returned checks with active sanction
        if (!empty($returnedChecks['count']) && $returnedChecks['count'] > 0) {
            $warningMsg = $langs->trans('WarningReturnedChecks', $returnedChecks['count'], price($returnedChecks['total'], 0, $langs, 1, -1, -1, $conf->currency));
            $warningMsg .= ' - ' . $langs->trans('ValidationBlocked');
            setEventMessages($warningMsg, null, 'warnings');
        }

        return 0;
    }

    /**
     * Get returned/unpaid checks for a customer with ACTIVE sanction
     *
     * @param int $socid Customer ID
     * @return array Array with 'count' and 'total'
     */
    private function getReturnedChecks($socid)
    {
        global $conf;

        $result = array('count' => 0, 'total' => 0, 'checks' => array());

        // Check our custom customercheck_returnedcheck table (with sanction system)
        $sql = "SHOW TABLES LIKE '".$this->db->prefix()."customercheck_returnedcheck'";
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $sql = "SELECT rowid, ref, amount, num_check, bank_name, date_check,";
            $sql .= " date_returned, reason, sanction_active";
            $sql .= " FROM ".$this->db->prefix()."customercheck_returnedcheck";
            $sql .= " WHERE fk_soc = ".((int) $socid);
            $sql .= " AND sanction_active = 1"; // Only active sanctions block validation
            $sql .= " AND entity = ".((int) $conf->entity);
            $sql .= " ORDER BY date_returned DESC";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $result['count']++;
                    $result['total'] += $obj->amount;
                    $result['checks'][] = array(
                        'id' => $obj->rowid,
                        'ref' => $obj->ref,
                        'amount' => $obj->amount,
                        'date' => $obj->date_returned,
                        'num' => $obj->num_check,
                        'reason' => $obj->reason
                    );
                }
                // Only return if we found results, otherwise check other tables
                if ($result['count'] > 0) {
                    return $result;
                }
            }
        }

        // Check custom orderpayment table - only where sanction is NOT lifted
        $sql = "SHOW TABLES LIKE '".$this->db->prefix()."order_payment'";
        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            $sql = "SELECT rowid, ref, amount, datep, num_payment, cancel_reason, sanction_lifted";
            $sql .= " FROM ".$this->db->prefix()."order_payment";
            $sql .= " WHERE fk_soc = ".((int) $socid);
            $sql .= " AND is_returned_check = 1";
            $sql .= " AND (sanction_lifted = 0 OR sanction_lifted IS NULL)"; // Only active sanctions
            $sql .= " ORDER BY datep DESC";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $result['count']++;
                    $result['total'] += $obj->amount;
                    $result['checks'][] = array(
                        'id' => $obj->rowid,
                        'ref' => $obj->ref,
                        'amount' => $obj->amount,
                        'date' => $obj->datep,
                        'num' => $obj->num_payment,
                        'reason' => $obj->cancel_reason
                    );
                }
                return $result;
            }
        }

        // Fallback: Check standard paiement table for rejected checks
        if ($result['count'] == 0) {
            $sql = "SELECT p.rowid, p.ref, p.amount, p.datep, p.num_paiement";
            $sql .= " FROM ".$this->db->prefix()."paiement as p";
            $sql .= " LEFT JOIN ".$this->db->prefix()."paiement_facture as pf ON pf.fk_paiement = p.rowid";
            $sql .= " LEFT JOIN ".$this->db->prefix()."facture as f ON f.rowid = pf.fk_facture";
            $sql .= " LEFT JOIN ".$this->db->prefix()."c_paiement as cp ON cp.id = p.fk_paiement";
            $sql .= " WHERE f.fk_soc = ".((int) $socid);
            $sql .= " AND cp.code = 'CHQ'";
            $sql .= " AND p.statut = 0";
            $sql .= " ORDER BY p.datep DESC";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $result['count']++;
                    $result['total'] += $obj->amount;
                    $result['checks'][] = array(
                        'id' => $obj->rowid,
                        'ref' => $obj->ref,
                        'amount' => $obj->amount,
                        'date' => $obj->datep,
                        'num' => $obj->num_paiement
                    );
                }
            }
        }

        return $result;
    }

    /**
     * Show warning on thirdparty card if customer has unpaid checks with active sanction
     *
     * @param CommonObject $object Societe object
     * @return int 0=OK
     */
    private function showThirdpartyWarning(&$object)
    {
        global $conf, $langs;

        // Check if object is a Societe
        if (!is_object($object) || get_class($object) != 'Societe') {
            return 0;
        }

        $socid = $object->id;
        if ($socid <= 0) {
            return 0;
        }

        // Check if returned checks warning is enabled
        if (!getDolGlobalString('CUSTOMERCHECK_ENABLE_RETURNED_CHECKS')) {
            return 0;
        }

        $langs->load('customercheck@customercheck');

        // Get returned checks with active sanction
        $returnedChecks = $this->getReturnedChecks($socid);

        // Show warning if there are returned checks with active sanction
        if (!empty($returnedChecks['count']) && $returnedChecks['count'] > 0) {
            $out = '<div class="warning" style="margin-bottom: 10px;">';
            $out .= img_warning().' ';
            $out .= '<strong>'.$langs->trans('WarningReturnedChecksThirdparty', $returnedChecks['count'], price($returnedChecks['total'], 0, $langs, 1, -1, -1, $conf->currency)).'</strong>';

            // Link to returned checks list
            $url = dol_buildpath('/customercheck/returnedchecks_list.php', 1).'?search_socid='.$socid;
            $out .= ' <a href="'.$url.'" class="paddingleft">'.img_picto($langs->trans('ViewReturnedChecks'), 'object_payment').'</a>';

            $out .= '</div>';

            $this->resprints = $out;
        }

        return 0;
    }
}
