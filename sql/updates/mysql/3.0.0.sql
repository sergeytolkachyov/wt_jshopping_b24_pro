CREATE TABLE IF NOT EXISTS `#__wt_jshopping_bitrix24_pro_products_relationship` (
    `jshopping_product_id` int(11) UNIQUE,
    `bitrix24_product_id`  int(11)
) DEFAULT CHARSET = utf8;