<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     2.5.2
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       2.2.0
 */
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;
FormHelper::loadFieldClass('list');
class B24ldealcategoryField extends ListField
{

	protected $type = 'B24ldealcategory';

	protected function getOptions()
	{
		if(PluginHelper::isEnabled('system', 'wt_jshopping_b24_pro') === true)
		{
			$plugin          = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
			$params          = (!empty($plugin->params) ? json_decode($plugin->params) : '');
			$crm_host        = (!empty($params->crm_host) ? $params->crm_host : '');
			$webhook_secret  = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
			$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');
			if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
			{
//				include_once(JPATH_SITE . "/plugins/system/wt_jshopping_b24_pro/lib/crest.php");

				$params = [
					'filter' => [
						'IS_LOCKED' => 'N'
					],
					'order'  => [
						'SORT' => 'ASC'
					],
					'select' => [
						"ID", "NAME"
					],
				];

				$get_deal_categories_params = [
					'get_default_deal_category' => [
						'method' => 'crm.dealcategory.default.get',
						'params' => [
							'filter' => [
								'IS_LOCKED' => 'N'
							],
							'order'  => [
								'SORT' => 'ASC'
							],
							'select' => [
								"ID", "NAME"
							],
						],
					],
					'get_deal_category'         => [
						'method' => 'crm.dealcategory.list'
					]
				];
				$get_deal_categories = CRest::callBatch($get_deal_categories_params);

				if(isset($get_deal_categories["result"])){
					$deal_categories = array($get_deal_categories["result"]["result"]["get_default_deal_category"]);
					$deal_categories = array_merge($deal_categories, $get_deal_categories["result"]["result"]["get_deal_category"]);

					$options = array();
					if (!empty($deal_categories))
					{
						foreach ($deal_categories as $deal_category)
						{
							$options[] = HTMLHelper::_('select.option', $deal_category["ID"], $deal_category["NAME"]);
						}
					}

					return $options;
				}
			}
		}
	}
}
?>