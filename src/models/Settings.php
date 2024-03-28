<?php

namespace viget\phonehome\models;

use craft\base\Model;
use viget\phonehome\endpoints\EndpointInterface;

/**
 * Phone Home settings
 */
class Settings extends Model
{
    /**
     * When $enabled is null, it's in "Auto" mode. Which means that phone home
     * will be disabled in `devMode` but enabled in all other environments.
     * @var bool|null
     */
    public ?bool $enabled = null;

    /** @var EndpointInterface[] */
    public array $endpoints = [];

}
