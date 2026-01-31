<?php

namespace Domain\Forecasting\Actions;

class ImportForecastResult
{
    public function __construct(
        public readonly int $imported,
        public readonly int $skipped,
        public readonly array $errors,
    ) {}

    public function summary(): string
    {
        $parts = [];

        if ($this->imported > 0) {
            $parts[] = "{$this->imported} forecast(s) imported";
        }

        if ($this->skipped > 0) {
            $parts[] = "{$this->skipped} skipped (already set)";
        }

        if (count($this->errors) > 0) {
            $parts[] = count($this->errors).' error(s)';
        }

        return implode(', ', $parts) ?: 'No rows processed.';
    }
}
