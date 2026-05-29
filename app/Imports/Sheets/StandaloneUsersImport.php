<?php

namespace App\Imports\Sheets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use App\Services\OrgScopeService;
use App\Services\HR\EmployeeJourneyService;
use App\Models\Admin\{Branch, Location, Designation, Department, Division, Vertical, PersonContact};
use App\Models\Vehicle\{Segment, SubSegment};

class StandaloneUsersImport implements ToCollection, WithHeadingRow
{
    private int $success = 0;
    private int $rowIndex = 1;
    private bool $headersDumped = false;

    private int $fallbackMobile = 0;
    private int $fallbackDesignation = 0;
    private int $fallbackBranch = 0;
    private int $fallbackLocation = 0;
    private int $fallbackDepartment = 0;
    private int $fallbackDivision = 0;
    private int $fallbackVertical = 0;
    private int $fallbackSegment = 0;
    private int $fallbackSubSegment = 0;
    private int $fallbackReportingManager = 0;

    public function collection(Collection $rows)
    {
        echo "\n🚀 Starting standalone Users_Import processing...\n\n";

        foreach ($rows as $row) {
            $this->rowIndex++;
            $this->processRow($row->toArray(), $this->rowIndex);
        }

        echo "\n✅ Standalone Users Import Completed! Success: {$this->success}\n";

        echo "\n════════════════════════════════════════════════════════════";
        echo "\n📊 FALLBACK SUMMARY REPORT";
        echo "\n════════════════════════════════════════════════════════════";
        echo "\nMobile (Personal Contact Number) : {$this->fallbackMobile}";
        echo "\nDesignation                      : {$this->fallbackDesignation}";
        echo "\nPrimary Branch                   : {$this->fallbackBranch}";
        echo "\nPrimary Location                 : {$this->fallbackLocation}";
        echo "\nPrimary Department               : {$this->fallbackDepartment}";
        echo "\nPrimary Division                 : {$this->fallbackDivision}";
        echo "\nVertical                         : {$this->fallbackVertical}";
        echo "\nSegment                          : {$this->fallbackSegment}";
        echo "\nSub Segment                      : {$this->fallbackSubSegment}";
        echo "\nReporting Manager                : {$this->fallbackReportingManager}";
        echo "\n════════════════════════════════════════════════════════════\n";
    }

    private function processRow(array $row, int $rowIndex): void
    {
        if (!$this->headersDumped) {
            echo "[DEBUG] First row keys: " . implode(', ', array_keys($row)) . "\n";
            $this->headersDumped = true;
        }

        $empCode = $this->getValue($row, ['emp_code', 'Emp Code*']);
        if (!$empCode) return;

        $rawPersonalMobile = $this->getValue($row, ['personal_contact_number', 'Personal Contact Number*']);
        $rawOfficialMobile = $this->getValue($row, ['official_contact_number', 'Official Contact Number']);
        $rawPersonalEmail  = $this->getValue($row, ['personal_mail_id', 'Personal Mail Id']);
        $rawOfficialEmail  = $this->getValue($row, ['official_mail_id', 'Official Mail ID']);

        $personCode = $this->derivePersonCode($row);

        $this->createOrUpdatePerson($row, $personCode, $rowIndex);
        $this->createOrUpdateEmployee($row, $empCode, $personCode, $rowIndex);
        $this->createOrUpdateUser($row, $empCode, $personCode, $rowIndex);

        $this->createPrimaryMobileContact($personCode, $rawPersonalMobile, $rawOfficialMobile, $rowIndex);
        $this->createPrimaryEmailContact($personCode, $rawPersonalEmail, $rawOfficialEmail, $rowIndex);

        $this->assignDesignationAndScopes($empCode, $row, $rowIndex);

        $this->success++;
        echo "[Row {$rowIndex}] ✅ SUCCESS - {$empCode}\n";
    }

    // ==================== PERSON ====================
    private function createOrUpdatePerson(array $row, string $personCode, int $rowIndex): void
    {
        $now = Carbon::now();
        $fullName = $this->s($this->getValue($row, ['employee_name', 'Employee Name*']));
        $nameParts = array_values(array_filter(explode(' ', $fullName)));

        $data = [
            'person_code'   => $personCode,
            'display_name'  => $fullName,
            'first_name'    => $nameParts[0] ?? '',
            'middle_name'   => $nameParts[1] ?? '',
            'last_name'     => implode(' ', array_slice($nameParts, 2)) ?: ($nameParts[1] ?? ''),
            'gender'        => $this->getValue($row, ['gender']),
            'dob'           => $this->parseDate($this->getValue($row, ['date_of_birth'])),
            'pan_no'        => $this->n($this->getValue($row, ['pan_no'])),
            'aadhaar_no'    => $this->n($this->getValue($row, ['aadhaar_no'])),
            'created_at'    => $now,
            'updated_at'    => $now,
        ];

        DB::table('xlr8_admin_person')->updateOrInsert(['person_code' => $personCode], $data);
    }

