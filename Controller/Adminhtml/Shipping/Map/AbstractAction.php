<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

declare(strict_types=1);

namespace DeckCommerce\Integration\Controller\Adminhtml\Shipping\Map;

use DeckCommerce\Integration\Helper\Data as DeckHelper;
use DeckCommerce\Integration\Model\MethodMap;
use DeckCommerce\Integration\Model\MethodMapFactory;
use DeckCommerce\Integration\Model\ResourceModel\MethodMap as MethodMapResource;
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

    const FORM_SOURCE_KEY = 'map';

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * @var MethodMapFactory
     */
    protected $methodMapFactory;

    /**
     * @var MethodMapResource
     */
    protected $methodMapResourceModel;

    /**
     * AbstractAction constructor.
     * @param Context $context
     * @param DeckHelper $helper
     * @param MethodMapFactory $methodMapFactory
     * @param MethodMapResource $methodMapResourceModel
     */
    public function __construct(
        Context $context,
        DeckHelper $helper,
        MethodMapFactory $methodMapFactory,
        MethodMapResource $methodMapResourceModel
    ) {
        parent::__construct($context);
        $this->helper = $helper;
        $this->methodMapFactory = $methodMapFactory;
        $this->methodMapResourceModel = $methodMapResourceModel;
    }

    /**
     * Get shipping method map by mapId
     *
     * @param int $mapId
     * @return MethodMap
     * @throws NoSuchEntityException
     */
    public function getMethodMap($mapId)
    {
        $methodMap = $this->methodMapFactory->create();

        $this->methodMapResourceModel->load(
            $methodMap,
            $mapId
        );

        if (!$methodMap->getMapId()) {
            throw new NoSuchEntityException();
        }

        return $methodMap;
    }
}
