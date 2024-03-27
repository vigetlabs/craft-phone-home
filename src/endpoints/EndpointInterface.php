<?php

namespace viget\phonehome\endpoints;

use viget\phonehome\models\SitePayload;

interface EndpointInterface
{
    /**
     * Send a SitePayload to the API endpoint
     * @param SitePayload $payload
     * @return void
     */
    public function send(SitePayload $payload): void;
}