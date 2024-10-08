<?php
/**
 *  @package       WT JShopping Bitrix 24 PRO
 *  @copyright Copyright Sergey Tolkachyov
 *  @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Extension\Wt_jshopping_b24_pro;

return new class () implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$subject = $container->get(DispatcherInterface::class);
				$config  = (array) PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
				$plugin = new Wt_jshopping_b24_pro($subject, $config);
				$plugin->setApplication(Factory::getApplication());
				$plugin->setDatabase($container->get(DatabaseInterface::class));
				return $plugin;
			}
		);
	}
};