<?php

namespace _PhpScoper5eddef0da618a\Mollie\Api\Resources;

class CustomerCollection extends \_PhpScoper5eddef0da618a\Mollie\Api\Resources\CursorCollection
{
    /**
     * @return string
     */
    public function getCollectionResourceName()
    {
        return "customers";
    }
    /**
     * @return BaseResource
     */
    protected function createResourceObject()
    {
        return new \_PhpScoper5eddef0da618a\Mollie\Api\Resources\Customer($this->client);
    }
}
