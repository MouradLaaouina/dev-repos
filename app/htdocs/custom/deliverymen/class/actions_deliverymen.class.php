<?php
/**
 * Hooks for Deliverymen module
 * Adds deliveryman assignment on shipment cards
 */

class ActionsDeliverymen
{
    /**
     * @var DoliDB Database handler
     */
    private $db;

    /**
     * @var string Error message
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();

    /**
     * @var array Results from hooks
     */
    public $results = array();

    /**
     * @var string Return string
     */
    public $resprints = '';

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
     * Add deliveryman info in shipment form
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function formObjectOptions($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = isset($parameters['context']) ? $parameters['context'] : '';

        // Only on shipment/expedition card
        if (strpos($context, 'expeditioncard') === false && strpos($context, 'shipmentcard') === false) {
            return 0;
        }

        // Check module
        if (!isModEnabled('deliverymen')) {
            return 0;
        }

        // Check if object is an Expedition
        if (!is_object($object) || get_class($object) != 'Expedition') {
            return 0;
        }

        // Load translations
        $langs->load('deliverymen@deliverymen');

        // Get current assignment
        dol_include_once('/deliverymen/class/deliverymenshipment.class.php');
        $assignment = new DeliverymenShipment($this->db);
        $hasAssignment = ($assignment->fetchByExpedition($object->id) > 0);

        // Get deliveryman info
        $deliverymanName = '';
        $deliveryDate = '';
        $deliveryStatus = '';
        if ($hasAssignment) {
            require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
            $deliveryman = new User($this->db);
            $deliveryman->fetch($assignment->fk_user_deliveryman);
            $deliverymanName = $deliveryman->getFullName($langs);
            $deliveryDate = $assignment->delivery_date ? dol_print_date($assignment->delivery_date, 'day') : '';
            $deliveryStatus = DeliverymenShipment::getStatusBadge($assignment->delivery_status);
        }

        $out = '';

        // Display deliveryman row
        $out .= '<tr class="field_deliveryman">';
        $out .= '<td class="titlefield">'.$langs->trans("Deliveryman").'</td>';
        $out .= '<td>';

        if ($hasAssignment) {
            $out .= '<span class="badge badge-info">'.$deliverymanName.'</span>';
            if ($deliveryDate) {
                $out .= ' - '.$langs->trans("DeliveryDate").': '.$deliveryDate;
            }
            $out .= ' '.$deliveryStatus;

            // Link to change
            if ($user->hasRight('deliverymen', 'assign')) {
                $url = dol_buildpath('/deliverymen/assign.php', 1).'?id='.$object->id;
                $out .= ' <a href="'.$url.'" class="paddingleft">'.img_picto($langs->trans("Modify"), 'edit').'</a>';
            }
        } else {
            $out .= '<span class="opacitymedium">'.$langs->trans("NoDeliverymanAssigned").'</span>';

            // Link to assign
            if ($user->hasRight('deliverymen', 'assign')) {
                $url = dol_buildpath('/deliverymen/assign.php', 1).'?id='.$object->id;
                $out .= ' <a href="'.$url.'" class="butAction small">'.$langs->trans("AssignDeliveryman").'</a>';
            }
        }

        $out .= '</td>';
        $out .= '</tr>';

        $this->resprints = $out;

        return 0;
    }

    /**
     * Add more buttons on shipment card
     *
     * @param array $parameters Hook parameters
     * @param CommonObject $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $langs, $user;

        $context = isset($parameters['context']) ? $parameters['context'] : '';

        // Only on shipment/expedition card
        if (strpos($context, 'expeditioncard') === false && strpos($context, 'shipmentcard') === false) {
            return 0;
        }

        // Check module and permissions
        if (!isModEnabled('deliverymen') || !$user->hasRight('deliverymen', 'assign')) {
            return 0;
        }

        // Check if object is an Expedition
        if (!is_object($object) || get_class($object) != 'Expedition') {
            return 0;
        }

        // Load translations
        $langs->load('deliverymen@deliverymen');

        // Get current assignment
        dol_include_once('/deliverymen/class/deliverymenshipment.class.php');
        $assignment = new DeliverymenShipment($this->db);
        $hasAssignment = ($assignment->fetchByExpedition($object->id) > 0);

        $url = dol_buildpath('/deliverymen/assign.php', 1).'?id='.$object->id;

        if (!$hasAssignment) {
            print '<a class="butAction" href="'.$url.'">'.$langs->trans("AssignDeliveryman").'</a>';
        }

        return 0;
    }
}
