<?php
/* Copyright (C) 2024 Utopios
 *
 * Licence payante - Tous droits réservés
 * L'utilisation de ce module nécessite un accord commercial avec Utopios
 *
 * Pour plus d'informations, contactez: contact@utopios.ma
 */

/**
 * \defgroup   exportpaie     Module ExportPaie
 * \brief      Module d'export des données de paie vers CNSS et CIMR
 * \file       htdocs/exportpaie/core/modules/modexportpaie.class.php
 * \ingroup    exportpaie
 * \brief      Fichier de description et activation du module ExportPaie
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module ExportPaie
 */
class modexportpaie extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		$this->numero = 1909671900;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'exportpaie';

		$this->editor_name = 'Utopios';
		$this->editor_url = 'https://www.utopios.ma';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		$this->family = "hr";

		// Module label
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description
		$this->description = "Export des données de paie vers CNSS et CIMR";

		// Module version
		$this->version = '1.0';

		// Key used in llx_const table to save module status enabled/disabled
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;

		// Module icon
		$this->picto = 'exportpaie@exportpaie';

		// Module parts
		$this->module_parts = array(
			'triggers' => 0,
			'css' => array('/exportpaie/css/exportpaie.css'),
			'js' => array('/exportpaie/js/exportpaie.js'),
		);

		// Data directories to create when module is enabled
		$this->dirs = array(
			'/exportpaie/temp',
			'/exportpaie/export'
		);

		// Config pages
		$this->config_page_url = array("exportpaie_setup.php@exportpaie");

		// Dependencies
		$this->hidden = false;
		$this->depends = array('modpaiedolibarr');
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->phpmin = array(5, 0);
		$this->need_dolibarr_version = array(3, 0);
		$this->langfiles = array("exportpaie@exportpaie");

		// Constants
		$this->const = array();

		// Tabs
		$this->tabs = array();

		// Dictionaries
		if (!isset($conf->exportpaie->enabled)) {
			$conf->exportpaie = new stdClass();
			$conf->exportpaie->enabled = 0;
		}
		$this->dictionaries = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$r = 0;

		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Lire les exports de paie';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Créer/Modifier les exports de paie';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = $this->numero + $r;
		$this->rights[$r][1] = 'Supprimer les exports de paie';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

		// Main menu entries
		$this->menu = array();
		$r = 0;

		// Menu principal dans HRM
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=hrm',
			'type' => 'left',
			'titre' => 'ExportPaie',
			'leftmenu' => 'exportpaie',
			'url' => '/exportpaie/index.php',
			'langs' => 'exportpaie@exportpaie',
			'position' => 300,
			'enabled' => '$conf->exportpaie->enabled',
			'perms' => '$user->rights->exportpaie->read',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Export CNSS
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=exportpaie',
			'type' => 'left',
			'titre' => 'ExportCNSS',
			'url' => '/exportpaie/cnss/index.php',
			'langs' => 'exportpaie@exportpaie',
			'position' => 301,
			'enabled' => '$conf->exportpaie->enabled',
			'perms' => '$user->rights->exportpaie->read',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Export CIMR
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=exportpaie',
			'type' => 'left',
			'titre' => 'ExportCIMR',
			'url' => '/exportpaie/cimr/index.php',
			'langs' => 'exportpaie@exportpaie',
			'position' => 302,
			'enabled' => '$conf->exportpaie->enabled',
			'perms' => '$user->rights->exportpaie->read',
			'target' => '',
			'user' => 2
		);
		$r++;

		// Sous-menu Configuration
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=hrm,fk_leftmenu=exportpaie',
			'type' => 'left',
			'titre' => 'Setup',
			'url' => '/exportpaie/admin/exportpaie_setup.php',
			'langs' => 'exportpaie@exportpaie',
			'position' => 310,
			'enabled' => '$conf->exportpaie->enabled',
			'perms' => '$user->admin',
			'target' => '',
			'user' => 2
		);
		$r++;
	}

	/**
	 * Function called when module is enabled.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/exportpaie/sql/');

		return $this->_init(array(), $options);
	}

	/**
	 * Function called when module is disabled.
	 *
	 * @param string $options Options when enabling module ('', 'noboxes')
	 * @return int 1 if OK, 0 if KO
	 */
	function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
