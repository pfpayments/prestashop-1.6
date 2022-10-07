<?php
/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if (! defined('_PS_VERSION_')) {
    exit();
}

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'postfinancecheckout_autoloader.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'postfinancecheckout-sdk' . DIRECTORY_SEPARATOR .
    'autoload.php');
class PostFinanceCheckout extends PaymentModule
{
    const CK_SHOW_CART = 'PFC_SHOW_CART';

    const CK_SHOW_TOS = 'PFC_SHOW_TOS';

    const CK_REMOVE_TOS = 'PFC_REMOVE_TOS';

    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->name = 'postfinancecheckout';
        $this->tab = 'payments_gateways';
        $this->author = 'Customweb GmbH';
        $this->bootstrap = true;
        $this->need_instance = 0;
        $this->version = '1.2.32';
        $this->displayName = 'PostFinance Checkout';
        $this->description = $this->l('This PrestaShop module enables to process payments with %s.');
        $this->description = sprintf($this->description, 'PostFinance Checkout');
        $this->ps_versions_compliancy = array(
            'min' => '1.6.1',
            'max' => '1.6.1.24'
        );
        $this->module_key = '';
        parent::__construct();
        $this->confirmUninstall = sprintf(
            $this->l('Are you sure you want to uninstall the %s module?', 'abstractmodule'),
            'PostFinance Checkout'
        );
        
