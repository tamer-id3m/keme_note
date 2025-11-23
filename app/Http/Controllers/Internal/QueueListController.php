<?php

namespace App\Http\Controllers\Internal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\QueueList;
use App\Traits\ApiResponseTrait;
use App\Traits\QueueStatus;
use App\Traits\QueueTrait;
use Illuminate\Support\Facades\DB;

class QueueListController extends Controller
{
    use ApiResponseTrait, QueueTrait;

    public function queueLists(Request $request)
    {
        $queueLists = QueueList::whereIn('note_id', $request->ids)
            ->where('model_name', $request->model_name)
            ->get();
        return $this->successResponse('Queue lists fetched successfully', $queueLists);
    }
    public function getNotProgressQueueList(Request $request)
    {
        $queueLists = QueueList::whereIn('note_id', $request->ids)
            ->whereNot('type', QueueStatus::IN_PROGRESS->value)
            ->where('model_name', $request->model_name)
            ->get();
        return $this->successResponse('Not in progress queue lists fetched successfully', $queueLists);
    }

    public function deleteQueueList(Request $request)
    {
        QueueList::where("note_id", $request->note_id)->where("model_name", $request->model_name)->delete();
    }
    public function addQueue(Request $request)
    {
        $this->setQueueModel($request->model_name);
        $doctorId = $request->input('doctor_id');
        $noteId = $request->input('note_id');
        $this->createQueue($doctorId, $noteId);
    }
    public function editQueueStatus(Request $request)
    {
        $this->setQueueModel($request->model_name);
        $user_id = $request->input('user_id');
        $note_id = $request->input('note_id');
        $status = QueueStatus::from($request->input('status'));
        $this->updateQueueStatus($user_id, $note_id, $status);
    }
    public function deleteCreateQueue(Request $request)
    {
        $this->setQueueModel($request->model_name);
        $doctor_id = $request->input('doctor_id');
        $note_id = $request->input('note_id');
        DB::transaction(function () use ($doctor_id, $note_id, $request) {
            $this->deleteQueueList($request);
            $this->createQueue($doctor_id, $note_id);
        });
    }
}
