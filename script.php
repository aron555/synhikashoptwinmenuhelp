<?php
/**
 * @package    synhikashoptwinmenuhelp
 * @author     Dmitry Tsymbal <cymbal@delo-design.ru>
 * @copyright  Copyright © 2019 Delo Design. All rights reserved.
 * @license    GNU General Public License version 3 or later; see license.txt
 * @link       https://delo-design.ru
 */

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerAdapter;

defined('_JEXEC') or die;

/**
 * Synhikashoptwinmenuhelp script file.
 *
 * @package   synhikashoptwinmenuhelp
 * @since     1.0.0
 */
class plgSystemSynhikashoptwinmenuhelpInstallerScript
{
	/**
	 * Constructor
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function __construct(JAdapterInstance $adapter) {}

	/**
	 * Called before any type of action
	 *
	 * @param   string  $route  Which action is happening (install|uninstall|discover_install|update)
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($route, JAdapterInstance $adapter) {}

	/**
	 * Runs right after any installation action.
	 *
	 * @param   string            $type    Type of PostFlight action. Possible values are:
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  1.0.0
	 */
	function postflight($type, $parent)
	{
		// Enable plugin
		if ($type == 'install')
		{
			$this->enablePlugin($parent);
		}

	}

	/**
	 * Called on installation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function install(JAdapterInstance $adapter) {}

	/**
	 * Called on update
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function update(JAdapterInstance $adapter) {}

	/**
	 * Called on uninstallation
	 *
	 * @param   JAdapterInstance  $adapter  The object responsible for running this script
	 */
	public function uninstall(JAdapterInstance $adapter) {}

	/**
	 * Enable plugin after installation.
	 *
	 * @param   InstallerAdapter  $parent  Parent object calling object.
	 *
	 * @since  1.0.0
	 */
	protected function enablePlugin($parent)
	{
		// Prepare plugin object
		$plugin          = new stdClass();
		$plugin->type    = 'plugin';
		$plugin->element = $parent->getElement();
		$plugin->folder  = (string) $parent->getParent()->manifest->attributes()['group'];
		$plugin->enabled = 1;

		// Update record
		Factory::getDbo()->updateObject('#__extensions', $plugin, array('type', 'element', 'folder'));
	}

}
