<?php

namespace viget\phonehome\endpoints;

use DateTimeImmutable;
use DateTimeZone;
use Notion\Databases\Query;
use Notion\Notion;
use Notion\Pages\Page;
use Notion\Pages\PageParent;
use Notion\Pages\Properties\Date;
use Notion\Pages\Properties\MultiSelect;
use Notion\Pages\Properties\RichTextProperty;
use Notion\Pages\Properties\Select;
use Notion\Pages\Properties\Title;
use Notion\Pages\Properties\Url;
use viget\phonehome\models\SitePayload;
use viget\phonehome\models\SitePayloadPlugin;

class NotionEndpoint implements EndpointInterface
{
    private const PROPERTY_URL = "Url";
    private const PROPERTY_ENVIRONMENT = "Environment";
    private const PROPERTY_CRAFT_VERSION = "Craft Version";
    private const PROPERTY_PHP_VERSION = "PHP Version";
    private const PROPERTY_DB_VERSION = "DB Version";
    private const PROPERTY_PLUGINS = "Plugins";
    private const PROPERTY_PLUGIN_VERSIONS = "Plugin Versions";
    private const PROPERTY_MODULES = "Modules";
    private const PROPERTY_DATE_UPDATED = "Date Updated";
    private const PROPERTY_NAME = "Name";

    public function __construct(
        private readonly string $secret,
        private readonly string $databaseId,
    )
    {
    }

    public function send(SitePayload $payload): void
    {
        $notion = Notion::create($this->secret);
        $database = $notion->databases()->find($this->databaseId);
        $existingProperties = $database->properties()->getAll();

        // Checks if a property exists
        $hasProperty = function (string $handle) use ($existingProperties): bool {
            return !empty($existingProperties[$handle]);
        };

        $shouldUpdate = false;

        // Make sure properties are present on page
        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\Url::create(self::PROPERTY_URL));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_ENVIRONMENT));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_CRAFT_VERSION));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_PHP_VERSION));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_DB_VERSION));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\MultiSelect::create(self::PROPERTY_PLUGINS));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\MultiSelect::create(self::PROPERTY_PLUGIN_VERSIONS));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\RichTextProperty::create(self::PROPERTY_MODULES));
            $shouldUpdate = true;
        }

        if (!$hasProperty(self::PROPERTY_URL)) {
            $database = $database->addProperty(\Notion\Databases\Properties\Date::create(self::PROPERTY_DATE_UPDATED));
            $shouldUpdate = true;
        }

        // Only update if properties have changed
        if ($shouldUpdate) {
            $notion->databases()->update($database);
        }

        $query = Query::create()
            ->changeFilter(
                Query\TextFilter::property(self::PROPERTY_URL)->equals($payload->siteUrl),
            )
            ->changePageSize(1);

        $result = $notion->databases()->query($database, $query);
        $page = $result->pages[0] ?? null;
        $isCreate = !$page;

        $parent = PageParent::database($database->id);
        $page = $page ?? Page::create($parent);

        // Update properties

        $plugins = $payload->plugins->map(fn(SitePayloadPlugin $plugin) => $plugin->id)->values()->all();
        $pluginVersions = $payload->plugins->map(fn(SitePayloadPlugin $plugin) => $plugin->versionedId)->values()->all();

        $page = $page->addProperty(self::PROPERTY_NAME, Title::fromString($payload->siteName))
            ->addProperty(self::PROPERTY_URL, Url::create($payload->siteUrl))
            ->addProperty(self::PROPERTY_ENVIRONMENT, Select::fromName($payload->environment))
            ->addProperty(self::PROPERTY_CRAFT_VERSION, Select::fromName($payload->craftVersion))
            ->addProperty(self::PROPERTY_PHP_VERSION, Select::fromName($payload->phpVersion))
            ->addProperty(self::PROPERTY_DB_VERSION, Select::fromName($payload->dbVersion))
            ->addProperty(self::PROPERTY_PLUGINS, MultiSelect::fromNames(...$plugins))
            ->addProperty(self::PROPERTY_PLUGIN_VERSIONS, MultiSelect::fromNames(...$pluginVersions))
            ->addProperty(self::PROPERTY_MODULES, RichTextProperty::fromString($payload->modules))
            ->addProperty(self::PROPERTY_DATE_UPDATED, Date::create(new DateTimeImmutable('now', new DateTimeZone('UTC'))));

        if ($isCreate) {
            $notion->pages()->create($page);
        } else {
            $notion->pages()->update($page);
        }
    }
}