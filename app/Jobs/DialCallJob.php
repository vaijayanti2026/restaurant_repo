<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\IVRController;

class DialCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $branchId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($branchId)
    {
        $this->branchId = $branchId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $request = new \Illuminate\Http\Request(['branch_id' => $this->branchId]);
        $ivrController = new IVRController();
        $ivrController->dialCall($request);
    }
}
