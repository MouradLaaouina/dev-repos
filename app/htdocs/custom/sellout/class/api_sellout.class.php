<?php
/**
 * API for Sellout module
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
dol_include_once('/sellout/class/sellout.class.php');

/**
 * Sellout REST endpoints
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class SelloutApi extends DolibarrApi
{
    /** @var SelloutSale */
    private $repository;

    /** @var Societe */
    private $thirdparty;

    /** @var Product */
    private $product;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;

        $this->db = $db;
        $this->repository = new SelloutSale($this->db);
        $this->thirdparty = new Societe($this->db);
        $this->product = new Product($this->db);
    }

    /**
     * Create a sellout sale entry
     *
     * @param array $request_data   Request data
     * @return array
     *
     * @url POST sales
     *
     * @throws RestException
     */
    public function postSale($request_data = null)
    {
        global $conf;

        $apiUser = DolibarrApiAccess::$user;
        if (!$apiUser) {
            throw new RestException(401);
        }
        if (empty($apiUser->rights->sellout->write)) {
            throw new RestException(403, 'Permission denied to create sellout sales');
        }

        $socid = isset($request_data['thirdparty_id']) ? (int) $request_data['thirdparty_id'] : (int) ($request_data['socid'] ?? 0);
        $productId = isset($request_data['product_id']) ? (int) $request_data['product_id'] : (int) ($request_data['fk_product'] ?? 0);
        $qty = isset($request_data['qty']) ? price2num($request_data['qty'], 'MS') : 1;
        $unitPrice = isset($request_data['unit_price']) ? price2num($request_data['unit_price'], 'MS') : 0;
        $currency = isset($request_data['currency_code']) ? $request_data['currency_code'] : (isset($request_data['currency']) ? $request_data['currency'] : '');
        $source = isset($request_data['source']) ? dol_trunc(trim($request_data['source']), 50) : '';
        $note = isset($request_data['note']) ? trim($request_data['note']) : '';
        $salesrepId = !empty($request_data['salesrep_id']) ? (int) $request_data['salesrep_id'] : (int) $apiUser->id;
        $saleDateInput = isset($request_data['sale_date']) ? $request_data['sale_date'] : (isset($request_data['date']) ? $request_data['date'] : '');
        $locationLabel = isset($request_data['location_label']) ? dol_trunc(trim($request_data['location_label']), 255) : '';
        $locationLat = isset($request_data['location_latitude']) ? $request_data['location_latitude'] : (isset($request_data['latitude']) ? $request_data['latitude'] : '');
        $locationLng = isset($request_data['location_longitude']) ? $request_data['location_longitude'] : (isset($request_data['longitude']) ? $request_data['longitude'] : '');

        if ($socid <= 0) {
            throw new RestException(400, 'thirdparty_id is required');
        }
        if ($productId <= 0) {
            throw new RestException(400, 'product_id is required');
        }
        if ($qty === null || $qty === '' || $qty <= 0) {
            throw new RestException(400, 'qty must be greater than zero');
        }
        if (!empty($apiUser->socid) && $apiUser->socid != $socid) {
            throw new RestException(403, 'You cannot create sellout sales for another thirdparty');
        }

        if ($this->thirdparty->fetch($socid) <= 0) {
            throw new RestException(404, 'Thirdparty not found');
        }
        if (!DolibarrApi::_checkAccessToResource('societe', $this->thirdparty->id, 'societe')) {
            throw new RestException(403, 'Access to this thirdparty is denied');
        }

        if ($this->product->fetch($productId) <= 0) {
            throw new RestException(404, 'Product not found');
        }
        if (!DolibarrApi::_checkAccessToResource('product', $this->product->id)) {
            throw new RestException(403, 'Access to this product is denied');
        }

        if ($salesrepId <= 0) {
            $salesrepId = (int) $apiUser->id;
        }
        if (!empty($apiUser->socid)) {
            $salesrepId = (int) $apiUser->id;
        }

        $saleDate = $this->resolveDateValue($saleDateInput);
        $currencyCode = $currency !== '' ? strtoupper(substr($currency, 0, 3)) : $conf->currency;
        $latValue = $this->parseCoordinate($locationLat, -90, 90, 'location_latitude');
        $lngValue = $this->parseCoordinate($locationLng, -180, 180, 'location_longitude');

        $data = array(
            'entity' => $conf->entity,
            'fk_user' => $salesrepId,
            'fk_soc' => $this->thirdparty->id,
            'fk_product' => $this->product->id,
            'qty' => $qty,
            'unit_price' => $unitPrice,
            'currency_code' => $currencyCode,
            'location_label' => $locationLabel,
            'location_latitude' => $latValue,
            'location_longitude' => $lngValue,
            'sale_date' => $saleDate,
            'source' => $source,
            'note' => $note,
            'datec' => dol_now()
        );

        $id = $this->repository->create($data);
        if ($id < 0) {
            throw new RestException(500, 'Error when creating sellout sale: '.$this->repository->error);
        }

        $sale = $this->repository->fetch($id);
        if ($sale === -1) {
            throw new RestException(500, 'Sellout sale was created but could not be reloaded');
        }
        if ($sale === 0) {
            throw new RestException(404, 'Sellout sale not found after creation');
        }

        return $this->formatSale($sale);
    }

    /**
     * Get a sellout sale by id
     *
     * @param int $id
     * @return array
     *
     * @url GET sales/{id}
     *
     * @throws RestException
     */
    public function getSale($id)
    {
        $apiUser = DolibarrApiAccess::$user;
        if (!$apiUser) {
            throw new RestException(401);
        }
        if (empty($apiUser->rights->sellout->read)) {
            throw new RestException(403, 'Permission denied to read sellout sales');
        }

        $sale = $this->repository->fetch($id);
        if ($sale === -1) {
            throw new RestException(500, 'Error when fetching sellout sale: '.$this->repository->error);
        }
        if ($sale === 0) {
            throw new RestException(404, 'Sellout sale not found');
        }

        if (!$this->canAccessSale($sale)) {
            throw new RestException(403, 'Access to this sellout sale is denied');
        }

        return $this->formatSale($sale);
    }

    /**
     * List sellout sales with optional filters
     *
     * @param int    $socid        Thirdparty id filter
     * @param int    $product_id   Product id filter
     * @param int    $salesrep_id  Sales rep/user id filter
     * @param string $date_from    Start date (timestamp or string)
     * @param string $date_to      End date (timestamp or string)
     * @param int    $page         Page number (0-based)
     * @param int    $limit        Page size (max 500)
     * @return array
     *
     * @url GET sales
     *
     * @throws RestException
     */
    public function listSales($socid = 0, $product_id = 0, $salesrep_id = 0, $date_from = '', $date_to = '', $page = 0, $limit = 50)
    {
        $apiUser = DolibarrApiAccess::$user;
        if (!$apiUser) {
            throw new RestException(401);
        }
        if (empty($apiUser->rights->sellout->read)) {
            throw new RestException(403, 'Permission denied to read sellout sales');
        }

        if (!empty($apiUser->socid)) {
            if (!empty($socid) && (int) $socid !== (int) $apiUser->socid) {
                throw new RestException(403, 'You cannot list sellout sales for another thirdparty');
            }
            $socid = (int) $apiUser->socid;
        }

        $limit = $this->sanitizeLimit($limit, 50, 500);
        $page = max(0, (int) $page);
        $offset = $page * $limit;

        $filters = array(
            'socid' => (int) $socid,
            'product_id' => (int) $product_id,
            'user_id' => 0,
            'date_from' => $this->resolveDateValue($date_from, true),
            'date_to' => $this->resolveDateValue($date_to, true)
        );

        if (!empty($salesrep_id)) {
            $filters['user_id'] = (int) $salesrep_id;
        } elseif (empty($apiUser->rights->societe->client->voir) && empty($apiUser->socid)) {
            $filters['user_id'] = (int) $apiUser->id;
        }

        $rows = $this->repository->search($filters, $limit, $offset);
        if ($rows === -1) {
            throw new RestException(500, 'Error when fetching sellout sales: '.$this->repository->error);
        }

        $result = array();
        foreach ($rows as $row) {
            if (!$this->canAccessSale($row)) {
                continue;
            }
            $result[] = $this->formatSale($row);
        }

        return $result;
    }

    /**
     * Build API payload for a sale row
     *
     * @param array $sale
     * @return array
     */
    private function formatSale(array $sale)
    {
        return array(
            'id' => $sale['id'],
            'thirdparty_id' => $sale['fk_soc'],
            'product_id' => $sale['fk_product'],
            'user_id' => $sale['fk_user'],
            'qty' => $sale['qty'],
            'unit_price' => $sale['unit_price'],
            'currency_code' => $sale['currency_code'],
            'location_label' => $sale['location_label'],
            'location_latitude' => $sale['location_latitude'],
            'location_longitude' => $sale['location_longitude'],
            'sale_date' => $sale['sale_date'],
            'sale_date_iso' => $sale['sale_date'] ? dol_print_date($sale['sale_date'], 'dayhourrfc') : '',
            'note' => $sale['note'],
            'source' => $sale['source'],
            'date_created' => $sale['datec'],
            'date_created_iso' => $sale['datec'] ? dol_print_date($sale['datec'], 'dayhourrfc') : '',
            'last_modified' => $sale['tms'],
            'last_modified_iso' => $sale['tms'] ? dol_print_date($sale['tms'], 'dayhourrfc') : ''
        );
    }

    /**
     * Check if current user can access the sale row
     *
     * @param array $sale
     * @return bool
     */
    private function canAccessSale(array $sale)
    {
        if (!DolibarrApi::_checkAccessToResource('societe', $sale['fk_soc'], 'societe')) {
            return false;
        }

        return true;
    }

    /**
     * Normalize a date filter into a timestamp
     *
     * @param mixed $value
     * @param bool  $allowEmpty
     * @return int
     */
    private function resolveDateValue($value, $allowEmpty = false)
    {
        if ($value === '' || $value === null) {
            return $allowEmpty ? 0 : dol_now();
        }

        if (is_numeric($value)) {
            $ts = (int) $value;
            if ($ts > 0) {
                return $ts;
            }
        }

        $ts = dol_stringtotime($value, 0);
        if ($ts > 0) {
            return $ts;
        }

        throw new RestException(400, 'Invalid date format');
    }

    /**
     * Clamp pagination limits
     *
     * @param int $limit
     * @param int $default
     * @param int $max
     * @return int
     */
    private function sanitizeLimit($limit, $default, $max)
    {
        $limit = (int) $limit;
        if ($limit <= 0) {
            return $default;
        }
        if ($limit > $max) {
            return $max;
        }

        return $limit;
    }

    /**
     * Parse and validate coordinate value
     *
     * @param mixed  $value
     * @param float  $min
     * @param float  $max
     * @param string $field
     * @return float|null
     * @throws RestException
     */
    private function parseCoordinate($value, $min, $max, $field)
    {
        if ($value === '' || $value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new RestException(400, $field.' must be numeric');
        }

        $num = (float) $value;
        if ($num < $min || $num > $max) {
            throw new RestException(400, $field.' must be between '.$min.' and '.$max);
        }

        return $num;
    }
}
