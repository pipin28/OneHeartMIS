<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMemberAgePricingCommand extends Command
{
    protected $signature = 'members:sync-age-pricing';

    protected $description = 'Update member ages, age categories, and unpaid contribution amounts from birthdates';

    public function handle(): int
    {
        $updatedMembers = 0;
        $updatedPayments = 0;

        DB::table('part2s')
            ->whereNotNull('date_of_birth')
            ->orderBy('id')
            ->chunkById(100, function ($members) use (&$updatedMembers, &$updatedPayments) {
                foreach ($members as $member) {
                    $part1 = DB::table('part1s')
                        ->where('id', $member->part1_id)
                        ->whereNull('claimed_at')
                        ->first();

                    if (! $part1) {
                        continue;
                    }

                    $age = Carbon::parse($member->date_of_birth)->age;
                    $pricing = $this->resolveAgeCategoryPricing($age);
                    $baseAmount = (float) $pricing['amount'];
                    $modeAmount = $this->contributionAmount($baseAmount, $part1->mode_of_payment ?? 'Monthly');

                    $memberNeedsUpdate = (int) ($member->age ?? 0) !== $age;
                    $planNeedsUpdate = ($part1->plan_type ?? '') !== $pricing['category']
                        || (float) ($part1->amount ?? 0) !== $baseAmount
                        || (float) ($part1->gross_contact_price ?? 0) !== $baseAmount;

                    if (! $memberNeedsUpdate && ! $planNeedsUpdate) {
                        continue;
                    }

                    DB::transaction(function () use ($member, $part1, $age, $pricing, $baseAmount, $modeAmount, &$updatedPayments) {
                        DB::table('part2s')
                            ->where('id', $member->id)
                            ->update([
                                'age' => $age,
                                'updated_at' => now(),
                            ]);

                        DB::table('part1s')
                            ->where('id', $part1->id)
                            ->update([
                                'plan_type' => $pricing['category'],
                                'gross_contact_price' => $baseAmount,
                                'amount' => $baseAmount,
                                'updated_at' => now(),
                            ]);

                        $updatedPayments += DB::table('payments')
                            ->where('part1_id', $part1->id)
                            ->where(function ($query) {
                                $query->where('payment_type', 'regular')
                                    ->orWhereNull('payment_type');
                            })
                            ->whereIn('status', ['pending', 'overdue'])
                            ->update([
                                'amount' => $modeAmount,
                                'updated_at' => now(),
                            ]);
                    });

                    $updatedMembers++;
                }
            });

        $this->info("Members synced: {$updatedMembers}");
        $this->info("Unpaid contribution rows repriced: {$updatedPayments}");

        return self::SUCCESS;
    }

    private function resolveAgeCategoryPricing(int $age): array
    {
        $category = match (true) {
            $age >= 81 => 'Age 81 above',
            $age >= 71 => 'Age 71 to 80',
            $age >= 66 => 'Age 66 to 70',
            default => 'Age 60 to 65',
        };

        $amount = (int) (DB::table('plan_settings')
            ->where('name', $category)
            ->value('contract_amount') ?? match ($category) {
                'Age 81 above' => 200,
                'Age 71 to 80' => 150,
                'Age 66 to 70' => 120,
                default => 100,
            });

        return [
            'category' => $category,
            'amount' => max(0, $amount),
        ];
    }

    private function contributionAmount(float $baseAmount, ?string $mode): float
    {
        $factor = match ($this->normalizeContributionMode($mode)) {
            'quarterly' => 3,
            'semi-annual' => 6,
            'annual' => 12,
            default => 1,
        };

        return max(0, round($baseAmount * $factor, 2));
    }

    private function normalizeContributionMode(?string $mode): string
    {
        return match (strtolower(trim((string) $mode))) {
            'semi annual' => 'semi-annual',
            'yearly' => 'annual',
            default => strtolower(trim((string) $mode)),
        };
    }
}
