<?php

namespace FormRelay\Mail\Utility;

class MailUtility
{
    public static function encode(string $string)
    {
        return '=?UTF-8?B?' . base64_encode(trim($string)) . '?=';
    }
}
