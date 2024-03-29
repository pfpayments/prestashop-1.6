/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2024 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
jQuery(function ($) {
    
    function movePostFinanceCheckoutManualTasks()
    {
        $("#postfinancecheckout_notifications").find("li").each(function (key, element) {
            $("#header_notifs_icon_wrapper").append(element);
        });
    }
    movePostFinanceCheckoutManualTasks();
    
});