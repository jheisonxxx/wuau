<?php

class WooCartPopUpBackendFestiPlugin extends WooCartPopUpFestiPlugin
{
    protected $_menuOptions = array(
        'settings' => "Settings",
        'help'   => "Help",
    );
    
    protected $_defaultMenuOption = 'settings';
    
    protected $_fileSystem = '';
    
    protected function onInit()
    {
        $this->addActionListener('admin_menu', 'onAdminMenuAction');
    } // end onInit
    
    public function _onFileSystemInstanceAction()
    {
        $this->_fileSystem = $this->getFileSystemInstance();
    } // end _onFileSystemInstanceAction
    
    public function onInstall($refresh = false)
    {   
        if (!$this->_fileSystem) {
            $this->_fileSystem = $this->getFileSystemInstance();
        }
        
        if ($this->_hasPermissionToCreateCacheFolder()) {
            $this->_fileSystem->mkdir($this->_pluginCachePath, 0777);
        }

        if ($refresh) {
            return true;
        }
                
        $this->_doInitDefaultOptions('settings');

        $this->_updateCookieCacheFile();       
    } // end onInstal
    
    private function _hasPermissionToCreateCacheFolder()
    {
        return ($this->_fileSystem->is_writable($this->_pluginPath)
               && !file_exists($this->_pluginCachePath));
    } // end _hasPermissionToCreateFolder
    
    public function onUninstall($refresh = false)
    {
        delete_option($this->_optionsPrefix.'settings');
        delete_option($this->_optionsPrefix.'coockie');
    } // end onUninstall           
    
    private function _updateCookieCacheFile()
    {
        $time = time();
        $content = md5($time);
        $content = array($content);
        $this->updateOptions('cookie', $content);
    } // end _updateCookieCacheFile
    
    public function getPluginTemplatePath($fileName)
    {
        return $this->_pluginTemplatePath.'backend/'.$fileName;
    } // end getPluginTemplatePath
    
    public function getPluginCssUrl($fileName) 
    {
        return $this->_pluginCssUrl.'backend/'.$fileName;
    } // end getPluginCssUrl
    
    public function getPluginJsUrl($fileName)
    {
        return $this->_pluginJsUrl.'backend/'.$fileName;
    } // end getPluginJsUrl
    
    public function onAdminMenuAction() 
    {
        $page = add_menu_page(
            __('WooCart PopUp Settings', $this->_languageDomain), 
            __('WooCart PopUp', $this->_languageDomain), 
            'manage_options', 
            'festi-cart-popup', 
            array(&$this, 'onDisplayOptionPage'), 
            $this->getPluginImagesUrl('icon_16x16.png')
        );
        
        $this->addActionListener(
            'admin_print_scripts-'.$page, 
            'onInitJsAction'
        );
        
        $this->addActionListener(
            'admin_print_styles-'.$page, 
            'onInitCssAction'
        );
        
        $this->addActionListener(
            'admin_head-'.$page,
            '_onFileSystemInstanceAction'
        );
    } // end onAdminMenuAction
    
    public function onInitJsAction()
    {
        $this->onEnqueueJsFileAction('jquery');
        $this->onEnqueueJsFileAction(
            'festi-cart-popup-general',
            'general.js',
            'jquery-ui-slider'
        );
        
        $this->onEnqueueJsFileAction(
            'festi-cart-popup-colorpicker',
            'colorpicker.js',
            'jquery'
        );
        
        $this->onEnqueueJsFileAction(
            'festi-cart-popup-tooltip',
            'tooltip.js',
            'festi-cart-popup-colorpicker'
        );
        
        $this->onEnqueueJsFileAction(
            'jquery-ui-slider',
            false,
            'jquery'
        );
        
        $this->onEnqueueJsFileAction(
            'festi-cart-popup-top-down-scroll-buttons',
            'top_down_scroll_buttons.js',
            'jquery'
        );
        
    } // end onInitJsAction
    
    public function onInitCssAction()
    {
        $this->onEnqueueCssFileAction(
            'festi-cart-popup-styles',
            'style.css'
        );
        
        $this->onEnqueueCssFileAction(
            'festi-cart-popup-colorpicker',
            'colorpicker.css'
        );
        
        $this->onEnqueueCssFileAction(
            'festi-cart-popup-tooltip',
            'tooltip.css'
        );
        
        $this->onEnqueueCssFileAction(
            'festi-cart-popup-slider',
            'slider.css'
        );
        
        $this->onEnqueueCssFileAction(
            'festi-cart-popup-top-down-scroll-buttons',
            'top_down_scroll_buttons.css'
        );
    } // end onInitCssAction
    
