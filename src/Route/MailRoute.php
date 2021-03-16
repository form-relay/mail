<?php

namespace FormRelay\Mail\Route;

use FormRelay\Mail\DataDispatcher\MailDataDispatcher;

class MailRoute extends AbstractMailRoute
{
    const KEY_VALUE_DELIMITER = 'valueDelimiter';
    const DEFAULT_VALUE_DELIMITER = '\s=\s';

    const KEY_LINE_DELIMITER = 'lineDelimiter';
    const DEFAULT_LINE_DELIMITER = '\n';

    protected function getDispatcher()
    {
        /** @var MailDataDispatcher $dispatcher */
        $dispatcher = parent::getDispatcher();
        $dispatcher->setValueDelimiter($this->getConfig(static::KEY_VALUE_DELIMITER));
        $dispatcher->setLineDelimiter($this->getConfig(static::KEY_LINE_DELIMITER));
        return $dispatcher;
    }

    public static function getDefaultConfiguration(): array
    {
        return parent::getDefaultConfiguration() + [
            static::KEY_VALUE_DELIMITER => static::DEFAULT_VALUE_DELIMITER,
            static::KEY_LINE_DELIMITER => static::DEFAULT_LINE_DELIMITER,
        ];
    }
}
