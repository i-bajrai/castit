<?php

namespace Domain\Forecasting\Actions;

use App\Models\ControlAccount;

class DeleteControlAccount
{
    public function execute(ControlAccount $controlAccount): void
    {
        $controlAccount->delete();
    }
}
