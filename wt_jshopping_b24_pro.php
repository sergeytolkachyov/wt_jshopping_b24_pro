<?php
/**
 * @package     WT JoomShopping B24 PRO
 * @version     2.4.0
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0
 */
// No direct access
defined( '_JEXEC' ) or die;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;


class plgSystemWt_jshopping_b24_pro extends CMSPlugin
{
	/* Header for debug element
	 * @var String
	 * @since 2.4.0
	 */
	private $debug_section_header;

	/* Debug data
	 * @var String
	 * @since 2.4.0
	 */
	private $debug_data;

	/* Debug output
	 * @var String
	 * @since 2.4.0
	 */
	private $debug_output;

	/**
	 * Class Constructor
	 * @param object $subject
	 * @param array $config
	 */
	public function __construct( & $subject, $config )
	{
		parent::__construct( $subject, $config );
        $this->loadLanguage();

    }


    public function onAfterCreateOrderFull($order,$cart)
    {
	    $session = Factory::getSession();
        $crm_host = $this->params->get('crm_host');
        $webhook_secret = $this->params->get('crm_webhook_secret');
        $crm_assigned_id = $this->params->get('crm_assigned');

    if(empty($crm_host) or empty($webhook_secret) or empty($crm_assigned_id)){
	    echo JText::_("PLG_WT_JSHOPPING_B24_PRO_B24_NOT_CONNECTED");
	}else{

        define('C_REST_WEB_HOOK_URL', 'https://' . $crm_host . '/rest/' . $crm_assigned_id . '/' . $webhook_secret . '/');//url on creat Webhook
        include_once("plugins/system/wt_jshopping_b24_pro/lib/crest.php");
        require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php');
        require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');
        $jshopConfig = JSFactory::getConfig();
        $orderId = $order->order_id;
        $order = JTable::getInstance('order', 'jshop');
        $order->load($orderId);
        $order->getAllItems();
	    $plugin_mode = $this->params->get('lead_vs_deal');

        $qr = array(
            'fields' => array(),
            'params' => array("REGISTER_SONET_EVENT" => "Y")
        );
	    if ($plugin_mode == "deal" || ($plugin_mode == "lead" && $this->params->get('create_contact_for_unknown_lead') == 1))
	    {
		    $contact = array(
			    'fields' => array()
		    );
		    $requisites = array(
			    'fields' => array()
		    );
	    }

	    $debug = $this->params->get('debug');

	    if($debug == 1){
			$this->prepareDebugInfo("Plugin mode",$plugin_mode);
	    }

	        $b24_fields = $this->params->get('fields');
	     for ($i=0; $i<count((array)$b24_fields);$i++){
	        $fields_num = "fields".$i;
	         $b24_field = "";
	         $store_field ="";
	             if($b24_fields->$fields_num->b24fieldtype == "standart"){
	                $b24_field = $b24_fields->$fields_num->b24fieldstandart;
	                      if($b24_field == "TITLE"){
	                            foreach ($b24_fields->$fields_num->storefield as $value){

	                                    $store_field .= $order->$value . " ";
	                            }
	                        $store_field = $this->params->get('order_name_prefix').$store_field;

	                    }elseif($b24_field == "EMAIL"){
	                        $store_field = array();

	                        $k=0;
	                        foreach($b24_fields->$fields_num->storefield as $value){
	                            $email_name="n".$k;

	                            $store_field[$email_name] = array(
	                                "VALUE" => $order->$value,
	                                "VALUE_TYPE" => "WORK"
	                            );
	                            $k++;
	                        }//end FOR

	                    }elseif($b24_field == "PHONE"){
	                        $store_field = array();

	                        $k=0;
	                        foreach($b24_fields->$fields_num->storefield as $value){
	                            $phone_name="n".$k;

	                            $store_field[$phone_name] = array(
	                                        "VALUE" => $order->$value,
	                                        "VALUE_TYPE" => "WORK"
	                                    );
	                            $k++;
	                        }//end FOR

	                    }else {
	                        // TODO: Сделать функцию, а не копировать 2 раза цикл
	                        foreach ($b24_fields->$fields_num->storefield as $value){
	                            if($value == "country"){//Получаем название страны
	                                $store_field .= $this->getCountryName($order->$value)." ";
	                            }elseif ($value == "coupon_id"){// Получаем код купона
	                                $store_field .= $this->getCountryCouponCode($order->$value)." ";
	                            }elseif($value == "shipping_method_id"){//название способа доставки
	                                $store_field .= $this->getShippingMethodName($order->$value)." ";
	                            }elseif($value == "payment_method_id"){//название способа оплаты
	                                $store_field .= $this->getPaymentMethodName($order->$value)." ";
	                            }elseif($value == "order_status"){//название статуса заказа
	                                $store_field .= $this->getOrderStatusName($order->$value)." ";
	                            }elseif($value == "birthday" AND ($order->$value == "0000-00-00" || $order->$value == "")){
	                                continue;
	                            }else {
	                                $store_field .= $order->$value . " ";
	                            }
	                        }
	                    }

	             }elseif ($b24_fields->$fields_num->b24fieldtype == "custom"){// Пользовательское поле Битрикс24
	                 $b24_field = $b24_fields->$fields_num->b24fieldcustom;

	                     foreach ($b24_fields->$fields_num->storefield as $value){
	                         if($value == "country"){//Получаем название страны
	                             $store_field .= $this->getCountryName($order->$value)." ";
	                         }elseif ($value == "coupon_id"){// Получаем код купона
	                             $store_field .= $this->getCountryCouponCode($order->$value)." ";
	                         }elseif($value == "shipping_method_id"){//название способа доставки
	                             $store_field .= $this->getShippingMethodName($order->$value)." ";
	                         }elseif($value == "payment_method_id"){//название способа оплаты
	                             $store_field .= $this->getPaymentMethodName($order->$value)." ";
	                         }elseif($value == "order_status"){//название статуса заказа
	                             $store_field .= $this->getOrderStatusName($order->$value)." ";
	                         }else {
	                             $store_field .= $order->$value . " ";
	                         }
	                     }
	             }

				/*
				 * Если Сделка или Лид+Контакт
				 */

			    if ($plugin_mode == "deal" || ($plugin_mode == "lead" && $this->params->get('create_contact_for_unknown_lead') == 1))
			     {
				     if ($b24_field == "NAME" || //Fields for contact
					     $b24_field == "LAST_NAME" ||
					     $b24_field == "SECOND_NAME" ||
					     $b24_field == "BIRTHDATE" ||
					     $b24_field == "PHONE" ||
					     $b24_field == "EMAIL" ||
					     $b24_field == "FAX"
				     )
				     {
					     $contact["fields"][$b24_field] = $store_field;

				     } elseif($b24_field == "ADDRESS" ||  //Fields for contact's requisites
					     $b24_field == "ADDRESS_2" ||
					     $b24_field == "ADDRESS_CITY" ||
					     $b24_field == "ADDRESS_POSTAL_CODE" ||
					     $b24_field == "ADDRESS_REGION" ||
					     $b24_field == "ADDRESS_PROVINCE" ||
					     $b24_field == "ADDRESS_COUNTRY"
				     )
				     {
					     $requisites["fields"][$b24_field] = $store_field;
				     }
				     else
				     {
					     $qr["fields"][$b24_field] = $store_field;
				     }


			     }// end if deal or lead+contact
			    /*
				* Если простой Лид
				*/
			     else
			     {
				     $qr["fields"][$b24_field] = $store_field;
			     }

	         }//END FOR


	        $qr["fields"]["SOURCE_ID"] = $this->params->get('lead_source');
	        $qr["fields"]["SOURCE_DESCRIPTION"] = $this->params->get('source_description');

	        /*
	         * Тип сделки: продажа, продажа товара, продажа услуги и т.д.
	         */

		    if($plugin_mode == "deal"){
			    $qr["fields"]["TYPE_ID"] = $this->params->get('deal_type');

		    }


			/*
			 *  Товарные позиции для лида или сделки
			 */

	        $product_rows = array();
	        $b24_comment="<br/>";
	       $a=0;
	       foreach($order->items as $items){
	           $product_rows[$a]["PRODUCT_NAME"] = $items->product_name;
	           $product_rows[$a]["PRICE"] = $items->product_item_price;
	           $product_rows[$a]["QUANTITY"] = $items->product_quantity;


	           if($this->params->get('product_image') == 1) {
	               $b24_comment .= "<img src='" . $jshopConfig->image_product_live_path . "/" . $items->thumb_image . "' width='150px'/><br/>";
	           }
	           if($this->params->get('product_link') == 1) {
	               $b24_comment .= "<a href='".substr(JURI::root(),0,-1). Route::_('index.php?option=com_jshopping&controller=product&task=view&category_id=' . $items->category_id . '&product_id=' . $items->product_id) . "'/>" . $items->product_name . "</a><br/>";
	           }else {
	               $b24_comment .= $items->product_name."<br/>";
	           }
	           if($this->params->get('ean') == 1) {
	               $b24_comment .= JText::_("PLG_WT_JSHOPPING_B24_PRO_B24_LEAD_JSHOPPING_EAN").": ".$items->ean."<br/>";
	           }
	           if($this->params->get('manufacturer_code') == 1) {
	               $b24_comment .= JText::_("PLG_WT_JSHOPPING_B24_PRO_B24_LEAD_JSHOPPING_MANUFACTURER_CODE").": ".$items->manufacturer_code."<br/>";
	           }
	           if($this->params->get('product_weight') == 1) {
	               $b24_comment .= JText::_("PLG_WT_JSHOPPING_B24_PRO_B24_LEAD_PRODUCT_WEIGHT").": ".$items->weight."<br/>";
	           }
	           $b24_comment .= $items->product_attributes . "<br/>";
	           $b24_comment .= $items->product_freeattributes . "<br/><hr/>";
	           $a++;
	       }


	        $b24_comment .= "<a href='".JURI::root()."administrator/index.php?option=com_jshopping&controller=orders&task=show&order_id=".$order->order_id."'>See this order in JoomShopping</a>";
	        $qr["fields"]["COMMENTS"] .= $b24_comment;

			$getCookie = JFactory::getApplication()->input->cookie;
	        $utms = array(
	            'utm_source',
	            'utm_medium',
	            'utm_campaign',
	            'utm_content',
	            'utm_term'
	        );
	        foreach ($utms as $key){
	            if($key != "utm_term"){
	                $utm = $getCookie->get($name = $key);
	            } else {
	                $utm = $getCookie->get($name = $key);
	                $utm = urldecode($utm);
	            }
	            $utm_name = strtoupper($key);
	            $qr["fields"][$utm_name] .=  $utm;
	        }


	        /*
	         * Добавление лида или сделки на определенную стадию (с определенным статусом)
	         */

		    if($this->params->get("create_lead_or_deal_on_specified_stage") == 1){
				if($plugin_mode == "lead" && !empty($this->params->get("lead_status"))){
					$qr["fields"]["STATUS_ID"] = $this->params->get("lead_status");
				}elseif($plugin_mode == "deal" && !empty($this->params->get("deal_stage"))){

				     $qr["fields"]["STAGE_ID"] = $this->params->get("deal_stage");
				     $qr["fields"]["CATEGORY_ID"] = $this->params->get("deal_category");
				}
		    }



	        if(!empty($this->params->get("assigned_by_id"))){
		        $qr["fields"]["ASSIGNED_BY_ID"] = $this->params->get("assigned_by_id");
			}

	        if ($plugin_mode == "deal" || ($plugin_mode == "lead" && $this->params->get('create_contact_for_unknown_lead') == 1)){
	        	/*
	        	 * Ищем дубли контактов
	        	 *
	        	 */

						$search_duobles_by_phone = $contact["fields"]["PHONE"]["n0"]["VALUE"];
						$search_duobles_by_email = $contact["fields"]["EMAIL"]["n0"]["VALUE"];

		        $find_doubles = [
			        'find_doubles_by_phone' => [
				        'method' => 'crm.duplicate.findbycomm',
				        'params' => [
					        "type"   => "PHONE",
					        "values" => [$search_duobles_by_phone]
				        ],
			        ],
			        'find_doubles_by_email' => [
				        'method' => 'crm.duplicate.findbycomm',
				        'params' => [
					        "type"   => "EMAIL",
					        "values" => [$search_duobles_by_email]
				        ]
			        ]
		        ];
		        $find_doublesBitrix24 = CRest::callBatch($find_doubles);

		        if($debug == 1)
		        {
			        $this->prepareDebugInfo("FIND_DOUBLES -> array TO BITRIX 24 with information for search duplicate contacts",$find_doubles);
			        $this->prepareDebugInfo("FIND_DOUBLES <- response array FROM BITRIX 24 with information about search results for duplicate contacts",$find_doublesBitrix24);
		        }


		        /*
				 * Конец поиска дублей контактов
				 *
				 * Начинаем разбор.
				 * Проверяем, не пустой ли массив.
				 * Проверяем, сколько найдено совпадений. Если больше одного совпадения - всю информацию отправляем в комментарий к сделке.
				 */
		        if (!empty($find_doublesBitrix24["result"]["result"]["find_doubles_by_phone"]["CONTACT"]) && !empty($find_doublesBitrix24["result"]["result"]["find_doubles_by_phone"]["CONTACT"][0]))
		        {
			        if(count($find_doublesBitrix24["result"]["result"]["find_doubles_by_phone"]["CONTACT"]) > 1){
			        	/*
			        	 * Если найдено больше одного совпадения по телефону
			        	 */
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($contact, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_1'));
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_1'));

				        if($plugin_mode == "lead"){
				        	$this->addLead($qr,$product_rows,$debug);
					        return;
				        } elseif ($plugin_mode == "deal"){
					        $this->addDeal($qr,$product_rows,$debug);
					        return;
				        }


			        }else {
				        $b24contact_id_by_phone = $find_doublesBitrix24["result"]["result"]["find_doubles_by_phone"]["CONTACT"][0];
			        }
		        }
		        if (!empty($find_doublesBitrix24["result"]["result"]["find_doubles_by_email"]["CONTACT"]) && !empty($find_doublesBitrix24["result"]["result"]["find_doubles_by_email"]["CONTACT"][0]))
		        {
			        if(count($find_doublesBitrix24["result"]["result"]["find_doubles_by_email"]["CONTACT"]) > 1){
				        /*
			        	 * Если найдено больше одного совпадения по email
			        	 */

				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($contact, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_2'));
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_2'));

				        if($plugin_mode == "lead"){
					        $this->addLead($qr,$product_rows,$debug);
					        return;
				        } elseif ($plugin_mode == "deal"){
					        $this->addDeal($qr,$product_rows,$debug);
					        return;
				        }

			        }else {
				        $b24contact_id_by_email = $find_doublesBitrix24["result"]["result"]["find_doubles_by_email"]["CONTACT"][0];
			        }

		        }


		        /*
		         *  Найдены совпадения И по email И по телефону
		         */
		        if(!is_null($b24contact_id_by_email) && !is_null($b24contact_id_by_phone)){
			        /*
					 * Проверяем, одинаковые ли CONTACT_ID при совпадении по телефону и почте
					 */
					if($b24contact_id_by_email == $b24contact_id_by_phone){
						$qr["fields"]["CONTACT_ID"] = $b24contact_id_by_email;
					} else {
						/*
						 * Если CONTACT_ID разные - пишем все в комментарий к сделке/лиду.
						 */

						$qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($contact, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_3'));
						$qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_3'));

					}
		        }// END Найдены совпадения И по email И по телефону

		        /*
		         *  У контакта совпал телефон, но не совпал email
		         */
				elseif(!is_null($b24contact_id_by_phone) && is_null($b24contact_id_by_email)){
					$upd_info_email = [
						'EMAIL' => [
							'n0' => [
								'VALUE' => $contact["fields"]["EMAIL"]["n0"]["VALUE"],
								'TYPE' => 'WORK'
							]
						]
					];


					$updateContactResult = $this->updateContact($b24contact_id_by_phone,$upd_info_email,$debug); //Добавляем в контакт EMAIL

					if($updateContactResult == false){
						$qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($contact, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
						$qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
					}else {

						$qr["fields"]["CONTACT_ID"] = $b24contact_id_by_phone;
					}
		        }// END У контакта совпал телефон, но не совпал email

		        /*
		         *  У контакта совпал email, но не совпал телефон
		         */
		        elseif(!is_null($b24contact_id_by_email) && is_null($b24contact_id_by_phone)){
			        $upd_info_phone = [
				        'PHONE' => [
					        'n0' => [
						        'VALUE' => $contact["fields"]["PHONE"]["n0"]["VALUE"],
						        'TYPE' => 'WORK'
					        ]
				        ]
			        ];

			        $updateContactResult  = $this->updateContact($b24contact_id_by_email,$upd_info_phone,$debug); //Добавляем в контакт PHONE

			        if($updateContactResult == false){
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($contact, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_4'));
			        }else {

				        $qr["fields"]["CONTACT_ID"] = $b24contact_id_by_email;
			        }

		        }// END У контакта совпал email, но не совпал телефон

		        /*
		         *  Нет совпадений с контактами. Создаем новый контакт.
		         */
		        elseif(is_null($b24contact_id_by_email) && is_null($b24contact_id_by_phone)){
			        $b24contact_id = $this->addContact($contact, $debug); //Получаем contact id
			        if($b24contact_id == false)
			        {
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($contact, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_5'));
				        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_5'));
			        } else
			        {
				        $addRaddRequisitesResult = $this->addRequisites($b24contact_id, $requisites, $debug);
				        /*
						 * Если ошибка добавления реквизитов
						 */
				        if ($addRaddRequisitesResult == false)
				        {
					        /*
							 * Пишем реквизиты в комментарий
							 */
					        $qr["fields"]["COMMENTS"] .= $this->prepareDataToSaveToComment($requisites, JText::_('PLG_WT_JSHOPPING_B24_PRO_ALERT_MESSAGE_6'));
				        } else {
					        /*
							 * Добавляем к лиду/сделке CONTACT_ID
							 */
					        $qr["fields"]["CONTACT_ID"] = $b24contact_id;
				        }
			        }
		        }


			if($debug == 1){

				$this->prepareDebugInfo("QR - order info array prepared to send to Bitrix24",$qr);
				$this->prepareDebugInfo("Product_rows - products rows array for include to lead or deal, prepared to send to Bitrix24",$product_rows);
				$this->prepareDebugInfo("Contact - contact array to send to functions (name, phone, email etc.)",$contact);
				$this->prepareDebugInfo("Requisites - Requisites array to send to functions (address, city, country etc.)",$requisites);
			}


		        if ($plugin_mode == "deal"){
			        /*
		        	 * Добавляем сделку
		        	 */
			        $this->addDeal($qr, $product_rows, $debug);
		        } elseif($plugin_mode == "lead" && $this->params->get('create_contact_for_unknown_lead') == 1) {
		        	/*
		        	 * Добавляем лид
		        	 */
			        $this->addLead($qr, $product_rows, $debug);
		        }

	        } else { // Простой лид
		        $this->addLead($qr, $product_rows, $debug);
	        }



        }//If b24 configured
    }// END onBeforeDisplayCheckoutFinish

/** Add Contact to Bitrix24
 * @param $contact array array with user contact data
 * @param $debug string to enable debug data from function
 * @return array Bitrix24 response array
 *
 * @since version 2.0.0-beta1
 */

private function addContact($contact, $debug){
	$resultBitrix24 = CRest::call("crm.contact.add",$contact);

	if($debug == 1)
	{
		$this->prepareDebugInfo("function addContact - Bitrix 24 response array",$resultBitrix24);
	}

	if($resultBitrix24["result"]["result_error"]){
		return false;
	} else {
		return $resultBitrix24["result"];
	}


}
/*
 * function for to add Requisites to Contact in Bitrix24
 * @param $contact_id string a contact id in Bitrix24
 * @param $requisites array an array with custromer address data
 * @param $contact array an array with contact data. For naming requisites
 * @param $debug string to enable debug data from function
 * @return false boolean If any errors return false
 * @return true boolean If Requisites added successfully
 */

private function addRequisites($contact_id,$requisites,$debug){

	$url = $this->params->get('crm_host');
	$check_domain_zone_ru = preg_match("/(.ru)/", $url);
	if($check_domain_zone_ru == 1){
		$preset_id = 5;//Россия: Организация - 1, Индивидуальный предприниматель - 3, Физическое лицо - 5.
	}else{
		$preset_id = 3;//Остальные страны: Организация - 1, Физическое лицо  - 3,
	}
	$resultRequisite = CRest::call(
		'crm.requisite.add',
		[
			'fields' => [
				'ENTITY_TYPE_ID' => 3,//3 - контакт, 4 - компания
				'ENTITY_ID' => $contact_id,//contact id
				'PRESET_ID' => $preset_id,//Россия: Организация - 1, Индивидуальный предприниматель - 3, Физическое лицо - 5. Украина: Организация - 1, Физическое лицо  - 3,
				'NAME' => 'Person',
				'ACTIVE' => 'Y'
			]
		]
	);

	$resultAddress = CRest::call(
		'crm.address.add',
		[
			'fields' => [
				'TYPE_ID' => 1,//Фактический адрес - 1, Юридический адрес - 6, Адрес регистрации - 4, Адрес бенефициара - 9
				'ENTITY_TYPE_ID' => 8,//ID типа родительской сущности. 8 - Реквизит
				'ENTITY_ID' => $resultRequisite["result"],// ID созданного реквизита
				'COUNTRY' => $requisites['fields']['ADDRESS_COUNTRY'],
				'PROVINCE' => $requisites['fields']['ADDRESS_PROVINCE'],
				'POSTAL_CODE' => $requisites['fields']['ADDRESS_POSTAL_CODE'],
				'CITY' => $requisites['fields']['ADDRESS_CITY'],
				'ADDRESS_1' => $requisites['fields']['ADDRESS'],
				'ADDRESS_2' => $requisites['fields']['ADDRESS_2'],
			]
		]
	);

	if($debug == 1)
	{
		$this->prepareDebugInfo("function addRequisites -> Requisites array",$requisites);
		$this->prepareDebugInfo("function addRequisites - addRequisites section - <- respone array from Bitrix 24",$resultRequisite);
		$this->prepareDebugInfo("function addRequisites - addAddress (to requisite) section -  <- respone array from Bitrix 24",$resultAddress);
	}
	if($resultRequisite["result"]["result_error"]){
		return false;
	} else {
		return true;
	}

}
/*
 * function for to add Bitrix24 contact by contact id
 * @param $contact_id string contact id found by findDoubles
 * @param $upd_info array phone or email array to add to existing contact
 * @param $debug string to enable debug data from function
 * @since version 2.0.0-beta1
 */
private function updateContact($contact_id, $upd_info, $debug){

	$req_crm_contact_fields = CRest::call(
		"crm.contact.update",[
			'ID'    => $contact_id,
			'fields'=>$upd_info
		]
	);

	if($debug == 1)
	{
		$this->prepareDebugInfo("function updateContact -> prepared info to send to Bitrix 24",$upd_info);
		$this->prepareDebugInfo("function updateContact <- respone array from Bitrix 24",$req_crm_contact_fields);
	}

	if($req_crm_contact_fields["result"]["result_error"])
	{
		return false;
	} else{
		return true;
	}


}

	/** Adding Lead to Bitrix24
	 * @param array $qr mixed array with contact and deal data
	 * @param array $product_rows product rows for lead
	 * @param string $debug to enable debug data from function
	 * @return array Bitrix24 response array
	 *
	 * @since version 2.0.0-beta1
	 */

	private function addLead($qr,$product_rows, $debug){
		$arData = [
			'add_lead' => [
				'method' => 'crm.lead.add',
				'params' => $qr
			],
			'add_products' => [
				'method' => 'crm.lead.productrows.set',
				'params' => [
					'id' => '$result[add_lead]',
					'rows' => $product_rows
				]
			]
		];
		$resultBitrix24 = CRest::callBatch($arData);
		if($debug == 1)
		{
			$this->prepareDebugInfo("function addLead - prepared array to send to Bitrix 24(arData)",$arData);
			$this->prepareDebugInfo("function addLead - Bitrix 24 response array (resultBitrix24)",$resultBitrix24);

		}

		if(!$resultBitrix24["result"]["result_error"])
		{
			return $resultBitrix24;
		} else {
			return false;
		}
	}
	/** Adding Deal to Bitrix24
	 * @param array $qr array with deal data
	 * @param array $product_rows product rows for lead
	 * @param array $debug boolean to enable debug data from function
	 * @return array Bitrix24 response array
	 *
	 * @since version 2.0.0-beta1
	 */
	private function addDeal($qr,$product_rows, $debug){
		$arData = [
			'add_deal' => [
				'method' => 'crm.deal.add',
				'params' => $qr
			],
			'add_products' => [
				'method' => 'crm.deal.productrows.set',
				'params' => [
					'id' => '$result[add_deal]',
					'rows' => $product_rows
				]
			]
		];
		$resultBitrix24 = CRest::callBatch($arData);
		if($debug == 1)
		{
			$this->prepareDebugInfo("function addDeal - prepared to Bitrix 24 array (arData)",$arData);
			$this->prepareDebugInfo("function addDeal - Bitrix 24 response array (resultBitrix24)",$resultBitrix24);
		}

		return $resultBitrix24;
	}

	/**
	 * function prepareDataToSaveToComment
	 *
	 * @param $data array contact or requisite array to implode with key names
	 * @param $message string Message for to wrap this data in comment
	 * @return $string string Stringified contact or requisite info to inqlude in lead/deal comment
	 * @since version 2.0.0-beta2
	 */
	private function prepareDataToSaveToComment ($data,$message){
		$string = "<br/>== ".$message." ==<br/>";
		foreach($data["fields"] as $key => $value){
			if ($key == "PHONE" || $key == "EMAIL" || $key == "FAX"){
				$string .= "<strong>".JText::_('PLG_WT_JSHOPPING_B24_PRO_LEAD_'.strtoupper($key)).":</strong> ".$value["n0"]["VALUE"]."<br/>";
			} else
			{
				$string .= "<strong>" . JText::_('PLG_WT_JSHOPPING_B24_PRO_LEAD_' . strtoupper($key)) . ":</strong> " . $value . "<br/>";
			}
		}
		$string .= "== ".$message." ==<br/>";
		return $string;
	}

    /** Returns country name by id
     * @param Int $country_id
     *
     * @return string
     *
     * @since version 1.0
     */
    private function getCountryName ($country_id){
        $lang = JFactory::getLanguage();
        $current_lang = $lang->getTag();
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('name_'.$current_lang))
            ->from($db->quoteName('#__jshopping_countries'))
            ->where($db->quoteName('country_id') . ' = '. $db->quote($country_id));
        $db->setQuery($query);
        $country_name = $db->loadAssoc();
        return $country_name["name_".$current_lang];
    }
    /** Returns coupon code by id
     * @param Int $coupon_id
     *
     * @return string
     *
     * @since version 1.0
     */
    private function getCountryCouponCode ($coupon_id){
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('coupon_code'))
            ->from($db->quoteName('#__jshopping_coupons'))
            ->where($db->quoteName('coupon_id') . ' = '. $db->quote($coupon_id));
        $db->setQuery($query);
        $coupon_code = $db->loadAssoc();
        return $coupon_code["coupon_code"];
    }

    /** Returns shipping method name by id
     * @param Int $shipping_method_id
     * @return string
     *
     * @since version 1.0
     */

    private function getShippingMethodName ($shipping_method_id){
        $lang = JFactory::getLanguage();
        $current_lang = $lang->getTag();
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('name_'.$current_lang))
            ->from($db->quoteName('#__jshopping_shipping_method'))
            ->where($db->quoteName('shipping_id') . ' = '. $db->quote($shipping_method_id));
        $db->setQuery($query);
        $shipping_name = $db->loadAssoc();
        return $shipping_name["name_".$current_lang];
    }
    /** Returns payment method name by id
     * @param Int $payment_method_id
     *
     * @return string
     *
     * @since version 1.0
     */
    private function getPaymentMethodName ($payment_method_id){
        $lang = JFactory::getLanguage();
        $current_lang = $lang->getTag();
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('name_'.$current_lang))
            ->from($db->quoteName('#__jshopping_payment_method'))
            ->where($db->quoteName('payment_id') . ' = '. $db->quote($payment_method_id));
        $db->setQuery($query);
        $payment_name = $db->loadAssoc();
        return $payment_name["name_".$current_lang];
    }
    /** Returns order status name by id
     * @param Int $order_status_id
     *
     * @return string
     *
     * @since version 1.1
     */
    private function getOrderStatusName ($order_status_id){
        $lang = JFactory::getLanguage();
        $current_lang = $lang->getTag();
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select($db->quoteName('name_'.$current_lang))
            ->from($db->quoteName('#__jshopping_order_status'))
            ->where($db->quoteName('payment_id') . ' = '. $db->quote($order_status_id));
        $db->setQuery($query);
        $order_status = $db->loadAssoc();
        return $order_status["name_".$current_lang];
    }


function onBeforeCompileHead()
    {
        $load_jquery_coockie_script = $this->params->get('load_jquery_coockie_script');
        $document = JFactory::getDocument();
        if ($load_jquery_coockie_script == 1) {
            $document->addScript(JURI::root(true) . "plugins/system/wt_jshopping_b24_pro/js/jquery.coockie.js");
        }
        $document->addScript(JURI::root(true) . "plugins/system/wt_jshopping_b24_pro/js/jquery.coockie.utm.js");


    }

    /*
     * Show debug on JoomShopping checkout finish page (thankoyu page
     * @debug_info  array   Array of debug information
     * @since 2.4.0
     * @todo Сделать показ ошибок не echo, а массив + цикл.
     */


	public function onBeforeDisplayCheckoutFinish(){
		if($this->params->get('debug') == 1){
			$session = Factory::getSession();
			$debug_info = $session->get("b24debugoutput");
			echo "<h3>WT JoomShopping Bitrix24 PRO debug information</h3><br/>".$debug_info;
			$session->clear("b24debugoutput");
		}
	}


	/*
	 *	Integration with Radical Form plugin - https://hika.su/rasshireniya/radical-form
	 *  Contact form plugin
	 *  $clear 	array 	это массив данных, полученный от формы и очищенный ото всех вспомогательных данных.
	 * 	$input 	array 	это полный массив данных, включая все вспомогательные данные о пользователе и передаваемой форме. Этот массив передается по ссылке и у вас есть возможность изменить переданные данные. В примере выше именно это и происходит, когда вместо вбитого в форму имени устанавливается фиксированная константа.
	 *	$params obj		это объект, содержащий все параметры плагина и вспомогательные данные, которые известны при отправке формы. Например здесь можно получить адрес папки, куда были загружены фотографии (их можно переместить в нужное вам место):
	 */
	public function onBeforeSendRadicalForm($clear, &$input, $params)
	{
		$crm_host = $this->params->get('crm_host');
		$webhook_secret = $this->params->get('crm_webhook_secret');
		$crm_assigned_id = $this->params->get('crm_assigned');

		/*
		 * Bitrix24 CRest SDK
		 */

			define('C_REST_WEB_HOOK_URL', 'https://' . $crm_host . '/rest/' . $crm_assigned_id . '/' . $webhook_secret . '/');//url on creat Webhook
			include_once("plugins/system/wt_jshopping_b24_pro/lib/crest.php");
			// Array of data to send to Bitrix24
			$qr = array(
				'fields' => array(),
				'params' => array("REGISTER_SONET_EVENT" => "Y")
			);
			//  Process form data
			foreach ($clear as $key => $value){

				if($key == "PHONE" || $key == "EMAIL"){

					/*
					 * If any phone numbers or emails are found
					 */
					if(is_array($value)){

						$k=0;
						foreach($value as $phone_or_email){
							$phone_or_email_iterator = "n".$k;


							$qr["fields"][$key][$phone_or_email_iterator] = Array(
								"VALUE" => $phone_or_email,
								"VALUE_TYPE" => "WORK",
							);

							$k++;
						}//end FOREACH

						/*
						 * Single email or phone number
						 */
					}else{
						$qr["fields"][$key]["n0"] = Array(
								"VALUE" => $value,
								"VALUE_TYPE" => "WORK",
							);
					}

					/*
					 * Other form data. Not email or phone
					 */
				} else {
					$qr["fields"][$key] = $value;
				}
			}//end foreach Process form data

		$result = $this->addLead($qr, "","0");

	}

	private function prepareDebugInfo($debug_section_header, $debug_data){
		$session = Factory::getSession();
		if(is_array($debug_data) || is_object($debug_data)){
			$debug_data = print_r($debug_data,true);
		}
			$debug_output = $session->get("b24debugoutput");

			$debug_output .= "<details style='border:1px solid #0FA2E6; margin-bottom:5px;'>";
			$debug_output .= "<summary style='background-color:#384148; color:#fff;'>".$debug_section_header."</summary>";
			$debug_output .= "<pre style='background-color: #eee; padding:10px;'>";
			$debug_output .= $debug_data;
			$debug_output .= "</pre>";
			$debug_output .= "</details>";

			$session->set("b24debugoutput", $debug_output);

	}



}//plgSystemWt_jshopping_b24_pro