    // ==================== EMPLOYEE ====================
    private function createOrUpdateEmployee(array $row, string $empCode, string $personCode, int $rowIndex): void
    {
        $now = Carbon::now();

        $rawDesig = $this->getValue($row, ['designation', 'Designation*']);
        $desigCode = $this->resolveDesignationCode($rawDesig);

        if (!$desigCode && $rawDesig) {
            $this->fallbackDesignation++;
            echo "[Row {$rowIndex}] ⚠️  FALLBACK DESIGNATION → Could not resolve: {$rawDesig}\n";
        }

        $primaryBranch   = $this->resolveSingleCode('branch', $this->getValue($row, ['primary_branch', 'Primary Branch*']));
        $primaryLocation = $this->resolveSingleCode('location', $this->getValue($row, ['primary_location', 'Primary Location*']));
        $primaryDept     = $this->resolveSingleCode('department', $this->getValue($row, ['primary_department', 'Primary Department*']));
        $primaryDiv      = $this->resolveSingleCode('division', $this->getValue($row, ['primary_division', 'Primary Division']));
        $verticalCode    = $this->resolveSingleCode('vertical', $this->getValue($row, ['vertical']));
        $segmentCode     = $this->resolveSingleCode('segment', $this->getValue($row, ['segment']));
        $subSegmentCode  = $this->resolveSingleCode('sub_segment', $this->getValue($row, ['sub_segment', 'Sub Segment']));

        $reportingManagerRaw = $this->getValue($row, ['reporting_manager', 'Reporting Manager']);
        $reportingManagerCode = $this->extractEmployeeCode($reportingManagerRaw);

        if (!$primaryBranch && $this->getValue($row, ['primary_branch', 'Primary Branch*'])) {
            $this->fallbackBranch++;
            echo "[Row {$rowIndex}] ⚠️  FALLBACK BRANCH → Raw: " . $this->getValue($row, ['primary_branch', 'Primary Branch*']) . "\n";
        }

        $data = [
            'code'                   => $empCode,
            'person_code'            => $personCode,
            'desig_code'             => ($desigCode && strlen($desigCode) <= 10) ? $desigCode : null,
            'designation_code'       => $desigCode,
            'primary_branch_code'    => $primaryBranch,
            'primary_loc_code'       => $primaryLocation,
            'primary_dept_code'      => $primaryDept,
            'primary_div_code'       => $primaryDiv,
            'mile_id'                => $this->getValue($row, ['oem_mile_id', 'OEM Mile ID', 'Mile ID']),
            'vertical_code'          => $verticalCode,
            'segment_code'           => $segmentCode,
            'sub_segment_code'       => $subSegmentCode,
            'reporting_manager_code' => $reportingManagerCode,
            'employment_type'        => 'Permanent',
            'joining_date'           => $this->parseDate($this->getValue($row, ['date_of_joining'])),
            'created_at'             => $now,
            'updated_at'             => $now,
        ];

        DB::table('xlr8_admin_employee')->updateOrInsert(['code' => $empCode], $data);
        app(EmployeeJourneyService::class)->logChange($empCode, $data, 'import', 'Imported/Updated from Excel');
    }

    // ==================== CONTACTS ====================
    private function createPrimaryMobileContact(string $personCode, ?string $personal, ?string $official, int $rowIndex): void
    {
        $mobile = $personal ?: $official;
        if (!$mobile) return;

        $cleaned = $this->cleanPhone($mobile);
        if (!$cleaned) return;

        $exists = PersonContact::where('person_code', $personCode)
            ->where('data_type', 'Mobile')
            ->where('contact_type', 'Primary')
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) return;

