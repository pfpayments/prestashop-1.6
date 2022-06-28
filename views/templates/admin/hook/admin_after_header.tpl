{*
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://postfinance.ch/en/business/products/e-commerce/postfinance-checkout-all-in-one.html).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div id="postfinancecheckout_notifications" style="display:none";>
	<li id="postfinancecheckout_manual_notifs" class="dropdown" data-type="postfinancecheckout_manual_messages">	
		<a href="javascript:void(0);" class="dropdown-toggle notifs" data-toggle="dropdown">
			<i class="icon-bullhorn"></i>
				{if $manualTotal > 0}
					<span id="postfinancecheckout_manual_messages_notif_number_wrapper" class="notifs_badge">
						<span id="postfinancecheckout_manual_messages_notif_value">{$manualTotal|escape:'html':'UTF-8'}</span>
					</span>
				{/if}
		</a>
		<div class="dropdown-menu notifs_dropdown">
			<section id="postfinancecheckout_manual_messages_notif_number_wrapper" class="notifs_panel">
				<div class="notifs_panel_header">
					<h3>Manual Tasks</h3>
				</div>
				<div id="list_postfinancecheckout_manual_messages_notif" class="list_notif">
					{if $manualTotal > 0}
					<a href="{$manualUrl|escape:'html'}" target="_blank">
						<p>{if $manualTotal > 1}
							{l s='There are %s manual tasks that need your attention.' sprintf=$manualTotal mod='postfinancecheckout'}
						{else}
							{l s='There is a manual task that needs your attention.' mod='postfinancecheckout'}
						{/if}
						</p>
					</a>
					{else}
						<span class="no_notifs">
						{l s='There are no manual tasks.' mod='postfinancecheckout'}
						</span>
					{/if}
				</div>
			</section>
		</div>
	</li>
</div>