<?php

namespace App\Traits;

use App\Events\NoteReady;
use App\Models\QueueList;

trait QueueTrait
{
    private $model;
    public function setQueueModel($model)
    {
        $this->model = $model;
    }

    public function createQueue($user_id, $note_id)
    {
        $queue = new QueueList();
        $queue->note_id = $note_id;
        $queue->user_id = $user_id;
        $queue->model_name = $this->model;
        $queue->type = QueueStatus::QUEUED->value;
        $queue->save();
    }

    public function updateQueueStatus($user_id, $note_id, $status)
    {
        $queue = QueueList::where('note_id', $note_id)
            ->where('model_name', $this->model)
            ->where('user_id', $user_id)
            ->orderBy('created_at', 'desc')
            ->first();
        $queue->type = $status->value;
        $queue->save();
        broadcast(new NoteReady());
    }
    
}
