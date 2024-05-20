<?php

namespace viget\phonehome\endpoints;

use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Notion\Databases\Database;
use Notion\Databases\Properties\Date as DateDb;
use Notion\Databases\Properties\MultiSelect as MultiSelectDb;
use Notion\Databases\Properties\PropertyInterface;
use Notion\Databases\Properties\RichTextProperty as RichTextDb;
use Notion\Databases\Properties\Select as SelectDb;
use Notion\Databases\Properties\Url as UrlDb;
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

    /**
     * @var array<string,array{
     *   class: class-string<PropertyInterface>
     * }>
     */
    private const PROPERTY_CONFIG = [
        self::PROPERTY_URL => [
            'class' => UrlDb::class,
        ],
        self::PROPERTY_ENVIRONMENT => [
            'class' => SelectDb::class,
        ],
        self::PROPERTY_CRAFT_VERSION => [
            'class' => SelectDb::class,
        ],
        self::PROPERTY_PHP_VERSION => [
            'class' => SelectDb::class,
        ],
        self::PROPERTY_DB_VERSION => [
            'class' => SelectDb::class,
        ],
        self::PROPERTY_PLUGINS => [
            'class' => MultiSelectDb::class,
        ],
        self::PROPERTY_PLUGIN_VERSIONS => [
            'class' => MultiSelectDb::class,
        ],
        self::PROPERTY_MODULES => [
            'class' => RichTextDb::class,
        ],
        self::PROPERTY_DATE_UPDATED => [
            'class' => DateDb::class,
        ],
    ];

    public function __construct(
        private readonly string $secret,
        private readonly string $databaseId,
    )
    {
    }

    /**
     * @param string $propertyName
     * @param class-string<PropertyInterface> $propertyClass
     * @param Database $database Pass by reference because there's some immutable stuff going on in the Notion lib
     * @return bool True if property was created
     * @throws Exception
     */
    private function createProperty(string $propertyName, string $propertyClass, Database &$database): bool
    {
        $existingProperties = $database->properties()->getAll();

        // Don't create a property if it already exists
        if (!empty($existingProperties[$propertyName])) {
            return false;
        }

        // If you're using a class that isn't in this list, most likely the ::create
        // method is compatible. But it's worth double-checking.
        $database = match ($propertyClass) {
            UrlDb::class,
            SelectDb::class,
            MultiSelectDb::class,
            RichTextDb::class,
            DateDb::class => $database->addProperty($propertyClass::create($propertyName)),
            default => throw new Exception("createProperty doesnt support the class $propertyClass. Double check that its ::create method is compatible and add to this method")
        };

        return true;
    }

    /**
     * @throws Exception
     */
    public function send(SitePayload $payload): void
    {
        $notion = Notion::create($this->secret);
        $database = $notion->databases()->find($this->databaseId);

        // Loop through property config and create properties that don't exist on the DB
        $updated = false;
        foreach (self::PROPERTY_CONFIG as $propertyName => $config) {
            $didUpdate = $this->createProperty(
                $propertyName,
                $config['class'],
                $database
            );

            // Always stay true if one property updated
            if ($didUpdate === true) {
                $updated = true;
            }
        }

        // Only update if properties have changed
        if ($updated) {
            $notion->databases()->update($database);
        }

        // Find existing DB record for site
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