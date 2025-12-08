<?php
/**
 * Module descriptor for Customer Return
 * Manages customer returns with stock increase and credit note generation
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modCustomerreturn extends DolibarrModules
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

        // Set module directories in conf (needed for PDF generation)
        if (!isset($conf->customerreturn)) {
            $conf->customerreturn = new stdClass();
        }
        $conf->customerreturn->enabled = 1;
        $conf->customerreturn->dir_output = DOL_DATA_ROOT.'/customerreturn';
        $conf->customerreturn->dir_temp = DOL_DATA_ROOT.'/customerreturn/temp';

        $this->numero = 122189950;
        $this->rights_class = 'customerreturn';
        $this->family = "products";
        $this->module_position = '90';
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Gestion des retours clients";
        $this->descriptionlong = "Ce module permet de gérer les retours clients avec génération de bons de retour, augmentation du stock et création d'avoirs client.";
        $this->editor_name = 'Custom';
        $this->editor_url = '';
        $this->version = '1.0.0';
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        $this->picto = 'dollyrevert';

        // Module parts - hooks and models
        $this->module_parts = array(
            'hooks' => array(
                'thirdpartycard',
                'expeditioncard'
            ),
            'models' => 1
        );

        // Directories
        $this->dirs = array(
            '/customerreturn/temp'
        );

        // Config pages
        $this->config_page_url = array("setup.php@customerreturn");

        // Dependencies - none required, stock and invoice are optional
        $this->depends = array();
        $this->requiredby = array();
        $this->conflictwith = array();

        // Constants
        $this->const = array(
            array('CUSTOMERRETURN_ADDON', 'chaine', 'mod_customerreturn_standard', 'Numbering module for customer returns', 0, 'current', 1),
            array('CUSTOMERRETURN_AUTO_CREDIT_NOTE', 'chaine', '1', 'Automatically create credit note on validation', 0, 'current', 1),
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

        // Read permission
        $this->rights[$r][0] = 1221899501;
        $this->rights[$r][1] = 'Lire les bons de retour';
        $this->rights[$r][2] = 'r';
        $this->rights[$r][3] = 1; // Default enabled
        $this->rights[$r][4] = 'read';
        $this->rights[$r][5] = '';
        $r++;

        // Create permission
        $this->rights[$r][0] = 1221899502;
        $this->rights[$r][1] = 'Créer/modifier les bons de retour';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $this->rights[$r][5] = '';
        $r++;

        // Validate permission
        $this->rights[$r][0] = 1221899503;
        $this->rights[$r][1] = 'Valider les bons de retour';
        $this->rights[$r][2] = 'w';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'validate';
        $this->rights[$r][5] = '';
        $r++;

        // Delete permission
        $this->rights[$r][0] = 1221899504;
        $this->rights[$r][1] = 'Supprimer les bons de retour';
        $this->rights[$r][2] = 'd';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'delete';
        $this->rights[$r][5] = '';
        $r++;

        // Menus
        $this->menu = array();
        $r = 0;

        // Top menu
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=products',
            'type' => 'left',
            'titre' => 'Retours clients',
            'prefix' => img_picto('', 'dollyrevert', 'class="paddingright pictofixedwidth"'),
            'mainmenu' => 'products',
            'leftmenu' => 'customerreturn',
            'url' => '/custom/customerreturn/list.php',
            'langs' => 'customerreturn@customerreturn',
            'position' => 100,
            'enabled' => 'isModEnabled("customerreturn")',
            'perms' => '$user->hasRight("customerreturn", "read")',
            'user' => 0
        );
        $r++;

        // Submenu - New return
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=products,fk_leftmenu=customerreturn',
            'type' => 'left',
            'titre' => 'Nouveau retour',
            'mainmenu' => 'products',
            'leftmenu' => 'customerreturn_new',
            'url' => '/custom/customerreturn/card.php?action=create',
            'langs' => 'customerreturn@customerreturn',
            'position' => 101,
            'enabled' => 'isModEnabled("customerreturn")',
            'perms' => '$user->hasRight("customerreturn", "write")',
            'user' => 0
        );
        $r++;

        // Submenu - List
        $this->menu[$r] = array(
            'fk_menu' => 'fk_mainmenu=products,fk_leftmenu=customerreturn',
            'type' => 'left',
            'titre' => 'Liste des retours',
            'mainmenu' => 'products',
            'leftmenu' => 'customerreturn_list',
            'url' => '/custom/customerreturn/list.php',
            'langs' => 'customerreturn@customerreturn',
            'position' => 102,
            'enabled' => 'isModEnabled("customerreturn")',
            'perms' => '$user->hasRight("customerreturn", "read")',
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
        global $conf;

        $result = $this->_load_tables('/customerreturn/sql/');
        if ($result < 0) {
            return -1;
        }

        // Create output directory
        $dir = DOL_DATA_ROOT.'/customerreturn';
        if (!is_dir($dir)) {
            dol_mkdir($dir);
        }

        // Set dir_output in conf
        $conf->customerreturn = new stdClass();
        $conf->customerreturn->dir_output = $dir;

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
