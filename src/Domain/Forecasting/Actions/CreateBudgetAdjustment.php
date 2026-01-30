<?php

namespace Domain\Forecasting\Actions;

use App\Models\BudgetAdjustment;
use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\User;
use DomainException;

class CreateBudgetAdjustment
{
    public function execute(
        ControlAccount $controlAccount,
        ForecastPeriod $period,
        User $user,
        float $amount,
        string $reason,
    ): BudgetAdjustment {
        if (! $period->isLocked()) {
            throw new DomainException('Adjustments can only be made to locked periods.');
        }

        if ($controlAccount->project_id !== $period->project_id) {
            throw new DomainException('Control account and period must belong to the same project.');
        }

        $previousBudget = (float) $controlAccount->approved_budget;
        $newBudget = $previousBudget + $amount;

        $controlAccount->update(['approved_budget' => $newBudget]);

        return BudgetAdjustment::create([
            'control_account_id' => $controlAccount->id,
            'forecast_period_id' => $period->id,
            'user_id' => $user->id,
            'amount' => $amount,
            'previous_approved_budget' => $previousBudget,
            'new_approved_budget' => $newBudget,
            'reason' => $reason,
        ]);
    }
}
