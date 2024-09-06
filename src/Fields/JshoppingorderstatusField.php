<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.1.4
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0.0
 */
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;

FormHelper::loadFieldClass('list');
class JshoppingorderstatusField extends ListField
{

	protected $type = 'Jshoppingorderstatus';

	protected function getOptions()
	{
		if(file_exists(JPATH_SITE."/components/com_jshopping/bootstrap.php")){
			$lang = Factory::getApplication()->getLanguage();
			$current_lang = $lang->getTag();
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);
			$query->select($db->quoteName('name_'.$current_lang));
			$query->select($db->quoteName('status_id'))
				->from($db->quoteName('#__jshopping_order_status'));
			$db->setQuery($query);
			$order_statuses = $db->loadAssocList();
			$name = 'name_'.$current_lang;
			$options = [];
			if (!empty($order_statuses))
			{
				foreach ($order_statuses as $order_status)
				{
					$options[] = HTMLHelper::_('select.option', $order_status["status_id"], $order_status[$name]);
				}
			}

			return $options;
		}

	}
}
