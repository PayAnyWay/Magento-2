<?php

namespace PayAnyWay\PayAnyWay\Model;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;

/**
 * Class PayAnyWay
 * @package PayAnyWay\PayAnyWay\Model
 */
class PayAnyWay extends AbstractMethod
{
	/**
	 * @var bool
	 */
	protected $_isGateway = true;

	/**
	 * @var bool
	 */
	protected $_isInitializeNeeded = true;

	/**
	 * Payment code
	 *
	 * @var string
	 */
	protected $_code = 'payanyway';

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_isOffline = false;

	/**
	 * Payment additional info block
	 *
	 * @var string
	 */
	protected $_formBlockType = 'PayAnyWay\PayAnyWay\Block\Form\PayAnyWay';

	/**
	 * Sidebar payment info block
	 *
	 * @var string
	 */
	protected $_infoBlockType = 'Magento\Payment\Block\Info\Instructions';

	protected $_gateUrl = "";

	protected $_test;

	protected $orderFactory;

	public function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Magento\Sales\Model\OrderFactory $orderFactory,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
		array $data = [])
	{
		$this->orderFactory = $orderFactory;
		parent::__construct($context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData,
			$scopeConfig,
			$logger,
			$resource,
			$resourceCollection,
			$data);

		$this->_gateUrl = $this->getConfigData('payment_action');
		$this->_test = $this->getConfigData('mnt_test_mode');
	}


	/**
	 * Получить объект Order по его orderId
	 *
	 * @param $orderId
	 * @return Order
	 */
	protected function getOrder($orderId)
	{
		return $this->orderFactory->create()->loadByIncrementId($orderId);
	}


	/**
	 * Получить сумму платежа по orderId заказа
	 *
	 * @param $orderId
	 * @return float
	 */
	public function getAmount($orderId)
	{
		return $this->getOrder($orderId)->getGrandTotal();
	}


	/**
	 * Получить идентификатор клиента по orderId заказа
	 *
	 * @param $orderId
	 * @return int|null
	 */
	public function getCustomerId($orderId)
	{
		return $this->getOrder($orderId)->getCustomerId();
	}


	/**
	 * Получить код используемой валюты по orderId заказа
	 *
	 * @param $orderId
	 * @return null|string
	 */
	public function getCurrencyCode($orderId)
	{
		return $this->getOrder($orderId)->getBaseCurrencyCode();
	}


	/**
	 * Set order state and status
	 * (Этот метод вызывается при нажатии на кнопку "Place Order")
	 *
	 * @param string $paymentAction
	 * @param \Magento\Framework\DataObject $stateObject
	 * @return void
	 */
	public function initialize($paymentAction, $stateObject)
	{
		$stateObject->setState(Order::STATE_PENDING_PAYMENT);
		$stateObject->setStatus(Order::STATE_PENDING_PAYMENT);
		$stateObject->setIsNotified(false);
	}


	/**
	 * Check whether payment method can be used with selected shipping method
	 * (Проверка возможности доставки)
	 *
	 * @param string $shippingMethod
	 * @return bool
	 */
	protected function isCarrierAllowed($shippingMethod)
	{
		return strpos($this->getConfigData('allowed_carrier'), $shippingMethod) !== false;
	}


	/**
	 * Check whether payment method can be used
	 * (Проверка на доступность метода оплаты)
	 *
	 * @param CartInterface|null $quote
	 * @return bool
	 */
	public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
	{
		if ($quote === null)
		{
			return false;
		}
		return parent::isAvailable($quote) && $this->isCarrierAllowed(
			$quote->getShippingAddress()->getShippingMethod()
		);
	}


	/**
	 * Получить адрес платежного шлюза
	 *
	 * @return string
	 */
	public function getGateUrl()
	{
		return $this->_gateUrl;
	}


	/**
	 * Сгенерировать MNT_SIGNATURE
	 *
	 * @param $MNT_ID
	 * @param $MNT_TRANSACTION_ID
	 * @param $MNT_AMOUNT
	 * @param $MNT_CURRENCY_CODE
	 * @param $MNT_SUBSCRIBER_ID
	 * @param $TEST_MODE
	 * @param $MNT_DATAINTEGRITY_CODE
	 * @return string
	 */
	public function generateSignature($MNT_ID, $MNT_TRANSACTION_ID, $MNT_AMOUNT, $MNT_CURRENCY_CODE, $MNT_SUBSCRIBER_ID, $MNT_TEST_MODE, $MNT_DATAINTEGRITY_CODE)
	{
		return md5($MNT_ID.$MNT_TRANSACTION_ID.$MNT_AMOUNT.$MNT_CURRENCY_CODE.$MNT_SUBSCRIBER_ID.$MNT_TEST_MODE.$MNT_DATAINTEGRITY_CODE);
	}


	/**
	 * Получить код проверки целостности данных из конфигурации
	 *
	 * @return mixed
	 */
	public function getDataIntegrityCode()
	{
		return $this->getConfigData('mnt_dataintegrity_code');
	}


	private static function cleanProductName($value)
	{
		$result = preg_replace('/[^0-9a-zA-Zа-яА-Я ]/ui', '', htmlspecialchars_decode($value));
		$result = trim(mb_substr($result, 0, 20));
		return $result;
	}


	/**
	 * Получить массив параметров для формы оплаты
	 *
	 * @param $orderId
	 * @return array
	 */
	public function getPostData($orderId)
	{
		$postData = [];
		$postData['MNT_ID'] = $this->getConfigData("mnt_id");
		$postData['MNT_AMOUNT'] = number_format($this->getAmount($orderId), 2, '.', '');
		$postData['MNT_TRANSACTION_ID'] = $orderId;
		$postData['MNT_TEST_MODE'] = $this->getConfigData('mnt_test_mode');
		$postData['MNT_SUBSCRIBER_ID'] = $this->getCustomerId($orderId);
		$postData['MNT_CURRENCY_CODE'] = $this->getCurrencyCode($orderId);
		$postData['MNT_DESCRIPTION'] = "Payment for order #{$orderId}";
		$postData['MNT_SIGNATURE'] = $this->generateSignature($postData['MNT_ID'], $postData['MNT_TRANSACTION_ID'],
			$postData['MNT_AMOUNT'], $postData['MNT_CURRENCY_CODE'], $postData['MNT_SUBSCRIBER_ID'],
			$postData['MNT_TEST_MODE'], $this->getDataIntegrityCode());
		return $postData;
	}


	/**
	 * Проверить данные ответного запроса (Pay URL)
	 *
	 * @param $response
	 * @return bool
	 */
	private function checkMonetaResponse($response)
	{
		$this->_logger->debug("checking Pay URL...");
		foreach(
			[
				"MNT_ID",
				"MNT_TRANSACTION_ID",
				"MNT_OPERATION_ID",
				"MNT_AMOUNT",
				"MNT_CURRENCY_CODE",
				"MNT_SIGNATURE"
			] as $param)
		{
			if (!isset($response[$param]))
			{
				$this->_logger->debug("Pay URL: required field \"{$param}\" is missing");
				return false;
			}
		}

		$mnt_signature = md5(
			$response["MNT_ID"].
			$response["MNT_TRANSACTION_ID"].
			$response["MNT_OPERATION_ID"].
			$response["MNT_AMOUNT"].
			$response["MNT_CURRENCY_CODE"].
			@$response["MNT_SUBSCRIBER_ID"].
			$this->getConfigData('mnt_test_mode').
			$this->getDataIntegrityCode()
		);
		if ($response["MNT_SIGNATURE"] != $mnt_signature)
		{
			$this->_logger->debug("Pay URL: invalid signature");
			return false;
		}
		$this->_logger->debug("Pay URL - OK");
		return true;
	}


	/**
	 * KASSA ADDON
	 */
	const paw_kassa_VAT0     = '1104';  // НДС 0%
	const paw_kassa_VAT10    = '1103';  // НДС 10%
	const paw_kassa_VAT18    = '1102';  // НДС 18%
	const paw_kassa_VATNOVAT = '1105';  // НДС не облагается
	const paw_kassa_VATWR10  = '1107';  // НДС с рассч. ставкой 10%
	const paw_kassa_VATWR18  = '1106';  // НДС с рассч. ставкой 18%

	private static function monetaPayURLResponse($mnt_id, $mnt_transaction_id, $mnt_data_integrity_code, $success = false,
												 $repeatRequest = false, $echo = true, $die = true, $kassa_inventory = null, $kassa_customer = null, $kassa_delivery = null)
	{
		if ($success === true)
			$resultCode = '200';
		elseif ($repeatRequest === true)
			$resultCode = '402';
		else
			$resultCode = '500';
		$mnt_signature = md5($resultCode.$mnt_id.$mnt_transaction_id.$mnt_data_integrity_code);
		$response = '<?xml version="1.0" encoding="UTF-8" ?>'."\n";
		$response .= "<MNT_RESPONSE>\n";
		$response .= "<MNT_ID>{$mnt_id}</MNT_ID>\n";
		$response .= "<MNT_TRANSACTION_ID>{$mnt_transaction_id}</MNT_TRANSACTION_ID>\n";
		$response .= "<MNT_RESULT_CODE>{$resultCode}</MNT_RESULT_CODE>\n";
		$response .= "<MNT_SIGNATURE>{$mnt_signature}</MNT_SIGNATURE>\n";
		if (!empty($kassa_inventory) || !empty($kassa_customer) || !empty($kassa_delivery))
		{
			$response .= "<MNT_ATTRIBUTES>\n";
			foreach (array('INVENTORY' => $kassa_inventory, 'CUSTOMER' => $kassa_customer, 'DELIVERY' => $kassa_delivery) as $k => $v)
				if (!empty($v))
					$response .= "<ATTRIBUTE><KEY>{$k}</KEY><VALUE>{$v}</VALUE></ATTRIBUTE>\n";
			$response .= "</MNT_ATTRIBUTES>\n";
		}
		$response .= "</MNT_RESPONSE>\n";
		if ($echo === true)
		{
			header("Content-type: application/xml");
			echo $response;
		}
		else
			return $response;
		if ($die === true)
			die;
		return '';
	}


	/**
	 * Вызывается при запросе Pay URL со стороны PayAnyWay
	 *
	 * @param $responseData
	 */
	public function processSuccess($responseData)
	{
		$debugData = ['response' => $responseData];
		$this->_logger->debug("processSuccess", $debugData);

		if ($this->checkMonetaResponse($responseData))
		{
			/** @var Order $order */
			$order = $this->getOrder($responseData['MNT_TRANSACTION_ID']);
			if ($order && ($this->_processOrder($order, $responseData) === true))
			{
				if ($this->getConfigData('mnt_kassa') == '1')
				{
					$inventoryPositions = array();
					$orderItems = $order->getAllItems();
					/** @var Order/Item $orderItem */
					foreach ($orderItems as $orderItem)
						if ($orderItem->getQtyToInvoice() > 0)
							$inventoryPositions[] = array(
								'name' => trim(preg_replace("/&?[a-z0-9]+;/i", "", htmlspecialchars($orderItem->getName()))),
								'price' => $orderItem->getProduct()->getFinalPrice(),
								'quantity' => $orderItem->getQtyToInvoice(),
								'vatTag' => self::paw_kassa_VATNOVAT
							);

					$shippingAmount = $order->getShippingAmount();
					$kassa_delivery = null;
					if ($shippingAmount > 0)
						$kassa_delivery = $shippingAmount;

					$kassa_inventory = json_encode($inventoryPositions);

					self::monetaPayURLResponse($responseData['MNT_ID'], $responseData['MNT_TRANSACTION_ID'],
						$this->getDataIntegrityCode(), true, false, true, true, $kassa_inventory,
						$order->getCustomerEmail(), $kassa_delivery);											// 200
				}

				echo "SUCCESS";
				return;
			}
		}
		echo "FAIL";
	}


	/**
	 * Метод вызывается при вызове Pay URL
	 *
	 * @param Order $order
	 * @param mixed $response
	 * @return bool
	 */
	protected function _processOrder(Order $order, $response)
	{
		$this->_logger->debug("_processOrder",
			[
				"\$order" => $order,
				"\$response" => $response
			]);
		try
		{
			if ($order->getGrandTotal() != $response["MNT_AMOUNT"])
			{
				$this->_logger->debug("_processOrder: amount mismatch, order FAILED");
				return false;
			}

			if ($order->getGlobalCurrencyCode() != $response["MNT_CURRENCY_CODE"])
			{
				$this->_logger->debug("_processOrder: currency code mismatch, order FAILED");
				return false;
			}

			/** @var \Magento\Sales\Model\Order\Payment $payment */
			$payment = $order->getPayment();

			$payment->setTransactionId($response["MNT_TRANSACTION_ID"])->setIsTransactionClosed(0);
			$order->setStatus(Order::STATE_PROCESSING);
			$this->_logger->debug("_processOrder: order state changed: STATE_PROCESSING");

			$order->save();
			$this->_logger->debug("_processOrder: order data saved, order OK");
			return true;
		}
		catch (\Exception $e)
		{
			$this->_logger->debug("_processOrder exception", $e->getTrace());
			return false;
		}
	}
}

