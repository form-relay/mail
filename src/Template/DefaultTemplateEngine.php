<?php

namespace FormRelay\Mail\Template;

use FormRelay\Core\Utility\GeneralUtility;

class DefaultTemplateEngine implements TemplateEngineInterface
{
    public function render($template, array $data): string
    {
        $result = GeneralUtility::parseSeparatorString($template);
        foreach ($data as $field => $value) {
            $result = str_replace('{' . $field . '}', $value, $result);
        }
        return $result;
    }
}
