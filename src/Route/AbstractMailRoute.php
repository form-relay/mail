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
    
    const KEY_SIGN_MESSAGE_BODY = 'signMessageBody';
    const DEFAULT_SIGN_MESSAGE_BODY = false;
    
    const KEY_SIGNING_CERTIFICATE = 'signingCertificate';
    const DEFAULT_SIGNING_CERTIFICATE = '';
    
    const KEY_SIGNING_PRIVATE_KEY = 'signingPrivateKey';
    const DEFAULT_SIGNING_PRIVATE_KEY = '';
    
    const KEY_ENCRYPT_MESSAGE_BODY = 'encryptMessageBody';
    const DEFAULT_ENCRYPT_MESSAGE_BODY = false;
    
    const KEY_ENCRYPTION_CERTIFICATE = 'encryptionCertificate';
    const DEFAULT_ENCRYPTION_CERTIFICATE = '';

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
        
        $signMessageBody = $this->resolveContent($this->getConfig(static::KEY_SIGN_MESSAGE_BODY));
        $dispatcher->setSignMessageBody($signMessageBody);
        
        $signingCertificate = $this->resolveContent($this->getConfig(static::KEY_SIGNING_CERTIFICATE));
        $dispatcher->setSigningCertificate($signingCertificate);
        
        $signingPrivateKey = $this->resolveContent($this->getConfig(static::KEY_SIGNING_PRIVATE_KEY));
        $dispatcher->setSigningPrivateKey($signingPrivateKey);
        
        $encryptMessageBody = $this->resolveContent($this->getConfig(static::KEY_ENCRYPT_MESSAGE_BODY));
        $dispatcher->setEncryptMessageBody($encryptMessageBody);
        
        $encryptionCertificate = $this->resolveContent($this->getConfig(static::KEY_ENCRYPTION_CERTIFICATE));
        $dispatcher->setEncryptionCertificate($encryptionCertificate);

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
                static::KEY_SIGN_MESSAGE_BODY => static::DEFAULT_SIGN_MESSAGE_BODY,
                static::KEY_SIGNING_CERTIFICATE => static::DEFAULT_SIGNING_CERTIFICATE,
                static::KEY_SIGNING_PRIVATE_KEY => static::DEFAULT_SIGNING_PRIVATE_KEY,
                static::KEY_ENCRYPT_MESSAGE_BODY => static::DEFAULT_ENCRYPT_MESSAGE_BODY,
                static::KEY_ENCRYPTION_CERTIFICATE => static::DEFAULT_ENCRYPTION_CERTIFICATE
            ];
    }
}
