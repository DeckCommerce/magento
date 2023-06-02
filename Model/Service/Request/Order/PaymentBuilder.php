<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request\Order;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use Magento\OfflinePayments\Model\Banktransfer;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\OfflinePayments\Model\Purchaseorder;
use Magento\Payment\Model\Method\Free;
use Magento\Paypal\Model\Config as PaypalConfig;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;

/**
 * Order PaymentBuilder model
 */
class PaymentBuilder
{

    const PAYMENT_PROCESSOR_STORE_CREDIT  = 'Cash';
    const PAYMENT_PROCESSOR_REWARD_POINTS = 'Loyalty';

    const MAPPING_ORDER_FIELD_PREFIX             = 'order:';
    const MAPPING_PAYMENT_ADDITIONAL_INFO_PREFIX = 'payment:additional_info:';
    const MAPPING_PAYMENT_FIELD_PREFIX           = 'payment:';
    const MAPPING_CONFIG_FIELD_PREFIX            = 'config:';

    const MAPPING_VARIABLE_TOKEN = '@payment_token';

    /** @var DeckHelper  */
    protected $helper;

    /**
     * CC cards mapping
     *
     * @var string[]
     */
    protected $supportedCcTypes = [
        'VI'  => 'Visa',
        'MC'  => 'MasterCard',
        'DI'  => 'Discover/DinersClub',
        'AE'  => 'Amex',
        'SM'  => 'Switch/Maestro',
        'MI'  => 'Maestro',
        'SO'  => 'Solo',
        'DN'  => 'DinersClub',
        'HC'  => 'Hipercard',
        'AU'  => 'Aura',
        'JCB' => 'JCB',
        'CUP' => 'UnionPay',
        'ELO' => 'Elo'
    ];

