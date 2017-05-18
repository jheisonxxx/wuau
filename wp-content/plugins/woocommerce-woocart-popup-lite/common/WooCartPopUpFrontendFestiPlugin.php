<?php

class WooCartPopUpFrontendFestiPlugin extends WooCartPopUpFestiPlugin
{
    protected function onInit()
    {
        $this->_onInitPluginCookie();
        
        $this->addActionListener('wp_enqueue_scripts', 'onInitJsAction');

        $this->addActionListener('wp_print_styles', 'onInitCssAction');

        $this->addActionListener(
            'wp_ajax_nopriv_remove_product',
            'onRemoveCartProductAction'
        );
        
        $this->addActionListener(
            'woocommerce_add_to_cart',
            'showPopupContainerAction'
        );
        
        $this->addActionListener(
            'wp_ajax_remove_product',
            'onRemoveCartProductAction'
        );
        
        $this->addFilterListener(
            'add_to_cart_fragments',
            'onDisplayCartFilter'
        );

        $options = $this->getOptions('settings');

        $this->appendCssToHeaderForCartCustomize($options);
        
        $this->appendHiddenPopupContainer($options);
    } // end onInit
    
    public function showPopupContainerAction()
    {
        $this->addActionListener(
            'wp_head',
            'appendCallScriptPopupContainerAction'
        );
    } // end showPopupContainerAction
    
    public function appendCallScriptPopupContainerAction()
    {
        echo $this-> fetch('popup_call_script.phtml');
    } // end appendCallScriptPopupContainerAction
    
    public function getPluginCssUrl($fileName) 
    {
        return $this->_pluginCssUrl.'frontend/'.$fileName;
    } // end getPluginCssUrl
    
    public function getPluginJsUrl($fileName)
    {
        return $this->_pluginJsUrl.'frontend/'.$fileName;
    } // end getPluginJsUrl
    
    public function getPluginTemplatePath($fileName)
    {
        return $this->_pluginTemplatePath.'frontend/'.$fileName;
    } // end getPluginTemplatePath
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-cart-general',
            'general.js',
            'festi-cart-popup',
            $this->_version
        );
        $this->onEnqueueJsFileAction(
            'festi-cart-popup',
            'popup.js',
            'jquery',
            $this->_version
        );
        wp_localize_script(
            'festi-cart-general',
            'fesiCartAjax',
            array('ajaxurl' => admin_url('admin-ajax.php'))
        );
    } // end onInitJsAction
    
    public function onRemoveCartProductAction()
    {
        if ($this->_hasDeleteItemInRequest()) {
            $woocommerce = $this->getWoocommerceInstance();
            $item = $_POST['deleteItem'];
            $woocommerce->cart->set_quantity($item , 0);
            
            echo $woocommerce->cart->get_cart_contents_count();
        }       
        
        exit();
    } // end onRemoveCartProductAction
    
    private function _hasDeleteItemInRequest()
    {
        return array_key_exists('deleteItem', $_POST) 
               && !empty($_POST['deleteItem']);
    } // end _hasDeleteItemInRequest
    
    public function onInitCssAction()
    {
        $this->onEnqueueCssFileAction(
            'festi-cart-styles',
            'style.css',
            array(),
            $this->_version
        );
    } // end onInitCssAction
    
    private function _onInitPluginCookie()
    {
        $value = $this->getPluginCookie();
        
        if (!$value) {
            return false;
        }
        
        if (!$this->_hasValueInCookieArray('woocommerce_cart_hash')) {
            $this->_setCookieForWoocommerceCartHash(
                'woocommerce_cart_hash',
                $value
            );     
        }

        $cookieName = 'festi_cart_for_woocommerce_storage';
        if (!$this->_hasValueInCookieArray($cookieName)) {
            $this->_setCookieForWoocommerceCartHash(
                'festi_cart_for_woocommerce_storage',
                $value
            );
        } else if ($this->_isChangedCookieValue($value)) {
            add_action(
                'wp_enqueue_scripts',
                array(&$this, 'onClearStorageAction')
            );
            
           $this->_setCookieForWoocommerceCartHash(
                'festi_cart_for_woocommerce_storage',
                $value
            );
        }
        
        return true;

    } // end _onInitPluginCookie
    
    public function getPluginCookie()
    {
        $value = array();
        
        $value = $this->getOptions('cookie');

        return $value[0];
    } // end getPluginCookie

    private function _setCookieForWoocommerceCartHash($name, $value, $time = 0)
    {
        setcookie(
            $name,
            $value, 
            $time,
            COOKIEPATH,
            COOKIE_DOMAIN
        );
    } // end _setCookieForWoocommerceCartHash
    
    public function fetchDropdownListContent()
    {
        $vars = array(
            'woocommerce' => $this->getWoocommerceInstance(),
            'settings'    => $this->getOptions('settings')
        );
        
       return $this->fetch('dropdown_list_content.phtml', $vars);
    } // end fetchDropdownListContent
    
    private function _hasValueInCookieArray($cookieName)
    {
        return isset($_COOKIE[$cookieName])
               && !empty($_COOKIE[$cookieName]);
    } // end _hasValueInCookieArray
    
    private function _isChangedCookieValue($value)
    {
        return $_COOKIE['festi_cart_for_woocommerce_storage'] != $value;
    } // end _isChangedCookieValue
    
    public function onClearStorageAction()
    {
        $this->onEnqueueJsFileAction(
            'festi-cart-clear-storage',
            'clear_storage.js',
            'jquery'
        );
    } // end onHeadAction
    
    public function appendHiddenPopupContainer($options)
    {
        if (!$this->_isEnablePopUpWindow($options)) {
            return false; 
        }
        
        $this->addActionListener(
            'wp_footer',
            'onDisplayPopupContainerAction'
        );
    } // end appendHiddenPopupContainer
    
    private function _isEnablePopUpWindow($options)
    {
        return array_key_exists('popup', $options);      
    } // end _isEnablePopUpWindow

    public function onDisplayPopupContainerAction()
    {
        $vars = array(
            'woocommerce' => $this->getWoocommerceInstance(),
            'settings'    => $this->getOptions('settings')
        );
        
        $content = $this->fetch('popup_content.phtml', $vars);
        
        $vars['content'] = $content;
        
        echo $this->fetch('popup_container.phtml', $vars);
    } // end onDisplayPopupContainerAction

    public function appendCssToHeaderForCartCustomize($options)
    {
        $this->addActionListener(
            'wp_head',
            'addCssToHeaderAction'
        );
    } // end appendCssToHeaderForCartCustomize
    
    public function addCssToHeaderAction()
    {
        $vars = array(
            'settings' => $this->getOptions('settings'),
            'woocommerce'=> $this->getWoocommerceInstance()
        );

        echo $this->fetch('popup_customize_style.phtml', $vars);
    } // end addCssToHeaderAction
    
    public function fetchCart($template = 'cart.phtml')
    {
        $vars = array(
            'woocommerce' => $this->getWoocommerceInstance(),
            'settings'    => $this->getOptions('settings')
        );

        return $this->fetch($template, $vars);
    } // end fetchCart
    
    public function onDisplayCartFilter($cssSelectors)
    {
        $content = $this->fetchCart('popup_content.phtml');
        
        $selectorName = '.festi-cart-pop-up-products-content';
        
        $cssSelectors[$selectorName] = $content;
        
        return $cssSelectors;
    } // end onDisplayCartFilter
}