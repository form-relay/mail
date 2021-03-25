<?php

namespace FormRelay\Mail\DataDispatcher;

use FormRelay\Core\DataDispatcher\DataDispatcher;
use FormRelay\Core\Exception\FormRelayException;
use FormRelay\Core\Model\Form\MultiValueField;
use FormRelay\Core\Model\Form\UploadField;
use FormRelay\Core\Service\RegistryInterface;
use FormRelay\Mail\Manager\DefaultMailManager;
use FormRelay\Mail\Manager\MailManagerInterface;
use FormRelay\Mail\Model\Form\EmailField;
use FormRelay\Mail\Template\DefaultTemplateEngine;
use FormRelay\Mail\Template\TemplateEngineInterface;
use FormRelay\Mail\Utility\MailUtility;
use Swift_Attachment;
use Swift_RfcComplianceException;
use Swift_Message;
use Swift_SwiftException;

abstract class AbstractMailDataDispatcher extends DataDispatcher
{
    protected $mailManager;
    protected $templateEngine;

    protected $attachUploadedFiles = false;

    protected $from = '';
    protected $to = '';
    protected $replyTo = '';
    protected $subject = '';

    public function __construct(RegistryInterface $registry, MailManagerInterface $mailManager = null, TemplateEngineInterface $templateEngine = null) {
        parent::__construct($registry);
        $this->mailManager = $mailManager ?? new DefaultMailManager();
        $this->templateEngine = $templateEngine ?? new DefaultTemplateEngine();
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
            /** @var UploadField $uploadField */
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
        try {
            $message = $this->mailManager->createMessage();
            $this->processMetaData($message, $data);
            $this->processContent($message, $data);
            if ($this->attachUploadedFiles) {
                $this->processAttachments($message, $data);
            }
            return $this->mailManager->sendMessage($message);
        } catch (Swift_SwiftException $e) {
            throw new FormRelayException($e->getMessage());
        }
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
     * EmailField()
     * [EmailField(), 'address@domain.tld']
     * MultiValue([EmailField(), 'address@domain.tld'])
     *
     * @param string|array|MultiValueField $addresses
     * @param bool $onlyOneAddress
     * @return array
     */
    protected function getAddressData($addresses, $onlyOneAddress = false)
    {
        if ($addresses instanceof EmailField) {
            $addresses = [$addresses];
        } elseif ($onlyOneAddress) {
            $addresses = [(string)$addresses];
        } elseif ($addresses instanceof MultiValueField) {
            $addresses = $addresses->toArray();
        } elseif (!is_array($addresses)) {
            $addresses = array_map('trim', explode(',', (string)$addresses));
        }
        $addresses = array_filter($addresses);

        $result = [];
        foreach ($addresses as $address) {
            $name = '';
            $email = '';
            if ($address instanceof EmailField) {
                $name = $address->getName();
                $email = $address->getAddress();
            } else {
                // Some Name <some-address@domain.tld>
                if (preg_match('/^([^<]+)<([^>]+)>$/', $address, $matches)) {
                    $name = $matches[1];
                    $email = $matches[2];
                } else {
                    $email = $address;
                }
            }
            if ($name) {
                $result[trim($email)] = MailUtility::encode($name);
            } else {
                $result[] = trim($email);
            }
        }
        return $result;
    }

    public function getAttachUploadedFiles(): bool
    {
        return $this->attachUploadedFiles;
    }

    public function setAttachUploadedFiles(bool $attachUploadedFiles)
    {
        $this->attachUploadedFiles = $attachUploadedFiles;
    }

    public function getTemplateEngine(): TemplateEngineInterface
    {
        return $this->templateEngine;
    }

    public function setTemplateEngine(TemplateEngineInterface $templateEngine)
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
        return array_filter($data, function($a) { return $a instanceof UploadField; });
    }

    abstract protected function getPlainBody(array $data): string;
    abstract protected function getHtmlBody(array $data): string;
}
