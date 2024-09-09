<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.2.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0.0
 */

// No direct access
namespace Joomla\Plugin\System\Wt_jshopping_b24_pro\Extension;
\defined('_JEXEC') or die;

use Joomla\Application\SessionAwareWebApplicationInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Filesystem\Folder;
use Joomla\Plugin\System\Wt_jshopping_b24_pro\Library\CRest;
use SimpleXMLElement;

class Wt_jshopping_b24_pro extends CMSPlugin implements SubscriberInterface
{
	use DatabaseAwareTrait;

	protected $autoloadLanguage = true;

	/** Header for debug element
	 * @var string $debug_section_header
	 * @since 2.4.0
	 */
	private string $debug_section_header;

	/** Debug data
	 * @var string $debug_data
	 * @since 2.4.0
	 */
	private string $debug_data;

	/** Debug HTML output
	 * @var string $debug_output
	 * @since 2.4.0
	 */
	private string $debug_output;

	/**
	 * Class Constructor
	 *
	 * @param   object  $subject
	 * @param   array   $config
	 */

	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$crm_host        = $this->params->get('crm_host');
		$webhook_secret  = $this->params->get('crm_webhook_secret');
		$crm_assigned_id = $this->params->get('crm_assigned');
		if (!empty($crm_host) && !empty($webhook_secret) && !empty($crm_assigned_id))
		{
			define('C_REST_WEB_HOOK_URL', 'https://' . $crm_host . '/rest/' . $crm_assigned_id . '/' . $webhook_secret . '/');//url on creat Webhook
		}
		else
		{
			if ($this->params->get('debug') == 1)
			{
				$this->prepareDebugInfo("Bitrix 24 connection status", Text::_("PLG_WT_JSHOPPING_B24_PRO_B24_NOT_CONNECTED"));
			}
		}

	}
	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onAfterCreateOrderFull'         => 'onAfterCreateOrderFull',
			'onAfterDispatch'                => 'onAfterDispatch',
			'onBeforeDisplayCheckoutFinish'  => 'onBeforeDisplayCheckoutFinish',
			'onBeforeSendRadicalForm'        => 'onBeforeSendRadicalForm',
			'onAjaxWt_jshopping_b24_pro'     => 'onAjaxWt_jshopping_b24_pro',
			'onDisplayProductEditTabsEndTab' => 'onDisplayProductEditTabsEndTab',
			'onDisplayProductEditTabsEnd'    => 'onDisplayProductEditTabsEnd',
			'onBeforeDisplayEditProductView' => 'onBeforeDisplayEditProductView',
		];
	}

	/**
	 * @param   string  $debug_section_header
	 * @param   mixed   $debug_data
	 *
	 *
	 * @since 2.0.0
	 */
	private function prepareDebugInfo(string $debug_section_header, $debug_data): void
	{
		// Берем сессию только в HTML фронте
		$session = ($this->getApplication() instanceof SessionAwareWebApplicationInterface ? $this->getApplication()->getSession() : null);
		if (is_array($debug_data) || is_object($debug_data))
		{
			$debug_data = print_r($debug_data, true);
		}
		$debug_output = $session->get("b24debugoutput");

		$debug_output .= "<details style='border:1px solid #0FA2E6; margin-bottom:5px;'>";
		$debug_output .= "<summary style='background-color:#384148; color:#fff;'>" . $debug_section_header . "</summary>";
		$debug_output .= "<pre style='background-color: #eee; padding:10px;'>";
		$debug_output .= $debug_data;
		$debug_output .= "</pre>";
		$debug_output .= "</details>";

		$session->set("b24debugoutput", $debug_output);

	}// END prepareDebugInfo

	/**
	 * Create an order which might be 'created' or 'not created'. 'Created' orders are displaying in JoomShopping admin panel.
	 * 'Not created' orders are hidden by filter.
	 *
	 * @param Event $event
	 *
	 * @since 2.3.0
	 */
	public function onAfterCreateOrderFull(Event $event): void
	{
		/**
		 * @var object $order  JoomShopping order object
		 * @var object $cart   JoomShopping cart object
		 */
		[$order, $cart] = $event->getArguments();

		if ($this->params->get('b24_trigger_event', 'always') == 'always')
		{
			$this->sendOrderToBitrix24($order->order_id);
		}

	}// end onAfterCreateOrderFull()

	/**
	 * Функция выполняет основную работу по добавлению заказа в Битрикс 24.
	 *
	 * @param int $orderId
	 *
	 *
	 * @since 2.6.0
	 */
	public function sendOrderToBitrix24(int $orderId): void
	{

		if (empty($orderId))
		{
			return;
		}
		if (!class_exists('JSFactory'))
		{
			require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
		}

		// Берем сессию только в HTML фронте
		$session     = ($this->getApplication() instanceof SessionAwareWebApplicationInterface ? $this->getApplication()->getSession() : null);
		$jshopConfig = \JSFactory::getConfig();
		$order       = \JSFactory::getTable('order', 'jshop');
		$order->load($orderId);
		$order->getAllItems();
		$plugin_mode = $this->params->get('lead_vs_deal');

		$qr = [
			'fields' => [],
			'params' => [
				'REGISTER_SONET_EVENT' => 'Y'
			]
		];
		if ($plugin_mode == 'deal' || ($plugin_mode == 'lead' && $this->params->get('create_contact_for_unknown_lead') == 1))
		{
			$contact    = [
				'fields' => []
			];
			$requisites = [
				'fields' => []
			];
		}

		$debug = (int) $this->params->get('debug',0);

		if ($debug == 1)
		{
			$this->prepareDebugInfo('Plugin mode', $plugin_mode);
		}

		$b24_fields = $this->params->get('fields');
		for ($i = 0; $i < count((array) $b24_fields); $i++)
		{
			$fields_num  = 'fields' . $i;
			$b24_field   = '';
			$store_field = '';
			if ($b24_fields->$fields_num->b24fieldtype == 'standart')
			{
				$b24_field = $b24_fields->$fields_num->b24fieldstandart;
				if ($b24_field == 'TITLE')
				{
					foreach ($b24_fields->$fields_num->storefield as $value)
					{

						$store_field .= $order->$value . ' ';
					}
					$store_field = $this->params->get('order_name_prefix') . $store_field;

				}
				elseif ($b24_field == 'EMAIL')
				{
					$store_field = [];

					$k = 0;
					foreach ($b24_fields->$fields_num->storefield as $value)
					{
						$email_name = "n" . $k;

						$store_field[$email_name] = [
							'VALUE'      => $order->$value,
							'VALUE_TYPE' => 'WORK'
						];
						$k++;
					}//end FOR

				}
				elseif ($b24_field == 'PHONE')
				{
					$store_field = [];

					$k = 0;
					foreach ($b24_fields->$fields_num->storefield as $value)
					{
						$phone_name = 'n' . $k;

						$store_field[$phone_name] = [
							'VALUE'      => $order->$value,
							'VALUE_TYPE' => 'WORK'
						];
						$k++;
					}//end FOR

				}
				else
				{
					// TODO: Сделать функцию, а не копировать 2 раза цикл
					foreach ($b24_fields->$fields_num->storefield as $value)
					{
						if ($value == 'country')
						{//Получаем название страны
							$store_field .= $this->getCountryName($order->$value) . ' ';
						}
						elseif ($value == 'coupon_id')
						{// Получаем код купона
							$store_field .= $this->getCouponCode($order->$value) . ' ';
						}
						elseif ($value == 'shipping_method_id')
						{//название способа доставки
							$store_field .= $order->getShippingName() . ' ';
						}
						elseif ($value == 'payment_method_id')
						{//название способа оплаты
							$store_field .= $order->getPaymentName() . ' ';
						}
						elseif ($value == 'order_status')
						{//название статуса заказа
							$store_field .= $order->getStatus() . ' ';
						}
						elseif ($value == 'birthday' and ($order->$value == '0000-00-00' || $order->$value == ""))
						{
							continue;
						}
						elseif ($value == 'wt_sm_otpravka_pochta_ru_barcode')
						{// трек-номер Почты России - WT SM Otpravka.pochta.ru
							$store_field .= $session->get('wt_sm_otpravka_pochta_ru_barcode');
						}
						else
						{
							$store_field .= $order->$value . ' ';
						}
					}
				}
			}
			elseif ($b24_fields->$fields_num->b24fieldtype == 'custom')
			{// Пользовательское поле Битрикс24
				$b24_field = $b24_fields->$fields_num->b24fieldcustom;

				foreach ($b24_fields->$fields_num->storefield as $value)
				{
					if ($value == 'country')
					{//Получаем название страны
						$store_field .= $this->getCountryName($order->$value) . ' ';
					}
					elseif ($value == 'coupon_id')
					{// Получаем код купона
						$store_field .= $this->getCouponCode($order->$value) . ' ';
					}
					elseif ($value == 'shipping_method_id')
					{//название способа доставки
						$store_field .= $order->getShippingName() . ' ';
					}
					elseif ($value == 'payment_method_id')
					{//название способа оплаты
						$store_field .= $order->getPaymentName() . ' ';
					}
					elseif ($value == 'order_status')
					{//название статуса заказа
						$store_field .= $order->getStatus() . ' ';
					}
					elseif ($value == 'wt_sm_otpravka_pochta_ru_barcode')
					{// трек-номер Почты России - WT SM Otpravka.pochta.ru
						$store_field .= $session->get('wt_sm_otpravka_pochta_ru_barcode') . ' ';
					}
					else
					{
						$store_field .= $order->$value . ' ';
					}
				}
			}

			/**
			 * Если Сделка или Лид+Контакт
			 */

			if ($plugin_mode == 'deal' || ($plugin_mode == 'lead' && $this->params->get('create_contact_for_unknown_lead') == 1))
			{
				if ($b24_field == 'NAME' || //Fields for contact
					$b24_field == 'LAST_NAME' ||
					$b24_field == 'SECOND_NAME' ||
					$b24_field == 'BIRTHDATE' ||
					$b24_field == 'PHONE' ||
					$b24_field == 'EMAIL' ||
					$b24_field == 'FAX'
				)
				{
					$contact['fields'][$b24_field] = $store_field;

				}
				elseif ($b24_field == 'ADDRESS' ||  //Fields for contact's requisites
					$b24_field == 'ADDRESS_2' ||
					$b24_field == 'ADDRESS_CITY' ||
					$b24_field == 'ADDRESS_POSTAL_CODE' ||
					$b24_field == 'ADDRESS_REGION' ||
					$b24_field == 'ADDRESS_PROVINCE' ||
					$b24_field == 'ADDRESS_COUNTRY'
				)
				{
					$requisites['fields'][$b24_field] = $store_field;
				}
				else
				{
					$qr['fields'][$b24_field] = $store_field;
				}


			}// end if deal or lead+contact
			/**
			* Если простой Лид
			*/
			else
			{
				$qr['fields'][$b24_field] = $store_field;
			}

		}//END FOR


		$qr['fields']['SOURCE_ID']          = $this->params->get('lead_source');
		$qr['fields']['SOURCE_DESCRIPTION'] = $this->params->get('source_description');

		/**
		 * Тип сделки: продажа, продажа товара, продажа услуги и т.д.
		 */

		if ($plugin_mode == 'deal')
		{
			$qr['fields']['TYPE_ID'] = $this->params->get('deal_type');

		}


		/**
		 * Товарные позиции для лида или сделки
		 */

		$product_rows = [];
		$b24_comment  = '<br/>';
		$a            = 0;

		/**
		 * Получаем таблицу соответствий товаров JoomShopping и Битрикс 24, если включено в настройках
		 */

		if ($this->params->get('b24_product_type_for_product_rows', 'commodity_items') == 'product')
		{
			$product_ids = [];
			foreach ($order->items as $item)
			{
				$product_ids[] = $item->product_id;
			}

			$jshopping_b24_products_relationship = $this->getJshoppingBitrix24ProductsRelationship($product_ids);
			$this->prepareDebugInfo('Соответствие товаров JoomSHopping и Битрикс 24',$jshopping_b24_products_relationship);
		}


		foreach ($order->items as $item)
		{

			if ($this->params->get('b24_product_type_for_product_rows', 'commodity_items') == 'product')
			{
				// Для добавления сущности товара (не товарной позции) к сделке нужно указывать ID товара в Битрикс 24.
				if ($jshopping_b24_products_relationship[$item->product_id])
				{
					$product_rows[$a]['PRODUCT_ID'] = $jshopping_b24_products_relationship[$item->product_id]['bitrix24_product_id'];
					if($jshopping_b24_products_relationship[$item->product_id]['bitrix24_product_main_variaton_id'] > 0)
					{
						// Если указана основная вариация товара Б24 для товара JoomShopping, то устанавливаем её.
						$product_rows[$a]['PRODUCT_ID'] = $jshopping_b24_products_relationship[$item->product_id]['bitrix24_product_main_variaton_id'];
					}
						//Если используются вариации товаров - находим для атрибута id вариации товара Битрикс 24
					if ($this->params->get('use_bitrix24_product_variants', 0) == 1)
					{
						$order_item_active_attr_id = $this->getJShoppingActiveProductAttributeInOrder($item->product_id,$item->attributes);

						// Приходит 0, если нет выборки
						$attr_to_variation_id = $this->getJshoppingAttrToVariationId($item->product_id,$order_item_active_attr_id['product_attr_id']);
						if ($attr_to_variation_id['b24_product_variation_id'] > 0)
						{
							$product_rows[$a]['PRODUCT_ID'] = $attr_to_variation_id['b24_product_variation_id'];
						}

					}
				}
				else
				{
					$product_rows[$a]['PRODUCT_NAME'] = $item->product_name;

				}
			}
			else
			{
				$product_rows[$a]['PRODUCT_NAME'] = $item->product_name;
			}
			$product_rows[$a]['PRICE']    = $item->product_item_price;
			$product_rows[$a]['QUANTITY'] = $item->product_quantity;


			if ($this->params->get('product_image') == 1)
			{
				$b24_comment .= HTMLHelper::image($jshopConfig->image_product_live_path . '/' . $item->thumb_image,'',['width' => '150']).'<br/>';
			}
			if ($this->params->get('product_link') == 1)
			{
				$lang          = $this->getApplication()->getLanguage()->getTag();
				$product_url   = 'index.php?option=com_jshopping&controller=product&task=view&category_id=' . $item->category_id . '&product_id=' . $item->product_id . '&lang=' . $lang;
				$defaultItemid = \JSHelper::getDefaultItemid($product_url);
				$product_url   .= $product_url . '&Itemid=' . $defaultItemid;
				$b24_comment   .= HTMLHelper::link(substr(URI::root(), 0, -1) . Route::_($product_url),$item->product_name).'<br/>';
			}
			else
			{
				$b24_comment .= $item->product_name . '<br/>';
			}
			if ($this->params->get('ean') == 1 && !empty($item->ean))
			{
				$b24_comment .= Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_LEAD_JSHOPPING_EAN') . ': ' . $item->ean . '<br/>';
			}
			if ($this->params->get('manufacturer_code') == 1 && !empty($item->manufacturer_code))
			{
				$b24_comment .= Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_LEAD_JSHOPPING_MANUFACTURER_CODE') . ': ' . $item->manufacturer_code . '<br/>';
			}
			if ($this->params->get('product_weight') == 1 && !empty($item->weight))
			{
				$b24_comment .= Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_LEAD_PRODUCT_WEIGHT') . ': ' . $item->weight . '<br/>';
			}

			if (!empty($item->product_attributes))
			{
				$b24_comment .= $item->product_attributes . '<br/>';
			}

			if (!empty($item->product_freeattributes))
			{
				$b24_comment .= $item->product_freeattributes . '<br/>';
			}

			$a++;
		}

		if ($this->params->get('jshopping_link_to_order_in_comment', '1') == 1)
		{
			$b24_comment .= HTMLHelper::link(URI::root() . 'administrator/index.php?option=com_jshopping&controller=orders&task=show&order_id=' . $order->order_id, Text::_('PLG_WT_JSHOPPING_B24_PRO_COMMENT_LINK_TO_ORDER_TEXT'));
		}

		$qr['fields']['COMMENTS'] .= $b24_comment;

		$this->checkUtms($qr);


		/**
		 * Добавление лида или сделки на определенную стадию (с определенным статусом)
		 */

		if ($this->params->get('create_lead_or_deal_on_specified_stage') == 1)
		{
			if ($plugin_mode == 'lead' && !empty($this->params->get('lead_status')))
			{
				$qr['fields']['STATUS_ID'] = $this->params->get('lead_status');
			}
			elseif ($plugin_mode == 'deal' && !empty($this->params->get('deal_stage')))
			{

				$qr['fields']['STAGE_ID']    = $this->params->get('deal_stage');
				$qr['fields']['CATEGORY_ID'] = $this->params->get('deal_category');
			}
		}


		if (!empty($this->params->get('assigned_by_id')))
		{
			$qr['fields']['ASSIGNED_BY_ID'] = $this->params->get('assigned_by_id');
		}

		if ($plugin_mode == 'deal' || ($plugin_mode == 'lead' && $this->params->get('create_contact_for_unknown_lead') == 1))
		{
			/**
			 * Ищем дубли контактов
			 *
			 */

			$search_duobles_by_phone = $contact['fields']['PHONE']['n0']['VALUE'];
			$search_duobles_by_email = $contact['fields']['EMAIL']['n0']['VALUE'];

			$find_doubles         = [
				'find_doubles_by_phone' => [
					'method' => 'crm.duplicate.findbycomm',
					'params' => [
						'type'   => 'PHONE',
						'values' => [$search_duobles_by_phone]
					],
				],
				'find_doubles_by_email' => [
					'method' => 'crm.duplicate.findbycomm',
					'params' => [
						'type'   => 'EMAIL',
						'values' => [$search_duobles_by_email]
					]
				]
			];
			$find_doublesBitrix24 = CRest::callBatch($find_doubles);

			if ($debug == 1)
			{
				$this->prepareDebugInfo('FIND_DOUBLES -> array TO BITRIX 24 with information for search duplicate contacts', $find_doubles);
				$this->prepareDebugInfo('FIND_DOUBLES <- response array FROM BITRIX 24 with information about search results for duplicate contacts', $find_doublesBitrix24);
			}


			/**
			 * Конец поиска дублей контактов
			 *
			 * Начинаем разбор.
			 * Проверяем, не пустой ли массив.
			 * Проверяем, сколько найдено совпадений. Если больше одного совпадения - всю информацию отправляем в комментарий к сделке.
			 */
			if (!empty($find_doublesBitrix24['result']['result']['find_doubles_by_phone']['CONTACT']) && !empty($find_doublesBitrix24['result']['result']['find_doubles_by_phone']['CONTACT'][0]))
			{
				if (count($find_doublesBitrix24['result']['result']['find_doubles_by_phone']['CONTACT']) > 1)
				{
					/*
					 * Если найдено больше одного совпадения по телефону
					 */
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($contact, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_1'));
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_1'));

					if ($plugin_mode == 'lead')
					{
						$this->addLead($qr, $product_rows, $debug, $order->order_id);

						return;
					}
					elseif ($plugin_mode == 'deal')
					{
						$this->addDeal($qr, $product_rows, $debug, $order->order_id);

						return;
					}


				}
				else
				{
					$b24contact_id_by_phone = $find_doublesBitrix24['result']['result']['find_doubles_by_phone']['CONTACT'][0];
				}
			}
			if (!empty($find_doublesBitrix24['result']['result']['find_doubles_by_email']['CONTACT']) && !empty($find_doublesBitrix24['result']['result']['find_doubles_by_email']['CONTACT'][0]))
			{
				if (count($find_doublesBitrix24['result']['result']['find_doubles_by_email']['CONTACT']) > 1)
				{
					/*
					 * Если найдено больше одного совпадения по email
					 */

					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($contact, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_2'));
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_2'));

					if ($plugin_mode == 'lead')
					{
						$this->addLead($qr, $product_rows, $debug, $order->order_id);

						return;
					}
					elseif ($plugin_mode == 'deal')
					{
						$this->addDeal($qr, $product_rows, $debug, $order->order_id);

						return;
					}

				}
				else
				{
					$b24contact_id_by_email = $find_doublesBitrix24['result']['result']['find_doubles_by_email']['CONTACT'][0];
				}

			}


			/**
			 *  Найдены совпадения И по email И по телефону
			 */
			if (!is_null($b24contact_id_by_email) && !is_null($b24contact_id_by_phone))
			{
				/*
				 * Проверяем, одинаковые ли CONTACT_ID при совпадении по телефону и почте
				 */
				if ($b24contact_id_by_email == $b24contact_id_by_phone)
				{
//					$qr['fields']['CONTACT_ID'] = $b24contact_id_by_email;
					$qr['fields']['CONTACT_IDS'] = [$b24contact_id_by_email];
				}
				else
				{
					/**
					 * Если CONTACT_ID разные - пишем все в комментарий к сделке/лиду.
					 */

					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($contact, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_3'));
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_3'));

				}
			}// END Найдены совпадения И по email И по телефону

			/**
			 *  У контакта совпал телефон, но не совпал email
			 */
			elseif (!is_null($b24contact_id_by_phone) && is_null($b24contact_id_by_email))
			{
				$upd_info_email = [
					'EMAIL' => [
						'n0' => [
							'VALUE' => $contact['fields']['EMAIL']['n0']['VALUE'],
							'TYPE'  => 'WORK'
						]
					]
				];


				$updateContactResult = $this->updateContact($b24contact_id_by_phone, $upd_info_email, $debug); //Добавляем в контакт EMAIL

				if ($updateContactResult == false)
				{
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($contact, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
				}
				else
				{

//					$qr['fields']['CONTACT_ID'] = $b24contact_id_by_phone;
					$qr['fields']['CONTACT_IDS'] = [$b24contact_id_by_phone];
				}
			}// END У контакта совпал телефон, но не совпал email

			/**
			 *  У контакта совпал email, но не совпал телефон
			 */
			elseif (!is_null($b24contact_id_by_email) && is_null($b24contact_id_by_phone))
			{
				$upd_info_phone = [
					'PHONE' => [
						'n0' => [
							'VALUE' => $contact['fields']['PHONE']['n0']['VALUE'],
							'TYPE'  => 'WORK'
						]
					]
				];

				$updateContactResult = $this->updateContact($b24contact_id_by_email, $upd_info_phone, $debug); //Добавляем в контакт PHONE

				if ($updateContactResult == false)
				{
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($contact, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
				}
				else
				{

//					$qr['fields']['CONTACT_ID'] = $b24contact_id_by_email;
					$qr['fields']['CONTACT_IDS'] = [$b24contact_id_by_email];
				}

			}// END У контакта совпал email, но не совпал телефон

			/**
			 *  Нет совпадений с контактами. Создаем новый контакт.
			 */
			elseif (is_null($b24contact_id_by_email) && is_null($b24contact_id_by_phone))
			{
				$b24contact_id = $this->addContact($contact, $debug); //Получаем contact id
				if ($b24contact_id == false)
				{
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($contact, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_5'));
					$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_5'));
				}
				else
				{
					$addRaddRequisitesResult = $this->addRequisites($b24contact_id, $requisites, $debug);
					/**
					 * Если ошибка добавления реквизитов
					 */
					if ($addRaddRequisitesResult == false)
					{
						/**
						 * Пишем реквизиты в комментарий
						 */
						$qr['fields']['COMMENTS'] .= $this->prepareDataToSaveToComment($requisites, Text::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_6'));
					}
					else
					{
						/**
						 * Добавляем к лиду/сделке CONTACT_ID
						 */
//						$qr['fields']['CONTACT_ID'] = $b24contact_id;
						$qr['fields']['CONTACT_IDS'] = [$b24contact_id];
					}
				}
			}


			if ($debug == 1)
			{

				$this->prepareDebugInfo('QR - order info array prepared to send to Bitrix24', $qr);
				$this->prepareDebugInfo('Product_rows - products rows array for include to lead or deal, prepared to send to Bitrix24', $product_rows);
				$this->prepareDebugInfo('Contact - contact array to send to functions (name, phone, email etc.)', $contact);
				$this->prepareDebugInfo('Requisites - Requisites array to send to functions (address, city, country etc.)', $requisites);
			}

			$qr = $this->customPreprocess('joomshopping', ['qr' => $qr, 'order' => $order, 'product_rows' => $product_rows]);

			if ($plugin_mode == 'deal')
			{
				/**
				 * Добавляем сделку
				 */
				$b24result = $this->addDeal($qr, $product_rows, $debug, $order->order_id);
			}
			elseif ($plugin_mode == 'lead' && $this->params->get('create_contact_for_unknown_lead') == 1)
			{
				/**
				 * Добавляем лид
				 */
				$b24result = $this->addLead($qr, $product_rows, $debug, $order->order_id);
			}

		}
		else
		{ // Простой лид

			$qr = $this->customPreprocess('joomshopping', ['qr' => $qr, 'order' => $order, 'product_rows' => $product_rows]);
			$b24result = $this->addLead($qr, $product_rows, $debug, $order->order_id);
		}


		if ($debug == 1)
		{
			$this->prepareDebugInfo('Bitrix24 result array', $b24result);
		}


	}



	/**
	 * Returns country name by id
	 *
	 * @param   Int  $country_id
	 *
	 * @return string
	 *
	 * @since version 1.0
	 */
	private function getCountryName($country_id)
	{
		$lang         = $this->getApplication()->getLanguage();
		$current_lang = $lang->getTag();
		$db           = $this->getDatabase();
		$query        = $db->getQuery(true);
		$query->select($db->quoteName('name_' . $current_lang))
			->from($db->quoteName('#__jshopping_countries'))
			->where($db->quoteName('country_id') . ' = ' . $db->quote($country_id));
		$db->setQuery($query);
		$country_name = $db->loadAssoc();

		return $country_name['name_' . $current_lang];
	}


	/**
	 * Returns coupon code by id
	 *
	 * @param   int  $coupon_id
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	private function getCouponCode(int $coupon_id): string
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('coupon_code'))
			->from($db->quoteName('#__jshopping_coupons'))
			->where($db->quoteName('coupon_id') . ' = ' . $db->quote($coupon_id));
		$db->setQuery($query);
		$coupon_code = $db->loadAssoc();

		return (string)$coupon_code['coupon_code'];
	}

	/**
	 * Function checks the utm marks and set its to array fields
	 *
	 * @param   array  $qr  Bitrix24 array data
	 *
	 * @return array    Bitrix24 array data with UTMs
	 * @since    2.4.1
	 */
	private function checkUtms(array &$qr): array
	{
		$utms = [
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_content',
			'utm_term'
		];
		foreach ($utms as $key)
		{
			$utm                     = $this->getApplication()->getInput()->cookie->get($key, '', 'raw');
			$utm                     = urldecode($utm);
			$utm_name                = strtoupper($key);
			$qr['fields'][$utm_name] = $utm;
		}

		return $qr;
	}

	/**
	 * function prepareDataToSaveToComment
	 *
	 * @param $data    array contact or requisite array to implode with key names
	 * @param $message string Message for to wrap this data in comment
	 *
	 * @return string  Stringified contact or requisite info to inqlude in lead/deal comment
	 * @since  2.0.0
	 */
	private function prepareDataToSaveToComment(array $data, string $message): string
	{
		$string = '<br/>== ' . $message . ' ==<br/>';
		foreach ($data['fields'] as $key => $value)
		{
			if ($key == 'PHONE' || $key == 'EMAIL' || $key == 'FAX')
			{
				$string .= '<strong>' . Text::_('PLG_WT_JSHOPPING_B24_PRO_LEAD_' . strtoupper($key)) . ':</strong> ' . $value['n0']['VALUE'] . '<br/>';
			}
			else
			{
				$string .= '<strong>' . Text::_('PLG_WT_JSHOPPING_B24_PRO_LEAD_' . strtoupper($key)) . ':</strong> ' . $value . '<br/>';
			}
		}
		$string .= '== ' . $message . ' ==<br/>';

		return $string;
	}

	/**
	 * Adding Lead to Bitrix24
	 *
	 * @param   array     $qr mixed array with contact and deal data
	 * @param   array     $product_rows product rows for lead
	 * @param   int       $debug to enable debug data from function
	 * @param   int|null  $order_id JoomShopping order id for saving JoomShopping and Bitrix24 entitie's relationship to database
	 *
	 * @return array|false Bitrix24 response array or false if
	 *
	 * @since 2.0.0
	 */
	private function addLead(array $qr, array $product_rows, int $debug, ?int $order_id)
	{
		$arData['add_lead'] = [
			'method' => 'crm.lead.add',
			'params' => $qr
		];

		if (!empty($product_rows))
		{
			$arData['add_products'] = [
				'method' => 'crm.lead.productrows.set',
				'params' => [
					'id'   => '$result[add_lead]',
					'rows' => $product_rows
				]
			];
		}
		$resultBitrix24 = CRest::callBatch($arData);
		if ($debug == 1)
		{
			$this->prepareDebugInfo('function addLead - prepared array to send to Bitrix 24(arData)', $arData);
			$this->prepareDebugInfo('function addLead - Bitrix 24 response array (resultBitrix24)', $resultBitrix24);

		}

		if (!isset($resultBitrix24['error']) && !is_null($order_id))
		{
			//Сохраняем id лида в свою таблицу в базе
			$this->setBitrix24LeadOrDealRelationshipToOrder($order_id, 'lead', $resultBitrix24['result']['result']['add_lead']);

			return $resultBitrix24;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Function to save JoomShopping orders and Bitrix24 leads/deals relationships in database
	 * when new lead or deal was created
	 *
	 * @param   int     $jshopping_order_id    joomshopping order id
	 * @param   string  $bitrix24_entity_type  Bitrix24 entity type: lead or deal
	 * @param   int     $bitrix24_entity_id    Bitrix 24 lead or deal id
	 *
	 * @since   2.5.0
	 */
	private function setBitrix24LeadOrDealRelationshipToOrder(int $jshopping_order_id, string $bitrix24_entity_type, int $bitrix24_entity_id) : void
	{
		if (!empty($jshopping_order_id) && !empty($bitrix24_entity_type) && !empty($bitrix24_entity_id))
		{
			$db = $this->getDatabase();
			$bitrix24_entity_type = 'bitrix24_' . $bitrix24_entity_type . '_id';
			$columns              = ['jshopping_order_id', $bitrix24_entity_type];
			$values               = [$jshopping_order_id, $bitrix24_entity_id];

			$query = $db->getQuery(true);
			$query->insert($db->quoteName('#__wt_jshopping_bitrix24_pro'))
				->columns($db->quoteName($columns))
				->values(implode(',', $values));
			$db->setQuery($query)->execute();
		}
	}

	/**
	 * Adding Deal to Bitrix24
	 *
	 * @param   array  $qr            array with deal data
	 * @param   array  $product_rows  product rows for lead
	 * @param   int    $debug         boolean to enable debug data from function
	 * @param   int    $order_id      JoomShopping order id for saving JoomShopping and Bitrix24 entitie's relationship to database
	 *
	 * @return array|bool Bitrix24 response array or false if there is no anything in Bitrix 24 response
	 *
	 * @see   https://dev.1c-bitrix.ru/rest_help/crm/productrow/crm_item_productrow_add.php
	 * @since 2.0.0
	 */
	private function addDeal(array $qr, array $product_rows, int $debug, int $order_id)
	{
		$arData = [
			'add_deal'     => [
				'method' => 'crm.deal.add',
				'params' => $qr
			],
			'add_products' => [
				'method' => 'crm.deal.productrows.set',
				'params' => [
					'id'   => '$result[add_deal]',
					'rows' => $product_rows
				]
			]
		];

		$resultBitrix24 = CRest::callBatch($arData);


		if ($debug == 1)
		{
			$this->prepareDebugInfo('function addDeal - prepared to Bitrix 24 array (arData)', $arData);
			$this->prepareDebugInfo('function addDeal - Bitrix 24 response array (resultBitrix24)', $resultBitrix24);
		}

		if (!isset($resultBitrix24['result']['result_error']) || !isset($resultBitrix24['error']))
		{
			//Сохраняем id лида в свою таблицу в базе
			$this->setBitrix24LeadOrDealRelationshipToOrder($order_id, 'deal', $resultBitrix24['result']['result']['add_deal']);

			return $resultBitrix24;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Update contact via crm.contact.update
	 *
	 * @param   int    $contact_id
	 * @param   array  $upd_info
	 * @param   int    $debug
	 *
	 * @return bool
	 *
	 * @since 2.0.0
	 * @link       https://web-tolk.ru
	 */
	private function updateContact(int $contact_id, array $upd_info, int $debug): bool
	{

		$req_crm_contact_fields = CRest::call(
			'crm.contact.update', [
				'ID'     => $contact_id,
				'fields' => $upd_info
			]
		);

		if ($debug == 1)
		{
			$this->prepareDebugInfo('function updateContact -> prepared info to send to Bitrix 24', $upd_info);
			$this->prepareDebugInfo('function updateContact <- respone array from Bitrix 24', $req_crm_contact_fields);
		}

		if (isset($req_crm_contact_fields['result']['result_error']) || isset($resultBitrix24['error']))
		{
			return false;
		}
		else
		{
			return true;
		}
	}


	/**
	 * Add Contact to Bitrix24
	 *
	 * @param   array   $contact  array with user contact data
	 * @param   int  $debug    to enable debug data from function
	 *
	 * @return mixed array|bool Bitrix24 response array or false
	 *
	 * @since 2.0.0
	 */
	private function addContact(array $contact, int $debug)
	{
		$resultBitrix24 = CRest::call('crm.contact.add', $contact);

		if ($debug == 1)
		{
			$this->prepareDebugInfo('function addContact - Bitrix 24 response array', $resultBitrix24);
		}

		if (isset($resultBitrix24['result']['result_error']) || isset($resultBitrix24['error']))
		{
			return false;
		}
		else
		{
			return $resultBitrix24['result'];
		}
	}

	/**
	 * function for to add Requisites to Contact in Bitrix24
	 *
	 * @param   int    $contact_id  a contact id in Bitrix24
	 * @param   array  $requisites  an array with custromer address data
	 * @param   int    $debug       to enable debug data from function
	 *
	 * @return bool false If any errors return
	 */
	private function addRequisites(int $contact_id, array $requisites, int $debug): bool
	{

		$url                  = $this->params->get('crm_host');
		$check_domain_zone_ru = preg_match('/(.ru)/', $url);
		if ($check_domain_zone_ru == 1)
		{
			$preset_id = 5;//Россия: Организация - 1, Индивидуальный предприниматель - 3, Физическое лицо - 5.
		}
		else
		{
			$preset_id = 3;//Остальные страны: Организация - 1, Физическое лицо - 3,
		}
		$resultRequisite = CRest::call(
			'crm.requisite.add',
			[
				'fields' => [
					'ENTITY_TYPE_ID' => 3,//3 - контакт, 4 - компания
					'ENTITY_ID'      => $contact_id,//contact id
					'PRESET_ID'      => $preset_id,//Россия: Организация - 1, Индивидуальный предприниматель - 3, Физическое лицо - 5. Украина: Организация - 1, Физическое лицо  - 3,
					'NAME'           => 'Person',
					'ACTIVE'         => 'Y'
				]
			]
		);

		$resultAddress = CRest::call(
			'crm.address.add',
			[
				'fields' => [
					'TYPE_ID'        => 1,//Фактический адрес - 1, Юридический адрес - 6, Адрес регистрации - 4, Адрес бенефициара - 9
					'ENTITY_TYPE_ID' => 8,//ID типа родительской сущности. 8 - Реквизит
					'ENTITY_ID'      => $resultRequisite['result'],// ID созданного реквизита
					'COUNTRY'        => $requisites['fields']['ADDRESS_COUNTRY'],
					'PROVINCE'       => $requisites['fields']['ADDRESS_PROVINCE'],
					'POSTAL_CODE'    => $requisites['fields']['ADDRESS_POSTAL_CODE'],
					'CITY'           => $requisites['fields']['ADDRESS_CITY'],
					'ADDRESS_1'      => $requisites['fields']['ADDRESS'],
					'ADDRESS_2'      => $requisites['fields']['ADDRESS_2'],
				]
			]
		);

		if ($debug == 1)
		{
			$this->prepareDebugInfo('function addRequisites -> Requisites array', $requisites);
			$this->prepareDebugInfo('function addRequisites - addRequisites section - <- respone array from Bitrix 24', $resultRequisite);
			$this->prepareDebugInfo('function addRequisites - addAddress (to requisite) section -  <- respone array from Bitrix 24', $resultAddress);
		}
		if (isset($resultRequisite['result']['result_error']) || isset($resultBitrix24['error']))
		{
			return false;
		}
		else
		{
			return true;
		}

	}

	/**
	 * Добавляем js-скрпиты на HTML-фронт
	 *
	 * @throws \Exception
	 * @since 3.0.0
	 */
	function onAfterDispatch(): void
	{
		// We are not work in Joomla API or CLI ar Admin area
		if (!$this->getApplication()->isClient('site')) return;

		$doc = $this->getApplication()->getDocument();
		// We are work only in HTML, not JSON, RSS etc.
		if (!($doc instanceof \Joomla\CMS\Document\HtmlDocument))
		{
			return;
		}

		$wa = $doc->getWebAssetManager();
		// Show plugin version in browser console from js-script for UTM
		$wtb24_plugin_info = simplexml_load_file(JPATH_SITE . '/plugins/system/wt_jshopping_b24_pro/wt_jshopping_b24_pro.xml');
		$doc->addScriptOptions('plg_system_wt_jshopping_b24_pro_version', $wtb24_plugin_info->version);
		$wa->registerAndUseScript('plg_system_wt_jshopping_b24_pro.wt_jshopping_b24_pro_utm', 'plg_system_wt_jshopping_b24_pro/wt_jshopping_b24_pro_utm.js', ['version' => 'auto', 'relative' => true]);

	}


	/**
	 * Fired after successful payment or chekout finish. The last checkout event.
	 *
	 * @param   Event  $event
	 *
	 *
	 * @throws \Exception
	 * @since 1.0.0
	 */
	public function onBeforeDisplayCheckoutFinish(Event $event): void
	{
		/**
		 * @var string $text     статический текст для страницы Завершения заказа из настроек JoomShopping
		 * @var int    $order_id id заказа
		 */
		[$text, $order_id] = $event->getArguments();

		if ($this->params->get('b24_trigger_event', 'always') == 'successful_payment')
		{
			$this->sendOrderToBitrix24($order_id);
		}

		if ($this->params->get('debug') == 1)
		{
			$session    = ($this->getApplication() instanceof SessionAwareWebApplicationInterface ? $this->getApplication()->getSession() : null);
			$debug_info = $session->get('b24debugoutput');
			echo '<h3>WT JoomShopping Bitrix24 PRO debug information</h3><br/>' . $debug_info;
			$session->clear('b24debugoutput');

		}
	}


	/**
	 *  Integration with Radical Form plugin
	 *  Contact form plugin
	 *
	 * @param Event $event
	 *
	 * @return void
	 * @see https://hika.su/rasshireniya/radical-form
	 */
	public function onBeforeSendRadicalForm(Event $event): void
	{
		/**
		 * @var array  $clear  это массив данных, полученный от формы и очищенный ото всех вспомогательных данных.
		 * @var array  $input  это полный массив данных, включая все вспомогательные данные о пользователе и передаваемой форме. Этот массив передается по ссылке и у вас есть возможность изменить переданные данные. В примере выше именно это и происходит, когда вместо вбитого в форму имени устанавливается фиксированная константа.
		 * @var object $params это объект, содержащий все параметры плагина и вспомогательные данные, которые известны при отправке формы. Например здесь можно получить адрес папки, куда были загружены фотографии (их можно переместить в нужное вам место):
		 */
		[$clear, $input, $params] = $event->getArguments();

		/**
		 * Bitrix24 CRest SDK
		 */

		// Array of data to send to Bitrix24
		$qr = [
			'fields' => [],
			'params' => [
				'REGISTER_SONET_EVENT' => 'Y'
			]
		];

		//  Process form data
		foreach ($clear as $key => $value)
		{

			if ($key == 'PHONE' || $key == 'EMAIL' || $key == 'phone' || $key == 'email')
			{

				/*
				 * If any phone numbers or emails are found
				 */
				if (is_array($value))
				{

					$k = 0;
					foreach ($value as $phone_or_email)
					{
						$phone_or_email_iterator = 'n' . $k;


						$qr['fields'][strtoupper($key)][$phone_or_email_iterator] = [
							'VALUE'      => $phone_or_email,
							'VALUE_TYPE' => 'WORK',
						];

						$k++;
					}//end FOREACH

					/*
					 * Single email or phone number
					 */
				}
				else
				{
					$qr['fields'][strtoupper($key)]['n0'] = [
						'VALUE'      => $value,
						'VALUE_TYPE' => 'WORK',
					];
				}

				/*
				 * Other form data. Not email or phone
				 */
			}
			else
			{

				/*
				*	If custom email subject (rfSubject) is exists
				*	then set it as a lead title
				*/
				if ($key == 'rfSubject')
				{
					$qr['fields']['TITLE'] = $value;
				}
				else
				{
					$qr['fields'][strtoupper($key)] = $value;
				}


			}
		}//end foreach Process form data

		/**
		 * Set assigned id form plugin params
		 */

		if (!empty($this->params->get('assigned_by_id')))
		{
			$qr['fields']['ASSIGNED_BY_ID'] = $this->params->get('assigned_by_id');
		}

		/**
		 * Lead source form plugin params
		 */
		$qr['fields']['SOURCE_ID'] = $this->params->get('lead_source');
		$comment = '';

		// Add page title to comment
		if ($this->params->get('radical_form_add_page_title'))
		{
			$comment .= '<br/>' . $input['pagetitle'];
		}

		// Add page url to comment
		if ($this->params->get('radical_form_add_page_url'))
		{
			$comment .= '<br/>'.HTMLHelper::link($input['url'], $input['url']);
		}

        // Add link to uploaded files to comment
        if ($this->params->get('radical_form_add_files'))
        {
            /**
             * Add uploaded files to comment
             */
            $fileupload = '';
            $url = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            $uniq = $input['uniq'];

            // Get the right directory with uploaded files

            $downloadPath = $params->get('downloadpath');
            $folders = Folder::folders($params->get('uploadstorage') . '/rf-' . $uniq);

            foreach ($folders as $folder)
            {
                //прикрепляем файлы
                $filesForAttachment = Folder::files($params->get('uploadstorage') . '/rf-' . $uniq . "/" . $folder, ".", false, true);

                foreach ($filesForAttachment as $file)
                {

                    $fileupload .= $params->get('delimiter',"<br />")."{$url}/{$downloadPath}/{$uniq}/{$folder}/".basename($file);

                }
            }


            $comment .= (!empty($fileupload) ? '<br/>'.$fileupload : '');
        }

		if(isset($qr['fields']['COMMENTS']) && !empty($qr['fields']['COMMENTS']))
		{
			$qr['fields']['COMMENTS']  .= $comment;
		} else {
			$qr['fields']['COMMENTS']  = $comment;
		}

		/**
		 * Add UTMs into array
		 */

		$this->checkUtms($qr);

		/**
		 * Create a lead
		 */
		$qr = $this->customPreprocess('radicalform', ['qr' => $qr, 'clear' => $clear, 'input' => $input, 'radicalform_params' => $params]);

		$result = $this->addLead($qr, [], 0, null);
	}


	/**
	 * Function for inbound connections from Bitrix 24
	 * @since    2.5.0
	 */

	public function onAjaxWt_jshopping_b24_pro(Event $event): void
	{
		$app           = $this->getApplication();
		$token         = $app->getInput()->get->get('token', '', 'raw');
		$action        = $app->getInput()->get->get('action', '', 'raw');
		$b24_auth_data = $app->getInput()->post->get('auth');
		if ($b24_auth_data)
		{
			$b24_app_token = $b24_auth_data['application_token'];
		}


		// Включены ли входящие подключения из Битрикс 24
		// Проверка токена из handler url
		// Проверка токена приложения из Битрикс24
		if ($this->params->get('bitrix24_inbound_integration') == 1 && $token == md5(Uri::root()) && $b24_app_token === $this->params->get('bitrix24_application_token'))
		{
			$b24_event = $app->getInput()->post->get('event');
			$b24_data  = $app->getInput()->post->get('data');
			if ($b24_event == 'ONCRMLEADUPDATE')
			{
				/**
				 * ONCRMLEADUPDATE - Обновление/создание лида
				 */
				$b24_inbound_entity_data = $this->getLead($b24_data['FIELDS']['ID']);
				$this->updateJShoppingOrderHistory($b24_inbound_entity_data);

			}
			elseif ($b24_event == 'ONCRMDEALADD')
			{
				/**
				 * ONCRMDEALDUPDATE - Создание сделки
				 */
				$b24_inbound_entity_data = $this->getDeal($b24_data['FIELDS']['ID']);
				//Если сделка создана в результате конвертации лида,
				// то добавляем id сделки к существующей записи с id лида.
				if (!empty($b24_inbound_entity_data['result']['LEAD_ID']))
				{
					$this->addBitrix24DealIdToRelationship($b24_inbound_entity_data['result']['LEAD_ID'], $b24_inbound_entity_data['result']['ID']);
					$this->updateJShoppingOrderHistory($b24_inbound_entity_data);
				}

			}
			elseif ($b24_event == 'ONCRMDEALUPDATE')
			{
				/**
				 * ONCRMDEALDUPDATE - Создание сделки
				 */

				$b24_inbound_entity_data = $this->getDeal($b24_data['FIELDS']['ID']);

				$this->updateJShoppingOrderHistory($b24_inbound_entity_data);
			}
			elseif ($b24_event == 'ONCRMPRODUCTUPDATE' && $this->params->get('bitrix24_inbound_update_jshopping_products_prices', 0) == 1)
			{
				/**
				 * ONCRMPRODUCTUPDATE - Изменение товара в Битрикс 24
				 */

				// Массив с id Товаров
				$b24_jshopping_relationship = $this->getJshoppingProductIdByBitrix24ProductId([$b24_data['FIELDS']['ID']]);

				if (!empty($b24_jshopping_relationship[$b24_data['FIELDS']['ID']]))
				{
					$b24_product_data = $this->getB24Product($b24_data['FIELDS']['ID']);
					if (count($b24_product_data) > 0)
					{
						$db = $this->getDatabase();
						$query = $db->getQuery(true);
						$query->update($db->quoteName('#__jshopping_products'));
						if (isset($b24_product_data['product_price']))
						{
							$query->set($db->quoteName('product_price') . ' = ' . floatval($b24_product_data['product_price']));
						}

						if (isset($b24_product_data['product_quantity']))
						{
							$query->set($db->quoteName('product_quantity') . ' = ' . floatval($b24_product_data['product_quantity']));
						}

						$query->where($db->quoteName('product_id') . ' = ' . $b24_jshopping_relationship[$b24_data['FIELDS']['ID']]);
						$result = $db->setQuery($query)->execute();
						$this->saveToLog(Text::sprintf('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_INBOUND_UPDATE_JSHOPPING_PRODUCTS_LOG_MESSAGE_1', $b24_jshopping_relationship[$b24_data['FIELDS']['ID']], floatval($b24_product_data['product_price']), $b24_data['FIELDS']['ID']));

					}
					else
					{
						$this->saveToLog(Text::sprintf('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_INBOUND_UPDATE_JSHOPPING_PRODUCTS_LOG_MESSAGE_2', $b24_data['FIELDS']['ID'], $b24_jshopping_relationship[$b24_data['FIELDS']['ID']]), 'ERROR');
					}

				}
				else
				{
					$this->saveToLog(Text::sprintf('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_INBOUND_UPDATE_JSHOPPING_PRODUCTS_LOG_MESSAGE_3', $b24_data['FIELDS']['ID']), 'ERROR');
				}
			}
		}// Проверка токенов END
		elseif (isset($action) && $action == 'getBitrix24Products')
		{
			$event->addResult($this->getBitrix24Products());
		}
		elseif (isset($action) && $action == 'getBitrix24ProductsVariations')
		{
			$event->addResult($this->getBitrix24ProductsVariations());
		}
	}


	/**
	 *  Function to get lead info from Bitrix24
	 *
	 * @param string $lead_id  lead id in Bitrix 24
	 *
	 * @return object lead info object
	 * @since 2.5.0
	 */
	private function getLead($lead_id)
	{
		if (!empty($lead_id))
		{

			$resultBitrix24 = CRest::call(
				'crm.lead.get',
				[
					'ID' => $lead_id
				]
			);

			return $resultBitrix24;
		}
	}

	/**
	 *  Function to get deal info from Bitrix24
	 *
	 * @param string lead id in Bitrix 24
	 *
	 * @return object lead info object
	 * @since 2.5.0
	 */
	private function getDeal($deal_id)
	{
		if (!empty($deal_id))
		{

			$resultBitrix24 = CRest::call(
				'crm.deal.get',
				[
					'ID' => $deal_id
				]
			);

			return $resultBitrix24;
		}
	}

	/**
	 * Get product price and product quantity form Bitrix 24 by product id
	 *
	 * @param $b24_product_id string|int Bitrix 24 product id
	 *
	 * @return array Bitrix24 product price and product quantity array
	 * @since 3.0.0
	 */
	private function getB24Product($b24_product_id): array
	{
		if (!empty($b24_product_id))
		{

			$resultBitrix24 = [];
			if ($this->params->get('bitrix24_inbound_update_jshopping_products_prices') == 1)
			{
				$resultBitrix24ProductPrice      = CRest::call('catalog.price.list', [
					'select' => [
						'price'
					],
					'filter' => [
						'productId' => $b24_product_id, // Фильтр по id Товара
					]
				]);
				$resultBitrix24['product_price'] = $resultBitrix24ProductPrice['result']['prices'][0]['price'];
			}

			if ($this->params->get('bitrix24_inbound_update_jshopping_products_quantities') == 1)
			{
				$resultBitrix24ProductQuantity      = CRest::call('catalog.product.list', [
					'select' => [
						'id', 'iblockId', 'name', 'detailPicture', 'price', 'quantity', 'xmlId'
					],
					'filter' => [
						'id'       => $b24_product_id, // Фильтр по id Товара
						'iblockId' => $this->params->get('default_bitrix24_store_iblock_id')
					]
				]);
				$resultBitrix24['product_quantity'] = $resultBitrix24ProductQuantity['result']['products'][0]['quantity'];
			}


			return $resultBitrix24;
		}

		return [];
	}


	/**
	 * Function to add Bitrix24 deal id to JoomShopping orders and Bitrix24 leads/deals relationships in database.
	 * If lead was converted to deal - save deal id to database.
	 *
	 * @param $bitrix24_lead_id int Bitrix 24 lead id
	 * @param $bitrix24_deal_id int Bitrix 24 deal id
	 *
	 * @since   2.5.0
	 */

	private function addBitrix24DealIdToRelationship($bitrix24_lead_id = null, $bitrix24_deal_id = null)
	{
		$db = $this->getDatabase();
		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__wt_jshopping_bitrix24_pro'))
			->set($db->quoteName('bitrix24_deal_id') . ' = ' . $bitrix24_deal_id)
			->where($db->quoteName('bitrix24_lead_id') . ' = ' . $bitrix24_lead_id);
		$db->setQuery($query)->execute();
	}


	/**
	 * Function for updating JoomShopping order history
	 *
	 * @param $b24_inbound_entity_data     string  Bitrix24 inbound data
	 *
	 * @since    2.5.0
	 */
	private function updateJShoppingOrderHistory($b24_inbound_entity_data)
	{

		$bitrix24_entity_id = $b24_inbound_entity_data['result']['ID'];
		//Проходим массив сабформы с настройками сопоставлений статусов JoomShopping и Битрикс24
		foreach ($this->params->get('order_status_b24_stages') as $stage)
		{

			if ($stage->b24_inbound_event_name == 'ONCRMLEADUPDATE')
			{
				//Получаем статус лида, если лид из Битрикс 24
				$b24_status_or_stage = $b24_inbound_entity_data['result']['STATUS_ID'];
				//Тип события из настроек сабформы - лид
				$b24_status_or_stage_in_joomla = $stage->b24_inbound_lead_status;
				$bitrix24_entity_type          = 'lead';

			}
			elseif ($stage->b24_inbound_event_name == 'ONCRMDEALUPDATE')
			{
				//Получаем стадию сделки, если сделка из Битрикс 24
				$b24_status_or_stage           = $b24_inbound_entity_data['result']['STAGE_ID'];
				$b24_status_or_stage_in_joomla = $stage->b24_inbound_deal_stage;
				$bitrix24_entity_type          = 'deal';
			}

			//Меняем статус заказа и отправляем уведомления на email
			if ($b24_status_or_stage_in_joomla == $b24_status_or_stage)
			{
				if (!class_exists('JSHelper') && file_exists(JPATH_SITE . '/components/com_jshopping/bootstrap.php'))
				{
					require_once(JPATH_SITE . '/components/com_jshopping/bootstrap.php');
				}
				\JSFactory::loadLanguageFile();
				$db = $this->getDatabase();
				$query = $db->getQuery(true)
					->select('jshopping_order_id')
					->from('#__wt_jshopping_bitrix24_pro')
					->where('bitrix24_' . $bitrix24_entity_type . '_id = ' . $bitrix24_entity_id);
				$db->setQuery($query)->execute();
				$jshopping_order_id = $db->loadResult();

				if (!empty($jshopping_order_id))
				{
					$orderChangeStatusModel = \JSFactory::getModel('orderchangestatus', 'Site');
					$orderChangeStatusModel->setData($jshopping_order_id, $stage->jshopping_order_status, 1, $stage->jshopping_order_status, 1, $stage->order_status_custom_text, 1, 0);
					$orderChangeStatusModel->store();
				}
			}
		}
	}


	/**
	 * Добавляем вкладки в карточки товаров для маппинга товаров JoomShopping <=> Битрикс 24
	 * Добавляет вкладку в табы карточки товара JoomShopping
	 * @since 3.0.0
	 */
	public function onDisplayProductEditTabsEndTab(Event $event)
	{
		/**
		 * @var array  $row
		 * @var array  $lists
		 * @var object $tax_value
		 */
		[$row, $lists, $tax_value] = $event->getArguments();

		echo '<li class="nav-item">
				<a href="#product_wt_jshopping_b24_pro" data-toggle="tab" class="nav-link">
					<span class="fw-bold me-2 p-1">
						<span style="color:#0fa2e6">W</span>
							<span style="color:#384148">T</span>
					 </span>
				Bitrix 24';

		if ($this->params->get('bitrix24_inbound_integration', 0) == 1 &&
			$this->params->get('bitrix24_inbound_update_jshopping_products_prices', 0) == 1 ||
			$this->params->get('bitrix24_inbound_update_jshopping_products_quantities', 0) == 1

		)
		{
			echo '<span class="position-absolute top-0 start-100 translate-middle badge border border-light rounded-circle bg-danger"><span class="visually-hidden">pay attantion</span></span>';
		}

		echo '</a></li>';
	}

	/**
	 * Добавляет таб Битрикс 24 в карточку товара
	 *
	 * @since 3.0.0
	 */

	public function onDisplayProductEditTabsEnd(Event $event): void
	{
		/**
		 * @var  $pane
		 * @var  $row
		 * @var  $lists
		 * @var  $tax_value
		 * @var  $currency
		 */
		[$pane, $row, $lists, $tax_value, $currency] = $event->getArguments();

		echo '<div id="product_wt_jshopping_b24_pro" class="tab-pane">';
		echo '<div class="main-card p-3">';
		echo '
				<div class="row py-3">
					<div class="col-12 col-md-2">
						<a href="https://web-tolk.ru" target="_blank" id="web_tolk_link" class="d-flex" title="Go to https://web-tolk.ru">
									<svg width="200" height="50" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg">
										 <g>
										  <title>Go to https://web-tolk.ru</title>
										  <text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="32" id="svg_3" y="36.085949" x="8.152073" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#0fa2e6">Web</text>
										  <text font-weight="bold" xml:space="preserve" text-anchor="start" font-family="Helvetica, Arial, sans-serif" font-size="32" id="svg_4" y="36.081862" x="74.239105" stroke-opacity="null" stroke-width="0" stroke="#000" fill="#384148">Tolk</text>
										 </g>
									</svg>
						</a>
					</div>
					<div class="col-12 col-md-10">
								<h4>WT JoomShopping Bitrix 24 PRO</h4>
								<p>' . Text::_('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_PRODUCT_EDIT_PAGE_PRODUCTS_CONNECTION_TAB_DESC') . '</p>
					</div>
				</div>';

		if ($this->params->get('bitrix24_inbound_integration', 0) == 1 &&
			$this->params->get('bitrix24_inbound_update_jshopping_products_prices', 0) == 1 ||
			$this->params->get('bitrix24_inbound_update_jshopping_products_quantities', 0) == 1

		)
		{
			$message = '<div class="alert alert-warning">';
			if ($this->params->get('bitrix24_inbound_update_jshopping_products_prices', 0) == 1)
			{
				$message .= '<p><span class="text-danger fw-bold">' . Text::_('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_PRODUCT_EDIT_PAGE_PRODUCTS_CONNECTION_TAB_PRODUCT_PRICE_UPDATE_ALERT') . '</span></p>';
			}
			if ($this->params->get('bitrix24_inbound_update_jshopping_products_quantities', 0) == 1)
			{
				$message .= '<p><span class="text-danger fw-bold">' . Text::_('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_PRODUCT_EDIT_PAGE_PRODUCTS_CONNECTION_TAB_PRODUCT_QUANTITY_UPDATE_ALERT') . '</span></p>';
			}
			$message .= Text::_('PLG_WT_JSHOPPING_B24_PRO_BITRIX24_PRODUCT_EDIT_PAGE_PRODUCTS_CONNECTION_TAB_PRODUCT_UPDATE_ALERT');
			$message .= '</div>';
			echo $message;
		}


		$form_data = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><form></form>');
		$fieldset  = $form_data->addChild('fieldset');
		$fieldset->addAttribute('name', 'wt_jshopping_b24_pro');
		$field = $fieldset->addChild('field');
		$field->addAttribute('type', 'b24crmproducts');
		$field->addAttribute('label', 'PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_LIST_FIELD_LABEL');
		$field->addAttribute('name', 'bitrix24_product_id');
		$field->addAttribute('class', 'form-control');
		$field->addAttribute('addfieldprefix', 'Joomla\Plugin\System\Wt_jshopping_b24_pro\Fields');

		$xml  = $form_data->asXML();
		/** @var Form $form
		 * @todo Переписать получение формы Joomla 5 like - из контейнера */
		$form = Form::getInstance('wt_jshopping_b24_pro', $xml);
		if (isset($row->bitrix24_product_id))
		{
			$form->bind($row->bitrix24_product_id);
		}
		echo $form->renderFieldset('wt_jshopping_b24_pro');

		// Если включена настройка использования товаров Битрикс 24 с вариациями
		if ($this->params->get('use_bitrix24_product_variants', 0) == 1)
		{
			$b24_product_main_variation_id   = [];
			$b24_product_main_variation_id[] = '<div class="control-group">
            <div class="control-label"><label id="bitrix24_products_main_variation_modal-lbl" for="bitrix24_products_main_variation_modal">
			    ID основной вариации товара Битрикс 24</label>
			</div>
        <div class="controls">';
			if ($row->bitrix24_product_id['bitrix24_product_id'] > 0){
				$b24_product_main_variation_id[] = '<div class="input-group">';
				$b24_product_main_variation_id[] = '<span class="input-group-text bg-light border-light">' . HTMLHelper::image('media/plg_system_wt_jshopping_b24_pro/images/b24-icon-24x24.jpg', 'Bitrix24 icon', ['width' => '24', 'height' => '24']) . '</span>';
				$b24_product_main_variation_id[] = '<input type="text" 
															id="b24-product-main-variation"
															class="form-control" 
															name="b24_product_main_variation_id"
															value="'.$row->bitrix24_product_id['bitrix24_product_main_variaton_id'].'" /> ';
				$b24_product_main_variation_id[] = '<button type="button" 
															data-product-id="' . $row->product_id . '" 
															data-bs-toggle="modal"
															data-bs-target="#b24_product_main_variation_modal"
															class="btn btn-success">Выбрать</button>';
				$b24_product_main_variation_id[] = '</div>';
			} else {
				$b24_product_main_variation_id[] = '<span class="badge bg-danger">'.Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_VARIATIONS_PARENT_PRODUCT_ID_NOT_SPECIFIED').'</span>';
			}

			$b24_product_main_variation_id[] = '</div>';
			echo implode('',$b24_product_main_variation_id);

			echo HTMLHelper::_(
				'bootstrap.renderModal',
				'b24_product_main_variation_modal',
				[
//						'url'         => $url,
					'title'       => Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_VARIATIONS_LIST_MODAL_HEADER'),
					'closeButton' => true,
					'height'      => '100%',
					'width'       => '100%',
					'modalWidth'  => '80',
					'bodyHeight'  => '60',
					'footer' => '<div class="btn-group" id="b24_product_main_variation_modal_product_pagination"></div>',
					'class' => 'overflow-scroll'
				],
				'<table class="table table-hover table-striped" id="b24_product_main_variation_modal_product_table">
						  <thead>
						    <tr>
						      <th>Название</th>
						      <th></th>
						    </tr>
						  </thead>
						  <tbody>
						    <!-- данные будут вставлены сюда -->
						  </tbody>
						</table>
						
						<template id="b24_product_main_variation_modal_productrow">
						  <tr>
						    <td></td>
						    <td></td>
						  </tr>
						</template>'

			);
		}

		echo '</div></div>';
	}


	/**
	 * Перед редактированием товара JoomShopping в админке
	 * получаем из таблицы связей в базе id товара в Битрикс 24
	 *
	 * @param Event $event
	 *
	 * @see   \Joomla\Component\Jshopping\Administrator\Controller\ProductsController::edit
	 * @since 3.1.0
	 */
	public function onBeforeDisplayEditProductView(Event $event): void
	{
		/**
		 * @var object $view Объект View product_edit
		 */
		[$view] = $event->getArguments();

		$product_id = $view->product->product_id;
		if(!$product_id)
		{
			return;
		}
		$db = $this->getDatabase();
		$query      = $db->getQuery(true);
		$query->select('bitrix24_product_id');

		$jshopping_b24_products_relationship = $this->getJshoppingBitrix24ProductsRelationship([$product_id]);
		$view->product->bitrix24_product_id = $jshopping_b24_products_relationship[$product_id];

		// Если включена настройка использования товаров Битрикс 24 с вариациями
		if ($this->params->get('use_bitrix24_product_variants', 0) == 1)
		{
			$view->dep_attr_td_header = '<th width="100">'.Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_VARIATIONS_LIST_ATTRIBUTES_TABLE_HEADER').'<br/><small class="text-danger">'.Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_VARIATIONS_LIST_ATTRIBUTES_TABLE_HEADER_NOTE').'</small></th>';
			foreach ($view->lists['attribs'] as $k => $v)
			{
				/**
				 * Атрибуты товара
				 */
				$b24_product_variation_id   = [];
				if ($view->product->bitrix24_product_id['bitrix24_product_id'] > 0)
				{

					// Приходит NULL, если нет выборки
					$attr_to_variation_id = $this->getJshoppingAttrToVariationId($view->product->product_id,$v->product_attr_id);
					$b24_product_variation_id[] = '<div class="input-group">';
					$b24_product_variation_id[] = '<span class="input-group-text bg-light border-light">' . HTMLHelper::image('media/plg_system_wt_jshopping_b24_pro/images/b24-icon-24x24.jpg', 'Bitrix24 icon', ['width' => '24', 'height' => '24']) . '</span>';
					$b24_product_variation_id[] = '<input type="text" 
														id="b24-product-variation-' . $view->product->product_id . '-' . $v->product_attr_id . '"
														class="form-control" 
														name="b24_product_variation_id[]" 
														value="' . $attr_to_variation_id['b24_product_variation_id'] . '" /> ';
					$b24_product_variation_id[] = '<button type="button" 
														data-product-id="' . $view->product->product_id . '" 
														data-product-attr-id="' . $v->product_attr_id . '"
														data-bs-toggle="modal"
														data-bs-target="#bitrix24_products_variations_modal"
														class="btn btn-success">Выбрать</button>';
					$b24_product_variation_id[] = '</div>';

				}
				else
				{
					$b24_product_variation_id[] = '<span class="badge bg-danger">'.Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_VARIATIONS_PARENT_PRODUCT_ID_NOT_SPECIFIED').'</span>';
				}
				$view->dep_attr_td_row[$k] = '<td>' . implode('', $b24_product_variation_id) . '</td>';
				unset($b24_product_variation_id);
			}//end foreach $lists
			// Для js-обработчика модального окна

			$wt_jshopping_b24_pro_options = $this->getApplication()->getDocument()->getScriptOptions('wt_jshopping_b24_pro');
			$wt_jshopping_b24_pro_options['product_parent_id_for_b24'] = $view->product->bitrix24_product_id['bitrix24_product_id'];
			$this->getApplication()->getDocument()->addScriptOptions('wt_jshopping_b24_pro',$wt_jshopping_b24_pro_options);

			$view->plugin_template_attribute .= HTMLHelper::_(
				'bootstrap.renderModal',
				'bitrix24_products_variations_modal',
				[
					'title'       => Text::_('PLG_WT_JSHOPPING_B24_PRO_B24_PRODUCT_VARIATIONS_LIST_MODAL_HEADER'),
					'closeButton' => true,
					'height'      => '100%',
					'width'       => '100%',
					'modalWidth'  => '80',
					'bodyHeight'  => '60',
					'footer' => '<div class="btn-group" id="bitrix24_products_variations_modal_product_pagination"></div>',
					'class' => 'overflow-scroll'
				],
				'<table class="table table-hover table-striped" id="bitrix24_products_variations_modal_product_table">
						  <thead>
						    <tr>
						      <th>Название</th>
						      <th></th>
						    </tr>
						  </thead>
						  <tbody>
						    <!-- данные будут вставлены сюда -->
						  </tbody>
						</table>
						
						<template id="bitrix24_products_variations_modal_productrow">
						  <tr>
						    <td></td>
						    <td></td>
						  </tr>
						</template>'

			);
			$this->getApplication()->getDocument()->getWebAssetManager()->registerAndUseScript('plg_system_wt_jshopping_b24_pro.B24catalogproductsvariationsField','plg_system_wt_jshopping_b24_pro/B24catalogproductsvariationsField.js')
				->addInlineStyle('#bitrix24_products_variations_modal .modal-body {overflow-y: scroll; overflow-x:none;}');

		}// end if use products variations
	}

	/**
	 * Сохраняем id товара Битрикс 24 в свою таблицу
	 *
	 * @param Event $event
	 *
	 * @throws \Exception
	 * @since 3.0.0
	 */
	public function onAfterSaveProduct(Event $event): void
	{
		/**
		 * @var object $product
		 */
		[$product] = $event->getArguments();

		$post                = $this->getApplication()->getInput()->post;
		$bitrix24_product_id = $post->get('bitrix24_product_id', '', 'raw');
		$bitrix24_product_main_variation_id = $post->get('b24_product_main_variation_id', '', 'raw');
		$db = $this->getDatabase();
		/**
		 * Сохраняем связь родительских товаров JoomShopping и Битрикс 24 (простые товары).
		 * @todo Переделать запрос на prepared statements
		 */
		$query = "REPLACE INTO `#__wt_jshopping_bitrix24_pro_products_relationship` SET " . $db->quoteName('jshopping_product_id') . "=" . $db->quote($product->product_id) . ", " . $db->quoteName('bitrix24_product_id') . "=" . $db->quote($bitrix24_product_id);
		// основная варииация товара Битрикс 24
		if(isset($bitrix24_product_main_variation_id)){
			$query .= ', ' . $db->quoteName('bitrix24_product_main_variaton_id') . '=' . $db->quote($bitrix24_product_main_variation_id);
		}
		$db->setQuery($query);
		$db->execute();

		// Если включена настройка использования товаров Битрикс 24 с вариациями
		if ($this->params->get('use_bitrix24_product_variants', 0) == 1)
		{
			/**
			 * Атрибуты сохраняются только в случае, если есть массив с ценами.
			 * @see \Joomla\Component\Jshopping\Administrator\Model\ProductsModel::saveAttributes
			 */
			$b24_product_variation_ids = $post->get('b24_product_variation_id', [], 'array');
			$product_attr_id           = $post->get('product_attr_id', [], 'array');

			/**
			 *  Удаляем из базы атрибуты, которых нет в $post.
			 *  Это происходит в том случае, когда атрибуты в товаре были, но их удалили.
			 *  Код взят из модели JoomShopping
			 * @see \Joomla\Component\Jshopping\Administrator\Model\ProductsModel::saveAttributes
			 */

			$list_exist_attr = $product->getAttributes();
			if (isset($product_attr_id))
			{
				$list_saved_attr = $product_attr_id;
			}
			else
			{
				$list_saved_attr = [];
			}
			foreach ($list_exist_attr as $attr)
			{
				if (!in_array($attr->product_attr_id, $list_saved_attr))
				{

					$query = $db->getQuery(true);
					$query->delete()
						->from($db->quoteName('#__wt_jshopping_bitrix24_pro_prod_attr_to_variations'))
						->where($db->quoteName('product_id') . ' = ' . $db->quote($product->product_id))
						->where($db->quoteName('product_attr_id') . ' = ' . $db->quote($attr->product_attr_id));
					$db->setQuery($query)->execute();
				}
			}

			/**
			 * Сохраняем связь вариации товара с атрибутом JoomShopping
			 */

			if (isset($b24_product_variation_ids))
			{
				foreach ($b24_product_variation_ids as $k => $v)
				{
					/** @todo Переделать запрос на prepared statements */
					$query = $db->getQuery(true);
					$query->select($db->quoteName('id'))
						->from($db->quoteName('#__wt_jshopping_bitrix24_pro_prod_attr_to_variations'))
						->where($db->quoteName('product_id') . ' = ' . $db->quote($product->product_id))
						->where($db->quoteName('product_attr_id') . ' = ' . $db->quote($product_attr_id[$k]));
					// Приходит 0, если нет выборки
					$attr_to_variation_id = $db->setQuery($query)->loadResult();

					if ((int) $attr_to_variation_id > 0)
					{
						$update_obj                           = new \stdClass();
						$update_obj->id                       = $attr_to_variation_id;
						$update_obj->product_id               = $product->product_id;
						$update_obj->product_attr_id          = $product_attr_id[$k];
						$update_obj->b24_product_variation_id = $b24_product_variation_ids[$k];
						$db->updateObject('#__wt_jshopping_bitrix24_pro_prod_attr_to_variations', $update_obj, 'id');

					}
					else
					{
						$update_obj                           = new \stdClass();
						$update_obj->product_id               = $product->product_id;
						$update_obj->product_attr_id          = $product_attr_id[$k];
						$update_obj->b24_product_variation_id = $b24_product_variation_ids[$k];
						$db->insertObject('#__wt_jshopping_bitrix24_pro_prod_attr_to_variations', $update_obj);
					}
				}
			}
		}
	}

	/**
	 * Чистим таблицу связей при удалении товара в JoomShopping
	 *
	 * @param   array  $ids  id удаляемых товаров
	 *
	 * @since 3.0.0
	 */
	public function onAfterRemoveProduct(Event $event): void
	{
		/**
		 * @var array $ids
		 */
		[$ids] = $event->getArguments();

		/** @todo Переделать запрос на prepared statements */
		$db = $this->getDatabase();
		$query      = $db->getQuery(true);
		$conditions = [
			$db->quoteName('jshopping_product_id') . ' IN (' . implode(',', $ids) . ')'
		];
		$query->delete($db->quoteName('#__wt_jshopping_bitrix24_pro_products_relationship'))
			->where($conditions);
		$db->setQuery($query);
		$db->execute();
		// Если включена настройка использования товаров Битрикс 24 с вариациями
		if ($this->params->get('use_bitrix24_product_variants', 0) == 1)
		{
			// Удаляем связи атрибутов товара с вариациями товара в Битрикс 24.
			$query      = $db->getQuery(true);
			$conditions = [
				$db->quoteName('product_id') . ' IN (' . implode(',', $ids) . ')'
			];
			$query->delete($db->quoteName('#__wt_jshopping_bitrix24_pro_prod_attr_to_variations'))
				->where($conditions);
			$db->setQuery($query)->execute();
		}
	}

	/**
	 * Получаем список соответствий товаров JoomShopping и Битрикс 24
	 *
	 * @param $product_ids array JoomShopping product ids
	 *
	 * @return array
	 *
	 * @since 3.0.0
	 */
	private function getJshoppingBitrix24ProductsRelationship(array $product_ids): array
	{
		$db = $this->getDatabase();
		/** @todo Переделать запрос на prepared statements */
		$query = "SELECT * FROM `#__wt_jshopping_bitrix24_pro_products_relationship` WHERE " . $db->quoteName('jshopping_product_id') . " IN (" . implode(',', $product_ids) . ")";
		$db->setQuery($query);
		$result                = $db->loadAssocList();
		$product_relationships = [];
		foreach ($result as $relationship)
		{
			$product_relationships[$relationship['jshopping_product_id']]['bitrix24_product_id'] = $relationship['bitrix24_product_id'];
			$product_relationships[$relationship['jshopping_product_id']]['bitrix24_product_main_variaton_id'] = $relationship['bitrix24_product_main_variaton_id'];
		}

		return $product_relationships;

	}

	/**
	 * Получаем id товаров JoomShopping по id товара из Битрикс 24
	 *
	 * @param $product_ids array Bitrix 24 product ids
	 *
	 * @return array
	 *
	 * @since 3.0.0
	 */
	private function getJshoppingProductIdByBitrix24ProductId(array $product_ids): array
	{
		$db = $this->getDatabase();
		/** @todo Переделать запрос на prepared statements */
		$query = "SELECT * FROM `#__wt_jshopping_bitrix24_pro_products_relationship` WHERE " . $db->quoteName('bitrix24_product_id') . " IN (" . implode(',', $product_ids) . ")";
		$db->setQuery($query);
		$result                = $db->loadAssocList();
		$product_relationships = [];
		foreach ($result as $relationship)
		{
			$product_relationships[$relationship['bitrix24_product_id']] = $relationship['jshopping_product_id'];
		}

		return $product_relationships;

	}

	/**
	 * Возвращает список товаров Битрикс 24 из API
	 *
	 * @since 3.0.0
	 */
	public function getBitrix24Products()
	{
		$product_pagination_start = $this->getApplication()->getInput()->get('start', 0);

		$resultBitrix24 = CRest::call("catalog.product.list", [
			'select' => [
				'id', 'iblockId', 'name', 'detailPicture', 'price', 'quantity', 'xmlId'
			],
			'filter' => [
//						'id' => 122, // Фильтр по id Товара
				'iblockId' => $this->params->get('default_bitrix24_store_iblock_id')
			],
//					'order' => ['id' =>'DESC' ],
			'start'  => $product_pagination_start
		]);

		return $resultBitrix24;


	}


	/**
	 * Возвращает список товаров Битрикс 24 из API
	 *
	 * @return array
	 *
	 * @throws \Exception
	 *
	 * @since 3.1.0
	 */
	public function getBitrix24ProductsVariations(): array
	{
		if ($this->params->get('use_bitrix24_product_variants', 0) == 1)
		{
			$product_pagination_start = $this->getApplication()->getInput()->get('start', 0);
			$b24_parent_product_id = $this->getApplication()->getInput()->get('b24_parent_product_id', 0);
			$request_options = [
				'select' => [
					'id', 'iblockId', 'name', 'detailPicture', 'price', 'quantity', 'xmlId'
				],
				'filter' => [
					'iblockId' => $this->params->get('bitrix24_products_variants_store_iblock_id')
				],
				'start'  => $product_pagination_start
			];

			if($b24_parent_product_id){
				$request_options['filter']['parentId'] = $b24_parent_product_id;
			}

			$resultBitrix24 = CRest::call("catalog.product.list", $request_options);

			return $resultBitrix24;
		}

		return [];
	}


	/**
	 * Получаем сопоставления атрибутов JoomSHopping и вариаций товара Битрикс 24
	 * @param $product_id int
	 * @param $product_attr_id int
	 *
	 * @return mixed
	 *
	 * @since 3.1.0
	 */
	public function getJshoppingAttrToVariationId($product_id, $product_attr_id)
	{
		$db = $this->getDatabase();
		// Выдергиваем из базы
		$query = $db->getQuery(true);
		$query->select('*')
			->from($db->quoteName('#__wt_jshopping_bitrix24_pro_prod_attr_to_variations'))
			->where($db->quoteName('product_id') . ' = ' . $db->quote($product_id))
			->where($db->quoteName('product_attr_id') . ' = ' . $db->quote($product_attr_id));
		// Приходит 0, если нет выборки
		return $db->setQuery($query)->loadAssoc();
	}


	/**
	 * @param   int     $product_id
	 * @param   string  $order_item_attribute
	 *
	 * @return array
	 *
	 * @since 3.1.0
	 */
	public function getJShoppingActiveProductAttributeInOrder(int $product_id, string $order_item_attribute){
		$attributes = unserialize($order_item_attribute);

		$db = $this->getDatabase();

		$where = '';
		foreach($attributes as $k=>$v){
			$where.=' and PA.attr_'.(int)$k.' = '.(int)$v;
		}
		$query = 'select PA.product_attr_id from `#__jshopping_products_attr` as PA where PA.product_id='.(int)$product_id.' '.$where;

		return (array) $db->setQuery($query)->loadAssoc();
	}

	/**
	 * Function for to log library errors in plg_system_wt_jshopping_b24_pro.log.php in
	 * Joomla log path. Default Log category plg_system_wt_jshopping_b24_pro
	 *
	 * @param   string  $data      error message
	 * @param   string  $priority  Joomla Log priority
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function saveToLog(string $data, string $priority = 'NOTICE'): void
	{
		Log::addLogger(
			[
				// Sets file name
				'text_file' => 'plg_system_wt_jshopping_b24_pro.log.php',
			],
			// Sets all but DEBUG log level messages to be sent to the file
			Log::ALL & ~Log::DEBUG,
			['plg_system_wt_jshopping_b24_pro']
		);
		$this->getApplication()->enqueueMessage($data, $priority);
		$priority = 'Log::' . $priority;
		Log::add($data, $priority, 'plg_system_wt_jshopping_b24_pro');

	}


	/**
	 * Custom preprocess data before sending it to Bitrix 24.
	 * Custom hadlers are placed in `/plugins/system/wt_jshopping_b24_pro/src/Custompreprocess`
	 * and uses via `require_once`. You can place here any separate files you need.
	 *
	 * You **MUST** return the `$qr` array
	 *
	 * @param string $section The context where we fire custom preprocess
	 * @param array $data All data for preprocessing
	 *
	 * @return array
	 *
	 * @since 3.2.0
	 */
	private function customPreprocess($section = '', $data = []): array
	{
		$qr = $data['qr'];
		/**
		 * Include files with custom SEO variables and overrides from
		 * plugins/system/wt_jshopping_b24_pro/src/Custompreprocess
		 */
		$preprocess_folder = JPATH_SITE . '/plugins/system/wt_jshopping_b24_pro/src/Custompreprocess';
		if (Folder::exists($preprocess_folder))
		{
			$custom_preprocessors = Folder::files($preprocess_folder);
			if ($this->params->get('debug') == 1)
			{
				$this->prepareDebugInfo('Custom preprocess folder found', $preprocess_folder);
				$this->prepareDebugInfo('Custom variables files found (' . count($custom_preprocessors) . ')', $custom_preprocessors);
			}

			foreach ($custom_preprocessors as $preprocessor)
			{
				require_once($preprocess_folder.'/' . $preprocessor);
			}
		}

		return $qr;
	}
}
