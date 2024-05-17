<?php

namespace viget\phonehome\endpoints;

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

        // Make sure properties are present on page
        // TODO only run if needed
        $database = $database
            ->addProperty(\Notion\Databases\Properties\Url::create(self::PROPERTY_URL))
            ->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_ENVIRONMENT))
            ->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_CRAFT_VERSION))
            ->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_PHP_VERSION))
            ->addProperty(\Notion\Databases\Properties\Select::create(self::PROPERTY_DB_VERSION))
            ->addProperty(\Notion\Databases\Properties\MultiSelect::create(self::PROPERTY_PLUGINS))
            ->addProperty(\Notion\Databases\Properties\MultiSelect::create(self::PROPERTY_PLUGIN_VERSIONS))
            ->addProperty(\Notion\Databases\Properties\RichTextProperty::create(self::PROPERTY_MODULES))
            ->addProperty(\Notion\Databases\Properties\Date::create(self::PROPERTY_DATE_UPDATED))
        ;

        $notion->databases()->update($database);

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
            ->addProperty(self::PROPERTY_DATE_UPDATED, Date::create(new \DateTimeImmutable('now', new \DateTimeZone('UTC'))))
        ;

        if ($isCreate) {
            $notion->pages()->create($page);
        } else {
            $notion->pages()->update($page);
        }
    }
}