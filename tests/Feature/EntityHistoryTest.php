<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Vehicle\Segment;
use App\Models\User;
use App\Models\Utilities\CommHistory\CommMaster;
use App\Models\Utilities\CommHistory\CommThread;
use App\Services\Utils\EntityHistoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\Test;

class EntityHistoryTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedEntityActions();
        Gate::define('view-entity-history', fn() => true);

        // Authenticate a user so actor_id is always available
        $user = User::first();
        if ($user) {
            $this->actingAs($user);
        }
    }

    private function seedEntityActions(): void
    {
        // same as before...
        $master = \App\Models\Utilities\KeyValue\KeywordMaster::firstOrCreate(
            ['code' => 'ENTITY_ACTIONS'],
            ['keyword' => 'Entity Actions', 'name' => 'Entity Actions', 'is_active' => true]
        );

        $actions = ['COMMENTED', 'QUERIED', 'REPLIED', 'STATUS_CHANGED', 'APPROVED', 'MESSAGED', 'ATTACHED'];

        foreach ($actions as $code) {
            \App\Models\Utilities\KeyValue\Keyvalue::firstOrCreate([
                'keyword_code' => 'ENTITY_ACTIONS',
                'code'         => $code,
            ], ['value' => ucfirst(strtolower(str_replace('_', ' ', $code))), 'is_active' => true]);
        }
    }

    #[Test]
    public function it_creates_comm_master_and_adds_root_thread()
    {
        $segment = Segment::first();
        if (!$segment) $this->markTestSkipped('No Segment found');

        $service = app(EntityHistoryService::class);
        $master = $service->createMaster($segment);

        $thread = $service->addThread($master, 'commented', 'Initial comment', 'Test body');

        $this->assertInstanceOf(CommThread::class, $thread);
        $this->assertNotNull($thread->actor_id);
    }

    #[Test]
    public function it_supports_nested_replies()
    {
        $segment = Segment::first();
        if (!$segment) $this->markTestSkipped('No Segment found');

        $service = app(EntityHistoryService::class);
        $master = $service->createMaster($segment);

        $root = $service->addThread($master, 'commented', 'Root');
        $reply = $service->addThread($master, 'replied', 'Reply', null, [], $root);

        $this->assertEquals($root->id, $reply->parent_id);
    }

    #[Test]
    public function it_allows_media_attachments_on_threads()
    {
        $segment = Segment::first();
        if (!$segment) $this->markTestSkipped('No Segment found');

        $service = app(EntityHistoryService::class);
        $master = $service->createMaster($segment);

        $thread = $service->addThread($master, 'attached', 'Doc attached', null, [
            'media' => [base_path('storage/test.pdf')]
        ]);

        $this->assertGreaterThan(0, $thread->getMedia('attachments')->count());
    }

    #[Test]
    public function trait_works_on_any_model()
    {
        $segment = Segment::first();
        if (!$segment) $this->markTestSkipped('No Segment found');

        $thread = $segment->addHistory('status_changed', 'Activated', 'Now live');

        $this->assertInstanceOf(CommThread::class, $thread);
    }

    #[Test]
    public function it_returns_full_history_with_nested_threads()
    {
        $segment = Segment::first();
        if (!$segment) $this->markTestSkipped('No Segment found');

        $service = app(EntityHistoryService::class);
        $history = $service->getFullHistory($segment);

        $this->assertNotNull($history);
    }
}