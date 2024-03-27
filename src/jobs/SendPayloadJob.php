<?php

namespace viget\phonehome\jobs;

use craft\queue\BaseJob;
use viget\phonehome\PhoneHome;

/**
 * Send Payload Job queue job
 */
class SendPayloadJob extends BaseJob
{
    function execute($queue): void
    {
        PhoneHome::getInstance()->phoneHome->sendPayload();
    }

    protected function defaultDescription(): ?string
    {
        return 'Phoning Home';
    }
}
