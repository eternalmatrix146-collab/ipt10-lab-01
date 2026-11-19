<?php

declare(strict_types=1);

namespace Phantom\Http\Client;

use RuntimeException;

/** Network-level failure (DNS, timeout, TLS...) — no HTTP response was received. */
class HttpClientException extends RuntimeException
{
}
