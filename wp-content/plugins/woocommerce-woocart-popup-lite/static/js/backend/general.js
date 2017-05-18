jQuery(document).ready(function() 
{
    jQuery('.festi-cart .festi-cart-icon span').click(function() 
    {
        if(!jQuery(this).hasClass('festi-cart-selected')) {           
            var currentImg = jQuery('img',this).attr('id');
            
            jQuery("input[name='iconList']").val(currentImg);

            jQuery('.festi-cart span.festi-cart-selected').removeClass("festi-cart-selected");
            
            jQuery(this).addClass("festi-cart-selected");
        }
    });

    jQuery('.festi-cart-help-tip').poshytip({
        className: 'tip-twitter',
        showTimeout:100,
        alignTo: 'target',
        alignX: 'center',
        alignY: 'bottom',
        offsetY: 5,
        allowTipHover: false,
        fade: true,
        slide: false
    });

    jQuery('input[data-event=visible]').change(function(){ 
       
        var className = jQuery(this).attr("name") + '-' + jQuery(this).data('event');

        if(jQuery(this).attr("checked")){ 
            
            jQuery('.'+className).fadeIn();         
        } else { 
            jQuery('.'+className).fadeOut(100);
        } 
    });
    
    jQuery('input[type=number]').live('keypress', function(e)
    {
        if( e.which!=8 && e.which!=0 && (e.which<48 || e.which>57))
        {
            return false;
        } 
    });
    
    jQuery('select[data-event=visible]').change(function(){ 
        var className = jQuery(this).attr("name") + '-' + jQuery(this).data('event');

        if(jQuery(this).val() == 'disable'){ 
            jQuery('.'+className).fadeOut(100);      
        } else { 
            jQuery('.'+className).fadeIn();
        }
    });
    
    jQuery('input[data-event=change-slider]').change(function(){
        var sliderSelector = jQuery(this).attr('name');
        var value = jQuery(this).val(); 
        
        jQuery( "#festi-cart-slider-" + sliderSelector).slider("value", value);
    });
    
    jQuery('.festi-cart-section-item a[href^="#"]').click(function(){
        
        var target = jQuery(this).attr('href');
 
        var offset = jQuery(target).offset();

        jQuery('html, body').animate({scrollTop: offset.top - 35}, 300);
        return false; 
   });
   
               
    jQuery('.festi-cart-help  input.festi-cart-short-code').click(function() 
    {
       jQuery(this).select();
    });
    
    jQuery('.festi-cart  textarea.festi-cart-export').click(function() 
    {
       jQuery(this).select();
    });      
    
        
    jQuery('fieldset  span.festi-cart-collapse-block').live('click', function() {
        var fildset = jQuery(this).parent().parent();
        var selector = 'div.festi-cart-fildset-table.'+fildset.attr('class');

        jQuery('fieldset span.festi-cart-expand-block').hide();
        jQuery('fieldset span.festi-cart-collapse-block').show();
                
        jQuery(this).hide();
        jQuery('fieldset.' + fildset.attr('class') + ' span.festi-cart-expand-block').show();
        
        jQuery('fieldset div.festi-cart-fildset-table').slideUp(500);
        jQuery(selector).slideDown(600,
            function () 
            {
                var offset = jQuery('fieldset.' + fildset.attr('class') + ' div.festi-cart-fildset-header').offset();
                jQuery('html, body').animate({scrollTop: offset.top - 50}, 300);
            }
        );
    });
    
     jQuery('fieldset  span.festi-cart-expand-block').live('click', function() {
        var fildset = jQuery(this).parent().parent();
        var selector = 'div.festi-cart-fildset-table.'+fildset.attr('class');
        
        jQuery(this).hide();
        jQuery('fieldset.' + fildset.attr('class') + ' span.festi-cart-collapse-block').show();
        
        jQuery(selector).slideUp(600);
    });
    
 
}); 