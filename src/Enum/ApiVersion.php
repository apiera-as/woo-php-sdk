<?php

declare(strict_types=1);

namespace Apiera\WooPhpSdk\Enum;

/**
 * @author Fredrik Tveraaen <fredrik.tveraaen@apiera.io>
 * @since 0.1.0
 */
enum ApiVersion: string
{
    case V1 = 'wp-json/wc/v1';
    case V2 = 'wp-json/wc/v2';
    case V3 = 'wp-json/wc/v3';
}
