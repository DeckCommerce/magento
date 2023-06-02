<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Map;

use DeckCommerce\Integration\Model\MethodMap;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Delete Controller
 */
class Delete extends AbstractAction implements HttpPostActionInterface
{

    /**
     * Execute delete shipping map action
     *
     * @return ResultInterface
     * @throws \Exception
     */
    public function execute(): ResultInterface
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $mapId = (int)$this->getRequest()->getParam(MethodMap::MAP_ID);
        if ($mapId === null) {
            $this->messageManager->addErrorMessage(__('Wrong request.'));
            return $resultRedirect->setPath('*/*');
        }

        try {
            $mapId = (int)$mapId;
            $this->methodMapResourceModel->delete($this->getMethodMap($mapId));
            $this->messageManager->addSuccessMessage(__('The Shipping Methods map has been deleted.'));
            $resultRedirect->setPath('*/*');
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect->setPath('*/*/edit', [
                MethodMap::MAP_ID => $mapId,
                '_current' => true,
            ]);
        }

        return $resultRedirect;
    }
}
