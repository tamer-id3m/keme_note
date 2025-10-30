<?php

namespace App\Jobs;

use App\Enums\QueueStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use App\Models\v3\AiDiagnosisHistory;
use App\Models\v3\Context;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\PatientSubmission\Contracts\AIResponseGeneratorInterface;
use App\Traits\QueueTrait;

class ProcessOnDemandSmartNote  implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, QueueTrait;

    protected $onDemandSmartNote;
    protected $user;

    /**
     * Create a new job instance.
     */
    public function __construct($onDemandSmartNote, $user = null)
    {

        $this->onDemandSmartNote = $onDemandSmartNote;
        $this->user = $user;
        $this->queue = 'onDemand_SmartNote';
        $this->setQueueModel("OnDemandSmartNote");
    }

    /**
     * Execute the job.
     */
    public function handle(AIResponseGeneratorInterface $responseGenerator)
    {
        try {
            $provider_category_id=$this->onDemandSmartNote->doctor->provider_category_id ?? null;
            $context=Context::where('is_active',true)->where('type',"on_demand")->where('provider_category_id', $provider_category_id)->first();
            $this->updateQueueStatus($this->user, $this->onDemandSmartNote->id, QueueStatus::IN_PROGRESS);
          
            Log::info("Starting ProcessOnDemandSmartNote job for note ID: {$this->onDemandSmartNote->id}");
            $translatedTextIntoEnglish = $this->translate($this->onDemandSmartNote->note);
            $response = $responseGenerator->generateResponse($context->id, $this->onDemandSmartNote->note, $context->keme_direct, $context->aienv_id);

            if ($response) {
                if ($this->user) {
                    AiDiagnosisHistory::create([
                        'on_demand_smart_note_id' => $this->onDemandSmartNote->id,
                        'edited_by' =>  $this->user,
                        'ai_diagnosis' => $this->onDemandSmartNote->ai_diagnosis ?? $response,
                        'context2_id' => $this->onDemandSmartNote->context2_id ?? null,
                        'ai_first_result' => $this->onDemandSmartNote->ai_first_result ?? null,
                        'updated_at' => now(),
                    ]);
                }

                $this->onDemandSmartNote->ai_diagnosis = $response;
                $this->onDemandSmartNote->save();
                $this->updateQueueStatus($this->user, $this->onDemandSmartNote->id, QueueStatus::DONE);
            }
        } catch (\Exception $e) {
            $this->updateQueueStatus($this->user, $this->onDemandSmartNote->id, QueueStatus::FAILED);
            Log::error("Error processing OnDemandSmartNote job for note ID: {$this->onDemandSmartNote->id}. Error: {$e->getMessage()}");
        }
    }
    private function translate($text, $target_lang = 'en')
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://translate.googleapis.com/translate_a/single?client=gtx&dt=t',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => 'sl=auto' . '&tl=' . $target_lang . '&q=' . urlencode($text),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        $sentencesArray = json_decode($response, true);
        $sentences = "";
        foreach ($sentencesArray[0] as $s) {
            $sentences .= isset($s[0]) ? $s[0] : '';
        }

        return $sentences;
    }
}