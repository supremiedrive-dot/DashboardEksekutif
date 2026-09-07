<?php

namespace App\Services;

use App\Models\IndicatorDefinition;
use Illuminate\Validation\ValidationException;

class DefinitionValueValidator
{
    public function normalize(IndicatorDefinition $definition, mixed $value): array
    {
        $rules = $definition->validation_rules ?? [];
        $type = $definition->value_type;
        return match ($type) {
            'integer', 'year' => $this->integer($value, $rules, $type),
            'decimal', 'percentage' => $this->decimal($value, $rules),
            'status' => $this->status($value, $rules),
            'boolean' => $this->boolean($value),
            'text' => $this->text($value, $rules),
            'range' => $this->range($value),
            default => $this->invalid('Tipe nilai definition tidak didukung.'),
        };
    }

    private function integer(mixed $value, array $rules, string $type): array
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false || is_bool($value)) $this->invalid('Nilai harus berupa bilangan bulat.');
        $value = (int) $value;
        if (isset($rules['min']) && $value < $rules['min']) $this->invalid('Nilai lebih kecil dari batas definition.');
        if (isset($rules['max']) && $value > $rules['max']) $this->invalid('Nilai lebih besar dari batas definition.');
        if ($type === 'year' && ($value < 1000 || $value > 9999)) $this->invalid('Nilai tahun harus berformat YYYY.');
        return [$type === 'year' ? 'value_year' : 'value_integer' => $value];
    }

    private function decimal(mixed $value, array $rules): array
    {
        if (is_float($value) && abs($value) > 9007199254740991) {
            $this->invalid('Angka besar harus dikirim sebagai string desimal agar tetap presisi.');
        }
        if (! is_int($value) && ! is_float($value) && ! is_string($value)) {
            $this->invalid('Nilai harus berupa angka desimal yang valid.');
        }
        $raw = is_float($value) ? json_encode($value, JSON_PRESERVE_ZERO_FRACTION) : (string)$value;
        if (! is_string($raw) || ! preg_match('/^(-?)(\d+)(?:\.(\d+))?$/D', $raw, $parts)) {
            $this->invalid('Nilai harus berupa angka desimal yang valid.');
        }
        $integer = ltrim($parts[2], '0') ?: '0';
        $fraction = $parts[3] ?? '';
        if (strlen($integer) > 18 || strlen($fraction) > 6) $this->invalid('Nilai melebihi presisi definition.');
        $normalized = ($parts[1] === '-' && ($integer !== '0' || trim($fraction, '0') !== '') ? '-' : '')
            .$integer.'.'.str_pad($fraction, 6, '0');
        $number = (float)$normalized;
        if (isset($rules['min']) && $number < $rules['min']) $this->invalid('Nilai lebih kecil dari batas definition.');
        if (isset($rules['max']) && $number > $rules['max']) $this->invalid('Nilai lebih besar dari batas definition.');
        return ['value_decimal'=>$normalized];
    }

    private function status(mixed $value, array $rules): array
    {
        if (! is_string($value) || ! in_array($value, $rules['allowed_values'] ?? [], true)) {
            $this->invalid('Nilai status tidak termasuk domain definition.');
        }
        return ['value_status_code'=>$value];
    }

    private function boolean(mixed $value): array
    {
        if (! is_bool($value)) $this->invalid('Nilai harus berupa boolean JSON.');
        return ['value_status_code'=>$value ? 'true' : 'false'];
    }

    private function text(mixed $value, array $rules): array
    {
        if (! is_string($value)) $this->invalid('Nilai harus berupa teks.');
        $max = $rules['max_length'] ?? 5000;
        if (mb_strlen($value) > $max) $this->invalid('Nilai teks terlalu panjang.');
        return ['value_text'=>$value];
    }

    private function range(mixed $value): array
    {
        if (! is_array($value) || ! array_key_exists('min', $value) || ! array_key_exists('max', $value)) {
            $this->invalid('Nilai rentang harus memiliki min dan max yang valid.');
        }
        $min = $this->decimal($value['min'], [])['value_decimal'];
        $max = $this->decimal($value['max'], [])['value_decimal'];
        if ((float)$min > (float)$max) $this->invalid('Nilai rentang harus memiliki min dan max yang valid.');
        return ['value_min'=>$min, 'value_max'=>$max];
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['value'=>[$message]]);
    }
}
