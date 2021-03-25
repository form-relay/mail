<?php

namespace FormRelay\Mail\DataDispatcher;

use FormRelay\Core\Model\Form\UploadField;
use FormRelay\Core\Utility\GeneralUtility;

class MailDataDispatcher extends AbstractMailDataDispatcher
{
    protected $valueDelimiter = '\s=\s';
    protected $lineDelimiter = '\n';

    public function getValueDelimiter(): string
    {
        return $this->valueDelimiter;
    }

    public function setValueDelimiter(string $valueDelimiter)
    {
        $this->valueDelimiter = $valueDelimiter;
    }

    public function getLineDelimiter(): string
    {
        return $this->lineDelimiter;
    }

    public function setLineDelimiter(string $lineDelimiter)
    {
        $this->lineDelimiter = $lineDelimiter;
    }

    protected function getPlainBody(array $data): string
    {
        if ($this->getAttachUploadedFiles()) {
            $data = array_filter($data, function($a) { return !$a instanceof UploadField; });
        }
        $valueDelimiter = GeneralUtility::parseSeparatorString($this->valueDelimiter);
        $lineDelimiter = GeneralUtility::parseSeparatorString($this->lineDelimiter);
        $content = '';
        foreach ($data as $field => $value) {
            $content .= $field . $valueDelimiter . $value . $lineDelimiter;
        }
        return $content;
    }

    protected function getHtmlBody(array $data): string
    {
        return '';
    }
}
