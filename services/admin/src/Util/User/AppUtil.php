<?php

namespace App\Util\User;

class AppUtil extends BaseUtil
{
    const APP_NAME = 'USER';
    const MESSAGE_VERSION = 1;

    public static function getAppName()
    {
        return self::APP_NAME;
    }
}
