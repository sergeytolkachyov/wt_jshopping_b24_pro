CREATE TABLE IF NOT EXISTS `#__wt_jshopping_bitrix24_pro_prod_attr_to_variations` (
    `product_id` int(11) UNIQUE,
    `product_attr_id` int(11),
    `b24_product_variation_id` int(11)
) DEFAULT CHARSET=utf8 ;