    /**
     * ItemBuilder constructor.
     *
     * @param DeckHelper $helper
     */
    public function __construct(
        DeckHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Build Order payments data for exported order
     *
     * @param OrderInterface $order
     * @return array
     */
    public function build(OrderInterface $order)
    {
        return [
            "OrderPayments" => $this->getOrderPayments($order)
        ];
    }

    /**
     * Get authorized amount
     *
     * @param OrderPaymentInterface $orderPayment
     * @return mixed
     */
    protected function getAuthorizedAmount($orderPayment)
    {
        return $orderPayment->getAmountAuthorized() > 0
            ? $orderPayment->getAmountAuthorized()
            : $orderPayment->getAmountOrdered();
    }

    /**
     * Get Payment token
     *
     * @param OrderPaymentInterface $orderPayment
     * @return mixed
     */
    protected function getPaymentToken($orderPayment)
    {
        if ($orderPayment->getExtensionAttributes()
            && $orderPayment->getExtensionAttributes()->getVaultPaymentToken()
            && $orderPayment->getExtensionAttributes()->getVaultPaymentToken()->getGatewayToken()
        ) {
            return $orderPayment->getExtensionAttributes()->getVaultPaymentToken()->getGatewayToken();
        }

        return $orderPayment->getLastTransId();
    }

    /**
     * Get order payments data
     *
     * @param OrderInterface $order
     * @return array
     */
    protected function getOrderPayments(OrderInterface $order)
    {
        $orderPayment = $order->getPayment();

        $data = [];
        if ($orderPayment->getAmountAuthorized() > 0 || $orderPayment->getAmountPaid() > 0) {
            $data[] = $this->getMainOrderPayment($orderPayment);
        }

        $storeCreditPaymentData = $this->getStoreCreditPayment($order->getBaseCustomerBalanceAmount());
        if (!empty($storeCreditPaymentData)) {
            $data[] = $storeCreditPaymentData;
        }

        $rewardPointsPaymentData = $this->getRewardPointsPayment($order->getBaseRewardCurrencyAmount());
        if (!empty($rewardPointsPaymentData)) {
            $data[] = $rewardPointsPaymentData;
        }

        return $data;
    }

    /**
     * Get main order payment data
     *
     * @param OrderPaymentInterface $orderPayment
     * @return array
     */
    protected function getMainOrderPayment(OrderPaymentInterface $orderPayment)
    {
        $data = [
            "PaymentProcessorSubTypeName" => $this->getPaymentSubTypeName($orderPayment),
            "Generic1"                    => $this->getPaymentMethod($orderPayment),
            "Generic4"                    => "",
            "Generic5"                    => "",
            "AuthorizedAmount"            => $this->helper->formatPrice($this->getAuthorizedAmount($orderPayment)),
        ];

        if ($orderPayment->getMethod() === 'amazon_payment') {
            $data["Generic2"] = $this->getAmazonOrderReferenceId($orderPayment);
            $data["Generic3"] = "";
        } elseif ($orderPayment->getMethod() == Free::PAYMENT_METHOD_FREE_CODE) {
            $data["PaymentToken"] = '';
            $data["CreditCard"]   = null;
            $data["Generic2"]     = '';
            $data["Generic3"]     = '';
        } else {
            $data["PaymentToken"] = $this->getPaymentToken($orderPayment);
            $data["Generic2"]     = $this->getCcLast4($orderPayment);
            $data["Generic3"]     = $this->getExprDate($orderPayment);
        }

        $data["OrderTransactions"] = $this->getOrderTransactions($orderPayment);

        $data = $this->applyPaymentMethodsMapping($orderPayment, $data);

        return $data;
    }

    /**
     * Override payment method values by the values specified in the configuration json
     *
     * @param OrderPaymentInterface $orderPayment
     * @param array $defaultMappingData
     * @return array
     */
    protected function applyPaymentMethodsMapping($orderPayment, $defaultMappingData)
    {
        $paymentMethodsMapping = $this->helper->getPaymentMethodsMapping();
        if ($paymentMethodsMapping && isset($paymentMethodsMapping[$orderPayment->getMethod()])) {

            $order = $orderPayment->getOrder();
            $paymentMethodsMappingFields = $paymentMethodsMapping[$orderPayment->getMethod()];
            foreach ($paymentMethodsMappingFields as $mappingFieldName => $mappingFieldValue) {
                if (is_array($mappingFieldValue)) {
                    continue;
                }

                $orderFieldValue = $this->processObjectTypeField(
                    $order, self::MAPPING_ORDER_FIELD_PREFIX, $mappingFieldValue
                );
                if (!is_null($orderFieldValue)) {
                    $defaultMappingData[$mappingFieldName] = $orderFieldValue;
                    continue;
                }

                $paymentAdditionalInfoValue = $this->processObjectTypeField(
                    $orderPayment, self::MAPPING_PAYMENT_ADDITIONAL_INFO_PREFIX, $mappingFieldValue
                );
                if (!is_null($paymentAdditionalInfoValue)) {
                    $defaultMappingData[$mappingFieldName] = $paymentAdditionalInfoValue;
                    continue;
                }

                $paymentFieldValue = $this->processObjectTypeField(
                    $orderPayment, self::MAPPING_PAYMENT_FIELD_PREFIX, $mappingFieldValue
                );
                if (!is_null($paymentFieldValue)) {
                    $defaultMappingData[$mappingFieldName] = $paymentFieldValue;
                    continue;
                }

                $configValue = $this->processConfigField($mappingFieldValue);
                if (!is_null($configValue)) {
                    $defaultMappingData[$mappingFieldName] = $configValue;
                    continue;
                }
                $customVariableValue = $this->processCustomMappingVariables($orderPayment, $mappingFieldValue);
                if (!is_null($customVariableValue)) {
                    $defaultMappingData[$mappingFieldName] = $customVariableValue;
                    continue;
                }

                $defaultMappingData[$mappingFieldName] = $mappingFieldValue;
            }
        }

        return $defaultMappingData;
    }

    /**
     * Process dynamic values with prefix "order:" or "payment:"
     * Method replaces values like "order:increment_id" with the actual values of specified order or payment by the field
     *
     * @param OrderInterface | OrderPaymentInterface $object
     * @param string $mappingFieldPrefix
     * @param string $mappingFieldValue
     * @return string | null
     */
    protected function processObjectTypeField($object, $mappingFieldPrefix, $mappingFieldValue)
    {
        if (strpos($mappingFieldValue, $mappingFieldPrefix) !== false) {
            $objectFieldName  = str_replace($mappingFieldPrefix, '', $mappingFieldValue);

            if ($mappingFieldPrefix === self::MAPPING_PAYMENT_ADDITIONAL_INFO_PREFIX) {
                return (string) $object->getAdditionalInformation($objectFieldName);
            }

            $objectFieldValue = $object->getData($objectFieldName);
            if (is_numeric($objectFieldValue) && strpos($objectFieldValue, '.') !== false) {
                return (string) $this->helper->formatPrice($objectFieldValue);
            }

            return (string) $objectFieldValue;
        }

        return null;
    }

    /**
     * Process dynamic values with prefix "config:"
     * Method replaces values like "config:general/store_information/name" with the actual values of system configuration by path
     *
     * @param string $mappingFieldValue
     * @return string | null
     */
    protected function processConfigField($mappingFieldValue)
    {
        if (strpos($mappingFieldValue, self::MAPPING_CONFIG_FIELD_PREFIX) !== false) {
            $configPath = str_replace(self::MAPPING_CONFIG_FIELD_PREFIX, '', $mappingFieldValue);
            return (string) $this->helper->getConfigValue($configPath);
        }

        return null;
    }

    /**
     * Process dynamic custom variables values
     * Available variable: "@payment_token"
     *
     * @param $payment
     * @param $mappingFieldValue
     * @return string | null
     */
    protected function processCustomMappingVariables($payment, $mappingFieldValue)
    {
        if (strpos($mappingFieldValue, self::MAPPING_VARIABLE_TOKEN) !== false) {
            return (string) $this->getPaymentToken($payment);
        }

        return null;
    }

    /**
     * Get Store Credit payment data
     *
     * @param null|float $customerBalanceAmount
     * @return array
     */
    protected function getStoreCreditPayment($customerBalanceAmount)
    {
        if (!$customerBalanceAmount) {
            return [];
        }

        $data = [
            "PaymentProcessorSubTypeID"   => 0,
            "PaymentProcessorSubTypeName" => self::PAYMENT_PROCESSOR_STORE_CREDIT,
            "AuthorizedAmount"            => $this->helper->formatPrice($customerBalanceAmount),
            "OrderTransactions"           => [
                [
                  "TransactionAmount"        => $this->helper->formatPrice($customerBalanceAmount)
                ]
            ]
        ];

        return $data;
    }

    /**
     * Get Reward Points payment data
     *
     * @param null|float $rewardPointsAmount
     * @return array
     */
    protected function getRewardPointsPayment($rewardPointsAmount)
    {
        if (!$rewardPointsAmount) {
            return [];
        }

        $data = [
            "PaymentProcessorSubTypeID"   => 0,
            "PaymentProcessorSubTypeName" => self::PAYMENT_PROCESSOR_REWARD_POINTS,
            "AuthorizedAmount"            => $this->helper->formatPrice($rewardPointsAmount),
            "OrderTransactions"           => [
                [
                    "TransactionAmount"        => $this->helper->formatPrice($rewardPointsAmount)
                ]
            ]
        ];

        return $data;
    }

    /**
     * Get payment processor
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getPaymentSubTypeName(OrderPaymentInterface $orderPayment)
    {
        switch ($orderPayment->getMethod()) {
            case Checkmo::PAYMENT_METHOD_CHECKMO_CODE:
            case Free::PAYMENT_METHOD_FREE_CODE:
            case Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE:
                $paymentTypeName = 'Cash';
                break;
            case 'amazon_payment':
                $paymentTypeName = 'AmazonPay';
                break;
            case Purchaseorder::PAYMENT_METHOD_PURCHASEORDER_CODE:
                $paymentTypeName = 'PurchaseOrder';
                break;
            case Banktransfer::PAYMENT_METHOD_BANKTRANSFER_CODE:
                $paymentTypeName = 'Cod';
                break;
            case 'applepay':
                $paymentTypeName = 'ApplePay';
                break;
            case 'braintree':
                $paymentTypeName = 'Braintree';
                break;
            case 'ebay':
                $paymentTypeName = 'eBay';
                break;
            case 'giftcardaccount':
                $paymentTypeName = 'GiftCard';
                break;
            case PaypalConfig::METHOD_EXPRESS:
            case PaypalConfig::METHOD_PAYMENT_PRO:
            case PaypalConfig::METHOD_PAYFLOWPRO:
            case PaypalConfig::METHOD_WPP_PE_EXPRESS:
            case PaypalConfig::METHOD_WPP_DIRECT:
            case PaypalConfig::METHOD_PAYFLOWLINK:
            case PaypalConfig::METHOD_PAYFLOWADVANCED:
            case PaypalConfig::METHOD_HOSTEDPRO:
                $paymentTypeName = 'PayPal';
                break;
            default:
                $paymentTypeName = $this->getPaymentMethodTitle($orderPayment);
        }

        return $paymentTypeName;
    }

    /**
     * Get payment method title name
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     */
    protected function getPaymentMethodTitle(OrderPaymentInterface $orderPayment)
    {
        $additionalInformation = $orderPayment->getAdditionalInformation();
        if (is_array($additionalInformation)
            && isset($additionalInformation['method_title'])
            && $additionalInformation['method_title']
        ) {
            return $additionalInformation['method_title'];
        }

        return $orderPayment->getMethod();
    }

    /**
     * Get payment method name
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     */
    protected function getPaymentMethod(OrderPaymentInterface $orderPayment)
    {
        if ($orderPayment->getCcType()) {
            return $this->supportedCcTypes[$orderPayment->getCcType()] ?? $orderPayment->getCcType();
        }

        return $this->getPaymentMethodTitle($orderPayment);
    }

    /**
     * Get Amazon payment reference id
     *
     * @param OrderPaymentInterface $orderPayment
     * @return null
     */
    protected function getAmazonOrderReferenceId(OrderPaymentInterface $orderPayment)
    {
        $order = $orderPayment->getOrder();
        if (!empty($order->getExtensionAttributes()->getAmazonOrderReferenceId())) {
            return $order->getExtensionAttributes()->getAmazonOrderReferenceId()->getAmazonOrderReferenceId();
        }
        return null;
    }

    /**
     * Get CC payment expiration date
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     */
    protected function getExprDate(OrderPaymentInterface $orderPayment)
    {
        if ($orderPayment->getCcExpMonth() && $orderPayment->getCcExpYear()) {
            return sprintf('%s/%s', $orderPayment->getCcExpMonth(), $orderPayment->getCcExpYear());
        }
        return "";
    }

    /**
     * Get CC payment last 4
     *
     * @param OrderPaymentInterface $orderPayment
     * @return string
     */
    protected function getCcLast4(OrderPaymentInterface $orderPayment)
    {
        return $orderPayment->getCcLast4() ?: $orderPayment->getAdditionalInformation('last4') ?: '';
    }

    /**
     * Get payment transaction data
     *
     * @param OrderPaymentInterface $orderPayment
     * @return array
     */
    private function getOrderTransactions(OrderPaymentInterface $orderPayment)
    {
        $transactions[] = [
            "Generic1"          => $orderPayment->getLastTransId(),
            "Generic2"          => "",
            "Generic3"          => "",
            "Generic4"          => "",
            "Generic5"          => "",
            "TransactionAmount" => $this->helper->formatPrice($orderPayment->getAmountOrdered())
        ];

        return $transactions;
    }
}
