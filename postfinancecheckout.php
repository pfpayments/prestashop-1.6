<?php
/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2019 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

if (! defined('_PS_VERSION_')) {
    exit();
}

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'postfinancecheckout_autoloader.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'postfinancecheckout-sdk' . DIRECTORY_SEPARATOR . 'autoload.php');

class PostFinanceCheckout extends PostFinanceCheckout_AbstractModule
{

    const CK_SHOW_CART = 'PFC_SHOW_CART';

    const CK_SHOW_TOS = 'PFC_SHOW_TOS';

    const CK_REMOVE_TOS = 'PFC_REMOVE_TOS';

    const CK_CRONJOB_TIMESTAMP = 'PFC_CRONJOB_TIMESTAMP';

    const CK_CRONJOB_RUNNING = 'PFC_CRONJOB_RUNNING';

    const CRON_MIN_INTERVAL_SEC = 300;
    
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
        $this->version = '1.0.17';
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => '1.6.1.21');
        parent::__construct();
    }

    protected function installHooks()
    {
        return parent::installHooks() &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('displayMobileHeader') &&
            $this->registerHook('displayPaymentEU') &&
            $this->registerHook('displayTop') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('postFinanceCheckoutCron');
    }

    protected function getBackendControllers()
    {
        return array(
            'AdminPostFinanceCheckoutMethodSettings' => array(
                'parentId' => Tab::getIdFromClassName('AdminParentModules'),
                'name' => 'PostFinance Checkout '.$this->l('Payment Methods')
            ),
            'AdminPostFinanceCheckoutDocuments' => array(
                'parentId' => - 1, // No Tab in navigation
                'name' => 'PostFinance Checkout '.$this->l('Documents')
            ),
            'AdminPostFinanceCheckoutOrder' => array(
                'parentId' => - 1, // No Tab in navigation
                'name' => 'PostFinance Checkout '.$this->l('Order Management')
            ),
            'AdminPostFinanceCheckoutCronJobs' => array(
                'parentId' => Tab::getIdFromClassName('AdminTools'),
                'name' => 'PostFinance Checkout '.$this->l('CronJobs')
            )
        );
    }

    protected function installConfigurationValues()
    {
        return parent::installConfigurationValues() &&
             Configuration::updateValue(self::CK_SHOW_CART, true) &&
             Configuration::updateValue(self::CK_SHOW_TOS, false) &&
             Configuration::updateValue(self::CK_REMOVE_TOS, false);
    }

    protected function uninstallConfigurationValues()
    {
         return parent::uninstallConfigurationValues() &&
             Configuration::deleteByName(self::CK_SHOW_CART) &&
             Configuration::deleteByName(self::CK_SHOW_TOS) &&
             Configuration::deleteByName(self::CK_REMOVE_TOS);
    }

    public function getContent()
    {
        $output = $this->getMailHookActiveWarning();
        $output .= $this->handleSaveAll();
        $output .= $this->handleSaveApplication();
        $output .= $this->handleSaveCheckout();
        $output .= $this->handleSaveEmail();
        $output .= $this->handleSaveFeeItem();
        $output .= $this->handleSaveDownload();
        $output .= $this->handleSaveSpaceViewId();
        $output .= $this->handleSaveOrderStatus();
        $output .= $this->displayHelpButtons();
        return $output . $this->displayForm();
    }
    
    protected function handleSaveCheckout()
    {
        $output = "";
        if (Tools::isSubmit('submit' . $this->name . '_checkout')) {
            if (!$this->context->shop->isFeatureActive() || $this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
                Configuration::updateValue(
                    self::CK_SHOW_CART,
                    Tools::getValue(self::CK_SHOW_CART)
                );
                Configuration::updateValue(
                    self::CK_SHOW_TOS,
                    Tools::getValue(self::CK_SHOW_TOS)
                );
                Configuration::updateValue(
                    self::CK_REMOVE_TOS,
                    Tools::getValue(self::CK_REMOVE_TOS)
                );
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            } else {
                $output .= $this->displayError(
                    $this->l('You can not store the configuration for all Shops or a Shop Group.')
                );
            }
        }
        return $output;
    }
    
    protected function getConfigurationForms()
    {
        return array(
            $this->getCheckoutForm(),
            $this->getEmailForm(),
            $this->getFeeForm(),
            $this->getDocumentForm(),
            $this->getSpaceViewIdForm(),
            $this->getOrderStatusForm()
        );
    }
    
    protected function getConfigurationValues()
    {
        return array_merge(
            $this->getApplicationConfigValues(),
            $this->getCheckoutConfigValues(),
            $this->getEmailConfigValues(),
            $this->getFeeItemConfigValues(),
            $this->getDownloadConfigValues(),
            $this->getSpaceViewIdConfigValues(),
            $this->getOrderStatusConfigValues()
        );
    }
    
    protected function getConfigurationKeys()
    {
        $base = parent::getConfigurationKeys();
        $base[] = self::CK_SHOW_CART;
        $base[] = self::CK_SHOW_TOS;
        $base[] = self::CK_REMOVE_TOS;
        return $base;
    }
    
    protected function getCheckoutForm()
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
                'desc' => $this->l('Should the Terms of Service be shown and checked on the payment details input page.'),
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
                'desc' => $this->l('Should the default Terms of Service be removed during the checkout. CAUTION: This option will remove the ToS for all payment methods.'),
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
                    'title' =>$this->l('Save All'),
                    'class' => 'pull-right',
                    'type' => 'input',
                    'icon' => 'process-icon-save',
                    'name' => 'submit' . $this->name . '_all'
                ),
                array(
                    'title' =>$this->l('Save'),
                    'class' => 'pull-right',
                    'type' => 'input',
                    'icon' => 'process-icon-save',
                    'name' => 'submit' . $this->name . '_checkout'
                )
            )
        );
    }
    
    protected function getCheckoutConfigValues()
    {
        $values = array();
        if (!$this->context->shop->isFeatureActive() || $this->context->shop->getContext() == Shop::CONTEXT_SHOP) {
                $values[self::CK_SHOW_CART] = (bool) Configuration::get(
                    self::CK_SHOW_CART
                );
                $values[self::CK_SHOW_TOS] = (bool) Configuration::get(
                    self::CK_SHOW_TOS
                );
                $values[self::CK_REMOVE_TOS] = (bool) Configuration::get(
                    self::CK_REMOVE_TOS
                );
        }
        return $values;
    }

    public function hookPostFinanceCheckoutCron($params)
    {
        $tasks = array();
        $tasks[] = 'PostFinanceCheckout_Cron::cleanUpCronDB';
        $voidService = PostFinanceCheckout_Service_TransactionVoid::instance();
        if ($voidService->hasPendingVoids()) {
            $tasks[] = array(
                $voidService,
                "updateVoids"
            );
        }
        $completionService = PostFinanceCheckout_Service_TransactionCompletion::instance();
        if ($completionService->hasPendingCompletions()) {
            $tasks[] = array(
                $completionService,
                "updateCompletions"
            );
        }
        $refundService = PostFinanceCheckout_Service_Refund::instance();
        if ($refundService->hasPendingRefunds()) {
            $tasks[] = array(
                $refundService,
                "updateRefunds"
            );
        }
        return $tasks;
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
        return $this->getCronJobItem();
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
            $possiblePaymentMethods = PostFinanceCheckout_Service_Transaction::instance()->getPossiblePaymentMethods(
                $cart
            );
        } catch (Exception $e) {
            return;
        }
        $shopId = $cart->id_shop;
        $configurations = array();
        $language = Context::getContext()->language->language_code;
        $methods = array();
        foreach ($possiblePaymentMethods as $possible) {
            $methodConfiguration = PostFinanceCheckout_Model_MethodConfiguration::loadByConfigurationAndShop(
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
        foreach (PostFinanceCheckout_Helper::sortMethodConfiguration($methods) as $methodConfiguration) {
            $parameters = $this->getParametersFromMethodConfiguration(
                $methodConfiguration,
                $cart,
                $shopId,
                $language
            );
            $this->smarty->assign($parameters);
            
            $result[] = array(
                'cta_text' => $this->display(__DIR__, 'hook/payment_eu_text.tpl'),
                'logo' => $parameters['image'],
                'form' => $this->display(__DIR__, 'hook/payment_eu_form.tpl')
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
                'total' => Tools::displayPrice(
                    $params['total_to_pay'],
                    $params['currencyObj'],
                    false
                )
            )
        );
        return $this->display(__DIR__, 'hook/payment_return.tpl');
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
            $possiblePaymentMethods = PostFinanceCheckout_Service_Transaction::instance()->getPossiblePaymentMethods(
                $cart
            );
        } catch (Exception $e) {
            return;
        }
        $shopId = $cart->id_shop;
        $configurations = array();
        $language = Context::getContext()->language->language_code;
        $methods = array();
        foreach ($possiblePaymentMethods as $possible) {
            $methodConfiguration = PostFinanceCheckout_Model_MethodConfiguration::loadByConfigurationAndShop(
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
        foreach (PostFinanceCheckout_Helper::sortMethodConfiguration($methods) as $methodConfiguration) {
            $templateVars = $this->getParametersFromMethodConfiguration(
                $methodConfiguration,
                $cart,
                $shopId,
                $language
            );
            $this->smarty->assign($templateVars);
            $result .= $this->display(__DIR__, 'hook/payment.tpl');
        }
        return $result;
    }

    private function getDeviceIdentifierScript()
    {
        $uniqueId = $this->context->cookie->pfc_device_id;
        if ($uniqueId == false) {
            $uniqueId = PostFinanceCheckout_Helper::generateUUID();
            $this->context->cookie->pfc_device_id = $uniqueId;
        }
        $scriptUrl = PostFinanceCheckout_Helper::getBaseGatewayUrl() . '/s/' .
             Configuration::get(self::CK_SPACE_ID) . '/payment/device.js?sessionIdentifier=' .
             $uniqueId;
        return '<script src="' . $scriptUrl . '" async="async"></script>';
    }

    private function getCronJobItem()
    {
        PostFinanceCheckout_Cron::cleanUpHangingCrons();
        PostFinanceCheckout_Cron::insertNewPendingCron();
        
        $currentToken = PostFinanceCheckout_Cron::getCurrentSecurityTokenForPendingCron();
        if ($currentToken) {
            $url = $this->context->link->getModuleLink(
                'postfinancecheckout',
                'cron',
                array(
                    'security_token' => $currentToken
                ),
                true
            );
            return '<img src="' . $url . '" style="display:none" />';
        }
    }

    public function hookActionFrontControllerSetMedia($arr)
    {
        if ($this->context->controller instanceof ParentOrderControllerCore) {
            $this->context->controller->addCSS(
                __PS_BASE_URI__ . 'modules/' . $this->name . '/views/css/frontend/checkout.css'
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
        $result = parent::hookDisplayAdminAfterHeader();
        $result .= $this->getCronJobItem();
        return $result;
    }
    
    protected function hasBackendControllerDeleteAccess(AdminController $backendController)
    {
        return $backendController->tabAccess['delete'] === '1';
    }
    
    protected function hasBackendControllerEditAccess(AdminController $backendController)
    {
        return $backendController->tabAccess['edit'] === '1';
    }
}
