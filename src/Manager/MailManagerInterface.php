<?php

namespace FormRelay\Mail\Manager;

use Symfony\Component\Mime\Email;

interface MailManagerInterface
{
    public function createMessage(): Email;
    public function sendMessage(Email $message): bool;
}
