<?php

namespace viget\phonehome\services;

use Craft;
use craft\helpers\Queue;
use viget\phonehome\endpoints\EndpointInterface;
use viget\phonehome\endpoints\NotionEndpoint;
use viget\phonehome\jobs\SendPayloadJob;
use viget\phonehome\models\SitePayload;
use viget\phonehome\PhoneHome;
use yii\base\Component;

/**
 * Phone Home service
 */
class PhoneHomeService extends Component
{

    private const CACHE_KEY = 'phone-home-payload-sent';

    private const DAY_IN_SECONDS = 86400;

    public function tryQueuePhoneHome(): void
    {
        $request = Craft::$app->getRequest();

        if (
            Craft::$app->getIsInstalled() === false
            || Craft::$app->getRequest()->getIsConsoleRequest()
            || !Craft::$app->getRequest()->getIsCpRequest() // Only run on CP request
            || $request->getIsAjax()
        ) {
            return;
        }

        // Only run when the cache is empty (once per day at most)
        if (Craft::$app->getCache()->get(self::CACHE_KEY) !== false) {
            return;
        }

        Queue::push(new SendPayloadJob());

        Craft::$app->getCache()->set(self::CACHE_KEY, true, self::DAY_IN_SECONDS);
    }

    public function sendPayload(): void
    {
        $settings = PhoneHome::getInstance()->getSettings();

        /** @var EndpointInterface[] $endpoints */
        $endpoints = [];

        // Lays the foundation for being able to configure multiple endpoints
        if ($settings->getNotion()) {
            $endpoints[] = new NotionEndpoint($settings->getNotion());
        }

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $payload = SitePayload::fromSite($site);
            foreach ($endpoints as $endpoint) {
                $endpoint->send($payload);
            }
        }
    }
}
