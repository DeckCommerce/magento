<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Method;

use DeckCommerce\Integration\Model\DeckMethod;
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
     * Execute save Deck Commerce shipping action
     *
     * @return ResultInterface
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
            $deckMethodId = isset($requestData[self::FORM_SOURCE_KEY][DeckMethod::DECK_METHOD_ID])
                ? (int)$requestData[self::FORM_SOURCE_KEY][DeckMethod::DECK_METHOD_ID]
                : null;

            /** @var DeckMethod $deckMethod */
            $deckMethod = $this->deckMethodFactory->create();
            $deckMethod->setData($requestData[self::FORM_SOURCE_KEY]);
            $this->deckMethodResourceModel->save($deckMethod);

            $this->messageManager->addSuccessMessage(__('The Deck Commerce shipping method has been saved.'));
            $this->processRedirectAfterSuccessSave($resultRedirect);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The Deck Commerce shipping method does not exist.'));
            $this->processRedirectAfterFailureSave($resultRedirect);
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $localizedError) {
                $this->messageManager->addErrorMessage($localizedError->getMessage());
            }
            $this->processRedirectAfterFailureSave($resultRedirect, $deckMethodId);
        } catch (CouldNotSaveException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->processRedirectAfterFailureSave($resultRedirect, $deckMethodId);
        } catch (InputException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->processRedirectAfterFailureSave($resultRedirect, $deckMethodId);
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not save Deck Commerce shipping method.'));
            $this->processRedirectAfterFailureSave($resultRedirect, $deckMethodId ?? null);
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
        $resultRedirect->setPath('deck/shipping_method/index');
    }

    /**
     * Process failure redirect
     *
     * @param Redirect $resultRedirect
     * @param int|null $deckMethodId
     *
     * @return void
     */
    private function processRedirectAfterFailureSave(Redirect $resultRedirect, int $deckMethodId = null)
    {
        if (null === $deckMethodId) {
            $resultRedirect->setPath('deck/shipping_method/new');
        } else {
            $resultRedirect->setPath('deck/shipping_method/edit', [
                DeckMethod::DECK_METHOD_ID => $deckMethodId,
                '_current' => true,
            ]);
        }
    }
}
