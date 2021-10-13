<?php

namespace FormRelay\Mail\Manager;

use Swift_Message;

interface MailManagerInterface
{
    public function createMessage(): Swift_Message;
    public function sendMessage(Swift_Message $message): bool;
}
