<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpiredTimeConsultation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consultation:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Command consultation:expire dipanggil');
        $expiredPayments = \App\Models\Payment::where('payment_status', 'Pending')
            ->where('expired_date', '<', now())
            ->get();

        Log::info('Expired payments found: ' . $expiredPayments->count());

        if ($expiredPayments->isEmpty()) {
            Log::info('No expired payments found at this moment.');
        }

        foreach ($expiredPayments as $payment) {
            $payment->update(['payment_status' => 'Failed']);
            if ($payment->consultation) {
                $payment->consultation->update(['status' => 'Cancelled']);
                if ($payment->consultation->schedule) {
                    $payment->consultation->schedule->update(['status' => 'Available']);
                }
            }
        }
      
        if ($this->hasOutput()) {
            $this->info("Expired consultations processed successfully.");
        }
        try {
            // proses utama
            Log::info('Consultation expire job run successfully.');
        } catch (\Throwable $e) {
            Log::error('Consultation expire failed: ' . $e->getMessage());
        }
    }
}
