<?php
/**
 * Class ReturnedCheck - Manage returned/unpaid checks with sanction system
 */

class ReturnedCheck extends CommonObject
{
    public $element = 'returnedcheck';
    public $table_element = 'customercheck_returnedcheck';
    public $picto = 'warning';

    public $ref;
    public $fk_soc;
    public $fk_payment;
    public $amount;
    public $num_check;
    public $bank_name;
    public $date_check;
    public $date_returned;
    public $reason;
    public $sanction_active;
    public $date_sanction_lifted;
    public $fk_user_sanction_lifted;
    public $note_sanction;
    public $date_creation;
    public $fk_user_creat;
    public $entity;

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
     * Create a returned check record
     *
     * @param User $user User creating the record
     * @return int ID of created record or negative on error
     */
    public function create($user)
    {
        global $conf;

        $this->entity = $conf->entity;
        $this->date_creation = dol_now();
        $this->fk_user_creat = $user->id;
        $this->sanction_active = 1;

        // Generate ref
        $this->ref = $this->getNextRef();

        $sql = "INSERT INTO ".$this->db->prefix().$this->table_element." (";
        $sql .= "ref, fk_soc, fk_payment, amount, num_check, bank_name,";
        $sql .= "date_check, date_returned, reason, sanction_active,";
        $sql .= "date_creation, fk_user_creat, entity";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= ((int) $this->fk_soc).",";
        $sql .= ($this->fk_payment > 0 ? ((int) $this->fk_payment) : "NULL").",";
        $sql .= ((float) $this->amount).",";
        $sql .= ($this->num_check ? "'".$this->db->escape($this->num_check)."'" : "NULL").",";
        $sql .= ($this->bank_name ? "'".$this->db->escape($this->bank_name)."'" : "NULL").",";
        $sql .= ($this->date_check ? "'".$this->db->idate($this->date_check)."'" : "NULL").",";
        $sql .= ($this->date_returned ? "'".$this->db->idate($this->date_returned)."'" : "NULL").",";
        $sql .= ($this->reason ? "'".$this->db->escape($this->reason)."'" : "NULL").",";
        $sql .= "1,";
        $sql .= "'".$this->db->idate($this->date_creation)."',";
        $sql .= ((int) $this->fk_user_creat).",";
        $sql .= ((int) $this->entity);
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
     * Fetch a returned check record
     *
     * @param int $id ID of record
     * @return int 1 if OK, 0 if not found, -1 on error
     */
    public function fetch($id)
    {
        $sql = "SELECT rowid, ref, fk_soc, fk_payment, amount, num_check, bank_name,";
        $sql .= " date_check, date_returned, reason, sanction_active,";
        $sql .= " date_sanction_lifted, fk_user_sanction_lifted, note_sanction,";
        $sql .= " date_creation, fk_user_creat, entity";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE rowid = ".((int) $id);

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->fk_soc = $obj->fk_soc;
                $this->fk_payment = $obj->fk_payment;
                $this->amount = $obj->amount;
                $this->num_check = $obj->num_check;
                $this->bank_name = $obj->bank_name;
                $this->date_check = $this->db->jdate($obj->date_check);
                $this->date_returned = $this->db->jdate($obj->date_returned);
                $this->reason = $obj->reason;
                $this->sanction_active = $obj->sanction_active;
                $this->date_sanction_lifted = $this->db->jdate($obj->date_sanction_lifted);
                $this->fk_user_sanction_lifted = $obj->fk_user_sanction_lifted;
                $this->note_sanction = $obj->note_sanction;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->fk_user_creat = $obj->fk_user_creat;
                $this->entity = $obj->entity;

                return 1;
            }
            return 0;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Update record
     *
     * @param User $user User making update
     * @return int 1 if OK, -1 on error
     */
    public function update($user)
    {
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " fk_soc = ".((int) $this->fk_soc).",";
        $sql .= " amount = ".((float) $this->amount).",";
        $sql .= " num_check = ".($this->num_check ? "'".$this->db->escape($this->num_check)."'" : "NULL").",";
        $sql .= " bank_name = ".($this->bank_name ? "'".$this->db->escape($this->bank_name)."'" : "NULL").",";
        $sql .= " date_check = ".($this->date_check ? "'".$this->db->idate($this->date_check)."'" : "NULL").",";
        $sql .= " date_returned = ".($this->date_returned ? "'".$this->db->idate($this->date_returned)."'" : "NULL").",";
        $sql .= " reason = ".($this->reason ? "'".$this->db->escape($this->reason)."'" : "NULL");
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Lift the sanction (allow customer to order again)
     *
     * @param User   $user User lifting the sanction
     * @param string $note Note explaining why sanction is lifted
     * @return int 1 if OK, -1 on error
     */
    public function liftSanction($user, $note = '')
    {
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " sanction_active = 0,";
        $sql .= " date_sanction_lifted = '".$this->db->idate(dol_now())."',";
        $sql .= " fk_user_sanction_lifted = ".((int) $user->id).",";
        $sql .= " note_sanction = ".($note ? "'".$this->db->escape($note)."'" : "NULL");
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->sanction_active = 0;
            $this->date_sanction_lifted = dol_now();
            $this->fk_user_sanction_lifted = $user->id;
            $this->note_sanction = $note;
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Reactivate the sanction
     *
     * @param User $user User reactivating
     * @return int 1 if OK, -1 on error
     */
    public function reactivateSanction($user)
    {
        $sql = "UPDATE ".$this->db->prefix().$this->table_element." SET";
        $sql .= " sanction_active = 1,";
        $sql .= " date_sanction_lifted = NULL,";
        $sql .= " fk_user_sanction_lifted = NULL,";
        $sql .= " note_sanction = NULL";
        $sql .= " WHERE rowid = ".((int) $this->id);

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->sanction_active = 1;
            $this->date_sanction_lifted = null;
            $this->fk_user_sanction_lifted = null;
            $this->note_sanction = null;
            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Delete record
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
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * Get next reference number
     *
     * @return string Reference
     */
    private function getNextRef()
    {
        global $conf;

        $sql = "SELECT MAX(CAST(SUBSTRING(ref, 4) AS UNSIGNED)) as maxref";
        $sql .= " FROM ".$this->db->prefix().$this->table_element;
        $sql .= " WHERE ref LIKE 'CHK%'";
        $sql .= " AND entity = ".((int) $conf->entity);

        $resql = $this->db->query($sql);
        if ($resql) {
            $obj = $this->db->fetch_object($resql);
            $num = $obj->maxref ? $obj->maxref + 1 : 1;
            return 'CHK'.str_pad($num, 6, '0', STR_PAD_LEFT);
        }
        return 'CHK000001';
    }

    /**
     * Get all returned checks for a customer with active sanctions
     *
     * @param int $socid Customer ID
     * @param int $activeOnly 1 to get only active sanctions, 0 for all
     * @return array Array with 'count', 'total', 'checks'
     */
    public static function getReturnedChecksForCustomer($db, $socid, $activeOnly = 1)
    {
        global $conf;

        $result = array('count' => 0, 'total' => 0, 'checks' => array());

        $sql = "SELECT rowid, ref, amount, num_check, bank_name, date_check,";
        $sql .= " date_returned, reason, sanction_active, date_sanction_lifted,";
        $sql .= " fk_user_sanction_lifted, note_sanction";
        $sql .= " FROM ".$db->prefix()."customercheck_returnedcheck";
        $sql .= " WHERE fk_soc = ".((int) $socid);
        $sql .= " AND entity = ".((int) $conf->entity);
        if ($activeOnly) {
            $sql .= " AND sanction_active = 1";
        }
        $sql .= " ORDER BY date_returned DESC";

        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                if ($obj->sanction_active) {
                    $result['count']++;
                    $result['total'] += $obj->amount;
                }
                $result['checks'][] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'amount' => $obj->amount,
                    'num_check' => $obj->num_check,
                    'bank_name' => $obj->bank_name,
                    'date_check' => $obj->date_check,
                    'date_returned' => $obj->date_returned,
                    'reason' => $obj->reason,
                    'sanction_active' => $obj->sanction_active,
                    'date_sanction_lifted' => $obj->date_sanction_lifted
                );
            }
        }

        return $result;
    }

    /**
     * Get link to card
     *
     * @param int $withpicto Add picto
     * @return string HTML link
     */
    public function getNomUrl($withpicto = 0)
    {
        $result = '';
        $url = dol_buildpath('/custom/customercheck/returnedcheck_card.php', 1).'?id='.$this->id;

        if ($withpicto) {
            $result .= img_picto('', $this->picto, 'class="paddingright"');
        }
        $result .= '<a href="'.$url.'">'.$this->ref.'</a>';

        return $result;
    }
}
