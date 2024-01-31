<?php
/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2024 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

class PostFinanceCheckoutVersionadapter
{
    public static function getConfigurationInterface()
    {
        return Adapter_ServiceLocator::get('Core_Business_ConfigurationInterface');
    }

    public static function getAddressFactory()
    {
        return Adapter_ServiceLocator::get('Adapter_AddressFactory');
    }

    public static function clearCartRuleStaticCache()
    {
    }

    public static function getAdminOrderTemplate()
    {
        return 'views/templates/admin/hook/admin_order.tpl';
    }

    /**
     * Returns true if the refund is only voucher, not required to be sent to PostFinanceCheckout.
     *
     * @param [] $postData
     * @return boolean
     */
    public static function isVoucherOnlyPostFinanceCheckout($postData)
    {
        return isset($postData['generateDiscountRefund']) && ! isset($postData['postfinancecheckout_offline']);
    }
}
