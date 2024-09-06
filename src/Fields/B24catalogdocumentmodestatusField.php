<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.2.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0
 */
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Text;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;

FormHelper::loadFieldClass('spacer');
class B24catalogdocumentmodestatusField extends NoteField
{

	protected $type = 'B24catalogdocumentmodestatus';

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
				if($resultBitrix24['result'] == 'Y'){
					// Включен складской учёт.
					$info = '</div><div class="alert alert-success py-1 my-0 col-12">'.Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_CATALOG_DOCUMENT_MODE_STATUS_FIELD_Y').'</div><div>';

				} elseif($resultBitrix24['result'] == 'N') {
					$info = '</div><div class="alert alert-warning py-1 my-0 col-12">'.Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_CATALOG_DOCUMENT_MODE_STATUS_FIELD_N').'</div><div>';
				} elseif (isset($resultBitrix24['error']))
				{
					$info = '</div><div class="alert alert-danger my-0 col-12"><strong>Bitrix 24 Error</strong> <p>' . $resultBitrix24['error'] . '</p><p>' . $resultBitrix24['error_description'] . '</p></div><div>';
				}

			}
		}
		else
		{
			$info = '';
		}


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