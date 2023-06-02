<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Map;

use DeckCommerce\Integration\Model\MethodMap;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Edit Controller
 */
class Edit extends AbstractAction implements HttpGetActionInterface
{

    /**
     * Execute edit shipping map action
     */
    public function execute(): ResultInterface
    {
        $mapId = (int)$this->getRequest()->getParam(MethodMap::MAP_ID);
        try {
            $methodMap = $this->getMethodMap($mapId);
            $method = $methodMap->getMethod();
            list($carrierCode, $methodCode) = explode('_', $method, 2);

            $fullMethodTitle = $this->helper->getMethodCarrierAndTitleByCodes($carrierCode, $methodCode);

            /** @var Page $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $result->setActiveMenu('DeckCommerce_Integration::deck_shipping_map')
                ->addBreadcrumb(__('Edit Shipping Methods map'), __('Edit Shipping Methods Map'));
            $result->getConfig()
                ->getTitle()
                ->prepend(__('Edit Shipping Methods Map: %name', ['name' => $fullMethodTitle]));
        } catch (NoSuchEntityException $e) {
            /** @var Redirect $result */
            $result = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(
                __('Deck Commerce Shipping Method with id "%value" does not exist.', ['value' => $methodMap->getId()])
            );
            $result->setPath('*/*');
        }

        return $result;
    }
}
