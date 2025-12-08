<?php
/**
 * Class CustomerBalance
 * Retrieves and calculates all financial data for a customer
 */

class CustomerBalance
{
    /**
     * @var DoliDB Database handler
     */
    public $db;

    /**
     * @var int Customer ID
     */
    public $socid;

    /**
     * @var Societe Customer object
     */
    public $thirdparty;

    /**
     * @var string Error message
     */
    public $error;

    /**
     * @var array Errors
     */
    public $errors = array();

    // Financial data
    public $invoices = array();           // All invoices
    public $payments = array();           // All payments
    public $creditnotes = array();        // Credit notes (avoirs)
    public $customerreturns = array();    // Customer returns

    // Totals
    public $total_invoiced = 0;           // Total facturé
    public $total_invoiced_ht = 0;        // Total facturé HT
    public $total_paid = 0;               // Total encaissé
    public $total_creditnotes = 0;        // Total avoirs
    public $total_returns = 0;            // Total retours
    public $balance = 0;                  // Solde (reste à payer)
    public $outstanding = 0;              // Encours autorisé
    public $outstanding_used = 0;         // Encours utilisé

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
     * Load all financial data for a customer
     *
     * @param int $socid Customer ID
     * @param string $date_start Start date filter (optional)
     * @param string $date_end End date filter (optional)
     * @return int 1 if OK, -1 if KO
     */
    public function fetch($socid, $date_start = '', $date_end = '')
    {
        global $conf;

        $this->socid = $socid;

        // Load customer
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $this->thirdparty = new Societe($this->db);
        if ($this->thirdparty->fetch($socid) <= 0) {
            $this->error = 'Customer not found';
            return -1;
        }

        // Get outstanding limit
        $this->outstanding = $this->thirdparty->outstanding_limit;

        // Load invoices
        $this->loadInvoices($date_start, $date_end);

        // Load payments
        $this->loadPayments($date_start, $date_end);

        // Load credit notes
        $this->loadCreditNotes($date_start, $date_end);

        // Load customer returns (if module enabled)
        if (isModEnabled('customerreturn')) {
            $this->loadCustomerReturns($date_start, $date_end);
        }

        // Calculate totals
        $this->calculateTotals();

        return 1;
    }

