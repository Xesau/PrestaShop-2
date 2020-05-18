<?php

namespace _PhpScoper5ea00cc67502b\Mollie\Api\Endpoints;

use _PhpScoper5ea00cc67502b\Mollie\Api\Resources\Chargeback;
use _PhpScoper5ea00cc67502b\Mollie\Api\Resources\ChargebackCollection;
use _PhpScoper5ea00cc67502b\Mollie\Api\Resources\Payment;
use Mollie\Api\Exceptions\ApiException;
use stdClass;

class PaymentChargebackEndpoint extends CollectionEndpointAbstract
{
    protected $resourcePath = "payments_chargebacks";
    /**
     * Get the object that is used by this API endpoint. Every API endpoint uses one type of object.
     *
     * @return Chargeback
     */
    protected function getResourceObject()
    {
        return new Chargeback($this->client);
    }
    /**
     * Get the collection object that is used by this API endpoint. Every API endpoint uses one type of collection object.
     *
     * @param int $count
     * @param stdClass $_links
     *
     * @return ChargebackCollection
     */
    protected function getResourceCollectionObject($count, $_links)
    {
        return new ChargebackCollection($this->client, $count, $_links);
    }
    /**
     * @param Payment $payment
     * @param string $chargebackId
     * @param array $parameters
     *
     * @return Chargeback
     * @throws ApiException
     */
    public function getFor(Payment $payment, $chargebackId, array $parameters = [])
    {
        return $this->getForId($payment->id, $chargebackId, $parameters);
    }
    /**
     * @param string $paymentId
     * @param string $chargebackId
     * @param array $parameters
     *
     * @return Chargeback
     * @throws ApiException
     */
    public function getForId($paymentId, $chargebackId, array $parameters = [])
    {
        $this->parentId = $paymentId;
        return parent::rest_read($chargebackId, $parameters);
    }
}
