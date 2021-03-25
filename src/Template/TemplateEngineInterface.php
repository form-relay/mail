<?php

namespace FormRelay\Mail\Template;

interface TemplateEngineInterface
{
    /**
     * @param mixed $template
     * @param array $data
     * @return string
     */
    public function render($template, array $data): string;
}
