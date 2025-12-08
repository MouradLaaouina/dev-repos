<?php
/**
 * Hooks for PDF TTC Only module
 * Modifies PDF generation to show only TTC amounts
 */

class ActionsPdfttconly
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
     * @var string Return string for print
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
     * Hook to modify PDF before creation
     *
     * @param array $parameters Hook parameters
     * @param object $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function beforePDFCreation($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        $elementType = $object->element ?? '';

        if ($elementType == 'propal' && !getDolGlobalString('PDFTTCONLY_ENABLED_PROPAL')) {
            return 0;
        }
        if ($elementType == 'commande' && !getDolGlobalString('PDFTTCONLY_ENABLED_ORDER')) {
            return 0;
        }
        if (($elementType == 'expedition' || $elementType == 'delivery') && !getDolGlobalString('PDFTTCONLY_ENABLED_SHIPMENT')) {
            return 0;
        }

        $conf->global->PDFTTCONLY_ACTIVE = 1;

        return 0;
    }

    /**
     * Hook called after PDF creation
     *
     * @param array $parameters Hook parameters
     * @param object $object Object
     * @param string $action Action
     * @param HookManager $hookmanager Hook manager
     * @return int 0=OK, <0=KO
     */
    public function afterPDFCreation($parameters, &$object, &$action, $hookmanager)
    {
        global $conf;

        unset($conf->global->PDFTTCONLY_ACTIVE);

        return 0;
    }
}
