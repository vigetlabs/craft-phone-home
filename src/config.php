<?php
/**
 * Phone Home config.php
 *
 * This file exists only as a template for Phone Home settings.
 *
 * Copy this file to the 'config' directory as 'phone-home.php'
 * and make your changes there to override default settings.
 */

return [
    // Whether phone home is enabled. When set to null (default), the plugin
    // will phone home when Craft is not in devMode
    // 'enabled' => null,

    // An array of classes that implement the \viget\phonehome\endpoints\EndpointInterface
    // You may configure as many endpoints as you would like... even of the same class
    // 'endpoints' => [
    //     new \viget\phonehome\endpoints\NotionEndpoint(
    //         secret: \craft\helpers\App::env('PHONE_HOME_NOTION_SECRET'),
    //         databaseId: \craft\helpers\App::env('PHONE_HOME_NOTION_DATABASE'),
    //     ),
    // ],
];