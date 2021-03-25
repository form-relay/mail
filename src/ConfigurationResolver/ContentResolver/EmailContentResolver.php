<?php

namespace FormRelay\Mail\ConfigurationResolver\ContentResolver;

use FormRelay\Core\ConfigurationResolver\ContentResolver\ContentResolver;
use FormRelay\Mail\Model\Form\EmailField;

class EmailContentResolver extends ContentResolver
{
    const KEY_ADDRESS = 'address';
    const KEY_NAME = 'name';

    public function build()
    {
        $config = is_array($this->config) ? $this->config : [static::KEY_ADDRESS => $this->config];
        $name = isset($config[static::KEY_NAME]) ? trim($this->resolveContent($config[static::KEY_NAME])) : '';
        $address = isset($config[static::KEY_ADDRESS]) ? trim($this->resolveContent($config[static::KEY_ADDRESS])) : '';
        if (!$address) {
            return null;
        }
        return new EmailField($address, $name);
    }
}
