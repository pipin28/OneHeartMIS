<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\StoreBeneficiariesRequest;
use App\Http\Requests\StoreMemberAssignmentRequest;
use App\Http\Requests\StorePart1Request;
use App\Http\Requests\StorePart2Request;
use App\Services\PaymentLifecycleService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MemberController extends Controller
{
    public function showStaff(Request $request)
    {
        $assignmentId = $request->query('assignment');
        $assignment = null;

        if ($assignmentId) {
            $assignment = DB::table('member_assignments')->where('id', $assignmentId)->first();
        }

        return View::make('add-members-staff', [
            'assignment' => $assignment,
            'agents' => $this->loadUsersByRole('agent'),
            'managers' => $this->loadUsersByRole('manager'),
        ]);
    }

    public function draftStaff()
    {
        return View::make('add-members-staff', [
            'assignment' => null,
            'isDraft' => true,
            'agents' => $this->loadUsersByRole('agent'),
            'managers' => $this->loadUsersByRole('manager'),
        ]);
    }

    public function storeStaff(StoreMemberAssignmentRequest $request)
    {
        $data = $request->validated();
        $assignmentId = $data['assignment_id'] ?? null;
        $agent = DB::table('users')->where('id', $data['agent_user_id'])->first();
        $manager = DB::table('users')->where('id', $data['manager_user_id'])->first();

        if ($assignmentId) {
            DB::table('member_assignments')->where('id', $assignmentId)->update([
                'unit_name' => $data['unit_name'],
                'collector_name' => '',
                'collector_user_id' => null,
                'agent_name' => $agent->name ?? '',
                'agent_user_id' => $agent->id ?? null,
                'sales_associate' => $data['sales_associate'],
                'staff_contact' => $data['staff_contact'],
                'manager_name' => $manager->name ?? '',
                'manager_user_id' => $manager->id ?? null,
                'updated_at' => now(),
            ]);
        } else {
            $assignmentId = DB::table('member_assignments')->insertGetId([
                'unit_name' => $data['unit_name'],
                'collector_name' => '',
                'collector_user_id' => null,
                'agent_name' => $agent->name ?? '',
                'agent_user_id' => $agent->id ?? null,
                'sales_associate' => $data['sales_associate'],
                'staff_contact' => $data['staff_contact'],
                'manager_name' => $manager->name ?? '',
                'manager_user_id' => $manager->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Redirect::route('add-members', ['assignment' => $assignmentId])
            ->with('status', 'Staff information saved. Continue with member enrollment.');
    }

    public function draftEnrollment()
    {
        return View::make('add-members', [
            'part1' => null,
            'part2' => null,
            'address' => null,
            'nextUserId' => $this->nextMemberUserId(),
            'planSettings' => $this->loadPlanSettings(),
            'registrationFee' => $this->loadRegistrationFee(),
            'assignment' => null,
            'isDraft' => true,
            'addedByUser' => auth()->user(),
        ]);
    }

    public function create(Request $request)
    {
        // Purge incomplete drafts so partial entries don't linger
        $this->purgeIncompleteDrafts();

        $part1Id = $request->query('part1');
        $assignmentId = $request->query('assignment');
        $nextUserId = $this->nextMemberUserId();

        $part1 = null;
        $part2 = null;
        $address = null;
        $assignment = null;

        if ($part1Id) {
            $part1 = DB::table('part1s')->where('id', $part1Id)->first();
            if ($part1) {
                if ($part1->member_assignment_id) {
                    $assignment = DB::table('member_assignments')->where('id', $part1->member_assignment_id)->first();
                }
                $part2 = DB::table('part2s')->where('part1_id', $part1Id)->orderByDesc('id')->first();
                if ($part2) {
                    $address = DB::table('part2_residential_addresses')->where('part2_id', $part2->id)->first();
                }
            }
        } elseif ($assignmentId) {
            $assignment = DB::table('member_assignments')->where('id', $assignmentId)->first();
        }

        if (! $assignment) {
            return Redirect::route('add-members.staff')
                ->with('status', 'Please complete staff information first.');
        }

        return View::make('add-members', [
            'part1' => $part1,
            'part2' => $part2,
            'address' => $address,
            'nextUserId' => $nextUserId,
            'planSettings' => $this->loadPlanSettings(),
            'registrationFee' => $this->loadRegistrationFee(),
            'assignment' => $assignment,
            'addedByUser' => $part1 ? $this->loadAddedByUser($part1) : auth()->user(),
        ]);
    }

    public function storePart1(StorePart1Request $request)
    {
        $data = $request->validated();

        $userId = $this->nextMemberUserId();
        $dueDate = now()->parse($data['application_date'])->addYears(5)->toDateString();

        $part1Id = DB::table('part1s')->insertGetId([
            'member_assignment_id' => $data['member_assignment_id'],
            'user_id' => $userId,
            'created_by_user_id' => auth()->id(),
            'added_by_user_id' => auth()->id(),
            'application_date' => $data['application_date'],
            'approved_date' => $data['approved_date'],
            'plan_type' => 'Pending Age Category',
            'gross_contact_price' => 0,
            'mode_of_payment' => $data['mode_of_payment'],
            'terms_of_payment' => 'Monthly',
            'due_date' => $dueDate,
            'amount' => 0,
            'payment_status' => 'pending',
            'member_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Redirect::route('add-members.part2', ['part1' => $part1Id])
            ->with('status', 'Part 1 saved. Continue with details.');
    }

    public function editPart1(int $part1)
    {
        $nextUserId = $this->nextMemberUserId();
        $record = DB::table('part1s')->where('id', $part1)->first();

        abort_unless($record, 404);

        $assignment = null;
        if ($record->member_assignment_id) {
            $assignment = DB::table('member_assignments')->where('id', $record->member_assignment_id)->first();
        }

        $part2 = DB::table('part2s')->where('part1_id', $part1)->orderByDesc('id')->first();
        $address = $part2
            ? DB::table('part2_residential_addresses')->where('part2_id', $part2->id)->first()
            : null;

        return View::make('add-members', [
            'part1' => $record,
            'part2' => $part2,
            'address' => $address,
            'nextUserId' => $nextUserId,
            'planSettings' => $this->loadPlanSettings(),
            'registrationFee' => $this->loadRegistrationFee(),
            'assignment' => $assignment,
            'addedByUser' => $this->loadAddedByUser($record),
        ]);
    }

    public function showPart2(Request $request, int $part1)
    {
        $part2Id = $request->query('part2');
        $part2 = null;

        if ($part2Id) {
            $part2 = DB::table('part2s')->where('id', $part2Id)->first();
        }

        if (! $part2) {
            $part2 = DB::table('part2s')->where('part1_id', $part1)->orderByDesc('id')->first();
        }

        $address = $part2 ? DB::table('part2_residential_addresses')->where('part2_id', $part2->id)->first() : null;
        $beneficiary = $part2 ? DB::table('part2_beneficiaries')->where('part2_id', $part2->id)->first() : null;

        $part1Record = DB::table('part1s')->where('id', $part1)->first();
        $assignment = $part1Record && $part1Record->member_assignment_id
            ? DB::table('member_assignments')->where('id', $part1Record->member_assignment_id)->first()
            : null;

        return View::make('add-members-part2', [
            'part1Id' => $part1,
            'part1' => $part1Record,
            'part2' => $part2,
            'address' => $address,
            'beneficiary' => $beneficiary,
            'assignment' => $assignment,
        ]);
    }

    public function draftPart2()
    {
        return View::make('add-members-part2', [
            'part1Id' => null,
            'part1' => null,
            'part2' => null,
            'address' => null,
            'beneficiary' => null,
            'assignment' => null,
            'isDraft' => true,
        ]);
    }

    public function storePart2(StorePart2Request $request, int $part1)
    {
        $data = $request->validated();
        $agePricing = $this->resolveAgeCategoryPricing((int) $data['age']);

        $part2Id = DB::table('part2s')->insertGetId([
            'part1_id' => $data['part1_id'],
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'midle_name' => $data['midle_name'] ?? null,
            'place_of_birth' => $data['place_of_birth'],
            'date_of_birth' => $data['date_of_birth'],
            'age' => $data['age'],
            'sex_at_birth' => $data['sex_at_birth'],
            'cellular_no' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $part1Record = DB::table('part1s')->where('id', $part1)->first();
        if ($part1Record) {
            DB::table('part1s')->where('id', $part1)->update([
                'reference_number' => $data['reference_number'],
                'plan_type' => $agePricing['category'],
                'gross_contact_price' => $agePricing['amount'],
                'terms_of_payment' => 'Monthly',
                'amount' => $agePricing['amount'],
                'updated_at' => now(),
            ]);

            $this->ensurePaymentSchedule((object) [
                'id' => $part1,
                'plan_type' => $agePricing['category'],
                'mode_of_payment' => $part1Record->mode_of_payment,
                'terms_of_payment' => 'Monthly',
                'application_date' => $part1Record->application_date,
                'approved_date' => $part1Record->approved_date,
                'gross_contact_price' => $agePricing['amount'],
                'amount' => $agePricing['amount'],
                'due_date' => $part1Record->due_date,
            ], (object) ['id' => $part2Id]);
        }

        return Redirect::route('add-members.part2.address', ['part1' => $part1, 'part2' => $part2Id])
            ->with('status', 'Part 2 saved. Add residential address.');
    }

    public function showAddress(int $part1, int $part2)
    {
        $address = DB::table('part2_residential_addresses')->where('part2_id', $part2)->first();
        $part1Record = DB::table('part1s')->where('id', $part1)->first();
        $assignment = $part1Record && $part1Record->member_assignment_id
            ? DB::table('member_assignments')->where('id', $part1Record->member_assignment_id)->first()
            : null;

        return View::make('add-members-address', [
            'part1Id' => $part1,
            'part2Id' => $part2,
            'address' => $address,
            'assignment' => $assignment,
        ]);
    }

    public function draftAddress()
    {
        return View::make('add-members-address', [
            'part1Id' => null,
            'part2Id' => null,
            'address' => null,
            'beneficiary' => null,
            'assignment' => null,
            'isDraft' => true,
        ]);
    }

    public function storeAddress(StoreAddressRequest $request, int $part1, int $part2)
    {
        $data = $request->validated();

        $existing = DB::table('part2_residential_addresses')->where('part2_id', $part2)->first();

        if ($existing) {
            DB::table('part2_residential_addresses')->where('id', $existing->id)->update([
                'part1_id' => $data['part1_id'],
                'complete_address' => $data['complete_address'],
                'contact_no' => $data['contact_no'],
                'religion' => $data['religion'],
                'occupation_livelihood' => $data['occupation_livelihood'],
                'valid_id' => $data['valid_id'],
                'valid_id_no' => $data['valid_id_no'],
                'updated_at' => now(),
            ]);
            $addressId = $existing->id;
        } else {
            $addressId = DB::table('part2_residential_addresses')->insertGetId([
                'part1_id' => $data['part1_id'],
                'part2_id' => $data['part2_id'],
                'complete_address' => $data['complete_address'],
                'contact_no' => $data['contact_no'],
                'religion' => $data['religion'],
                'occupation_livelihood' => $data['occupation_livelihood'],
                'valid_id' => $data['valid_id'],
                'valid_id_no' => $data['valid_id_no'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Redirect::route('add-members.part2.beneficiaries', [
            'part1' => $part1,
            'part2' => $part2,
            'address' => $addressId,
        ])->with('status', 'Address saved. Add beneficiaries.');
    }

    public function showBeneficiaries(Request $request, int $part1, int $part2)
    {
        $addressId = $request->query('address');
        $beneficiaries = DB::table('part2_beneficiaries')->where('part2_id', $part2)->get();
        $part1Record = DB::table('part1s')->where('id', $part1)->first();
        $assignment = $part1Record && $part1Record->member_assignment_id
            ? DB::table('member_assignments')->where('id', $part1Record->member_assignment_id)->first()
            : null;

        return View::make('add-members-beneficiaries', [
            'part1Id' => $part1,
            'part2Id' => $part2,
            'addressId' => $addressId,
            'beneficiaries' => $beneficiaries,
            'assignment' => $assignment,
        ]);
    }

    public function draftBeneficiaries()
    {
        return View::make('add-members-beneficiaries', [
            'part1Id' => null,
            'part2Id' => null,
            'addressId' => null,
            'beneficiaries' => [],
            'assignment' => null,
            'isDraft' => true,
        ]);
    }

    public function storeDraft(Request $request)
    {
        $data = $request->validate([
            'staff.agent_user_id' => ['required', 'integer', 'exists:users,id'],
            'staff.manager_user_id' => ['required', 'integer', 'exists:users,id'],
            'staff.unit_name' => ['required', 'string', 'max:255'],
            'staff.sales_associate' => ['required', 'string', 'max:255'],
            'staff.staff_contact' => ['required', 'string', 'max:255'],
            'enrollment.application_date' => ['required', 'date'],
            'enrollment.approved_date' => ['required', 'date'],
            'enrollment.mode_of_payment' => ['required', 'string', 'max:255'],
            'member.reference_number' => ['required', 'string', 'max:255', Rule::unique('part1s', 'reference_number')],
            'member.surname' => ['required', 'string', 'max:255'],
            'member.first_name' => ['required', 'string', 'max:255'],
            'member.midle_name' => ['nullable', 'string', 'max:255'],
            'member.place_of_birth' => ['required', 'string', 'max:255'],
            'member.date_of_birth' => ['required', 'date', 'before_or_equal:' . now()->subYears(60)->toDateString()],
            'member.age' => ['required', 'integer', 'min:60'],
            'member.sex_at_birth' => ['required', 'string', 'max:255'],
            'address.complete_address' => ['required', 'string', 'max:1000'],
            'address.contact_no' => ['required', 'string', 'max:255'],
            'address.religion' => ['required', 'string', 'max:255'],
            'address.occupation_livelihood' => ['required', 'string', 'max:255'],
            'address.valid_id' => ['required', 'string', 'max:255'],
            'address.valid_id_no' => ['required', 'string', 'max:255'],
            'beneficiaries' => ['required', 'array', 'min:1'],
            'beneficiaries.*.name' => ['required', 'string', 'max:255'],
            'beneficiaries.*.address' => ['required', 'string', 'max:255'],
            'beneficiaries.*.relationship_to_planholder' => ['required', 'string', 'max:255'],
        ], [
            'member.date_of_birth.before_or_equal' => 'The member age must be not below 60 years old.',
            'member.age.min' => 'The member age must be not below 60 years old.',
        ]);

        $staff = $data['staff'];
        $enrollment = $data['enrollment'];
        $member = $data['member'];
        $address = $data['address'];
        $beneficiaries = $data['beneficiaries'];

        $agePricing = $this->resolveAgeCategoryPricing((int) $member['age']);

        $userId = $this->nextMemberUserId();
        $creatorId = auth()->id();

        $result = DB::transaction(function () use ($staff, $enrollment, $member, $address, $beneficiaries, $userId, $creatorId, $agePricing) {
            $agent = DB::table('users')->where('id', $staff['agent_user_id'])->first();
            $manager = DB::table('users')->where('id', $staff['manager_user_id'])->first();

            $assignmentId = DB::table('member_assignments')->insertGetId([
                'unit_name' => $staff['unit_name'],
                'collector_name' => '',
                'collector_user_id' => null,
                'agent_name' => $agent->name ?? '',
                'agent_user_id' => $agent->id ?? null,
                'sales_associate' => $staff['sales_associate'],
                'staff_contact' => $staff['staff_contact'],
                'manager_name' => $manager->name ?? '',
                'manager_user_id' => $manager->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $part1Id = DB::table('part1s')->insertGetId([
                'member_assignment_id' => $assignmentId,
                'user_id' => $userId,
                'reference_number' => $member['reference_number'],
                'created_by_user_id' => $creatorId,
                'added_by_user_id' => $creatorId,
                'application_date' => $enrollment['application_date'],
                'approved_date' => $enrollment['approved_date'],
                'plan_type' => $agePricing['category'],
                'gross_contact_price' => $agePricing['amount'],
                'mode_of_payment' => $enrollment['mode_of_payment'],
                'terms_of_payment' => 'Monthly',
                'due_date' => now()->parse($enrollment['application_date'])->addYears(5)->toDateString(),
                'amount' => $agePricing['amount'],
                'payment_status' => 'pending',
                'member_status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $part2Id = DB::table('part2s')->insertGetId([
                'part1_id' => $part1Id,
                'surname' => $member['surname'],
                'first_name' => $member['first_name'],
                'midle_name' => $member['midle_name'] ?? null,
                'place_of_birth' => $member['place_of_birth'],
                'date_of_birth' => $member['date_of_birth'],
                'age' => (int) $member['age'],
                'sex_at_birth' => $member['sex_at_birth'],
                'cellular_no' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $addressId = DB::table('part2_residential_addresses')->insertGetId([
                'part1_id' => $part1Id,
                'part2_id' => $part2Id,
                'complete_address' => $address['complete_address'],
                'contact_no' => $address['contact_no'],
                'religion' => $address['religion'],
                'occupation_livelihood' => $address['occupation_livelihood'],
                'valid_id' => $address['valid_id'],
                'valid_id_no' => $address['valid_id_no'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = [];
            foreach ($beneficiaries as $bene) {
                $rows[] = [
                    'part1_id' => $part1Id,
                    'part2_id' => $part2Id,
                    'par2_residential_address_id' => $addressId,
                    'name' => $bene['name'],
                    'address' => $bene['address'],
                    'relationship_to_planholder' => $bene['relationship_to_planholder'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($rows)) {
                DB::table('part2_beneficiaries')->insert($rows);
            }

            $this->ensurePaymentSchedule((object) [
                'id' => $part1Id,
                'plan_type' => $agePricing['category'],
                'mode_of_payment' => $enrollment['mode_of_payment'],
                'terms_of_payment' => 'Monthly',
                'application_date' => $enrollment['application_date'],
                'approved_date' => $enrollment['approved_date'],
                'gross_contact_price' => $agePricing['amount'],
                'amount' => $agePricing['amount'],
                'due_date' => now()->parse($enrollment['application_date'])->addYears(5)->toDateString(),
            ], (object) ['id' => $part2Id]);

            return [
                'assignment_id' => $assignmentId,
                'part1_id' => $part1Id,
                'part2_id' => $part2Id,
            ];
        });

        return response()->json([
            'status' => 'ok',
            'redirect' => route($this->postEnrollmentRedirectRouteName()),
            'ids' => $result,
        ]);
    }

    public function storeBeneficiaries(StoreBeneficiariesRequest $request, int $part1, int $part2)
    {
        $data = $request->validated();

        $rows = [];
        $count = count($data['name']);

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'part1_id' => $data['part1_id'],
                'part2_id' => $data['part2_id'],
                'par2_residential_address_id' => $data['par2_residential_address_id'] ?? null,
                'name' => $data['name'][$i] ?? null,
                'address' => $data['address'][$i] ?? null,
                'relationship_to_planholder' => $data['relationship_to_planholder'][$i] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('part2_beneficiaries')->where('part2_id', $part2)->delete();

        if (! empty($rows)) {
            DB::table('part2_beneficiaries')->insert($rows);
        }

        return Redirect::route($this->postEnrollmentRedirectRouteName())
            ->with('status', 'Beneficiaries saved.');
    }

    public function index(Request $request)
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $roleColumn = match ($role) {
            'agent' => 'agent_user_id',
            'manager' => 'manager_user_id',
            default => null,
        };
        $scopeLabel = 'All records';

        if ($role === 'encoder') {
            $part1s = DB::table('part1s')
                ->where('created_by_user_id', auth()->id())
                ->get()
                ->keyBy('id');
            $scopeLabel = 'My encoded records';

            $members = $part1s->isEmpty()
                ? collect()
                : DB::table('part2s')
                    ->whereIn('part1_id', $part1s->keys())
                    ->orderByDesc('created_at')
                    ->get();
        } elseif ($roleColumn) {
            $assignmentIds = DB::table('member_assignments')
                ->where($roleColumn, auth()->id())
                ->pluck('id');
            $scopeLabel = 'My role records';

            $part1s = $assignmentIds->isEmpty()
                ? collect()
                : DB::table('part1s')
                    ->whereIn('member_assignment_id', $assignmentIds)
                    ->get()
                    ->keyBy('id');

            $members = $part1s->isEmpty()
                ? collect()
                : DB::table('part2s')
                    ->whereIn('part1_id', $part1s->keys())
                    ->orderByDesc('created_at')
                    ->get();
        } else {
            $members = DB::table('part2s')->orderByDesc('created_at')->get();

            $part1s = DB::table('part1s')
                ->whereIn('id', $members->pluck('part1_id'))
                ->get()
                ->keyBy('id');
        }

        $paymentLifecycle = app(PaymentLifecycleService::class)->sync($part1s->keys());
        if ($part1s->isNotEmpty()) {
            $part1s = DB::table('part1s')
                ->whereIn('id', $part1s->keys())
                ->get()
                ->keyBy('id');
            $activePart1Ids = $part1s
                ->reject(fn($part1) => strtolower((string) ($part1->member_status ?? 'active')) !== 'active')
                ->keys();
            $part1s = $part1s->only($activePart1Ids);
            $members = $members->filter(fn($member) => $part1s->has($member->part1_id))->values();
        }

        $part1s = $this->attachAddedByUsers($part1s);

        $assignmentIds = $part1s
            ->pluck('member_assignment_id')
            ->filter()
            ->unique()
            ->values();

        $assignments = $assignmentIds->isEmpty()
            ? collect()
            : DB::table('member_assignments')
                ->whereIn('id', $assignmentIds)
                ->get()
                ->keyBy('id');

        $addresses = DB::table('part2_residential_addresses')
            ->whereIn('part2_id', $members->pluck('id'))
            ->get()
            ->keyBy('part2_id');

        $beneficiaries = DB::table('part2_beneficiaries')
            ->whereIn('part2_id', $members->pluck('id'))
            ->get()
            ->groupBy('part2_id');

        $paidInstallmentsByPart1 = $part1s->isEmpty()
            ? collect()
            : DB::table('payments')
                ->join('part1s', 'part1s.id', '=', 'payments.part1_id')
                ->select('payments.part1_id', DB::raw('COUNT(*) as paid_installments'))
                ->whereIn('payments.part1_id', $part1s->keys())
                ->where('payments.status', 'paid')
                ->where(function ($query) {
                    $query->whereNull('payments.payment_type')
                        ->orWhere('payments.payment_type', 'regular');
                })
                ->where(function ($query) {
                    $query->whereNull('part1s.contestability_at')
                        ->orWhereColumn('payments.paid_at', '>=', 'part1s.contestability_at');
                })
                ->groupBy('payments.part1_id')
                ->pluck('paid_installments', 'part1_id');

        $paidAmountByPart1 = $part1s->isEmpty()
            ? collect()
            : DB::table('payments')
                ->select('part1_id', DB::raw('COALESCE(SUM(amount), 0) as paid_amount_total'))
                ->whereIn('part1_id', $part1s->keys())
                ->where('status', 'paid')
                ->groupBy('part1_id')
                ->pluck('paid_amount_total', 'part1_id');

        $paymentHistories = $part1s->isEmpty()
            ? collect()
            : DB::table('payments')
                ->whereIn('part1_id', $part1s->keys())
                ->orderBy('due_date')
                ->orderByRaw("CASE WHEN payment_type = 'registration_renewal' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get()
                ->groupBy('part1_id');

        $percentageTotal = null;
        if ($roleColumn && $part1s->isNotEmpty()) {
            $percentageTotal = $this->sumRolePercentageDeductions($part1s->keys()->all(), $role);
        }

        return View::make('show_members', [
            'members' => $members,
            'part1s' => $part1s,
            'assignments' => $assignments,
            'addresses' => $addresses,
            'beneficiaries' => $beneficiaries,
            'percentageTotal' => $percentageTotal,
            'isReadOnly' => ! in_array($role, ['admin', 'manager', 'encoder'], true),
            'agents' => $this->loadUsersByRole('agent'),
            'managers' => $this->loadUsersByRole('manager'),
            'paidInstallmentsByPart1' => $paidInstallmentsByPart1,
            'paidAmountByPart1' => $paidAmountByPart1,
            'paymentHistories' => $paymentHistories,
            'paymentLifecycle' => $paymentLifecycle,
            'scopeLabel' => $scopeLabel,
            'planSettings' => $this->loadPlanSettings(),
            'canEditMembers' => in_array($role, ['admin', 'manager'], true),
        ]);
    }

    public function inactiveMembers(Request $request)
    {
        [$part1s, $scopeLabel] = $this->resolveScopedPart1sForListing();
        $paymentLifecycle = app(PaymentLifecycleService::class)->sync($part1s->keys());

        if ($part1s->isNotEmpty()) {
            $part1s = DB::table('part1s')
                ->whereIn('id', $part1s->keys())
                ->get()
                ->keyBy('id')
                ->filter(fn($part1) => strtolower((string) ($part1->member_status ?? '')) === 'inactive');
        }

        $members = $part1s->isEmpty()
            ? collect()
            : DB::table('part2s')
                ->whereIn('part1_id', $part1s->keys())
                ->orderByDesc('created_at')
                ->get();

        $addresses = $members->isEmpty()
            ? collect()
            : DB::table('part2_residential_addresses')
                ->whereIn('part2_id', $members->pluck('id'))
                ->get()
                ->keyBy('part2_id');

        return View::make('inactive-members', [
            'members' => $members,
            'part1s' => $part1s,
            'addresses' => $addresses,
            'paymentLifecycle' => $paymentLifecycle,
            'scopeLabel' => $scopeLabel,
            'contestabilityFee' => $this->loadContestabilityFee(),
        ]);
    }

    public function claimedMembers(Request $request)
    {
        [$part1s, $scopeLabel] = $this->resolveScopedPart1sForListing();

        if ($part1s->isNotEmpty()) {
            $part1s = DB::table('part1s')
                ->whereIn('id', $part1s->keys())
                ->where('member_status', 'claimed')
                ->get()
                ->keyBy('id');
        }

        $members = $part1s->isEmpty()
            ? collect()
            : DB::table('part2s')
                ->whereIn('part1_id', $part1s->keys())
                ->orderByDesc('created_at')
                ->get();

        $addresses = $members->isEmpty()
            ? collect()
            : DB::table('part2_residential_addresses')
                ->whereIn('part2_id', $members->pluck('id'))
                ->get()
                ->keyBy('part2_id');
        $claimBenefits = $part1s->mapWithKeys(function ($part1) {
            $computed = $this->claimBenefitMeta($part1);
            $storedMonths = (int) ($part1->claim_contribution_months ?? 0);
            $storedTotal = (float) ($part1->claim_total_amount ?? 0);

            if ($storedMonths >= 24 && $storedTotal > 0) {
                return [$part1->id => [
                    'claim_contribution_months' => $storedMonths,
                    'claim_cash_assistance' => (float) ($part1->claim_cash_assistance ?? 0),
                    'claim_burial_assistance' => (float) ($part1->claim_burial_assistance ?? 0),
                    'claim_total_amount' => $storedTotal,
                ]];
            }

            return [$part1->id => $computed];
        });

        return View::make('claimed-members', [
            'members' => $members,
            'part1s' => $part1s,
            'addresses' => $addresses,
            'claimBenefits' => $claimBenefits,
            'scopeLabel' => $scopeLabel,
        ]);
    }

    public function update(Request $request, int $part2)
    {
        abort_unless($this->canEditMembers(), 403);

        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $section = $request->input('section');
        $part1Id = $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1((int) $part1Id), 403);

        switch ($section) {
            case 'enrollment':
                $data = $request->validate([
                    'application_date' => ['nullable', 'date'],
                    'approved_date' => ['nullable', 'date'],
                    'mode_of_payment' => ['nullable', 'string', 'max:255', 'in:Monthly,Quarterly,Semi-Annual,Annual'],
                    'due_date' => ['nullable', 'date'],
                    'payment_status' => ['nullable', 'string', 'max:50'],
                ]);

                $newMode = $data['mode_of_payment'] ?? $part1->mode_of_payment;
                $modeChanged = $this->normalizePaymentMode($newMode) !== $this->normalizePaymentMode($part1->mode_of_payment);
                if ($modeChanged) {
                    $this->assertPaymentModeCanChange($newMode);
                }

                $nextPaymentStatus = $data['payment_status'] ?? $part1->payment_status;

                DB::table('part1s')->where('id', $part1Id)->update([
                    'application_date' => $data['application_date'] ?? $part1->application_date,
                    'approved_date' => $data['approved_date'] ?? $part1->approved_date,
                    'mode_of_payment' => $newMode,
                    'due_date' => $data['due_date'] ?? $part1->due_date,
                    'payment_status' => $nextPaymentStatus,
                    'member_status' => ! empty($part1->claimed_at)
                        ? 'claimed'
                        : (strtolower((string) $nextPaymentStatus) === 'inactive' ? 'inactive' : 'active'),
                    'updated_at' => now(),
                ]);

                if ($modeChanged) {
                    $this->repricePendingRegularPayments($part1Id, (float) ($part1->amount ?? 0), $newMode);
                }
                break;

            case 'member':
                $data = $request->validate([
                    'reference_number' => ['nullable', 'string', 'max:255', Rule::unique('part1s', 'reference_number')->ignore($part1Id)],
                    'surname' => ['nullable', 'string', 'max:255'],
                    'first_name' => ['nullable', 'string', 'max:255'],
                    'midle_name' => ['nullable', 'string', 'max:255'],
                    'place_of_birth' => ['nullable', 'string', 'max:255'],
                    'date_of_birth' => ['nullable', 'date', 'before_or_equal:' . now()->subYears(60)->toDateString()],
                    'age' => ['nullable', 'integer', 'min:60'],
                    'sex_at_birth' => ['nullable', 'string', 'max:255'],
                ], [
                    'date_of_birth.before_or_equal' => 'The member age must be not below 60 years old.',
                    'age.min' => 'The member age must be not below 60 years old.',
                ]);
                $newAge = (int) ($data['age'] ?? $member->age);
                $agePricing = $this->resolveAgeCategoryPricing($newAge);

                DB::table('part2s')->where('id', $part2)->update([
                    'surname' => $data['surname'] ?? $member->surname,
                    'first_name' => $data['first_name'] ?? $member->first_name,
                    'midle_name' => $data['midle_name'] ?? $member->midle_name,
                    'place_of_birth' => $data['place_of_birth'] ?? $member->place_of_birth,
                    'date_of_birth' => $data['date_of_birth'] ?? $member->date_of_birth,
                    'age' => $data['age'] ?? $member->age,
                    'sex_at_birth' => $data['sex_at_birth'] ?? $member->sex_at_birth,
                    'cellular_no' => null,
                    'updated_at' => now(),
                ]);

                DB::table('part1s')->where('id', $part1Id)->update([
                    'reference_number' => $data['reference_number'] ?? $part1->reference_number,
                    'plan_type' => $agePricing['category'],
                    'gross_contact_price' => $agePricing['amount'],
                    'terms_of_payment' => 'Monthly',
                    'amount' => $agePricing['amount'],
                    'updated_at' => now(),
                ]);

                DB::table('payments')
                    ->where('part1_id', $part1Id)
                    ->where(function ($query) {
                        $query->where('payment_type', 'regular')
                            ->orWhereNull('payment_type');
                    })
                    ->whereIn('status', ['pending', 'overdue'])
                    ->update([
                        'amount' => $this->periodContributionAmount((float) $agePricing['amount'], $part1->mode_of_payment),
                        'updated_at' => now(),
                    ]);
                break;

            case 'address':
                $data = $request->validate([
                    'complete_address' => ['nullable', 'string', 'max:1000'],
                    'contact_no' => ['nullable', 'string', 'max:255'],
                    'religion' => ['nullable', 'string', 'max:255'],
                    'occupation_livelihood' => ['nullable', 'string', 'max:255'],
                    'valid_id' => ['nullable', 'string', 'max:255'],
                    'valid_id_no' => ['nullable', 'string', 'max:255'],
                ]);

                DB::table('part2_residential_addresses')->updateOrInsert(
                    ['part2_id' => $part2],
                    [
                        'part1_id' => $part1Id,
                        'complete_address' => $data['complete_address'] ?? null,
                        'contact_no' => $data['contact_no'] ?? null,
                        'religion' => $data['religion'] ?? null,
                        'occupation_livelihood' => $data['occupation_livelihood'] ?? null,
                        'valid_id' => $data['valid_id'] ?? null,
                        'valid_id_no' => $data['valid_id_no'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                break;

            case 'beneficiary':
                $data = $request->validate([
                    'beneficiary_id' => ['nullable', 'integer'],
                    'par2_residential_address_id' => ['nullable', 'integer'],
                    'name' => ['nullable'],
                    'address' => ['nullable'],
                    'relationship_to_planholder' => ['nullable'],
                ]);

                $beneficiaryId = $data['beneficiary_id'] ?? null;

                if ($beneficiaryId) {
                    DB::table('part2_beneficiaries')
                        ->where('id', $beneficiaryId)
                        ->where('part2_id', $part2)
                        ->update([
                            'par2_residential_address_id' => $data['par2_residential_address_id'] ?? null,
                            'name' => is_array($data['name']) ? ($data['name'][0] ?? null) : ($data['name'] ?? null),
                            'address' => is_array($data['address']) ? ($data['address'][0] ?? null) : ($data['address'] ?? null),
                            'relationship_to_planholder' => is_array($data['relationship_to_planholder']) ? ($data['relationship_to_planholder'][0] ?? null) : ($data['relationship_to_planholder'] ?? null),
                            'updated_at' => now(),
                        ]);
                } else {
                    $names = $data['name'] ?? [];
                    $addresses = $data['address'] ?? [];
                    $relationships = $data['relationship_to_planholder'] ?? [];

                    if (! is_array($names)) $names = [$names];
                    if (! is_array($addresses)) $addresses = [$addresses];
                    if (! is_array($relationships)) $relationships = [$relationships];

                    $rows = [];
                    $count = count($names);

                    for ($i = 0; $i < $count; $i++) {
                        if (empty($names[$i])) {
                            continue;
                        }

                        $rows[] = [
                            'part1_id' => $part1Id,
                            'part2_id' => $part2,
                            'par2_residential_address_id' => $data['par2_residential_address_id'] ?? null,
                            'name' => $names[$i] ?? null,
                            'address' => $addresses[$i] ?? null,
                            'relationship_to_planholder' => $relationships[$i] ?? null,
                            'updated_at' => now(),
                            'created_at' => now(),
                        ];
                    }

                    DB::table('part2_beneficiaries')->where('part2_id', $part2)->delete();

                    if (! empty($rows)) {
                        DB::table('part2_beneficiaries')->insert($rows);
                    }
                }
                break;
            case 'staff':
                $data = $request->validate([
                    'assignment_id' => ['nullable', 'integer'],
                    'unit_name' => ['nullable', 'string', 'max:255'],
                    'agent_user_id' => ['nullable', 'integer', 'exists:users,id'],
                    'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
                    'sales_associate' => ['nullable', 'string', 'max:255'],
                    'staff_contact' => ['nullable', 'string', 'max:255'],
                ]);

                $assignmentId = $data['assignment_id'] ?? $part1?->member_assignment_id;
                if (! $assignmentId) {
                    abort(400, 'Missing assignment id.');
                }

                $agent = isset($data['agent_user_id'])
                    ? DB::table('users')->where('id', $data['agent_user_id'])->first()
                    : null;
                $manager = isset($data['manager_user_id'])
                    ? DB::table('users')->where('id', $data['manager_user_id'])->first()
                    : null;

                DB::table('member_assignments')
                    ->where('id', $assignmentId)
                    ->update([
                        'unit_name' => $data['unit_name'] ?? null,
                        'collector_name' => '',
                        'collector_user_id' => null,
                        'agent_name' => $agent?->name ?? null,
                        'agent_user_id' => $agent?->id ?? null,
                        'sales_associate' => $data['sales_associate'] ?? null,
                        'staff_contact' => $data['staff_contact'] ?? null,
                        'manager_name' => $manager?->name ?? null,
                        'manager_user_id' => $manager?->id ?? null,
                        'updated_at' => now(),
                    ]);
                break;

            default:
                abort(400, 'Invalid section.');
        }

        AuditLogger::log('member.update_section', 'member', $part2, [
            'part1_id' => (int) $part1Id,
            'section' => (string) $section,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function destroy(int $part2)
    {
        abort_unless($this->canEditMembers(), 403);

        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);
        $part1Id = $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1((int) $part1Id), 403);

        DB::transaction(function () use ($part2, $part1Id) {
            DB::table('part2_beneficiaries')->where('part2_id', $part2)->delete();
            DB::table('part2_residential_addresses')->where('part2_id', $part2)->delete();
            DB::table('part2s')->where('id', $part2)->delete();
            DB::table('payments')->where('part1_id', $part1Id)->delete();
            DB::table('part1s')->where('id', $part1Id)->delete();
        });

        AuditLogger::log('member.delete', 'member', $part2, [
            'part1_id' => (int) $part1Id,
        ]);

        return response()->json(['status' => 'deleted']);
    }

    public function claim(Request $request, int $part2)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $part1Id = (int) $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1($part1Id), 403);

        $claimBenefit = $this->claimBenefitMeta($part1, $request->boolean('include_burial', true));
        $payload = [
            'claimed_at' => now(),
            'claimed_by_user_id' => auth()->id(),
            'member_status' => 'claimed',
            'updated_at' => now(),
        ];

        foreach ([
            'claim_contribution_months',
            'claim_cash_assistance',
            'claim_burial_assistance',
            'claim_total_amount',
        ] as $column) {
            if (Schema::hasColumn('part1s', $column)) {
                $payload[$column] = $claimBenefit[$column];
            }
        }

        DB::table('part1s')->where('id', $part1Id)->update($payload);

        AuditLogger::log('member.claim', 'member', $part2, [
            'part1_id' => $part1Id,
            'claim_benefit' => $claimBenefit,
        ]);

        return response()->json(['status' => 'claimed', 'claim_benefit' => $claimBenefit]);
    }

    public function markInactive(int $part2)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $part1Id = (int) $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1($part1Id), 403);

        DB::table('part1s')->where('id', $part1Id)->update([
            'payment_status' => 'inactive',
            'member_status' => 'inactive',
            'manual_inactive_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::log('member.mark_inactive', 'member', $part2, [
            'part1_id' => $part1Id,
        ]);

        return response()->json(['status' => 'inactive']);
    }

    public function contestability(int $part2)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $part1Id = (int) $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1($part1Id), 403);

        $contestabilityFee = $this->loadContestabilityFee();
        $paymentId = null;

        DB::transaction(function () use ($part1Id, $part2, $contestabilityFee, &$paymentId) {
            DB::table('part1s')->where('id', $part1Id)->update([
                'payment_status' => 'pending',
                'member_status' => 'active',
                'manual_inactive_at' => null,
                'contestability_at' => now(),
                'updated_at' => now(),
            ]);

            $paymentId = DB::table('payments')->insertGetId([
                'part1_id' => $part1Id,
                'part2_id' => $part2,
                'due_date' => now()->toDateString(),
                'amount' => $contestabilityFee,
                'payment_type' => 'contestability',
                'status' => 'paid',
                'paid_at' => now(),
                'reference' => 'Contestability',
                'notes' => 'Contestability fee',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        AuditLogger::log('member.contestability', 'member', $part2, [
            'part1_id' => $part1Id,
            'payment_id' => $paymentId,
            'contestability_fee' => $contestabilityFee,
        ]);

        return response()->json(['status' => 'active']);
    }

    public function payments(Request $request, int $part2)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $part1Id = (int) $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1($part1Id), 403);

        $this->ensurePaymentSchedule($part1, $member);

        return response()->json([
            'payments' => DB::table('payments')
                ->where('part1_id', $part1Id)
                ->orderBy('due_date')
                ->orderByRaw("CASE WHEN payment_type = 'registration_renewal' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function payNext(Request $request, int $part2)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $part1Id = (int) $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1($part1Id), 403);

        $initialRegistration = DB::table('payments')
            ->where('part1_id', $part1Id)
            ->where('payment_type', 'registration_renewal')
            ->orderBy('due_date')
            ->orderBy('id')
            ->first();

        $selectedPaymentIds = collect($request->input('payment_ids', []))
            ->when(! is_array($request->input('payment_ids', [])), fn($items) => collect([$request->input('payment_ids')]))
            ->merge([(int) $request->input('payment_id', 0)])
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $paymentQuery = DB::table('payments')
            ->where('part1_id', $part1Id)
            ->where(function ($query) {
                $query->whereNull('status')
                    ->orWhere('status', '!=', 'paid');
            });

        if ($selectedPaymentIds->isNotEmpty()) {
            $paymentQuery->whereIn('id', $selectedPaymentIds->all());
        } else {
            $paymentQuery
                ->orderByRaw("CASE WHEN payment_type = 'registration_renewal' AND due_date <= CURDATE() THEN 0 ELSE 1 END")
                ->orderBy('due_date')
                ->orderBy('id')
                ->limit(1);
        }

        $paymentsToPay = $paymentQuery
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
        $payment = $paymentsToPay->first();

        if (! $payment || $paymentsToPay->isEmpty()) {
            return response()->json(['message' => 'No payable schedule found.'], 422);
        }

        if ($selectedPaymentIds->isNotEmpty() && $paymentsToPay->count() !== $selectedPaymentIds->count()) {
            return response()->json(['message' => 'Some selected payments are no longer payable.'], 422);
        }

        $paymentType = strtolower((string) ($payment->payment_type ?? 'regular'));
        $hasRenewal = $paymentsToPay->contains(fn($row) => strtolower((string) ($row->payment_type ?? 'regular')) === 'registration_renewal');
        $hasContribution = $paymentsToPay->contains(fn($row) => strtolower((string) ($row->payment_type ?? 'regular')) !== 'registration_renewal');
        if ($hasRenewal && $hasContribution) {
            return response()->json(['message' => 'Pay renewals and contributions separately.'], 422);
        }

        if ($initialRegistration && (int) $payment->id !== (int) $initialRegistration->id) {
            if ($paymentType === 'registration_renewal') {
                $nextRenewals = DB::table('payments')
                    ->where('part1_id', $part1Id)
                    ->where('payment_type', 'registration_renewal')
                    ->where(function ($query) {
                        $query->whereNull('status')
                            ->orWhere('status', '!=', 'paid');
                    })
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->get();

                $allowedIds = $nextRenewals
                    ->take($paymentsToPay->count())
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();
                $submittedIds = $paymentsToPay
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                if ($submittedIds !== $allowedIds) {
                    return response()->json(['message' => 'Renewal must be paid in schedule order.'], 422);
                }
            } else {
                $nextContributions = DB::table('payments')
                    ->where('part1_id', $part1Id)
                    ->where(function ($query) {
                        $query->whereNull('status')
                            ->orWhere('status', '!=', 'paid');
                    })
                    ->where(function ($query) {
                        $query->whereNull('payment_type')
                            ->orWhere('payment_type', 'regular');
                    })
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->get();

                $allowedIds = $nextContributions
                    ->take($paymentsToPay->count())
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();
                $submittedIds = $paymentsToPay
                    ->pluck('id')
                    ->map(fn($id) => (int) $id)
                    ->values()
                    ->all();

                if ($submittedIds !== $allowedIds) {
                    return response()->json(['message' => 'Contribution must be paid in schedule order.'], 422);
                }
            }
        }

        $paymentIds = [];
        DB::transaction(function () use ($paymentsToPay, $part1, $request, $part1Id, &$paymentIds) {
            foreach ($paymentsToPay as $paymentRow) {
                $deductionMeta = $this->paymentDeductionMeta($paymentRow, $part1);

                DB::table('payments')->where('id', $paymentRow->id)->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'reference' => $request->input('reference'),
                    'notes' => $request->input('notes'),
                    'insurance_total' => $deductionMeta['total_deductions'] > 0 ? $deductionMeta['total_deductions'] : null,
                    'net_amount' => $deductionMeta['total_deductions'] > 0 ? $deductionMeta['net_amount'] : null,
                    'insurance_breakdown' => ! empty($deductionMeta['deductions']) ? json_encode($deductionMeta['deductions']) : null,
                    'updated_at' => now(),
                ]);

                $paymentIds[] = (int) $paymentRow->id;

                AuditLogger::log('payment.mark_paid', 'payment', (int) $paymentRow->id, [
                    'part1_id' => $part1Id,
                    'gross_amount' => (float) $paymentRow->amount,
                    'deductions' => $deductionMeta['total_deductions'],
                    'net_amount' => $deductionMeta['net_amount'],
                ]);
            }
        });

        $state = app(PaymentLifecycleService::class)->sync([$part1Id])[$part1Id] ?? [];
        $payments = DB::table('payments')
            ->where('part1_id', $part1Id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        return response()->json([
            'status' => 'paid',
            'payment_id' => (int) $paymentIds[0],
            'payment_ids' => $paymentIds,
            'payments' => $payments,
            'payment_status' => $state['payment_status'] ?? 'pending',
            'paid_installments' => $this->paidRegularPaymentsAfterContestability($part1, $payments)->count(),
            'paid_amount_total' => round((float) $payments->where('status', 'paid')->sum('amount'), 2),
        ]);
    }

    public function redoPayment(Request $request, int $part2, int $payment)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $part1Id = (int) $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canManagePart1($part1Id), 403);

        $paymentRow = DB::table('payments')
            ->where('id', $payment)
            ->where('part1_id', $part1Id)
            ->first();

        if (! $paymentRow) {
            return response()->json(['message' => 'Payment not found.'], 404);
        }

        if (strtolower((string) $paymentRow->status) !== 'paid') {
            return response()->json(['message' => 'Only paid payments can be redone.'], 422);
        }

        $latestPaid = DB::table('payments')
            ->where('part1_id', $part1Id)
            ->where('status', 'paid')
            ->orderByDesc('due_date')
            ->orderByRaw("CASE WHEN payment_type = 'registration_renewal' THEN 0 ELSE 1 END DESC")
            ->orderByDesc('id')
            ->first();

        if ($latestPaid && (int) $latestPaid->id !== (int) $paymentRow->id) {
            return response()->json(['message' => 'Redo the latest paid payment first.'], 422);
        }

        $newStatus = $paymentRow->due_date && (string) $paymentRow->due_date < now()->toDateString()
            ? 'overdue'
            : 'pending';

        DB::table('payments')->where('id', $paymentRow->id)->update([
            'status' => $newStatus,
            'paid_at' => null,
            'reference' => null,
            'notes' => null,
            'insurance_total' => null,
            'net_amount' => null,
            'insurance_breakdown' => null,
            'updated_at' => now(),
        ]);

        $state = app(PaymentLifecycleService::class)->sync([$part1Id])[$part1Id] ?? [];
        $payments = DB::table('payments')
            ->where('part1_id', $part1Id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        AuditLogger::log('payment.redo', 'payment', (int) $paymentRow->id, [
            'part1_id' => $part1Id,
            'previous_status' => $paymentRow->status,
            'new_status' => $newStatus,
        ]);

        return response()->json([
            'status' => $newStatus,
            'payment_id' => (int) $paymentRow->id,
            'payments' => $payments,
            'payment_status' => $state['payment_status'] ?? 'pending',
            'paid_installments' => $this->paidRegularPaymentsAfterContestability($part1, $payments)->count(),
            'paid_amount_total' => round((float) $payments->where('status', 'paid')->sum('amount'), 2),
        ]);
    }

    private function resolveScopedPart1sForListing(): array
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $roleColumn = match ($role) {
            'agent' => 'agent_user_id',
            'manager' => 'manager_user_id',
            default => null,
        };
        $scopeLabel = 'All records';
        $query = DB::table('part1s');

        if ($role === 'encoder') {
            $query->where('created_by_user_id', auth()->id());
            $scopeLabel = 'My encoded records';
        } elseif ($roleColumn) {
            $assignmentIds = DB::table('member_assignments')
                ->where($roleColumn, auth()->id())
                ->pluck('id');
            $scopeLabel = 'My role records';

            if ($assignmentIds->isEmpty()) {
                return [collect(), $scopeLabel];
            }

            $query->whereIn('member_assignment_id', $assignmentIds);
        }

        return [$query->orderByDesc('created_at')->get()->keyBy('id'), $scopeLabel];
    }

    private function paymentDeductionMeta($payment, $part1): array
    {
        $gross = (float) ($payment->amount ?? 0);
        $deductions = [];
        $insuranceTotal = 0.0;

        $partners = DB::table('insurance_partners')
            ->where('active', true)
            ->orderBy('sort_order')
            ->limit(2)
            ->get();

        foreach ($partners as $partner) {
            $amount = $partner->amount !== null ? (float) $partner->amount : 0.0;
            if ($amount <= 0) {
                continue;
            }

            $insuranceTotal += $amount;
            $deductions[] = [
                'id' => $partner->id,
                'name' => $partner->name,
                'amount' => $amount,
            ];
        }

        $modeKey = match ($this->normalizePaymentMode($part1->mode_of_payment ?? 'monthly')) {
            'quarterly' => 'Quarterly',
            'semi-annual' => 'Semi-Annual',
            'annual' => 'Annual',
            default => 'Monthly',
        };

        $paymentIds = DB::table('payments')
            ->where('part1_id', $payment->part1_id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->pluck('id');
        $position = $paymentIds->search($payment->id);
        $paymentNumber = $position === false ? 1 : ((int) $position + 1);

        $tier = match ($modeKey) {
            'Quarterly' => $paymentNumber === 1 ? 'first_quarter' : ($paymentNumber <= 4 ? 'quarters_2_4' : 'quarters_5_20'),
            'Semi-Annual' => $paymentNumber === 1 ? 'first_semi' : ($paymentNumber === 2 ? 'semis_2_2' : 'semis_3_10'),
            'Annual' => $paymentNumber === 1 ? 'first_year' : 'years_2_5',
            default => $paymentNumber === 1 ? 'first_month' : ($paymentNumber <= 12 ? 'months_2_12' : 'months_13_60'),
        };

        $roleDeductionTotal = 0.0;
        $roles = ['agent', 'manager', 'others'];
        $percentByRole = DB::table('percentage_settings')
            ->where('mode', $modeKey)
            ->where('tier', $tier)
            ->whereIn('role', $roles)
            ->pluck('percent', 'role')
            ->toArray();

        foreach ($roles as $role) {
            $percent = isset($percentByRole[$role]) ? (float) $percentByRole[$role] : 0.0;
            if ($percent <= 0) {
                continue;
            }

            $roleAmount = round($gross * ($percent / 100), 2);
            if ($roleAmount <= 0) {
                continue;
            }

            $roleDeductionTotal += $roleAmount;
            $deductions[] = [
                'id' => null,
                'name' => ucfirst($role) . ' Percentage',
                'amount' => $roleAmount,
                'meta' => [
                    'percent' => $percent,
                    'tier' => $tier,
                    'mode' => $modeKey,
                    'role' => $role,
                ],
            ];
        }

        $totalDeductions = min($gross, round($insuranceTotal + $roleDeductionTotal, 2));

        return [
            'deductions' => $deductions,
            'total_deductions' => $totalDeductions,
            'net_amount' => max(0, round($gross - $totalDeductions, 2)),
        ];
    }

    private function canManagePart1(int $part1Id): bool
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $userId = (int) auth()->id();

        if ($role === 'admin') {
            return true;
        }

        if ($role === 'encoder') {
            return DB::table('part1s')
                ->where('id', $part1Id)
                ->where('created_by_user_id', $userId)
                ->exists();
        }

        if ($role === 'manager') {
            return DB::table('part1s')
                ->join('member_assignments', 'member_assignments.id', '=', 'part1s.member_assignment_id')
                ->where('part1s.id', $part1Id)
                ->where('member_assignments.manager_user_id', $userId)
                ->exists();
        }

        return false;
    }

    private function canEditMembers(): bool
    {
        return in_array(strtolower((string) (auth()->user()->role ?? '')), ['admin', 'manager'], true);
    }

    private function claimBenefitMeta($part1, bool $includeBurial = true): array
    {
        $paidContributions = $this->paidRegularPaymentsAfterContestability($part1, DB::table('payments')
            ->where('part1_id', $part1->id)
            ->where('status', 'paid')
            ->where(function ($query) {
                $query->whereNull('payment_type')
                    ->orWhere('payment_type', 'regular');
            })
            ->get());
        $paidContributionCount = $paidContributions->count();
        $paidContributionTotal = round((float) $paidContributions->sum('amount'), 2);

        $monthsPerContribution = match ($this->normalizePaymentMode($part1->mode_of_payment ?? 'monthly')) {
            'quarterly' => 3,
            'semi-annual' => 6,
            'annual' => 12,
            default => 1,
        };
        $contributionMonths = (int) $paidContributionCount * $monthsPerContribution;
        $qualifiedForTwoYears = $contributionMonths >= 24;
        $cashAssistance = $qualifiedForTwoYears ? 20000.00 : $paidContributionTotal;
        $burialAssistance = $qualifiedForTwoYears && $includeBurial ? 10000.00 : 0.00;

        return [
            'claim_contribution_months' => $contributionMonths,
            'claim_cash_assistance' => $cashAssistance,
            'claim_burial_assistance' => $burialAssistance,
            'claim_total_amount' => $cashAssistance + $burialAssistance,
        ];
    }

    private function paidRegularPaymentsAfterContestability($part1, $payments)
    {
        $contestabilityAt = $part1?->contestability_at
            ? \Carbon\Carbon::parse($part1->contestability_at)
            : null;

        return collect($payments)
            ->filter(function ($payment) use ($contestabilityAt) {
                $type = strtolower((string) ($payment->payment_type ?? 'regular'));
                if (! in_array($type, ['', 'regular'], true)) {
                    return false;
                }

                if (strtolower((string) ($payment->status ?? '')) !== 'paid') {
                    return false;
                }

                if (! $contestabilityAt) {
                    return true;
                }

                if (empty($payment->paid_at)) {
                    return false;
                }

                return \Carbon\Carbon::parse($payment->paid_at)->greaterThanOrEqualTo($contestabilityAt);
            })
            ->values();
    }

    private function normalizePaymentMode(?string $mode): string
    {
        return match (strtolower(trim((string) $mode))) {
            'semi annual' => 'semi-annual',
            'yearly' => 'annual',
            default => strtolower(trim((string) $mode)),
        };
    }

    private function assertPaymentModeCanChange(string $mode): void
    {
        $normalized = $this->normalizePaymentMode($mode);
        $month = (int) now()->month;
        $monthsRemaining = 12 - $month + 1;

        $message = match (true) {
            $normalized === 'annual' && $month !== 1 => 'Annual mode can only be changed in January.',
            $normalized === 'semi-annual' && $monthsRemaining < 6 => 'Semi-Annual mode needs at least 6 months remaining in the year.',
            $normalized === 'quarterly' && $monthsRemaining < 3 => 'Quarterly mode needs at least 3 months remaining in the year.',
            default => null,
        };

        if ($message) {
            throw ValidationException::withMessages([
                'mode_of_payment' => $message,
            ]);
        }
    }

    private function repricePendingRegularPayments(int $part1Id, float $baseAmount, string $mode): void
    {
        $amount = $this->periodContributionAmount($baseAmount, $mode);

        DB::table('payments')
            ->where('part1_id', $part1Id)
            ->where(function ($query) {
                $query->where('payment_type', 'regular')
                    ->orWhereNull('payment_type');
            })
            ->whereIn('status', ['pending', 'overdue'])
            ->update([
                'amount' => $amount,
                'updated_at' => now(),
            ]);
    }

    private function periodContributionAmount(float $baseAmount, string $mode): float
    {
        $factor = match ($this->normalizePaymentMode($mode)) {
            'quarterly' => 3,
            'semi-annual' => 6,
            'annual' => 12,
            default => 1,
        };

        return max(0, round($baseAmount * $factor, 2));
    }

    private function nextMemberUserId(): int
    {
        $userIds = DB::table('part1s')
            ->whereNotNull('user_id')
            ->orderBy('user_id')
            ->pluck('user_id');

        $next = 1;

        foreach ($userIds as $id) {
            $id = (int) $id;
            if ($id > $next) {
                break;
            }
            if ($id === $next) {
                $next++;
            }
        }

        return $next;
    }

    private function loadUsersByRole(string $role)
    {
        return DB::table('users')
            ->where('role', $role)
            ->orderBy('name')
            ->get();
    }

    private function loadAddedByUser(object $part1): ?object
    {
        $userId = (int) ($part1->added_by_user_id ?? $part1->created_by_user_id ?? 0);
        if ($userId <= 0) {
            return null;
        }

        return DB::table('users')->where('id', $userId)->first(['id', 'name', 'role']);
    }

    private function attachAddedByUsers($part1s)
    {
        if ($part1s->isEmpty()) {
            return $part1s;
        }

        $userIds = $part1s
            ->map(fn($part1) => $part1->added_by_user_id ?? $part1->created_by_user_id ?? null)
            ->filter()
            ->unique()
            ->values();

        $users = $userIds->isEmpty()
            ? collect()
            : DB::table('users')->whereIn('id', $userIds)->get(['id', 'name', 'role'])->keyBy('id');

        return $part1s->map(function ($part1) use ($users) {
            $userId = $part1->added_by_user_id ?? $part1->created_by_user_id ?? null;
            $user = $userId ? $users->get($userId) : null;
            $part1->added_by_name = $user->name ?? '-';
            $part1->added_by_role = $user->role ?? null;
            return $part1;
        });
    }

    private function sumRolePercentageDeductions(array $part1Ids, string $role): float
    {
        if (empty($part1Ids)) {
            return 0.0;
        }

        $payments = DB::table('payments')
            ->whereIn('part1_id', $part1Ids)
            ->where('status', 'paid')
            ->whereNotNull('insurance_breakdown')
            ->get();

        $total = 0.0;
        $roleLabel = ucfirst($role);

        foreach ($payments as $payment) {
            $rows = json_decode($payment->insurance_breakdown, true);
            if (! is_array($rows)) continue;
            foreach ($rows as $row) {
                $meta = $row['meta'] ?? [];
                $rowRole = $meta['role'] ?? null;
                $hasPercent = isset($meta['percent']) && (float) $meta['percent'] > 0;
                if (! $hasPercent) continue;
                if ($rowRole !== null && $rowRole !== $role) continue;
                if ($rowRole === null && stripos((string) ($row['name'] ?? ''), $roleLabel) === false) continue;
                $total += (float) ($row['amount'] ?? 0);
            }
        }

        return round($total, 2);
    }

    /**
     * Generate payment schedule rows for a plan.
     */
    private function ensurePaymentSchedule($part1, $member = null): void
    {
        if (!$part1) return;

        $plan = strtolower(trim($part1->plan_type ?? ''));
        $mode = strtolower(trim($part1->mode_of_payment ?? 'monthly'));
        $start = now()->parse($part1->application_date ?? $part1->due_date ?? now());

        $defaults = $this->loadPlanDefaults();

        $meta = $defaults[$plan] ?? ['contract' => ($part1->gross_contact_price ?? 0), 'premium' => ($part1->amount ?? 0), 'months' => 12, 'legacy_monthly' => 0];
        $contract = $part1->gross_contact_price ?? $meta['contract'] ?? 0;
        $premium = $part1->amount ?? $meta['premium'] ?? 0;
        $totalMonths = $this->parseMonths($part1->terms_of_payment ?? '', $meta['months']);
        $end = ! empty($part1->due_date) ? now()->parse($part1->due_date) : null;
        if ($end && $end->greaterThan($start)) {
            $monthsByDateRange = (($end->year - $start->year) * 12) + ($end->month - $start->month);
            if ($end->day > $start->day) {
                $monthsByDateRange++;
            }
            $totalMonths = max($totalMonths, $monthsByDateRange);
        }
        $registrationFee = $this->loadRegistrationFee();
        $renewalFee = $this->loadRenewalFee();

        if ($plan === 'legacy care') {
            $monthlyAmount = max(0, (float) ($meta['legacy_monthly'] ?? 0));
            $rows = [
                [
                    'part1_id' => $part1->id,
                    'part2_id' => $member?->id,
                    'due_date' => $start->toDateString(),
                    'amount' => $registrationFee,
                    'payment_type' => 'registration_renewal',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'part1_id' => $part1->id,
                    'part2_id' => $member?->id,
                    'due_date' => $start->toDateString(),
                    'amount' => $contract,
                    'payment_type' => 'regular',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ];

            if ($monthlyAmount > 0) {
                $rows[] = [
                    'part1_id' => $part1->id,
                    'part2_id' => $member?->id,
                    'due_date' => (clone $start)->addMonth()->toDateString(),
                    'amount' => $monthlyAmount,
                    'payment_type' => 'regular',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $this->insertMissingPaymentScheduleRows((int) $part1->id, $rows);
            return;
        }

        $intervalMonths = match ($mode) {
            'quarterly' => 3,
            'semi-annual', 'semi annual' => 6,
            'annual', 'yearly' => 12,
            'one-time', 'one time', 'one_time' => $totalMonths,
            default => 1,
        };

        $periods = max(1, (int) ceil($totalMonths / $intervalMonths));
        $amountPerPeriod = $this->computePeriodAmount($premium, $contract, $intervalMonths, $mode);

        $rows = [
            [
                'part1_id' => $part1->id,
                'part2_id' => $member?->id,
                'due_date' => $start->toDateString(),
                'amount' => $registrationFee,
                'payment_type' => 'registration_renewal',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        for ($i = 0; $i < $periods; $i++) {
            $due = (clone $start)->addMonths($i * $intervalMonths);
            $rows[] = [
                'part1_id' => $part1->id,
                'part2_id' => $member?->id,
                'due_date' => $due->toDateString(),
                'amount' => $amountPerPeriod,
                'payment_type' => 'regular',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        for ($month = 12; $month <= $totalMonths; $month += 12) {
            $rows[] = [
                'part1_id' => $part1->id,
                'part2_id' => $member?->id,
                'due_date' => (clone $start)->addMonths($month)->toDateString(),
                'amount' => $renewalFee,
                'payment_type' => 'registration_renewal',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            $this->insertMissingPaymentScheduleRows((int) $part1->id, $rows);
        }
    }

    private function insertMissingPaymentScheduleRows(int $part1Id, array $rows): void
    {
        if (empty($rows)) return;

        $existingKeys = DB::table('payments')
            ->where('part1_id', $part1Id)
            ->get(['due_date', 'payment_type'])
            ->mapWithKeys(function ($row) {
                $type = strtolower((string) ($row->payment_type ?: 'regular'));
                return [$type . '|' . $row->due_date => true];
            });

        $missingRows = collect($rows)
            ->reject(function ($row) use ($existingKeys) {
                $type = strtolower((string) (($row['payment_type'] ?? null) ?: 'regular'));
                return $existingKeys->has($type . '|' . $row['due_date']);
            })
            ->values()
            ->all();

        if (! empty($missingRows)) {
            DB::table('payments')->insert($missingRows);
        }
    }

    private function parseMonths(?string $terms, int $fallback): int
    {
        if (!$terms) return $fallback;
        if (preg_match('/(\\d+)\\s*month/i', $terms, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\\d+)\\s*year/i', $terms, $m)) {
            return (int) $m[1] * 12;
        }
        return $fallback;
    }

    private function computePeriodAmount(float $premium, float $contract, int $intervalMonths, string $mode): float
    {
        $m = strtolower($mode);
        $base = $premium > 0 ? $premium : $contract;
        return match ($m) {
            'quarterly' => $base * 3,
            'semi-annual', 'semi annual' => $base * 6,
            'annual', 'yearly' => $base * 12,
            'one-time', 'one time', 'one_time' => $contract > 0 ? $contract : $base,
            default => $base,
        };
    }

    private function loadPlanSettings(): array
    {
        $rows = DB::table('plan_settings')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [
                'Age 60 to 65' => [
                    'contract_amount' => 100,
                    'legacy_monthly_amount' => null,
                    'premium_amount' => 100,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months',
                    'default_months' => 60,
                ],
                'Age 66 to 70' => [
                    'contract_amount' => 120,
                    'legacy_monthly_amount' => null,
                    'premium_amount' => 120,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months',
                    'default_months' => 60,
                ],
                'Age 71 to 80' => [
                    'contract_amount' => 150,
                    'legacy_monthly_amount' => null,
                    'premium_amount' => 150,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months',
                    'default_months' => 60,
                ],
                'Age 81 above' => [
                    'contract_amount' => 200,
                    'legacy_monthly_amount' => null,
                    'premium_amount' => 200,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months',
                    'default_months' => 60,
                ],
            ];
        }

        $plans = [];
        foreach ($rows as $row) {
            $plans[$row->name] = [
                'contract_amount' => (int) $row->contract_amount,
                'legacy_monthly_amount' => isset($row->legacy_monthly_amount) ? (int) $row->legacy_monthly_amount : null,
                'premium_amount' => (int) $row->premium_amount,
                'default_mode' => $row->default_mode,
                'default_terms' => $row->default_terms,
                'default_months' => (int) $row->default_months,
            ];
        }

        return $plans;
    }

    private function loadPlanDefaults(): array
    {
        $settings = $this->loadPlanSettings();
        $defaults = [];
        foreach ($settings as $name => $meta) {
            $defaults[strtolower($name)] = [
                'contract' => $meta['contract_amount'],
                'premium' => $meta['premium_amount'],
                'months' => $meta['default_months'],
                'legacy_monthly' => $meta['legacy_monthly_amount'] ?? 0,
            ];
        }
        return $defaults;
    }

    private function resolveAgeCategoryPricing(int $age): array
    {
        $category = match (true) {
            $age >= 81 => 'Age 81 above',
            $age >= 71 => 'Age 71 to 80',
            $age >= 66 => 'Age 66 to 70',
            default => 'Age 60 to 65',
        };

        $settings = $this->loadPlanSettings();
        $amount = (int) ($settings[$category]['contract_amount'] ?? match ($category) {
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

    private function loadRegistrationFee(): int
    {
        return $this->loadSystemFee('registration_fee', 300);
    }

    private function loadRenewalFee(): int
    {
        return $this->loadSystemFee('renewal_fee', $this->loadRegistrationFee());
    }

    private function loadContestabilityFee(): int
    {
        return $this->loadSystemFee('contestability_fee', 0);
    }

    private function loadSystemFee(string $key, int $fallback): int
    {
        if (! Schema::hasTable('system_settings')) {
            return $fallback;
        }

        return max(0, (int) (DB::table('system_settings')->where('key', $key)->value('value') ?? $fallback));
    }

    private function resolvePlanContractAmount(string $planName): int
    {
        $settings = $this->loadPlanSettings();
        $value = $settings[$planName]['contract_amount'] ?? 0;
        return max(0, (int) $value);
    }

    private function postEnrollmentRedirectRouteName(): string
    {
        return 'show-members';
    }

    /**
     * Delete drafts that have not completed all four steps (part1 + part2 + address + beneficiaries).
     */
    private function purgeIncompleteDrafts(): void
    {
        $draftPart1s = DB::table('part1s')
            ->leftJoin('part2s', 'part2s.part1_id', '=', 'part1s.id')
            ->leftJoin('part2_residential_addresses', 'part2_residential_addresses.part2_id', '=', 'part2s.id')
            ->leftJoin('part2_beneficiaries', 'part2_beneficiaries.part2_id', '=', 'part2s.id')
            ->whereNull('part2s.id')
            ->orWhereNull('part2_residential_addresses.id')
            ->orWhereNull('part2_beneficiaries.id')
            ->select('part1s.id')
            ->pluck('id');

        if ($draftPart1s->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($draftPart1s) {
            $part1Ids = $draftPart1s->all();
            $part2Ids = DB::table('part2s')->whereIn('part1_id', $part1Ids)->pluck('id');

            DB::table('part2_beneficiaries')->whereIn('part2_id', $part2Ids)->delete();
            DB::table('part2_residential_addresses')->whereIn('part2_id', $part2Ids)->delete();
            DB::table('part2s')->whereIn('id', $part2Ids)->delete();
            DB::table('payments')->whereIn('part1_id', $part1Ids)->delete();
            DB::table('part1s')->whereIn('id', $part1Ids)->delete();
        });
    }
}
