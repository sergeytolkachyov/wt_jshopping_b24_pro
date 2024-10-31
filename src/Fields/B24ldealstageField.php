<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.2.1
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       2.2.0
 */
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;
use Joomla\CMS\Form\Field\ListField;

FormHelper::loadFieldClass('list');
class B24ldealstageField extends ListField
{

	protected $type = 'B24ldealstage';

	protected function getOptions()
	{
		if(PluginHelper::isEnabled('system', 'wt_jshopping_b24_pro') === true)
		{
			$plugin          = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
			$params          = (!empty($plugin->params) ? json_decode($plugin->params) : '');
			$crm_host        = (!empty($params->crm_host) ? $params->crm_host : '');
			$webhook_secret  = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
			$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');
			$deal_category   = (!empty($params->deal_category) ? $params->deal_category : '');

			if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
			{
//				include_once(JPATH_SITE . "/plugins/system/wt_jshopping_b24_pro/lib/crest.php");


				if (!empty($deal_category))
				{
					$resultBitrix24 = CRest::call("crm.dealcategory.stage.list", ["id" => $deal_category]);
				}
				else
				{
					$b24_params     = [
						'filter' => [
							'ENTITY_ID' => 'DEAL_STAGE'
						],
						'order'  => [
							'SORT' => 'ASC'
						]
					];
					$resultBitrix24 = CRest::call("crm.status.list", $b24_params);
				}

				$options = array();
				if (isset($resultBitrix24["result"]))
				{
					foreach ($resultBitrix24["result"] as $lead_status)
					{
						$options[] = HTMLHelper::_('select.option', $lead_status["STATUS_ID"], $lead_status["NAME"]);
					}

					return $options;

				} elseif (isset($resultBitrix24['error'])){
					Factory::getApplication()->enqueueMessage($resultBitrix24['error']." ".$resultBitrix24['error_description'],'error');
					return $options;
				}

			}
		}
	}
}
?>