<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     3.1.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       3.1.0
 */

namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Uri\Uri;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;
use Joomla\CMS\Form\Field\ListField;


class B24catalogproductsvariationsField extends FormField
{

	protected $type = 'B24catalogproductsvariations';

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                        For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
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
				$doc->addScriptOptions('wt_jshopping_b24_pro',[
					'crm_host' => $crm_host,
					'webhook_secret' => $webhook_secret,
					'crm_assigned_id' => $crm_assigned_id,
					'modal_id' => $this->__get('id')
				]);
				$languages   = LanguageHelper::getContentLanguages(array(0, 1), false);
				// Load language
				Factory::getApplication()->getLanguage()->load('wt_jshopping_b24_pro', JPATH_ADMINISTRATOR);

//				echo 'catalog.store.amount';
//				$resultBitrix24 = CRest::call("catalog.store.amount", ['ELEMENT_ID' => 118]);

//				$resultBitrix24 = CRest::call("crm.product.list", []);
				// получение цены товара по ID товара в б24
				// $resultBitrix24 = CRest::call("catalog.price.list", ['filter' => ['productId' => 13]]);


				// Список товаров https://dev.1c-bitrix.ru/rest_help/catalog/product/catalog_product_list.php

				// catalog.product.get(id) - https://dev.1c-bitrix.ru/rest_help/catalog/product/catalog_product_get.php
				// в нём есть iblockId для catalog.product.getFieldsByFilter

//
//				$resultBitrix24 = CRest::call("crm.product.get", [
//					'id' => 118
//				]);
//				echo '<pre><h4>crm.product.get - Товар id 118</h4>';
//				print_r($resultBitrix24);
//				echo '</pre>';
//
//				$resultBitrix24 = CRest::call("catalog.product.get", [
//					'id' => 118
//				]);
//				echo '<pre><h4>catalog.product.get - Товар id 118</h4>';
//				print_r($resultBitrix24);
//				echo '</pre>';
//
//
//				$product_iblockId = $resultBitrix24['result']['product']['iblockId'];
//				$product_type = $resultBitrix24['result']['product']['type'];
//				// https://dev.1c-bitrix.ru/rest_help/catalog/product/catalog_product_getfieldsbyfilter.php
//				$resultBitrix24 = CRest::call("catalog.product.getFieldsByFilter",[
//					'filter' =>
//                       [
//	                       'iblockId' => $product_iblockId,
//	                       'productType' => $product_type
//                       ]
//				]);
//
//
//				echo '<pre><h4>Поля товара по фильтру</h4>';
//				print_r($resultBitrix24);
//				echo '</pre>';
//
//				$resultBitrix24 = CRest::call("catalog.document.element.list",[
//					'filter' =>
//                       [
//	                       'id' => 54,
//                       ]
//				]);
//
//
//				echo '<pre><h4>Товары накладной с id 54</h4><p>catalog.document.element.list</p>';
//				print_r($resultBitrix24);
//				echo '</pre>';

$html = '<div class="input-group">

    	    <input type="text" 
    	    class="form-control"
    	    name="'.$this->__get('name').'"
    	    id="'.$this->__get('id').'"
    	    '.(($this->__get('required') == true) ? 'required="'.$this->__get('required').'"' : '').'" 
    	    value="'.($this->__get('value') ? $this->__get('value') : '').'"/>

           <button type="button" class="btn btn-success button-select" data-bs-toggle="modal" 
           data-field-bitrix24-products data-bs-target="#bitrix24_products_field_' . $this->__get('id').'">'. Text::_('JLIB_FORM_BUTTON_SELECT').'</button>
	    </div>
	    
	    <script>
			  window.addEventListener("message", function (e) {
				const data = JSON.parse(e.data);
				  var source = e.source;
				  let b24_product_id_input_field = document.getElementById("'.$this->__get('id').'");
				  b24_product_id_input_field.value = data.b24ProductId; 
				  
			  });
		</script>
	   	    
	    '; //attr_row_1

				$url = Uri::root()."index.php?option=com_ajax&plugin=wt_jshopping_b24_pro&group=system&action=getBitrix24ProductsVariations&format=raw";
				$modalHTML = HTMLHelper::_(
					'bootstrap.renderModal',
					'bitrix24_products_field_' . $this->__get('id') ,
					[
//						'url'         => $url,
						'title'       => Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_LIST_FIELD_MODAL_HEADER'),
						'closeButton' => true,
						'height'      => '100%',
						'width'       => '100%',
						'modalWidth'  => '80',
						'bodyHeight'  => '60',
						'footer' => '<div class="btn-group" id="bitrix24_products_field_' . $this->__get('id').'_product_pagination"></div>',
						'class' => 'overflow-scroll'
					],
					'<table class="table table-hover table-striped" id="bitrix24_products_field_' . $this->__get('id').'_product_table">
						  <thead>
						    <tr>
						      <td>Название</td>
						      <td>Кнопка</td>
						    </tr>
						  </thead>
						  <tbody>
						    <!-- данные будут вставлены сюда -->
						  </tbody>
						</table>
						
						<template id="bitrix24_products_field_' . $this->__get('id').'_productrow">
						  <tr>
						    <td></td>
						    <td></td>
						    <td></td>
						  </tr>
						</template>'

				);
//						'footer'      => '<button type="button" class="btn btn-success button-save-selected">' . Text::_('JSELECT') . '</button><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>',
				Factory::getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript('plg_system_wt_jshopping_b24_pro.B24crmproductsField','plg_system_wt_jshopping_b24_pro/B24crmproductsField.js')
					->addInlineStyle('#bitrix24_products_field_' . $this->__get('id').' .modal-body {overflow-y: scroll; overflow-x:none;}');
				return $html.$modalHTML;
//				$options = array();
//				if (isset($resultBitrix24["result"]))
//				{
//					foreach ($resultBitrix24["result"] as $lead_status)
//					{
//						$options[] = HTMLHelper::_('select.option', $lead_status["STATUS_ID"], $lead_status["NAME"]);
//					}
//
//					return $options;
//
//				}
//				elseif (isset($resultBitrix24['error']))
//				{
//					Factory::getApplication()->enqueueMessage($resultBitrix24['error'] . " " . $resultBitrix24['error_description'], 'error');
//
//					return $options;
//				}

			} else {
				return '';
			}
		}
	}
}

?>