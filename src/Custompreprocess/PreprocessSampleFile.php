<?php
/**
 * Custom preprocess data before sending it to Bitrix 24.
 * Custom hadlers are placed in `/plugins/system/wt_jshopping_b24_pro/src/Custompreprocess`
 * and uses via `require_once`.
 * You can place here any separate files you need.
 *
 * You MUST RETURN the $qr array
 * @var array $qr  The data for preprocessing. It will be sent to Bitrix 24
 *
 * if($section == 'joomshopping')
 *
 * @var array $data
 * @var string $section 'joomshopping' or 'radicalform'
 * @var array $data['order']
 * @var array $data['product_rows']
 *
 * if($section == 'radicalform')
 *
 * @var array $data['clear']
 * @var array $data['input']
 * @var array $data['radicalform_params']
 *
 *
 * @since 3.2.0
 */

use Joomla\CMS\Factory;


if($section == 'joomshopping')
{
	$order = $data['order'];
	$qr['fields']['COMMENTS'] .= '<br>'.$order->order_number;

}

if($section == 'radicalform')
{

}