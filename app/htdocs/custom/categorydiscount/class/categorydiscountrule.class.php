<?php

/**
 * Helper for category discount rules
 */
class CategoryDiscountRule
{
    /** @var DoliDB */
    private $db;

    public $id;
    public $entity;
    public $fk_soc;
    public $fk_categorie;
    public $discount_percent;
    public $fk_user_creat;
    public $fk_user_modif;
    public $datec;
    public $tms;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Ensure database table exists (useful if activation script was skipped)
     *
     * @return bool
     */
    public function ensureTable()
    {
        $res = $this->db->query("SHOW TABLES LIKE '".$this->db->escape(MAIN_DB_PREFIX."categorydiscount_rule")."'");
        if ($res && $this->db->num_rows($res) > 0) {
            return true;
        }

        $sql = "CREATE TABLE IF NOT EXISTS ".MAIN_DB_PREFIX."categorydiscount_rule (";
        $sql .= " rowid integer AUTO_INCREMENT PRIMARY KEY,";
        $sql .= " entity integer NOT NULL DEFAULT 1,";
        $sql .= " fk_soc integer NULL,";
        $sql .= " fk_categorie integer NOT NULL,";
        $sql .= " discount_percent double(24,8) NOT NULL DEFAULT 0,";
        $sql .= " datec datetime NULL,";
        $sql .= " tms timestamp NULL DEFAULT CURRENT_TIMESTAMP,";
        $sql .= " fk_user_creat integer NULL,";
        $sql .= " fk_user_modif integer NULL,";
        $sql .= " INDEX idx_categorydiscount_entity (entity),";
        $sql .= " INDEX idx_categorydiscount_soc (fk_soc),";
        $sql .= " INDEX idx_categorydiscount_cat (fk_categorie)";
        $sql .= ") ENGINE=innodb;";

        return (bool) $this->db->query($sql);
    }

    /**
     * Create a new rule
     *
     * @param User $user
     * @return int
     */
    public function create($user)
    {
        $this->datec = dol_now();
        $this->entity = (int) $this->entity;
        $this->fk_soc = $this->fk_soc > 0 ? (int) $this->fk_soc : null;
        $this->fk_categorie = (int) $this->fk_categorie;
        $this->discount_percent = price2num($this->discount_percent);
        $this->fk_user_creat = $user->id;

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."categorydiscount_rule(entity, fk_soc, fk_categorie, discount_percent, datec, fk_user_creat)";
        $sql .= " VALUES(".$this->entity.",";
        $sql .= ($this->fk_soc ? $this->fk_soc : "NULL").",";
        $sql .= $this->fk_categorie.",";
        $sql .= $this->discount_percent.",";
        $sql .= "'".$this->db->idate($this->datec)."',";
        $sql .= (int) $this->fk_user_creat.")";

        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.'categorydiscount_rule');
            return $this->id;
        }

        dol_syslog(__METHOD__.' error '.$this->db->lasterror(), LOG_ERR);
        return -1;
    }

    /**
     * Delete rule
     *
     * @param int $id
     * @return int
     */
    public function delete($id)
    {
        $sql = "DELETE FROM ".MAIN_DB_PREFIX."categorydiscount_rule WHERE rowid=".(int) $id;
        if ($this->db->query($sql)) {
            return 1;
        }
        dol_syslog(__METHOD__.' error '.$this->db->lasterror(), LOG_ERR);
        return -1;
    }

    /**
     * Fetch the best matching rule for a product/categories + thirdparty
     *
     * @param int $socId
     * @param int $productId
     * @param int $entity
     * @return array|null
     */
    public function fetchBestRuleForProduct($socId, $productId, $entity)
    {
        $categories = $this->getProductCategoryIds($productId);
        if (empty($categories)) {
            return null;
        }

        $sql = "SELECT rowid, fk_soc, fk_categorie, discount_percent";
        $sql .= " FROM ".MAIN_DB_PREFIX."categorydiscount_rule";
        $sql .= " WHERE entity=".(int) $entity;
        $sql .= " AND fk_categorie IN (".implode(',', array_map('intval', $categories)).")";
        $sql .= " AND (fk_soc IS NULL OR fk_soc=0 OR fk_soc=".(int) $socId.")";
        $sql .= " ORDER BY fk_soc DESC, discount_percent DESC";
        $sql .= " LIMIT 1";

        $res = $this->db->query($sql);
        if ($res) {
            $obj = $this->db->fetch_object($res);
            if ($obj) {
                return array(
                    'id' => (int) $obj->rowid,
                    'fk_soc' => (int) $obj->fk_soc,
                    'fk_categorie' => (int) $obj->fk_categorie,
                    'discount_percent' => (float) $obj->discount_percent
                );
            }
        } else {
            dol_syslog(__METHOD__.' error '.$this->db->lasterror(), LOG_ERR);
        }

        return null;
    }

    /**
     * List rules for display
     *
     * @param int $entity
     * @return array
     */
    public function fetchAll($entity)
    {
        $rules = array();
        $sql = "SELECT rowid, fk_soc, fk_categorie, discount_percent, datec";
        $sql .= " FROM ".MAIN_DB_PREFIX."categorydiscount_rule";
        $sql .= " WHERE entity=".(int) $entity;
        $sql .= " ORDER BY fk_soc DESC, fk_categorie ASC";

        $res = $this->db->query($sql);
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $rules[] = $obj;
            }
            $this->db->free($res);
        }

        return $rules;
    }

    /**
     * Get category ids linked to product
     *
     * @param int $productId
     * @return int[]
     */
    private function getProductCategoryIds($productId)
    {
        $ids = array();
        $sql = "SELECT fk_categorie FROM ".MAIN_DB_PREFIX."categorie_product WHERE fk_product=".(int) $productId;
        $res = $this->db->query($sql);
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $ids[] = (int) $obj->fk_categorie;
            }
            $this->db->free($res);
        }

        return array_values(array_unique($ids));
    }
}
