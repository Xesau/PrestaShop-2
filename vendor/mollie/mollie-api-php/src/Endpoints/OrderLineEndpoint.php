<?php

namespace MolliePrefix\Mollie\Api\Endpoints;

use MolliePrefix\Mollie\Api\Exceptions\ApiException;
use MolliePrefix\Mollie\Api\Resources\Order;
use MolliePrefix\Mollie\Api\Resources\OrderLine;
use MolliePrefix\Mollie\Api\Resources\OrderLineCollection;
use MolliePrefix\Mollie\Api\Resources\ResourceFactory;
class OrderLineEndpoint extends \MolliePrefix\Mollie\Api\Endpoints\CollectionEndpointAbstract
{
    protected $resourcePath = "orders_lines";
    /**
     * @var string
     */
    const RESOURCE_ID_PREFIX = 'odl_';
    /**
     * Get the object that is used by this API endpoint. Every API endpoint uses one
     * type of object.
     *
     * @return OrderLine
     */
    protected function getResourceObject()
    {
        return new \MolliePrefix\Mollie\Api\Resources\OrderLine($this->client);
    }
    /**
     * Get the collection object that is used by this API endpoint. Every API
     * endpoint uses one type of collection object.
     *
     * @param int $count
     * @param \stdClass $_links
     *
     * @return OrderLineCollection
     */
    protected function getResourceCollectionObject($count, $_links)
    {
        return new \MolliePrefix\Mollie\Api\Resources\OrderLineCollection($count, $_links);
    }
    /**
     * Cancel lines for the provided order.
     * The data array must contain a lines array.
     * You can pass an empty lines array if you want to cancel all eligible lines.
     * Returns null if successful.
     *
     * @param Order $order
     * @param array $data
     *
     * @return null
     * @throws ApiException
     */
    public function cancelFor(\MolliePrefix\Mollie\Api\Resources\Order $order, array $data)
    {
        return $this->cancelForId($order->id, $data);
    }
    /**
     * Cancel lines for the provided order id.
     * The data array must contain a lines array.
     * You can pass an empty lines array if you want to cancel all eligible lines.
     * Returns null if successful.
     *
     * @param string $orderId
     * @param array $data
     *
     * @return null
     * @throws ApiException
     */
    public function cancelForId($orderId, array $data)
    {
        if (!isset($data['lines']) || !\is_array($data['lines'])) {
            throw new \MolliePrefix\Mollie\Api\Exceptions\ApiException("A lines array is required.");
        }
        $this->parentId = $orderId;
        $this->client->performHttpCall(self::REST_DELETE, "{$this->getResourcePath()}", $this->parseRequestBody($data));
        return null;
    }
}
