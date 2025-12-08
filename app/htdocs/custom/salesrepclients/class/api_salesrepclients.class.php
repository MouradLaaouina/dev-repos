<?php
/**
 * API to expose clients attached to a sales representative.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Salesrepclients REST class
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Salesrepclients extends DolibarrApi
{
	/**
	 * @var Societe
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
	 * List clients attached to a sales representative.
	 *
	 * @param int $salesrep_id User id of the sales representative. Defaults to the connected API user.
	 * @param int $client_type 1 = clients, 2 = prospects, 3 = both
	 * @param int $page        Page number (0-based)
	 * @param int $limit       Number of rows per page (max 500)
	 *
	 * @url GET salesreps/{salesrep_id}/clients
	 * @url GET salesreps/clients
	 *
	 * @return array
	 * @throws RestException
	 */
	public function getClientsBySalesRep($salesrep_id = 0, $client_type = 1, $page = 0, $limit = 100)
	{
		$apiUser = DolibarrApiAccess::$user;
		if (!$apiUser) {
			throw new RestException(401, 'Authentication required');
		}

		if (!$apiUser->hasRight('societe', 'lire')) {
			throw new RestException(403, 'Permission denied to read third parties');
		}

		if (empty($salesrep_id)) {
			$salesrep_id = $apiUser->id;
		}

		$canSeeOtherSalesReps = $apiUser->hasRight('societe', 'client', 'voir');
		if (!$canSeeOtherSalesReps && (int) $salesrep_id !== (int) $apiUser->id) {
			throw new RestException(403, 'Not allowed to read clients of another sales representative');
		}

		$limit = ((int) $limit > 0 && (int) $limit <= 500) ? (int) $limit : 100;
		$page = max(0, (int) $page);

		$sql = "SELECT DISTINCT s.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
		$sql .= " WHERE s.entity IN (".getEntity('societe').")";

		if ((int) $client_type === 1) {
			$sql .= " AND (s.client = 1 OR s.client = 3)";
		} elseif ((int) $client_type === 2) {
			$sql .= " AND (s.client = 2 OR s.client = 3)";
		} else {
			$sql .= " AND s.client > 0";
		}

		$sql .= " AND sc.fk_user = ".((int) $salesrep_id);
		$sql .= " ORDER BY s.rowid ASC";
		$sql .= $this->db->plimit($limit, $page * $limit);

		$result = $this->db->query($sql);

		if (!$result) {
			throw new RestException(500, 'Error when fetching clients: '.$this->db->lasterror());
		}

		$clients = array();
		while ($obj = $this->db->fetch_object($result)) {
			$soc = new Societe($this->db);
			if ($soc->fetch($obj->rowid) > 0) {
				if (!DolibarrApi::_checkAccessToResource('societe', $soc->id, 'societe')) {
					continue;
				}
				$clients[] = $this->_cleanObjectDatas($soc);
			}
		}

		return $clients;
	}

	/**
	 * List proposals with linked orders/shipping status and last modification user.
	 *
	 * @param string $status       Optional proposal status filter (draft|validated|signed|notsigned|billed|all)
	 * @param int    $salesrep_id  Optional sales rep id filter (defaults to current user when visibility is limited)
	 * @param int    $page         Page number (0-based)
	 * @param int    $limit        Number of rows per page (max 200)
	 *
	 * @url GET proposals/with-orders
	 *
	 * @return array
	 * @throws RestException
	 */
	public function getProposalsWithOrders($status = 'all', $salesrep_id = 0, $page = 0, $limit = 50)
	{
		global $db, $langs;

		$apiUser = DolibarrApiAccess::$user;
		if (!$apiUser) {
			throw new RestException(401, 'Authentication required');
		}
		if (!$apiUser->hasRight('propal', 'lire')) {
			throw new RestException(403, 'No permission to read proposals');
		}

		$limit = ((int) $limit > 0 && (int) $limit <= 200) ? (int) $limit : 50;
		$page = max(0, (int) $page);

		$statusMap = array(
			'draft' => Propal::STATUS_DRAFT,
			'validated' => Propal::STATUS_VALIDATED,
			'signed' => Propal::STATUS_SIGNED,
			'notsigned' => Propal::STATUS_NOTSIGNED,
			'billed' => Propal::STATUS_BILLED
		);

		$sql = "SELECT p.rowid, p.fk_user_modif, p.fk_user_valid, p.fk_user_author";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";

		$needJoinSalesRep = false;
		$effectiveSalesRepId = 0;

		if (!empty($salesrep_id)) {
			$needJoinSalesRep = true;
			$effectiveSalesRepId = (int) $salesrep_id;
		}

		// Restrict visibility when user cannot see all customers
		if (!$apiUser->hasRight('societe', 'client', 'voir') && empty($apiUser->socid)) {
			$needJoinSalesRep = true;
			if (empty($effectiveSalesRepId)) {
				$effectiveSalesRepId = (int) $apiUser->id;
			}
		}

		if ($needJoinSalesRep) {
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = p.fk_soc";
		}

		$sql .= " WHERE p.entity IN (".getEntity('propal').")";

		if ($apiUser->socid) {
			$sql .= " AND p.fk_soc = ".((int) $apiUser->socid);
		}

		if ($needJoinSalesRep && $effectiveSalesRepId > 0) {
			$sql .= " AND sc.fk_user = ".((int) $effectiveSalesRepId);
		}

		if (!empty($status) && $status !== 'all') {
			$statusKey = strtolower($status);
			if (isset($statusMap[$statusKey])) {
				$sql .= " AND p.fk_statut = ".((int) $statusMap[$statusKey]);
			}
		}

		$sql .= " ORDER BY p.rowid DESC";
		$sql .= $db->plimit($limit, $page * $limit);

		$resql = $db->query($sql);
		if (!$resql) {
			throw new RestException(500, 'Error when fetching proposals: '.$db->lasterror());
		}

		$results = array();
		while ($obj = $db->fetch_object($resql)) {
			$propal = new Propal($db);
			// Load lines too
			$fetchRes = $propal->fetch((int) $obj->rowid, '', '', '', '', '', '', 1);
			if ($fetchRes <= 0) {
				continue;
			}

			if (!DolibarrApi::_checkAccessToResource('propal', $propal->id, 'propal')) {
				continue;
			}

			$linkedOrders = $this->loadOrdersForPropal($propal->id);
			$lastModifierId = 0;
			if (!empty($obj->fk_user_modif)) {
				$lastModifierId = (int) $obj->fk_user_modif;
			} elseif (!empty($obj->fk_user_valid)) {
				$lastModifierId = (int) $obj->fk_user_valid;
			} elseif (!empty($obj->fk_user_author)) {
				$lastModifierId = (int) $obj->fk_user_author;
			} elseif (!empty($propal->fk_user_modif)) {
				$lastModifierId = (int) $propal->fk_user_modif;
			} elseif (!empty($propal->fk_user_valid)) {
				$lastModifierId = (int) $propal->fk_user_valid;
			} elseif (!empty($propal->fk_user_author)) {
				$lastModifierId = (int) $propal->fk_user_author;
			}

			$lastModifiedBy = $this->loadUserSummary($lastModifierId);

			$clean = $this->_cleanObjectDatas($propal);
			$clean->linked_orders = $linkedOrders;
			$clean->last_modified_by = $lastModifiedBy;

			$results[] = $clean;
		}

		return $results;
	}

	/**
	 * Load linked orders for a proposal with delivery status info.
	 *
	 * @param int $propalId
	 * @return array
	 */
	private function loadOrdersForPropal($propalId)
	{
		global $db;

		$data = array();
		$sql = "SELECT c.rowid, c.ref, c.fk_statut";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_element as ee";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."commande as c ON c.rowid = ee.fk_target";
		$sql .= " WHERE ee.sourcetype = 'propal'";
		$sql .= " AND ee.targettype = 'commande'";
		$sql .= " AND ee.fk_source = ".((int) $propalId);
		$sql .= " AND c.entity IN (".getEntity('commande').")";

		$resql = $db->query($sql);
		if (!$resql) {
			return $data;
		}

		while ($obj = $db->fetch_object($resql)) {
			$status = (int) $obj->fk_statut;
			$deliveryState = 'pending';
			if ($status === Commande::STATUS_SHIPMENTONPROCESS) {
				$deliveryState = 'in_delivery';
			} elseif ($status >= Commande::STATUS_CLOSED) {
				$deliveryState = 'delivered';
			} elseif ($status === Commande::STATUS_CANCELED) {
				$deliveryState = 'canceled';
			}

			// Check access to order
			if (!DolibarrApi::_checkAccessToResource('commande', $obj->rowid, 'commande')) {
				continue;
			}

			$data[] = array(
				'id' => (int) $obj->rowid,
				'ref' => $obj->ref,
				'status' => $status,
				'delivery_state' => $deliveryState,
				'shipments' => $this->loadShipmentsForOrder((int) $obj->rowid)
			);
		}

		return $data;
	}

	/**
	 * Load minimal user info for last modification.
	 *
	 * @param int $userId
	 * @return array|null
	 */
	private function loadUserSummary($userId)
	{
		if (empty($userId)) {
			return null;
		}

		$usr = new User($this->db);
		if ($usr->fetch($userId, '', '', 0, -1) <= 0) {
			return null;
		}

		return array(
			'id' => (int) $usr->id,
			'login' => $usr->login,
			'lastname' => $usr->lastname,
			'firstname' => $usr->firstname,
			'email' => $usr->email
		);
	}

	/**
	 * Load shipments linked to an order with delivery details.
	 *
	 * @param int $orderId
	 * @return array
	 */
	private function loadShipmentsForOrder($orderId)
	{
		global $db;

		$data = array();

		$sql = "SELECT e.rowid, e.ref, e.fk_statut, e.date_expedition, e.date_delivery, e.fk_user_author, e.fk_user_valid,";
		$sql .= " e.fk_shipping_method, e.tracking_number, sm.libelle as shipping_method";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_element as ee";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expedition as e ON e.rowid = ee.fk_target";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_shipment_mode as sm ON sm.rowid = e.fk_shipping_method";
		$sql .= " WHERE ee.sourcetype = 'commande'";
		$sql .= " AND ee.targettype = 'shipping'";
		$sql .= " AND ee.fk_source = ".((int) $orderId);
		$sql .= " AND e.entity IN (".getEntity('expedition').")";

		$resql = $db->query($sql);
		if (!$resql) {
			return $data;
		}

		while ($obj = $db->fetch_object($resql)) {
			$data[] = array(
				'id' => (int) $obj->rowid,
				'ref' => $obj->ref,
				'status' => (int) $obj->fk_statut,
				'date_expedition' => $db->jdate($obj->date_expedition),
				'date_delivery' => $db->jdate($obj->date_delivery),
				'shipping_method' => $obj->shipping_method,
				'tracking_number' => $obj->tracking_number,
				'author' => $this->loadUserSummary((int) $obj->fk_user_author),
				'validated_by' => $this->loadUserSummary((int) $obj->fk_user_valid)
			);
		}

		return $data;
	}
}
