<?php
/**
 * API BToC Stats
 */

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';

/**
 * API class for BToC statistics
 */
class Btocstats extends DolibarrApi
{
    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Create a statistics entry
     *
     * @param string $action   Action performed
     * @param string $details  Details of the action (JSON or string)
     * @param int    $user_id  User ID
     *
     * @url POST /
     *
     * @return int ID of the created entry
     */
    public function post($action, $details = '', $user_id = null)
    {
        global $db, $user;

        if (empty($user_id)) {
            $user_id = $user->id;
        }

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."btoc_stats (date_creation, action, user_id, details, entity)";
        $sql .= " VALUES (";
        $sql .= " '".$db->idate(dol_now())."',";
        $sql .= " '".$db->escape($action)."',";
        $sql .= " ".($user_id ? (int) $user_id : 'NULL').",";
        $sql .= " ".(!empty($details) ? "'".$db->escape($details)."'" : 'NULL').",";
        $sql .= " ".(int) $user->entity;
        $sql .= ")";

        $result = $db->query($sql);
        if ($result) {
            return $db->last_insert_id(MAIN_DB_PREFIX."btoc_stats");
        } else {
            throw new RestException(500, 'Error when creating stat entry: '.$db->lasterror());
        }
    }

    /**
     * Get statistics
     *
     * @param string $action   Filter by action
     * @param int    $user_id  Filter by user ID
     * @param int    $limit    Limit of results
     * @param int    $page     Page number
     *
     * @url GET /
     *
     * @return array List of statistics
     */
    public function index($action = '', $user_id = null, $limit = 100, $page = 0)
    {
        global $db;

        $sql = "SELECT rowid, date_creation, action, user_id, details";
        $sql .= " FROM ".MAIN_DB_PREFIX."btoc_stats";
        $sql .= " WHERE entity IN (".getEntity('btoc_stats').")";

        if (!empty($action)) {
            $sql .= " AND action = '".$db->escape($action)."'";
        }
        if (!empty($user_id)) {
            $sql .= " AND user_id = ".(int) $user_id;
        }

        $sql .= " ORDER BY date_creation DESC";
        $sql .= $db->plimit($limit, $page * $limit);

        $result = $db->query($sql);
        if (!$result) {
            throw new RestException(500, 'Error when fetching stats: '.$db->lasterror());
        }

        $stats = array();
        while ($obj = $db->fetch_object($result)) {
            $stats[] = $obj;
        }

        return $stats;
    }
}
