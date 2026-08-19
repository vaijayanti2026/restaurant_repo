<?php

namespace App\Http\Controllers;

use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Twilio\TwiML\VoiceResponse;

class IVRController extends Controller
{
    protected $transferNumber = '+15805636309';

    public function dialCallB(Request $request)
    {
        $branchId = $request->input('branch_id');
        $branch = DB::table('branches')->where('id', $branchId)->first();

        if (!$branch || !$branch->phone) {
            return response()->json(['error' => 'Invalid branch ID'], 400);
        }

        return $this->startOrderCall($this->transferNumber);
    }

    public function dialCall(Request $request)
    {
        $branchId = $request->input('branch_id');
        $branch = DB::table('branches')->where('id', $branchId)->first();

        if (!$branch || !$branch->phone) {
            return response()->json(['error' => 'Invalid branch ID'], 400);
        }

        return $this->startOrderCall($branch->phone);
    }

    protected function startOrderCall($phoneNumber)
    {
        try {
            $call = app(TwilioService::class)->makeCall($phoneNumber, [
                'url' => route('twilio.order-call-twiml'),
                'method' => 'GET',
                'statusCallback' => route('twilio.call.status'),
                'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                'statusCallbackMethod' => 'POST',
            ]);

            Log::info('Twilio call initiated successfully.', [
                'sid' => $call->sid ?? null,
                'to' => $phoneNumber,
            ]);

            return response()->json(['status' => 'OK', 'sid' => $call->sid ?? null], 200);
        } catch (\Exception $exception) {
            Log::error('Twilio call initiation failed: ' . $exception->getMessage());
            return response()->json(['error' => 'Unable to initiate call'], 500);
        }
    }

    public function orderCallTwiml()
    {
        $response = new VoiceResponse();
        $response->play(url('/playOrder'));

        return $this->twiml($response);
    }

    public function playJenksIntro()
    {
        return $this->audioResponse(public_path('assets/audios/jenks/audio1.mp3'));
    }

    public function playJenksAudio2()
    {
        return $this->audioResponse(public_path('assets/audios/jenks/audio2.mp3'));
    }

    public function playJenksAudio3()
    {
        return $this->audioResponse(public_path('assets/audios/jenks/audio3.mp3'));
    }

    public function playJenksCallWait()
    {
        return $this->audioResponse(public_path('assets/audios/jenks/audio5.mp3'));
    }

    public function playOrder()
    {
        return $this->audioResponse(public_path('assets/audios/order.mp3'));
    }

    protected function audioResponse($filePath)
    {
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'File not found.'], 404);
        }

        return response(file_get_contents($filePath), 200)
            ->header('Content-Type', mime_content_type($filePath));
    }

    protected function twiml(VoiceResponse $response)
    {
        return response((string) $response, 200)->header('Content-Type', 'text/xml');
    }
}
