<?php

declare(strict_types=1);

namespace Phantom\Validation;

use Phantom\Database\DB;
use Phantom\Exceptions\ValidationException;

/**
 * Pipe-delimited rule validation:
 *
 *   $data = Validator::make($request->all(), [
 *       'email'    => 'required|email|max:255|unique:users,email',
 *       'password' => 'required|min:8|confirmed',
 *       'price'    => 'required|numeric|min:0',
 *   ])->validate();
 *
 * Supported: required, nullable, string, email, numeric, integer, boolean, url,
 * alpha_dash, min, max, between, in, confirmed, same, unique, exists, regex.
 */
class Validator
{
    /** @var array<string, string[]> */
    private array $errors = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|string[]> $rules
     * @param array<string, string> $messages custom messages keyed "field.rule"
     */
    public function __construct(private array $data, private array $rules, private array $messages = [])
    {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string|string[]> $rules
     * @param array<string, string> $messages
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    public function passes(): bool
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleSet) {
            $rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            $value = $this->data[$field] ?? null;
            if (is_string($value)) {
                $value = trim($value);
            }
            $isEmpty = $value === null || $value === '' || $value === [];
            $isNumeric = in_array('numeric', $rules, true) || in_array('integer', $rules, true);

            if ($isEmpty && in_array('nullable', $rules, true)) {
                continue;
            }

            foreach ($rules as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);
                if ($name === 'nullable') {
                    continue;
                }
                if ($isEmpty && $name !== 'required') {
                    continue; // only "required" applies to empty values
                }
                if (!$this->check($name, $field, $value, $param, $isNumeric)) {
                    $this->errors[$field][] = $this->message($field, $name, $param, $isNumeric);
                    if ($name === 'required') {
                        break;
                    }
                }
            }
        }

        return $this->errors === [];
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * @return array<string, mixed> only the validated fields
     * @throws ValidationException
     */
    public function validate(): array
    {
        if (!$this->passes()) {
            throw new ValidationException($this->errors, $this->data);
        }
        $clean = [];
        foreach (array_keys($this->rules) as $field) {
            if (array_key_exists($field, $this->data)) {
                $clean[$field] = is_string($this->data[$field]) ? trim($this->data[$field]) : $this->data[$field];
            }
        }
        return $clean;
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    private function check(string $rule, string $field, mixed $value, ?string $param, bool $isNumeric): bool
    {
        $size = static function (mixed $value) use ($isNumeric): float {
            if ($isNumeric && is_numeric($value)) {
                return (float) $value;
            }
            return is_array($value) ? count($value) : mb_strlen((string) $value);
        };

        return match ($rule) {
            'required' => !($value === null || $value === '' || $value === []),
            'string' => is_string($value),
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'numeric' => is_numeric($value),
            'integer' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'boolean' => in_array($value, [true, false, 0, 1, '0', '1', 'on', 'off'], true),
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'alpha_dash' => (bool) preg_match('/^[A-Za-z0-9_-]+$/', (string) $value),
            'min' => $size($value) >= (float) $param,
            'max' => $size($value) <= (float) $param,
            'between' => (function () use ($size, $value, $param): bool {
                [$min, $max] = explode(',', (string) $param);
                return $size($value) >= (float) $min && $size($value) <= (float) $max;
            })(),
            'in' => in_array((string) $value, explode(',', (string) $param), true),
            'confirmed' => isset($this->data[$field . '_confirmation']) && $value === $this->data[$field . '_confirmation'],
            'same' => $value === ($this->data[(string) $param] ?? null),
            'regex' => (bool) preg_match((string) $param, (string) $value),
            'unique' => $this->checkUnique((string) $param, $field, $value),
            'exists' => $this->checkExists((string) $param, $field, $value),
            default => throw new \InvalidArgumentException("Unknown validation rule [{$rule}]"),
        };
    }

    /** unique:table,column,exceptId */
    private function checkUnique(string $param, string $field, mixed $value): bool
    {
        $parts = explode(',', $param);
        $query = DB::table($parts[0])->where($parts[1] ?? $field, $value);
        if (isset($parts[2]) && $parts[2] !== '') {
            $query->where('id', '!=', (int) $parts[2]);
        }
        return !$query->exists();
    }

    /** exists:table,column */
    private function checkExists(string $param, string $field, mixed $value): bool
    {
        $parts = explode(',', $param);
        return DB::table($parts[0])->where($parts[1] ?? $field, $value)->exists();
    }

    private function message(string $field, string $rule, ?string $param, bool $isNumeric): string
    {
        if (isset($this->messages["{$field}.{$rule}"])) {
            return $this->messages["{$field}.{$rule}"];
        }
        $label = str_replace('_', ' ', $field);
        return match ($rule) {
            'required' => "The {$label} field is required.",
            'email' => "The {$label} must be a valid email address.",
            'numeric' => "The {$label} must be a number.",
            'integer' => "The {$label} must be a whole number.",
            'min' => $isNumeric ? "The {$label} must be at least {$param}." : "The {$label} must be at least {$param} characters.",
            'max' => $isNumeric ? "The {$label} may not be greater than {$param}." : "The {$label} may not be longer than {$param} characters.",
            'between' => "The {$label} must be between " . str_replace(',', ' and ', (string) $param) . '.',
            'in' => "The selected {$label} is invalid.",
            'confirmed' => "The {$label} confirmation does not match.",
            'unique' => "That {$label} is already taken.",
            'exists' => "The selected {$label} does not exist.",
            'url' => "The {$label} must be a valid URL.",
            'alpha_dash' => "The {$label} may only contain letters, numbers, dashes and underscores.",
            default => "The {$label} is invalid.",
        };
    }
}
