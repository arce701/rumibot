<?php

use App\Support\PhoneHelper;

if (! function_exists('format_phone')) {
    function format_phone(string $phone): string
    {
        return PhoneHelper::format($phone);
    }
}

if (! function_exists('phone_flag')) {
    function phone_flag(string $phone): string
    {
        return PhoneHelper::flagForPhone($phone) ?? '';
    }
}
