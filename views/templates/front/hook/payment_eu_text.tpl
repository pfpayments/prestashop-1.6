{*
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
{$name|escape:'html':'UTF-8'}
{if !empty($description)}
			<span class="payment-method-description">{postfinancecheckout_clean_html text=$description}</span>
{/if}

{if !empty($surchargeValues)}
	<span class="postfinancecheckout-surcharge postfinancecheckout-additional-amount"><span class="postfinancecheckout-surcharge-text postfinancecheckout-additional-amount-text">{l s='Minimum Sales Surcharge:' mod='postfinancecheckout'}</span>
		<span class="postfinancecheckout-surcharge-value postfinancecheckout-additional-amount-value">
			{if $priceDisplay}
	          	{displayPrice price=$surchargeValues.surcharge_total} {if $display_tax_label}{l s='(tax excl.)' mod='postfinancecheckout'}{/if}
	        {else}
	          	{displayPrice price=$surchargeValues.surcharge_total_wt} {if $display_tax_label}{l s='(tax incl.)' mod='postfinancecheckout'}{/if}
	        {/if}
       </span>
   </span>
{/if}
{if !empty($feeValues)}
	<span class="postfinancecheckout-payment-fee postfinancecheckout-additional-amount"><span class="postfinancecheckout-payment-fee-text postfinancecheckout-additional-amount-text">{l s='Payment Fee:' mod='postfinancecheckout'}</span>
		<span class="postfinancecheckout-payment-fee-value postfinancecheckout-additional-amount-value">
			{if $priceDisplay}
	          	{displayPrice price=$feeValues.fee_total} {if $display_tax_label}{l s='(tax excl.)' mod='postfinancecheckout'}{/if}
	        {else}
	          	{displayPrice price=$feeValues.fee_total_wt} {if $display_tax_label}{l s='(tax incl.)' mod='postfinancecheckout'}{/if}
	        {/if}
       </span>
   </span>
{/if}