<?php
/**
 * Data layer for Sellout sales entries
 */

class SelloutSale
{
    /** @var DoliDB */
    private $db;

    /** @var string */
    public $error = '';

    /** @var array */
    public $errors = array();

    /**
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Insert a new sellout sale row
     *
     * @param array $data
     * @return int                 Row id or -1 on error
     */
    public function create($data)
    {
        global $conf;

        $this->error = '';
        $this->errors = array();

        $entity = isset($data['entity']) ? (int) $data['entity'] : (int) $conf->entity;
        $qty = price2num($data['qty'], 'MS');
        $unitPrice = price2num($data['unit_price'], 'MS');
        $currency = empty($data['currency_code']) ? $conf->currency : substr(trim($data['currency_code']), 0, 3);
        $saleDate = !empty($data['sale_date']) ? (int) $data['sale_date'] : dol_now();
        $datec = !empty($data['datec']) ? (int) $data['datec'] : dol_now();
        $locationLabel = !empty($data['location_label']) ? dol_trunc(trim($data['location_label']), 255) : '';
        $locationLat = isset($data['location_latitude']) && $data['location_latitude'] !== '' ? (float) $data['location_latitude'] : null;
        $locationLng = isset($data['location_longitude']) && $data['location_longitude'] !== '' ? (float) $data['location_longitude'] : null;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."sellout_sale(";
        $sql .= "entity, fk_user, fk_soc, fk_product, qty, unit_price, currency_code, location_label, location_latitude, location_longitude, sale_date, source, note, datec";
        $sql .= ") VALUES (";
        $sql .= $entity.", ";
        $sql .= ((int) $data['fk_user']).", ";
        $sql .= ((int) $data['fk_soc']).", ";
        $sql .= ((int) $data['fk_product']).", ";
        $sql .= $qty.", ";
        $sql .= $unitPrice.", ";
        $sql .= ($currency !== '' ? "'".$this->db->escape($currency)."'" : "NULL").", ";
        $sql .= ($locationLabel !== '' ? "'".$this->db->escape($locationLabel)."'" : "NULL").", ";
        $sql .= ($locationLat !== null ? (float) $locationLat : "NULL").", ";
        $sql .= ($locationLng !== null ? (float) $locationLng : "NULL").", ";
        $sql .= "'".$this->db->idate($saleDate)."', ";
        $sql .= (!empty($data['source']) ? "'".$this->db->escape($data['source'])."'" : "NULL").", ";
        $sql .= (!empty($data['note']) ? "'".$this->db->escape($data['note'])."'" : "NULL").", ";
        $sql .= "'".$this->db->idate($datec)."'";
        $sql .= ")";

        $this->db->begin();

        $res = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }

        $id = $this->db->last_insert_id($this->db->prefix().'sellout_sale');
        if ($id <= 0) {
            $this->error = 'Failed to retrieve inserted id';
            $this->db->rollback();
            return -1;
        }

        $this->db->commit();

        return (int) $id;
    }

    /**
     * Fetch one sellout sale row
     *
     * @param int $id
     * @return array|int           Row data, 0 if not found, -1 on error
     */
    public function fetch($id)
    {
        $this->error = '';
        $this->errors = array();

        $sql = "SELECT s.rowid, s.entity, s.fk_user, s.fk_soc, s.fk_product, s.qty, s.unit_price, s.currency_code, s.location_label, s.location_latitude, s.location_longitude, s.sale_date, s.source, s.note, s.datec, s.tms";
        $sql .= " FROM ".MAIN_DB_PREFIX."sellout_sale as s";
        $sql .= " WHERE s.rowid = ".((int) $id);

        $res = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $obj = $this->db->fetch_object($res);
        if (!$obj) {
            return 0;
        }

        return array(
            'id' => (int) $obj->rowid,
            'entity' => (int) $obj->entity,
            'fk_user' => (int) $obj->fk_user,
            'fk_soc' => (int) $obj->fk_soc,
            'fk_product' => (int) $obj->fk_product,
            'qty' => (float) $obj->qty,
            'unit_price' => (float) $obj->unit_price,
            'currency_code' => $obj->currency_code,
            'location_label' => $obj->location_label,
            'location_latitude' => $obj->location_latitude !== null ? (float) $obj->location_latitude : null,
            'location_longitude' => $obj->location_longitude !== null ? (float) $obj->location_longitude : null,
            'sale_date' => $this->db->jdate($obj->sale_date),
            'source' => $obj->source,
            'note' => $obj->note,
            'datec' => $this->db->jdate($obj->datec),
            'tms' => $this->db->jdate($obj->tms)
        );
    }

    /**
     * List sellout sales with optional filters
     *
     * @param array $filters
     * @param int   $limit
     * @param int   $offset
     * @return array|int            Rows or -1 on error
     */
    public function search(array $filters, $limit = 50, $offset = 0)
    {
        $this->error = '';
        $this->errors = array();

        $sql = "SELECT s.rowid, s.entity, s.fk_user, s.fk_soc, s.fk_product, s.qty, s.unit_price, s.currency_code, s.location_label, s.location_latitude, s.location_longitude, s.sale_date, s.source, s.note, s.datec, s.tms";
        $sql .= " FROM ".MAIN_DB_PREFIX."sellout_sale as s";
        $sql .= " WHERE s.entity IN (".getEntity('sellout_sale').")";

        if (!empty($filters['socid'])) {
            $sql .= " AND s.fk_soc = ".((int) $filters['socid']);
        }
        if (!empty($filters['user_id'])) {
            $sql .= " AND s.fk_user = ".((int) $filters['user_id']);
        }
        if (!empty($filters['product_id'])) {
            $sql .= " AND s.fk_product = ".((int) $filters['product_id']);
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND s.sale_date >= '".$this->db->idate($filters['date_from'])."'";
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND s.sale_date <= '".$this->db->idate($filters['date_to'])."'";
        }

        $sql .= " ORDER BY s.sale_date DESC, s.rowid DESC";

        if ($limit > 0) {
            $sql .= $this->db->plimit($limit, $offset);
        }

        $res = $this->db->query($sql);
        if (!$res) {
            $this->error = $this->db->lasterror();
            return -1;
        }

        $rows = array();
        while ($obj = $this->db->fetch_object($res)) {
            $rows[] = array(
                'id' => (int) $obj->rowid,
                'entity' => (int) $obj->entity,
                'fk_user' => (int) $obj->fk_user,
                'fk_soc' => (int) $obj->fk_soc,
                'fk_product' => (int) $obj->fk_product,
                'qty' => (float) $obj->qty,
                'unit_price' => (float) $obj->unit_price,
                'currency_code' => $obj->currency_code,
                'location_label' => $obj->location_label,
                'location_latitude' => $obj->location_latitude !== null ? (float) $obj->location_latitude : null,
                'location_longitude' => $obj->location_longitude !== null ? (float) $obj->location_longitude : null,
                'sale_date' => $this->db->jdate($obj->sale_date),
                'source' => $obj->source,
                'note' => $obj->note,
                'datec' => $this->db->jdate($obj->datec),
                'tms' => $this->db->jdate($obj->tms)
            );
        }

        return $rows;
    }
}
