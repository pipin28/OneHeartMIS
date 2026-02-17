<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\StoreBeneficiariesRequest;
use App\Http\Requests\StoreMemberAssignmentRequest;
use App\Http\Requests\StorePart1Request;
use App\Http\Requests\StorePart2Request;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

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
            'collectors' => $this->loadUsersByRole('collector'),
            'agents' => $this->loadUsersByRole('agent'),
            'managers' => $this->loadUsersByRole('manager'),
        ]);
    }

    public function draftStaff()
    {
        return View::make('add-members-staff', [
            'assignment' => null,
            'isDraft' => true,
            'collectors' => $this->loadUsersByRole('collector'),
            'agents' => $this->loadUsersByRole('agent'),
            'managers' => $this->loadUsersByRole('manager'),
        ]);
    }

    public function storeStaff(StoreMemberAssignmentRequest $request)
    {
        $data = $request->validated();
        $assignmentId = $data['assignment_id'] ?? null;
        $collector = DB::table('users')->where('id', $data['collector_user_id'])->first();
        $agent = DB::table('users')->where('id', $data['agent_user_id'])->first();
        $manager = DB::table('users')->where('id', $data['manager_user_id'])->first();

        if ($assignmentId) {
            DB::table('member_assignments')->where('id', $assignmentId)->update([
                'collector_name' => $collector->name ?? '',
                'collector_user_id' => $collector->id ?? null,
                'agent_name' => $agent->name ?? '',
                'agent_user_id' => $agent->id ?? null,
                'manager_name' => $manager->name ?? '',
                'manager_user_id' => $manager->id ?? null,
                'updated_at' => now(),
            ]);
        } else {
            $assignmentId = DB::table('member_assignments')->insertGetId([
                'collector_name' => $collector->name ?? '',
                'collector_user_id' => $collector->id ?? null,
                'agent_name' => $agent->name ?? '',
                'agent_user_id' => $agent->id ?? null,
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
            'assignment' => null,
            'isDraft' => true,
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
            'assignment' => $assignment,
        ]);
    }

    public function storePart1(StorePart1Request $request)
    {
        $data = $request->validated();

        if (($data['plan_type'] ?? null) === 'Legacy Care') {
            $data['amount'] = $this->resolvePlanContractAmount('Legacy Care');
            $data['mode_of_payment'] = 'One-time';
            $data['terms_of_payment'] = 'Infinite';
        }

        $userId = $this->nextMemberUserId();

        $part1Id = DB::table('part1s')->insertGetId([
            'member_assignment_id' => $data['member_assignment_id'],
            'user_id' => $userId,
            'created_by_user_id' => auth()->id(),
            'lpaf_no' => $data['lpaf_no'],
            'application_date' => $data['application_date'],
            'sales_counselor_code' => $data['sales_counselor_code'],
            'plan_type' => $data['plan_type'],
            'gross_contact_price' => $data['gross_contact_price'],
            'mode_of_payment' => $data['mode_of_payment'],
            'terms_of_payment' => $data['terms_of_payment'],
            'due_date' => $data['due_date'] ?? null,
            'amount' => $data['amount'],
            'payment_status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // auto-generate payment schedule immediately
        $this->ensurePaymentSchedule((object)[
            'id' => $part1Id,
            'plan_type' => $data['plan_type'],
            'mode_of_payment' => $data['mode_of_payment'],
            'terms_of_payment' => $data['terms_of_payment'],
            'application_date' => $data['application_date'],
            'gross_contact_price' => $data['gross_contact_price'],
            'amount' => $data['amount'],
            'due_date' => $data['due_date'] ?? null,
        ], null);

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
            'assignment' => $assignment,
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

        $part2Id = DB::table('part2s')->insertGetId([
            'part1_id' => $data['part1_id'],
            'surname' => $data['surname'],
            'first_name' => $data['first_name'],
            'midle_name' => $data['midle_name'] ?? null,
            'place_of_birth' => $data['place_of_birth'],
            'date_of_birth' => $data['date_of_birth'],
            'age' => $data['age'],
            'sex_at_birth' => $data['sex_at_birth'],
            'civil_status' => $data['civil_status'],
            'cellular_no' => $data['cellular_no'],
            'email_address' => $data['email_address'],
            'nationality' => $data['nationality'],
            'institution_name' => $data['institution_name'],
            'institution_no' => $data['institution_no'],
            'occupation' => $data['occupation'],
            'name_of_employer' => $data['name_of_employer'],
            'office_address' => $data['office_address'],
            'office_no' => $data['office_no'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
                'lot_house_numer' => $data['lot_house_numer'],
                'street' => $data['street'],
                'barangay' => $data['barangay'],
                'province' => $data['province'],
                'zip_code' => $data['zip_code'],
                'contact_no' => $data['contact_no'],
                'sss_gsis_no' => $data['sss_gsis_no'],
                'tin_no' => $data['tin_no'],
                'source_of_funds_if_not_imployed' => $data['source_of_funds_if_not_imployed'],
                'updated_at' => now(),
            ]);
            $addressId = $existing->id;
        } else {
            $addressId = DB::table('part2_residential_addresses')->insertGetId([
                'part1_id' => $data['part1_id'],
                'part2_id' => $data['part2_id'],
                'lot_house_numer' => $data['lot_house_numer'],
                'street' => $data['street'],
                'barangay' => $data['barangay'],
                'province' => $data['province'],
                'zip_code' => $data['zip_code'],
                'contact_no' => $data['contact_no'],
                'sss_gsis_no' => $data['sss_gsis_no'],
                'tin_no' => $data['tin_no'],
                'source_of_funds_if_not_imployed' => $data['source_of_funds_if_not_imployed'],
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
            'staff.collector_user_id' => ['required', 'integer', 'exists:users,id'],
            'staff.agent_user_id' => ['required', 'integer', 'exists:users,id'],
            'staff.manager_user_id' => ['required', 'integer', 'exists:users,id'],
            'enrollment.lpaf_no' => ['required', 'integer'],
            'enrollment.application_date' => ['required', 'date'],
            'enrollment.sales_counselor_code' => ['required', 'string', 'max:255'],
            'enrollment.plan_type' => ['required', 'string', 'max:255'],
            'enrollment.gross_contact_price' => ['required'],
            'enrollment.mode_of_payment' => ['required', 'string', 'max:255'],
            'enrollment.terms_of_payment' => ['required', 'string', 'max:255'],
            'enrollment.due_date' => ['required', 'date'],
            'enrollment.amount' => ['required'],
            'member.surname' => ['required', 'string', 'max:255'],
            'member.first_name' => ['required', 'string', 'max:255'],
            'member.midle_name' => ['nullable', 'string', 'max:255'],
            'member.place_of_birth' => ['required', 'string', 'max:255'],
            'member.date_of_birth' => ['required', 'date'],
            'member.age' => ['required', 'integer'],
            'member.sex_at_birth' => ['required', 'string', 'max:255'],
            'member.civil_status' => ['required', 'string', 'max:255'],
            'member.cellular_no' => ['required', 'string', 'max:255'],
            'member.email_address' => ['required', 'email', 'max:255'],
            'member.nationality' => ['required', 'string', 'max:255'],
            'member.institution_name' => ['required', 'string', 'max:255'],
            'member.institution_no' => ['required', 'integer'],
            'member.occupation' => ['required', 'string', 'max:255'],
            'member.name_of_employer' => ['required', 'string', 'max:255'],
            'member.office_address' => ['required', 'string', 'max:255'],
            'member.office_no' => ['required', 'integer'],
            'address.lot_house_numer' => ['required', 'string', 'max:255'],
            'address.street' => ['required', 'string', 'max:255'],
            'address.barangay' => ['required', 'string', 'max:255'],
            'address.province' => ['required', 'string', 'max:255'],
            'address.zip_code' => ['required', 'string', 'max:255'],
            'address.contact_no' => ['required', 'string', 'max:255'],
            'address.sss_gsis_no' => ['required', 'string', 'max:255'],
            'address.tin_no' => ['required', 'string', 'max:255'],
            'address.source_of_funds_if_not_imployed' => ['required', 'string', 'max:255'],
            'beneficiaries' => ['required', 'array', 'min:1'],
            'beneficiaries.*.type' => ['required', 'string', 'max:255'],
            'beneficiaries.*.name' => ['required', 'string', 'max:255'],
            'beneficiaries.*.age' => ['required', 'integer'],
            'beneficiaries.*.address' => ['required', 'string', 'max:255'],
            'beneficiaries.*.relationship_to_planholder' => ['required', 'string', 'max:255'],
        ]);

        $staff = $data['staff'];
        $enrollment = $data['enrollment'];
        $member = $data['member'];
        $address = $data['address'];
        $beneficiaries = $data['beneficiaries'];

        $cleanNumber = static fn($value) => (float) preg_replace('/[^0-9.]/', '', (string) $value);
        $planType = $enrollment['plan_type'];
        if ($planType === 'Legacy Care') {
            $enrollment['amount'] = $this->resolvePlanContractAmount('Legacy Care');
            $enrollment['mode_of_payment'] = 'One-time';
            $enrollment['terms_of_payment'] = 'Infinite';
        }

        $userId = $this->nextMemberUserId();
        $creatorId = auth()->id();

        $result = DB::transaction(function () use ($staff, $enrollment, $member, $address, $beneficiaries, $userId, $cleanNumber, $creatorId) {
            $collector = DB::table('users')->where('id', $staff['collector_user_id'])->first();
            $agent = DB::table('users')->where('id', $staff['agent_user_id'])->first();
            $manager = DB::table('users')->where('id', $staff['manager_user_id'])->first();

            $assignmentId = DB::table('member_assignments')->insertGetId([
                'collector_name' => $collector->name ?? '',
                'collector_user_id' => $collector->id ?? null,
                'agent_name' => $agent->name ?? '',
                'agent_user_id' => $agent->id ?? null,
                'manager_name' => $manager->name ?? '',
                'manager_user_id' => $manager->id ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $part1Id = DB::table('part1s')->insertGetId([
                'member_assignment_id' => $assignmentId,
                'user_id' => $userId,
                'created_by_user_id' => $creatorId,
                'lpaf_no' => (int) $enrollment['lpaf_no'],
                'application_date' => $enrollment['application_date'],
                'sales_counselor_code' => $enrollment['sales_counselor_code'],
                'plan_type' => $enrollment['plan_type'],
                'gross_contact_price' => $cleanNumber($enrollment['gross_contact_price']),
                'mode_of_payment' => $enrollment['mode_of_payment'],
                'terms_of_payment' => $enrollment['terms_of_payment'],
                'due_date' => $enrollment['due_date'] ?? null,
                'amount' => $cleanNumber($enrollment['amount']),
                'payment_status' => 'pending',
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
                'civil_status' => $member['civil_status'],
                'cellular_no' => $member['cellular_no'],
                'email_address' => $member['email_address'],
                'nationality' => $member['nationality'],
                'institution_name' => $member['institution_name'],
                'institution_no' => (int) $member['institution_no'],
                'occupation' => $member['occupation'],
                'name_of_employer' => $member['name_of_employer'],
                'office_address' => $member['office_address'],
                'office_no' => (int) $member['office_no'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $addressId = DB::table('part2_residential_addresses')->insertGetId([
                'part1_id' => $part1Id,
                'part2_id' => $part2Id,
                'lot_house_numer' => $address['lot_house_numer'],
                'street' => $address['street'],
                'barangay' => $address['barangay'],
                'province' => $address['province'],
                'zip_code' => $address['zip_code'],
                'contact_no' => $address['contact_no'],
                'sss_gsis_no' => $address['sss_gsis_no'],
                'tin_no' => $address['tin_no'],
                'source_of_funds_if_not_imployed' => $address['source_of_funds_if_not_imployed'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = [];
            foreach ($beneficiaries as $bene) {
                $rows[] = [
                    'part1_id' => $part1Id,
                    'part2_id' => $part2Id,
                    'par2_residential_address_id' => $addressId,
                    'type' => $bene['type'],
                    'name' => $bene['name'],
                    'age' => $bene['age'],
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
                'plan_type' => $enrollment['plan_type'],
                'mode_of_payment' => $enrollment['mode_of_payment'],
                'terms_of_payment' => $enrollment['terms_of_payment'],
                'application_date' => $enrollment['application_date'],
                'gross_contact_price' => $cleanNumber($enrollment['gross_contact_price']),
                'amount' => $cleanNumber($enrollment['amount']),
                'due_date' => $enrollment['due_date'] ?? null,
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
                'type' => $data['type'][$i] ?? null,
                'name' => $data['name'][$i] ?? null,
                'age' => $data['age'][$i] ?? null,
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
            'collector' => 'collector_user_id',
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
                ->select('part1_id', DB::raw('COUNT(*) as paid_installments'))
                ->whereIn('part1_id', $part1s->keys())
                ->where('status', 'paid')
                ->groupBy('part1_id')
                ->pluck('paid_installments', 'part1_id');

        $paidAmountByPart1 = $part1s->isEmpty()
            ? collect()
            : DB::table('payments')
                ->select('part1_id', DB::raw('COALESCE(SUM(amount), 0) as paid_amount_total'))
                ->whereIn('part1_id', $part1s->keys())
                ->where('status', 'paid')
                ->groupBy('part1_id')
                ->pluck('paid_amount_total', 'part1_id');

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
            'isReadOnly' => (bool) $roleColumn || $role === 'encoder',
            'collectors' => $this->loadUsersByRole('collector'),
            'agents' => $this->loadUsersByRole('agent'),
            'managers' => $this->loadUsersByRole('manager'),
            'paidInstallmentsByPart1' => $paidInstallmentsByPart1,
            'paidAmountByPart1' => $paidAmountByPart1,
            'scopeLabel' => $scopeLabel,
            'planSettings' => $this->loadPlanSettings(),
        ]);
    }

    public function update(Request $request, int $part2)
    {
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);

        $section = $request->input('section');
        $part1Id = $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);

        switch ($section) {
            case 'enrollment':
                $data = $request->validate([
                    'lpaf_no' => ['nullable', 'integer'],
                    'plan_type' => ['nullable', 'string', 'max:255'],
                    'application_date' => ['nullable', 'date'],
                    'gross_contact_price' => ['nullable', 'integer'],
                    'mode_of_payment' => ['nullable', 'string', 'max:255'],
                    'terms_of_payment' => ['nullable', 'string', 'max:255'],
                    'due_date' => ['nullable', 'date'],
                    'amount' => ['nullable', 'integer'],
                    'payment_status' => ['nullable', 'string', 'max:50'],
                ]);

                $planType = $data['plan_type'] ?? $part1->plan_type;
                if ($planType === 'Legacy Care') {
                    $data['plan_type'] = 'Legacy Care';
                    $data['amount'] = $this->resolvePlanContractAmount('Legacy Care');
                    $data['mode_of_payment'] = 'One-time';
                    $data['terms_of_payment'] = 'Infinite';
                }

                DB::table('part1s')->where('id', $part1Id)->update([
                    'lpaf_no' => $data['lpaf_no'] ?? $part1->lpaf_no,
                    'plan_type' => $data['plan_type'] ?? $part1->plan_type,
                    'application_date' => $data['application_date'] ?? $part1->application_date,
                    'gross_contact_price' => $data['gross_contact_price'] ?? $part1->gross_contact_price,
                    'mode_of_payment' => $data['mode_of_payment'] ?? $part1->mode_of_payment,
                    'terms_of_payment' => $data['terms_of_payment'] ?? $part1->terms_of_payment,
                    'due_date' => $data['due_date'] ?? $part1->due_date,
                    'amount' => $data['amount'] ?? $part1->amount,
                    'payment_status' => $data['payment_status'] ?? $part1->payment_status,
                    'updated_at' => now(),
                ]);
                break;

            case 'member':
                $data = $request->validate([
                    'surname' => ['nullable', 'string', 'max:255'],
                    'first_name' => ['nullable', 'string', 'max:255'],
                    'midle_name' => ['nullable', 'string', 'max:255'],
                    'place_of_birth' => ['nullable', 'string', 'max:255'],
                    'date_of_birth' => ['nullable', 'date'],
                    'age' => ['nullable', 'integer'],
                    'sex_at_birth' => ['nullable', 'string', 'max:255'],
                    'civil_status' => ['nullable', 'string', 'max:255'],
                    'cellular_no' => ['nullable', 'string', 'max:255'],
                    'email_address' => ['nullable', 'email', 'max:255'],
                    'nationality' => ['nullable', 'string', 'max:255'],
                    'institution_name' => ['nullable', 'string', 'max:255'],
                    'institution_no' => ['nullable', 'integer'],
                    'occupation' => ['nullable', 'string', 'max:255'],
                    'name_of_employer' => ['nullable', 'string', 'max:255'],
                    'office_address' => ['nullable', 'string', 'max:255'],
                    'office_no' => ['nullable', 'integer'],
                ]);

                DB::table('part2s')->where('id', $part2)->update([
                    'surname' => $data['surname'] ?? $member->surname,
                    'first_name' => $data['first_name'] ?? $member->first_name,
                    'midle_name' => $data['midle_name'] ?? $member->midle_name,
                    'place_of_birth' => $data['place_of_birth'] ?? $member->place_of_birth,
                    'date_of_birth' => $data['date_of_birth'] ?? $member->date_of_birth,
                    'age' => $data['age'] ?? $member->age,
                    'sex_at_birth' => $data['sex_at_birth'] ?? $member->sex_at_birth,
                    'civil_status' => $data['civil_status'] ?? $member->civil_status,
                    'cellular_no' => $data['cellular_no'] ?? $member->cellular_no,
                    'email_address' => $data['email_address'] ?? $member->email_address,
                    'nationality' => $data['nationality'] ?? $member->nationality,
                    'institution_name' => $data['institution_name'] ?? $member->institution_name,
                    'institution_no' => $data['institution_no'] ?? $member->institution_no,
                    'occupation' => $data['occupation'] ?? $member->occupation,
                    'name_of_employer' => $data['name_of_employer'] ?? $member->name_of_employer,
                    'office_address' => $data['office_address'] ?? $member->office_address,
                    'office_no' => $data['office_no'] ?? $member->office_no,
                    'updated_at' => now(),
                ]);
                break;

            case 'address':
                $data = $request->validate([
                    'lot_house_numer' => ['nullable', 'string', 'max:255'],
                    'street' => ['nullable', 'string', 'max:255'],
                    'barangay' => ['nullable', 'string', 'max:255'],
                    'province' => ['nullable', 'string', 'max:255'],
                    'zip_code' => ['nullable', 'string', 'max:255'],
                    'contact_no' => ['nullable', 'string', 'max:255'],
                    'sss_gsis_no' => ['nullable', 'string', 'max:255'],
                    'tin_no' => ['nullable', 'string', 'max:255'],
                    'source_of_funds_if_not_imployed' => ['nullable', 'string', 'max:255'],
                ]);

                DB::table('part2_residential_addresses')->updateOrInsert(
                    ['part2_id' => $part2],
                    [
                        'part1_id' => $part1Id,
                        'lot_house_numer' => $data['lot_house_numer'] ?? null,
                        'street' => $data['street'] ?? null,
                        'barangay' => $data['barangay'] ?? null,
                        'province' => $data['province'] ?? null,
                        'zip_code' => $data['zip_code'] ?? null,
                        'contact_no' => $data['contact_no'] ?? null,
                        'sss_gsis_no' => $data['sss_gsis_no'] ?? null,
                        'tin_no' => $data['tin_no'] ?? null,
                        'source_of_funds_if_not_imployed' => $data['source_of_funds_if_not_imployed'] ?? null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
                break;

            case 'beneficiary':
                $data = $request->validate([
                    'beneficiary_id' => ['nullable', 'integer'],
                    'par2_residential_address_id' => ['nullable', 'integer'],
                    'type' => ['nullable'],
                    'name' => ['nullable'],
                    'age' => ['nullable'],
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
                            'type' => is_array($data['type']) ? ($data['type'][0] ?? null) : ($data['type'] ?? null),
                            'name' => is_array($data['name']) ? ($data['name'][0] ?? null) : ($data['name'] ?? null),
                            'age' => is_array($data['age']) ? ($data['age'][0] ?? null) : ($data['age'] ?? null),
                            'address' => is_array($data['address']) ? ($data['address'][0] ?? null) : ($data['address'] ?? null),
                            'relationship_to_planholder' => is_array($data['relationship_to_planholder']) ? ($data['relationship_to_planholder'][0] ?? null) : ($data['relationship_to_planholder'] ?? null),
                            'updated_at' => now(),
                        ]);
                } else {
                    $types = $data['type'] ?? [];
                    $names = $data['name'] ?? [];
                    $ages = $data['age'] ?? [];
                    $addresses = $data['address'] ?? [];
                    $relationships = $data['relationship_to_planholder'] ?? [];

                    if (! is_array($types)) $types = [$types];
                    if (! is_array($names)) $names = [$names];
                    if (! is_array($ages)) $ages = [$ages];
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
                            'type' => $types[$i] ?? null,
                            'name' => $names[$i] ?? null,
                            'age' => $ages[$i] ?? null,
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
                    'collector_user_id' => ['nullable', 'integer', 'exists:users,id'],
                    'agent_user_id' => ['nullable', 'integer', 'exists:users,id'],
                    'manager_user_id' => ['nullable', 'integer', 'exists:users,id'],
                ]);

                $assignmentId = $data['assignment_id'] ?? $part1?->member_assignment_id;
                if (! $assignmentId) {
                    abort(400, 'Missing assignment id.');
                }

                $collector = isset($data['collector_user_id'])
                    ? DB::table('users')->where('id', $data['collector_user_id'])->first()
                    : null;
                $agent = isset($data['agent_user_id'])
                    ? DB::table('users')->where('id', $data['agent_user_id'])->first()
                    : null;
                $manager = isset($data['manager_user_id'])
                    ? DB::table('users')->where('id', $data['manager_user_id'])->first()
                    : null;

                DB::table('member_assignments')
                    ->where('id', $assignmentId)
                    ->update([
                        'collector_name' => $collector?->name ?? null,
                        'collector_user_id' => $collector?->id ?? null,
                        'agent_name' => $agent?->name ?? null,
                        'agent_user_id' => $agent?->id ?? null,
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
        $member = DB::table('part2s')->where('id', $part2)->first();
        abort_unless($member, 404);
        $part1Id = $member->part1_id;
        $part1 = DB::table('part1s')->where('id', $part1Id)->first();
        abort_unless($part1, 404);

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
        $exists = DB::table('payments')->where('part1_id', $part1->id)->exists();
        if ($exists) return;

        $plan = strtolower(trim($part1->plan_type ?? ''));
        $mode = strtolower(trim($part1->mode_of_payment ?? 'monthly'));
        $start = now()->parse($part1->due_date ?? $part1->application_date ?? now());

        $defaults = $this->loadPlanDefaults();

        $meta = $defaults[$plan] ?? ['contract' => ($part1->gross_contact_price ?? 0), 'premium' => ($part1->amount ?? 0), 'months' => 12, 'legacy_monthly' => 0];
        $contract = $part1->gross_contact_price ?? $meta['contract'] ?? 0;
        $premium = $part1->amount ?? $meta['premium'] ?? 0;
        $totalMonths = $this->parseMonths($part1->terms_of_payment ?? '', $meta['months']);

        if ($plan === 'legacy care') {
            $monthlyAmount = max(0, (float) ($meta['legacy_monthly'] ?? 0));
            $rows = [
                [
                    'part1_id' => $part1->id,
                    'part2_id' => $member?->id,
                    'due_date' => $start->toDateString(),
                    'amount' => $contract,
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
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('payments')->insert($rows);
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

        $rows = [];
        for ($i = 0; $i < $periods; $i++) {
            $due = (clone $start)->addMonths($i * $intervalMonths);
            $rows[] = [
                'part1_id' => $part1->id,
                'part2_id' => $member?->id,
                'due_date' => $due->toDateString(),
                'amount' => $amountPerPeriod,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('payments')->insert($rows);
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
                'Serenity Care' => [
                    'contract_amount' => 30000,
                    'legacy_monthly_amount' => null,
                    'premium_amount' => 500,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months (5 years)',
                    'default_months' => 60,
                ],
                'Everlasting Care' => [
                    'contract_amount' => 20000,
                    'legacy_monthly_amount' => null,
                    'premium_amount' => 350,
                    'default_mode' => 'Monthly',
                    'default_terms' => '60 months (5 years)',
                    'default_months' => 60,
                ],
                'Legacy Care' => [
                    'contract_amount' => 30000,
                    'legacy_monthly_amount' => 0,
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

    private function resolvePlanContractAmount(string $planName): int
    {
        $settings = $this->loadPlanSettings();
        $value = $settings[$planName]['contract_amount'] ?? 0;
        return max(0, (int) $value);
    }

    private function postEnrollmentRedirectRouteName(): string
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        return $role === 'encoder' ? 'show-members' : 'payment';
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
