CREATE TABLE IF NOT EXISTS `#__wt_jshopping_bitrix24_pro` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jshopping_order_id` int(11),
  `bitrix24_lead_id` int(11),
  `bitrix24_deal_id` int(11),
  PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `#__wt_jshopping_bitrix24_pro_products_relationship` (
  `jshopping_product_id` int(11) UNIQUE,
  `bitrix24_product_id` int(11)
) DEFAULT CHARSET=utf8 ;