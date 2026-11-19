<?php

declare(strict_types=1);

namespace App\Services;

use DomainException;

/** A business-rule failure in the cart (unknown product, out of stock...). The message is safe to show users. */
class CartException extends DomainException
{
}
