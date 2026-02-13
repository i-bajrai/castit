<?php

namespace Domain\Forecasting\Actions;

use App\Enums\CompanyRole;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ControlAccount;
use App\Models\CostPackage;
use App\Models\LineItem;
use App\Models\LineItemForecast;
use App\Models\Project;
use App\Models\User;

class SeedDemoProject
{
    public function __construct(
        private SyncForecastPeriods $syncForecastPeriods,
    ) {}

    public function execute(): Project
    {
        // 1. User & Company — find or create
        $user = User::firstOrCreate(
            ['email' => 'demo@castit.com'],
            ['name' => 'Demo User', 'password' => 'password'],
        );

        $company = Company::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => 'CastIt Construction'],
        );

        $user->update([
            'company_id' => $company->id,
            'company_role' => CompanyRole::Admin,
        ]);

        // Company members
        User::firstOrCreate(
            ['email' => 'engineer@castit.com'],
            [
                'name' => 'Engineer',
                'password' => 'password',
                'role' => UserRole::User,
                'company_id' => $company->id,
                'company_role' => CompanyRole::Engineer,
                'email_verified_at' => now(),
            ],
        );

        User::firstOrCreate(
            ['email' => 'viewer@castit.com'],
            [
                'name' => 'Viewer',
                'password' => 'password',
                'role' => UserRole::User,
                'company_id' => $company->id,
                'company_role' => CompanyRole::Viewer,
                'email_verified_at' => now(),
            ],
        );

        // Delete existing projects for this company so it's re-runnable
        $company->projects()->each(function (Project $p): void {
            $p->delete();
        });

        // 2. Project — end date extends to current month so it's always editable
        $endDate = max(
            now()->startOfMonth(),
            \Carbon\Carbon::parse('2024-01-01'),
        );

        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'PRISM Highway Extension',
            'original_budget' => 248117066,
            'start_date' => '2023-02-01',
            'end_date' => $endDate->format('Y-m-d'),
        ]);

        // 3. Control Account: 401CB00
        $ca = ControlAccount::create([
            'project_id' => $project->id,
            'phase' => '4 - Construction',
            'code' => '401CB00',
            'description' => 'Line Wide - Civil - Concrete Barriers',
            'category' => '401S - Structure',
            'baseline_budget' => 962977,
            'approved_budget' => 1098258,
            'sort_order' => 1,
        ]);

        // ============================================================
        // ORIGINAL BUDGET ($753,182.45)
        // ============================================================

        $pkg006 = $this->createPackage($ca, $project, '006', 'GNANGARA INTERSECTION (DESIGN PACKAGE 02 - E039)', 1);
        $items006 = $this->createLineItems($pkg006, [
            ['007', 'TL5 BARRIER 295-CB-001, 1070 HIGH BARRIER', 'LM', 0, 342.00, 0],
            ['008', 'TL5 BARRIER 295-CB-002, 1070 HIGH BARRIER', 'LM', 0, 313.85, 0],
        ]);

        $pkg009 = $this->createPackage($ca, $project, '009', 'WHITEMAN PARK (DESIGN PACKAGE 04 - E050)', 2);
        $items009 = $this->createLineItems($pkg009, [
            ['010', 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER<0.7mm', 'LM', 1737, 291.75, 506769.75],
            ['012', 'TL5 BARRIER AV-CB-001, 1420MM HIGH BARRIER', 'LM', 40, 606.25, 24250.00],
            [null, 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER>0.7mm', 'LM', 88, 345.25, 30382.00],
            ['013', 'TL5 BARRIER AV-CB-002, 1420MM HIGH BARRIER', 'LM', 40, 606.25, 24250.00],
            [null, 'TRANSITION FROM 1420MM TO 1070MM', 'LM', 0, 606.25, 0],
        ]);

        $pkg003 = $this->createPackage($ca, $project, '3', 'Dulwich / Cheltenham Street', 3);
        $itemsDulwich = $this->createLineItems($pkg003, [
            ['VO-003.01', 'TL5 BARRIER AX-CB-001, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.01', 'TL5 BARRIER AX-CB-002, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['011', 'TL5 BARRIER AX-CB-003, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.01', 'TL5 BARRIER AX-CB-004, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.01', 'TL5 BARRIER AX-CB-005, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.01', 'TL5 BARRIER AX-CB-005, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['014', 'W-BEAM TO CONCRETE TRANSITION', 'Each', 0, 6305.00, 0],
        ]);

        $pkg004 = $this->createPackage($ca, $project, '4', 'Beechboro Road North', 4);
        $itemsBeechboro = $this->createLineItems($pkg004, [
            ['VO-003.03', 'TL5 BARRIER AA-CB-001, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.03', 'TL5 BARRIER AA-CB-002, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.03', 'TL5 BARRIER AA-CB-003, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            ['VO-003.03', 'TL5 BARRIER AA-CB-004, 1070 HIGH BARRIER', 'LM', 0, 345.25, 0],
            [null, 'W-BEAM TO CONCRETE TRANSITION', 'Each', 0, 6305.00, 0],
        ]);

        $pkgBtB = $this->createPackage($ca, $project, 'BtB', 'SA5517B - W-BEAM TO CONCRETE BARRIER, TYPE T5', 5);
        $itemsBtB = $this->createLineItems($pkgBtB, [
            ['014', 'W-BEAM TO CONCRETE TRANSITION (3X DUMPLIER)', 'Each', 3, 6305.00, 18915.00],
            ['015', 'W-BEAM TO CONCRETE TRANSITION - GNANGARA', 'Each', 2, 6305.00, 12610.00],
            ['1.9', 'SQUARE END (SITUATED BEHIND W-BEAM)', 'Each', 2, 1650.00, 3300.00],
            ['1.10', 'TL5 BLUNT END', 'Each', 2, 941.55, 1883.10],
            ['1.11', 'TL5 1400 TRANSITION TO W-BEAM (5MTRS)', 'Each', 0, 8245.00, 0],
        ]);

        $pkgPrelim = $this->createPackage($ca, $project, '2', 'PRELIMINARIES', 6);
        $itemsPrelim = $this->createLineItems($pkgPrelim, [
            ['002', 'MOBILISATION', 'Ea', 4, 6400.00, 25600.00],
            ['003', 'DEMOBILISATION', 'EA', 4, 6400.00, 25600.00],
            [null, 'QUALITY DOCUMENTATION', 'Lump Sum', 1, 0, 0],
        ]);

        $pkgOther = $this->createPackage($ca, $project, '3', 'OTHER', 7);
        $itemsOther = $this->createLineItems($pkgOther, [
            ['004', 'INTERNAL MOBILIZATION', 'EA', 4, 2425.00, 9700.00],
            ['005', 'MEDICALS AND INDUCTIONS PER PERSON', 'EA', 4, 625.00, 2500.00],
            [null, 'CONCRETE CORRECTOR', 'Cu. M', 0, 245.00, 0],
        ]);

        // ============================================================
        // VARIATION RATES ($361,125.52)
        // ============================================================

        $pkgVar = $this->createPackage($ca, $project, '4', 'VARIATION RATES', 8);
        $itemsVar = $this->createLineItems($pkgVar, [
            ['4.1', 'STANDARD SLIPFORM TL5 CONCRETE BARRIER', 'LM', 0, 291.75, 0],
            [null, 'CONCRETE SUPPLY', 'Each', 0, 245.00, 0],
            ['4.3', 'PLANT OPENING FEE FOR OUTSIDE NORMAL HOURS', 'LM', 0, 2500.00, 0],
            ['4.4', 'NIGHT SHIFT / SUNDAY SURCHARGE', 'LM', 0, 0, 0],
            ['VO-005.02', 'Beechboro Radius curve barrier TL5', 'LM', 1, 27500.00, 27500.00],
            ['VO-005.01', 'SA5517B - W-BEAM TO CONCRETE BARRIER, TYPE T5', 'LM', 15, 1650.00, 24750.00],
            ['VO-006', 'Concrete Barrier TL5 1270mm - AS per SIN-181-KER-00008', 'LM', 0, 0, 0],
        ]);

        $pkgUnlet = $this->createPackage($ca, $project, null, 'UNLET SCOPE', 9);
        $itemsUnlet = $this->createLineItems($pkgUnlet, [
            [null, 'BAYSWATER TL5 CONCRETE BARRIER ALLOWANCE ON AD DESIGN', 'LM', 362, 413.56, 149683.24],
            [null, 'BEECHBORO RD TL5 CONCRETE BARRIER ALLOWANCE ON AD DESIGN', 'LM', 80, 413.57, 32973.70],
            [null, 'BENNETT SPRINGS TL5 CONCRETE BARRIER ALLOWANCE ON AD DESIGN', 'LM', 159, 413.56, 65730.47],
            [null, 'May23 Approved Budget adjustment since November 2022', 'Lump Sum', 1, 64677.61, 64677.61],
        ]);

        $pkgSlipform = $this->createPackage($ca, $project, null, 'Slipform Barriers', 10);
        $this->createLineItems($pkgSlipform, [
            [null, 'San Lorenzo - ~16m in-situ Barrier', 'LM', 16, 538.00, 8608.00],
            [null, 'Tonkin Hwy N/B Noranda South - ~54m 1420mm TL5 Barrier (TGA spec)', 'LM', 54, 538.00, 29052.00],
            [null, 'Tonkin Hwy N/B Noranda North - ~40m 1420mm TL5 Barrier (TGA spec)', 'LM', 40, 538.00, 21520.00],
            [null, 'San Lorenzo - backfill limestone upto 2m', 'Lump Sum', 1, 10650.00, 10650.00],
            [null, 'Mob and Demob', 'Lump Sum', 1, 0, 0],
            [null, 'Tonkin Hwy NB Noranda South backfill of limestone upto 54m', 'Lump Sum', 1, 5500.00, 5500.00],
            [null, 'Tonkin Hwy NB Noranda North backfill of limestone upto 40m', 'Lump Sum', 1, 5740.00, 5740.00],
            [null, 'Cleaning at San Lorenzo after finishing barriers', 'per day', 0, 81.00, 0],
            [null, 'Cleaning at Tonkin Highway North Bound Noranda South', 'per day', 0, 81.00, 0],
            [null, 'Cleaning at Tonkin Highway North Bound Noranda North', 'per day', 0, 81.00, 0],
        ]);

        $pkgOtherCA = $this->createPackage($ca, $project, 'VO-001', 'Other Control Account', 11);
        $this->createLineItems($pkgOtherCA, [
            ['VO-001/401AN00', 'ANTI-GRAFFITI COATING ON THE TL-5 ON THE EGG-FARM DRIVEWAY - 401AN00', 'LM', 266, 15.75, -4189.50],
        ]);

        $pkgSubcon = $this->createPackage($ca, $project, null, 'Other Subcontractor - costed against 401CB00', 12);
        $this->createLineItems($pkgSubcon, [
            ['401CB00', 'SK97/00134 TALCO GROUP PTY LTD - Transition Gnangara - Beechboro', 'Lump Sum', 0, 0, 0],
            ['401CB00', 'SK97/00066 PROTECH PERSONNEL (WA) PTY LTD', 'Lump Sum', 0, 0, 0],
            ['401CB00', 'Select Plant Hire Internal Charges/Accruals', 'Lump Sum', 0, 0, 0],
            ['401CB00', 'Internal Charges/Accruals', 'Lump Sum', 0, 0, 0],
        ]);

        // ============================================================
        // 4. Sync all forecast periods (prepopulates with zeros)
        // ============================================================
        $this->syncForecastPeriods->execute($project);

        // ============================================================
        // 5. Apply monthly CTD data from CSV
        // ============================================================
        $this->applyCsvForecasts($project, $items006, $itemsDulwich, $itemsBeechboro, $itemsPrelim, $itemsOther);

        return $project;
    }

    private function createPackage(
        ControlAccount $ca,
        Project $project,
        ?string $itemNo,
        string $name,
        int $sortOrder,
    ): CostPackage {
        return $ca->costPackages()->create([
            'project_id' => $project->id,
            'item_no' => $itemNo,
            'name' => $name,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array<int, array{0: ?string, 1: string, 2: string, 3: int|float, 4: float, 5: float}>  $rows
     * @return array<int, LineItem>
     */
    private function createLineItems(CostPackage $package, array $rows): array
    {
        $items = [];
        foreach ($rows as $i => $row) {
            $items[] = LineItem::create([
                'cost_package_id' => $package->id,
                'item_no' => $row[0],
                'description' => $row[1],
                'unit_of_measure' => $row[2],
                'original_qty' => $row[3],
                'original_rate' => $row[4],
                'original_amount' => $row[5],
                'sort_order' => $i + 1,
            ]);
        }

        return $items;
    }

    /**
     * @param  array<int, LineItem>  $items006
     * @param  array<int, LineItem>  $itemsDulwich
     * @param  array<int, LineItem>  $itemsBeechboro
     * @param  array<int, LineItem>  $itemsPrelim
     * @param  array<int, LineItem>  $itemsOther
     */
    private function applyCsvForecasts(Project $project, array $items006, array $itemsDulwich, array $itemsBeechboro, array $itemsPrelim, array $itemsOther): void
    {
        $allItems = LineItem::whereHas('costPackage', fn ($q) => $q->where('project_id', $project->id))->get();
        $periods = $project->forecastPeriods()->orderBy('period_date')->get();

        // Build description → item lookup
        $lookup = [];
        foreach ($allItems as $item) {
            if (! isset($lookup[$item->description])) {
                $lookup[$item->description] = $item;
            }
        }

        // Override duplicates with explicit references
        $lookup['W-BEAM TO CONCRETE TRANSITION'] = $itemsDulwich[6];
        $lookup['__beechboro_wbeam__'] = $itemsBeechboro[4];

        // Alias map: CSV description → lookup key
        $aliases = [
            'BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER' => 'TL5 BARRIER AV-CB-003 ALONG DRUMPELLIER DR, 1070 HIGH BARRIER<0.7mm',
            'ANTI-GRAFFITI COATING ON THE TL-5 ON THE EGG-FARM DRIVEWAY' => 'ANTI-GRAFFITI COATING ON THE TL-5 ON THE EGG-FARM DRIVEWAY - 401AN00',
            'W-BEAM TO CONCRETE TRANSITION(3X DUMPLIER)' => 'W-BEAM TO CONCRETE TRANSITION (3X DUMPLIER)',
            'W-BEAM TO CONCRETE TRANSITION- GNANGARA' => 'W-BEAM TO CONCRETE TRANSITION - GNANGARA',
            'W-BEAM TO CONCRETE TRANSITION (Beechboro)' => '__beechboro_wbeam__',
            'SK97/00134 TALCO GROUP PTY LTD- Transition Gnangara - Beechboro' => 'SK97/00134 TALCO GROUP PTY LTD - Transition Gnangara - Beechboro',
            'Concrete Barrier TL5 1270mm- AS per SIN-181-KER-00008' => 'Concrete Barrier TL5 1270mm - AS per SIN-181-KER-00008',
            'SA5517B -  W-BEAM TO CONCRETE BARRIER, TYPE T5' => 'SA5517B - W-BEAM TO CONCRETE BARRIER, TYPE T5',
        ];

        // References for duplicate "AX-CB-005" items (first=qty74, second=qty79)
        $axCb005First = $itemsDulwich[4];
        $axCb005Second = $itemsDulwich[5];

        // Parse CSV grouped by period
        $csvData = [];
        $handle = fopen(base_path('data/401CB00-ctd-feb2023.csv'), 'r');
        fgetcsv($handle); // skip header
        while (($row = fgetcsv($handle)) !== false) {
            $csvData[$row[1]][] = ['description' => $row[0], 'ctd_qty' => (float) $row[2]];
        }
        fclose($handle);

        // Bulk set fcac, rates, and previous values for all forecasts
        foreach ($allItems as $item) {
            LineItemForecast::where('line_item_id', $item->id)->update([
                'fcac_qty' => $item->original_qty,
                'fcac_rate' => $item->original_rate,
                'ctd_rate' => $item->original_rate,
                'ctc_rate' => $item->original_rate,
                'previous_qty' => $item->original_qty,
                'previous_rate' => $item->original_rate,
            ]);
        }

        // FCAC overrides for variation items (original_qty=0 but forecast is non-zero)
        $fcacOverrides = [
            // Items 007/008 (Gnangara)
            [$items006[0], 103],
            [$items006[1], 120],
            // Dulwich
            [$itemsDulwich[0], 83],
            [$itemsDulwich[1], 78],
            [$itemsDulwich[2], 85],
            [$itemsDulwich[3], 45],
            [$itemsDulwich[4], 74],
            [$itemsDulwich[5], 79],
            [$itemsDulwich[6], 4],
            // Beechboro
            [$itemsBeechboro[0], 45],
            [$itemsBeechboro[1], 46],
            [$itemsBeechboro[2], 45],
            [$itemsBeechboro[3], 53],
            [$itemsBeechboro[4], 4],
            // Prelim / Other with changed FCAC
            [$itemsPrelim[0], 6],  // MOBILISATION
            [$itemsPrelim[1], 6],  // DEMOBILISATION
            [$itemsOther[1], 5],   // MEDICALS
            [$itemsOther[2], 267.3], // CONCRETE CORRECTOR
        ];

        foreach ($fcacOverrides as [$item, $fcacQty]) {
            LineItemForecast::where('line_item_id', $item->id)->update([
                'fcac_qty' => $fcacQty,
                'fcac_rate' => $item->original_rate,
                'previous_qty' => $fcacQty,
                'previous_rate' => $item->original_rate,
            ]);
        }

        // Apply CTD from CSV per period
        foreach ($csvData as $periodKey => $rows) {
            $period = $periods->first(fn ($p) => $p->period_date->format('Y-m') === $periodKey);
            if (! $period) {
                continue;
            }

            $axCb005Count = 0;

            foreach ($rows as $row) {
                $desc = $row['description'];

                // Handle duplicate AX-CB-005
                if ($desc === 'TL5 BARRIER AX-CB-005, 1070 HIGH BARRIER') {
                    $item = $axCb005Count === 0 ? $axCb005First : $axCb005Second;
                    $axCb005Count++;
                } else {
                    $lookupKey = $aliases[$desc] ?? $desc;
                    $item = $lookup[$lookupKey] ?? null;
                }

                if (! $item) {
                    continue;
                }

                LineItemForecast::where('line_item_id', $item->id)
                    ->where('forecast_period_id', $period->id)
                    ->update(['ctd_qty' => $row['ctd_qty']]);
            }
        }

        // Apply comments to the last period (Jan 2024) matching the spreadsheet
        $lastHistorical = $periods->first(fn ($p) => $p->period_date->format('Y-m') === '2024-01');
        if ($lastHistorical) {
            $comments = [
                'MOBILISATION' => 'Additional Mob at Gnangara and whiteman park',
                'DEMOBILISATION' => 'Additional Mob at Gnangara and whiteman park',
                'MEDICALS AND INDUCTIONS PER PERSON' => 'Extra concrete charges due to the footing being deeper than 200mm allowed on the contract.',
                'ANTI-GRAFFITI COATING ON THE TL-5 ON THE EGG-FARM DRIVEWAY - 401AN00' => 'Cost to be transferred onto the Anti-Graffiti Contract',
            ];

            foreach ($comments as $desc => $comment) {
                $item = $lookup[$desc] ?? null;
                if ($item) {
                    LineItemForecast::where('line_item_id', $item->id)
                        ->where('forecast_period_id', $lastHistorical->id)
                        ->update(['comments' => $comment]);
                }
            }
        }
    }
}
