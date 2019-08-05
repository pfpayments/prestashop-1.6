{*
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2019 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div class="row">
	<div class="col-xs-12">
		<div class="payment_module postfinancecheckout-method">
			<div class="postfinancecheckout {if empty($image)}no_logo{/if}" 
					{if !empty($image)} 
						style="background-image: url({$image|escape:'html'}); background-repeat: no-repeat; background-size: 64px; background-position:15px;"		
					{/if}
					onclick="document.getElementById('postfinancecheckout-{$methodId|escape:'html':'UTF-8'}-link').click();" >
				<a class="postfinancecheckout" id="postfinancecheckout-{$methodId|escape:'html':'UTF-8'}-link" href="{$link|escape:'html'}" title="{$name}" >{$name}</a>
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
			</div>					
		</div>	
	</div>
</div>
