<?php
/* Copyright (C) 2024  Codex */

/**
 * Helper to manage arrival costs allocation on supplier orders
 */
class ArrivalCost
{
    /** @var DoliDB */
    private $db;

    /** @var string */
    public $error = '';

    /** @var array */
    public $errors = array();

    /** @var string */
    public $last_ref = '';

    /** @var array<int,float> */
    private static $productCostCache = array();

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Ensure extrafields exist on supplier order lines
     *
     * @return void
     */
    public function ensureLineExtraFields()
    {
        global $conf, $langs;

        $langs->load('arrivalcost@arrivalcost');

        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);
        $current = $extrafields->fetch_name_optionals_label('commande_fournisseurdet');

        $listVisibility = '0'; // hide from forms/lists, only stored

        if (!isset($current['arrivalcost_unit'])) {
            $extrafields->addExtraField(
                'arrivalcost_unit',
                $langs->trans('ArrivalCostExtraUnit'),
                'price',
                1,
                '',
                'commande_fournisseurdet',
                0,
                0,
                '0',
                '',
                1,
                '',
                $listVisibility,
                '',
                '',
                $conf->entity
            );
        } else {
            $extrafields->updateExtraField(
                'arrivalcost_unit',
                $langs->trans('ArrivalCostExtraUnit'),
                'price',
                1,
                '',
                'commande_fournisseurdet',
                0,
                0,
                '0',
                '',
                1,
                '',
                $listVisibility,
                '',
                '',
                $conf->entity,
                '',
                '1',
                0
            );
        }

