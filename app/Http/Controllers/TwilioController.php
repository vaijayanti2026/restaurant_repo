<?php

namespace App\Http\Controllers;

use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Twilio\TwiML\VoiceResponse;

class TwilioController extends Controller
{
    protected $transferNumber = '+15805636309';

    public function initiateCall(Request $request)
    {
        $branchId = $request->input('branch_id', 1);
        $branch = DB::table('branches')->where('id', $branchId)->first();
        $toNumber = $request->input('to', $branch->phone ?? null);

        if (!$toNumber) {
            return response()->json(['error' => 'Phone number not found.'], 400);
        }

        try {
            $call = app(TwilioService::class)->makeCall($toNumber, [
                'url' => route('twilio.order-call-twiml'),
                'method' => 'GET',
                'statusCallback' => route('twilio.call.status'),
                'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                'statusCallbackMethod' => 'POST',
            ]);

            return response()->json([
                'message' => 'Call initiated.',
                'sid' => $call->sid ?? null,
            ]);
        } catch (\Exception $exception) {
            Log::error('Twilio manual call failed: ' . $exception->getMessage());
            return response()->json(['error' => 'Error making the API request.'], 500);
        }
    }

    public function handleAnswer(Request $request)
    {
        $branch = $request->input('branch');
        $soundUrl = $this->getSoundUrlForBranch($branch);

        $response = new VoiceResponse();
        $response->play($soundUrl);

        return $this->twiml($response);
    }

    public function getSoundUrlForBranch($id)
    {
        return asset('public/assets/audios/brokenarrow/audio1.mp3');
    }

    public function handleHangup()
    {
        $response = new VoiceResponse();
        $response->hangup();

        return $this->twiml($response);
    }

    public function outboundWebhook(Request $request)
    {
        Log::info('Twilio outbound webhook.', $request->all());
        return response()->json(['status' => 'OK']);
    }

    public function handleCallStatus(Request $request)
    {
        Log::info('Twilio call status webhook.', $request->all());
        return response()->json(['status' => 'OK']);
    }

    public function handleJenksIncomingCall(Request $request)
    {
        Log::info('Twilio Jenks incoming call.', $request->all());

        $response = new VoiceResponse();
        $gather = $response->gather([
            'action' => route('twilio.jenks.gather'),
            'method' => 'POST',
            'numDigits' => 1,
        ]);
        $gather->play(url('/playJenksIntro'));
        $response->hangup();

        return $this->twiml($response);
    }

    public function handleJenksDtmfInput(Request $request)
    {
        $digits = str_replace('#', '', (string) $request->input('Digits'));
        $response = new VoiceResponse();

        if ($digits === '1') {
            $response->play(url('/playJenksCallWait'));
            $response->dial($this->transferNumber);
        } elseif ($digits === '2') {
            $response->play(url('/playJenksAudio2'));
        } elseif ($digits === '3') {
            $response->play(url('/playJenksAudio3'));
        } else {
            $response->play(asset('public/assets/audios/callaudios/audio2.mp3'));
            $response->hangup();
        }

        return $this->twiml($response);
    }

    public function handelIncommingCall(Request $request)
    {
        Log::info('Twilio incoming branch call.', $request->all());

        $to = $request->input('To');
        $branch = $this->getBranch($to);
        $response = new VoiceResponse();

        if (!$branch) {
            $response->say('Location not found.');
            $response->hangup();
            return $this->twiml($response);
        }

        $slug = Str::slug($branch->name);
        $audioUrl = $this->getBranchAudio($slug, $to, 'audio1.mp3');
        $audioUrlNotResponse = asset('public/assets/audios/callaudios/audio3.mp3');
        $listenAgain = asset('public/assets/audios/callaudios/audio1.mp3');

        $gather = $response->gather([
            'action' => route('gather.action', ['type' => 'main_menu']),
            'method' => 'POST',
            'numDigits' => 1,
        ]);
        $gather->play($audioUrl);

        $response->play($audioUrlNotResponse);

        $repeatGather = $response->gather([
            'action' => route('gather.action', ['type' => 'main_menu']),
            'method' => 'POST',
            'numDigits' => 1,
        ]);
        $repeatGather->play($listenAgain);
        $response->hangup();

        return $this->twiml($response);
    }

    public function handleDtmfInput(Request $request)
    {
        $dtmfDigits = str_replace('#', '', (string) $request->input('Digits'));
        $branch = $this->getBranch($request->input('To'));
        $response = new VoiceResponse();

        if (!$branch) {
            $response->say('Location not found.');
            $response->hangup();
            return $this->twiml($response);
        }

        $slug = Str::slug($branch->name);

        if ($request->input('type') === 'main_menu' && $dtmfDigits === '0') {
            $response->play(asset('public/assets/audios/callaudios/audio2.mp3'));
            $gather = $response->gather([
                'action' => route('gather.action', ['type' => 'sub_menu']),
                'method' => 'POST',
                'numDigits' => 1,
            ]);
            $gather->play(asset('public/assets/audios/callaudios/audio1.mp3'));
            $response->play(asset('public/assets/audios/callaudios/audio3.mp3'));
            $response->hangup();

            return $this->twiml($response);
        }

        switch ($dtmfDigits) {
            case '1':
                $response->play($this->getBranchAudio($slug, $request->input('To'), 'audio5.mp3', $dtmfDigits));
                if ($branch->phone) {
                    $response->dial($branch->phone);
                } else {
                    $response->say('Branch phone number not found.');
                    $response->hangup();
                }
                break;

            case '2':
                $this->playSubMenu(
                    $response,
                    $this->getBranchAudio($slug, $request->input('To'), 'audio2.mp3', $dtmfDigits)
                );
                break;

            case '3':
                $this->playSubMenu(
                    $response,
                    $this->getBranchAudio($slug, $request->input('To'), 'audio3.mp3', $dtmfDigits)
                );
                break;

            case '4':
            case '0':
                $gather = $response->gather([
                    'action' => route('gather.action', ['type' => 'sub_menu']),
                    'method' => 'POST',
                    'numDigits' => 1,
                ]);
                $gather->play($this->getBranchAudio($slug, $request->input('To'), 'audio1.mp3', $dtmfDigits));
                $response->play(asset('public/assets/audios/callaudios/audio3.mp3'));
                if ($dtmfDigits === '0') {
                    $response->hangup();
                }
                break;

            default:
                $response->play(asset('public/assets/audios/callaudios/audio2.mp3'));
                $response->hangup();
                break;
        }

        return $this->twiml($response);
    }

    protected function playSubMenu(VoiceResponse $response, $audioUrl)
    {
        $response->play($audioUrl);

        $gather = $response->gather([
            'action' => route('gather.action', ['type' => 'sub_menu']),
            'method' => 'POST',
            'numDigits' => 1,
        ]);
        $gather->play(asset('public/assets/audios/callaudios/audio6.mp3'));

        $response->play(asset('public/assets/audios/callaudios/audio3.mp3'));
        $response->hangup();
    }

    protected function getBranchAudio($slug, $number, $audio, $digit = null)
    {
        return asset('public/assets/audios/' . $slug . '/' . $audio);
    }

    protected function getBranch($tollFreeNumber)
    {
        return DB::table('branches')->where('tool_free_number', $tollFreeNumber)->first();
    }

    protected function twiml(VoiceResponse $response)
    {
        return response((string) $response, 200)->header('Content-Type', 'text/xml');
    }
}
