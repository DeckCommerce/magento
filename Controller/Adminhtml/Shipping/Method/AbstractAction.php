<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Method;

use DeckCommerce\Integration\Model\DeckMethod;
use DeckCommerce\Integration\Model\DeckMethodFactory;
use DeckCommerce\Integration\Model\ResourceModel\DeckMethod as DeckMethodResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Abstract class for controllers
 */
abstract class AbstractAction extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'DeckCommerce_Integration::deck_shipping';

    const FORM_SOURCE_KEY = 'method';

    /**
     * @var DeckMethodFactory
     */
    protected $deckMethodFactory;

    /**
     * @var DeckMethodResource
     */
    protected $deckMethodResourceModel;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param DeckMethodFactory $deckMethodFactory
     * @param DeckMethodResource $deckMethodResourceModel
     */
    public function __construct(
        Context $context,
        DeckMethodFactory $deckMethodFactory,
        DeckMethodResource $deckMethodResourceModel
    ) {
        parent::__construct($context);
        $this->deckMethodFactory       = $deckMethodFactory;
        $this->deckMethodResourceModel = $deckMethodResourceModel;
    }

    /**
     * Get Deck Commerce shipping method by id
     *
     * @param int $deckMethodId
     * @return DeckMethod
     * @throws NoSuchEntityException
     */
    public function getDeckMethod($deckMethodId)
    {
        $deckMethod = $this->deckMethodFactory->create();

        $this->deckMethodResourceModel->load(
            $deckMethod,
            $deckMethodId
        );

        if (!$deckMethod->getId()) {
            throw new NoSuchEntityException();
        }

        return $deckMethod;
    }
}
