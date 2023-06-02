<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Map;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * NewAction Controller
 */
class NewAction extends Action implements HttpGetActionInterface
{

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DeckCommerce_Integration::deck_shipping';

    /**
     * Execute new shipping map action
     */
    public function execute(): ResultInterface
    {
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('DeckCommerce_Integration::deck_shipping_map');
        $resultPage->getConfig()->getTitle()->prepend(__('New Magento to Deck Commerce Mapping'));

        return $resultPage;
    }
}
