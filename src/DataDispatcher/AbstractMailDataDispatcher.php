<?php

namespace FormRelay\Mail\DataDispatcher;

use FormRelay\Core\DataDispatcher\DataDispatcher;
use FormRelay\Core\Exception\FormRelayException;
use FormRelay\Core\Model\Form\MultiValueField;
use FormRelay\Core\Model\Form\UploadFormField;
use FormRelay\Core\Service\RegistryInterface;
use FormRelay\Mail\Template\DefaultTemplateEngine;
use FormRelay\Mail\Template\TemplateEngineInerface;
use Swift_Attachment;
use Swift_RfcComplianceException;
use Swift_Mailer;
use Swift_Message;
use Swift_SendmailTransport;
use Swift_SmtpTransport;

abstract class AbstractMailDataDispatcher extends DataDispatcher
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

    protected $templateEngine;

    protected $transportConfiguration = [];
    protected $attachUploadedFiles = false;

    protected $from = '';
    protected $to = '';
    protected $replyTo = '';
    protected $subject = '';

    public function __construct(RegistryInterface $registry) {
        parent::__construct($registry);
        $this->templateEngine = new DefaultTemplateEngine();
    }

    /**
     * Checks string for suspicious characters
     *
     * @param string $string String to check
     * @return string Valid or empty string
     */
    protected function sanitizeHeaderString(string $string): string
    {
        $pattern = '/[\\r\\n\\f\\e]/';
        if (preg_match($pattern, $string) > 0) {
            $this->logger->warning('Dirty mail header found: "' . $string . '"');
            $string = '';
        }
        return $string;
    }

    protected function processMetaData(Swift_Message &$message, array $data)
    {
        try {
            $from = $this->getFrom($data);
            $message->setFrom($this->getAddressData($from, true));

            $to = $this->getTo($data);
            $message->setTo($this->getAddressData($to));

            $replyTo = $this->getReplyTo($data);
            if ($replyTo) {
                $message->setReplyTo($this->getAddressData($replyTo, true));
            }

            $subject = $this->getSubject($data);
            $message->setSubject($this->sanitizeHeaderString($subject));
        } catch (Swift_RfcComplianceException $e) {
            throw new FormRelayException($e->getMessage());
        }
    }

    protected function processContent(Swift_Message &$message, array $data)
    {
        $plainBody = $this->getPlainBody($data);
        $htmlBody = $this->getHtmlBody($data);
        if ($htmlBody) {
            $message->setBody($htmlBody, 'text/html');
            if ($plainBody) {
                $message->addPart($plainBody, 'text/plain');
            }
        } elseif ($plainBody) {
            $message->setBody($plainBody, 'text/plain');
        }
    }

    protected function processAttachments(Swift_Message &$message, array $data)
    {
        $uploadFields = $this->getUploadFields($data);
        if (!empty($uploadFields)) {
            /** @var UploadFormField $uploadField */
            foreach ($uploadFields as $uploadField) {
                $message->attach(
                    Swift_Attachment::fromPath(
                        $uploadField->getRelativePath(),
                        $uploadField->getMimeType()
                    )->setFilename($uploadField->getFileName())
                );
            }
        }
    }

    public function send(array $data): bool
    {
        $mailer = $this->getMailer();
        /** @var Swift_Message $message */
        $message = $mailer->createMessage();
        $this->processMetaData($message, $data);
        $this->processContent($message, $data);
        if ($this->attachUploadedFiles) {
            $this->processAttachments($message, $data);
        }
        return $mailer->send($message);
    }

    public function getTransport()
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

    protected function getMailer()
    {
        return new Swift_Mailer($this->getTransport());
    }

    /**
     * getAddressData
     *
     * Input examples:
     * 'address@domain.tld'
     * 'Some Name <address@domain.tld>'
     * 'address@domain.tld, address-2@domain.tld'
     * 'Some Name <address@domain.tld>, address-2@domain.tld, Some Other Name <address-3@domain.tld>'
     * ['address@domain.tld', 'Some Name <address@domain.tld>']
     * MultiValueField(['address@domain.tld', 'Some Name <address@domain.tld>'])
     *
     * @param string|array|MultiValueField $addresses
     * @param bool $onlyOneAddress
     * @return array
     */
    protected function getAddressData($addresses, $onlyOneAddress = false)
    {
        if ($onlyOneAddress) {
            $addresses = [(string)$addresses];
        } else {
            if ($addresses instanceof MultiValueField) {
                $addresses = $addresses->toArray();
            } elseif (!is_array($addresses)) {
                $addresses = array_map('trim', explode(',', $addresses));
            }
            $addresses = array_map(function($a) { return (string)$a; }, $addresses);
            $addresses = array_filter($addresses);
        }

        $result = [];
        foreach ($addresses as $address) {
            $matches = [];
            // Some Name <some-address@domain.tld>
            if (preg_match('/^([^<]+)<([^>]+)>$/', $address, $matches)) {
                $result[$matches[2]] = $matches[1];
            } else {
                $result[] = $address;
            }
        }
        return $result;
    }

    public function getTransportConfiguration(): array
    {
        return $this->transportConfiguration;
    }

    public function setTransportConfiguration(array $transportConfiguration)
    {
        $this->transportConfiguration = $transportConfiguration;
    }

    public function getAttachUploadedFiles(): bool
    {
        return $this->attachUploadedFiles;
    }

    public function setAttachUploadedFiles(bool $attachUploadedFiles)
    {
        $this->attachUploadedFiles = $attachUploadedFiles;
    }

    public function getTemplateEngine(): TemplateEngineInerface
    {
        return $this->templateEngine;
    }

    public function setTemplateEngine(TemplateEngineInerface $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    public function getFrom(array $data)
    {
        return $this->templateEngine->render($this->from, $data);
    }

    public function setFrom($from)
    {
        $this->from = $from;
    }

    public function getTo(array $data)
    {
        return $this->templateEngine->render($this->to, $data);
    }

    public function setTo($to)
    {
        $this->to = $to;
    }

    public function getReplyTo(array $data)
    {
        return $this->templateEngine->render($this->replyTo, $data);
    }

    public function setReplyTo($replyTo)
    {
        $this->replyTo = $replyTo;
    }

    public function getSubject(array $data): string
    {
        return $this->templateEngine->render($this->subject, $data);
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function getUploadFields(array $data): array
    {
        return array_filter($data, function($a) { return $a instanceof UploadFormField; });
    }

    abstract protected function getPlainBody(array $data): string;
    abstract protected function getHtmlBody(array $data): string;
}
