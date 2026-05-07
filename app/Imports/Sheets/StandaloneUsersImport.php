<?php

namespace App\Imports\Sheets;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class StandaloneUsersImport implements ToCollection, WithHeadingRow
{
    private int $success = 0;
    private int $rowIndex = 1;
    private bool $headersDumped = false;

    public function collection(Collection $rows)
    {
        Log::info('=== Standalone Users Import Started ===');
        echo "\n🚀 Starting standalone Users_Import processing...\n\n";

        foreach ($rows as $row) {
            $this->rowIndex++;
            $this->processRow($row->toArray(), $this->rowIndex);
        }

        echo "\n✅ Standalone Users Import Completed!\n";
        echo "Total rows processed: " . ($this->rowIndex - 1) . " | Successful: {$this->success}\n\n";
        Log::info("Standalone Users Import Finished. Rows: " . ($this->rowIndex - 1) . " | Success: {$this->success}");
    }

    private function processRow(array $row, int $rowIndex): void
    {
        if (!$this->headersDumped) {
            echo "[DEBUG] First row keys: " . implode(', ', array_keys($row)) . "\n";
            $this->headersDumped = true;
        }

        $empCode = $this->getValue($row, ['emp_code', 'emp code', 'Emp Code*']);
        if (!$empCode) {
            $this->logRow($rowIndex, '❌ FAIL', 'Missing emp_code');
            return;
        }

        echo "\n[Row {$rowIndex}] Processing Emp: {$empCode} - " . $this->s($this->getValue($row, ['employee_name', 'Employee Name*'])) . "\n";

        $personCode = $this->derivePersonCode($row);

        if (!$this->createOrUpdatePerson($row, $personCode, $rowIndex)) return;
        if (!$this->createOrUpdateEmployee($row, $empCode, $personCode, $rowIndex)) return;
        $this->createOrUpdateUser($row, $empCode, $personCode, $rowIndex);
        $this->assignPrimaryPost($row, $empCode, $rowIndex);

        $this->success++;
        echo "[Row {$rowIndex}] ✅ SUCCESS - All stages completed for {$empCode}\n";
    }

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
        $pan = $this->n($this->getValue($row, ['pan_no', 'PAN No']));
        $aadhaar = $this->n($this->getValue($row, ['aadhaar_no', 'Aadhaar No']));
        if ($pan) return strtoupper($pan);
        if ($aadhaar) return strtoupper($aadhaar);

