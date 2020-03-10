jQuery(document).ready(function ($) {
    jQuery.urlParam = function (name) {
        var results = new RegExp('[\?&]' + name + '=([^]*)').exec(window.location.href);
        if (results == null) {
            return null;
        } else {
            return results[1] || 0;
        }
    }

    let utms = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term'
    ];


    utms.forEach(function(item){

        try{
            var utm = jQuery.urlParam(item);
            console.log("utm from foreach: "+utm);
            if(utm != null || utm != ""){
                console.log(utm);
                utm = jQuery.urlParam(item).split('&')[0];

                if (utm && (jQuery.cookie(item) == null || jQuery.cookie(item) == "")) {
                    jQuery.cookie(item, utm);
                    console.log(item + " = "+utm);
                }
            }
        } finally{
            return;
        }



    });

});