CREATE TABLE IF NOT EXISTS `#__wt_jshopping_bitrix24_pro_prod_attr_to_variations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11),
    `product_attr_id` int(11),
    `b24_product_variation_id` int(11),
    PRIMARY KEY (`id`)
) DEFAULT CHARSET=utf8 ;

ALTER TABLE `#__wt_jshopping_bitrix24_pro_products_relationship` ADD `bitrix24_product_main_variaton_id` INT NOT NULL