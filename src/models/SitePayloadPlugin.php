<?php

namespace viget\phonehome\models;

use craft\base\PluginInterface;

/**
 * DTO for transferring plugin info to our Endpoints
 */
class SitePayloadPlugin
{
    public function __construct(
        public readonly string $id,
        public readonly string $versionedId,
    )
    {
    }

    public static function fromPluginInterface(PluginInterface $pluginInterface): self
    {
        return new self(
            id: $pluginInterface->id,
            versionedId: $pluginInterface->id . ':' . $pluginInterface->version,
        );
    }
}