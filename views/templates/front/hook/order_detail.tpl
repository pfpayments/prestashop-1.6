{*
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2019 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div id="postfinancecheckout_documents" style="display:none">
{if !empty($postFinanceCheckoutInvoice)}
	<p class="postfinancecheckout-document">
		<i class="icon-file-text-o"></i>
		<a target="_blank" href="{$postFinanceCheckoutInvoice|escape:'html'}">{l s='Download your %s invoice as a PDF file.' sprintf='PostFinance Checkout' mod='postfinancecheckout'}</a>
	</p>
{/if}
{if !empty($postFinanceCheckoutPackingSlip)}
	<p class="postfinancecheckout-document">
		<i class="icon-truck"></i>
		<a target="_blank" href="{$postFinanceCheckoutPackingSlip|escape:'html'}">{l s='Download your %s packing slip as a PDF file.' sprintf='PostFinance Checkout' mod='postfinancecheckout'}</a>
	</p>
{/if}
</div>
<script type="text/javascript">

jQuery(function($) {    
    $('#postfinancecheckout_documents').find('p.postfinancecheckout-document').each(function(key, element){
	
		$(".info-order.box").append(element);
    });
});

</script>