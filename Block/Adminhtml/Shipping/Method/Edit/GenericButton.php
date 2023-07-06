<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Shipping\Method\Edit;

use DeckCommerce\Integration\Model\DeckMethodFactory;
use DeckCommerce\Integration\Model\ResourceModel\DeckMethod;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class for common code for buttons on the create/edit form
 */
class GenericButton
{
    /**
     * @var DeckMethodFactory
     */
    private $deckMethodFactory;

    /**
     * @var DeckMethod
     */
    private $deckMethodResourceModel;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * GenericButton constructor.
     * @param UrlInterface $urlBuilder
     * @param RequestInterface $request
     * @param DeckMethodFactory $deckMethodFactory
     * @param DeckMethod $deckMethodResourceModel
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        DeckMethodFactory $deckMethodFactory,
        DeckMethod $deckMethodResourceModel
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->deckMethodFactory = $deckMethodFactory;
        $this->deckMethodResourceModel = $deckMethodResourceModel;
    }

    /**
     * Return deck method id
     *
     * @return int|null
     */
    public function getDeckMethodId()
    {
        $deckMethod = $this->deckMethodFactory->create();

        $this->deckMethodResourceModel->load(
            $deckMethod,
            $this->request->getParam('deck_method_id')
        );

        return $deckMethod->getDeckMethodId() ?: null;
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->urlBuilder->getUrl($route, $params);
    }
}
