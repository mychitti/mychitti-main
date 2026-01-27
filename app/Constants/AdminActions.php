<?php

namespace App\Constants;

class AdminActions
{
    // USER
    public const RETAIL_RECHARGE_WALLET      = 'retail_recharge_wallet';
    public const DOWNLOAD_FILE       = 'download_file';


    // GROUPED ACTIONS (VERY USEFUL)
    public const OTP_REQUIRED = [
        self::RETAIL_RECHARGE_WALLET,
        self::DOWNLOAD_FILE,
    ];
}
