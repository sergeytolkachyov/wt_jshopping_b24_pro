<?php
/**
 * @package       WT JShopping Bitrix 24 PRO
 * @version     3.2.1
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       3.0.0
 */

/**
 * Custom preprocess data before sending it to Bitrix 24.
 * Custom hadlers are placed in `/plugins/system/wt_jshopping_b24_pro/src/Custompreprocess`
 * and uses via `require_once`.
 * You can place here any separate files you need.
 *
 * You MUST RETURN the $qr array. See the customPreprocess() method in Wt_jshopping_b24_pro.php
 * @var array  $qr      The data for preprocessing. It will be sent to Bitrix 24
 *
 * if($context == 'joomshopping')
 *
 * @var array  $data
 * @var string $context 'joomshopping' or 'radicalform'
 * @var array  $data    ['order']
 * @var array  $data    ['product_rows']
 *
 * if($context == 'radicalform')
 *
 * @var array  $data    ['clear']
 * @var array  $data    ['input']
 * @var array  $data    ['radicalform_params']
 *
 *
 * @since 3.2.0
 */

use Joomla\CMS\Factory;

\defined('_JEXEC') or die;

if ($context == 'joomshopping')
{
	$order                    = $data['order'];
	//$qr['fields']['COMMENTS'] .= '<br>Order number: ' . $order->order_number;

}

if ($context == 'radicalform')
{

}