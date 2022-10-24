<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     2.1.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0
 */
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SpacerField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Text;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;

FormHelper::loadFieldClass('spacer');
class B24CatalogDocumentModeStatusField extends SpacerField
{

	protected $type = 'B24CatalogDocumentModeStatus';

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
		if (PluginHelper::isEnabled('system', 'wt_jshopping_b24_pro') === true)
		{
			$plugin          = PluginHelper::getPlugin('system', 'wt_jshopping_b24_pro');
			$params          = (!empty($plugin->params) ? json_decode($plugin->params) : '');
			$crm_host        = (!empty($params->crm_host) ? $params->crm_host : '');
			$webhook_secret  = (!empty($params->crm_webhook_secret) ? $params->crm_webhook_secret : '');
			$crm_assigned_id = (!empty($params->crm_assigned) ? $params->crm_assigned : '');

			if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
			{

				$resultBitrix24 = CRest::call("catalog.document.mode.status", []);
				$info =  'Для получения статуса складского учета (включен / выключен) нужно создать вебхук с правами администратора.';
echo '<pre>';
print_r($resultBitrix24);
echo '</pre>';
				if (isset($resultBitrix24["result"]))
				{

					$info = '<div class="webhook-info row">
                        <div class="col-1 overflow-hidden img-thumbnail" style="background: url(\'' . $resultBitrix24["result"]["PERSONAL_PHOTO"] . '\') no-repeat center; background-size:cover"></div>
                        <div class="col-11">
	                        <p><strong>Assigned:</strong> ' . $resultBitrix24["result"]["NAME"] . ' ' . $resultBitrix24["result"]["LAST_NAME"] . ' 
	                        <strong>Assigned ID:</strong> ' . $resultBitrix24["result"]["ID"] . ' <strong>Is admin:</strong> ' . $is_admin . '</p>
						</div>

                    </div>';

				}
				elseif (isset($resultBitrix24['error']))
				{
					$info = '</div><div class="webhook-info col-12"><strong>Bitrix 24 Error</strong> <p>' . $resultBitrix24['error'] . '</p><p>' . $resultBitrix24['error_description'] . '</p></div><div>';
				}
			}
		}
		else
		{
			$info = '';
		}
//		$info = "123";

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