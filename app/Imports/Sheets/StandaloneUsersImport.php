<?php

namespace App\Imports\Sheets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use App\Services\OrgScopeService;

class StandaloneUsersImport implements ToCollection, WithHeadingRow
{
    private int $success = 0;
    private int $rowIndex = 1;
    private bool $headersDumped = false;

    public function collection(Collection $rows)
    {
        echo "\n🚀 Starting standalone Users_Import processing...\n\n";

        foreach ($rows as $row) {
            $this->rowIndex++;
            $this->processRow($row->toArray(), $this->rowIndex);
        }

        echo "\n✅ Standalone Users Import Completed! Success: {$this->success}\n\n";
    }

    private function processRow(array $row, int $rowIndex): void
    {
        if (!$this->headersDumped) {
            echo "[DEBUG] First row keys: " . implode(', ', array_keys($row)) . "\n";
            $this->headersDumped = true;
        }

        $empCode = $this->getValue($row, ['emp_code', 'Emp Code*']);
        if (!$empCode) {
            $this->logRow($rowIndex, '❌ FAIL', 'Missing emp_code');
            return;
        }

        echo "\n[Row {$rowIndex}] Processing Emp: {$empCode}\n";

        $personCode = $this->derivePersonCode($row);

        if (!$this->createOrUpdatePerson($row, $personCode, $rowIndex)) return;
        if (!$this->createOrUpdateEmployee($row, $empCode, $personCode, $rowIndex)) return;
        $this->createOrUpdateUser($row, $empCode, $personCode, $rowIndex);

        $this->assignDesignationAndScopes($empCode, $row, $rowIndex);

        $this->success++;
        echo "[Row {$rowIndex}] ✅ SUCCESS - {$empCode}\n";
    }

