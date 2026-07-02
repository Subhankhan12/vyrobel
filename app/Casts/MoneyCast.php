<?php

declare(strict_types=1);

namespace App\Casts;

use App\Support\Money\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Maps a pair of columns — an integer `*_minor` amount and a `currency` char(3) — onto a
 * single {@see Money} value object attribute.
 *
 * Usage (in a model's casts()):
 *   'price' => MoneyCast::class.':price_minor,currency',
 *
 * Then `$model->price` is a Money, and setting `$model->price = Money::of(1500, 'EUR')`
 * writes both underlying columns. The virtual attribute name ('price') need not be a column.
 *
 * @implements CastsAttributes<Money|null, mixed>
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(
        protected string $amountColumn = 'amount_minor',
        protected string $currencyColumn = 'currency',
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        $amount = $attributes[$this->amountColumn] ?? null;

        if ($amount === null) {
            return null;
        }

        $currency = $attributes[$this->currencyColumn] ?? null;

        if ($currency === null) {
            throw new InvalidArgumentException(
                "MoneyCast: amount present but currency column [{$this->currencyColumn}] is null on [".$model::class.']'
            );
        }

        return new Money((int) $amount, (string) $currency);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$this->amountColumn => null];
        }

        if (! $value instanceof Money) {
            throw new InvalidArgumentException(
                'MoneyCast expects a '.Money::class.' instance, got ['.get_debug_type($value).'].'
            );
        }

        return [
            $this->amountColumn => $value->amountMinor(),
            $this->currencyColumn => $value->currency(),
        ];
    }
}
