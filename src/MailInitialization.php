<?php

namespace FormRelay\Mail;

use FormRelay\Core\Initialization;
use FormRelay\Mail\ConfigurationResolver\ContentResolver\EmailContentResolver;
use FormRelay\Mail\DataDispatcher\MailDataDispatcher;
use FormRelay\Mail\Route\MailRoute;

class MailInitialization extends Initialization
{
    const CONTENT_RESOLVERS = [
        EmailContentResolver::class,
    ];
    const DATA_DISPATCHERS = [
        MailDataDispatcher::class,
    ];
    const ROUTES = [
        MailRoute::class,
    ];
}
