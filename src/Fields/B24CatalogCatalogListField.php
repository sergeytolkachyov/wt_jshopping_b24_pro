<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.2.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       3.0.0
 */

namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');

class B24CatalogCatalogListField extends ListField
{

	protected $type = 'B24CatalogCatalogList';

	protected function getOptions()
	{
		if (PluginHelper::isEnabled('system', 'wt_jshopping_b24_pro') === true)
		{
			$plugin          = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
			$params          = (!empty($plugin->params) ? json_decode($plugin->params) : '');
			$crm_host        = (!empty($params->crm_host) ? $params->crm_host : '');
			$webhook_secret  = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
			$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');

			if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
			{
				// Список товарных каталогов
				$resultBitrix24 = CRest::call("catalog.catalog.list", [
					'select' => [
						"id", "name", "iblockTypeId", "iblockId","skuPropertyId"
					]
				]);

				$options = [];
				if (isset($resultBitrix24["result"]))
				{
					foreach ($resultBitrix24['result']['catalogs'] as $catalog)
					{
						$options[] = HTMLHelper::_('select.option', $catalog["iblockId"], $catalog["name"]);
					}
				}
				elseif (isset($resultBitrix24['error']))
				{
					$options[] = HTMLHelper::_('select.option', '0', Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_CATALOGCATALOGLIST_FIELD_WRONG_CRM_SCOPE'));
				}

				return $options;
			}
		}
	}
}

?>