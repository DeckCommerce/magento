<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * DownloadFileAction column
 */
class DownloadFileAction extends Column
{

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /** Url Path */
    const DOWNLOAD_FILE_URL = 'deck/inventory_log/downloadFile';

    /**
     * DownloadFileAction constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item[$name]) && $item[$name]) {
                    $url = $this->urlBuilder->getUrl(self::DOWNLOAD_FILE_URL, ['id' => $item['id']]);
                    // phpcs:ignore Magento2.Functions.DiscouragedFunction
                    $item[$name] = html_entity_decode('<a href="' . $url . '">' . $item[$name] . '</a>');
                }
            }
        }
        return $dataSource;
    }
}
