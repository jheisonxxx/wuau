<?php
/**
 * Plugin Name: Woocart Popup Lite for WooCommerce
 * Plugin URI: https://festi.team/plugins/woocommerce_cart/
 * Description:  Use this plugin to show lightbox popup cart for add to cart action.
 * Version: 1.1
 * Author: Festi-Team 
 * Author URI: http://festi.team/
 * License: You should have purchased a license from http://codecanyon.net/item/woocommerce-woocart-pro/7992078?ref=Festi_io
 * Copyright 2014  Festi-Team  http://festi.team/
 */
 
if (!class_exists('FestiPlugin')) {
    require_once dirname(__FILE__).'/common/FestiPlugin.php';
}

class WooCartPopUpFestiPlugin extends FestiPlugin
{
    protected $_version = '1.1';
    protected $_languageDomain = 'festi_cart_popup_';
    protected $_optionsPrefix = 'festi_cart_popup_';   
     
    protected function onInit()
    {
        $this->addActionListener('plugins_loaded', 'onLanguagesInitAction');

        if ($this->_isWoocommercePluginNotActiveWhenFestiCartPluginActive()) {
            $this->addActionListener(
                'admin_notices',
                'onDisplayInfoAboutDisabledWoocommerceAction' 
            );
            
            return false;
        }
        
        parent::onInit();
    } // end onInit
    
    private function _isWoocommercePluginNotActiveWhenFestiCartPluginActive()
    {
        return $this->_isFestiCartPluginActive()
               && !$this->_isWoocommercePluginActive();
    } // end _isWoocommercePluginNotActiveWhenFestiCartPluginActive
    
    public function onInstall()
    {
        if (!$this->_isWoocommercePluginActive()) {
            $message = 'WooCommerce not active or not installed.';
            $this->displayError($message);
            exit();
        } 

        $plugin = $this->onBackendInit();
        
        $plugin->onInstall();
    } // end onInstall
    
    public function onUninstall()
    {
        $plugin = $this->onBackendInit();
        
        $plugin->onUninstall();
    } // end onUnistall
    
    public function onBackendInit()
    {
        $path = $this->_pluginPath.'common/WooCartPopUpBackendFestiPlugin.php';
        require_once $path;
        $backend = new WooCartPopUpBackendFestiPlugin(__FILE__);
        return $backend;
    } // end onBackendInit
    
    protected function onFrontendInit()
    {
        $path = $this->_pluginPath.'common/WooCartPopUpFrontendFestiPlugin.php';
        require_once $path;
        $frontend = new WooCartPopUpFrontendFestiPlugin(__FILE__);
        return $frontend;
    } // end onFrontendIn

    
    public function onLanguagesInitAction()
    {
        load_plugin_textdomain(
            $this->_languageDomain,
            false,
            $this->_pluginLanguagesPath
        );
    } // end onLanguagesInitAction
    
    private function _isFestiCartPluginActive()
    {        
        return $this->isPluginActive('woocommerce-woocartpro/plugin.php'); 
    } // end _isFestiCartPluginActive
    
    private function _isWoocommercePluginActive()
    {        
        return $this->isPluginActive('woocommerce/woocommerce.php');
    } // end _isWoocommercePluginActive
    
    public function &getWoocommerceInstance()
    {
        return $GLOBALS['woocommerce'];
    } // end getWoocommerceInstance
    
    public function getPluginIconsPath($dirname = '')
    {
        return $this->getPluginImagesPath('icons/'.$dirname);
    } // end getPluginIconsPath
    
    public function onDisplayInfoAboutDisabledWoocommerceAction()
    {        
        $message = 'WooCommerce WooCart PopUp: ';
        $message .= 'WooCommerce not active or not installed.';
        $this->displayError($message);
    } //end onDisplayInfoAboutDisabledWoocommerceAction
    
    public function convertHexToRgb($hex)
    {
        $hex = str_replace("#", "", $hex);
  
        if (strlen($hex) == 3) {
           
              $r = hexdec(substr($hex,0,1).substr($hex,0,1));
            
              $g = hexdec(substr($hex,1,1).substr($hex,1,1));
              
              $b = hexdec(substr($hex,2,1).substr($hex,2,1));
        } else {
              $r = hexdec(substr($hex,0,2));
            
              $g = hexdec(substr($hex,2,2));
              
              $b = hexdec(substr($hex,4,2));
        }
        
        $rgb = array($r, $g, $b);
      
        return $rgb;
    } // end _convertHexToRgb
}

$GLOBALS['wooCommerceFestiCartPopUp'] = new WooCartPopUpFestiPlugin(__FILE__);
