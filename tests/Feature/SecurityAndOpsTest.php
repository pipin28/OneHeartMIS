<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SecurityAndOpsTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_invalidates_authenticated_session(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'auth.logout',
            'entity_type' => 'user',
            'entity_id' => $user->id,
        ]);
    }

    public function test_non_admin_user_cannot_update_member_records(): void
    {
        $encoder = User::factory()->create(['role' => 'encoder']);

        $response = $this
            ->actingAs($encoder)
            ->post(route('members.update', ['part2' => 1]), ['section' => 'member']);

        $response->assertForbidden();
    }

    public function test_sync_status_command_updates_overdue_and_part1_status(): void
    {
        $part1Id = DB::table('part1s')->insertGetId([
            'member_assignment_id' => null,
            'user_id' => 101,
            'created_by_user_id' => null,
            'lpaf_no' => 123456,
            'application_date' => now()->toDateString(),
            'sales_counselor_code' => 'SC-001',
            'plan_type' => 'Serenity Care',
            'gross_contact_price' => 30000,
            'mode_of_payment' => 'Monthly',
            'terms_of_payment' => '60 months (5 years)',
            'due_date' => now()->toDateString(),
            'amount' => 500,
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payments')->insert([
            [
                'part1_id' => $part1Id,
                'part2_id' => 0,
                'due_date' => now()->subDay()->toDateString(),
                'amount' => 500,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'part1_id' => $part1Id,
                'part2_id' => 0,
                'due_date' => now()->addDay()->toDateString(),
                'amount' => 500,
                'status' => 'overdue',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Artisan::call('payments:sync-status');

        $this->assertDatabaseHas('payments', [
            'part1_id' => $part1Id,
            'due_date' => now()->subDay()->toDateString(),
            'status' => 'overdue',
        ]);
        $this->assertDatabaseHas('payments', [
            'part1_id' => $part1Id,
            'due_date' => now()->addDay()->toDateString(),
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('part1s', [
            'id' => $part1Id,
            'payment_status' => 'overdue',
        ]);
    }
}
