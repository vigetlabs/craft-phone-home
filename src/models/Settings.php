<?php

namespace viget\phonehome\models;

use craft\base\Model;

/**
 * Phone Home settings
 */
class Settings extends Model
{
    public ?bool $enabled = null;

    private ?SettingsNotion $_notion = null;

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'notion', // Notion is a getter/setter. Make Yii aware of it
        ];
    }

    public function getNotion(): ?SettingsNotion
    {
        return $this->_notion;
    }

    public function setNotion(array $settings): self
   {
        $secretKey = $settings['secretKey'] ?? null;
        $databaseId = $settings['databaseId'] ?? null;

        if (!$secretKey || !$databaseId) {
            return $this;
        }

        $this->_notion = new SettingsNotion(
            secretKey: $secretKey,
            databaseId: $databaseId,
        );

        return $this;
    }
}
