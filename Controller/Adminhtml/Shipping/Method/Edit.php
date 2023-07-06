<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Method;

use DeckCommerce\Integration\Model\DeckMethod;
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
     * Execute edit Deck Commerce shipping action
     */
    public function execute(): ResultInterface
    {

        $deckMethodId = (int)$this->getRequest()->getParam(DeckMethod::DECK_METHOD_ID);
        try {
            $deckMethod = $this->getDeckMethod($deckMethodId);

            /** @var Page $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
            $result->setActiveMenu('DeckCommerce_Integration::deck_shipping_method')
                ->addBreadcrumb(__('Edit Deck Commerce Shipping Method'), __('Edit Deck Commerce Shipping Method'));
            $result->getConfig()
                ->getTitle()
                ->prepend(__('Edit Deck Commerce Shipping Method: %name', [
                    'name' => $deckMethod->getDeckMethodName()
                ]));
        } catch (NoSuchEntityException $e) {
            /** @var Redirect $result */
            $result = $this->resultRedirectFactory->create();
            $this->messageManager->addErrorMessage(
                __('Deck Commerce Shipping Method with id "%value" does not exist.', [
                    'value' => $deckMethod->getDeckMethodId()
                ])
            );
            $result->setPath('*/*');
        }

        return $result;
    }
}
