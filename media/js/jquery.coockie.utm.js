jQuery(document).ready(function ($) {
    let utms = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term'
    ];
    let plugin_version = Joomla.getOptions('plg_system_wt_jshopping_b24_pro_version');
	console.info("WT JoomShopping Bitrix24 PRO v." + plugin_version + " Joomla plugin");
    
	utms.forEach(function(item){
        console.log("From COOKIE - " + item + " : " + jQuery.cookie(item));
	    try{
			const url = new URL(window.location.href);
			let utm = url.searchParams.get(item);
		
			console.log("From URL - " + item + " : " + utm);

            if(utm != null || utm != ""){

                if (utm && (jQuery.cookie(item) == null || jQuery.cookie(item) == "")) {
					utm = encodeURIComponent(utm);
                    jQuery.cookie(item, utm);
                }
            }
        } finally{
            return;
        }
    });
});