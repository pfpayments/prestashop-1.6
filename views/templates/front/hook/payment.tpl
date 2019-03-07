{*
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch).
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
					onclick="document.getElementById('postfinancecheckout-{$methodId}-link').click();" >
				<a class="postfinancecheckout" id="postfinancecheckout-{$methodId}-link" href="{$link|escape:'html'}" title="{$name}" >{$name}</a>
			{if !empty($description)}
				<span class="payment-method-description">{$description}</span>
			{/if}
			{if !empty($feeValues)}
				<span class="postfinancecheckout-payment-fee"><span class="postfinancecheckout-payment-fee-text">{l s='Additional Fee:' mod='postfinancecheckout'}</span>
					<span class="postfinancecheckout-payment-fee-value">
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
