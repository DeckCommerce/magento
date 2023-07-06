<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Method;

use DeckCommerce\Integration\Model\DeckMethod;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Delete Controller
 */
class Delete extends AbstractAction implements HttpPostActionInterface
{

    /**
     * Execute delete Deck Commerce shipping action
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $deckMethodId = (int)$this->getRequest()->getParam(DeckMethod::DECK_METHOD_ID);
        if ($deckMethodId === null) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            return $resultRedirect->setPath('*/*');
        }

        try {
            $deckMethodId = (int)$deckMethodId;
            $this->deckMethodResourceModel->delete($this->getDeckMethod($deckMethodId));
            $this->messageManager->addSuccessMessage(__('The Shipping Methods map has been deleted.'));
            $resultRedirect->setPath('*/*');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*/*/edit', [
                DeckMethod::DECK_METHOD_ID => $deckMethodId,
                '_current' => true,
            ]);
        }

        return $resultRedirect;
    }
}
