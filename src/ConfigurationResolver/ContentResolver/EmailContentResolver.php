<?php

namespace FormRelay\Mail\ConfigurationResolver\ContentResolver;

use FormRelay\Core\ConfigurationResolver\ContentResolver\ContentResolver;
use FormRelay\Mail\Model\Form\EmailField;

class EmailContentResolver extends ContentResolver
{
    const KEY_ADDRESS = 'address';
    const DEFAULT_ADDRESS = '';

    const KEY_NAME = 'name';
    const DEFAULT_NAME = '';

    public function build()
    {
        if (!is_array($this->configuration)) {
            $this->configuration = [static::KEY_ADDRESS => $this->configuration];
        }

        $name = trim($this->resolveContent($this->getConfig(static::KEY_NAME)));
        $address = trim($this->resolveContent($this->getConfig(static::KEY_ADDRESS)));

        if (!$address) {
            return null;
        }

        return new EmailField($address, $name);
    }

    public static function getDefaultConfiguration(): array
    {
        return parent::getDefaultConfiguration() + [
            static::KEY_ADDRESS => static::DEFAULT_ADDRESS,
            static::KEY_NAME => static::DEFAULT_NAME,
        ];
    }
}
