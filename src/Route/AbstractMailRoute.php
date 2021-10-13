<?php

namespace FormRelay\Mail\Route;

use FormRelay\Core\Route\Route;
use FormRelay\Mail\DataDispatcher\AbstractMailDataDispatcher;

abstract class AbstractMailRoute extends Route
{
    const DATA_DISPATCHER_KEYWORD = 'mail';

    const DEFAULT_PASSTHROUGH_FIELDS = true;

    const KEY_FROM = 'sender';
    const DEFAULT_FROM = '';

    const KEY_TO = 'recipients';
    const DEFAULT_TO = '';

    const KEY_REPLY_TO = 'replyTo';
    const DEFAULT_REPLY_TO = '';

    const KEY_SUBJECT = 'subject';
    const DEFAULT_SUBJECT = 'New Form Submission';

    const KEY_ATTACH_UPLOADED_FILES = 'includeAttachmentsInMail';
    const DEFAULT_ATTACH_UPLOADED_FILES = false;

    protected function getDispatcher()
    {
        /** @var AbstractMailDataDispatcher $dispatcher */
        $dispatcher = $this->registry->getDataDispatcher(static::DATA_DISPATCHER_KEYWORD);

        $from = $this->resolveContent($this->getConfig(static::KEY_FROM));
        $dispatcher->setFrom($from);

        $to = $this->resolveContent($this->getConfig(static::KEY_TO));
        $dispatcher->setTo($to);

        $replyTo = $this->resolveContent($this->getConfig(static::KEY_REPLY_TO));
        $dispatcher->setReplyTo($replyTo);

        $subject = $this->resolveContent($this->getConfig(static::KEY_SUBJECT));
        $dispatcher->setSubject($subject);

        $attachUploadedFiles = $this->resolveContent($this->getConfig(static::KEY_ATTACH_UPLOADED_FILES));
        $dispatcher->setAttachUploadedFiles($attachUploadedFiles);

        return $dispatcher;
    }

    public static function getDefaultConfiguration(): array
    {
        return parent::getDefaultConfiguration() + [
                static::KEY_FROM => static::DEFAULT_FROM,
                static::KEY_TO => static::DEFAULT_TO,
                static::KEY_REPLY_TO => static::DEFAULT_REPLY_TO,
                static::KEY_SUBJECT => static::DEFAULT_SUBJECT,
                static::KEY_ATTACH_UPLOADED_FILES => static::DEFAULT_ATTACH_UPLOADED_FILES,
            ];
    }
}
