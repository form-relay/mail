<?php

namespace FormRelay\Mail\Manager;

use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;
use Swift_Transport;

class DefaultMailManager implements MailManagerInterface
{
    const TRANSPORT_TYPE = 'type';
    const TRANSPORT_TYPE_SENDMAIL = 'sendmail';
    const TRANSPORT_TYPE_SMTP = 'smtp';

    const TRANSPORT_CONFIG = 'config';
    const TRANSPORT_CONFIG_SENDMAIL_CMD = 'cmd';
    const TRANSPORT_CONFIG_SMTP_DOMAIN = 'domain';
    const TRANSPORT_CONFIG_SMTP_PORT = 'port';
    const TRANSPORT_CONFIG_SMTP_USERNAME = 'username';
    const TRANSPORT_CONFIG_SMTP_PASSWORD = 'password';

    protected $transportConfiguration = [
        self::TRANSPORT_TYPE => self::TRANSPORT_TYPE_SENDMAIL,
        self::TRANSPORT_CONFIG => [
            self::TRANSPORT_CONFIG_SENDMAIL_CMD => '/usr/sbin/sendmail -bs',
        ],
    ];

    public function getTransportConfiguration(): array
    {
        return $this->transportConfiguration;
    }

    public function setTransportConfiguration(array $transportConfiguration)
    {
        $this->transportConfiguration = $transportConfiguration;
    }

    public function getTransport(): Swift_Transport
    {
        $transport = null;
        $config = $this->transportConfiguration[static::TRANSPORT_CONFIG];
        switch ($this->transportConfiguration[static::TRANSPORT_TYPE]) {
            case static::TRANSPORT_TYPE_SENDMAIL:
                $transport = new Swift_SendmailTransport($config[static::TRANSPORT_CONFIG_SENDMAIL_CMD]);
                break;
            case static::TRANSPORT_TYPE_SMTP:
                $transport = new Swift_SmtpTransport(
                    $config[static::TRANSPORT_CONFIG_SMTP_DOMAIN],
                    $config[static::TRANSPORT_CONFIG_SMTP_PORT]
                );
                if (isset($config[static::TRANSPORT_CONFIG_SMTP_USERNAME],)) {
                    $transport->setUsername($config[static::TRANSPORT_CONFIG_SMTP_USERNAME]);
                }
                if (isset($config[static::TRANSPORT_CONFIG_SMTP_PASSWORD])) {
                    $transport->setPassword($config[static::TRANSPORT_CONFIG_SMTP_PASSWORD]);
                }
                break;
        }
        return $transport;
    }

    public function getMailer(): Swift_Mailer
    {
        $transport = $this->getTransport();
        return new Swift_Mailer($transport);
    }

    public function createMessage(): Swift_Message
    {
        $mailer = $this->getMailer();
        /** @var Swift_Message $message */
        $message = $mailer->createMessage();
        return $message;
    }

    public function sendMessage(Swift_Message $message): bool
    {
        return $this->getMailer()->send($message);
    }
}
