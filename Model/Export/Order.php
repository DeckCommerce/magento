<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Export;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\Export\Order as DeckOrder;
use DeckCommerce\Integration\Model\Service\HttpClient;
use DeckCommerce\Integration\Model\Service\Request\OrderBuilderInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\ScopeInterface;
use DeckCommerce\Integration\Model\Service\Response\Handler as ResponseHandler;

/**
 * Order Export Class
 */
class Order
{
    const EXPORT_STATUS = 'deck_export_status';

    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED  = 2;
    const STATUS_SKIPPED = 3;

    const GUEST_CUSTOMER_ID = "-100";

    const ERROR_DUPLICATE_ORDER = "Duplicate OrderNumber";

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var OrderBuilderInterface
     */
    protected $orderBuilder;

    /**
     * InventoryCheck constructor.
     * @param DeckHelper $helper
     * @param HttpClient $httpClient
     * @param OrderBuilderInterface $orderBuilder
     */
    public function __construct(
        DeckHelper $helper,
        HttpClient $httpClient,
        OrderBuilderInterface $orderBuilder
    ) {
        $this->helper                = $helper;
        $this->httpClient            = $httpClient;
        $this->orderBuilder          = $orderBuilder;
    }

    /**
     * Check if export status "Pending"
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function isPending($order)
    {
        return $order->getData(self::EXPORT_STATUS) == self::STATUS_PENDING;
    }

    /**
     * Check if export status "Success"
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function isSuccess($order)
    {
        return $order->getData(self::EXPORT_STATUS) == self::STATUS_SUCCESS;
    }

    /**
     * Check if export status "Failed"
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function isFailed($order)
    {
        return $order->getData(self::EXPORT_STATUS) == self::STATUS_FAILED;
    }

    /**
     * Check if export status "Skipped"
     *
     * @param OrderInterface $order
     * @return mixed
     */
    public function isSkipped($order)
    {
        return $order->getData(self::EXPORT_STATUS) == self::STATUS_SKIPPED;
    }

    /**
     * Is successful order
     *
     * @param array $response
     * @return bool
     */
    protected function isSuccessOrder($response)
    {
        return
            $response
            && isset($response[ResponseHandler::RESPONSE_CODE_FIELD])
            && in_array($response[ResponseHandler::RESPONSE_CODE_FIELD], [
               ResponseHandler::SUCCESS_RESPONSE_CODE,
               ResponseHandler::SUCCESSFULLY_QUEUED_RESPONSE_CODE
            ]);
    }

    /**
     * Validate if order can be exported
     *
     * @param OrderInterface $order
     * @throws ValidatorException
     */
    protected function validateOrder($order)
    {
        if ($order->getIsVirtual()) {
            throw new ValidatorException(__('Virtual order can not be exported.'));
        }

        //temporary allow offline order
        /*if ($order->getPayment()->getMethodInstance()->isOffline()) {
            throw new ValidatorException(__('Order with offline payment can not be exported.'));
        }*/
    }

    /**
     * Send Magento order data to DeckCommerce
     *
     * @param OrderInterface $order
     * @param string $scope
     * @return array|null
     */
    public function send($order, $scope = ScopeInterface::SCOPE_STORE)
    {
        $result = null;
        $errorMessage = __("Unable to export order to Deck Commerce. ");
        $status = self::STATUS_FAILED;

        try {
            $this->validateOrder($order);

            $params = $this->orderBuilder->build($order);

            $this->helper->addOrderExportLog('Request params:', $params);

            $result = $this->httpClient->execute(
                $this->helper->getOrderExportApiName(),
                'POST',
                $params,
                $scope
            );

            $this->helper->addOrderExportLog('Response result:', $result);

            if ($this->isSuccessOrder($result)) {
                $order->setData(self::EXPORT_STATUS, self::STATUS_SUCCESS);
                $order->addCommentToStatusHistory(__("Order has been exported to Deck Commerce."));
                $order->save();

                return $result;
            }
        } catch (ValidatorException $e) {
            $errorMessage .= $e->getMessage();
            $this->helper->addOrderExportLog('NOTICE:', $e->getMessage());
            $status = self::STATUS_SKIPPED;
        } catch (\Exception $e) {
            $errorMessage .= $e->getMessage();
            $this->helper->addOrderExportLog('!!! ERROR:', $e->getMessage());
            $status = strpos($errorMessage, self::ERROR_DUPLICATE_ORDER) === false
                ? self::STATUS_FAILED
                : self::STATUS_SUCCESS;
        }

        $order->setData(self::EXPORT_STATUS, $status);
        $order->addCommentToStatusHistory($errorMessage);
        $order->save();

        return $result;
    }
}
