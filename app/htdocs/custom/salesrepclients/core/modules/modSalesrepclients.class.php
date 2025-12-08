<?php
/**
 * Module descriptor for Salesrepclients
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module Salesrepclients
 */
class modSalesrepclients extends DolibarrModules
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;
		$this->numero = 1909672100; // Unique id for the module
		$this->rights_class = 'salesrepclients';
		$this->family = "interface";
		$this->module_position = '91';
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Expose an API to list customers by sales representative";
		$this->descriptionlong = "Custom REST endpoint to retrieve customers attached to a sales representative";
		$this->version = '1.0';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'generic';

		$this->module_parts = array(
			'api' => 1,
			'moduleforexternal' => 0
		);

		// Config pages
		$this->config_page_url = array();

		// Dependencies
		$this->hidden = false;
		$this->depends = array('modApi', 'modSociete', 'modPropale', 'modCommande', 'modExpedition');
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
		$this->rights[$r][1] = 'Read clients by sales representative';
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

		$result = $this->_load_tables('/salesrepclients/sql/');

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
