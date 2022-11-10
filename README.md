[![Version](https://img.shields.io/badge/Version-3.0.0-blue.svg)](https://web-tolk.ru/dev/joomla-plugins/wt-joomshopping-bitrix24-pro.html?utm_source=github) [![Status](https://img.shields.io/badge/Status-stable-green.svg)]() [![JoomlaVersion](https://img.shields.io/badge/Joomla-4.2-orange.svg)]() [![JoomShoppingVersion](https://img.shields.io/badge/JoomShopping-5.1.x-important.svg)]() [![DocumentationRus](https://img.shields.io/badge/Documentation-rus-blue.svg)](https://web-tolk.ru/dev/joomla-plugins/wt-joomshopping-bitrix24-pro.html?utm_source=github) [![DocumentationEng](https://img.shields.io/badge/Documentation-eng-blueviolet.svg)](https://web-tolk.ru/en/dev/joomla-plugins/wt-joomshopping-bitrix24-pro.html?utm_source=github)
![GitHub all releases](https://img.shields.io/github/downloads/sergeytolkachyov/wt_jshopping_b24_pro/total?style=for-the-badge&color=blue)  ![GitHub release (latest by SemVer)](https://img.shields.io/github/downloads/sergeytolkachyov/wt_jshopping_b24_pro/latest/total?style=for-the-badge&color=blue)
# WT JoomShopping Bitrix24 PRO free integration Joomla plugin
Joomla 3 JoomShopping system plugin WT JoomShopping Bitrix24 PRO. The plugin allows you to send JoomShopping order data to Bitrix24 CRM.
PRO-version of the plugin for sending orders from the JoomShopping online store in Bitrix24 CRM.

# v.3.0.0
## Joomla 4 support
Starting from version 3.0.0, the plugin supports only Joomla 4 and JoomShopping 5. The plugin has been rewritten taking into account the new structure of Joomla 4 plugins, which means that it will work with Joomla 5 as well.
## Changes
- removal jquery.coockie.js
- utm tags on js are obtained without jQuery
## New features
- The ability to configure the juxtaposition of JoomShopping and Bitrix 24 products for each product. The mappings are stored in a separate table in the database.
- If product comparisons are set up, then you can transmit information about the products to the lead or transaction in the form of goods, and not commodity items. Thus, the ordered goods will participate in Bitrix 24 reporting systems, will be reserved in the warehouse, etc.
- If product comparisons are set up and you use Bitrix 24 warehouse accounting, then you can specify the default warehouse in the plugin settings (to get balances).
- Updating prices and balances of JoomShopping products when manually editing products in Bitrix 24.
- Logging of JoomShopping product price updates when manually editing products in Bitrix 24.

# v.2.5.0 Video demo. Two-way integration between JoomShopping and Bitrix 24 CRM
[![](https://img.youtube.com/vi/6Uo3LEnKJ2g/0.jpg)](https://www.youtube.com/watch?v=6Uo3LEnKJ2g)

# v.2.0.0 Video demo
[![](https://img.youtube.com/vi/WwhFJbb1kBM/0.jpg)](https://www.youtube.com/watch?v=WwhFJbb1kBM)

# v.2.1.0 Video demo
[![](https://img.youtube.com/vi/pekbg9HX8_c/0.jpg)](https://www.youtube.com/watch?v=pekbg9HX8_c)



# Features
- 18 standard Bitrix24 fields, 36 JoomShopping fields
- Sending data to user fields Bitrix24 (UF_CRM_) (for example, coupon codes, cost and delivery method, packaging margins, etc.)
- Combining multiple JoomShopping fields into a single Bitrix24 field
- Adding products from the basket by entities of the lead's product items.
- UTM-tags. These tags can be used to track the advertising channel that the customer used to make a purchase.
- Creating leads
- Creating deals
- Create new contacts and link them to leads and deals
- Search for matches among existing contacts in CRM by phone and email.
- If a match is made only by mail or email, add information to an existing contact.
- If several different contacts are found by the specified phone number and email, all contact details are recorded in the comment to the lead or transaction.
- Creating contact details
- Extended display of debugging information
- Ability to create leads and deals at certain stages (with a certain status) from Your CRM.
- Add a deal category choice (sales funnel)
- Add an ID of the employee assigned to be responsible for the lead / deal. For this functionality you should save plugin params with webhook data. Then you'll see data from your CRM.
- Joomla Radical Form support
- Nevigen's Quick order plugin support