        static $seq = 0;
        return 'PRSN' . str_pad(++$seq, 5, '0', STR_PAD_LEFT);
    }

    private function createOrUpdatePerson(array $row, string $personCode, int $rowIndex): bool
    {
        $now = Carbon::now();
        $data = [
            'person_code'  => $personCode,
            'display_name' => $this->s($this->getValue($row, ['employee_name', 'Employee Name*'])),
            'gender'       => $this->s($this->getValue($row, ['gender', 'Gender'])),
            'dob'          => $this->parseDate($this->getValue($row, ['date_of_birth', 'Date of Birth'])),
            'marital_status'=> $this->s($this->getValue($row, ['marital_status', 'Marital Status'])),
            'pan_no'       => $this->n($this->getValue($row, ['pan_no', 'PAN No'])),
            'aadhaar_no'   => $this->n($this->getValue($row, ['aadhaar_no', 'Aadhaar No'])),
            'created_at'   => $now,
            'updated_at'   => $now,
        ];

        $exists = DB::table('xlr8_admin_person')->where('person_code', $personCode)->exists();
        if ($exists) {
            DB::table('xlr8_admin_person')->where('person_code', $personCode)->update($data);
            $this->logRow($rowIndex, '🔄 UPDATED (Person)', "person_code = {$personCode}");
        } else {
            DB::table('xlr8_admin_person')->insert($data);
            $this->logRow($rowIndex, '✅ CREATED (Person)', "person_code = {$personCode}");
        }

        $mobile = $this->cleanPhone($this->getValue($row, ['personal_contact_number', 'Personal Contact Number*']));
        if ($mobile) {
            DB::table('xlr8_admin_person_contacts')->updateOrInsert(
                ['person_code' => $personCode, 'data_type' => 'Mobile', 'contact_type' => 'Primary'],
                ['contact_detail' => $mobile, 'updated_at' => $now]
            );
        }

        $email = $this->n($this->getValue($row, ['official_mail_id', 'Official Mail ID', 'personal_mail_id']));
        if ($email) {
            DB::table('xlr8_admin_person_contacts')->updateOrInsert(
                ['person_code' => $personCode, 'data_type' => 'Email', 'contact_type' => 'Primary'],
                ['contact_detail' => $email, 'updated_at' => $now]
            );
        }
        return true;
    }

    private function createOrUpdateEmployee(array $row, string $empCode, string $personCode, int $rowIndex): bool
    {
        $now = Carbon::now();
        $data = [
            'code'                => $empCode,
            'person_code'         => $personCode,
            'desig_code'          => $this->code($this->getValue($row, ['designation', 'Designation*'])),
            'primary_branch_code' => $this->code($this->getValue($row, ['primary_branch', 'Primary Branch*'])),
            'primary_loc_code'    => $this->code($this->getValue($row, ['primary_location', 'Primary Location*'])),
            'primary_dept_code'   => $this->code($this->getValue($row, ['primary_department', 'Primary Department*'])),
            'primary_div_code'    => $this->code($this->getValue($row, ['primary_division', 'Primary Division'])),
            'father_name'         => $this->s($this->getValue($row, ['father_name', 'Father Name'])),
            'employment_type'     => 'permanent',
            'employment_status'   => 'active',
            'joining_date'        => $this->parseDate($this->getValue($row, ['date_of_joining', 'Date of Joining'])),
            'created_at'          => $now,
            'updated_at'          => $now,
        ];

        $exists = DB::table('xlr8_admin_employee')->where('code', $empCode)->exists();
        if ($exists) {
            DB::table('xlr8_admin_employee')->where('code', $empCode)->update($data);
            $this->logRow($rowIndex, '🔄 UPDATED (Employee)', "emp_code = {$empCode}");
        } else {
            DB::table('xlr8_admin_employee')->insert($data);
            $this->logRow($rowIndex, '✅ CREATED (Employee)', "emp_code = {$empCode}");
        }
        return true;
    }

    private function createOrUpdateUser(array $row, string $empCode, string $personCode, int $rowIndex): void
    {
        $now = Carbon::now();
        $username = strtolower($empCode);
        $desig = strtoupper($this->code($this->getValue($row, ['designation', 'Designation*'])));
        $userType = in_array($desig, ['RTO', 'DSA']) ? 'Associate' : 'Emp';

        $mobile = $this->cleanPhone($this->getValue($row, ['personal_contact_number', 'Personal Contact Number*'])) ?? '1234567890';
        $password = Hash::make($mobile);

        $data = [
            'username'       => $username,
            'password'       => $password,
            'user_type'      => $userType,           // ← correct column
            'person_code'    => $personCode,
            'employee_code'  => $empCode,
            'is_active'      => 1,
            'created_at'     => $now,
            'updated_at'     => $now,
        ];

        $exists = DB::table('users')->where('username', $username)->exists();
        if ($exists) {
            DB::table('users')->where('username', $username)->update($data);
            $this->logRow($rowIndex, '🔄 UPDATED (User)', "username = {$username} | type = {$userType}");
        } else {
            DB::table('users')->insert($data);
            $this->logRow($rowIndex, '✅ CREATED (User)', "username = {$username} | type = {$userType}");
        }
    }

    private function assignPrimaryPost(array $row, string $empCode, int $rowIndex): void
    {
        $basePostCode = $this->code($this->getValue($row, ['primary_post', 'post_code', 'Post_Code']));
        if (!$basePostCode) return;

        $finalPostCode = $this->getNextAvailablePostCode($basePostCode);

        $exists = DB::table('xlr8_iam_roles')->where('post_code', $finalPostCode)->where('is_post', 1)->exists();
        if (!$exists) {
            DB::table('xlr8_iam_roles')->insert([
                'post_code'    => $finalPostCode,
                'name'         => $finalPostCode,
                'guard_name'   => 'web',
                'is_post'      => true,
                'display_name' => $finalPostCode,
                'branch_code'  => $this->code($this->getValue($row, ['primary_branch'])),
                'loc_code'     => $this->code($this->getValue($row, ['primary_location'])),
                'dept_code'    => $this->code($this->getValue($row, ['primary_department'])),
                'div_code'     => $this->code($this->getValue($row, ['primary_division'])),
                'desig_code'   => $this->code($this->getValue($row, ['designation'])),
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $this->logRow($rowIndex, '✅ CREATED (Post)', "post_code = {$finalPostCode}");
        }

        DB::table('xlr8_admin_emp_post_assignments')->updateOrInsert(
            ['emp_code' => $empCode, 'post_code' => $finalPostCode, 'to_date' => null],
            ['assignment_type' => 'primary', 'from_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]
        );

        $this->logRow($rowIndex, '✅ ASSIGNED (Post)', "emp_code={$empCode} → post_code={$finalPostCode}");
    }

    private function getNextAvailablePostCode(string $base): string
    {
        $codes = DB::table('xlr8_iam_roles')
            ->where('post_code', 'LIKE', $base.'%')
            ->where('is_post', 1)
            ->pluck('post_code')
            ->toArray();

        if (empty($codes)) return $base;

        $maxSeq = 1;
        foreach ($codes as $code) {
            if ($code === $base) continue;
            if (preg_match('/' . preg_quote($base, '/') . '_(\d+)$/', $code, $m)) {
                $maxSeq = max($maxSeq, (int)$m[1]);
            }
        }

        $baseAssigned = DB::table('xlr8_admin_emp_post_assignments')
            ->where('post_code', $base)
            ->whereNull('to_date')
            ->exists();

        return $baseAssigned ? $base . '_' . str_pad($maxSeq + 1, 3, '0', STR_PAD_LEFT) : $base;
    }

    // ====================== HELPERS ======================
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