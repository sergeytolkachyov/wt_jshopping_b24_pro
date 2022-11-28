<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     3.1.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       3.0.0
 */

namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;

class B24crmproductsField extends FormField
{

	protected $type = 'B24crmproducts';

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   \SimpleXMLElement  $element   The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value     The form field value to validate.
	 * @param   string             $group     The field name group control value. This acts as an array container for the field.
	 *                                        For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                        full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     FormField::setup()
	 * @since   3.7.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		return parent::setup($element, $value, $group);
	}

	protected function getInput()
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
				$doc = Factory::getApplication()->getDocument();
				$doc->addScriptOptions('wt_jshopping_b24_pro', [
					'crm_host'        => $crm_host,
					'webhook_secret'  => $webhook_secret,
					'crm_assigned_id' => $crm_assigned_id,
					'modal_id'        => $this->__get('id')
				]);
				$languages = LanguageHelper::getContentLanguages(array(0, 1), false);
				// Load language
				Factory::getApplication()->getLanguage()->load('wt_jshopping_b24_pro', JPATH_ADMINISTRATOR);


				$html = '<div class="input-group">

    	    <input type="text" 
    	    class="form-control"
    	    name="' . $this->__get('name') . '"
    	    id="' . $this->__get('id') . '"
    	    ' . (($this->__get('required') == true) ? 'required="' . $this->__get('required') . '"' : '') . '" 
    	    value="' . ($this->__get('value') ? $this->__get('value') : '') . '"/>

           <button type="button" class="btn btn-success button-select" data-bs-toggle="modal" 
           data-field-bitrix24-products data-bs-target="#bitrix24_products_field_' . $this->__get('id') . '">' . Text::_('JLIB_FORM_BUTTON_SELECT') . '</button>
	    </div>
	    
	    <script>
			  window.addEventListener("message", function (e) {
				const data = JSON.parse(e.data);
				  var source = e.source;
				  let b24_product_id_input_field = document.getElementById("' . $this->__get('id') . '");
				  b24_product_id_input_field.value = data.b24ProductId; 
				  
			  });
		</script>
	   	    
	    ';

				$url       = Uri::root() . "index.php?option=com_ajax&plugin=wt_jshopping_b24_pro&group=system&action=getBitrix24Products&format=raw";
				$modalHTML = HTMLHelper::_(
					'bootstrap.renderModal',
					'bitrix24_products_field_' . $this->__get('id'),
					[
//						'url'         => $url,
						'title'       => Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_LIST_FIELD_MODAL_HEADER'),
						'closeButton' => true,
						'height'      => '100%',
						'width'       => '100%',
						'modalWidth'  => '80',
						'bodyHeight'  => '60',
						'footer'      => '<div class="btn-group" id="bitrix24_products_field_' . $this->__get('id') . '_product_pagination"></div>',
						'class'       => 'overflow-scroll'
					],
					'<table class="table table-hover table-striped" id="bitrix24_products_field_' . $this->__get('id') . '_product_table">
						  <thead>
						    <tr>
						      <td>' . Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_LIST_FIELD_MODAL_TABLE_HEAD_NAME') . '</td>
						      <td></td>
						    </tr>
						  </thead>
						  <tbody>
						    <!-- данные будут вставлены сюда -->
						  </tbody>
						</table>
						
						<template id="bitrix24_products_field_' . $this->__get('id') . '_productrow">
						  <tr>
						    <td></td>
						    <td></td>
						    <td></td>
						  </tr>
						</template>'

				);
				Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript('plg_system_wt_jshopping_b24_pro.B24crmproductsField', 'plg_system_wt_jshopping_b24_pro/B24crmproductsField.js')
					->addInlineStyle('#bitrix24_products_field_' . $this->__get('id') . ' .modal-body {overflow-y: scroll; overflow-x:none;}');

				return $html . $modalHTML;
			}
			else
			{
				return '';
			}
		}
	}
}

?>