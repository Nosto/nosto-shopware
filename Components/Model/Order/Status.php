<?php

/**
 * Created by PhpStorm.
 * User: lindqvic
 * Date: 13/05/15
 * Time: 09:06
 */
class Shopware_Plugins_Frontend_NostoTagging_Components_Model_Order_Status implements NostoOrderStatusInterface
{
	/**
	 * @var string the order status code.
	 */
	protected $_code;

	/**
	 * @var string the order status label.
	 */
	protected $_label;

	/**
	 * Populates the order status with data from the order model.
	 *
	 * @param Shopware\Models\Order\Order $order the order model.
	 */
	public function loadData(Shopware\Models\Order\Order $order)
	{
		// todo: implement
		// $this->_payment_status = $order->getOrderStatus()->getDescription(); // $order->getPaymentStatus()->getDescription()
	}

	/**
	 * @inheritdoc
	 */
	public function getCode()
	{
		return $this->_code;
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel()
	{
		return $this->_label;
	}
} 