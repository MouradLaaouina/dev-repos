<?php
/**
 * API for CategoryDiscount module
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
dol_include_once('/categorydiscount/class/categorydiscountrule.class.php');

/**
 * CategoryDiscount REST endpoints
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class CategorydiscountApi extends DolibarrApi
{
    /** @var CategoryDiscountRule */
    private $discountRule;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $db;

        $this->db = $db;
        $this->discountRule = new CategoryDiscountRule($this->db);
    }

    /**
     * Get discount for a product and client
     *
     * Returns the best matching discount rule for a given product and client.
     * The discount is based on product categories and can be client-specific or generic.
     *
     * @param int $product_id   Product ID
     * @param int $client_id    Client/Thirdparty ID
     * @return array            Discount information
     *
     * @url GET discount/{product_id}/{client_id}
     *
     * @throws RestException
     */
    public function getDiscount($product_id, $client_id)
    {
        global $conf;

        $apiUser = DolibarrApiAccess::$user;
        if (!$apiUser) {
            throw new RestException(401);
        }

        $product_id = (int) $product_id;
        $client_id = (int) $client_id;

        if ($product_id <= 0) {
            throw new RestException(400, 'product_id is required');
        }
        if ($client_id <= 0) {
            throw new RestException(400, 'client_id is required');
        }

        $rule = $this->discountRule->fetchBestRuleForProduct($client_id, $product_id, $conf->entity);

        if ($rule) {
            return array(
                'has_discount' => true,
                'discount_percent' => (float) $rule['discount_percent'],
                'rule_id' => (int) $rule['id'],
                'is_client_specific' => ($rule['fk_soc'] > 0),
                'category_id' => (int) $rule['fk_categorie']
            );
        }

        return array(
            'has_discount' => false,
            'discount_percent' => 0,
            'rule_id' => null,
            'is_client_specific' => false,
            'category_id' => null
        );
    }

    /**
     * Get discounts for multiple products and a client
     *
     * Returns the best matching discount rules for multiple products and a single client.
     * Useful for checking discounts for an entire cart.
     *
     * @param array $request_data   Request data with product_ids and client_id
     * @return array                Array of discount information per product
     *
     * @url POST discounts/batch
     *
     * @throws RestException
     */
    public function getDiscountsBatch($request_data = null)
    {
        global $conf;

        $apiUser = DolibarrApiAccess::$user;
        if (!$apiUser) {
            throw new RestException(401);
        }

        $productIds = isset($request_data['product_ids']) ? $request_data['product_ids'] : array();
        $clientId = isset($request_data['client_id']) ? (int) $request_data['client_id'] : 0;

        if (empty($productIds) || !is_array($productIds)) {
            throw new RestException(400, 'product_ids array is required');
        }
        if ($clientId <= 0) {
            throw new RestException(400, 'client_id is required');
        }

        $results = array();

        foreach ($productIds as $productId) {
            $productId = (int) $productId;
            if ($productId <= 0) {
                continue;
            }

            $rule = $this->discountRule->fetchBestRuleForProduct($clientId, $productId, $conf->entity);

            if ($rule) {
                $results[$productId] = array(
                    'has_discount' => true,
                    'discount_percent' => (float) $rule['discount_percent'],
                    'rule_id' => (int) $rule['id'],
                    'is_client_specific' => ($rule['fk_soc'] > 0),
                    'category_id' => (int) $rule['fk_categorie']
                );
            } else {
                $results[$productId] = array(
                    'has_discount' => false,
                    'discount_percent' => 0,
                    'rule_id' => null,
                    'is_client_specific' => false,
                    'category_id' => null
                );
            }
        }

        return array(
            'client_id' => $clientId,
            'discounts' => $results
        );
    }
}
