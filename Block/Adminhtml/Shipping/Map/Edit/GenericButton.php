<?php

/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Block\Adminhtml\Shipping\Map\Edit;

use DeckCommerce\Integration\Model\MethodMapFactory;
use DeckCommerce\Integration\Model\ResourceModel\MethodMap;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class for common code for buttons on the create/edit form
 */
class GenericButton
{
    /**
     * @var MethodMapFactory
     */
    private $methodMapFactory;

    /**
     * @var MethodMap
     */
    private $methodMapResourceModel;

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
     * @param MethodMapFactory $methodMapFactory
     * @param MethodMap $methodMapResourceModel
     */
    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        MethodMapFactory $methodMapFactory,
        MethodMap $methodMapResourceModel
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->methodMapFactory = $methodMapFactory;
        $this->methodMapResourceModel = $methodMapResourceModel;
    }

    /**
     * Return get shipping methods map Id
     *
     * @return int|null
     */
    public function getMethodsMapId()
    {
        $methodMap = $this->methodMapFactory->create();

        $this->methodMapResourceModel->load(
            $methodMap,
            $this->request->getParam('map_id')
        );

        return $methodMap->getMapId() ?: null;
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
