<?php
/**
 * @package     WT JoomShopping SW Projects
 * @version     1.0.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Factory;
FormHelper::loadFieldClass('list');
class JFormFieldJshoppingorderstatus extends JFormFieldList
{

	protected $type = 'jshoppingorderstatus';

	protected function getOptions()
	{
		if(file_exists(JPATH_SITE."/components/com_jshopping/jshopping.php")){
			$lang = Factory::getLanguage();
			$current_lang = $lang->getTag();
			$db = JFactory::getDBO();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('name_'.$current_lang));
			$query->select($db->quoteName('status_id'))
				->from($db->quoteName('#__jshopping_order_status'));
			$db->setQuery($query);
			$order_statuses = $db->loadAssocList();
			$name = 'name_'.$current_lang;
			$options = array();
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
?>