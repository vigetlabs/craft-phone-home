<?php

namespace viget\phonehome;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use viget\phonehome\models\Settings;
use viget\phonehome\services\PhoneHomeService;

/**
 * Phone Home plugin
 *
 * @method static PhoneHome getInstance()
 * @method Settings getSettings()
 * @author Viget
 * @copyright Viget
 * @license MIT
 * @property-read PhoneHomeService $phoneHome
 */
class PhoneHome extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    /**
     * @phpstan-ignore-next-line
     */
    public static function config(): array
    {
        return [
            'components' => ['phoneHome' => PhoneHomeService::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        // If enabled hasn't been configured, enable for non-devMode environments
        $enabled = $this->getSettings()->enabled ?? Craft::$app->getConfig()->getGeneral()->devMode === false;

        $this->phoneHome->sendPayload();

        if (!$enabled) {
            return;
        }

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->phoneHome->tryQueuePhoneHome();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('phone-home/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }
}
