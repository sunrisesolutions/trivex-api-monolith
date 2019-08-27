<?php

namespace App\Util\Event;

class AppUtil extends BaseUtil
{
    const APP_NAME = 'EVENT';

    public static function generateUuid($prefix = self::APP_NAME)
    {
        return sprintf('%s-%s-%s', $prefix, uniqid(), date_format(new \DateTime(), 'HidmY'));
    }
}