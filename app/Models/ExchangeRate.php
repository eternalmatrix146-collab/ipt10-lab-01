<?php

declare(strict_types=1);

namespace App\Models;

use Phantom\Database\Model;

/**
 * @property string $base
 * @property string $currency
 * @property string $rate
 * @property string $rate_date
 * @property string $fetched_at
 */
class ExchangeRate extends Model
{
    protected static string $table = 'exchange_rates';

    protected static array $fillable = ['base', 'currency', 'rate', 'rate_date', 'fetched_at'];
}
