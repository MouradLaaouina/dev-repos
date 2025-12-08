<?php
/**
 * API A2S - Endpoint personnalisé pour récupérer les clients par commercial
 */

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

/**
 * API class for A2S custom endpoints
 */
class A2sapi extends DolibarrApi
{
    /**
     * @var Societe $societe {@type Societe}
     */
    public $societe;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->societe = new Societe($this->db);
    }

    /**
     * Get thirdparties (clients) by sales representative
     *
     * @param int    $user_id     User ID of the sales representative
     * @param int    $client_type Client type (1=client, 2=prospect, 3=both)
     * @param int    $page        Page number (0=first page)
     * @param int    $limit       Limit of results per page
     *
     * @url GET /thirdparties/bysalesrep
     *
     * @return array List of thirdparties
     */
    public function getThirdpartiesBySalesRep($user_id = 0, $client_type = 1, $page = 0, $limit = 100)
    {
        global $db, $user;

        // Si user_id n'est pas fourni, utiliser l'utilisateur connecté
        if (empty($user_id)) {
            $user_id = $user->id;
        }

        $sql = "SELECT DISTINCT s.rowid";
        $sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
        $sql .= " WHERE s.entity IN (".getEntity('societe').")";

        // Filtre par type de client
        if ($client_type == 1) {
            $sql .= " AND (s.client = 1 OR s.client = 3)";
        } elseif ($client_type == 2) {
            $sql .= " AND (s.client = 2 OR s.client = 3)";
        } elseif ($client_type == 3) {
            $sql .= " AND s.client > 0";
        }

        // Filtre UNIQUEMENT par commercial assigné (pas par créateur)
        $sql .= " AND sc.fk_user = ".((int) $user_id);

        $sql .= " ORDER BY s.rowid ASC";
        $sql .= $db->plimit($limit, $page * $limit);

        $result = $db->query($sql);

        if (!$result) {
            throw new RestException(500, 'Error when fetching thirdparties: '.$db->lasterror());
        }

        $thirdparties = array();
        $num = $db->num_rows($result);

        for ($i = 0; $i < $num; $i++) {
            $obj = $db->fetch_object($result);

            $societe = new Societe($db);
            if ($societe->fetch($obj->rowid) > 0) {
                $thirdparties[] = $this->_cleanObjectDatas($societe);
            }
        }

        return $thirdparties;
    }

    /**
     * Get sales representatives for a thirdparty
     *
     * @param int $id ID of thirdparty
     *
     * @url GET /thirdparties/{id}/salesreps
     *
     * @return array List of sales representatives
     */
    public function getThirdpartySalesReps($id)
    {
        global $db;

        $sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.email";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_user = u.rowid";
        $sql .= " WHERE sc.fk_soc = ".((int) $id);

        $result = $db->query($sql);

        if (!$result) {
            throw new RestException(500, 'Error when fetching sales reps: '.$db->lasterror());
        }

        $salesreps = array();
        $num = $db->num_rows($result);

        for ($i = 0; $i < $num; $i++) {
            $obj = $db->fetch_object($result);
            $salesreps[] = array(
                'id' => $obj->rowid,
                'login' => $obj->login,
                'lastname' => $obj->lastname,
                'firstname' => $obj->firstname,
                'email' => $obj->email
            );
        }

        return $salesreps;
    }
}
