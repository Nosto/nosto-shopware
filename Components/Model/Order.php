<?php

class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order extends Shopware_Plugins_Frontend_NostoTagging_Components_Model_Base implements NostoOrderInterface {
	/**
	 * @var string|int the unique order number identifying the order.
	 */
	protected $_order_number;

	/**
	 * @var string the date when the order was placed.
	 */
	protected $_created_date;

	/**
	 * @var string the payment provider used for order.
	 *
	 * Formatted according to "[provider name] [provider version]".
	 */
	protected $_payment_provider;

    /**
     * @var string the payment status of the order.
     */
    protected $_payment_status;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer The user info of the buyer.
	 */
	protected $_buyer;

	/**
	 * @var Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem[] the items in the order.
	 */
	protected $_items = array();

	/**
	 * @var bool if special line items like shipping cost should be included.
	 */
	protected $_include_special_line_items = true;

    /**
     * Returns an array of required items in the model.
     *
     * @return array the list of required items.
     */
    public function getRequiredAttributes() {
        return array(
            '_order_number',
            '_created_date',
            '_payment_provider',
            '_payment_status',
            '_buyer',
            '_items',
        );
    }

	/**
	 * Loads order details from the order model based on it's order number.
	 *
	 * @param int $order_number the order number of the order model.
	 */
	public function loadData($order_number) {
		if (!($order_number > 0)) {
			return;
		}

		/** @var Shopware\Models\Order\Order $order */
		$order = Shopware()->Models()->getRepository('Shopware\Models\Order\Order')->findOneBy(array('number' => $order_number));
		if (!is_null($order)) {
			$this->_order_number = $order->getNumber();
			$this->_created_date = $order->getOrderTime()->format('Y-m-d');
			$this->_payment_provider = $order->getPayment()->getName();
			$payment_plugin = $order->getPayment()->getPlugin();
			if (!is_null($payment_plugin)) {
				$this->_payment_provider .= sprintf(' [%s]', $payment_plugin->getVersion());
			}
            $this->_payment_status = $order->getOrderStatus()->getDescription(); // $order->getPaymentStatus()->getDescription()

			$this->_buyer = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Buyer();
			$this->_buyer->loadData($order->getCustomer());

			foreach ($order->getDetails() as $detail) {
				/** @var Shopware\Models\Order\Detail $detail */
				if ($this->_include_special_line_items || $detail->getArticleId() > 0) {
					$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem();
					$item->loadData($detail);
					$this->_items[] = $item;
				}
			}

			if ($this->_include_special_line_items) {
				$shipping_cost = $order->getInvoiceShipping();
				if ($shipping_cost > 0) {
					$item = new Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_LineItem();
					$item->loadSpecialItemData('Shipping cost', $shipping_cost, $order->getCurrency());
					$this->_items[] = $item;
				}
			}
		}
	}

	/**
	 * Disables the special line items so they are not included when calling `loadData()`.
	 */
	public function disableSpecialLineItems() {
		$this->_include_special_line_items = false;
	}

	/**
	 * The unique order number identifying the order.
	 *
	 * @return string|int the order number.
	 */
	public function getOrderNumber()
	{
		return $this->_order_number;
	}

	/**
	 * The date when the order was placed.
	 *
	 * @return string the creation date.
	 */
	public function getCreatedDate()
	{
		return $this->_created_date;
	}

	/**
	 * The payment provider used for placing the order, formatted according to "[provider name] [provider version]".
	 *
	 * @return string the payment provider.
	 */
	public function getPaymentProvider()
	{
		return $this->_payment_provider;
	}

    /**
     * The orders payment status.
     *
     * @return string the status.
     */
    public function getPaymentStatus() {
        return $this->_payment_status;
    }

	/**
	 * The buyer info of the user who placed the order.
	 *
	 * @return NostoOrderBuyerInterface the meta data model.
	 */
	public function getBuyerInfo()
	{
		return $this->_buyer;
	}

	/**
	 * The purchased items which were included in the order.
	 *
	 * @return NostoOrderPurchasedItemInterface[] the meta data models.
	 */
	public function getPurchasedItems()
	{
		return $this->_items;
	}
}
