<?php
/**
 * @author DeckCommerce Team
 * @copyright Copyright (c) 2023 DeckCommerce (https://www.deckcommerce.com)
 * @package DeckCommerce_Integration
 */

namespace DeckCommerce\Integration\Model\Service\Request;

use DeckCommerce\Integration\Helper\Data as DeckHelper;

/**
 * OrderBuilder model
 */
class RmaBuilder implements RmaBuilderInterface
{

    /**
     * @var DeckHelper
     */
    protected $helper;

    /**
     * OrderBuilder constructor.
     *
     * @param DeckHelper $helper
     */
    public function __construct(
        DeckHelper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Build data for RMA export
     *
     * @param string $orderId
     * @param string $rmaNumber
     * @param array $rmaItems
     * @return array
     */
    public function build($orderId, $rmaNumber, $rmaItems)
    {
        $items = [];
        foreach ($rmaItems as $item) {
            $items[] = [
                "SKU"                       => $item['sku'],
                "ReturnReasonID"            => $item['reason'],
                "ReturnReasonText"          => $item['reason_text'],
                "ReturnTypeID"              => $this->helper->getDefaultRmaType(),
                "Quantity"                  => 1
            ];
        }

        $result = [
            "RmaNumber" => $rmaNumber,
            "Orders"    => [[
                "OrderNumber" => $orderId,
                "Items"       => $items
            ]]
        ];

        return $result;
    }
}
