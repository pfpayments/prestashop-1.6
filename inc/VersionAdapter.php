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

class PostFinanceCheckout_VersionAdapter
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
}
