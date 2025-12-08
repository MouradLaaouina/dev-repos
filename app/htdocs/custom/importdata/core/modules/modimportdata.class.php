<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \defgroup   importdata     Module ImportData
 * \brief      Module d'import de données (clients, produits, commandes, salariés, etc.)
 * \file       core/modules/modimportdata.class.php
 * \ingroup    importdata
 * \brief      Descripteur du module ImportData
 */

include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module ImportData
 */
class modimportdata extends DolibarrModules
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique)
		$this->numero = 1909672000;

		// Key text used to identify module
		$this->rights_class = 'importdata';

		$this->editor_name = 'Utopios';
		$this->editor_url = 'https://www.utopios.ma';

		// Family
		$this->family = "technic";

		// Module label
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description
		$this->description = "Import avancé de données avec support des extrafields";

		// Module version
		$this->version = '1.0';

		// Key used in llx_const table
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Module icon
		$this->picto = 'importdata@importdata';

		// Module parts
		$this->module_parts = array(
			'triggers' => 0,
			'css' => array('/importdata/css/importdata.css'),
			'js' => array('/importdata/js/importdata.js'),
		);

		// Data directories
		$this->dirs = array(
			'/importdata/temp',
			'/importdata/import',
			'/importdata/logs'
		);

		// Config pages
		$this->config_page_url = array("importdata_setup.php@importdata");

		// Dependencies
		$this->hidden = false;
		$this->depends = array();
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->phpmin = array(7, 0);
		$this->need_dolibarr_version = array(14, 0);
		$this->langfiles = array("importdata@importdata");

		// Constants
		$this->const = array();

		// Tabs
		$this->tabs = array();

		// Dictionaries
		$this->dictionaries = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Lire les imports';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Créer/Modifier les imports';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Supprimer les imports';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

		// Main menu entries
		$this->menu = array();
		$r = 0;

		// Menu principal dans Tools
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools',
			'type' => 'left',
			'titre' => 'ImportData',
			'leftmenu' => 'importdata',
			'url' => '/importdata/index.php',
			'langs' => 'importdata@importdata',
			'position' => 100,
			'enabled' => '$conf->importdata->enabled',
			'perms' => '$user->rights->importdata->read',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Import Clients
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'ImportThirdparties',
			'url' => '/importdata/import/thirdparties.php',
			'langs' => 'importdata@importdata',
			'position' => 101,
			'enabled' => '$conf->importdata->enabled',
			'perms' => '$user->rights->importdata->write',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Import Produits
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'ImportProducts',
			'url' => '/importdata/import/products.php',
			'langs' => 'importdata@importdata',
			'position' => 102,
			'enabled' => '$conf->importdata->enabled',
			'perms' => '$user->rights->importdata->write',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Import Commandes
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'ImportOrders',
			'url' => '/importdata/import/orders.php',
			'langs' => 'importdata@importdata',
			'position' => 103,
			'enabled' => '$conf->importdata->enabled',
			'perms' => '$user->rights->importdata->write',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Import Salariés
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'ImportEmployees',
			'url' => '/importdata/import/employees.php',
			'langs' => 'importdata@importdata',
			'position' => 104,
			'enabled' => '$conf->importdata->enabled',
			'perms' => '$user->rights->importdata->write',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Import Entrepôts
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'ImportWarehouses',
			'url' => '/importdata/import/warehouses.php',
			'langs' => 'importdata@importdata',
			'position' => 105,
			'enabled' => '$conf->importdata->enabled && $conf->stock->enabled',
			'perms' => '$user->rights->importdata->write',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Import Mouvements de Stock
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'ImportStockMovements',
			'url' => '/importdata/import/stockmovements.php',
			'langs' => 'importdata@importdata',
			'position' => 106,
			'enabled' => '$conf->importdata->enabled && $conf->stock->enabled',
			'perms' => '$user->rights->importdata->write',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Configuration
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools,fk_leftmenu=importdata',
			'type' => 'left',
			'titre' => 'Setup',
			'url' => '/importdata/admin/importdata_setup.php',
			'langs' => 'importdata@importdata',
			'position' => 110,
			'enabled' => '$conf->importdata->enabled',
			'perms' => '$user->admin',
			'target' => '',
			'user' => 2
		);
		$r++;
	}
}
