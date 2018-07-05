<?php
if (! defined('_PS_VERSION_')) {
    exit();
}

/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Provider of payment connector information from the gateway.
 */
class PostFinanceCheckout_Provider_PaymentConnector extends PostFinanceCheckout_Provider_Abstract {

	protected function __construct(){
		parent::__construct('postfinancecheckout_connectors');
	}

	/**
	 * Returns the payment connector by the given id.
	 *
	 * @param int $id
	 * @return \PostFinanceCheckout\Sdk\Model\PaymentConnector
	 */
	public function find($id){
		return parent::find($id);
	}

	/**
	 * Returns a list of payment connectors.
	 *
	 * @return \PostFinanceCheckout\Sdk\Model\PaymentConnector[]
	 */
	public function getAll(){
		return parent::getAll();
	}

	protected function fetchData(){
	    $connectorService = new \PostFinanceCheckout\Sdk\Service\PaymentConnectorService(PostFinanceCheckout_Helper::getApiClient());
		return $connectorService->all();
	}

	protected function getId($entry){
		/* @var \PostFinanceCheckout\Sdk\Model\PaymentConnector $entry */
		return $entry->getId();
	}
}