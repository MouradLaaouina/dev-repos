<?php
/**
 * Module descriptor for Customer Balance / Financial Status
 * Provides a unified view of customer financial status including:
 * - Balance (invoices, payments)
 * - Payments received
 * - Credit notes / Returns
 * - Outstanding amount (encours)
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modCustomerbalance extends DolibarrModules
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

        // Set module directories in conf
        if (!isset($conf->customerbalance)) {
            $conf->customerbalance = new stdClass();
        }
        $conf->customerbalance->enabled = 1;
        $conf->customerbalance->dir_output = DOL_DATA_ROOT.'/customerbalance';

        $this->numero = 122189960;
        $this->rights_class = 'customerbalance';
        $this->family = "financial";
        $this->module_position = '91';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Etat financier client";
        $this->descriptionlong = "Ce module permet de visualiser l'état financier complet d'un client : balance, encaissements, avoirs/retours et encours dans une seule vue avec export PDF.";
        $this->editor_name = 'Custom';
        $this->editor_url = '';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'bill';

        // Module parts - hooks and models
        $this->module_parts = array(
            'models' => 1
        );

        // Directories
        $this->dirs = array(
            '/customerbalance'
        );

        // Config pages
        $this->config_page_url = array();

        // Dependencies
        $this->depends = array('modFacture');
        $this->requiredby = array();
        $this->conflictwith = array();

        // Constants
        $this->const = array();

        // Tabs - Add financial status tab on thirdparty card
        $this->tabs = array(
            'thirdparty:+financialstatus:EtatFinancier:customerbalance@customerbalance:$user->hasRight("customerbalance", "read"):/custom/customerbalance/financialstatus.php?socid=__ID__'
        );

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        // Read permission
        $this->rights[$r][0] = 1221899601;
        $this->rights[$r][1] = 'Consulter l\'état financier client';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1; // Default enabled
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = '';
        $r++;

        // Export PDF permission
        $this->rights[$r][0] = 1221899602;
        $this->rights[$r][1] = 'Exporter l\'état financier en PDF';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1;
        $this->rights[$r][4] = 'export';
        $this->rights[$r][5] = '';
        $r++;

        // Menus
        $this->menu = array();
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options when enabling module
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        global $conf;

        // Create output directory
        $dir = DOL_DATA_ROOT.'/customerbalance';
        if (!is_dir($dir)) {
            dol_mkdir($dir);
        }

        $sql = array();
        return $this->_init($sql, $options);
    }

    /**
     * Function called when module is disabled
     *
     * @param string $options Options when disabling module
     * @return int 1 if OK, 0 if KO
     */
    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
