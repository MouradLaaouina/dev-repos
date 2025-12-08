<?php
/**
 * Order Payment Class
 * Manages payments linked directly to orders without invoices
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

class OrderPayment extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'orderpayment';

    /**
     * @var string Name of table without prefix where object is stored
     */
    public $table_element = 'order_payment';

    /**
     * @var string Field with ID of parent key if this field has a parent
     */
    public $fk_element = 'fk_orderpayment';

    /**
     * @var string Picto
     */
    public $picto = 'payment';

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var string Ref
     */
    public $ref;

    /**
     * @var int Third party id
     */
    public $fk_soc;

    /**
     * @var float Amount
     */
    public $amount;

    /**
     * @var int Payment date (timestamp)
     */
    public $datep;

    /**
     * @var int Payment mode id (from c_paiement)
     */
    public $fk_mode_reglement;

    /**
     * @var string Payment number (check number, etc.)
     */
    public $num_payment;

    /**
     * @var int Bank line id (llx_bank)
     */
    public $fk_bank;

    /**
     * @var int Bank account id
     */
    public $fk_account;

    /**
     * @var string Public note
     */
    public $note_public;

    /**
     * @var string Private note
     */
    public $note_private;

    /**
     * @var int Status (1=validated, 9=cancelled)
     */
    public $status;

    /**
     * @var int User id who created
     */
    public $fk_user_creat;

    /**
     * @var int User id who modified
     */
    public $fk_user_modif;

    /**
     * @var int User id who cancelled
     */
    public $fk_user_cancel;

    /**
     * @var int Creation date (timestamp)
     */
    public $datec;

    /**
     * @var int Cancellation date (timestamp)
     */
    public $date_cancel;

    /**
     * @var string Cancel reason
     */
    public $cancel_reason;

    /**
     * @var int Is returned check (cheque impaye)
     */
    public $is_returned_check;

    /**
     * @var array Lines (linked orders)
     */
    public $lines = array();

    /**
     * Status constants
     */
    const STATUS_VALIDATED = 1;
    const STATUS_CANCELLED = 9;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        $this->status = self::STATUS_VALIDATED;
        $this->is_returned_check = 0;
    }

    /**
     * Create payment in database
     *
     * @param User $user User that creates
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf;

        $error = 0;
        $now = dol_now();

        // Clean parameters
        $this->amount = price2num($this->amount);
        $this->fk_soc = (int) $this->fk_soc;

        // Validate
        if (empty($this->amount) || $this->amount <= 0) {
            $this->errors[] = 'ErrorFieldRequired - Amount';
            return -1;
        }
        if (empty($this->fk_soc)) {
            $this->errors[] = 'ErrorFieldRequired - ThirdParty';
            return -1;
        }
        if (empty($this->lines)) {
            $this->errors[] = 'ErrorFieldRequired - Orders';
            return -1;
        }

        // Generate reference
        $this->ref = $this->getNextNumRef();

        $this->db->begin();

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element." (";
        $sql .= "entity, ref, fk_soc, amount, datep, fk_mode_reglement, num_payment,";
        $sql .= "fk_bank, fk_account, note_public, note_private, status, fk_user_creat, datec";
        $sql .= ") VALUES (";
        $sql .= ((int) $conf->entity).",";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= ((int) $this->fk_soc).",";
        $sql .= ((float) $this->amount).",";
        $sql .= ($this->datep ? "'".$this->db->idate($this->datep)."'" : "'".$this->db->idate($now)."'").",";
        $sql .= ($this->fk_mode_reglement > 0 ? ((int) $this->fk_mode_reglement) : "NULL").",";
        $sql .= ($this->num_payment ? "'".$this->db->escape($this->num_payment)."'" : "NULL").",";
        $sql .= ($this->fk_bank > 0 ? ((int) $this->fk_bank) : "NULL").",";
        $sql .= ($this->fk_account > 0 ? ((int) $this->fk_account) : "NULL").",";
        $sql .= ($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "NULL").",";
        $sql .= ($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "NULL").",";
        $sql .= ((int) self::STATUS_VALIDATED).",";
        $sql .= ((int) $user->id).",";
        $sql .= "'".$this->db->idate($now)."'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error ".$this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);

            // Insert lines (linked orders)
            foreach ($this->lines as $line) {
                $sqlline = "INSERT INTO ".$this->db->prefix()."order_payment_det (";
                $sqlline .= "fk_payment, fk_commande, amount, entity";
                $sqlline .= ") VALUES (";
                $sqlline .= ((int) $this->id).",";
                $sqlline .= ((int) $line->fk_commande).",";
                $sqlline .= ((float) $line->amount).",";
                $sqlline .= ((int) $conf->entity);
                $sqlline .= ")";

                if (!$this->db->query($sqlline)) {
                    $error++;
                    $this->errors[] = "Error line: ".$this->db->lasterror();
                    break;
                }
            }
        }

        // Add to bank if account selected
        if (!$error && $this->fk_account > 0) {
            $result = $this->addPaymentToBank($user, 'payment_order', '(OrderPayment)', $this->fk_account, '', '');
            if ($result < 0) {
                $error++;
            }
        }

        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Add payment to bank account
     *
     * @param User $user User object
     * @param string $mode payment_order
     * @param string $label Label
     * @param int $accountid Bank account id
     * @param string $emession_number Check number
     * @param string $banque Bank name
     * @return int <0 if KO, bank_line_id if OK
     */
    public function addPaymentToBank($user, $mode, $label, $accountid, $emession_number = '', $banque = '')
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

        $error = 0;
        $bank_line_id = 0;

        if (isModEnabled('bank')) {
            $acc = new Account($this->db);
            $acc->fetch($accountid);

            // Insert into bank
            $bank_line_id = $acc->addline(
                $this->datep,
                $this->fk_mode_reglement,
                $label.' '.$this->ref,
                $this->amount,
                $this->num_payment,
                '',
                $user,
                $emession_number,
                $banque
            );

            if ($bank_line_id > 0) {
                // Update fk_bank
                $sql = "UPDATE ".$this->db->prefix().$this->table_element;
                $sql .= " SET fk_bank = ".((int) $bank_line_id);
                $sql .= " WHERE rowid = ".((int) $this->id);
                $this->db->query($sql);
                $this->fk_bank = $bank_line_id;

                // Link bank line to payment
                $result = $acc->add_url_line($bank_line_id, $this->id, DOL_URL_ROOT.'/custom/orderpayment/card.php?id=', '(paiement)', 'payment_order');
                if ($result < 0) {
                    $error++;
                    $this->errors[] = $acc->error;
                }

                // Link bank line to thirdparty
                if (!$error && $this->fk_soc > 0) {
                    $result = $acc->add_url_line($bank_line_id, $this->fk_soc, DOL_URL_ROOT.'/societe/card.php?socid=', '', 'company');
                }
            } else {
                $error++;
                $this->errors[] = $acc->error;
            }
        }

        if ($error) {
            return -1;
        }
        return $bank_line_id;
    }

    /**
     * Load object in memory from the database
     *
     * @param int $id Id object
     * @param string $ref Ref
     * @return int <0 if KO, 0 if not found, >0 if OK
     */
    public function fetch($id, $ref = null)
    {
        $sql = "SELECT op.rowid, op.entity, op.ref, op.fk_soc, op.amount, op.datep,";
        $sql .= " op.fk_mode_reglement, op.num_payment, op.fk_bank, op.fk_account,";
        $sql .= " op.note_public, op.note_private,";
        $sql .= " op.status, op.fk_user_creat, op.fk_user_modif, op.fk_user_cancel,";
        $sql .= " op.datec, op.tms, op.date_cancel, op.cancel_reason, op.is_returned_check";
        $sql .= " FROM ".$this->db->prefix().$this->table_element." as op";
        if ($id > 0) {
            $sql .= " WHERE op.rowid = ".((int) $id);
        } elseif ($ref) {
            $sql .= " WHERE op.ref = '".$this->db->escape($ref)."'";
        } else {
            return -1;
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->entity = $obj->entity;
                $this->ref = $obj->ref;
                $this->fk_soc = $obj->fk_soc;
                $this->amount = $obj->amount;
                $this->datep = $this->db->jdate($obj->datep);
                $this->fk_mode_reglement = $obj->fk_mode_reglement;
                $this->num_payment = $obj->num_payment;
                $this->fk_bank = $obj->fk_bank;
                $this->fk_account = $obj->fk_account;
                $this->note_public = $obj->note_public;
                $this->note_private = $obj->note_private;
                $this->status = $obj->status;
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->fk_user_modif = $obj->fk_user_modif;
                $this->fk_user_cancel = $obj->fk_user_cancel;
                $this->datec = $this->db->jdate($obj->datec);
                $this->date_cancel = $this->db->jdate($obj->date_cancel);
                $this->cancel_reason = $obj->cancel_reason;
                $this->is_returned_check = $obj->is_returned_check;

                // Load lines
                $this->lines = $this->fetchLines();

                $this->db->free($resql);
                return 1;
            }
            $this->db->free($resql);
            return 0;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Load lines (linked orders)
     *
     * @return array Array of lines
     */
    public function fetchLines()
    {
        $lines = array();

        $sql = "SELECT rowid, fk_payment, fk_commande, amount, entity";
        $sql .= " FROM ".$this->db->prefix()."order_payment_det";
        $sql .= " WHERE fk_payment = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $line = new stdClass();
                $line->id = $obj->rowid;
                $line->fk_payment = $obj->fk_payment;
                $line->fk_commande = $obj->fk_commande;
                $line->amount = $obj->amount;
                $line->entity = $obj->entity;
                $lines[] = $line;
            }
            $this->db->free($resql);
        }

        return $lines;
    }

    /**
     * Cancel payment
     *
     * @param User $user User that cancels
     * @param string $reason Cancel reason
     * @param int $is_returned_check Is it a returned check
     * @return int <0 if KO, >0 if OK
     */
    public function cancel($user, $reason = '', $is_returned_check = 0)
    {
        global $conf;

        $error = 0;
        $now = dol_now();

        $this->db->begin();

        // Cancel payment
        $sql = "UPDATE ".$this->db->prefix().$this->table_element;
        $sql .= " SET status = ".((int) self::STATUS_CANCELLED).",";
        $sql .= " fk_user_cancel = ".((int) $user->id).",";
        $sql .= " date_cancel = '".$this->db->idate($now)."',";
        $sql .= " cancel_reason = ".($reason ? "'".$this->db->escape($reason)."'" : "NULL").",";
        $sql .= " is_returned_check = ".((int) $is_returned_check);
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->error = $this->db->lasterror();
        }

        // Reverse bank entry if exists
        if (!$error && $this->fk_bank > 0 && isModEnabled('bank')) {
            require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

            $acc = new Account($this->db);
            if ($this->fk_account > 0) {
                $acc->fetch($this->fk_account);
            }

            // Add reverse entry
            $label = $is_returned_check ? 'Cheque impaye - ' : 'Annulation paiement - ';
            $bank_line_id = $acc->addline(
                $now,
                $this->fk_mode_reglement,
                $label.$this->ref,
                -$this->amount, // Negative amount
                '',
                '',
                $user
            );

            if ($bank_line_id < 0) {
                $error++;
                $this->errors[] = $acc->error;
            }
        }

        // If returned check, record it for the client
        if (!$error && $is_returned_check && $this->fk_soc > 0) {
            // Could add to a specific table or extrafield for thirdparty
            // For now we just keep the flag on the payment
        }

        if (!$error) {
            $this->status = self::STATUS_CANCELLED;
            $this->fk_user_cancel = $user->id;
            $this->date_cancel = $now;
            $this->cancel_reason = $reason;
            $this->is_returned_check = $is_returned_check;

            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Get total amount paid for an order
     *
     * @param int $orderId Order id
     * @return float Total amount paid
     */
    public function getSumPaymentsForOrder($orderId)
    {
        $sql = "SELECT SUM(d.amount) as total";
        $sql .= " FROM ".$this->db->prefix()."order_payment_det as d";
        $sql .= " INNER JOIN ".$this->db->prefix().$this->table_element." as p ON p.rowid = d.fk_payment";
        $sql .= " WHERE d.fk_commande = ".((int) $orderId);
        $sql .= " AND p.status = ".((int) self::STATUS_VALIDATED);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return (float) ($obj->total ?? 0);
        }
        return 0;
    }

    /**
     * Get all payments for an order
     *
     * @param int $orderId Order id
     * @return array Array of payments
     */
    public function getPaymentsForOrder($orderId)
    {
        $payments = array();

        $sql = "SELECT p.rowid, p.ref, p.amount as payment_amount, p.datep, p.status,";
        $sql .= " p.fk_mode_reglement, p.num_payment, p.cancel_reason, p.is_returned_check,";
        $sql .= " d.amount as line_amount";
        $sql .= " FROM ".$this->db->prefix()."order_payment_det as d";
        $sql .= " INNER JOIN ".$this->db->prefix().$this->table_element." as p ON p.rowid = d.fk_payment";
        $sql .= " WHERE d.fk_commande = ".((int) $orderId);
        $sql .= " ORDER BY p.datep DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $payments[] = $obj;
            }
            $this->db->free($resql);
        }

        return $payments;
    }

    /**
     * Get total amount paid by a thirdparty
     *
     * @param int $socid Thirdparty id
     * @return float Total amount paid
     */
    public function getSumPaymentsForThirdparty($socid)
    {
        $sql = "SELECT SUM(amount) as total";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE fk_soc = ".((int) $socid);
        $sql .= " AND status = ".((int) self::STATUS_VALIDATED);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return (float) ($obj->total ?? 0);
        }
        return 0;
    }

    /**
     * Get returned checks count for a thirdparty
     *
     * @param int  $socid              Thirdparty id
     * @param bool $onlyActiveSanction If true, only count checks where sanction is NOT lifted
     * @return int Number of returned checks
     */
    public function getReturnedChecksCount($socid, $onlyActiveSanction = false)
    {
        $sql = "SELECT COUNT(*) as nb";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE fk_soc = ".((int) $socid);
        $sql .= " AND is_returned_check = 1";
        if ($onlyActiveSanction) {
            $sql .= " AND (sanction_lifted = 0 OR sanction_lifted IS NULL)";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return (int) ($obj->nb ?? 0);
        }
        return 0;
    }

    /**
     * Get returned checks total for a thirdparty
     *
     * @param int  $socid              Thirdparty id
     * @param bool $onlyActiveSanction If true, only count checks where sanction is NOT lifted
     * @return float Total amount of returned checks
     */
    public function getReturnedChecksTotal($socid, $onlyActiveSanction = false)
    {
        $sql = "SELECT SUM(amount) as total";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE fk_soc = ".((int) $socid);
        $sql .= " AND is_returned_check = 1";
        if ($onlyActiveSanction) {
            $sql .= " AND (sanction_lifted = 0 OR sanction_lifted IS NULL)";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            return (float) ($obj->total ?? 0);
        }
        return 0;
    }

    /**
     * Get returned checks for a thirdparty
     *
     * @param int $socid Thirdparty id
     * @return array Array of returned checks
     */
    public function getReturnedChecks($socid)
    {
        $checks = array();

        $sql = "SELECT rowid, ref, amount, datep, date_cancel, cancel_reason, num_payment";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE fk_soc = ".((int) $socid);
        $sql .= " AND is_returned_check = 1";
        $sql .= " ORDER BY date_cancel DESC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $checks[] = $obj;
            }
            $this->db->free($resql);
        }

        return $checks;
    }

    /**
     * Generate next reference
     *
     * @return string Next reference
     */
    public function getNextNumRef()
    {
        global $conf;

        $prefix = 'OP';
        $date = dol_print_date(dol_now(), '%y%m');

        // Get last ref for this month
        $sql = "SELECT MAX(CAST(SUBSTRING(ref, ".(strlen($prefix) + 5).") AS UNSIGNED)) as maxnum";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE ref LIKE '".$this->db->escape($prefix.$date)."%'";
        $sql .= " AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $num = ($obj->maxnum ?? 0) + 1;
        } else {
            $num = 1;
        }

        return $prefix.$date.sprintf('%04d', $num);
    }

    /**
     * Return label of status
     *
     * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * @return string Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode, $this->is_returned_check);
    }

    /**
     * Return label of a status
     *
     * @param int $status Status
     * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * @param int $is_returned_check Is returned check
     * @return string Label of status
     */
    public function LibStatut($status, $mode = 0, $is_returned_check = 0)
    {
        global $langs;

        $langs->load("orderpayment@orderpayment");

        $statusType = '';
        $labelStatus = '';
        $labelStatusShort = '';

        if ($status == self::STATUS_VALIDATED) {
            $labelStatus = $langs->transnoentities('OrderPaymentValidated');
            $labelStatusShort = $langs->transnoentities('OrderPaymentValidated');
            $statusType = 'status4';
        } elseif ($status == self::STATUS_CANCELLED) {
            if ($is_returned_check) {
                $labelStatus = $langs->transnoentities('ReturnedCheck');
                $labelStatusShort = $langs->transnoentities('ReturnedCheck');
                $statusType = 'status8';
            } else {
                $labelStatus = $langs->transnoentities('OrderPaymentCancelled');
                $labelStatusShort = $langs->transnoentities('OrderPaymentCancelled');
                $statusType = 'status9';
            }
        }

        return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
    }

    /**
     * Return URL link to object card
     *
     * @param int $withpicto Include picto
     * @param string $option Options
     * @param int $notooltip No tooltip
     * @param string $morecss More css
     * @param int $save_lastsearch_value Save last search value
     * @return string HTML link
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs;

        $result = '';
        $label = img_picto('', 'payment').' <u>'.$langs->trans("OrderPaymentCard").'</u><br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $url = dol_buildpath('/orderpayment/card.php', 1).'?id='.$this->id;

        $linkstart = '<a href="'.$url.'"';
        if (empty($notooltip)) {
            $linkstart .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'" title="'.dol_escape_htmltag($label, 1).'"';
        } else {
            $linkstart .= ' class="'.($morecss ? $morecss : '').'"';
        }
        $linkstart .= '>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object($label, 'payment', 'class="pictofixedwidth"');
        }
        $result .= $this->ref;
        $result .= $linkend;

        return $result;
    }
}