    private function _doInitDefaultOptions($option)
    {
        $methodName = $this->getMethodName('load', $option);

        $method = array($this, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }

        $options = call_user_func_array($method, array());
        foreach ($options as $ident => &$item) {
            if ($this->_hasDefaultValueInItem($item)) {
                $values[$ident] = $item['default'];
            }
        }
        unset($item);

        $this->updateOptions($option, $values);

    } // end _doInitDefaultOptions
    
    public function getMethodName($prefix, $option)
    {
        $option = explode('_', $option);
        
        $option = array_map('ucfirst', $option);
        
        $option = implode('', $option);
        
        $methodName = $prefix.$option;
        
        return $methodName;
    } // end getMethodName
    
    private function _hasDefaultValueInItem($item)
    {
        return isset($item['default']);
    } //end _hasDefaultValueInItem

    public function onDisplayOptionPage()
    {
        $menu = $this->fetch('menu.phtml');
        echo $menu;
        
        echo $this->fetch('banner.phtml');
        
        $methodName = 'fetchOptionPage';
        
        if ($this->hasOptionPageInRequest()) {
            $postfix = $_GET['tab'];
        } else {
            $postfix = $this->_defaultMenuOption;
        }
        $methodName.= ucfirst($postfix);
        
        $method = array(&$this, $methodName);
        
        if (!is_callable($method)) {
            throw new Exception("Undefined method name: ".$methodName);
        }
        
        call_user_func_array($method, array());
    } // end onDisplayOptionPage
    
    public function fetchOptionPageSettings()
    {        
        $vars = array();

        if ($this->_isRefreshPlugin()) {
            $this->onRefreshPlugin();

            $message = __(
                'Success update plugin',
                $this->_languageDomain
            );
            
            $this->displayUpdate($message);
        }
        
        $this->_displayFoldersAccessErrors();

        
        if ($this->isUpdateOptions('save')) {
            try {
                $this->_doUpdateOptions($_POST);
                           
                $message = __(
                    'Success update settings',
                    $this->_languageDomain
                );
                
                $this->displayUpdate($message);               
            } catch (Exception $e) {
                $message = $e->getMessage();
                $this->displayError($message);
            }
        }
        
        
        $options = $this->getOptions('settings');
        
        $vars['fieldset'] = $this->getOptionsFieldSet();        
        $vars['currentValues'] = $options;
        
        echo $this->fetch('settings_page.phtml', $vars);  
    } // end fetchOptionPageSettings
    
    private function _isRefreshPlugin()
    {
        return array_key_exists('refresh_plugin', $_GET);
    } // end _isRefreshPlugin
    
    public function onRefreshPlugin()
    {
        //$this->onUninstall(true);
        $this->onInstall(true);
    } // end onRefreshPlugin
    
    private function _doUpdateOptions($newSettings = array())
    {
        $options = $this->getOptions('settings');

        $this->updateOptions('settings', $newSettings);
        
        $this->_updateCookieCacheFile();
    } // end _doUpdateOptions

    private function _displayFoldersAccessErrors()
    {        
        $caheFolderErorrs = $this->_detectTheCacheFolderAccessErrors();
        
        if ($caheFolderErorrs) {
            echo $this->fetch('refresh.phtml');
        }
    } // end _displayFoldersAccessErrors
    
    private function _detectTheCacheFolderAccessErrors()
    {
        if (!$this->_fileSystem->is_writable($this->_pluginCachePath)) {
            
            $message = __(
                "Caching does not work! ",
                $this->_languageDomain
            );
            
            $message .= __(
                "You don't have permission to access: ",
                $this->_languageDomain
            );
            
            $path = $this->_pluginCachePath;
            
            if (!$this->_fileSystem->exists($path)) {
                $path = $this->_pluginPath;
            }
            
            $message .= $path;
            $message .= $this->fetch('manual_url.phtml');
            
            $this->displayError($message);
            
            return true;
        }
        
        return false;
    } // end _detectTheCacheFolderAccessErrors

