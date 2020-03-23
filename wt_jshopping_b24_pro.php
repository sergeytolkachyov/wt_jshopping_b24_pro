<?php
// No direct access
defined( '_JEXEC' ) or die;


/**
 * @package     WT JoomShopping B24 PRO
 * @version     1.1.2
 * WT Bitrix24 Connector PRO - advanced tool for reciving order information from JoomShopping into CRM Bitrix24
 * @Author Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2020 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since 1.0
 */
jimport('joomla.plugin.plugin');
class plgSystemWt_jshopping_b24_pro extends JPlugin
{
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



    public function onBeforeDisplayCheckoutFinish()
    {

        $session = JFactory::getSession();
        $crm_host = $this->params->get('crm_host');
        $webhook_secret = $this->params->get('crm_webhook_secret');
        $crm_assigned_id = $this->params->get('crm_assigned');

    if(!$crm_host or $crm_host == "" or !$webhook_secret or $webhook_secret == "" or !$crm_assigned_id or $crm_assigned_id == ""){
	    echo JText::_("PLG_WT_JSHOPPING_B24_PRO_B24_NOT_CONNECTED");
	}else{

	        define('C_REST_WEB_HOOK_URL', 'https://' . $crm_host . '/rest/' . $crm_assigned_id . '/' . $webhook_secret . '/');//url on creat Webhook
	        include_once("plugins/system/wt_jshopping_b24_pro/lib/crest.php");
	        require_once (JPATH_SITE.'/components/com_jshopping/lib/factory.php');
	        require_once (JPATH_SITE.'/components/com_jshopping/lib/functions.php');
	        $jshopConfig = JSFactory::getConfig();
	        $orderId = $session->get('jshop_end_order_id');
	        $order = JTable::getInstance('order', 'jshop');
	        $order->load($orderId);
	        $order->getAllItems();

	        $qr = array(
	            'fields' => array(),
	            'params' => array("REGISTER_SONET_EVENT" => "Y")
	        );
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

	         $qr["fields"][$b24_field] = $store_field;

	         }//END FOR


	        $qr["fields"]["SOURCE_ID"] = $this->params->get('lead_source');
	        $qr["fields"]["SOURCE_DESCRIPTION"] = $this->params->get('source_description');






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
	               $b24_comment .= "<a href='" . SEFLink('index.php?option=com_jshopping&controller=product&task=view&category_id=' . $items->category_id . '&product_id=' . $items->product_id) . "'/>" . $items->product_name . "</a><br/>";
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

	        /************* DEBUG *****************/
	        $debug = $this->params->get('debug');
	        if($debug == 1){
	            echo"<pre><h3>Query array (to Bitrix24)</h3><br/>";
	            print_r($qr);
	            echo "</pre>";

	            echo"<pre><h3>Product rows array (to Bitrix24)</h3><br/>";
	            print_r($product_rows);
	            echo"</pre>";
	            echo"<pre><h3>Bitrix24 response array</h3><br/>";
	             print_r($resultBitrix24);
	            echo "</pre>";
	            echo"<pre><h3>JoomShopping order array</h3><br/>";
	            print_r($order);
	            echo "</pre>";

	        }// if debug
        }//If b24 configured
    }// END onBeforeDisplayCheckoutFinish


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



}//plgSystemWt_jshopping_b24_pro
