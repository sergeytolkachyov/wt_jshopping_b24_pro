<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     2.1.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Text;
FormHelper::loadFieldClass('spacer');
class JFormFieldB24info extends JFormFieldSpacer
{

	protected $type = 'b24info';

	/**
	 * Method to get the field input markup for a spacer.
	 * The spacer does not have accept input.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		return ' ';
	}

	/**
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{
		$plugin = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
		$params = (!empty($plugin->params) ? json_decode($plugin->params) : '');
		$crm_host = (!empty($params->crm_host) ? $params->crm_host : '');
		$webhook_secret = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
		$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');
		$info = "123";

		return $info;
	}

	/**
	 * Method to get the field title.
	 *
	 * @return  string  The field title.
	 *
	 * @since   1.7.0
	 */
	protected function getTitle()
	{
		return $this->getLabel();
	}

	/**
	 * Method to get a control group with label and input.
	 *
	 * @param   array  $options  Options to be passed into the rendering of the field
	 *
	 * @return  string  A string containing the html for the control group
	 *
	 * @since   3.7.3
	 */
	public function renderField($options = array())
	{
		$options['class'] = empty($options['class']) ? 'field-spacer' : $options['class'] . ' field-spacer';

		return parent::renderField($options);
	}
}
?>