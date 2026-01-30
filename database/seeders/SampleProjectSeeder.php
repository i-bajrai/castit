<?php

namespace Database\Seeders;

use App\Models\ControlAccount;
use App\Models\ControlAccountForecast;
use App\Models\CostPackage;
use App\Models\ForecastPeriod;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleProjectSeeder extends Seeder
{
    /**
     * Seed the application's database with realistic construction forecasting data.
     */
    public function run(): void
    {
        // 1. Create test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@castit.com',
            'password' => 'password',
        ]);

        // 2. Create project
        $project = Project::create([
            'user_id' => $user->id,
            'name' => 'PRISM Highway Extension',
            'original_budget' => 248117066,
        ]);

        // 3. Create forecast periods
        $decPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2023-12-01',
            'is_current' => false,
        ]);

        $janPeriod = ForecastPeriod::create([
            'project_id' => $project->id,
            'period_date' => '2024-01-01',
            'is_current' => true,
        ]);

        // 4. Create cost packages
        $package006 = CostPackage::create([
            'project_id' => $project->id,
            'item_no' => '006',
            'name' => 'GNANGARA INTERSECTION (DESIGN PACKAGE 02 - E039)',
            'sort_order' => 1,
        ]);

        $package009 = CostPackage::create([
            'project_id' => $project->id,
            'item_no' => '009',
            'name' => 'WHITEMAN PARK (DESIGN PACKAGE 04 - E050)',
            'sort_order' => 2,
        ]);

        $package3 = CostPackage::create([
            'project_id' => $project->id,
            'item_no' => '3',
            'name' => 'Dulwich / Cheltenham Street',
            'sort_order' => 3,
        ]);

        // 5. Create line items and 6. Create forecasts

        // --- Package 006 (Gnangara) ---
        $item007 = LineItem::create([
            'cost_package_id' => $package006->id,
            'item_no' => '007',
            'description' => 'TL5 BARRIER 295-CB-001, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 98,
            'original_rate' => 342.00,
            'original_amount' => 33516.00,
            'sort_order' => 1,
        ]);

        $item008 = LineItem::create([
            'cost_package_id' => $package006->id,
            'item_no' => '008',
            'description' => 'TL5 BARRIER 295-CB-002, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 116,
            'original_rate' => 313.85,
            'original_amount' => 36406.60,
            'sort_order' => 2,
        ]);

        // --- Package 009 (Whiteman Park) ---
        $item010 = LineItem::create([
            'cost_package_id' => $package009->id,
            'item_no' => '010',
            'description' => 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER<0.7mm',
            'unit_of_measure' => 'LM',
            'original_qty' => 1730,
            'original_rate' => 291.75,
            'original_amount' => 504727.50,
            'sort_order' => 1,
        ]);

        $item012 = LineItem::create([
            'cost_package_id' => $package009->id,
            'item_no' => '012',
            'description' => 'TL5 BARRIER AV-CB-001, 1420MM HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 40,
            'original_rate' => 606.25,
            'original_amount' => 24250.00,
            'sort_order' => 2,
        ]);

        $itemGt07 = LineItem::create([
            'cost_package_id' => $package009->id,
            'item_no' => null,
            'description' => 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER>0.7mm',
            'unit_of_measure' => 'LM',
            'original_qty' => 88,
            'original_rate' => 345.25,
            'original_amount' => 30382.00,
            'sort_order' => 3,
        ]);

        $item013 = LineItem::create([
            'cost_package_id' => $package009->id,
            'item_no' => '013',
            'description' => 'TL5 BARRIER AV-CB-002, 1420MM HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 40,
            'original_rate' => 606.25,
            'original_amount' => 24250.00,
            'sort_order' => 4,
        ]);

        $itemTransition = LineItem::create([
            'cost_package_id' => $package009->id,
            'item_no' => null,
            'description' => 'TRANSITION FROM 1420MM TO 1070MM',
            'unit_of_measure' => 'LM',
            'original_qty' => 0,
            'original_rate' => 606.25,
            'original_amount' => 0,
            'sort_order' => 5,
        ]);

        // --- Package 3 (Dulwich/Cheltenham) ---
        $itemAx001 = LineItem::create([
            'cost_package_id' => $package3->id,
            'item_no' => 'VO-003.01',
            'description' => 'TL5 BARRIER AX-CB-001, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 83,
            'original_rate' => 345.25,
            'original_amount' => 28655.75,
            'sort_order' => 1,
        ]);

        $itemAx002 = LineItem::create([
            'cost_package_id' => $package3->id,
            'item_no' => 'VO-003.01',
            'description' => 'TL5 BARRIER AX-CB-002, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 78,
            'original_rate' => 345.25,
            'original_amount' => 26929.50,
            'sort_order' => 2,
        ]);

        $itemAx003 = LineItem::create([
            'cost_package_id' => $package3->id,
            'item_no' => '011',
            'description' => 'TL5 BARRIER AX-CB-003, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 88,
            'original_rate' => 345.25,
            'original_amount' => 30382.00,
            'sort_order' => 3,
        ]);

        $itemAx004 = LineItem::create([
            'cost_package_id' => $package3->id,
            'item_no' => 'VO-003.01',
            'description' => 'TL5 BARRIER AX-CB-004, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 42,
            'original_rate' => 345.25,
            'original_amount' => 14500.50,
            'sort_order' => 4,
        ]);

        $itemAx005 = LineItem::create([
            'cost_package_id' => $package3->id,
            'item_no' => 'VO-003.01',
            'description' => 'TL5 BARRIER AX-CB-005, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 74,
            'original_rate' => 345.25,
            'original_amount' => 25548.50,
            'sort_order' => 5,
        ]);

        $itemAx006 = LineItem::create([
            'cost_package_id' => $package3->id,
            'item_no' => 'VO-003.01',
            'description' => 'TL5 BARRIER AX-CB-006, 1070 HIGH BARRIER',
            'unit_of_measure' => 'LM',
            'original_qty' => 79,
            'original_rate' => 345.25,
            'original_amount' => 27274.75,
            'sort_order' => 6,
        ]);

        // 6. Line Item Forecasts for Jan 2024

        // Package 006 forecasts
        LineItemForecast::create([
            'line_item_id' => $item007->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_qty' => 98,
            'previous_rate' => 342,
            'previous_amount' => 33516,
            'ctd_qty' => 98,
            'ctd_rate' => 342,
            'ctd_amount' => 33516,
            'ctc_rate' => 342,
            'ctc_amount' => 0,
            'fcac_rate' => 342,
            'fcac_amount' => 33516,
            'variance' => 0,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item008->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_qty' => 116,
            'previous_rate' => 313.85,
            'previous_amount' => 36406.60,
            'ctd_qty' => 116,
            'ctd_rate' => 313.85,
            'ctd_amount' => 36406.60,
            'ctc_rate' => 313.85,
            'ctc_amount' => 0,
            'fcac_rate' => 313.85,
            'fcac_amount' => 36406.60,
            'variance' => 0,
        ]);

        // Package 009 forecasts
        LineItemForecast::create([
            'line_item_id' => $item010->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_qty' => 1730,
            'previous_rate' => 291.75,
            'previous_amount' => 504727.50,
            'ctd_qty' => 1730,
            'ctd_rate' => 291.75,
            'ctd_amount' => 504727.50,
            'ctc_rate' => 291.75,
            'ctc_amount' => 0,
            'fcac_rate' => 291.75,
            'fcac_amount' => 504727.50,
            'variance' => 0,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item012->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_amount' => 24250,
            'ctd_amount' => 0,
            'ctc_qty' => 40,
            'ctc_rate' => 606.25,
            'ctc_amount' => 24250,
            'fcac_rate' => 606.25,
            'fcac_amount' => 24250,
            'variance' => 0,
            'comments' => 'Done by SVG',
        ]);

        LineItemForecast::create([
            'line_item_id' => $itemGt07->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_amount' => 30382,
            'ctd_qty' => 88,
            'ctd_amount' => 30382,
            'ctc_amount' => 0,
            'fcac_rate' => 345.25,
            'fcac_amount' => 30382,
            'variance' => 0,
        ]);

        LineItemForecast::create([
            'line_item_id' => $item013->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_amount' => 24250,
            'ctd_amount' => 0,
            'ctc_qty' => 40,
            'ctc_rate' => 606.25,
            'ctc_amount' => 24250,
            'fcac_rate' => 606.25,
            'fcac_amount' => 24250,
            'variance' => 0,
            'comments' => 'Done by SVG',
        ]);

        LineItemForecast::create([
            'line_item_id' => $itemTransition->id,
            'forecast_period_id' => $janPeriod->id,
            'previous_amount' => 0,
            'ctd_amount' => 0,
            'ctc_amount' => 0,
            'fcac_rate' => 606.25,
            'fcac_amount' => 0,
            'variance' => 0,
        ]);

        // Package 3 forecasts (all have ctd matching previous, ctc_amount:0, variance:0)
        $package3Data = [
            ['item' => $itemAx001, 'amount' => 28655.75, 'qty' => 83],
            ['item' => $itemAx002, 'amount' => 26929.50, 'qty' => 78],
            ['item' => $itemAx003, 'amount' => 30382.00, 'qty' => 88],
            ['item' => $itemAx004, 'amount' => 14500.50, 'qty' => 42],
            ['item' => $itemAx005, 'amount' => 25548.50, 'qty' => 74],
            ['item' => $itemAx006, 'amount' => 27274.75, 'qty' => 79],
        ];

        foreach ($package3Data as $data) {
            LineItemForecast::create([
                'line_item_id' => $data['item']->id,
                'forecast_period_id' => $janPeriod->id,
                'previous_qty' => $data['qty'],
                'previous_rate' => 345.25,
                'previous_amount' => $data['amount'],
                'ctd_qty' => $data['qty'],
                'ctd_rate' => 345.25,
                'ctd_amount' => $data['amount'],
                'ctc_rate' => 345.25,
                'ctc_amount' => 0,
                'fcac_rate' => 345.25,
                'fcac_amount' => $data['amount'],
                'variance' => 0,
            ]);
        }

        // 7. Create control accounts
        $ca401AN = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401AN00',
            'description' => 'Line Wide - Civil - Anti Graffiti',
            'category' => '401C - Civil',
            'baseline_budget' => 339264,
            'approved_budget' => 790194,
            'sort_order' => 1,
        ]);

        $ca401AS = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401AS00',
            'description' => 'Line Wide - Civil - Asphalt',
            'category' => '401C - Civil',
            'baseline_budget' => 2591182,
            'approved_budget' => 9496972,
            'sort_order' => 2,
        ]);

        $ca401BE = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401BE00',
            'description' => 'Line Wide - Civil - Bearing Install',
            'category' => '401S - Structure',
            'baseline_budget' => 282073,
            'approved_budget' => 38756,
            'sort_order' => 3,
        ]);

        $ca401BS = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401BS00',
            'description' => 'Line Wide - Civil - Bearing Supply',
            'category' => '401S - Structure',
            'baseline_budget' => 369233,
            'approved_budget' => 630522,
            'sort_order' => 4,
        ]);

        // 8. Control account forecasts for Jan 2024
        ControlAccountForecast::create([
            'control_account_id' => $ca401AN->id,
            'forecast_period_id' => $janPeriod->id,
            'monthly_cost' => -61888,
            'cost_to_date' => 466750,
            'estimate_to_complete' => 525603,
            'estimated_final_cost' => 992352,
            'last_month_efc' => 948803,
            'efc_movement' => 43549,
            'monthly_comments' => "SCOPE GROWTH\n\$12k - allowance for Anti Graffiti Removal Line wide for re-application of new anti graffiti paint. (20 Visits @ \$600)\n\$18k - for Additional Whiteman Park Underpass Scope gap and Night works allowance to complete it\nCLIENT/STAKEHOLDER\n\$14K - Cheltham St Fencing 2x Layer paint for residents",
        ]);

        ControlAccountForecast::create([
            'control_account_id' => $ca401AS->id,
            'forecast_period_id' => $janPeriod->id,
            'monthly_cost' => -7838,
            'cost_to_date' => 7517034,
            'estimate_to_complete' => 3752696,
            'estimated_final_cost' => 11269730,
            'last_month_efc' => 10343490,
            'efc_movement' => 926240,
            'monthly_comments' => 'CC',
        ]);

        ControlAccountForecast::create([
            'control_account_id' => $ca401BE->id,
            'forecast_period_id' => $janPeriod->id,
            'monthly_cost' => 0,
            'cost_to_date' => 0,
            'estimate_to_complete' => 0,
            'estimated_final_cost' => 0,
            'last_month_efc' => 0,
            'efc_movement' => 0,
        ]);

        ControlAccountForecast::create([
            'control_account_id' => $ca401BS->id,
            'forecast_period_id' => $janPeriod->id,
            'monthly_cost' => 0,
            'cost_to_date' => 497154,
            'estimate_to_complete' => 0,
            'estimated_final_cost' => 497154,
            'last_month_efc' => 497154,
            'efc_movement' => 0,
        ]);
    }
}
