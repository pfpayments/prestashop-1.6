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

class PostFinanceCheckoutFrontpaymentcontroller extends ModuleFrontController
{

    /**
     * Checks if the module is still active and various checkout specfic values.
     * Returns a redirect URL where the customer has to be redirected, if there is an issue.
     *
     * @param Cart $cart
     * @return string|NULL
     */
    protected function checkAvailablility(Cart $cart)
    {
        if ($cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || ! $this->module->active || ! Validate::isLoadedObject(new Customer($cart->id_customer))) {
            $this->context->cookie->pfc_error = $this->module->l('Your session expired, please try again.', 'frontpaymentcontroller');
            return $this->context->link->getPageLink('order', true, null, "step=1");
        }
        // Check that this payment option is still available in case the customer changed his address just before the
        // end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'postfinancecheckout') {
                $authorized = true;
                break;
            }
        }
        if (! $authorized) {
            $this->context->cookie->pfc_error = $this->module->l('This payment method is no longer available, please try another one.', 'frontpaymentcontroller');
            return $this->context->link->getPageLink('order', true, null, "step=3");
        }
        if (! $this->module instanceof PostFinanceCheckout) {
            $this->context->cookie->pfc_error = $this->module->l('There was a technical issue, please try again.', 'frontpaymentcontroller');
            return $this->context->link->getPageLink('order', true, null, "step=3");
        }
        return null;
    }
}
