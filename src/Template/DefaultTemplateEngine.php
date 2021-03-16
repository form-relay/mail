<?php

namespace FormRelay\Mail\Template;

use FormRelay\Core\Utility\GeneralUtility;

class DefaultTemplateEngine implements TemplateEngineInerface
{
    public function render($template, array $data): string
    {
        $result = GeneralUtility::parseSeparatorString($template);
        foreach ($data as $field => $value) {
            $result = preg_replace('/\{' . preg_quote($field) . '\}/', $value, $result);
        }
        return $result;
    }
}
