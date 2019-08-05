{*
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch/checkout).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2019 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html'}" title="{l s='Go back to the Checkout' mod='postfinancecheckout'}">{l s='Checkout' mod='postfinancecheckout'}</a><span class="navigation-pipe">{$navigationPipe}</span>{$name|escape:'html':'UTF-8'}
{/capture}

<h1 class="page-heading">
    {l s='Order Review' mod='postfinancecheckout'}
</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $productNumber <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='postfinancecheckout'}
    </p>
{else}
	<div id="postfinancecheckout-processing-spinner-container" class="postfinancecheckout-processing-spinner-container invisible">
		<div class="postfinancecheckout-processing-spinner"></div>
	</div>
	{if $showCart}
		<div class="box">
			<p>
                <strong class="dark">
				{l s='Please finalize the order by clicking "I confirm my order".' mod='postfinancecheckout'}
			 </strong>
            </p>
		</div>
		{assign var='cartTemplate' value="{postfinancecheckout_resolve_template template='cart_contents.tpl'}"}
		{include file="$cartTemplate"}
	{else}
		<div class="box">
			<p class="postfinancecheckout-indent">
                <strong class="dark">
                	{l s='Please finalize your order.' mod='postfinancecheckout'}
                </strong>
            </p>
			<p>
                - {l s='The total amount of your order comes to' mod='postfinancecheckout'}
	                <span id="amount" class="price">{displayPrice price=$total_price}</span>
					{if $use_taxes == 1}
				    	{l s='(tax incl.)' mod='postfinancecheckout'}
				    {/if}
            </p>
            <p>
                - {l s='To finalize the order click "I confirm my order".' mod='postfinancecheckout'}
            </p>
		</div>
	{/if}
	
	<div id="postfinancecheckout-error-messages"></div>
	
	
	<div id="postfinancecheckout-payment-container" class="invisible">
	<h3 class="page-subheading" id="postfinancecheckout-method-title">
        <span><span style="font-size:smaller">{l s='Payment Method:' mod='postfinancecheckout'}</span> {$name|escape:'html':'UTF-8'}</span>
        
        <button class="button btn btn-default button-medium postfinancecheckout-submit right" id="postfinancecheckout-submit-top" disabled>
            <span>{l s='I confirm my order' mod='postfinancecheckout'}<i class="icon-chevron-right right"></i></span>
        </button>
    </h3>
	</div>
	<form action="{$form_target_url|escape:'html'}" method="post" id="postfinancecheckout-payment-form">
    	<input type="hidden" name="cartHash" value="{$cartHash|escape:'html':'UTF-8'}" />
    	<input type="hidden" name="methodId" value="{$methodId|escape:'html':'UTF-8'}" />
    	
        <div id="postfinancecheckout-method-configuration" class="postfinancecheckout-method-configuration" style="display: none;"
	data-method-id="{$methodId|escape:'html':'UTF-8'}" data-configuration-id="{$configurationId|escape:'html':'UTF-8'}"></div>
		<div id="postfinancecheckout-method-container">
			<input type="hidden" id="postfinancecheckout-iframe-possible" name="postfinancecheckout-iframe-possible" value="false" />
			<div class="postfinancecheckout-loader"></div>		
		</div>
		
		{if $showTOS && $conditions && $cmsId}
	 		{if isset($overrideTOSDisplay) && $overrideTOSDisplay}
	        	{$overrideTOSDisplay|escape:'html':'UTF-8'}
			{else}
				<div class="box">
					<p class="checkbox">
						<input type="checkbox" name="cgv" id="cgv" value="1" {if $checkedTOS}checked="checked"{/if}/>
						<label for="cgv">{l s='I agree to the terms of service and will adhere to them unconditionally.' mod='postfinancecheckout'}</label>
						<a href="{$linkConditions|escape:'html':'UTF-8'}" class="iframe" rel="nofollow">{l s='(Read the Terms of Service)' mod='postfinancecheckout'}</a>
					</p>
				</div>
			{/if}
		{/if}
        <p class="cart_navigation clearfix" id="cart_navigation">
            <a class="button-exclusive btn btn-default" href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}" tabindex="-1" id="postfinancecheckout-back">
                <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='postfinancecheckout'}
            </a>
            <button class="button btn btn-default button-medium postfinancecheckout-submit" id="postfinancecheckout-submit-bottom" disabled>
                <span>{l s='I confirm my order' mod='postfinancecheckout'}<i class="icon-chevron-right right"></i></span>
            </button>
        </p>
    </form>
    
    <script type="text/javascript">$("a.iframe").fancybox({
		"type" : "iframe",
		"width":600,
		"height":600
	});</script>
	
	{if $showTOS && $conditions && cmsId}
		{addJsDefL name=postfinancecheckout_msg_tos_error}{l s='You must agree to the terms of service before continuing.'  mod='postfinancecheckout' js=1}{/addJsDefL}
	{/if}
	{addJsDefL name=postfinancecheckout_msg_json_error}{l s='The server experienced an unexpected error, you may try again or try to use a different payment method.'  mod='postfinancecheckout' js=1}{/addJsDefL}
{/if}