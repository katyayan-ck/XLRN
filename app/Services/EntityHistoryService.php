<?php

namespace App\Services;

use App\Models\Core\CommMaster;
use App\Models\Core\CommThread;
use App\Models\Core\Keyvalue;
use App\Services\KeywordValueService;
use App\Services\NotificationService;
use App\Models\Core\Chat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class EntityHistoryService
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Create CommMaster for entity or standalone
     *
     * @param mixed $entity
     * @param string $title
     * @param string|null $description
     * @param string $statusKey
     * @param mixed $actor
     * @return CommMaster
     */
    public function createMaster($entity, string $title, ?string $description = null, string $statusKey = 'submitted', $actor = null): CommMaster
    {
        $actor = $actor ?? Auth::user();

        $statusId = KeywordValueService::getValueId('entity_statuses', $statusKey);

        $master = CommMaster::create([
            'title' => $title,
            'description' => $description,
            'status_id' => $statusId,
            'actor_id' => $actor->id,
        ]);

        if ($entity) {
            $master->entityable_type = get_class($entity);
            $master->entityable_id = $entity->id;
            $master->save();
        }

        return $master;
    }

    /**
     * Create CommMaster for chat (group or one-to-one)
     *
     * @param string $type 'group' or 'one_to_one'
     * @param array $participants User IDs
     * @param string $title
     * @param string|null $description
     * @return CommMaster
     */
    public function createChatMaster(string $type, array $participants, string $title, ?string $description = null): CommMaster
    {
        $chat = Chat::create([
            'type' => $type,
            'participants' => $participants,
        ]);

        return $this->createMaster($chat, $title, $description, 'active');
    }

    /**
     * Add thread/comment to CommMaster
     *
     * @param CommMaster $commMaster
     * @param string $actionKey
     * @param string|null $title
     * @param string|null $message
     * @param array $attachments
     * @param CommThread|null $parentThread
     * @param mixed $actor
     * @return CommThread
     */
    public function addThread(CommMaster $commMaster, string $actionKey, ?string $title = null, ?string $message = null, array $attachments = [], ?CommThread $parentThread = null, $actor = null): CommThread
    {
        $actor = $actor ?? Auth::user();

        $actionId = KeywordValueService::getValueId('entity_actions', $actionKey);

        $thread = CommThread::create([
            'comm_master_id' => $commMaster->id,
            'parent_id' => $parentThread ? $parentThread->id : null,
            'actor_id' => $actor->id,
            'action_id' => $actionId,
            'title' => $title,
            'message_text' => $message,
        ]);

        foreach ($attachments as $attachment) {
            $thread->addMedia($attachment['path'])->toMediaCollection('attachments');
        }

        $this->notifyStakeholders($thread);

        return $thread;
    }

    private function notifyStakeholders(CommThread $thread): void
    {
        $stakeholders = $this->getStakeholders($thread->commMaster->entityable ?? $thread->commMaster);

        foreach ($stakeholders as $user) {
            if ($user->id !== $thread->actor_id) {
                $this->notificationService->sendAndLogNotification(
                    $user,
                    'comment',
                    'New Comment',
                    "New comment on {$thread->commMaster->title}",
                    get_class($thread->commMaster->entityable ?? $thread->commMaster),
                    $thread->commMaster->entityable_id ?? $thread->commMaster->id,
                    ['thread_id' => $thread->id]
                );
            }
        }
    }

    private function getStakeholders($entity): array
    {
        if ($entity instanceof Chat) {
            return User::whereIn('id', $entity->participants)->get()->toArray();
        }

        $stakeholders = [$entity->created_by ? User::find($entity->created_by) : null];

        if (method_exists($entity, 'approvalChain')) {
            $approvers = $entity->approvalChain()->pluck('user_id');
            $stakeholders = array_merge($stakeholders, User::whereIn('id', $approvers)->get()->toArray());
        }

        return array_filter($stakeholders);
    }
}
