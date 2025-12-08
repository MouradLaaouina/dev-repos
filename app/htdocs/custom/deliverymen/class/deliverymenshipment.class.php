<?php
/**
 * Class DeliverymenShipment - Manage delivery assignments
 */

class DeliverymenShipment extends CommonObject
{
    public $element = 'deliverymenshipment';
    public $table_element = 'deliverymen_shipment';
    public $picto = 'user';

    // Fields
    public $fk_expedition;
    public $fk_user_deliveryman;
    public $date_assignment;
    public $fk_user_assign;
    public $delivery_date;
    public $delivery_status;
    public $date_delivery_done;
    public $note_delivery;
    public $signature;
    public $datec;
    public $entity;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_FAILED = 'failed';

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
     * Create assignment
     *
     * @param User $user User creating
     * @return int ID if OK, <0 if KO
     */
    public function create($user)
    {
        global $conf;

        $this->entity = $conf->entity;
        $this->datec = dol_now();
        $this->date_assignment = dol_now();
        $this->fk_user_assign = $user->id;
        $this->delivery_status = self::STATUS_PENDING;

        // Check if assignment already exists
        $existing = $this->fetchByExpedition($this->fk_expedition);
        if ($existing > 0) {
            // Update instead
            return $this->update($user);
        }

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element." (";
        $sql .= "entity, fk_expedition, fk_user_deliveryman, date_assignment,";
        $sql .= "fk_user_assign, delivery_date, delivery_status, note_delivery, datec";
        $sql .= ") VALUES (";
        $sql .= ((int) $this->entity).",";
        $sql .= ((int) $this->fk_expedition).",";
        $sql .= ((int) $this->fk_user_deliveryman).",";
        $sql .= "'".$this->db->idate($this->date_assignment)."',";
        $sql .= ((int) $this->fk_user_assign).",";
        $sql .= ($this->delivery_date ? "'".$this->db->idate($this->delivery_date)."'" : "NULL").",";
        $sql .= "'".$this->db->escape($this->delivery_status)."',";
        $sql .= ($this->note_delivery ? "'".$this->db->escape($this->note_delivery)."'" : "NULL").",";
        $sql .= "'".$this->db->idate($this->datec)."'";
        $sql .= ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id($this->db->prefix().$this->table_element);
            return $this->id;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Fetch by ID
     *
     * @param int $id ID
     * @return int 1 if OK, 0 if not found, -1 on error
     */
    public function fetch($id)
    {
        $sql = "SELECT rowid, entity, fk_expedition, fk_user_deliveryman, date_assignment,";
        $sql .= " fk_user_assign, delivery_date, delivery_status, date_delivery_done,";
        $sql .= " note_delivery, signature, datec";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                $this->id = $obj->rowid;
                $this->entity = $obj->entity;
                $this->fk_expedition = $obj->fk_expedition;
                $this->fk_user_deliveryman = $obj->fk_user_deliveryman;
                $this->date_assignment = $this->db->jdate($obj->date_assignment);
                $this->fk_user_assign = $obj->fk_user_assign;
                $this->delivery_date = $this->db->jdate($obj->delivery_date);
                $this->delivery_status = $obj->delivery_status;
                $this->date_delivery_done = $this->db->jdate($obj->date_delivery_done);
                $this->note_delivery = $obj->note_delivery;
                $this->signature = $obj->signature;
                $this->datec = $this->db->jdate($obj->datec);
                return 1;
            }
            return 0;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Fetch by expedition ID
     *
     * @param int $expeditionid Expedition ID
     * @return int 1 if OK, 0 if not found, -1 on error
     */
    public function fetchByExpedition($expeditionid)
    {
        global $conf;

        $sql = "SELECT rowid FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE fk_expedition = ".((int) $expeditionid);
        $sql .= " AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);
                return $this->fetch($obj->rowid);
            }
            return 0;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Update assignment
     *
     * @param User $user User updating
     * @return int 1 if OK, -1 on error
     */
    public function update($user)
    {
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " fk_user_deliveryman = ".((int) $this->fk_user_deliveryman).",";
        $sql .= " delivery_date = ".($this->delivery_date ? "'".$this->db->idate($this->delivery_date)."'" : "NULL").",";
        $sql .= " delivery_status = '".$this->db->escape($this->delivery_status)."',";
        $sql .= " date_delivery_done = ".($this->date_delivery_done ? "'".$this->db->idate($this->date_delivery_done)."'" : "NULL").",";
        $sql .= " note_delivery = ".($this->note_delivery ? "'".$this->db->escape($this->note_delivery)."'" : "NULL").",";
        $sql .= " signature = ".($this->signature ? "'".$this->db->escape($this->signature)."'" : "NULL");
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Set delivery status
     *
     * @param User   $user   User
     * @param string $status New status
     * @param string $note   Note
     * @return int 1 if OK, -1 on error
     */
    public function setStatus($user, $status, $note = '')
    {
        $this->delivery_status = $status;
        if ($note) {
            $this->note_delivery = $note;
        }
        if ($status == self::STATUS_DELIVERED || $status == self::STATUS_FAILED) {
            $this->date_delivery_done = dol_now();
        }
        return $this->update($user);
    }

    /**
     * Delete assignment
     *
     * @param User $user User deleting
     * @return int 1 if OK, -1 on error
     */
    public function delete($user)
    {
        $sql = "DELETE FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    /**
     * Get deliveries for a deliveryman on a specific date
     *
     * @param DoliDB $db          Database handler
     * @param int    $userid      Deliveryman user ID
     * @param int    $date        Date (timestamp)
     * @param string $status      Filter by status (optional)
     * @return array Array of deliveries
     */
    public static function getDeliveriesForUser($db, $userid, $date = null, $status = '')
    {
        global $conf;

        $deliveries = array();

        $sql = "SELECT ds.rowid, ds.fk_expedition, ds.delivery_date, ds.delivery_status,";
        $sql .= " ds.date_delivery_done, ds.note_delivery,";
        $sql .= " e.ref as expedition_ref, e.date_expedition, e.fk_soc,";
        $sql .= " s.nom as thirdparty_name, s.address, s.zip, s.town";
        $sql .= " FROM ".$db->prefix()."deliverymen_shipment as ds";
        $sql .= " LEFT JOIN ".$db->prefix()."expedition as e ON e.rowid = ds.fk_expedition";
        $sql .= " LEFT JOIN ".$db->prefix()."societe as s ON s.rowid = e.fk_soc";
        $sql .= " WHERE ds.fk_user_deliveryman = ".((int) $userid);
        $sql .= " AND ds.entity = ".((int) $conf->entity);

        if ($date) {
            $sql .= " AND ds.delivery_date = '".$db->idate($date)."'";
        }
        if ($status) {
            $sql .= " AND ds.delivery_status = '".$db->escape($status)."'";
        }

        $sql .= " ORDER BY ds.delivery_date ASC, e.ref ASC";

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $deliveries[] = array(
                    'id' => $obj->rowid,
                    'fk_expedition' => $obj->fk_expedition,
                    'expedition_ref' => $obj->expedition_ref,
                    'date_expedition' => $obj->date_expedition,
                    'delivery_date' => $obj->delivery_date,
                    'delivery_status' => $obj->delivery_status,
                    'date_delivery_done' => $obj->date_delivery_done,
                    'note_delivery' => $obj->note_delivery,
                    'fk_soc' => $obj->fk_soc,
                    'thirdparty_name' => $obj->thirdparty_name,
                    'address' => $obj->address,
                    'zip' => $obj->zip,
                    'town' => $obj->town
                );
            }
        }

        return $deliveries;
    }

    /**
     * Get all deliverymen (users who have been assigned deliveries)
     *
     * @param DoliDB $db Database handler
     * @return array Array of users
     */
    public static function getAllDeliverymen($db)
    {
        global $conf;

        $users = array();

        // Get users who are employees
        $sql = "SELECT DISTINCT u.rowid, u.login, u.lastname, u.firstname";
        $sql .= " FROM ".$db->prefix()."user as u";
        $sql .= " WHERE u.statut = 1"; // Active users
        $sql .= " AND u.employee = 1"; // Employees only
        $sql .= " AND u.entity IN (0, ".((int) $conf->entity).")";
        $sql .= " ORDER BY u.lastname, u.firstname";

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $users[$obj->rowid] = array(
                    'id' => $obj->rowid,
                    'login' => $obj->login,
                    'lastname' => $obj->lastname,
                    'firstname' => $obj->firstname,
                    'fullname' => trim($obj->firstname.' '.$obj->lastname)
                );
            }
        }

        return $users;
    }

    /**
     * Get status label
     *
     * @param string $status Status code
     * @return string Translated label
     */
    public static function getStatusLabel($status)
    {
        global $langs;
        $langs->load('deliverymen@deliverymen');

        $labels = array(
            self::STATUS_PENDING => $langs->trans('StatusPending'),
            self::STATUS_IN_PROGRESS => $langs->trans('StatusInProgress'),
            self::STATUS_DELIVERED => $langs->trans('StatusDelivered'),
            self::STATUS_FAILED => $langs->trans('StatusFailed')
        );

        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Get status badge HTML
     *
     * @param string $status Status code
     * @return string HTML badge
     */
    public static function getStatusBadge($status)
    {
        $label = self::getStatusLabel($status);

        $colors = array(
            self::STATUS_PENDING => 'status1',
            self::STATUS_IN_PROGRESS => 'status4',
            self::STATUS_DELIVERED => 'status6',
            self::STATUS_FAILED => 'status8'
        );

        $color = isset($colors[$status]) ? $colors[$status] : 'status0';

        return '<span class="badge badge-'.$color.' badge-status">'.$label.'</span>';
    }
}
