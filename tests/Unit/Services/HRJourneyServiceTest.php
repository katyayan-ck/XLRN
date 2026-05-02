<?php

namespace Tests\Unit\Services;

use App\Exceptions\ApplicationException;
use App\Models\Admin\EmpPostAssignment;
use App\Models\IAM\Post;
use App\Services\HR\HRJourneyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HRJourneyServiceTest extends TestCase
{
    use RefreshDatabase;

    private HRJourneyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(HRJourneyService::class);
    }

    public function test_onboard_creates_primary_assignment(): void
    {
        $post = Post::factory()->create(['max_occupants' => 1]);

        $assignment = $this->service->onboard('BMPL-0001', $post->post_code, '2024-01-01');

        $this->assertEquals('BMPL-0001', $assignment->emp_code);
        $this->assertEquals('primary', $assignment->assignment_type);
        $this->assertEquals('onboarding', $assignment->relieving_type);
        $this->assertNull($assignment->to_date);
    }

    public function test_onboard_fails_if_post_fully_occupied(): void
    {
        $post = Post::factory()->create(['max_occupants' => 1]);
        EmpPostAssignment::factory()->create(['post_code' => $post->post_code, 'to_date' => null]);

        $this->expectException(ApplicationException::class);
        $this->service->onboard('BMPL-0002', $post->post_code, '2024-01-01');
    }

    public function test_onboard_fails_if_emp_already_has_primary(): void
    {
        $post1 = Post::factory()->create(['max_occupants' => 2]);
        $post2 = Post::factory()->create(['max_occupants' => 2]);

        $this->service->onboard('BMPL-0001', $post1->post_code, '2024-01-01');

        $this->expectException(ApplicationException::class);
        $this->service->onboard('BMPL-0001', $post2->post_code, '2024-06-01');
    }

    public function test_transfer_relieves_old_and_assigns_new(): void
    {
        $post1 = Post::factory()->create(['max_occupants' => 2]);
        $post2 = Post::factory()->create(['max_occupants' => 2]);

        $this->service->onboard('BMPL-0001', $post1->post_code, '2024-01-01');
        $result = $this->service->transfer('BMPL-0001', $post2->post_code, '2024-06-01');

        $this->assertNotNull($result['relieved']->to_date);
        $this->assertEquals('transfer', $result['relieved']->relieving_type);
        $this->assertNull($result['assigned']->to_date);
        $this->assertEquals($post2->post_code, $result['assigned']->post_code);
    }

    public function test_separate_closes_all_active_assignments(): void
    {
        $post1 = Post::factory()->create(['max_occupants' => 2]);
        $post2 = Post::factory()->create(['max_occupants' => 2]);

        EmpPostAssignment::factory()->create([
            'emp_code'        => 'BMPL-0001',
            'post_code'       => $post1->post_code,
            'assignment_type' => 'primary',
            'to_date'         => null,
        ]);
        EmpPostAssignment::factory()->create([
            'emp_code'        => 'BMPL-0001',
            'post_code'       => $post2->post_code,
            'assignment_type' => 'additional',
            'to_date'         => null,
        ]);

        $relieved = $this->service->separate('BMPL-0001', '2024-12-31', 'relieving');

        $this->assertCount(2, $relieved);
        $relieved->each(fn($a) => $this->assertEquals('2024-12-31', $a->to_date));
    }

    public function test_get_journey_returns_chronological(): void
    {
        $post = Post::factory()->create(['max_occupants' => 5]);

        EmpPostAssignment::factory()->create([
            'emp_code'  => 'BMPL-0001',
            'post_code' => $post->post_code,
            'from_date' => '2023-06-01',
            'to_date'   => '2024-01-01',
        ]);
        EmpPostAssignment::factory()->create([
            'emp_code'  => 'BMPL-0001',
            'post_code' => $post->post_code,
            'from_date' => '2022-01-01',
            'to_date'   => '2023-05-31',
        ]);

        $journey = $this->service->getJourney('BMPL-0001');
        $this->assertEquals('2022-01-01', $journey->first()->from_date->toDateString());
    }
}