        // Remove Fee Item
        if (isset($this->context->cart) && Validate::isLoadedObject($this->context->cart)) {
            PostFinanceCheckoutFeehelper::removeFeeSurchargeProductsFromCart($this->context->cart);
        }
        if (! empty($this->context->cookie->pfc_error)) {
            $errors = $this->context->cookie->pfc_error;
            if (is_string($errors)) {
                $this->context->controller->errors[] = $errors;
            } elseif (is_array($errors)) {
                foreach ($errors as $error) {
                    $this->context->controller->errors[] = $error;
                }
            }
            unset($_SERVER['HTTP_REFERER']); // To disable the back button in the error message
            $this->context->cookie->pfc_error = null;
        }
    }
    
    public function addError($error)
    {
        $this->_errors[] = $error;
    }
    
    public function getContext()
    {
        return $this->context;
    }
    
    public function getTable()
    {
        return $this->table;
    }
    
    public function getIdentifier()
    {
        return $this->identifier;
    }
    
    public function install()
    {
        if (! PostFinanceCheckoutBasemodule::checkRequirements($this)) {
            return false;
        }
        if (! parent::install()) {
            return false;
        }
        return PostFinanceCheckoutBasemodule::install($this);
    }
    
    public function uninstall()
    {
        return parent::uninstall() && PostFinanceCheckoutBasemodule::uninstall($this);
    }
    

    public function installHooks()
    {
        return PostFinanceCheckoutBasemodule::installHooks($this) && $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayHeader') && $this->registerHook('displayMobileHeader') &&
            $this->registerHook('displayPaymentEU') && $this->registerHook('displayTop') &&
            $this->registerHook('payment') && $this->registerHook('paymentReturn') &&
            $this->registerHook('postFinanceCheckoutCron');
    }

    public function getBackendControllers()
    {
        return array(
            'AdminPostFinanceCheckoutMethodSettings' => array(
                'parentId' => Tab::getIdFromClassName('AdminParentModules'),
                'name' => 'PostFinance Checkout ' . $this->l('Payment Methods')
            ),
            'AdminPostFinanceCheckoutDocuments' => array(
                'parentId' => - 1, // No Tab in navigation
                'name' => 'PostFinance Checkout ' . $this->l('Documents')
            ),
            'AdminPostFinanceCheckoutOrder' => array(
                'parentId' => - 1, // No Tab in navigation
                'name' => 'PostFinance Checkout ' . $this->l('Order Management')
            ),
            'AdminPostFinanceCheckoutCronJobs' => array(
                'parentId' => Tab::getIdFromClassName('AdminTools'),
                'name' => 'PostFinance Checkout ' . $this->l('CronJobs')
            )
        );
    }

    public function installConfigurationValues()
    {
        return Configuration::updateValue(self::CK_SHOW_CART, true) &&
            Configuration::updateValue(self::CK_SHOW_TOS, false) &&
            Configuration::updateValue(self::CK_REMOVE_TOS, false) &&
            PostFinanceCheckoutBasemodule::installConfigurationValues();
    }

    public function uninstallConfigurationValues()
    {
        return Configuration::deleteByName(self::CK_SHOW_CART) &&
            Configuration::deleteByName(self::CK_SHOW_TOS) && Configuration::deleteByName(self::CK_REMOVE_TOS) &&
            PostFinanceCheckoutBasemodule::uninstallConfigurationValues();
    }

    public function getContent()
    {
        $output = PostFinanceCheckoutBasemodule::getMailHookActiveWarning($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveAll($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveApplication($this);
        $output .= $this->handleSaveCheckout();
        $output .= PostFinanceCheckoutBasemodule::handleSaveEmail($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveFeeItem($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveDownload($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveSpaceViewId($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveOrderStatus($this);
        $output .= PostFinanceCheckoutBasemodule::handleSaveCronSettings($this);
        $output .= PostFinanceCheckoutBasemodule::displayHelpButtons($this);
        return $output . PostFinanceCheckoutBasemodule::displayForm($this);
    }

    private function handleSaveCheckout()
    {
        $output = "";
        if (Tools::isSubmit('submit' . $this->name . '_checkout')) {
            if (! $this->context->shop->isFeatureActive() || $this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
                Configuration::updateValue(self::CK_SHOW_CART, Tools::getValue(self::CK_SHOW_CART));
                Configuration::updateValue(self::CK_SHOW_TOS, Tools::getValue(self::CK_SHOW_TOS));
                Configuration::updateValue(self::CK_REMOVE_TOS, Tools::getValue(self::CK_REMOVE_TOS));
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError(
                    $this->l('You can not store the configuration for all Shops or a Shop Group.')
                );
            }
        }
        return $output;
    }

    public function getConfigurationForms()
    {
        return array(
            $this->getCheckoutForm(),
            PostFinanceCheckoutBasemodule::getEmailForm($this),
            PostFinanceCheckoutBasemodule::getFeeForm($this),
            PostFinanceCheckoutBasemodule::getDocumentForm($this),
            PostFinanceCheckoutBasemodule::getSpaceViewIdForm($this),
            PostFinanceCheckoutBasemodule::getOrderStatusForm($this),
            PostFinanceCheckoutBasemodule::getCronSettingsForm($this),
        );
    }

    public function getConfigurationValues()
    {
        return array_merge(
            PostFinanceCheckoutBasemodule::getApplicationConfigValues($this),
            $this->getCheckoutConfigValues(),
            PostFinanceCheckoutBasemodule::getEmailConfigValues($this),
            PostFinanceCheckoutBasemodule::getFeeItemConfigValues($this),
            PostFinanceCheckoutBasemodule::getDownloadConfigValues($this),
            PostFinanceCheckoutBasemodule::getSpaceViewIdConfigValues($this),
            PostFinanceCheckoutBasemodule::getOrderStatusConfigValues($this),
            PostFinanceCheckoutBasemodule::getCronSettingsConfigValues($this)
        );
    }

    public function getConfigurationKeys()
    {
        $base = PostFinanceCheckoutBasemodule::getConfigurationKeys();
        $base[] = self::CK_SHOW_CART;
        $base[] = self::CK_SHOW_TOS;
        $base[] = self::CK_REMOVE_TOS;
        return $base;
    }

    private function getCheckoutForm()
    {
        $checkoutConfig = array(
            array(
                'type' => 'switch',
                'label' => $this->l('Show Cart Summary'),
                'name' => self::CK_SHOW_CART,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Show')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Hide')
                    )
                ),
                'desc' => $this->l('Should a cart summary be shown on the payment details input page.'),
                'lang' => false
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Show Terms of Service'),
                'name' => self::CK_SHOW_TOS,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Show')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Hide')
                    )
                ),
                'desc' => $this->l(
                    'Should the Terms of Service be shown and checked on the payment details input page.'
                ),
                'lang' => false
            ),
            array(
                'type' => 'switch',
                'label' => $this->l('Remove default Terms of Service'),
                'name' => self::CK_REMOVE_TOS,
                'is_bool' => true,
                'values' => array(
                    array(
                        'id' => 'active_on',
                        'value' => 1,
                        'label' => $this->l('Keep')
                    ),
                    array(
                        'id' => 'active_off',
                        'value' => 0,
                        'label' => $this->l('Remove')
                    )
                ),
                'desc' => $this->l(
                    'Should the default Terms of Service be removed during the checkout. CAUTION: This option will remove the ToS for all payment methods.'
                ),
                'lang' => false
            )
        );

        return array(
            'legend' => array(
                'title' => $this->l('Checkout Settings')
            ),
            'input' => $checkoutConfig,
            'buttons' => array(
                array(
                    'title' => $this->l('Save All'),
                    'class' => 'pull-right',
                    'type' => 'input',
                    'icon' => 'process-icon-save',
                    'name' => 'submit' . $this->name . '_all'
                ),
                array(
                    'title' => $this->l('Save'),
                    'class' => 'pull-right',
                    'type' => 'input',
                    'icon' => 'process-icon-save',
                    'name' => 'submit' . $this->name . '_checkout'
                )
            )
        );
    }

    private function getCheckoutConfigValues()
    {
        $values = array();
        if (! $this->context->shop->isFeatureActive() || $this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
            $values[self::CK_SHOW_CART] = (bool) Configuration::get(self::CK_SHOW_CART);
            $values[self::CK_SHOW_TOS] = (bool) Configuration::get(self::CK_SHOW_TOS);
            $values[self::CK_REMOVE_TOS] = (bool) Configuration::get(self::CK_REMOVE_TOS);
        }
        return $values;
    }

    public function hookPostFinanceCheckoutCron($params)
    {
        return PostFinanceCheckoutBasemodule::hookPostFinanceCheckoutCron($params);
    }

    public function hookDisplayHeader($params)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            return $this->getDeviceIdentifierScript();
        }
    }

    public function hookDisplayMobileHeader($params)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            return $this->getDeviceIdentifierScript();
        }
    }

    public function hookDisplayTop($params)
    {
        return  PostFinanceCheckoutBasemodule::hookDisplayTop($this, $params);
    }

    /**
     * hookPayment replacement for compatibility with module eu_legal
     *
     * @param array $params
     * @return string Generated html
     */
    public function hookDisplayPaymentEU($params)
    {
        if (! $this->active) {
            return;
        }
        if (! isset($params['cart']) || ! ($params['cart'] instanceof Cart)) {
            return;
        }
        $cart = $params['cart'];
        try {
            $possiblePaymentMethods = PostFinanceCheckoutServiceTransaction::instance()->getPossiblePaymentMethods(
                $cart
            );
        } catch (PostFinanceCheckoutExceptionInvalidtransactionamount $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 2, null, 'PostFinanceCheckout');
            return array(
                array(
                    'cta_text' => $this->display(dirname(__FILE__), 'hook/amount_error_eu.tpl'),
                    'form' => ""
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 1, null, 'PostFinanceCheckout');
            return;
        }
        $shopId = $cart->id_shop;
        $language = Context::getContext()->language->language_code;
        $methods = array();
        foreach ($possiblePaymentMethods as $possible) {
            $methodConfiguration = PostFinanceCheckoutModelMethodconfiguration::loadByConfigurationAndShop(
                $possible->getSpaceId(),
                $possible->getId(),
                $shopId
            );
            if (! $methodConfiguration->isActive()) {
                continue;
            }
            $methods[] = $methodConfiguration;
        }
        $result = array();
        
        $this->context->smarty->registerPlugin(
            'function',
            'postfinancecheckout_clean_html',
            array(
                'PostFinanceCheckoutSmartyfunctions',
                'cleanHtml'
            )
        );
        
        foreach (PostFinanceCheckoutHelper::sortMethodConfiguration($methods) as $methodConfiguration) {
            $parameters = PostFinanceCheckoutBasemodule::getParametersFromMethodConfiguration($this, $methodConfiguration, $cart, $shopId, $language);
            $this->smarty->assign($parameters);

            $result[] = array(
                'cta_text' => $this->display(dirname(__FILE__), 'hook/payment_eu_text.tpl'),
                'logo' => $parameters['image'],
                'form' => $this->display(dirname(__FILE__), 'hook/payment_eu_form.tpl')
            );
        }
        return $result;
    }

    public function hookDisplayPaymentReturn($params)
    {
        if ($this->active == false) {
            return false;
        }
        $order = $params['objOrder'];
        if ($order->module != $this->name) {
            return false;
        }
        $this->smarty->assign(
            array(
                'reference' => $order->reference,
                'params' => $params,
                'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false)
            )
        );
        return $this->display(dirname(__FILE__), 'hook/payment_return.tpl');
    }

    public function hookPayment($params)
    {
        if (! $this->active) {
            return;
        }
        if (! isset($params['cart']) || ! ($params['cart'] instanceof Cart)) {
            return;
        }
        $cart = $params['cart'];
        try {
            $possiblePaymentMethods = PostFinanceCheckoutServiceTransaction::instance()->getPossiblePaymentMethods(
                $cart
            );
        } catch (PostFinanceCheckoutExceptionInvalidtransactionamount $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 2, null, 'PostFinanceCheckout');
            return $this->display(dirname(__FILE__), 'hook/amount_error.tpl');
        } catch (Exception $e) {
            PrestaShopLogger::addLog($e->getMessage() . " CartId: " . $cart->id, 1, null, 'PostFinanceCheckout');
            return;
        }
        $shopId = $cart->id_shop;
        $language = Context::getContext()->language->language_code;
        $methods = array();
        foreach ($possiblePaymentMethods as $possible) {
            $methodConfiguration = PostFinanceCheckoutModelMethodconfiguration::loadByConfigurationAndShop(
                $possible->getSpaceId(),
                $possible->getId(),
                $shopId
            );
            if (! $methodConfiguration->isActive()) {
                continue;
            }
            $methods[] = $methodConfiguration;
        }
        $result = "";
        $this->context->smarty->registerPlugin(
            'function',
            'postfinancecheckout_clean_html',
            array(
                'PostFinanceCheckoutSmartyfunctions',
                'cleanHtml'
            )
        );
        foreach (PostFinanceCheckoutHelper::sortMethodConfiguration($methods) as $methodConfiguration) {
            $templateVars = PostFinanceCheckoutBasemodule::getParametersFromMethodConfiguration($this, $methodConfiguration, $cart, $shopId, $language);
            $this->smarty->assign($templateVars);
            $result .= $this->display(dirname(__FILE__), 'hook/payment.tpl');
        }
        return $result;
    }

    private function getDeviceIdentifierScript()
    {
        $uniqueId = $this->context->cookie->pfc_device_id;
        if ($uniqueId == false) {
            $uniqueId = PostFinanceCheckoutHelper::generateUUID();
            $this->context->cookie->pfc_device_id = $uniqueId;
        }
        $scriptUrl = PostFinanceCheckoutHelper::getBaseGatewayUrl() . '/s/' . Configuration::get(PostFinanceCheckoutBasemodule::CK_SPACE_ID) .
            '/payment/device.js?sessionIdentifier=' . $uniqueId;
        return '<script src="' . $scriptUrl . '" async="async"></script>';
    }

    
    public function hookActionFrontControllerSetMedia($arr)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            $this->context->controller->addCSS(
                __PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/frontend/checkout.css'
            );
            $this->context->controller->addJS(
                __PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/frontend/selection.js'
            );
            $cart = $this->context->cart;
            if (Configuration::get(self::CK_REMOVE_TOS, null, null, $cart->id_shop)) {
                $this->context->cookie->checkedTOS = 1;
                $this->context->controller->addJS(
                    __PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/frontend/tos-handling.js'
                );
            }
        }
    }

    /**
     * Show the manual task in the admin bar.
     * The output is moved with javascript to the correct place as better hook is missing.
     *
     * @return string
     */
    public function hookDisplayAdminAfterHeader()
    {
        $result = PostFinanceCheckoutBasemodule::hookDisplayAdminAfterHeader($this);
        $result .= PostFinanceCheckoutBasemodule::getCronJobItem($this);
        return $result;
    }

    public function hasBackendControllerDeleteAccess(AdminController $backendController)
    {
        return $backendController->tabAccess['delete'] === '1';
    }

    public function hasBackendControllerEditAccess(AdminController $backendController)
    {
        return $backendController->tabAccess['edit'] === '1';
    }
    
       
    public function hookPostFinanceCheckoutSettingsChanged($params)
    {
        return PostFinanceCheckoutBasemodule::hookPostFinanceCheckoutSettingsChanged($this, $params);
    }
    
    public function hookActionMailSend($data)
    {
        return PostFinanceCheckoutBasemodule::hookActionMailSend($this, $data);
    }
    
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        PostFinanceCheckoutBasemodule::validateOrder($this, $id_cart, $id_order_state, $amount_paid, $payment_method, $message, $extra_vars, $currency_special, $dont_touch_amount, $secure_key, $shop);
    }
    
    public function validateOrderParent(
        $id_cart,
        $id_order_state,
        $amount_paid,
        $payment_method = 'Unknown',
        $message = null,
        $extra_vars = array(),
        $currency_special = null,
        $dont_touch_amount = false,
        $secure_key = false,
        Shop $shop = null
    ) {
        parent::validateOrder($id_cart, $id_order_state, $amount_paid, $payment_method, $message, $extra_vars, $currency_special, $dont_touch_amount, $secure_key, $shop);
    }
    
    public function hookDisplayOrderDetail($params)
    {
        return PostFinanceCheckoutBasemodule::hookDisplayOrderDetail($this, $params);
    }
    
    public function hookActionAdminControllerSetMedia($arr)
    {
        PostFinanceCheckoutBasemodule::hookActionAdminControllerSetMedia($this, $arr);
    }
    
    public function hookDisplayBackOfficeHeader($params)
    {
        PostFinanceCheckoutBasemodule::hookDisplayBackOfficeHeader($this, $params);
    }

    public function hookDisplayAdminOrderLeft($params)
    {
        return PostFinanceCheckoutBasemodule::hookDisplayAdminOrderLeft($this, $params);
    }

    public function hookDisplayAdminOrderTabOrder($params)
    {
        return PostFinanceCheckoutBasemodule::hookDisplayAdminOrderTabOrder($this, $params);
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        return PostFinanceCheckoutBasemodule::hookDisplayAdminOrderContentOrder($this, $params);
    }
    
    public function hookDisplayAdminOrder($params)
    {
        return PostFinanceCheckoutBasemodule::hookDisplayAdminOrder($this, $params);
    }
    
    public function hookActionAdminOrdersControllerBefore($params)
    {
        return PostFinanceCheckoutBasemodule::hookActionAdminOrdersControllerBefore($this, $params);
    }
    
    public function hookActionObjectOrderPaymentAddBefore($params)
    {
        PostFinanceCheckoutBasemodule::hookActionObjectOrderPaymentAddBefore($this, $params);
    }
    
    public function hookActionOrderEdited($params)
    {
        PostFinanceCheckoutBasemodule::hookActionOrderEdited($this, $params);
    }
}
