<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PercentageController extends Controller
{
    /**
     * Standalone percentage calculator (not tied to member payments).
     */
    public function index()
    {
        $defaults = [
            'collector' => 10, // percent
            'agent' => 5,
            'manager' => 5,
            'others' => 10,
        ];

        $plans = $this->loadPlanSettings();
        $percentages = $this->loadPercentageSettings();

        return view('settings', [
            'payments' => collect(), // intentionally empty; calculator is manual
            'defaults' => $defaults,
            'plans' => $plans,
            'percentages' => $percentages,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.contract_amount' => ['required', 'integer', 'min:0'],
            'plans.*.default_mode' => ['required', 'string', 'max:50'],
            'plans.*.default_terms' => ['required', 'string', 'max:255'],
            'plans.*.default_months' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($data) {
            foreach ($data['plans'] as $name => $meta) {
                $contract = (int) ($meta['contract_amount'] ?? 0);
                $months = max(1, (int) $meta['default_months']);
                $mode = strtolower(trim((string) ($meta['default_mode'] ?? 'monthly')));
                $factor = match ($mode) {
                    'quarterly' => 3,
                    'semi-annual', 'semi annual' => 6,
                    'annual', 'yearly' => 12,
                    'one-time', 'one time', 'one_time' => $months,
                    default => 1,
                };
                $premium = $mode === 'one-time' || $mode === 'one time' || $mode === 'one_time'
                    ? $contract
                    : (int) round(($contract / $months) * $factor);
                DB::table('plan_settings')->updateOrInsert(
                    ['name' => $name],
                    [
                        'contract_amount' => $contract,
                        'premium_amount' => $premium,
                        'default_mode' => $meta['default_mode'],
                        'default_terms' => $meta['default_terms'],
                        'default_months' => $months,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return Redirect::route('settings')->with('status', 'Plan settings updated.');
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:plan_settings,name'],
            'contract_amount' => ['required', 'integer', 'min:0'],
            'default_mode' => ['required', 'string', 'max:50'],
            'default_terms' => ['required', 'string', 'max:255'],
            'default_months' => ['required', 'integer', 'min:1'],
        ]);

        $contract = (int) $data['contract_amount'];
        $months = max(1, (int) $data['default_months']);
        $mode = strtolower(trim((string) $data['default_mode']));
        $factor = match ($mode) {
            'quarterly' => 3,
            'semi-annual', 'semi annual' => 6,
            'annual', 'yearly' => 12,
            'one-time', 'one time', 'one_time' => $months,
            default => 1,
        };
        $premium = $mode === 'one-time' || $mode === 'one time' || $mode === 'one_time'
            ? $contract
            : (int) round(($contract / $months) * $factor);

        DB::table('plan_settings')->insert([
            'name' => $data['name'],
            'contract_amount' => $contract,
            'premium_amount' => $premium,
            'default_mode' => $data['default_mode'],
            'default_terms' => $data['default_terms'],
            'default_months' => $months,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Redirect::route('settings')->with('status', 'Plan added.');
    }

    public function deletePlan(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        DB::table('plan_settings')->where('name', $data['name'])->delete();

        return Redirect::route('settings')->with('status', 'Plan deleted.');
    }

    public function updatePlan(Request $request)
    {
        $data = $request->validate([
            'original_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'contract_amount' => ['required', 'integer', 'min:0'],
            'default_mode' => ['required', 'string', 'max:50'],
            'default_terms' => ['required', 'string', 'max:255'],
            'default_months' => ['required', 'integer', 'min:1'],
        ]);

        $contract = (int) $data['contract_amount'];
        $months = max(1, (int) $data['default_months']);
        $mode = strtolower(trim((string) $data['default_mode']));
        $factor = match ($mode) {
            'quarterly' => 3,
            'semi-annual', 'semi annual' => 6,
            'annual', 'yearly' => 12,
            'one-time', 'one time', 'one_time' => $months,
            default => 1,
        };
        $premium = $mode === 'one-time' || $mode === 'one time' || $mode === 'one_time'
            ? $contract
            : (int) round(($contract / $months) * $factor);

        DB::table('plan_settings')->where('name', $data['original_name'])->update([
            'name' => $data['name'],
            'contract_amount' => $contract,
            'premium_amount' => $premium,
            'default_mode' => $data['default_mode'],
            'default_terms' => $data['default_terms'],
            'default_months' => $months,
            'updated_at' => now(),
        ]);

        return Redirect::route('settings')->with('status', 'Plan updated.');
    }

    public function updatePercentages(Request $request)
    {
        $data = $request->validate([
            'percentages' => ['required', 'array'],
            'percentages.*.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'mode' => ['nullable', 'string', 'max:50'],
        ]);

        $roles = ['collector', 'agent', 'manager', 'others'];
        $allModes = ['Monthly', 'Quarterly', 'Semi-Annual', 'Annual'];
        $modes = $data['mode'] && in_array($data['mode'], $allModes, true) ? [$data['mode']] : $allModes;
        $tiersByMode = [
            'Monthly' => ['first_month', 'months_2_12', 'months_13_60'],
            'Quarterly' => ['first_quarter', 'quarters_2_4', 'quarters_5_20'],
            'Semi-Annual' => ['first_semi', 'semis_2_10', 'semis_11_30'],
            'Annual' => ['first_year', 'years_2_5', 'years_6_10'],
        ];

        DB::transaction(function () use ($data, $roles, $modes, $tiersByMode) {
            foreach ($modes as $mode) {
                $tiers = $tiersByMode[$mode] ?? [];
                foreach ($roles as $role) {
                    foreach ($tiers as $tier) {
                        $value = $data['percentages'][$mode][$role][$tier] ?? null;
                        $percent = $value === '' || $value === null ? null : (float) $value;
                        DB::table('percentage_settings')->updateOrInsert(
                            ['mode' => $mode, 'role' => $role, 'tier' => $tier],
                            [
                                'percent' => $percent,
                                'updated_at' => now(),
                                'created_at' => now(),
                            ]
                        );
                    }
                }
            }
        });

        return Redirect::route('settings')->with('status', 'Percentage settings updated.');
    }

    private function loadPlanSettings(): array
    {
        $rows = DB::table('plan_settings')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [
                'Serenity Care' => [
                    'contract_amount' => 30000,
                    'premium_amount' => 500,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months (5 years)',
                    'default_months' => 60,
                ],
                'Everlasting Care' => [
                    'contract_amount' => 20000,
                    'premium_amount' => 350,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months (5 years)',
                    'default_months' => 60,
                ],
                'Legacy Care' => [
                    'contract_amount' => 30000,
                    'premium_amount' => 0,
                    'default_mode' => 'One-time',
                    'default_terms' => 'Infinite',
                    'default_months' => 1,
                ],
            ];
        }

        $plans = [];
        foreach ($rows as $row) {
            $plans[$row->name] = [
                'contract_amount' => (int) $row->contract_amount,
                'premium_amount' => (int) $row->premium_amount,
                'default_mode' => $row->default_mode,
                'default_terms' => $row->default_terms,
                'default_months' => (int) $row->default_months,
            ];
        }

        return $plans;
    }

    private function loadPercentageSettings(): array
    {
        $rows = DB::table('percentage_settings')->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->mode][$row->role][$row->tier] = $row->percent;
        }
        return $settings;
    }
}
