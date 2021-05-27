<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     2.5.2
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since   1.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
FormHelper::loadFieldClass('list');
class JFormFieldB24leadstatus extends JFormFieldList
{

	protected $type = 'b24leadstatus';

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
				include_once(JPATH_SITE . "/plugins/system/wt_jshopping_b24_pro/lib/crest.php");

				$params         = [
					'filter' => [
						'ENTITY_ID' => 'STATUS'
					],
					'order'  => [
						'SORT' => 'ASC'
					]
				];
				$resultBitrix24 = CRest::call("crm.status.list", $params);
				if(isset($resultBitrix24["result"])){
					$options = array();

					foreach ($resultBitrix24["result"] as $lead_status)
					{
						$options[] = HTMLHelper::_('select.option', $lead_status["STATUS_ID"], $lead_status["NAME"]);
					}

					return $options;
				}

				return;
				}
		}
	}
}
?>