        PersonContact::create([
            'person_code'    => $personCode,
            'data_type'      => 'Mobile',
            'contact_type'   => 'Primary',
            'contact_detail' => $cleaned,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function createPrimaryEmailContact(string $personCode, ?string $personal, ?string $official, int $rowIndex): void
    {
        $email = $personal ?: $official;
        if (!$email) return;

        $cleaned = trim($email);
        if (!filter_var($cleaned, FILTER_VALIDATE_EMAIL)) return;

        $exists = PersonContact::where('person_code', $personCode)
            ->where('data_type', 'Email')
            ->where('contact_type', 'Primary')
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) return;

        PersonContact::create([
            'person_code'    => $personCode,
            'data_type'      => 'Email',
            'contact_type'   => 'Primary',
            'contact_detail' => $cleaned,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    // ==================== USER ====================
    private function createOrUpdateUser(array $row, string $empCode, string $personCode, int $rowIndex): void
    {
        $now = Carbon::now();
        $username = strtolower($empCode);

        $rawMobile = $this->getValue($row, ['personal_contact_number', 'Personal Contact Number*', 'official_contact_number', 'Official Contact Number']);
        $cleanedMobile = $this->cleanPhone($rawMobile);
        $finalMobile = $cleanedMobile ?? '1234567890';

        if (!$cleanedMobile) {
            $this->fallbackMobile++;
            echo "[Row {$rowIndex}] ⚠️  FALLBACK MOBILE USED → 1234567890\n";
        }

        $data = [
            'username'      => $username,
            'password'      => Hash::make($finalMobile),
            'user_type'     => 'Emp',
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

    // ==================== ASSIGN + SCOPES ====================
    private function assignDesignationAndScopes(string $empCode, array $row, int $rowIndex): void
    {
        $user = \App\Models\User::where('username', strtolower($empCode))->first();
        if (!$user) return;

        $rawDesig = $this->getValue($row, ['designation', 'Designation*']);
        $desigCode = $this->resolveDesignationCode($rawDesig);

        if ($desigCode) {
            $role = Designation::where('code', $desigCode)->first();
            if ($role) {
                try {
                    $user->assignRole($role);
                } catch (\Throwable $e) {
                    echo "[Row {$rowIndex}] ⚠️  Could not assign role: " . $e->getMessage() . "\n";
                }
            }
        }

        $this->syncUserScopes($user->id, $row, $rowIndex);
    }

    // ==================== CORE SCOPING LOGIC (AS PER YOUR RULES) ====================
    private function syncUserScopes(int $userId, array $row, int $rowIndex): void
    {
        $now = now()->toDateString();

        // 1. PRIMARY BRANCH
        $primaryBranchRaw = $this->getValue($row, ['primary_branch', 'Primary Branch*']);
        $primaryBranchCodes = $this->resolvePrimaryField('branch', $primaryBranchRaw);

        // 2. ADDON BRANCH (only if Primary Branch was specific)
        $addonBranchCodes = [];
        if (!empty($primaryBranchCodes) && count($primaryBranchCodes) === 1) {
            $addonBranchRaw = $this->getValue($row, ['addon_branch', 'Addon Branch']);
            if ($addonBranchRaw) {
                $addonBranchCodes = $this->resolveEntityCodes('branch', $addonBranchRaw);
            }
        }

        // 3. PRIMARY LOCATION (hierarchical with Primary Branch)
        $primaryLocationRaw = $this->getValue($row, ['primary_location', 'Primary Location*']);
        $primaryLocationCodes = $this->resolvePrimaryLocation($primaryLocationRaw, $primaryBranchCodes);

        // 4. ADDON LOCATION (only if Primary Location was specific)
        $addonLocationCodes = [];
        if (!empty($primaryLocationCodes) && count($primaryLocationCodes) === 1) {
            $addonLocationRaw = $this->getValue($row, ['addon_location', 'AddOn Location']);
            if ($addonLocationRaw) {
                $addonLocationCodes = $this->resolveEntityCodes('location', $addonLocationRaw);
            }
        }

        // 5. PRIMARY DEPARTMENT
        $primaryDeptRaw = $this->getValue($row, ['primary_department', 'Primary Department*']);
        $primaryDeptCodes = $this->resolvePrimaryField('department', $primaryDeptRaw);

        // 6. ADDON DEPARTMENT (only if Primary Department was specific)
        $addonDeptCodes = [];
        if (!empty($primaryDeptCodes) && count($primaryDeptCodes) === 1) {
            $addonDeptRaw = $this->getValue($row, ['addon_department', 'Addon Department']);
            if ($addonDeptRaw) {
                $addonDeptCodes = $this->resolveEntityCodes('department', $addonDeptRaw);
            }
        }

        // 7. PRIMARY DIVISION (hierarchical with Primary Department)
        $primaryDivRaw = $this->getValue($row, ['primary_division', 'Primary Division']);
        $primaryDivCodes = $this->resolvePrimaryDivision($primaryDivRaw, $primaryDeptCodes);

        // 8. ADDON DIVISION (only if Primary Division was specific)
        $addonDivCodes = [];
        if (!empty($primaryDivCodes) && count($primaryDivCodes) === 1) {
            $addonDivRaw = $this->getValue($row, ['add_on_divisions', 'Add On Divisions']);
            if ($addonDivRaw) {
                $addonDivCodes = $this->resolveEntityCodes('division', $addonDivRaw);
            }
        }

        // 9. VERTICAL (multi)
        $verticalCodes = $this->resolveMultiField('vertical', $this->getValue($row, ['vertical']));

        // 10. SEGMENT (multi)
        $segmentCodes = $this->resolveMultiField('segment', $this->getValue($row, ['segment']));

        // 11. SUB SEGMENT (multi + child filter)
        $subSegmentCodes = $this->resolveChildField('sub_segment', $this->getValue($row, ['sub_segment', 'Sub Segment']), 'segment', $segmentCodes);

        // 12. MODELS (multi + child filter)
        $modelCodes = $this->resolveChildField('model', $this->getValue($row, ['models']), 'sub_segment', $subSegmentCodes);

        // Save all scopes
        $allScopes = [
            'branch'      => array_unique(array_merge($primaryBranchCodes, $addonBranchCodes)),
            'location'    => array_unique(array_merge($primaryLocationCodes, $addonLocationCodes)),
            'department'  => array_unique(array_merge($primaryDeptCodes, $addonDeptCodes)),
            'division'    => array_unique(array_merge($primaryDivCodes, $addonDivCodes)),
            'vertical'    => $verticalCodes,
            'segment'     => $segmentCodes,
            'sub_segment' => $subSegmentCodes,
            'model'       => $modelCodes,
        ];

        foreach ($allScopes as $scopeType => $codes) {
            foreach ($codes as $code) {
                if (!$code) continue;
                DB::table('xlr8_admin_user_scopes')->updateOrInsert(
                    [
                        'user_id'    => $userId,
                        'scope_type' => $scopeType,
                        'scope_code' => strtoupper($code),
                    ],
                    [
                        'is_active'  => 1,
                        'from_date'  => $now,
                        'to_date'    => null,
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }

    // ==================== HELPER METHODS FOR SCOPING ====================

    private function resolvePrimaryField(string $type, ?string $input): array
    {
        if (!$input || in_array(strtoupper(trim($input)), ['ALL', 'ANY', ''])) {
            return match($type) {
                'branch'     => Branch::where('is_active', true)->pluck('code')->toArray(),
                'department' => Department::where('is_active', true)->pluck('code')->toArray(),
                default      => [],
            };
        }
        return $this->resolveEntityCodes($type, $input);
    }

    private function resolvePrimaryLocation(?string $input, array $primaryBranchCodes): array
    {
        if (!$input || in_array(strtoupper(trim($input)), ['ALL', 'ANY', ''])) {
            if (empty($primaryBranchCodes)) {
                return Location::where('is_active', true)->pluck('code')->toArray();
            }
            return Location::whereIn('branch_code', $primaryBranchCodes)->where('is_active', true)->pluck('code')->toArray();
        }
        return $this->resolveEntityCodes('location', $input);
    }

    private function resolvePrimaryDivision(?string $input, array $primaryDeptCodes): array
    {
        if (!$input || in_array(strtoupper(trim($input)), ['ALL', 'ANY', ''])) {
            if (empty($primaryDeptCodes)) {
                return Division::where('is_active', true)->pluck('code')->toArray();
            }
            return Division::whereIn('dept_code', $primaryDeptCodes)->where('is_active', true)->pluck('code')->toArray();
        }
        return $this->resolveEntityCodes('division', $input);
    }

    private function resolveMultiField(string $type, ?string $input): array
    {
        if (!$input || in_array(strtoupper(trim($input)), ['ALL', 'ANY', ''])) {
            return match($type) {
                'vertical' => Vertical::where('is_active', true)->pluck('code')->toArray(),
                'segment'  => Segment::where('is_active', true)->pluck('code')->toArray(),
                default    => [],
            };
        }
        return $this->resolveEntityCodes($type, $input);
    }

    private function resolveChildField(string $type, ?string $input, string $parentType, array $parentCodes): array
    {
        if (!$input || in_array(strtoupper(trim($input)), ['ALL', 'ANY', ''])) {
            if ($type === 'sub_segment' && !empty($parentCodes)) {
                return SubSegment::whereIn('segment_code', $parentCodes)->where('is_active', true)->pluck('code')->toArray();
            }
            if ($type === 'model' && !empty($parentCodes)) {
                return \App\Models\Vehicle\VehicleModel::whereIn('sub_segment_code', $parentCodes)->where('is_active', true)->pluck('code')->toArray();
            }
            return match($type) {
                'sub_segment' => SubSegment::where('is_active', true)->pluck('code')->toArray(),
                'model'       => \App\Models\Vehicle\VehicleModel::where('is_active', true)->pluck('code')->toArray(),
                default       => [],
            };
        }
        return $this->resolveEntityCodes($type, $input);
    }

    // ==================== EXISTING HELPER METHODS (unchanged) ====================

    private function resolveSingleCode(string $type, ?string $input): ?string
    {
        $codes = $this->resolveEntityCodes($type, $input);
        return $codes[0] ?? null;
    }

    private function resolveEntityCodes(string $type, ?string $input): array
    {
        if (!$input || in_array(strtoupper(trim($input)), ['ALL', 'ANY', ''])) {
            return match($type) {
                'branch'      => Branch::where('is_active', true)->pluck('code')->toArray(),
                'location'    => Location::where('is_active', true)->pluck('code')->toArray(),
                'department'  => Department::where('is_active', true)->pluck('code')->toArray(),
                'division'    => Division::where('is_active', true)->pluck('code')->toArray(),
                'vertical'    => Vertical::where('is_active', true)->pluck('code')->toArray(),
                'segment'     => Segment::where('is_active', true)->pluck('code')->toArray(),
                'sub_segment' => SubSegment::where('is_active', true)->pluck('code')->toArray(),
                default       => [],
            };
        }

        $parts = array_map('trim', explode(',', $input));
        $resolved = [];

        foreach ($parts as $part) {
            if (!$part) continue;
            $upper = strtoupper($part);

            $model = match($type) {
                'branch'      => Branch::class,
                'location'    => Location::class,
                'department'  => Department::class,
                'division'    => Division::class,
                'vertical'    => Vertical::class,
                'segment'     => Segment::class,
                'sub_segment' => SubSegment::class,
                default       => null,
            };

            if (!$model) continue;

            $record = $model::whereRaw('UPPER(code) = ?', [$upper])->first();
            if (!$record) {
                $record = $model::whereRaw('UPPER(name) LIKE ?', ["%{$upper}%"])->first();
            }

            if ($record) {
                $resolved[] = $record->code;
            }
        }

        return array_unique($resolved);
    }

    private function resolveDesignationCode(?string $value): ?string
    {
        if (!$value) return null;
        $val = strtoupper(trim($value));

        $d = Designation::whereRaw('UPPER(code) = ?', [$val])->first();
        if ($d) return $d->code;

        $d = Designation::whereRaw('UPPER(name) = ?', [$val])->first();
        if ($d) return $d->code;

        $d = Designation::whereRaw('UPPER(name) LIKE ?', ["%{$val}%"])->first();
        if ($d) return $d->code;

        return null;
    }

    private function extractEmployeeCode(?string $value): ?string
    {
        if (!$value) return null;
        if (preg_match('/\(([A-Z0-9-]+)\)/', $value, $matches)) {
            return strtoupper(trim($matches[1]));
        }
        return null;
    }

    private function getValue(array $row, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') return trim((string)$row[$key]);
            foreach ($row as $rowKey => $value) {
                if (strtolower(trim($rowKey)) === strtolower(trim($key)) && trim((string)$value) !== '') {
                    return trim((string)$value);
                }
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
        return in_array(strtolower($v), ['', 'null', 'n/a', 'na', '-', '?', '0'], true) ? null : $v;
    }

    private function parseDate(mixed $v): ?string {
        if (!$v || trim((string)$v) === '') return null;
        try { return Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable) { return null; }
    }

    private function cleanPhone(?string $v): ?string {
        if (!$v) return null;
        $v = preg_replace('/\D/', '', $v);
        if (strlen($v) === 12 && str_starts_with($v, '91')) $v = substr($v, 2);
        if (strlen($v) === 11 && str_starts_with($v, '0')) $v = substr($v, 1);
        return strlen($v) === 10 ? $v : null;
    }
}