<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order implements NostoOrderInterface
{
	/**
	 * @var string|int the unique order number identifying the order.
	 */
	protected $_orderNumber;

	/**
	 * @var string the date when the order was placed.
	 */
	protected $_createdDate;

	/**
	 * @var string the payment provider used for order.
	 *
	 * Formatted according to "[provider name] [provider version]".
	 */
	protected $_paymentProvider;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer The user info of the buyer.
	 */
	protected $_buyer;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem[] the items in the order.
	 */
	protected $_items = array();

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status the order status model.
	 */
	protected $_orderStatus;

	/**
	 * @var bool if special line items like shipping cost should be included.
	 */
	protected $_includeSpecialLineItems = true;

	/**
	 * Loads order details from the order model based on it's order number.
	 *
	 * @param int $orderNumber the order number of the order model.
	 */
	public function loadData($orderNumber)
	{
		if (!($orderNumber > 0)) {
			return;
		}

		/** @var Shopware\Models\Order\Order $order */
		$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(array('number' => $orderNumber));
		if (!is_null($order)) {
			$this->_orderNumber = $order->getNumber();
			$this->_createdDate = $order->getOrderTime()->format('Y-m-d');

			$this->_paymentProvider = $order->getPayment()->getName();
			$paymentPlugin = $order->getPayment()->getPlugin();
			if (!is_null($paymentPlugin)) {
				$this->_paymentProvider .= sprintf(' [%s]', $paymentPlugin->getVersion());
			}

			$this->_orderStatus = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status();
			$this->_orderStatus->loadData($order);

			$this->_buyer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer();
			$this->_buyer->loadData($order->getCustomer());

			foreach ($order->getDetails() as $detail) {
				/** @var Shopware\Models\Order\Detail $detail */
				if ($this->_includeSpecialLineItems || $detail->getArticleId() > 0) {
					$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem();
					$item->loadData($detail);
					$this->_items[] = $item;
				}
			}

			if ($this->_includeSpecialLineItems) {
				$shippingCost = $order->getInvoiceShipping();
				if ($shippingCost > 0) {
					$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem();
					$item->loadSpecialItemData('Shipping cost', $shippingCost, $order->getCurrency());
					$this->_items[] = $item;
				}
			}
		}
	}

	/**
	 * Disables "special" line items when calling `loadData()`.
	 * Special items are shipping cost, cart based discounts etc.
	 */
	public function disableSpecialLineItems()
	{
		$this->_includeSpecialLineItems = false;
	}

	/**
	 * @inheritdoc
	 */
	public function getOrderNumber()
	{
		return $this->_orderNumber;
	}

	/**
	 * @inheritdoc
	 */
	public function getCreatedDate()
	{
		return $this->_createdDate;
	}

	/**
	 * @inheritdoc
	 */
	public function getPaymentProvider()
	{
		return $this->_paymentProvider;
	}

	/**
	 * @inheritdoc
	 */
	public function getBuyerInfo()
	{
		return $this->_buyer;
	}

	/**
	 * @inheritdoc
	 */
	public function getPurchasedItems()
	{
		return $this->_items;
	}

	/**
	 * @inheritdoc
	 */
	public function getOrderStatus()
	{
		return $this->_orderStatus;
	}
}
