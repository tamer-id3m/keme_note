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

    public function bulkQueueListTypes(Request $request)
    {
        $noteIds = $request->input('nid', []);
        $model   = $request->input('model', 'fake');

        
        $bulk = QueueList::whereIn('note_id', $noteIds)
            ->where('model_name', $model)
            ->latest()
            ->groupBy('note_id')
            ->get()
            ->toArray();
        
        return response()->json(['data' => $bulk]);
        
    }
    public function queueList(Request $request)
    {
        $noteId = $request->input('nid', 0);
        $modelName = $request->input('model', 'fake');
    
        $queue =QueueList::where(
            [
                'note_id' => $noteId,
                'model_name' => $modelName
            ])
            ->latest()
            ->first();
    
        return response()->json(['data' => $queue]);
    }

    public function queueSyncDelete($id , $model)
    {
        $deleted = QueueList::where('note_id', $id)->where('model_name' , $model)->delete();

        return response()->json(['data' => ['delete' =>   (bool) $deleted ]]);
    }

    public function queueSync(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'note_id'    => 'required|integer',
            'user_id'    => 'required|integer',
            'model_name' => 'required|string',
            'type'       => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 'note-error',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }
    
        $payload = [
            'note_id'    => $request->note_id,
            'user_id'    => $request->user_id,
            'model_name' => $request->model_name,
            'type'       => $request->type,
            'updated_at' => now(),
        ];
    
        $queue_list =  QueueList::updateOrCreate(
            [
                'note_id'    => $request->note_id,
                'user_id'    => $request->user_id,
                'model_name' => $request->model_name,
            ],
            $payload
        );
    

        return response()->json(['data' => $queue_list]);
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
