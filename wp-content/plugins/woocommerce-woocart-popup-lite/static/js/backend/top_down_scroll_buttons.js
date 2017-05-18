jQuery(document).ready(function() 
{
    if( jQuery(window).scrollTop() >= "250") {
        jQuery("#festi-cart-to-top").fadeIn("slow");
    }

    jQuery(window).scroll(function() {
        if (jQuery(window).scrollTop() <= "250") {
            jQuery("#festi-cart-to-top").fadeOut("slow");  
        }
        else {
            jQuery("#festi-cart-to-top").fadeIn("slow");   
        }
    });

    if ( jQuery(window).scrollTop()<= jQuery(document).height()-"999") {
          jQuery("#festi-cart-to-bottom").fadeIn("slow");   
    }
    jQuery(window).scroll(function() {
        if ( jQuery(window).scrollTop()>= jQuery(document).height()-"999") {
            jQuery("#festi-cart-to-bottom").fadeOut("slow");    
        }
        else {
            jQuery("#festi-cart-to-bottom").fadeIn("slow");   
        }
    });

  jQuery("#festi-cart-to-top").click(function() {
      jQuery("html,body").animate({scrollTop:0},"slow");
  })
  jQuery("#festi-cart-to-bottom").click(function(){
      jQuery("html,body").animate(
          {scrollTop: jQuery(document).height()},
          "slow"
      );
  })   
}); 