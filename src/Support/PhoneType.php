<?php

declare(strict_types=1);

namespace EastpointLabs\LaravelAu\Support;

/**
 * The kind of Australian phone number, which decides how it's formatted and whether it can
 * be dialled from overseas.
 */
enum PhoneType: string
{
    case Mobile = 'mobile';
    case Landline = 'landline';
    case Service = 'service';
}
