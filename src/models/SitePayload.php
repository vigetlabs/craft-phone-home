<?php

namespace viget\phonehome\models;

use Craft;
use craft\base\PluginInterface;
use craft\helpers\App;
use craft\models\Site;
use Illuminate\Support\Collection;
use yii\base\Module;

final class SitePayload
{

    public function __construct(
        public readonly string $siteUrl,
        public readonly string $siteName,
        public readonly string $environment,
        /** @var string $craftEdition - Solo, Team, Pro, etc */
        public readonly string $craftEdition,
        /** @var string The version number */
        public readonly string $craftVersion,
        public readonly string $phpVersion,
        public readonly string $dbVersion,
        /** @var Collection<int,SitePayloadPlugin> $plugins */
        public readonly Collection $plugins,
        /** @var Collection<int,string> $modules */
        public readonly Collection $modules
    )
    {
    }

    public static function fromSite(Site $site): self
    {
        $siteUrl = $site->getBaseUrl();
        $environment = Craft::$app->env;

        if (!$siteUrl || !$environment) {
           throw new \Exception('$siteUrl or $environment not found');
        }

        return new self(
            siteUrl: $siteUrl,
            siteName: $site->name,
            environment: $environment,
            craftEdition: App::editionName(Craft::$app->getEdition()),
            craftVersion: App::normalizeVersion(Craft::$app->getVersion()),
            phpVersion: App::phpVersion(),
            dbVersion: self::_dbDriver(),
            plugins: Collection::make(Craft::$app->plugins->getAllPlugins())
                ->map(SitePayloadPlugin::fromPluginInterface(...))
                ->values(),
            modules: self::_modules()
        );
    }

    /**
     * Returns the DB driver name and version
     *
     * @return string
     */
    private static function _dbDriver(): string
    {
        $db = Craft::$app->getDb();

        if ($db->getIsMysql()) {
            $driverName = 'MySQL';
        } else {
            $driverName = 'PostgreSQL';
        }

        return $driverName . ' ' . App::normalizeVersion($db->getSchema()->getServerVersion());
    }

    /**
     * Returns the list of modules
     *
     * @return Collection<int,string>
     */
    private static function _modules(): Collection
    {
        $modules = [];

        foreach (Craft::$app->getModules() as $id => $module) {
            if ($module instanceof PluginInterface) {
                continue;
            }

            if ($module instanceof Module) {
                $modules[$id] = get_class($module);
            } else if (is_string($module)) {
                $modules[$id] = $module;
            } else if (is_array($module) && isset($module['class'])) {
                $modules[$id] = $module['class'];
            }
        }

        // ->values() forces a 0 indexed array
        return Collection::make($modules)->values();
    }

}