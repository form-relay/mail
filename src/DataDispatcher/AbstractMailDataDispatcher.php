<?php

namespace FormRelay\Mail\DataDispatcher;

use FormRelay\Core\DataDispatcher\DataDispatcher;
use FormRelay\Core\Exception\FormRelayException;
use FormRelay\Core\Log\LoggerInterface;
use FormRelay\Core\Model\Form\MultiValueField;
use FormRelay\Core\Model\Form\UploadField;
use FormRelay\Core\Utility\GeneralUtility;
use FormRelay\Mail\Manager\DefaultMailManager;
use FormRelay\Mail\Manager\MailManagerInterface;
use FormRelay\Mail\Model\Form\EmailField;
use FormRelay\Mail\Template\DefaultTemplateEngine;
use FormRelay\Mail\Template\TemplateEngineInterface;
use FormRelay\Mail\Utility\MailUtility;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Crypto\SMimeSigner;
use Symfony\Component\Mime\Crypto\SMimeEncrypter;

abstract class AbstractMailDataDispatcher extends DataDispatcher
{
    protected $mailManager;
    protected $templateEngine;

    protected $attachUploadedFiles = false;
    
    protected $signMessageBody = false;
    protected $signingCertificate = '';
    protected $signingPrivateKey = '';
    
    protected $encryptMessageBody = false;
    protected $encryptionCertificate = '';

    protected $from = '';
    protected $to = '';
    protected $replyTo = '';
    protected $subject = '';

    public function __construct(LoggerInterface $logger, MailManagerInterface $mailManager = null, TemplateEngineInterface $templateEngine = null)
    {
        parent::__construct($logger);
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

    protected function processMetaData(Email &$message, array $data)
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
        } catch (RfcComplianceException $e) {
            throw new FormRelayException($e->getMessage());
        }
    }

    protected function processContent(Email &$message, array $data)
    {
        $plainBody = $this->getPlainBody($data);
        $htmlBody = $this->getHtmlBody($data);
        if ($htmlBody) {
            $message->html($htmlBody, 'text/html');
            if ($plainBody) {
                $message->text($plainBody, 'text/plain');
            }
        } elseif ($plainBody) {
            $message->text($plainBody, 'text/plain');
        }
    }

    protected function processAttachments(Email &$message, array $data)
    {
        $uploadFields = $this->getUploadFields($data);
        if (!empty($uploadFields)) {
            /** @var UploadField $uploadField */
            foreach ($uploadFields as $uploadField) {
                $message->attachFromPath(
                        $uploadField->getRelativePath(), 
                        $uploadField->getFileName(),
                        $uploadField->getMimeType()
                );
            }
        }
    }
    
    protected function signMessage(Email &$message): void
    {
        try {
            $certificateFilePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $this->signingCertificate;
            $privateKeyFilePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $this->signingPrivateKey;
            
            $signer = new SMimeSigner($certificateFilePath, $privateKeyFilePath);
            $signedMessage = $signer->sign($message);
        
            $signedMessageBody = $signedMessage->getBody();
            $message->setBody($signedMessageBody);
        } catch (\Exception $e) {
            echo '<pre> Exception signing', $e->getMessage(); var_dump(get_class($e)); // TODO remove
            throw new FormRelayException($e->getMessage());
        }
    }

    protected function encryptMessage(Email &$message): void
    {
        try {
            $certificateFilePath = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . $this->encryptionCertificate;
            
            $encrypter = new SMimeEncrypter($certificateFilePath);
            $encryptedMessage = $encrypter->encrypt($message);

            $encryptedMessageBody = $encryptedMessage->getBody();
            $message->setBody($encryptedMessageBody);
        } catch (\Exception $e) {
            echo '<pre> Exception encrypt', $e->getMessage(); var_dump(get_class($e)); // TODO remove
            throw new FormRelayException($e->getMessage());
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
            if ($this->signMessageBody) {
                $this->signMessage($message);
            }
            if ($this->encryptMessageBody) {
                $this->encryptMessage($message);
            }
            return $this->mailManager->sendMessage($message);
        } catch (\Exception $e) {
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
     * @param string|array|MultiValueField|EmailField $addresses
     * @param bool $onlyOneAddress
     * @return array
     */
    protected function getAddressData($addresses, $onlyOneAddress = false)
    {
        if ($addresses instanceof EmailField) {
            $addresses = [$addresses];
        } elseif ($onlyOneAddress) {
            $addresses = [(string)$addresses];
        } else {
            $addresses = GeneralUtility::castValueToArray($addresses);
        }
        $addresses = array_filter($addresses);

        $result = [];
        foreach ($addresses as $address) {
            $name = '';
            $email = '';
            if ($address instanceof EmailField) {
                $name = $address->getName();
                $email = $address->getAddress();
            } elseif (preg_match('/^([^<]+)<([^>]+)>$/', $address, $matches)) {
                // Some Name <some-address@domain.tld>
                $name = $matches[1];
                $email = $matches[2];
            } else {
                $email = $address;
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
    
    public function getSignMessageBody(): bool
    {
        return $this->signMessageBody;
    }

    public function setSignMessageBody(bool $signMessageBody)
    {
        $this->signMessageBody = $signMessageBody;
    }
    
    public function getSigningCertificate(): string
    {
        return $this->signingCertificate;
    }

    public function setSigningCertificate(string $signingCertificate)
    {
        $this->signingCertificate = $signingCertificate;
    }
    
    public function getSigningPrivateKey(): string
    {
        return $this->signingPrivateKey;
    }

    public function setSigningPrivateKey(string $signingPrivateKey)
    {
        $this->signingPrivateKey = $signingPrivateKey;
    }
    
    public function getEncryptMessageBody(): bool
    {
        return $this->encryptMessageBody;
    }

    public function setEncryptMessageBody(bool $encryptMessageBody)
    {
        $this->encryptMessageBody = $encryptMessageBody;
    }
    
    public function getEncryptionCertificate(): string
    {
        return $this->encryptionCertificate;
    }

    public function setEncryptionCertificate(string $encryptionCertificate)
    {
        $this->encryptionCertificate = $encryptionCertificate;
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
        return array_filter($data, function ($a) { return $a instanceof UploadField; });
    }

    abstract protected function getPlainBody(array $data): string;
    abstract protected function getHtmlBody(array $data): string;
}
