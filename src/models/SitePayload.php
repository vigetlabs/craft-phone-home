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
        public readonly string $craftVersion,
        public readonly string $phpVersion,
        public readonly string $dbVersion,
        /** @var Collection<SitePayloadPlugin> $plugins */
        public readonly Collection $plugins,
        public readonly string $modules
    )
    {
    }

    public static function fromSite(Site $site): self
    {
        return new self(
            siteUrl: $site->getBaseUrl(),
            siteName: $site->name,
            environment: Craft::$app->env,
            craftVersion: App::editionName(Craft::$app->getEdition()),
            phpVersion: App::phpVersion(),
            dbVersion: self::_dbDriver(),
            plugins: Collection::make(Craft::$app->plugins->getAllPlugins())
                ->map(SitePayloadPlugin::fromPluginInterface(...)),
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
     * @return string
     */
    private static function _modules(): string
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

        return implode(PHP_EOL, $modules);
    }

}