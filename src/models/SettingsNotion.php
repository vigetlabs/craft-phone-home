<?php

namespace viget\phonehome\models;

class SettingsNotion
{
    public function __construct(
        public string $secretKey,
        public string $databaseId,
    )
    {
    }
}