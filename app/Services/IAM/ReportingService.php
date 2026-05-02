<?php

namespace App\Services\IAM;

use App\Models\Admin\DesigDeptTree;
use App\Models\Admin\EmpPostAssignment;
use App\Models\IAM\Post;
use App\Models\IAM\PostReporting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ReportingService — resolves reporting lines between posts.
 *
 * Resolution Order (for "who does Post A report to for topic X?"):
 *  1. PostReporting — exact param match (e.g. segment=LMM) — highest priority
 *  2. PostReporting — wildcard param (param_value IS NULL)
 *  3. PostReporting — no param at all (applies to all)
 *  4. DesigDeptTree.reports_to_code fallback (structural default)
 *
 * All queries Pure Eloquent via named scopes.
 */
class ReportingService
{
    // ── Who Does A Post Report To? ────────────────────────────────────

    /**
     * Returns the Post that `fromPostCode` reports to for given topic on date.
     * Optional paramType/paramValue for topic-specific overrides (e.g. segment, vehicle_model).
     */
    public function getReportingPost(
        string $fromPostCode,
        string $topic,
        ?Carbon $onDate = null,
        ?string $paramType = null,
        ?string $paramValue = null
    ): ?Post {
        $date = $onDate ?? now();

        // Step 1: Exact param match
        if ($paramType && $paramValue) {
            $line = PostReporting::from($fromPostCode)
                                  ->forTopic($topic)
                                  ->onDate($date)
                                  ->withParam($paramType, $paramValue)
                                  ->byPriority()
                                  ->first();
            if ($line) {
                return Post::where('post_code', $line->to_post_code)->first();
            }
        }

        // Step 2: Wildcard param match
        if ($paramType) {
            $line = PostReporting::from($fromPostCode)
                                  ->forTopic($topic)
                                  ->onDate($date)
                                  ->where('param_type', $paramType)
                                  ->whereNull('param_value')
                                  ->byPriority()
                                  ->first();
            if ($line) {
                return Post::where('post_code', $line->to_post_code)->first();
            }
        }

        // Step 3: No param — applies to all
        $line = PostReporting::from($fromPostCode)
                              ->forTopic($topic)
                              ->onDate($date)
                              ->whereNull('param_type')
                              ->byPriority()
                              ->first();
        if ($line) {
            return Post::where('post_code', $line->to_post_code)->first();
        }

        // Step 4: DesigDeptTree structural fallback
        return $this->getTreeFallback($fromPostCode);
    }

    /**
     * Returns all Posts that report TO a given post for a topic.
     */
    public function getDirectReports(
        string $toPostCode,
        string $topic,
        ?Carbon $onDate = null
    ): Collection {
        $date  = $onDate ?? now();
        $codes = PostReporting::to($toPostCode)
                               ->forTopic($topic)
                               ->onDate($date)
                               ->pluck('from_post_code')
                               ->unique();

        return Post::whereIn('post_code', $codes)->active()->get();
    }

    // ── Employee-level Convenience ────────────────────────────────────

    /**
     * Who does this employee report to for a given topic?
     * Resolves via current primary post.
     */
    public function getEmployeeReportingPost(
        string $empCode,
        string $topic,
        ?string $paramType = null,
        ?string $paramValue = null
    ): ?Post {
        $assignment = EmpPostAssignment::forEmployee($empCode)->primary()->current()->first();
        if (!$assignment) return null;

        return $this->getReportingPost($assignment->post_code, $topic, null, $paramType, $paramValue);
    }

    /**
     * Get the reporting chain (full upward hierarchy) for a post.
     * Returns ordered Collection from immediate manager up to root.
     */
    public function getReportingChain(
        string $fromPostCode,
        string $topic,
        int $maxDepth = 10
    ): Collection {
        $chain   = collect();
        $current = $fromPostCode;
        $depth   = 0;

        while ($depth < $maxDepth) {
            $manager = $this->getReportingPost($current, $topic);
            if (!$manager || $manager->post_code === $current) break;

            $chain->push($manager);
            $current = $manager->post_code;
            $depth++;
        }

        return $chain;
    }

    // ── Manage Reporting Lines ────────────────────────────────────────

    public function setReportingLine(array $data): PostReporting
    {
        return DB::transaction(function () use ($data) {
            // Close any existing line for same from/topic/param combination
            PostReporting::from($data['from_post_code'])
                          ->forTopic($data['topic'])
                          ->current()
                          ->when(
                              isset($data['param_type']),
                              fn($q) => $q->where('param_type', $data['param_type'])
                                          ->where('param_value', $data['param_value'] ?? null)
                          )
                          ->update([
                              'to_date'    => Carbon::parse($data['from_date'])->subDay()->toDateString(),
                              'updated_by' => auth()->id(),
                          ]);

            return PostReporting::create([
                'from_post_code' => $data['from_post_code'],
                'to_post_code'   => $data['to_post_code'],
                'topic'          => $data['topic'],
                'param_type'     => $data['param_type']  ?? null,
                'param_value'    => $data['param_value'] ?? null,
                'from_date'      => Carbon::parse($data['from_date'])->toDateString(),
                'to_date'        => null,
                'priority'       => $data['priority'] ?? 1,
                'notes'          => $data['notes']    ?? null,
                'created_by'     => auth()->id(),
            ]);
        });
    }

    public function closeReportingLine(
        string $fromPostCode,
        string $topic,
        Carbon|string $closeDate
    ): int {
        return PostReporting::from($fromPostCode)
                             ->forTopic($topic)
                             ->current()
                             ->update([
                                 'to_date'    => Carbon::parse($closeDate)->toDateString(),
                                 'updated_by' => auth()->id(),
                             ]);
    }

    // ── Private Helpers ───────────────────────────────────────────────

    private function getTreeFallback(string $fromPostCode): ?Post
    {
        $post = Post::with('desigDeptTree.reportsTo.posts')
                    ->where('post_code', $fromPostCode)
                    ->first();

        if (!$post?->desigDeptTree?->reportsTo) return null;

        // Get the first active post in the reporting-to tree node at same org scope
        return Post::where('tree_code', $post->desigDeptTree->reportsTo->tree_code)
                   ->where('branch_code', $post->branch_code)
                   ->active()
                   ->first();
    }
}