    public function isUpdateOptions($action)
    {
        return array_key_exists('__action', $_POST)
               && $_POST['__action'] == $action;
    } // end isUpdateOptions
    
    public function getOptionsFieldSet()
    {
        $fildset = array(
            'general' => array(
                'legend' => __('General', $this->_languageDomain),
                'display' => true
            ),
            'lightbox' => array(
                'legend' => __(
                    'Customization for lightbox',
                    $this->_languageDomain
                )
            ),
            'headerText' => array(
                'legend' => __(
                    'Customization for Header text',
                    $this->_languageDomain
                )
            ),
            'continueButton' => array(
                'legend' => __(
                    'Customization for Continue Shopping Button',
                    $this->_languageDomain
                )
            ),
        );
        
        $settings = $this->loadSettings();
        
        if ($settings) {
            foreach ($settings as $ident => &$item) {
                if (array_key_exists('fieldsetKey', $item)) {
                   $key = $item['fieldsetKey'];
                   $fildset[$key]['filds'][$ident] = $settings[$ident];
                }
            }
            unset($item);
        }
        
        return $fildset;
    } // end getOptionsFieldSet
    
    public function loadSettings()
    {
        $settings = array(
            'popup' => array(
                'caption' => __(
                    'Show',
                    $this->_languageDomain
                ),
                'lable' => __(
                    'Enable displaying popup after adding product',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'general',
                'backlight' => 'light'
            ),
            'dropdownListAmountProducts' => array(
                'caption' => __(
                    'Set Maximum Number of Products',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox'
            ),
            'productListFontSize' => array(
                'caption' => __('Font Size', $this->_languageDomain),
                'lable' => 'px',
                'hint' => __(
                    'Change font size', 
                    $this->_languageDomain
                ),
                'type' => 'input_size',
                'default' => 13,
                'class' => 'festi-cart-font-size',
                'fieldsetKey' => 'lightbox'
            ),
            'popupWidth' => array(
                'caption' => __(
                    'Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 400,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 100,
                'max' => 1000,
                'fieldsetKey' => 'lightbox'
            ),
            'popupPadding' => array(
                'caption' => __(
                    'Padding',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox'
            ),
            'popupBackAroundDivider' => array(
                'caption' => __(
                    'Blacked out background',
                    $this->_languageDomain
                ),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'popupAroundBackColor' => array(
                'caption' => __(
                    'Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#000000',
                'fieldsetKey' => 'lightbox'
            ),
            'popupAroundBackOpacity' => array(
                'caption' => __(
                    'Opacity',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 2,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 10,
                'fieldsetKey' => 'lightbox'
            ),
            'popupBackgroundDivider' => array(
                'caption' => __('Background', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'popupBackgroundColor' => array(
                'caption' => __(
                    'Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#ffffff',
                'fieldsetKey' => 'lightbox'
            ),
            'popupBackgroundOpacity' => array(
                'caption' => __(
                    'Opacity',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 10,
                'fieldsetKey' => 'lightbox'
            ),
            'popupShadowDivider' => array(
                'caption' => __('Shadow', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'popupShadowColor' => array(
                'caption' => __(
                    'Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the shadow color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#5e5e5e',
                'fieldsetKey' => 'lightbox'
            ),
            'popupShadowWidth' => array(
                'caption' => __(
                    'Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 500,
                'fieldsetKey' => 'lightbox'
            ),
            'popupShadowBlur' => array(
                'caption' => __(
                    'Blur',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 1000,
                'fieldsetKey' => 'lightbox'
            ),
            'popupBorderDivider' => array(
                'caption' => __('Border', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'popupBorderWidth' => array(
                'caption' => __(
                    'Border Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 3,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox',
            ),
            'popupBorderRadius' => array(
                'caption' => __(
                    'Border Radius',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'lightbox',
            ),
            'popupBorderColor' => array(
                'caption' => __(
                    'Border Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#00a8ca',
                'fieldsetKey' => 'lightbox',
            ),
            'popupCloseButtonDivider' => array(
                'caption' => __(
                    'Close button',
                    $this->_languageDomain
                ),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox',
            ),
            'displayPopupCloseButton' => array(
                'caption' => __('Display', $this->_languageDomain),
                'lable' => __(
                    'Enable close button',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'popupCloseButtonSize' => array(
                'caption' => __(
                    'Size',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 30,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 5,
                'max' => 50,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayPopupCloseButton',
            ),
            'popupCloseButtonColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#00a8ca',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayPopupCloseButton',
            ),
            'popupCloseButtonHoverColor' => array(
                'caption' => __(
                    'Font Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#72ddf2',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayPopupCloseButton',
            ),
            'popupCloseButtonMarginTop' => array(
                'caption' => __(
                    'Margin Top',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayPopupCloseButton',
            ),
            'popupCloseButtonMarginRight' => array(
                'caption' => __(
                    'Margin Right',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayPopupCloseButton',
            ),
            'popupProductsListDivider' => array(
                'caption' => __(
                    'Products List',
                    $this->_languageDomain
                ),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox',
            ),
            'popupProductsListScroll' => array(
                'caption' => __(
                    'Scrollbar',
                    $this->_languageDomain
                ),
                'lable' => __(
                    'Enable scrollbar for products list',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'popupProductsListScrollHeight' => array(
                'caption' => __(
                    'Height',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 200,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 100,
                'max' => 1000,
                'fieldsetKey' => 'lightbox',
            ),
            'productTitleDivider' => array(
                'caption' => __('Product Title', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'displayProductTitle' => array(
                'caption' => __('Display', $this->_languageDomain),
                'lable' => __(
                    'Enable product title',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'productFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#00497d',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductTitle'
            ),
            'productHoverFontColor' => array(
                'caption' => __(
                    'Font Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#8094ed',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductTitle'
            ),
            'popupHeaderTextDivider' => array(
                'caption' => __(
                    'Header Text Font Styles',
                    $this->_languageDomain
                ),
                'type'    => 'divider',
                //'fieldsetKey' => 'headerText',
            ),
            'popupHeaderText' => array(
                'caption' => __(
                    'Text',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the text',
                    $this->_languageDomain
                ),
                'type' => 'input_text',
                'default' => __(
                    'Item Added to your Cart!',
                    $this->_languageDomain
                ),
                'fieldsetKey' => 'headerText',
            ),
            'popupHeaderTextAlign' => array(
                'caption' => __(
                    'Alignment',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select the location of text in popup header', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'left' => __('Left', $this->_languageDomain),
                    'center' => __('Center', $this->_languageDomain),
                    'right' => __('Right', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'center',
                'fieldsetKey' => 'headerText',
            ),
            'popupHeaderTextFontSize' => array(
                'caption' => __('Font Size', $this->_languageDomain),
                'lable' => 'px',
                'hint' => __(
                    'Change font size', 
                    $this->_languageDomain
                ),
                'type' => 'input_size',
                'default' => 20,
                'class' => 'festi-cart-font-size',
                'fieldsetKey' => 'headerText',
            ),
            'popupHeaderTextColor' => array(
                'caption' => __('Font Color', $this->_languageDomain),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#5b9e2b',
                'fieldsetKey' => 'headerText',
            ),
            'popupHeaderTextMarginTop' => array(
                'caption' => __(
                    'Margin Top',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'headerText',
            ),
             'popupHeaderTextMarginBottom' => array(
                'caption' => __(
                    'Margin Bottom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 20,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'headerText',
            ),
            'popupHeaderTextMarginLeft' => array(
                'caption' => __(
                    'Margin Left',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'headerText',
            ),
            'popupHeaderTextMarginRight' => array(
                'caption' => __(
                    'Margin Right',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'headerText',
            ),
            'displayPopupContinueButton' => array(
                'caption' => __(
                    'Continue Shopping Button',
                    $this->_languageDomain
                ),
                'lable' => __(
                    'Enable Continue Shopping button',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'continueButton',
                'backlight' => 'light'
            ),
            'popupContinueButtonText' => array(
                'caption' => __(
                    'Title',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the button text',
                    $this->_languageDomain
                ),
                'type' => 'input_text',
                'default' => __('Continue Shopping', $this->_languageDomain),
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonFontSize' => array(
                'caption' => __('Font Size', $this->_languageDomain),
                'lable' => 'px',
                'hint' => __(
                    'Change font size', 
                    $this->_languageDomain
                ),
                'type' => 'input_size',
                'default' => 20,
                'class' => 'festi-cart-font-size',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonAlign' => array(
                'caption' => __(
                    'Alignment',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select the location of button in popup footer', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'left' => __('Left', $this->_languageDomain),
                    'center' => __('Center', $this->_languageDomain),
                    'right' => __('Right', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'center',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonWidthType' => array(
                'caption' => __(
                    'Width Type',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select width type', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'auto' => __('Auto', $this->_languageDomain),
                    'full' => __('Full Width', $this->_languageDomain),
                    'custom' => __('Custom', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'auto',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonWidth' => array(
                'caption' => __('Custom Width', $this->_languageDomain),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 160,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 50,
                'max' => 1000,
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonPaddingTop' => array(
                'caption' => __(
                    'Padding Top',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonPaddingBottom' => array(
                'caption' => __(
                    'Padding Bottom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 10,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonBackground' => array(
                'caption' => __(
                    'Background Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#ffffff',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonHoverBackground' => array(
                'caption' => __(
                    'Background Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#ffffff',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#00a8ca',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonHoverFontColor' => array(
                'caption' => __(
                    'Font Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#72ddf2',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonBorderWidth' => array(
                'caption' => __(
                    'Border Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 15,
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonBorderRadius' => array(
                'caption' => __(
                    'Border Radius',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonBorderColor' => array(
                'caption' => __(
                    'Border Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e0e0e0',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'popupContinueButtonHoverBorderColor' => array(
                'caption' => __(
                    'Border Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e0e0e0',
                'fieldsetKey' => 'continueButton',
                'eventClasses' => 'displayPopupContinueButton',
            ),
            'productListEmptyCartDivider' => array(
                'caption' => __('Empty Cart', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox',
                'hide' => true
            ),
            'productListEmptyText' => array(
                'caption' => __(
                    'Text for Empty Cart',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change Dropdown List Text for Empty Cart',
                    $this->_languageDomain
                ),
                'type' => 'input_text',
                'default' => 'There are no products',
                'fieldsetKey' => 'lightbox',
                'hide' => true
            ),
            'productListEmptyPaddingTop' => array(
                'caption' => __(
                    'Padding Top',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 5,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox',
                'hide' => true
            ),
            'productListEmptyPaddingBottom' => array(
                'caption' => __(
                    'Padding Bottom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 5,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox',
                'hide' => true
            ),
           'productListEmptyFontColor' => array(
                'caption' => __(
                    'Text Font Color for Empty Cart',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color for empty product list text',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#111111',
                'fieldsetKey' => 'lightbox',
                'hide' => true
            ),
            'productAmountDivider' => array(
                'caption' => __('Amount and Price', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'displayProductTotalPrice' => array(
                'caption' => __('Display', $this->_languageDomain),
                'lable' => __(
                    'Enable amount and price',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'productTotalPriceFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#1f1e1e',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductTotalPrice'
            ),
            'productListSubtotalDivider' => array(
                'caption' => __('Subtotal', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'displayProductListTotal' => array(
                'caption' => __('Display', $this->_languageDomain),
                'lable' => __(
                    'Enable Subtotal',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'productListTotalText' => array(
                'caption' => __(
                    'Title',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the text',
                    $this->_languageDomain
                ),
                'type' => 'input_text',
                'default' => __('Subtotal', $this->_languageDomain),
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListTotalTextAlign' => array(
                'caption' => __(
                    'Text Position',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select the location of text in subtotal container', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'left' => __('Left', $this->_languageDomain),
                    'center' => __('Center', $this->_languageDomain),
                    'right' => __('Right', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'right',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListTotalPriceBackground' => array(
                'caption' => __(
                    'Background Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#eeeeee',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListTotalPriceBorderWidth' => array(
                'caption' => __(
                    'Border Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 15,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListTotalPriceBorderColor' => array(
                'caption' => __(
                    'Border Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color for total price in product list',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e6e6e6',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListTotalPriceBorderRadius' => array(
                'caption' => __(
                    'Border Radius',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 7,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListTotalPriceFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#000000',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductListTotal'
            ),
            'productListButtonsDivider' => array(
                'caption' => __(
                    'View Cart & Checkout Buttons',
                    $this->_languageDomain
                ),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'productListButtonsFontWeight' => array(
                'caption' => __(
                    'Font Weight',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select font weight for Buttons', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'normal' => __('Normal', $this->_languageDomain),
                    'bold' => __('Bold', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'normal',
                'fieldsetKey' => 'lightbox',
            ),
            'productListButtonsQueue' => array(
                'caption' => __(
                    'Display the First in Queue',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select Display the button to display the first', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'viewCart' => __('View Cart', $this->_languageDomain),
                    'checkout' => __('Checkout', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'viewCart',
                'fieldsetKey' => 'lightbox',
            ),
            'displayViewCartButton' => array(
                'caption' => __('View Cart Button', $this->_languageDomain),
                'lable' => __(
                    'Enable View Cart button',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'viewCartButtonText' => array(
                'caption' => __(
                    'Title',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the button text',
                    $this->_languageDomain
                ),
                'type' => 'input_text',
                'default' => __('View Cart', $this->_languageDomain),
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton'
            ),
            'viewCartButtonWidthType' => array(
                'caption' => __(
                    'Width Type',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select width type', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'auto' => __('Auto', $this->_languageDomain),
                    'full' => __('Full Width', $this->_languageDomain),
                    'custom' => __('Custom', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'auto',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton'
            ),
            'viewCartButtonWidth' => array(
                'caption' => __('Custom Width', $this->_languageDomain),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 160,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 50,
                'max' => 1000,
                'eventClasses' => 'displayViewCartButton',
                'fieldsetKey' => 'lightbox'
            ),
            'viewCartButtonPaddingTop' => array(
                'caption' => __(
                    'Padding Top',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 5,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox'
            ),
            'viewCartButtonPaddingBottom' => array(
                'caption' => __(
                    'Padding Bottom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 5,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox'
            ),
            'viewCartButtonBackground' => array(
                'caption' => __(
                    'Background Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#eeeeee',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonHoverBackground' => array(
                'caption' => __(
                    'Background Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#6caff7',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#000000',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonHoverFontColor' => array(
                'caption' => __(
                    'Font Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#ffffff',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonBorderWidth' => array(
                'caption' => __(
                    'Border Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 1,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 15,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonBorderRadius' => array(
                'caption' => __(
                    'Border Radius',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 7,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonBorderColor' => array(
                'caption' => __(
                    'Border Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e0e0e0',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'viewCartButtonHoverBorderColor' => array(
                'caption' => __(
                    'Border Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e0e0e0',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayViewCartButton',
            ),
            'displayCheckoutButton' => array(
                'caption' => __('Checkout Button', $this->_languageDomain),
                'lable' => __(
                    'Enable Checkout button',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'checkoutButtonText' => array(
                'caption' => __(
                    'Title',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the button text',
                    $this->_languageDomain
                ),
                'type' => 'input_text',
                'default' => __('Checkout', $this->_languageDomain),
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton'
            ),
            'checkoutButtonWidthType' => array(
                'caption' => __(
                    'Width Type',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select width type', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'auto' => __('Auto', $this->_languageDomain),
                    'full' => __('Full Width', $this->_languageDomain),
                    'custom' => __('Custom', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'auto',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton'
            ),
            'checkoutButtonWidth' => array(
                'caption' => __('Custom Width', $this->_languageDomain),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 160,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 50,
                'max' => 1000,
                'eventClasses' => 'displayCheckoutButton',
                'fieldsetKey' => 'lightbox'
            ),
            'checkoutButtonPaddingTop' => array(
                'caption' => __(
                    'Padding Top',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 5,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox'
            ),
            'checkoutButtonPaddingBottom' => array(
                'caption' => __(
                    'Padding Bottom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 5,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 50,
                'fieldsetKey' => 'lightbox'
            ),
            'checkoutButtonBackground' => array(
                'caption' => __(
                    'Background Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#eeeeee',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonHoverBackground' => array(
                'caption' => __(
                    'Background Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the background color',
                    $this->_languageDomain
                ),
                'type' => 'color_picker',
                'default' => '#6caff7',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#000000',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonHoverFontColor' => array(
                'caption' => __(
                    'Font Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#ffffff',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonBorderWidth' => array(
                'caption' => __(
                    'Border Width',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 1,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 15,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonBorderRadius' => array(
                'caption' => __(
                    'Border Radius',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 7,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 100,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonBorderColor' => array(
                'caption' => __(
                    'Border Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e0e0e0',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'checkoutButtonHoverBorderColor' => array(
                'caption' => __(
                    'Border Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e0e0e0',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayCheckoutButton',
            ),
            'productListDeleteButtonDivider' => array(
                'caption' => __(
                    'Delete Product Button',
                    $this->_languageDomain
                ),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'displayDeleteButton' => array(
                'caption' => __('Display', $this->_languageDomain),
                'lable' => __(
                    'Enable Delete Product Button',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'deleteButtonPosition' => array(
                'caption' => __(
                    'Position',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select the location of button in dropdown list', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'left' => __('Left', $this->_languageDomain),
                    'right' => __('Right', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'left',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayDeleteButton'
            ),
            'deleteButtonVerticalAlignment' => array(
                'caption' => __(
                    'Vertical Alignment',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Select the vertical alignment of button in dropdown list', 
                    $this->_languageDomain
                ),
                'values' => array(
                    'top' => __('Top', $this->_languageDomain),
                    'middle' => __('Middle', $this->_languageDomain),
                    'bottom' => __('Bottom', $this->_languageDomain),
                ),
                'type' => 'select',
                'default' => 'top',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayDeleteButton'
            ),
            'deleteButtonSize' => array(
                'caption' => __(
                    'Size',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 18,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 5,
                'max' => 50,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayDeleteButton'
            ),
            'deleteButtonFontColor' => array(
                'caption' => __(
                    'Font Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#000000',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayDeleteButton'
            ),
            'deleteButtonHoverFontColor' => array(
                'caption' => __(
                    'Font Color on Hover',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#807878',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayDeleteButton'
            ),
            'productListDelimiterDivider' => array(
                'caption' => __('Divider for products', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'delimiterPositionsWidth' => array(
                'caption' => __(
                    'Height',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 1,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 15,
                'fieldsetKey' => 'lightbox'
            ),
            'delimiterPositionsColor' => array(
                'caption' => __(
                    'Color',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Change the Color',
                    $this->_languageDomain
                ),
                'type'    => 'color_picker',
                'default' => '#e8e4e3',
                'fieldsetKey' => 'lightbox'
            ),
            'productsPicturesDivider' => array(
                'caption' => __('Products Thumbnails', $this->_languageDomain),
                'type'    => 'divider',
                'fieldsetKey' => 'lightbox'
            ),
            'displayProductsPictures' => array(
                'caption' => __('Display', $this->_languageDomain),
                'lable' => __(
                    'Enable product picture',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'default' => 1,
                'event' => 'visible',
                'fieldsetKey' => 'lightbox',
                'backlight' => 'light'
            ),
            'productDefaultThumbnail' => array(
                'caption' => __('Use Default Thumbnails', $this->_languageDomain),
                'lable' => __(
                    'Enable option',
                    $this->_languageDomain
                ),
                'hint' => __(
                    'Will use default  WooCommerce Product Thumbnails',
                    $this->_languageDomain
                ),
                'type' => 'input_checkbox',
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductsPictures'
            ),
            'productImageMaxWidth' => array(
                'caption' => __(
                    'Max Width for Custom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 40,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 500,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductsPictures'
            ),
            'productImageMaxHeight' => array(
                'caption' => __(
                    'Max Height for Custom',
                    $this->_languageDomain
                ),
                'lable' => 'px',
                'type' => 'slider',
                'default' => 0,
                'class' => 'festi-cart-change-slider',
                'event' => 'change-slider',
                'min' => 0,
                'max' => 500,
                'fieldsetKey' => 'lightbox',
                'eventClasses' => 'displayProductsPictures'
            ),
        );
        
        $values = $this->getOptions('settings');
        if ($values) {
            foreach ($settings as $ident => &$item) {
                if (array_key_exists($ident, $values)) {
                    $item['value'] = $values[$ident];
                }
            }
            unset($item);
        }

        return $settings;
    } // end loadSettings
    
    public function getSelectorClassForDisplayEvent($class)
    {
        $selector = $class.'-visible';
        
        $options = $this->getOptions('settings');
                
        if (!isset($options[$class]) || $options[$class] == 'disable') {
            $selector.=  ' festi-cart-hidden ';
        }
        
        return $selector;
    } // end getSelectorClassForDisplayEvent
       
    protected function hasOptionPageInRequest()
    {
        return array_key_exists('tab', $_GET)
               && array_key_exists($_GET['tab'], $this->_menuOptions);
    } // end hasOptionPageInRequest
    
    public function fetchOptionPageHelp()
    {
        echo $this->fetch('help_page.phtml');
    } // end fetchOptionPageManual
}