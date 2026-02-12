<?php

namespace Domain\Forecasting\Actions;

class ReassignLineItemsResult
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public readonly int $moved,
        public readonly int $merged,
        public readonly array $errors,
    ) {}

    public function summary(): string
    {
        $parts = [];

        if ($this->moved > 0) {
            $parts[] = "{$this->moved} item(s) moved";
        }

        if ($this->merged > 0) {
            $parts[] = "{$this->merged} item(s) merged";
        }

        if (count($this->errors) > 0) {
            $parts[] = count($this->errors).' error(s)';
        }

        return implode(', ', $parts) ?: 'No items processed.';
    }
}
