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
    
    var targetNodes         = $("#HOOK_PAYMENT");
    var MutationObserver    = window.MutationObserver || window.WebKitMutationObserver;
    var myObserver          = new MutationObserver(mutationHandler);
    var obsConfig           = { childList: true };

    //--- Add a target node to the observer. Can only add one node at a time.
    targetNodes.each(function () {
        myObserver.observe(this, obsConfig);
    });

    function mutationHandler(mutationRecords)
    {
        console.info("mutationHandler:");

        mutationRecords.forEach(function (mutation) {
            $('#HOOK_PAYMENT div.postfinancecheckout-method a:not(.postfinancecheckout)').off('click.postfinancecheckout').on('click.postfinancecheckout', function (event) {
                event.stopPropagation()});
        });
    }
    $('#HOOK_PAYMENT div.postfinancecheckout-method a:not(.postfinancecheckout)').off('click.postfinancecheckout').on('click.postfinancecheckout', function (event) {
        event.stopPropagation()});
});