        if (!isset($current['arrivalcost_total'])) {
            $extrafields->addExtraField(
                'arrivalcost_total',
                $langs->trans('ArrivalCostExtraTotal'),
                'price',
                2,
                '',
                'commande_fournisseurdet',
                0,
                0,
                '0',
                '',
                1,
                '',
                $listVisibility,
                '',
                '',
                $conf->entity
            );
        } else {
            $extrafields->updateExtraField(
                'arrivalcost_total',
                $langs->trans('ArrivalCostExtraTotal'),
                'price',
                2,
                '',
                'commande_fournisseurdet',
                0,
                0,
                '0',
                '',
                1,
                '',
                $listVisibility,
                '',
                '',
                $conf->entity,
                '',
                '1',
                0
            );
        }
    }

    /**
     * Ensure schema upgrades (add missing columns)
     *
     * @return void
     */
    public function ensureSchemaUpgrade()
    {
        // Add fk_supplier_invoice if missing
        $check = "SHOW COLUMNS FROM ".$this->db->prefix()."arrivalcost LIKE 'fk_supplier_invoice'";
        $res = $this->db->query($check);
        if ($res && $this->db->num_rows($res) == 0) {
            $alter = "ALTER TABLE ".$this->db->prefix()."arrivalcost ADD fk_supplier_invoice INTEGER NULL";
            $this->db->query($alter);
        }
    }

    /**
     * Create an arrival cost entry and allocate it on lines
     *
     * @param User  $user
     * @param array $data
     * @return int rowid
     */
    public function create($user, array $data)
    {
        global $conf, $langs;

        $this->error = '';
        $this->errors = array();

        $amount = price2num(isset($data['amount']) ? $data['amount'] : 0, 'MT');
        $mode = !empty($data['mode']) && $data['mode'] === 'qty' ? 'qty' : 'amount';
        $label = trim((string) (isset($data['label']) ? $data['label'] : ''));
        $note = trim((string) (isset($data['note']) ? $data['note'] : ''));

        $orderIds = array();
        if (!empty($data['orders']) && is_array($data['orders'])) {
            foreach ($data['orders'] as $orderId) {
                $id = (int) $orderId;
                if ($id > 0) {
                    $orderIds[] = $id;
                }
            }
        }
        $orderIds = array_unique($orderIds);
        $fkSupplier = (int) (isset($data['fk_supplier']) ? $data['fk_supplier'] : 0);
        $createInvoice = !empty($data['create_invoice']) && $fkSupplier > 0;

        if ($amount <= 0) {
            $this->errors[] = $langs->trans('ArrivalCostErrorAmount');
        }
        if (empty($orderIds)) {
            $this->errors[] = $langs->trans('ArrivalCostErrorOrders');
        }
        if (empty($label)) {
            $this->errors[] = $langs->trans('ArrivalCostErrorLabel');
        }
        if (!empty($this->errors)) {
            return -1;
        }

        $lines = $this->fetchOrderLines($orderIds);
        if (empty($lines)) {
            $this->errors[] = $langs->trans('ArrivalCostErrorNoLines');
            return -1;
        }

        $totalBase = 0;
        foreach ($lines as &$line) {
            $line['base'] = $mode === 'qty' ? (float) $line['qty'] : (float) $line['total_ht'];
            if ($line['base'] < 0) {
                $line['base'] = 0;
            }
            $totalBase += $line['base'];
        }
        unset($line);

        if ($totalBase <= 0 && $mode === 'amount') {
            $mode = 'qty';
            $totalBase = 0;
            foreach ($lines as &$line) {
                $line['base'] = (float) $line['qty'];
                if ($line['base'] < 0) {
                    $line['base'] = 0;
                }
                $totalBase += $line['base'];
            }
            unset($line);
        }

        if ($totalBase <= 0) {
            $this->errors[] = $langs->trans('ArrivalCostErrorNoBase');
            return -1;
        }

        $now = dol_now();
        $ref = $this->buildRef();
        $this->last_ref = $ref;

        $this->db->begin();

        $sql = "INSERT INTO ".$this->db->prefix()."arrivalcost(ref, label, amount_ht, mode, note, entity, datec, fk_user_creat)";
        $sql .= " VALUES(";
        $sql .= " '".$this->db->escape($ref)."'";
        $sql .= ", '".$this->db->escape($label)."'";
        $sql .= ", ".((float) $amount);
        $sql .= ", '".$this->db->escape($mode)."'";
        $sql .= ", ".(!empty($note) ? "'".$this->db->escape($note)."'" : "null");
        $sql .= ", ".((int) $conf->entity);
        $sql .= ", '".$this->db->idate($now)."'";
        $sql .= ", ".((int) $user->id);
        $sql .= ")";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            $this->errors[] = $this->error;
            return -1;
        }

        $arrivalId = (int) $this->db->last_insert_id($this->db->prefix().'arrivalcost');

        foreach ($lines as $line) {
            $ratio = ($totalBase > 0 && $line['base'] > 0) ? ($line['base'] / $totalBase) : 0;
            $allocated = price2num($amount * $ratio, 'MT');
            $unit = ($line['qty'] != 0) ? price2num($allocated / $line['qty'], 'MU') : 0;

            $insert = "INSERT INTO ".$this->db->prefix()."arrivalcost_line(";
            $insert .= "fk_arrivalcost, fk_supplier_order, fk_supplier_orderdet, fk_product, qty, base_amount, allocated_ht, allocated_unit, entity, datec";
            $insert .= ") VALUES(";
            $insert .= (int) $arrivalId;
            $insert .= ", ".((int) $line['fk_order']);
            $insert .= ", ".((int) $line['id']);
            $insert .= ", ".(empty($line['fk_product']) ? "NULL" : ((int) $line['fk_product']));
            $insert .= ", ".((float) $line['qty']);
            $insert .= ", ".((float) $line['base']);
            $insert .= ", ".((float) $allocated);
            $insert .= ", ".((float) $unit);
            $insert .= ", ".((int) $conf->entity);
            $insert .= ", '".$this->db->idate($now)."'";
            $insert .= ")";

            $resline = $this->db->query($insert);
            if (!$resline) {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                $this->errors[] = $this->error;
                return -1;
            }

            $updateResult = $this->updateLineExtras($line['id'], $unit, $allocated);
            if ($updateResult < 0) {
                $this->db->rollback();
                if (empty($this->error)) {
                    $this->error = 'Failed to update extrafields for line '.$line['id'];
                }
                $this->errors[] = $this->error;
                return -1;
            }
        }

        // Optionally create supplier invoice
        if ($createInvoice) {
            $invoiceId = $this->createSupplierInvoice($user, $arrivalId, $ref, $label, $note, $amount, $fkSupplier, $orderIds);
            if ($invoiceId < 0) {
                $this->db->rollback();
                return -1;
            }

            $up = "UPDATE ".$this->db->prefix()."arrivalcost SET fk_supplier_invoice=".(int) $invoiceId." WHERE rowid=".(int) $arrivalId;
            if (!$this->db->query($up)) {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                $this->errors[] = $this->error;
                return -1;
            }
        }

        $this->db->commit();

        return $arrivalId;
    }

    /**
     * Fetch a single arrival cost
     *
     * @param int $id
     * @return array|null
     */
    public function fetch($id)
    {
        global $conf;

        $sql = "SELECT rowid, ref, label, amount_ht, mode, note, datec, status";
        $sql .= " FROM ".$this->db->prefix()."arrivalcost";
        $sql .= " WHERE rowid = ".((int) $id);
        $sql .= " AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if ($resql && $this->db->num_rows($resql) > 0) {
            return $this->db->fetch_array($resql);
        }

        return null;
    }

    /**
     * Fetch arrival cost list
     *
     * @param int $limit
     * @return array
     */
    public function fetchAll($limit = 25)
    {
        global $conf;

        $sql = "SELECT a.rowid, a.ref, a.label, a.amount_ht, a.mode, a.datec, a.status, a.fk_supplier_invoice,";
        $sql .= " COUNT(l.rowid) as line_count, SUM(l.allocated_ht) as allocated_ht";
        $sql .= " FROM ".$this->db->prefix()."arrivalcost as a";
        $sql .= " LEFT JOIN ".$this->db->prefix()."arrivalcost_line as l ON l.fk_arrivalcost = a.rowid";
        $sql .= " WHERE a.entity = ".((int) $conf->entity);
        $sql .= " GROUP BY a.rowid, a.ref, a.label, a.amount_ht, a.mode, a.datec, a.status";
        $sql .= " ORDER BY a.datec DESC";
        $sql .= " LIMIT ".((int) $limit);

        $resql = $this->db->query($sql);
        if (!$resql) {
            return array();
        }

        $out = array();
        while ($obj = $this->db->fetch_object($resql)) {
            $out[] = array(
                'rowid' => (int) $obj->rowid,
                'ref' => $obj->ref,
                'label' => $obj->label,
                'amount_ht' => (float) $obj->amount_ht,
                'mode' => $obj->mode,
                'datec' => $obj->datec,
                'status' => (int) $obj->status,
                'line_count' => (int) $obj->line_count,
                'allocated_ht' => (float) $obj->allocated_ht
            );
        }

        return $out;
    }

    /**
     * Fetch lines allocated for an arrival cost
     *
     * @param int $id
     * @return array
     */
    public function fetchLines($id)
    {
        global $conf;

        $sql = "SELECT l.rowid, l.fk_supplier_order, l.fk_supplier_orderdet, l.qty, l.base_amount, l.allocated_ht, l.allocated_unit,";
        $sql .= " c.ref as order_ref, d.description, d.total_ht as line_total_ht, d.qty as order_qty,";
        $sql .= " p.ref as product_ref, p.label as product_label";
        $sql .= " FROM ".$this->db->prefix()."arrivalcost_line as l";
        $sql .= " LEFT JOIN ".$this->db->prefix()."commande_fournisseur as c ON c.rowid = l.fk_supplier_order";
        $sql .= " LEFT JOIN ".$this->db->prefix()."commande_fournisseurdet as d ON d.rowid = l.fk_supplier_orderdet";
        $sql .= " LEFT JOIN ".$this->db->prefix()."product as p ON p.rowid = l.fk_product";
        $sql .= " WHERE l.fk_arrivalcost = ".((int) $id);
        $sql .= " AND l.entity = ".((int) $conf->entity);
        $sql .= " ORDER BY c.ref, l.fk_supplier_orderdet";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return array();
        }

        $lines = array();
        while ($obj = $this->db->fetch_object($resql)) {
            $lines[] = array(
                'rowid' => (int) $obj->rowid,
                'fk_order' => (int) $obj->fk_supplier_order,
                'order_ref' => $obj->order_ref,
                'fk_order_line' => (int) $obj->fk_supplier_orderdet,
                'qty' => (float) $obj->qty,
                'base_amount' => (float) $obj->base_amount,
                'allocated_ht' => (float) $obj->allocated_ht,
                'allocated_unit' => (float) $obj->allocated_unit,
                'description' => $obj->description,
                'line_total_ht' => (float) $obj->line_total_ht,
                'order_qty' => (float) $obj->order_qty,
                'product_ref' => $obj->product_ref,
                'product_label' => $obj->product_label
            );
        }

        return $lines;
    }

    /**
     * @param array $orderIds
     * @return array
     */
    private function fetchOrderLines(array $orderIds)
    {
        global $conf;

        $cleanIds = array();
        foreach ($orderIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $cleanIds[] = $id;
            }
        }

        if (empty($cleanIds)) {
            return array();
        }

        $sql = "SELECT d.rowid, d.fk_commande as fk_order, d.fk_product, d.qty, d.total_ht, d.description";
        $sql .= " FROM ".$this->db->prefix()."commande_fournisseurdet as d";
        $sql .= " INNER JOIN ".$this->db->prefix()."commande_fournisseur as c ON c.rowid = d.fk_commande";
        $sql .= " WHERE c.entity = ".((int) $conf->entity);
        $sql .= " AND d.fk_commande IN (".implode(',', $cleanIds).")";
        $sql .= " AND d.special_code = 0";

        $resql = $this->db->query($sql);
        if (!$resql) {
            return array();
        }

        $lines = array();
        while ($obj = $this->db->fetch_object($resql)) {
            $lines[] = array(
                'id' => (int) $obj->rowid,
                'fk_order' => (int) $obj->fk_order,
                'fk_product' => (int) $obj->fk_product,
                'qty' => (float) $obj->qty,
                'total_ht' => (float) $obj->total_ht,
                'description' => $obj->description
            );
        }

        return $lines;
    }

    /**
     * Get averaged arrival cost per unit for a product
     *
     * @param int $productId
     * @return float
     */
    public function getProductArrivalUnitCost($productId)
    {
        global $conf;

        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }

        if (isset(self::$productCostCache[$productId])) {
            return self::$productCostCache[$productId];
        }

        $sql = "SELECT SUM(allocated_ht) as total_cost, SUM(qty) as total_qty";
        $sql .= " FROM ".$this->db->prefix()."arrivalcost_line";
        $sql .= " WHERE fk_product = ".((int) $productId);
        $sql .= " AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            if ($obj && $obj->total_qty > 0) {
                $unit = (float) ($obj->total_cost / $obj->total_qty);
                self::$productCostCache[$productId] = $unit;
                return $unit;
            }
        }

        self::$productCostCache[$productId] = 0;
        return 0;
    }

    /**
     * Create supplier invoice for arrival cost
     *
     * @param User  $user
     * @param int   $arrivalId
     * @param string $ref
     * @param string $label
     * @param string $note
     * @param float $amount
     * @param int   $fkSupplier
     * @param array $orderIds
     * @return int
     */
    private function createSupplierInvoice($user, $arrivalId, $ref, $label, $note, $amount, $fkSupplier, array $orderIds = array())
    {
        global $langs, $conf;

        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

        $invoice = new FactureFournisseur($this->db);
        $invoice->socid = (int) $fkSupplier;
        $invoice->date = dol_now();
        $invoice->libelle = $label;
        $invoice->ref_supplier = $ref;
        $invoice->note_private = $note;
        $invoice->type = FactureFournisseur::TYPE_STANDARD;

        $res = $invoice->create($user);
        if ($res < 0) {
            $this->error = $invoice->error;
            $this->errors = $invoice->errors;
            return -1;
        }

        $desc = $label;
        if (!empty($orderIds)) {
            $orderRefs = $this->fetchSupplierOrderRefs($orderIds);
            if (!empty($orderRefs)) {
                $desc .= ' ('.$langs->trans('SupplierOrders').': '.implode(', ', $orderRefs).')';
            }
        }

        $lineRes = $invoice->addline(
            $desc,
            price2num($amount, 'MU'),
            1,
            0,
            0,
            0,
            0,
            0,
            'HT'
        );

        if ($lineRes < 0) {
            $this->error = $invoice->error;
            $this->errors = $invoice->errors;
            return -1;
        }

        // Stay as draft; user can validate it
        return (int) $invoice->id;
    }

    /**
     * Fetch refs for supplier orders
     *
     * @param array $ids
     * @return array
     */
    private function fetchSupplierOrderRefs(array $ids)
    {
        global $conf;

        $clean = array();
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $clean[] = $id;
            }
        }
        if (empty($clean)) {
            return array();
        }

        $sql = "SELECT rowid, ref FROM ".$this->db->prefix()."commande_fournisseur";
        $sql .= " WHERE rowid IN (".implode(',', $clean).")";
        $sql .= " AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if (!$resql) {
            return array();
        }

        $refs = array();
        while ($obj = $this->db->fetch_object($resql)) {
            $refs[] = $obj->ref;
        }

        return $refs;
    }

    /**
     * Persist allocated amounts into line extrafields
     *
     * @param int   $lineId
     * @param float $unit
     * @param float $total
     * @return int
     */
    private function updateLineExtras($lineId, $unit, $total)
    {
        global $user;

        require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.orderline.class.php';

        $line = new CommandeFournisseurLigne($this->db);
        $res = $line->fetch($lineId);
        if ($res <= 0) {
            return -1;
        }

        $line->array_options['options_arrivalcost_unit'] = price2num($unit, 'MU');
        $line->array_options['options_arrivalcost_total'] = price2num($total, 'MT');

        $result = $line->insertExtraFields('', $user);
        if ($result < 0) {
            return -1;
        }

        return 1;
    }

    /**
     * Generate a small reference
     *
     * @return string
     */
    private function buildRef()
    {
        return 'ARR'.dol_print_date(dol_now(), '%Y%m%d%H%M%S');
    }

    /**
     * Remove extrafields from supplier order lines
     *
     * @return void
     */
    public function deleteLineExtraFields()
    {
        require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
        $extrafields = new ExtraFields($this->db);

        $extrafields->delete('arrivalcost_unit', 'commande_fournisseurdet');
        $extrafields->delete('arrivalcost_total', 'commande_fournisseurdet');
    }
}
