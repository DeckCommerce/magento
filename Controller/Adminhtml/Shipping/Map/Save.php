<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Map;

use DeckCommerce\Integration\Model\MethodMap;
use Exception;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Validation\ValidationException;

/**
 * Save Controller
 */
class Save extends AbstractAction implements HttpPostActionInterface
{

    /**
     * Execute save shipping map action
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $requestData = $this->_request->getParams();
        if (!$this->_request->isPost() || empty($requestData[self::FORM_SOURCE_KEY])) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
            return $resultRedirect;
        }
        return $this->processSave($requestData, $resultRedirect);
    }

    /**
     * Save data
     *
     * @param array $requestData
     * @param Redirect $resultRedirect
     * @return ResultInterface
     */
    private function processSave(
        array $requestData,
        Redirect $resultRedirect
    ): ResultInterface {
        try {
            $methodMapId = isset($requestData[self::FORM_SOURCE_KEY][MethodMap::MAP_ID])
                ? (int)$requestData[self::FORM_SOURCE_KEY][MethodMap::MAP_ID]
                : null;

            /** @var MethodMap $methodMap */
            $methodMap = $this->methodMapFactory->create();
            $methodMap->setData($requestData[self::FORM_SOURCE_KEY]);
            $this->methodMapResourceModel->save($methodMap);

            $this->messageManager->addSuccessMessage(__('The Deck Commerce shipping method has been saved.'));
            $this->processRedirectAfterSuccessSave($resultRedirect);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The Deck Commerce shipping method does not exist.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $localizedError) {
                $this->messageManager->addErrorMessage($localizedError->getMessage());
            }
            $this->processRedirectAfterFailureSave($resultRedirect, $methodMapId);
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->processRedirectAfterFailureSave($resultRedirect, $methodMapId);
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->processRedirectAfterFailureSave($resultRedirect, $methodMapId);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save Deck Commerce shipping method.'));
            $this->processRedirectAfterFailureSave($resultRedirect, $methodMapId ?? null);
        }
        return $resultRedirect;
    }

    /**
     * Process success redirect
     *
     * @param Redirect $resultRedirect
     */
    private function processRedirectAfterSuccessSave(Redirect $resultRedirect)
    {
        $resultRedirect->setPath('deck/shipping_map/index');
    }

    /**
     * Process failure redirect
     *
     * @param Redirect $resultRedirect
     * @param int|null $methodMapId
     */
    private function processRedirectAfterFailureSave(Redirect $resultRedirect, int $methodMapId = null)
    {
        if (null === $methodMapId) {
            $resultRedirect->setPath('deck/shipping_map/new');
        } else {
            $resultRedirect->setPath('deck/shipping_map/edit', [
                MethodMap::MAP_ID => $methodMapId,
                '_current' => true,
            ]);
        }
    }
}
