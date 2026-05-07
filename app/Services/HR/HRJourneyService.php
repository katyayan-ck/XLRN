<?php

namespace App\Services\HR;

use App\Enums\ErrorCodeEnum;
use App\Exceptions\DomainException;
use App\Models\Admin\EmpPostAssignment;
use App\Models\IAM\Post;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * HRJourneyService — manages entire employee post lifecycle.
 *
 * All events write to xlr8_admin_emp_post_assignments with relieving_type.
 * Business invariants:
 *  - An employee can have ONE primary active post at any time
 *  - An employee can have multiple additional posts simultaneously
 *  - Assigning a new primary auto-relieves the current primary
 */
class HRJourneyService
{
    // ── Onboarding ────────────────────────────────────────────────────

    public function onboard(
        string $empCode,
        string $postCode,
        Carbon|string $fromDate,
        string $remarks = ''
    ): EmpPostAssignment {
        return DB::transaction(function () use ($empCode, $postCode, $fromDate, $remarks) {
            $post = $this->requireVacantPost($postCode);

            // Ensure no active primary already exists
            $existing = EmpPostAssignment::forEmployee($empCode)
                                         ->primary()
                                         ->current()
                                         ->first();
            if ($existing) {
                throw new DomainException(
                    ErrorCodeEnum::EMP_ALREADY_HAS_PRIMARY_POST,
                    "Employee [{$empCode}] already has active primary post [{$existing->post_code}]. Use transfer() instead."
                );
            }

            return EmpPostAssignment::create([
                'emp_code'        => $empCode,
                'post_code'       => $postCode,
                'assignment_type' => 'primary',
                'from_date'       => Carbon::parse($fromDate)->toDateString(),
                'to_date'         => null,
                'relieving_type'  => 'onboarding',
                'remarks'         => $remarks,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    // ── Transfer ──────────────────────────────────────────────────────

    public function transfer(
        string $empCode,
        string $newPostCode,
        Carbon|string $effectiveDate,
        string $remarks = ''
    ): array {
        return DB::transaction(function () use ($empCode, $newPostCode, $effectiveDate, $remarks) {
            $date = Carbon::parse($effectiveDate)->toDateString();

            // Relieve from current primary post
            $current = EmpPostAssignment::forEmployee($empCode)->primary()->current()->first();
            if ($current) {
                $current->update([
                    'to_date'      => $date,
                    'relieving_type' => 'transfer',
                    'remarks'      => "Transferred to {$newPostCode}",
                    'updated_by'   => auth()->id(),
                ]);
            }

            $this->requireVacantPost($newPostCode);

            // Assign new primary post
            $new = EmpPostAssignment::create([
                'emp_code'        => $empCode,
                'post_code'       => $newPostCode,
                'assignment_type' => 'primary',
                'from_date'       => $date,
                'to_date'         => null,
                'relieving_type'  => 'transfer',
                'remarks'         => $remarks,
                'created_by'      => auth()->id(),
            ]);

            return ['relieved' => $current, 'assigned' => $new];
        });
    }

    // ── Promotion / Demotion ──────────────────────────────────────────

    public function promote(
        string $empCode,
        string $newPostCode,
        Carbon|string $effectiveDate,
        string $remarks = ''
    ): array {
        return $this->changePost($empCode, $newPostCode, $effectiveDate, 'promotion', $remarks);
    }

    public function demote(
        string $empCode,
        string $newPostCode,
        Carbon|string $effectiveDate,
        string $remarks = ''
    ): array {
        return $this->changePost($empCode, $newPostCode, $effectiveDate, 'demotion', $remarks);
    }

    // ── Additional Charge ─────────────────────────────────────────────

    public function assignAdditionalCharge(
        string $empCode,
        string $postCode,
        Carbon|string $fromDate,
        ?Carbon $toDate = null,
        string $remarks = ''
    ): EmpPostAssignment {
        return DB::transaction(function () use ($empCode, $postCode, $fromDate, $toDate, $remarks) {
            // Check for existing additional charge on same post
            $existing = EmpPostAssignment::forEmployee($empCode)
                                         ->additional()
                                         ->forPost($postCode)
                                         ->current()
                                         ->first();
            if ($existing) {
                throw new DomainException(
                    ErrorCodeEnum::EMP_ALREADY_HAS_ADDITIONAL_POST,
                    "Employee [{$empCode}] already holds additional charge of [{$postCode}]."
                );
            }

            return EmpPostAssignment::create([
                'emp_code'        => $empCode,
                'post_code'       => $postCode,
                'assignment_type' => 'additional',
                'from_date'       => Carbon::parse($fromDate)->toDateString(),
                'to_date'         => $toDate?->toDateString(),
                'relieving_type'  => 'additional_charge',
                'remarks'         => $remarks,
                'created_by'      => auth()->id(),
            ]);
        });
    }

    public function relieveAdditionalCharge(
        string $empCode,
        string $postCode,
        Carbon|string $reliefDate,
        string $remarks = ''
    ): EmpPostAssignment {
        return DB::transaction(function () use ($empCode, $postCode, $reliefDate, $remarks) {
            $assignment = EmpPostAssignment::forEmployee($empCode)
                                            ->additional()
                                            ->forPost($postCode)
                                            ->current()
                                            ->firstOrFail();

            $assignment->update([
                'to_date'        => Carbon::parse($reliefDate)->toDateString(),
                'relieving_type' => 'charge_relieved',
                'remarks'        => $remarks,
                'updated_by'     => auth()->id(),
            ]);

            return $assignment->fresh();
        });
    }

    // ── Separation ────────────────────────────────────────────────────

    public function separate(
        string $empCode,
        Carbon|string $separationDate,
        string $type = 'relieving',
        string $remarks = ''
    ): Collection {
        return DB::transaction(function () use ($empCode, $separationDate, $type, $remarks) {
            $date = Carbon::parse($separationDate)->toDateString();

            // Relieve ALL active assignments (primary + additional)
            $activeAssignments = EmpPostAssignment::forEmployee($empCode)->current()->get();

            foreach ($activeAssignments as $assignment) {
                $assignment->update([
                    'to_date'        => $date,
                    'relieving_type' => $type,
                    'remarks'        => $remarks,
                    'relieved_by'    => auth()->id(),
                    'updated_by'     => auth()->id(),
                ]);
            }

            return $activeAssignments;
        });
    }

    // ── Read ──────────────────────────────────────────────────────────

    public function getJourney(string $empCode): Collection
    {
        return EmpPostAssignment::forEmployee($empCode)
                                 ->with('post.designation')
                                 ->chronological()
                                 ->get();
    }

    public function getCurrentPost(string $empCode): ?EmpPostAssignment
    {
        return EmpPostAssignment::forEmployee($empCode)
                                 ->primary()
                                 ->current()
                                 ->with('post.designation')
                                 ->first();
    }

    public function getPostOnDate(string $empCode, Carbon|string $date): ?EmpPostAssignment
    {
        return EmpPostAssignment::forEmployee($empCode)
                                 ->primary()
                                 ->onDate($date)
                                 ->with('post')
                                 ->first();
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function changePost(
        string $empCode,
        string $newPostCode,
        Carbon|string $effectiveDate,
        string $type,
        string $remarks
    ): array {
        return DB::transaction(function () use ($empCode, $newPostCode, $effectiveDate, $type, $remarks) {
            $date    = Carbon::parse($effectiveDate)->toDateString();
            $current = EmpPostAssignment::forEmployee($empCode)->primary()->current()->first();

            if ($current) {
                $current->update([
                    'to_date'        => $date,
                    'relieving_type' => $type,
                    'updated_by'     => auth()->id(),
                ]);
            }

            $this->requireVacantPost($newPostCode);

            $new = EmpPostAssignment::create([
                'emp_code'        => $empCode,
                'post_code'       => $newPostCode,
                'assignment_type' => 'primary',
                'from_date'       => $date,
                'to_date'         => null,
                'relieving_type'  => $type,
                'remarks'         => $remarks,
                'created_by'      => auth()->id(),
            ]);

            return ['relieved' => $current, 'assigned' => $new];
        });
    }

    private function requireVacantPost(string $postCode): Post
    {
        $post = Post::where('post_code', $postCode)->active()->first();
        if (!$post) {
            throw new DomainException(
                ErrorCodeEnum::POST_NOT_FOUND,
                "Post [{$postCode}] not found or inactive."
            );
        }
        if (!$post->isVacant()) {
            throw new DomainException(
                ErrorCodeEnum::POST_FULLY_OCCUPIED,
                "Post [{$postCode}] has no vacancy (max_occupants={$post->max_occupants})."
            );
        }
        return $post;
    }
}