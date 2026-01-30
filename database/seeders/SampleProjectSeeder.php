<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleProjectSeeder extends Seeder
{
    public function run(): void
    {
        // 1. User & Company
        $user = User::factory()->create([
            'name' => 'Imran',
            'email' => 'imran@castit.com',
            'password' => 'password',
        ]);

        $company = Company::create([
            'user_id' => $user->id,
            'name' => 'CastIt Construction',
        ]);

        // 2. Project
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'PRISM Highway Extension',
            'original_budget' => 248117066,
            'start_date' => '2024-01-01',
            'end_date' => '2025-12-01',
        ]);

        // 3. Forecast period (current month so it's editable)
        $period = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => now()->startOfMonth(),
            'is_current' => true,
        ]);

        // 4. Control Account: 401CB00
        $ca401CB = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401CB00',
            'description' => 'Line Wide - Civil - Concrete Barriers',
            'category' => '401S - Structure',
            'baseline_budget' => 962977,
            'approved_budget' => 1098258,
            'sort_order' => 1,
        ]);

        // 5. Cost Packages under 401CB00
        $pkg006 = $ca401CB->costPackages()->create([
            'project_id' => $project->id,
            'item_no' => '006',
            'name' => 'GNANGARA INTERSECTION (DESIGN PACKAGE 02 - E039)',
            'sort_order' => 1,
        ]);

        $pkg009 = $ca401CB->costPackages()->create([
            'project_id' => $project->id,
            'item_no' => '009',
            'name' => 'WHITEMAN PARK (DESIGN PACKAGE 04 - E050)',
            'sort_order' => 2,
        ]);

        $pkg003 = $ca401CB->costPackages()->create([
            'project_id' => $project->id,
            'item_no' => '3',
            'name' => 'Dulwich / Cheltenham Street',
            'sort_order' => 3,
        ]);

        // 6. Line Items — Package 006 (Gnangara)
        $item007 = LineItem::create([
            'cost_package_id' => $pkg006->id,
            'item_no' => '007',
            'description' => 'TL5 BARRIER 295-CB-001, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 98,
            'original_rate' => 342.00,
            'original_amount' => 33516.00,
            'sort_order' => 1,
        ]);

        $item008 = LineItem::create([
            'cost_package_id' => $pkg006->id,
            'item_no' => '008',
            'description' => 'TL5 BARRIER 295-CB-002, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 116,
            'original_rate' => 313.85,
            'original_amount' => 36406.60,
            'sort_order' => 2,
        ]);

        // 7. Line Items — Package 009 (Whiteman Park)
        $item010 = LineItem::create([
            'cost_package_id' => $pkg009->id,
            'item_no' => '010',
            'description' => 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER<0.7mm',
            'unit_of_measure' => 'LM',
            'original_qty' => 1737,
            'original_rate' => 291.75,
            'original_amount' => 506769.75,
            'sort_order' => 1,
        ]);

        $item012 = LineItem::create([
            'cost_package_id' => $pkg009->id,
            'item_no' => '012',
            'description' => 'TL5 BARRIER AV-CB-001, 1420MM HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 40,
            'original_rate' => 606.25,
            'original_amount' => 24250.00,
            'sort_order' => 2,
        ]);

        $itemGt07 = LineItem::create([
            'cost_package_id' => $pkg009->id,
            'item_no' => null,
            'description' => 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER>0.7mm',
            'unit_of_measure' => 'LM',
            'original_qty' => 88,
            'original_rate' => 345.25,
            'original_amount' => 30382.00,
            'sort_order' => 3,
        ]);

        $item013 = LineItem::create([
            'cost_package_id' => $pkg009->id,
            'item_no' => '013',
            'description' => 'TL5 BARRIER AV-CB-002, 1420MM HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 40,
            'original_rate' => 606.25,
            'original_amount' => 24250.00,
            'sort_order' => 4,
        ]);

        $itemTransition = LineItem::create([
            'cost_package_id' => $pkg009->id,
            'item_no' => null,
            'description' => 'TRANSITION FROM 1420MM TO 1070MM',
            'unit_of_measure' => 'LM',
            'original_qty' => 0,
            'original_rate' => 606.25,
            'original_amount' => 0,
            'sort_order' => 5,
        ]);

        // 8. Line Items — Package 3 (Dulwich/Cheltenham)
        $dulwichItems = [
            ['item_no' => 'VO-003.01', 'description' => 'TL5 BARRIER AX-CB-001, 1070 HIGH BARRIER', 'qty' => 83, 'rate' => 345.25, 'amount' => 28655.75, 'sort' => 1],
            ['item_no' => 'VO-003.01', 'description' => 'TL5 BARRIER AX-CB-002, 1070 HIGH BARRIER', 'qty' => 78, 'rate' => 345.25, 'amount' => 26929.50, 'sort' => 2],
            ['item_no' => '011', 'description' => 'TL5 BARRIER AX-CB-003, 1070 HIGH BARRIER', 'qty' => 88, 'rate' => 345.25, 'amount' => 30382.00, 'sort' => 3],
            ['item_no' => 'VO-003.01', 'description' => 'TL5 BARRIER AX-CB-004, 1070 HIGH BARRIER', 'qty' => 42, 'rate' => 345.25, 'amount' => 14500.50, 'sort' => 4],
            ['item_no' => 'VO-003.01', 'description' => 'TL5 BARRIER AX-CB-005, 1070 HIGH BARRIER', 'qty' => 74, 'rate' => 345.25, 'amount' => 25548.50, 'sort' => 5],
            ['item_no' => 'VO-003.01', 'description' => 'TL5 BARRIER AX-CB-006, 1070 HIGH BARRIER', 'qty' => 79, 'rate' => 345.25, 'amount' => 27274.75, 'sort' => 6],
            ['item_no' => '014', 'description' => 'W-BEAM TO CONCRETE TRANSITION', 'qty' => 6, 'rate' => 3500.00, 'amount' => 21000.00, 'sort' => 7, 'uom' => 'Each'],
        ];

        $dulwichLineItems = [];
        foreach ($dulwichItems as $d) {
            $dulwichLineItems[] = LineItem::create([
                'cost_package_id' => $pkg003->id,
                'item_no' => $d['item_no'],
                'description' => $d['description'],
                'unit_of_measure' => $d['uom'] ?? 'LM',
                'original_qty' => $d['qty'],
                'original_rate' => $d['rate'],
                'original_amount' => $d['amount'],
                'sort_order' => $d['sort'],
            ]);
        }

        // 9. Line Item Forecasts — simulate partial completion
        // Gnangara items: fully complete (CTD = original qty)
        $this->createForecast($item007, $period, ctdQty: 98);
        $this->createForecast($item008, $period, ctdQty: 116);

        // Whiteman Park: partially complete
        $this->createForecast($item010, $period, ctdQty: 1500);
        $this->createForecast($item012, $period, ctdQty: 0, comments: 'Done by SVG');
        $this->createForecast($itemGt07, $period, ctdQty: 88);
        $this->createForecast($item013, $period, ctdQty: 0, comments: 'Done by SVG');
        $this->createForecast($itemTransition, $period, ctdQty: 0);

        // Dulwich: most items partially/fully complete
        $dulwichCtd = [83, 78, 88, 42, 60, 50, 4];
        foreach ($dulwichLineItems as $i => $li) {
            $this->createForecast($li, $period, ctdQty: $dulwichCtd[$i]);
        }
    }

    private function createForecast(
        LineItem $item,
        ForecastPeriod $period,
        float $ctdQty,
        ?string $comments = null,
    ): void {
        $origRate = (float) $item->original_rate;
        $origQty = (float) $item->original_qty;

        $ctdRate = $origRate;
        $ctdAmount = $ctdQty * $ctdRate;
        $ctcQty = max(0, $origQty - $ctdQty);
        $ctcRate = $origRate;
        $ctcAmount = $ctcQty * $ctcRate;
        $fcacAmount = $ctdAmount + $ctcAmount;
        $totalQty = $ctdQty + $ctcQty;
        $fcacRate = $totalQty > 0 ? $fcacAmount / $totalQty : 0;
        $previousAmount = (float) $item->original_amount;
        $variance = $previousAmount - $fcacAmount;

        LineItemForecast::create([
            'line_item_id' => $item->id,
            'forecast_period_id' => $period->id,
            'previous_qty' => $origQty,
            'previous_rate' => $origRate,
            'previous_amount' => $previousAmount,
            'ctd_qty' => $ctdQty,
            'ctd_rate' => $ctdRate,
            'ctd_amount' => $ctdAmount,
            'ctc_qty' => $ctcQty,
            'ctc_rate' => $ctcRate,
            'ctc_amount' => $ctcAmount,
            'fcac_rate' => $fcacRate,
            'fcac_amount' => $fcacAmount,
            'variance' => $variance,
            'comments' => $comments,
        ]);
    }
}
