<?php
/**
 * Module descriptor for Customer Check
 * Displays warnings before order/proposal validation for unpaid checks and credit limit exceeded
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modCustomercheck extends DolibarrModules
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

        $this->numero = 122189800;
        $this->rights_class = 'customercheck';
        $this->family = "financial";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Vérifications client avant validation";
        $this->descriptionlong = "Ce module affiche des avertissements avant la validation des commandes et devis : chèques impayés et dépassement d'encours.";
        $this->editor_name = 'Custom';
        $this->editor_url = '';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'warning';

        // Module parts - hooks for order and proposal validation + thirdparty card
        $this->module_parts = array(
            'hooks' => array(
                'propalcard',
                'ordercard',
                'thirdpartycard'
            )
        );

        // Config pages
        $this->config_page_url = array("setup.php@customercheck");

        // Dependencies
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        // Constants
        $this->const = array(
            array('CUSTOMERCHECK_ENABLE_RETURNED_CHECKS', 'chaine', '1', 'Enable warning for returned checks', 0, 'current', 1),
            array('CUSTOMERCHECK_ENABLE_CREDIT_LIMIT', 'chaine', '1', 'Enable warning for credit limit exceeded', 0, 'current', 1),
        );

        // Tabs
        $this->tabs = array();

        // Dictionaries
        $this->dictionaries = array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        // Permission to validate despite unpaid checks
        $this->rights[$r][0] = 1221898001; // Permission id
        $this->rights[$r][1] = 'Valider malgré chèques impayés'; // Permission label
        $this->rights[$r][2] = 'w'; // Permission type (w=write)
        $this->rights[$r][3] = 0; // Not default
        $this->rights[$r][4] = 'validatealifornia'; // Permission object
        $this->rights[$r][5] = ''; // Sub-permission
        $r++;

        // Permission to lift unpaid check sanction
        $this->rights[$r][0] = 1221898002; // Permission id
        $this->rights[$r][1] = 'Lever la sanction chèque impayé'; // Permission label
        $this->rights[$r][2] = 'w'; // Permission type (w=write)
        $this->rights[$r][3] = 0; // Not default
        $this->rights[$r][4] = 'liftsanction'; // Permission object
        $this->rights[$r][5] = ''; // Sub-permission
        $r++;

        // Menus
        $this->menu = array();
        $r = 0;

        // Menu for unpaid checks management
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=billing',
            'type' => 'left',
            'titre' => 'Chèques impayés',
            'prefix' => img_picto('', 'warning', 'class="paddingright pictofixedwidth"'),
            'mainmenu' => 'billing',
            'leftmenu' => 'customercheck_returnedchecks',
            'url' => '/custom/customercheck/returnedchecks_list.php',
            'langs' => 'customercheck@customercheck',
            'position' => 100,
            'enabled' => 'isModEnabled("customercheck")',
            'perms' => '1',
            'user' => 0
        );
        $r++;
    }

    /**
     * Function called when module is enabled
     *
     * @param string $options Options when enabling module
     * @return int 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
        $result = $this->_load_tables('/customercheck/sql/');
        if ($result < 0) {
            return -1;
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
