jQuery( document ).ready(function() {
    
    var lastUsedShowProducts;
    
    var productsListSelector = '.festi-cart-products';
    
    var festiSetTimeout;
    
    function getPositionProductList(element)
    {
        var windowWidth = jQuery(window).width();
        var offset = element.offset();
        
        var height = element.outerHeight();
        
        var width = element.outerWidth();
        
        var selectorWidth = jQuery(productsListSelector).width();
           
        if ((offset.left + selectorWidth) > windowWidth) {
            offset.left = offset.left - selectorWidth + width; 
        }
        offset.top = offset.top + height - 1; 
        return offset;
    }

    jQuery('.festi-cart-products').live('hover', function() {
        festiCartProductsMouseRemove = 0;
    });

    jQuery('.festi-cart.festi-cart-click').live('click', function() {
 
        festiCartClick(this);   

        return false;
    });
    
    
    jQuery('body').live('click', function(event) 
    {      
        if(jQuery(event.target).closest(productsListSelector).length == 0) {
            jQuery(productsListSelector).hide();
            jQuery('.festi-cart-arrow').hide();
            jQuery("a.festi-cart").removeClass("festi-cart-active");
        }
    });
    
    function festiCartClick(element)
    {
        if(jQuery(productsListSelector).css('display') != 'none' && jQuery(element).get(0) == lastUsedShowProducts.get(0)) {
            jQuery(productsListSelector).hide();
            jQuery('.festi-cart-arrow').hide();
            jQuery(element).removeClass("festi-cart-active");
        } else {
            jQuery(productsListSelector).show(); 
            lastUsedShowProducts = jQuery(element);
      
            offset = getPositionProductList(jQuery(element));
            jQuery(productsListSelector).offset({top:offset.top, left:offset.left});
            
            elementOffset = jQuery(element).offset();
            jQuery('.festi-cart-arrow').show();
            jQuery('.festi-cart-arrow').offset({top:offset.top, left:elementOffset.left+(jQuery(element).width()/2)});
            jQuery(element).addClass("festi-cart-active");
        }
    }
    
    function festiCartMouseOver(element)
    {
        festiCartProductsMouseRemove = 0;

        jQuery(productsListSelector).show(); 
        lastUsedShowProducts = jQuery(element);
  
        offset = getPositionProductList(jQuery(element));
        jQuery(productsListSelector).offset({top:offset.top, left:offset.left});
        elementOffset = jQuery(element).offset();
        jQuery('.festi-cart-arrow').show();
        jQuery('.festi-cart-arrow').offset({top:offset.top, left:elementOffset.left+(jQuery(element).width()/2)});
        jQuery(element).addClass("festi-cart-active");
    }
    
    
    jQuery('.festi-cart.festi-cart-hover').live('mouseover', function() {
        
        festiCartMouseOver(this);   
        
        return false;
    });
    
    jQuery('body').live('mouseover', function(event) 
    {
        if(jQuery(event.target).closest(productsListSelector).length == 0 && jQuery(".festi-cart.festi-cart-hover").length != 0) {
            clearTimeout(festiSetTimeout);
            festiCartProductsMouseRemove = 1;
            festiSetTimeout = setTimeout(function () { hideProductsList(); }, 100);
        }
    });
    
    function hideProductsList()
    {
        if(festiCartProductsMouseRemove == 1) {
           jQuery(productsListSelector).hide();
           jQuery('.festi-cart-arrow').hide();
           jQuery("a.festi-cart").removeClass("festi-cart-active");
        }       
    }
    
    jQuery('.festi-cart-remove-product').live('click', function() 
    {
        var data = {
            action: 'remove_product',
            deleteItem: jQuery(this).attr('href')
        };
    
                
        var variable = this;

        jQuery.post(fesiCartAjax.ajaxurl, data, function(productCount) {
            
            var productCount = productCount;
            
            var data = {
                    action: 'woocommerce_get_refreshed_fragments'
            };
     
            jQuery.post(fesiCartAjax.ajaxurl, data, function(response) {
                fragments = response.fragments;
        
                if (fragments) {
                    jQuery.each(fragments, function(key, value) {
                        jQuery(key).replaceWith(value);
                    });

                    if (!jQuery(variable).hasClass("fecti-cart-from-widget")){
                        jQuery('a.festi-cart').addClass("festi-cart-active");                        
                    }
                }
                
                if(productCount < 1) {
                    var parent = jQuery(lastUsedShowProducts).parent()
                    if(parent.hasClass("widget")) {
                        jQuery(productsListSelector).fadeOut();
                    }
                }

            })

            //lastUsedShowProducts.addClass("festi-cart-active");
            
        });
        
        return false;
    });
    
    jQuery(window).scroll(function () {
        if(jQuery(productsListSelector).css('display') != 'none' && jQuery(productsListSelector).length!=0) {
            var offset = getPositionProductList(lastUsedShowProducts);
            if((offset.top - jQuery(document).scrollTop()) > 0) {
               jQuery(productsListSelector).offset({top:offset.top, left:offset.left}); 
               elementOffset = jQuery(lastUsedShowProducts).offset();
                jQuery('.festi-cart-arrow').offset({top:offset.top, left:elementOffset.left+(jQuery(lastUsedShowProducts).width()/2)});
            } else {
                jQuery(productsListSelector).hide();
                jQuery('.festi-cart-arrow').hide();
                jQuery("a.festi-cart").removeClass("festi-cart-active");
            }
              
        }
    });
    
    
    if (jQuery('.festi-cart-horizontal-position-center').length > 0) {
        var documentWidth = jQuery(document).width();
        var windowCartOuterWidth = jQuery('.festi-cart-horizontal-position-center').outerWidth()
        
        var leftPosition = (documentWidth - windowCartOuterWidth)/2;
    
        jQuery('.festi-cart-horizontal-position-center').css({
            left: leftPosition,
        });
        
        jQuery('.festi-cart-horizontal-position-center').show();
    }
    
     if (jQuery('.festi-cart-vertical-position-middle').length > 0) {
        var documentHeight = jQuery(document).height();
        var windowCartOuterHeight = jQuery('.festi-cart-vertical-position-middle').outerHeight()
        
        var topPosition = (documentHeight- windowCartOuterHeight)/2;
    
        jQuery('.festi-cart-vertical-position-middle').css({
            top: topPosition,
        });
        
        jQuery('.festi-cart-vertical-position-middle').show();
    }

    jQuery('body').on('added_to_cart',function() {
        jQuery('#festi-cart-pop-up-content').bPopup({
            modalClose: true,
            positionStyle: 'fixed'
        });
    });
    
})