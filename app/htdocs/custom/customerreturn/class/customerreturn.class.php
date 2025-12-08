<?php
/**
 * Class for customer returns (Bons de retour)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobjectline.class.php';

class CustomerReturn extends CommonObject
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'customerreturn';

    /**
     * @var string Name of table without prefix
     */
    public $table_element = 'customerreturn';

    /**
     * @var string Name of line table without prefix
     */
    public $table_element_line = 'customerreturndet';

    /**
     * @var string Field with ID of parent key if this object has a parent
     */
    public $fk_element = 'fk_customerreturn';

    /**
     * @var int Does object support extrafields?
     */
    public $isextrafieldmanaged = 1;

    /**
     * @var string picto
     */
    public $picto = 'dollyrevert';

    const STATUS_DRAFT = 0;
    const STATUS_VALIDATED = 1;
    const STATUS_CLOSED = 2;
    const STATUS_CANCELED = 9;

    /**
     * @var string Reference
     */
    public $ref;

    /**
     * @var int Entity
     */
    public $entity;

    /**
     * @var int Customer ID
     */
    public $fk_soc;
    public $socid;

    /**
     * @var int Expedition ID (optional)
     */
    public $fk_expedition;

    /**
     * @var int Deliveryman ID (user who brought the return)
     */
    public $fk_deliveryman;

    /**
     * @var int Creation date
     */
    public $datec;

    /**
     * @var int Return date
     */
    public $date_return;

    /**
     * @var int Validation date
     */
    public $date_valid;

    /**
     * @var int Author user ID
     */
    public $fk_user_author;

    /**
     * @var int Validation user ID
     */
    public $fk_user_valid;

    /**
     * @var float Total HT
     */
    public $total_ht;

    /**
     * @var float Total VAT
     */
    public $total_tva;

    /**
     * @var float Total TTC
     */
    public $total_ttc;

    /**
     * @var string Private note
     */
    public $note_private;

    /**
     * @var string Public note
     */
    public $note_public;

    /**
     * @var int Status
     */
    public $fk_statut;
    public $statut;
    public $status;

    /**
     * @var int Credit note ID generated from this return
     */
    public $fk_facture_avoir;

    /**
     * @var string PDF model
     */
    public $model_pdf;

    /**
     * @var array Lines
     */
    public $lines = array();

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
     * Create customer return
     *
     * @param User $user User creating
     * @param int $notrigger Disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf;

        $error = 0;

        // Clean parameters
        $this->ref = trim($this->ref);
        $this->fk_soc = (int) $this->fk_soc;
        $this->fk_expedition = (int) $this->fk_expedition;
        $this->fk_deliveryman = (int) $this->fk_deliveryman;

        // Get next ref if not set
        if (empty($this->ref)) {
            $this->ref = $this->getNextNumRef();
        }

        $this->db->begin();

        $sql = "INSERT INTO ".$this->db->prefix()."customerreturn (";
        $sql .= "ref";
        $sql .= ", entity";
        $sql .= ", fk_soc";
        $sql .= ", fk_expedition";
        $sql .= ", fk_deliveryman";
        $sql .= ", datec";
        $sql .= ", date_return";
        $sql .= ", fk_user_author";
        $sql .= ", fk_statut";
        $sql .= ", note_private";
        $sql .= ", note_public";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."'";
        $sql .= ", ".((int) $conf->entity);
        $sql .= ", ".((int) $this->fk_soc);
        $sql .= ", ".($this->fk_expedition > 0 ? ((int) $this->fk_expedition) : "NULL");
        $sql .= ", ".($this->fk_deliveryman > 0 ? ((int) $this->fk_deliveryman) : "NULL");
        $sql .= ", '".$this->db->idate(dol_now())."'";
        $sql .= ", ".($this->date_return ? "'".$this->db->idate($this->date_return)."'" : "NULL");
        $sql .= ", ".((int) $user->id);
        $sql .= ", 0";
        $sql .= ", ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "NULL");
        $sql .= ", ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "NULL");
        $sql .= ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error: ".$this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id($this->db->prefix()."customerreturn");

            if (!$notrigger) {
                // Call triggers
                $result = $this->call_trigger('CUSTOMERRETURN_CREATE', $user);
                if ($result < 0) {
                    $error++;
                }
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
     * Fetch customer return
     *
     * @param int $id ID
     * @param string $ref Reference
     * @return int <0 if KO, >0 if OK
     */
    public function fetch($id, $ref = '')
    {
        $sql = "SELECT c.rowid, c.ref, c.entity, c.fk_soc, c.fk_expedition, c.fk_deliveryman,";
        $sql .= " c.datec, c.date_return, c.date_valid, c.fk_user_author, c.fk_user_valid,";
        $sql .= " c.total_ht, c.total_tva, c.total_ttc, c.note_private, c.note_public,";
        $sql .= " c.fk_statut, c.fk_facture_avoir, c.model_pdf";
        $sql .= " FROM ".$this->db->prefix()."customerreturn as c";
        if ($id) {
            $sql .= " WHERE c.rowid = ".((int) $id);
        } else {
            $sql .= " WHERE c.ref = '".$this->db->escape($ref)."'";
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->entity = $obj->entity;
                $this->fk_soc = $obj->fk_soc;
                $this->socid = $obj->fk_soc;
                $this->fk_expedition = $obj->fk_expedition;
                $this->fk_deliveryman = $obj->fk_deliveryman;
                $this->datec = $this->db->jdate($obj->datec);
                $this->date_return = $this->db->jdate($obj->date_return);
                $this->date_valid = $this->db->jdate($obj->date_valid);
                $this->fk_user_author = $obj->fk_user_author;
                $this->fk_user_valid = $obj->fk_user_valid;
                $this->total_ht = $obj->total_ht;
                $this->total_tva = $obj->total_tva;
                $this->total_ttc = $obj->total_ttc;
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
                $this->fk_statut = $obj->fk_statut;
                $this->statut = $obj->fk_statut;
                $this->status = $obj->fk_statut;
                $this->fk_facture_avoir = $obj->fk_facture_avoir;
                $this->model_pdf = $obj->model_pdf;

                // Load lines
                $this->fetch_lines();

                $this->db->free($resql);
                return 1;
            } else {
                return 0;
            }
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Fetch lines of return
     *
     * @return int <0 if KO, >0 if OK
     */
    public function fetch_lines()
    {
        $this->lines = array();

        $sql = "SELECT d.rowid, d.fk_customerreturn, d.fk_product, d.fk_entrepot,";
        $sql .= " d.label, d.description, d.qty, d.subprice,";
        $sql .= " d.total_ht, d.total_tva, d.total_ttc, d.tva_tx,";
        $sql .= " d.batch, d.rang, d.reason,";
        $sql .= " p.ref as product_ref, p.label as product_label";
        $sql .= " FROM ".$this->db->prefix()."customerreturndet as d";
        $sql .= " LEFT JOIN ".$this->db->prefix()."product as p ON p.rowid = d.fk_product";
        $sql .= " WHERE d.fk_customerreturn = ".((int) $this->id);
        $sql .= " ORDER BY d.rang ASC, d.rowid ASC";

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $line = new CustomerReturnLine($this->db);
                $line->id = $obj->rowid;
                $line->rowid = $obj->rowid;
                $line->fk_customerreturn = $obj->fk_customerreturn;
                $line->fk_product = $obj->fk_product;
                $line->fk_entrepot = $obj->fk_entrepot;
                $line->label = $obj->label;
                $line->description = $obj->description;
                $line->qty = $obj->qty;
                $line->subprice = $obj->subprice;
                $line->total_ht = $obj->total_ht;
                $line->total_tva = $obj->total_tva;
                $line->total_ttc = $obj->total_ttc;
                $line->tva_tx = $obj->tva_tx;
                $line->batch = $obj->batch;
                $line->rang = $obj->rang;
                $line->reason = $obj->reason;
                $line->product_ref = $obj->product_ref;
                $line->product_label = $obj->product_label;

                $this->lines[] = $line;
            }
            $this->db->free($resql);
            return count($this->lines);
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update customer return
     *
     * @param User $user User modifying
     * @param int $notrigger Disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function update($user, $notrigger = 0)
    {
        $error = 0;

        $this->db->begin();

        $sql = "UPDATE ".$this->db->prefix()."customerreturn SET";
        $sql .= " fk_soc = ".((int) $this->fk_soc);
        $sql .= ", fk_expedition = ".($this->fk_expedition > 0 ? ((int) $this->fk_expedition) : "NULL");
        $sql .= ", fk_deliveryman = ".($this->fk_deliveryman > 0 ? ((int) $this->fk_deliveryman) : "NULL");
        $sql .= ", date_return = ".($this->date_return ? "'".$this->db->idate($this->date_return)."'" : "NULL");
        $sql .= ", note_private = ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : "NULL");
        $sql .= ", note_public = ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : "NULL");
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = "Error: ".$this->db->lasterror();
        }

        if (!$error && !$notrigger) {
            $result = $this->call_trigger('CUSTOMERRETURN_MODIFY', $user);
            if ($result < 0) {
                $error++;
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Delete customer return
     *
     * @param User $user User deleting
     * @param int $notrigger Disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function delete($user, $notrigger = 0)
    {
        $error = 0;

        // Cannot delete if validated
        if ($this->statut != self::STATUS_DRAFT) {
            $this->error = 'ErrorCannotDeleteValidatedReturn';
            return -1;
        }

        $this->db->begin();

        if (!$error && !$notrigger) {
            $result = $this->call_trigger('CUSTOMERRETURN_DELETE', $user);
            if ($result < 0) {
                $error++;
            }
        }

        // Delete lines first
        if (!$error) {
            $sql = "DELETE FROM ".$this->db->prefix()."customerreturndet";
            $sql .= " WHERE fk_customerreturn = ".((int) $this->id);
            if (!$this->db->query($sql)) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }
        }

        // Delete main record
        if (!$error) {
            $sql = "DELETE FROM ".$this->db->prefix()."customerreturn";
            $sql .= " WHERE rowid = ".((int) $this->id);
            if (!$this->db->query($sql)) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Add a line to customer return
     *
     * @param int $fk_product Product ID
     * @param float $qty Quantity
     * @param float $subprice Unit price HT
     * @param float $tva_tx VAT rate
     * @param int $fk_entrepot Warehouse ID
     * @param string $label Label
     * @param string $description Description
     * @param string $reason Reason for return
     * @param string $batch Batch number
     * @return int <0 if KO, >0 if OK (line ID)
     */
    public function addLine($fk_product, $qty, $subprice = 0, $tva_tx = 0, $fk_entrepot = 0, $label = '', $description = '', $reason = '', $batch = '')
    {
        $error = 0;

        // Calculate totals
        $total_ht = $qty * $subprice;
        $total_tva = $total_ht * $tva_tx / 100;
        $total_ttc = $total_ht + $total_tva;

        // Get max rang
        $sql = "SELECT MAX(rang) as maxrang FROM ".$this->db->prefix()."customerreturndet WHERE fk_customerreturn = ".((int) $this->id);
        $resql = $this->db->query($sql);
        $rang = 1;
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $rang = ($obj->maxrang ? $obj->maxrang + 1 : 1);
        }

        $sql = "INSERT INTO ".$this->db->prefix()."customerreturndet (";
        $sql .= "fk_customerreturn";
        $sql .= ", fk_product";
        $sql .= ", fk_entrepot";
        $sql .= ", label";
        $sql .= ", description";
        $sql .= ", qty";
        $sql .= ", subprice";
        $sql .= ", total_ht";
        $sql .= ", total_tva";
        $sql .= ", total_ttc";
        $sql .= ", tva_tx";
        $sql .= ", batch";
        $sql .= ", rang";
        $sql .= ", reason";
        $sql .= ") VALUES (";
        $sql .= ((int) $this->id);
        $sql .= ", ".($fk_product > 0 ? ((int) $fk_product) : "NULL");
        $sql .= ", ".($fk_entrepot > 0 ? ((int) $fk_entrepot) : "NULL");
        $sql .= ", ".($label ? "'".$this->db->escape($label)."'" : "NULL");
        $sql .= ", ".($description ? "'".$this->db->escape($description)."'" : "NULL");
        $sql .= ", ".((float) $qty);
        $sql .= ", ".((float) $subprice);
        $sql .= ", ".((float) $total_ht);
        $sql .= ", ".((float) $total_tva);
        $sql .= ", ".((float) $total_ttc);
        $sql .= ", ".((float) $tva_tx);
        $sql .= ", ".($batch ? "'".$this->db->escape($batch)."'" : "NULL");
        $sql .= ", ".((int) $rang);
        $sql .= ", ".($reason ? "'".$this->db->escape($reason)."'" : "NULL");
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $lineid = $this->db->last_insert_id($this->db->prefix()."customerreturndet");
            $this->updateTotals();
            return $lineid;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update a line
     *
     * @param int $lineid Line ID
     * @param float $qty Quantity
     * @param float $subprice Unit price HT
     * @param float $tva_tx VAT rate
     * @param int $fk_entrepot Warehouse ID
     * @param string $label Label
     * @param string $description Description
     * @param string $reason Reason
     * @param string $batch Batch
     * @return int <0 if KO, >0 if OK
     */
    public function updateLine($lineid, $qty, $subprice = 0, $tva_tx = 0, $fk_entrepot = 0, $label = '', $description = '', $reason = '', $batch = '')
    {
        // Calculate totals
        $total_ht = $qty * $subprice;
        $total_tva = $total_ht * $tva_tx / 100;
        $total_ttc = $total_ht + $total_tva;

        $sql = "UPDATE ".$this->db->prefix()."customerreturndet SET";
        $sql .= " qty = ".((float) $qty);
        $sql .= ", subprice = ".((float) $subprice);
        $sql .= ", total_ht = ".((float) $total_ht);
        $sql .= ", total_tva = ".((float) $total_tva);
        $sql .= ", total_ttc = ".((float) $total_ttc);
        $sql .= ", tva_tx = ".((float) $tva_tx);
        $sql .= ", fk_entrepot = ".($fk_entrepot > 0 ? ((int) $fk_entrepot) : "NULL");
        $sql .= ", label = ".($label ? "'".$this->db->escape($label)."'" : "NULL");
        $sql .= ", description = ".($description ? "'".$this->db->escape($description)."'" : "NULL");
        $sql .= ", reason = ".($reason ? "'".$this->db->escape($reason)."'" : "NULL");
        $sql .= ", batch = ".($batch ? "'".$this->db->escape($batch)."'" : "NULL");
        $sql .= " WHERE rowid = ".((int) $lineid);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->updateTotals();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Delete a line
     *
     * @param int $lineid Line ID
     * @return int <0 if KO, >0 if OK
     */
    public function deleteLine($lineid)
    {
        $sql = "DELETE FROM ".$this->db->prefix()."customerreturndet";
        $sql .= " WHERE rowid = ".((int) $lineid);
        $sql .= " AND fk_customerreturn = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->updateTotals();
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update totals
     *
     * @return int <0 if KO, >0 if OK
     */
    public function updateTotals()
    {
        $sql = "SELECT SUM(total_ht) as total_ht, SUM(total_tva) as total_tva, SUM(total_ttc) as total_ttc";
        $sql .= " FROM ".$this->db->prefix()."customerreturndet";
        $sql .= " WHERE fk_customerreturn = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $this->total_ht = $obj->total_ht ? $obj->total_ht : 0;
            $this->total_tva = $obj->total_tva ? $obj->total_tva : 0;
            $this->total_ttc = $obj->total_ttc ? $obj->total_ttc : 0;

            $sql = "UPDATE ".$this->db->prefix()."customerreturn SET";
            $sql .= " total_ht = ".((float) $this->total_ht);
            $sql .= ", total_tva = ".((float) $this->total_tva);
            $sql .= ", total_ttc = ".((float) $this->total_ttc);
            $sql .= " WHERE rowid = ".((int) $this->id);

            return $this->db->query($sql) ? 1 : -1;
        }
        return -1;
    }

    /**
     * Validate customer return
     * This will:
     * 1. Increase stock for each line
     * 2. Create a credit note (avoir) for the customer
     *
     * @param User $user User validating
     * @param int $notrigger Disable triggers
     * @return int <0 if KO, >0 if OK
     */
    public function validate($user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;

        if ($this->statut != self::STATUS_DRAFT) {
            $this->error = 'ErrorReturnAlreadyValidated';
            return -1;
        }

        // Check we have lines
        if (empty($this->lines) || count($this->lines) == 0) {
            $this->fetch_lines();
        }
        if (empty($this->lines) || count($this->lines) == 0) {
            $this->error = 'ErrorReturnHasNoLines';
            return -1;
        }

        $this->db->begin();

        // 1. Increase stock for each line
        if (!$error) {
            $result = $this->increaseStock($user);
            if ($result < 0) {
                $error++;
            }
        }

        // 2. Create credit note if enabled
        if (!$error && getDolGlobalString('CUSTOMERRETURN_AUTO_CREDIT_NOTE')) {
            $result = $this->createCreditNote($user);
            if ($result < 0) {
                $error++;
            }
        }

        // Update status
        if (!$error) {
            $sql = "UPDATE ".$this->db->prefix()."customerreturn SET";
            $sql .= " fk_statut = ".self::STATUS_VALIDATED;
            $sql .= ", date_valid = '".$this->db->idate(dol_now())."'";
            $sql .= ", fk_user_valid = ".((int) $user->id);
            if (!empty($this->fk_facture_avoir)) {
                $sql .= ", fk_facture_avoir = ".((int) $this->fk_facture_avoir);
            }
            $sql .= " WHERE rowid = ".((int) $this->id);

            if (!$this->db->query($sql)) {
                $error++;
                $this->errors[] = $this->db->lasterror();
            }
        }

        if (!$error && !$notrigger) {
            $result = $this->call_trigger('CUSTOMERRETURN_VALIDATE', $user);
            if ($result < 0) {
                $error++;
            }
        }

        if (!$error) {
            $this->statut = self::STATUS_VALIDATED;
            $this->status = self::STATUS_VALIDATED;
            $this->fk_statut = self::STATUS_VALIDATED;
            $this->date_valid = dol_now();
            $this->fk_user_valid = $user->id;

            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Increase stock for all lines
     *
     * @param User $user User
     * @return int <0 if KO, >0 if OK
     */
    private function increaseStock($user)
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';

        $error = 0;

        foreach ($this->lines as $line) {
            if ($line->fk_product > 0 && $line->qty > 0) {
                // Get default warehouse if not set on line
                $warehouse_id = $line->fk_entrepot;
                if (empty($warehouse_id)) {
                    $warehouse_id = getDolGlobalInt('MAIN_DEFAULT_WAREHOUSE');
                }

                if (empty($warehouse_id)) {
                    // Get first warehouse
                    $sql = "SELECT rowid FROM ".$this->db->prefix()."entrepot WHERE entity = ".((int) $conf->entity)." AND statut = 1 LIMIT 1";
                    $resql = $this->db->query($sql);
                    if ($resql && $this->db->num_rows($resql) > 0) {
                        $obj = $this->db->fetch_object($resql);
                        $warehouse_id = $obj->rowid;
                    }
                }

                if (empty($warehouse_id)) {
                    $this->error = $langs->trans('ErrorNoWarehouseForStockIncrease');
                    $error++;
                    break;
                }

                $mouvementstock = new MouvementStock($this->db);
                $mouvementstock->origin_type = 'customerreturn';
                $mouvementstock->origin_id = $this->id;

                $result = $mouvementstock->reception(
                    $user,
                    $line->fk_product,
                    $warehouse_id,
                    $line->qty,
                    $line->subprice,
                    $langs->trans('CustomerReturnStockMovement', $this->ref),
                    '',
                    '',
                    $line->batch
                );

                if ($result < 0) {
                    $this->error = $mouvementstock->error;
                    $this->errors = $mouvementstock->errors;
                    $error++;
                    break;
                }
            }
        }

        return $error ? -1 : 1;
    }

    /**
     * Create credit note (avoir) from return
     *
     * @param User $user User
     * @return int <0 if KO, >0 if OK (credit note ID)
     */
    private function createCreditNote($user)
    {
        global $conf, $langs;

        require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

        $error = 0;

        // Create credit note
        $facture = new Facture($this->db);
        $facture->type = Facture::TYPE_CREDIT_NOTE;
        $facture->socid = $this->fk_soc;
        $facture->date = dol_now();
        $facture->note_private = $langs->trans('CreditNoteFromReturn', $this->ref);
        $facture->note_public = '';
        $facture->cond_reglement_id = 0;
        $facture->mode_reglement_id = 0;

        $result = $facture->create($user);
        if ($result <= 0) {
            $this->error = $facture->error;
            $this->errors = $facture->errors;
            return -1;
        }

        // Add lines
        foreach ($this->lines as $line) {
            $result = $facture->addline(
                $line->description ? $line->description : $line->label,
                $line->subprice,
                $line->qty,
                $line->tva_tx,
                0, // localtax1
                0, // localtax2
                $line->fk_product,
                0, // remise
                '', // date_start
                '', // date_end
                0, // ventil
                0, // info_bits
                0, // fk_remise_except
                'HT',
                0, // total_ht
                Facture::TYPE_CREDIT_NOTE,
                -1, // rang
                0, // special_code
                '', // origin
                0, // origin_id
                0, // fk_parent_line
                null // fk_fournprice
            );

            if ($result < 0) {
                $this->error = $facture->error;
                $this->errors = $facture->errors;
                return -1;
            }
        }

        // Validate credit note
        $result = $facture->validate($user);
        if ($result < 0) {
            $this->error = $facture->error;
            $this->errors = $facture->errors;
            return -1;
        }

        $this->fk_facture_avoir = $facture->id;

        return $facture->id;
    }

    /**
     * Set to draft
     *
     * @param User $user User
     * @return int <0 if KO, >0 if OK
     */
    public function setDraft($user)
    {
        // Cannot set to draft if already validated with stock movements
        if ($this->statut >= self::STATUS_VALIDATED) {
            $this->error = 'ErrorCannotSetValidatedReturnToDraft';
            return -1;
        }

        $sql = "UPDATE ".$this->db->prefix()."customerreturn SET";
        $sql .= " fk_statut = ".self::STATUS_DRAFT;
        $sql .= " WHERE rowid = ".((int) $this->id);

        if ($this->db->query($sql)) {
            $this->statut = self::STATUS_DRAFT;
            $this->status = self::STATUS_DRAFT;
            $this->fk_statut = self::STATUS_DRAFT;
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Get next reference number
     *
     * @return string Next reference
     */
    public function getNextNumRef()
    {
        global $conf;

        $mask = 'RET{yy}{mm}-{0000}';

        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        return get_next_value($this->db, $mask, 'customerreturn', 'ref', '', null, dol_now());
    }

    /**
     * Return label of status
     *
     * @param int $mode 0=long label, 1=short label, 2=Picto+short label, 3=Picto, 4=Picto+long label, 5=Short label+Picto, 6=Long label+Picto
     * @return string Label
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     * Return label of status
     *
     * @param int $status Status
     * @param int $mode Mode
     * @return string Label
     */
    public function LibStatut($status, $mode = 0)
    {
        global $langs;

        $langs->load('customerreturn@customerreturn');

        $statusLabel = array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_VALIDATED => 'Validated',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELED => 'Canceled'
        );

        $statusLabelShort = array(
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_VALIDATED => 'Validated',
            self::STATUS_CLOSED => 'Closed',
            self::STATUS_CANCELED => 'Canceled'
        );

        $statusType = array(
            self::STATUS_DRAFT => 'status0',
            self::STATUS_VALIDATED => 'status4',
            self::STATUS_CLOSED => 'status6',
            self::STATUS_CANCELED => 'status9'
        );

        $statusCode = isset($statusLabel[$status]) ? $statusLabel[$status] : '';
        $statusCodeShort = isset($statusLabelShort[$status]) ? $statusLabelShort[$status] : '';
        $statusTypeCode = isset($statusType[$status]) ? $statusType[$status] : 'status0';

        return dolGetStatus($langs->trans($statusCode), $langs->trans($statusCodeShort), '', $statusTypeCode, $mode);
    }

    /**
     * Get URL to use on thirdparty card
     *
     * @param int $withpicto Picto
     * @param string $option Options
     * @param int $notooltip No tooltip
     * @param string $morecss More CSS
     * @param int $save_lastsearch_value Save last search
     * @return string URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs;

        $result = '';

        $label = img_picto('', $this->picto).' <u>'.$langs->trans("CustomerReturn").'</u>';
        $label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

        $url = dol_buildpath('/customerreturn/card.php', 1).'?id='.$this->id;

        $linkclose = '';
        if (empty($notooltip)) {
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        } else {
            $linkclose .= ($morecss ? ' class="'.$morecss.'"' : '');
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object(($notooltip ? '' : $label), $this->picto, ($notooltip ? '' : 'class="paddingright classfortooltip"'));
        }
        if ($withpicto != 2) {
            $result .= $this->ref;
        }
        $result .= $linkend;

        return $result;
    }

    /**
     * Info of object
     *
     * @param int $id Object ID
     * @return void
     */
    public function info($id)
    {
        $sql = "SELECT c.datec, c.date_valid, c.fk_user_author, c.fk_user_valid, c.tms";
        $sql .= " FROM ".$this->db->prefix()."customerreturn as c";
        $sql .= " WHERE c.rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_validation = $this->db->jdate($obj->date_valid);
                $this->user_creation_id = $obj->fk_user_author;
                $this->user_validation_id = $obj->fk_user_valid;
                $this->date_modification = $this->db->jdate($obj->tms);
            }
            $this->db->free($resql);
        }
    }

    /**
     * Generate PDF document
     *
     * @param string $modele Model to use
     * @param Translate $outputlangs Language object
     * @param int $hidedetails Hide details
     * @param int $hidedesc Hide description
     * @param int $hideref Hide ref
     * @return int 1 if OK, <=0 if KO
     */
    public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
    {
        global $conf, $langs;

        $result = 0;
        $includePath = '/customerreturn/core/modules/customerreturn/doc/pdf_'.$modele.'.modules.php';

        if (file_exists(dol_buildpath($includePath))) {
            dol_include_once($includePath);

            $classname = 'pdf_'.$modele;
            if (class_exists($classname)) {
                $obj = new $classname($this->db);

                // We need to fetch lines if not already done
                if (empty($this->lines)) {
                    $this->fetch_lines();
                }

                $result = $obj->write_file($this, $outputlangs, '', $hidedetails, $hidedesc, $hideref);
                if ($result > 0) {
                    // Update model_pdf field
                    $this->model_pdf = $modele;
                    $sql = "UPDATE ".$this->db->prefix()."customerreturn SET model_pdf = '".$this->db->escape($modele)."' WHERE rowid = ".((int) $this->id);
                    $this->db->query($sql);
                }
            } else {
                $this->error = $langs->trans("ErrorClassNotFound", $classname);
                return -1;
            }
        } else {
            $this->error = $langs->trans("ErrorFileNotFound", $includePath);
            return -1;
        }

        return $result;
    }
}

/**
 * Class CustomerReturnLine
 */
class CustomerReturnLine extends CommonObjectLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'customerreturndet';

    /**
     * @var string Name of table without prefix
     */
    public $table_element = 'customerreturndet';

    public $id;
    public $rowid;
    public $fk_customerreturn;
    public $fk_product;
    public $fk_entrepot;
    public $label;
    public $description;
    public $qty;
    public $subprice;
    public $total_ht;
    public $total_tva;
    public $total_ttc;
    public $tva_tx;
    public $batch;
    public $rang;
    public $reason;
    public $product_ref;
    public $product_label;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }
}