    // ─────────────────────────────────────────────────────────────
    // PERSON
    // ─────────────────────────────────────────────────────────────
    private function createOrUpdatePerson(array $row, string $personCode, int $rowIndex): bool
    {
        $now = Carbon::now();
        $fullName = $this->s($this->getValue($row, ['employee_name', 'Employee Name*']));
        $nameParts = array_values(array_filter(explode(' ', $fullName)));
        $firstName  = $nameParts[0] ?? '';
        $middleName = $nameParts[1] ?? '';
        $lastName   = implode(' ', array_slice($nameParts, 2)) ?: ($nameParts[1] ?? '');

        $data = [
            'person_code' => $personCode,
            'display_name' => $fullName,
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'gender' => $this->s($this->getValue($row, ['gender'])),
            'dob' => $this->parseDate($this->getValue($row, ['date_of_birth'])),
            'pan_no' => $this->n($this->getValue($row, ['pan_no'])),
            'aadhaar_no' => $this->n($this->getValue($row, ['aadhaar_no'])),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $exists = DB::table('xlr8_admin_person')->where('person_code', $personCode)->exists();
        $exists ? DB::table('xlr8_admin_person')->where('person_code', $personCode)->update($data)
                : DB::table('xlr8_admin_person')->insert($data);

        // Mobile + Email + Address + Banking (simplified)
        $mobile = $this->cleanPhone($this->getValue($row, ['personal_contact_number']));
        if ($mobile) {
            DB::table('xlr8_admin_person_contacts')->updateOrInsert(
                ['person_code' => $personCode, 'data_type' => 'Mobile', 'contact_type' => 'Primary'],
                ['contact_detail' => $mobile, 'updated_at' => $now]
            );
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────
    // EMPLOYEE - Safe with OrgScopeService
    // ─────────────────────────────────────────────────────────────
    private function createOrUpdateEmployee(array $row, string $empCode, string $personCode, int $rowIndex): bool
    {
        $now = Carbon::now();

        $desigCode      = OrgScopeService::firstCode('designation', $this->getValue($row, ['designation', 'Designation*']));
        $verticalCode   = OrgScopeService::firstCode('vertical',   $this->getValue($row, ['vertical']));
        $segmentCode    = OrgScopeService::firstCode('segment',    $this->getValue($row, ['segment']));
        $subSegmentCode = OrgScopeService::firstCode('sub_segment',$this->getValue($row, ['sub_segment']));

        $data = [
            'code'                 => $empCode,
            'person_code'          => $personCode,
            'desig_code'           => $desigCode,
            'designation_code'     => $desigCode,
            'primary_branch_code'  => $this->code($this->getValue($row, ['primary_branch'])),
            'primary_loc_code'     => $this->code($this->getValue($row, ['primary_location'])),
            'primary_dept_code'    => $this->code($this->getValue($row, ['primary_department'])),
            'primary_div_code'     => $this->code($this->getValue($row, ['primary_division'])),
            'vertical_code'        => $verticalCode,
            'segment_code'         => $segmentCode,
            'sub_segment_code'     => $subSegmentCode,
            'father_name'          => $this->s($this->getValue($row, ['father_name'])),
            'employment_type'      => 'Permanent',
            'joining_date'         => $this->parseDate($this->getValue($row, ['date_of_joining'])),
            'created_at'           => $now,
            'updated_at'           => $now,
        ];

        $exists = DB::table('xlr8_admin_employee')->where('code', $empCode)->exists();
        $exists ? DB::table('xlr8_admin_employee')->where('code', $empCode)->update($data)
                : DB::table('xlr8_admin_employee')->insert($data);

        return true;
    }

    private function createOrUpdateUser(array $row, string $empCode, string $personCode, int $rowIndex): void
    {
        $now = Carbon::now();
        $username = strtolower($empCode);
        $userType = 'Emp';

        $mobile = $this->cleanPhone($this->getValue($row, ['personal_contact_number'])) ?? '1234567890';

        $data = [
            'username'      => $username,
            'password'      => Hash::make($mobile),
            'user_type'     => $userType,
            'person_code'   => $personCode,
            'employee_code' => $empCode,
            'is_active'     => 1,
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        $exists = DB::table('users')->where('username', $username)->exists();
        $exists ? DB::table('users')->where('username', $username)->update($data)
                : DB::table('users')->insert($data);
    }

    // ─────────────────────────────────────────────────────────────
    // Designation + Full Hierarchical User Scopes
    // ─────────────────────────────────────────────────────────────
    private function assignDesignationAndScopes(string $empCode, array $row, int $rowIndex): void
    {
        $user = \App\Models\User::where('username', strtolower($empCode))->first();
        if (!$user) return;

        $desigCode = OrgScopeService::firstCode('designation', $this->getValue($row, ['designation']));
        if ($desigCode) {
            \App\Models\Admin\Designation::firstOrCreate(
                ['code' => $desigCode],
                ['name' => $desigCode, 'guard_name' => 'web', 'is_active' => 1]
            );
            $user->assignRole($desigCode);
            $this->logRow($rowIndex, '✅ ASSIGNED (Designation Role)', $desigCode);
        }

        $this->syncUserScopes($user->id, $row, $rowIndex);
    }

    private function syncUserScopes(int $userId, array $row, int $rowIndex): void
    {
        $now = now()->toDateString();

        $scopeMap = [
            'primary_branch'    => 'branch',
            'addon_branch'      => 'branch',
            'primary_location'  => 'location',
            'addon_location'    => 'location',
            'primary_department'=> 'department',
            'addon_department'  => 'department',
            'primary_division'  => 'division',
            'add_on_divisions'  => 'division',
            'vertical'          => 'vertical',
            'segment'           => 'segment',
            'sub_segment'       => 'sub_segment',
            'models'            => 'model',
            'variant'           => 'variant',
            'variants'          => 'variant',
        ];

        $context = [];

        foreach ($scopeMap as $field => $scopeType) {
            $rawValue = $this->getValue($row, [$field]);
            if (!$rawValue) continue;

            $resolvedCodes = OrgScopeService::expandCodes($scopeType, $rawValue, $context);

            foreach ($resolvedCodes as $code) {
                DB::table('xlr8_admin_user_scopes')->updateOrInsert(
                    [
                        'user_id'    => $userId,
                        'scope_type' => $scopeType,
                        'scope_code' => $code,
                    ],
                    [
                        'is_active'  => 1,
                        'from_date'  => $now,
                        'to_date'    => null,
                        'updated_at' => now(),
                    ]
                );

                $this->logRow($rowIndex, '✅ SCOPED', "{$scopeType} = {$code}");
            }

            // Update context for child levels
            if (!empty($resolvedCodes) && $resolvedCodes[0] !== 'ALL') {
                $parentCol = $this->getParentColumn($scopeType);
                if ($parentCol) {
                    $context[$parentCol] = $resolvedCodes[0];
                }
            }
        }
    }

    private function getParentColumn(string $type): ?string
    {
        return match($type) {
            'location'    => 'branch_code',
            'division'    => 'dept_code',
            'sub_segment' => 'segment_code',
            'model'       => 'segment_code',
            'variant'     => 'model_code',
            default       => null,
        };
    }

    // ─────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────
    private function getValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                return trim((string)$row[$key]);
            }
        }
        return null;
    }

    private function derivePersonCode(array $row): string
    {
        $pan = $this->n($this->getValue($row, ['pan_no']));
        $aadhaar = $this->n($this->getValue($row, ['aadhaar_no']));
        if ($pan) return strtoupper($pan);
        if ($aadhaar) return strtoupper($aadhaar);

        static $seq = 0;
        return 'PRSN' . str_pad(++$seq, 5, '0', STR_PAD_LEFT);
    }

    private function s(mixed $v): string { return trim((string)($v ?? '')); }
    private function n(mixed $v): ?string {
        $v = trim((string)($v ?? ''));
        return in_array(strtolower($v), ['', 'null', 'n/a', 'na', '-', '?'], true) ? null : $v;
    }

    private function code(mixed $v, int $max = 0): ?string {
        $v = strtoupper(trim((string)($v ?? '')));
        if (in_array($v, ['', 'NULL', 'N/A', 'NA', '-'], true)) return null;
        return $max > 0 ? substr($v, 0, $max) : $v;
    }

    private function parseDate(mixed $v): ?string {
        if (!$v || trim((string)$v) === '') return null;
        try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable) { return null; }
    }

    private function cleanPhone(?string $v): ?string {
        if (!$v) return null;
        $v = preg_replace('/\D/', '', $v);
        $v = ltrim($v, '91'); $v = ltrim($v, '0');
        return strlen($v) === 10 ? $v : null;
    }

    private function logRow(int $rowIndex, string $status, string $msg = ''): void
    {
        $log = "[Row {$rowIndex}] {$status}" . ($msg ? " | {$msg}" : '');
        echo $log . PHP_EOL;
        Log::info($log);
    }
}