    /**
     * Load all invoices for the customer
     *
     * @param string $date_start Start date
     * @param string $date_end End date
     */
    private function loadInvoices($date_start = '', $date_end = '')
    {
        $sql = "SELECT f.rowid, f.ref, f.datef, f.date_lim_reglement, f.total_ht, f.total_ttc, f.total_tva,";
        $sql .= " f.paye, f.fk_statut as status, f.type";
        $sql .= " FROM ".$this->db->prefix()."facture as f";
        $sql .= " WHERE f.fk_soc = ".((int) $this->socid);
        $sql .= " AND f.entity IN (".getEntity('invoice').")";
        // Only standard invoices (type 0) and replacement invoices (type 1)
        $sql .= " AND f.type IN (0, 1)";
        // Only validated invoices
        $sql .= " AND f.fk_statut > 0";

        if ($date_start) {
            $sql .= " AND f.datef >= '".$this->db->escape($date_start)."'";
        }
        if ($date_end) {
            $sql .= " AND f.datef <= '".$this->db->escape($date_end)."'";
        }

        $sql .= " ORDER BY f.datef DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                // Get amount already paid for this invoice
                $paid = $this->getInvoicePaidAmount($obj->rowid);

                $this->invoices[] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'date' => $this->db->jdate($obj->datef),
                    'date_due' => $this->db->jdate($obj->date_lim_reglement),
                    'total_ht' => $obj->total_ht,
                    'total_tva' => $obj->total_tva,
                    'total_ttc' => $obj->total_ttc,
                    'paid' => $paid,
                    'remain' => $obj->total_ttc - $paid,
                    'status' => $obj->status,
                    'paye' => $obj->paye,
                    'type' => $obj->type
                );
            }
            $this->db->free($resql);
        }
    }

    /**
     * Get amount already paid for an invoice
     *
     * @param int $facid Invoice ID
     * @return float Amount paid
     */
    private function getInvoicePaidAmount($facid)
    {
        $sql = "SELECT SUM(pf.amount) as paid";
        $sql .= " FROM ".$this->db->prefix()."paiement_facture as pf";
        $sql .= " INNER JOIN ".$this->db->prefix()."paiement as p ON p.rowid = pf.fk_paiement";
        $sql .= " WHERE pf.fk_facture = ".((int) $facid);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->paid ? $obj->paid : 0;
        }
        return 0;
    }

    /**
     * Load all payments for the customer
     *
     * @param string $date_start Start date
     * @param string $date_end End date
     */
    private function loadPayments($date_start = '', $date_end = '')
    {
        $sql = "SELECT DISTINCT p.rowid, p.ref, p.datep, p.amount, p.num_paiement, c.code as payment_type, c.libelle as payment_label";
        $sql .= " FROM ".$this->db->prefix()."paiement as p";
        $sql .= " INNER JOIN ".$this->db->prefix()."paiement_facture as pf ON pf.fk_paiement = p.rowid";
        $sql .= " INNER JOIN ".$this->db->prefix()."facture as f ON f.rowid = pf.fk_facture";
        $sql .= " LEFT JOIN ".$this->db->prefix()."c_paiement as c ON c.id = p.fk_paiement";
        $sql .= " WHERE f.fk_soc = ".((int) $this->socid);
        $sql .= " AND f.entity IN (".getEntity('invoice').")";

        if ($date_start) {
            $sql .= " AND p.datep >= '".$this->db->escape($date_start)."'";
        }
        if ($date_end) {
            $sql .= " AND p.datep <= '".$this->db->escape($date_end)."'";
        }

        $sql .= " ORDER BY p.datep DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                // Get total for this payment (sum of all invoice payments)
                $paymentTotal = $this->getPaymentTotal($obj->rowid);

                $this->payments[] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'date' => $this->db->jdate($obj->datep),
                    'amount' => $paymentTotal,
                    'num_paiement' => $obj->num_paiement,
                    'payment_type' => $obj->payment_type,
                    'payment_label' => $obj->payment_label
                );
            }
            $this->db->free($resql);
        }
    }

    /**
     * Get total amount for a payment
     *
     * @param int $paymentid Payment ID
     * @return float Total amount
     */
    private function getPaymentTotal($paymentid)
    {
        $sql = "SELECT SUM(pf.amount) as total";
        $sql .= " FROM ".$this->db->prefix()."paiement_facture as pf";
        $sql .= " INNER JOIN ".$this->db->prefix()."facture as f ON f.rowid = pf.fk_facture";
        $sql .= " WHERE pf.fk_paiement = ".((int) $paymentid);
        $sql .= " AND f.fk_soc = ".((int) $this->socid);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return $obj->total ? $obj->total : 0;
        }
        return 0;
    }

    /**
     * Load credit notes for the customer
     *
     * @param string $date_start Start date
     * @param string $date_end End date
     */
    private function loadCreditNotes($date_start = '', $date_end = '')
    {
        $sql = "SELECT f.rowid, f.ref, f.datef, f.total_ht, f.total_ttc, f.total_tva, f.fk_statut as status";
        $sql .= " FROM ".$this->db->prefix()."facture as f";
        $sql .= " WHERE f.fk_soc = ".((int) $this->socid);
        $sql .= " AND f.entity IN (".getEntity('invoice').")";
        $sql .= " AND f.type = 2"; // Credit notes only
        $sql .= " AND f.fk_statut > 0"; // Validated

        if ($date_start) {
            $sql .= " AND f.datef >= '".$this->db->escape($date_start)."'";
        }
        if ($date_end) {
            $sql .= " AND f.datef <= '".$this->db->escape($date_end)."'";
        }

        $sql .= " ORDER BY f.datef DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $this->creditnotes[] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'date' => $this->db->jdate($obj->datef),
                    'total_ht' => $obj->total_ht,
                    'total_tva' => $obj->total_tva,
                    'total_ttc' => $obj->total_ttc,
                    'status' => $obj->status
                );
            }
            $this->db->free($resql);
        }
    }

    /**
     * Load customer returns
     *
     * @param string $date_start Start date
     * @param string $date_end End date
     */
    private function loadCustomerReturns($date_start = '', $date_end = '')
    {
        // Check if customerreturn table exists
        $sql = "SHOW TABLES LIKE '".$this->db->prefix()."customerreturn'";
        $resql = $this->db->query($sql);
        if (!$resql || $this->db->num_rows($resql) == 0) {
            return;
        }

        $sql = "SELECT cr.rowid, cr.ref, cr.date_creation, cr.total_ht, cr.total_ttc, cr.total_tva, cr.statut as status";
        $sql .= " FROM ".$this->db->prefix()."customerreturn as cr";
        $sql .= " WHERE cr.fk_soc = ".((int) $this->socid);
        $sql .= " AND cr.statut > 0"; // Validated

        if ($date_start) {
            $sql .= " AND cr.date_creation >= '".$this->db->escape($date_start)."'";
        }
        if ($date_end) {
            $sql .= " AND cr.date_creation <= '".$this->db->escape($date_end)."'";
        }

        $sql .= " ORDER BY cr.date_creation DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $this->customerreturns[] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'date' => $this->db->jdate($obj->date_creation),
                    'total_ht' => $obj->total_ht,
                    'total_tva' => $obj->total_tva,
                    'total_ttc' => $obj->total_ttc,
                    'status' => $obj->status
                );
            }
            $this->db->free($resql);
        }
    }

    /**
     * Calculate all totals
     */
    private function calculateTotals()
    {
        // Total invoiced
        $this->total_invoiced = 0;
        $this->total_invoiced_ht = 0;
        foreach ($this->invoices as $invoice) {
            $this->total_invoiced += $invoice['total_ttc'];
            $this->total_invoiced_ht += $invoice['total_ht'];
        }

        // Total paid
        $this->total_paid = 0;
        foreach ($this->payments as $payment) {
            $this->total_paid += $payment['amount'];
        }

        // Total credit notes
        $this->total_creditnotes = 0;
        foreach ($this->creditnotes as $creditnote) {
            $this->total_creditnotes += $creditnote['total_ttc'];
        }

        // Total returns
        $this->total_returns = 0;
        foreach ($this->customerreturns as $return) {
            $this->total_returns += $return['total_ttc'];
        }

        // Balance = Invoiced - Paid - Credit notes
        $this->balance = $this->total_invoiced - $this->total_paid - $this->total_creditnotes;

        // Outstanding used = unpaid invoices
        $this->outstanding_used = 0;
        foreach ($this->invoices as $invoice) {
            if ($invoice['remain'] > 0) {
                $this->outstanding_used += $invoice['remain'];
            }
        }
    }

    /**
     * Get summary data for display
     *
     * @return array Summary data
     */
    public function getSummary()
    {
        return array(
            'total_invoiced' => $this->total_invoiced,
            'total_invoiced_ht' => $this->total_invoiced_ht,
            'total_paid' => $this->total_paid,
            'total_creditnotes' => $this->total_creditnotes,
            'total_returns' => $this->total_returns,
            'balance' => $this->balance,
            'outstanding' => $this->outstanding,
            'outstanding_used' => $this->outstanding_used,
            'outstanding_available' => $this->outstanding - $this->outstanding_used,
            'nb_invoices' => count($this->invoices),
            'nb_payments' => count($this->payments),
            'nb_creditnotes' => count($this->creditnotes),
            'nb_returns' => count($this->customerreturns)
        );
    }

    /**
     * Get unpaid invoices
     *
     * @return array Unpaid invoices
     */
    public function getUnpaidInvoices()
    {
        $unpaid = array();
        foreach ($this->invoices as $invoice) {
            if ($invoice['remain'] > 0.01) {
                $unpaid[] = $invoice;
            }
        }
        return $unpaid;
    }

    /**
     * Get overdue invoices
     *
     * @return array Overdue invoices
     */
    public function getOverdueInvoices()
    {
        $overdue = array();
        $now = dol_now();
        foreach ($this->invoices as $invoice) {
            if ($invoice['remain'] > 0.01 && $invoice['date_due'] && $invoice['date_due'] < $now) {
                $overdue[] = $invoice;
            }
        }
        return $overdue;
    }
}
