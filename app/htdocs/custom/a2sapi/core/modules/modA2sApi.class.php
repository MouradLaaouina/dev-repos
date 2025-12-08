<?php
/**
 * Module A2S API
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Module A2S API descriptor class
 */
class modA2sApi extends DolibarrModules
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        global $langs, $conf;

        $this->db = $db;
        $this->numero = 500000;
        $this->rights_class = 'a2sapi';
        $this->family = "technic";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "A2S Custom API endpoints";
        $this->descriptionlong = "Custom API endpoints for A2S B2B application";
        $this->editor_name = 'A2S';
        $this->editor_url = '';
        $this->version = '1.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'generic';

        $this->module_parts = array(
            'moduleforexternal' => 0
        );

        // Config pages
        $this->config_page_url = array();

        // Dependencies
        $this->hidden = false;
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();
        $this->langfiles = array();

        // Constants
        $this->const = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        $this->rights[$r][0] = $this->numero + $r;
        $this->rights[$r][1] = 'Read A2S API';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;

        // Main menu entries
        $this->menu = array();
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf, $langs;

        $result = $this->_load_tables('/a2sapi/sql/');

        if ($result < 0) {
            return -1;
        }

        return $this->_init(array(), $options);
    }

    /**
     * Function called when module is disabled
     *
     * @param string $options Options
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        return $this->_remove($options);
    }
}
