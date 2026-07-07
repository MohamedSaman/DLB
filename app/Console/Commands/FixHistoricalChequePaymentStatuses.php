<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FixHistoricalChequePaymentStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:fix-returned-cheques';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates historical payments to status return if their cheque was returned';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding payments associated with returned cheques...');

        $cheques = \App\Models\Cheque::where('status', 'return')->whereNotNull('payment_id')->get();
        $count = 0;

        foreach ($cheques as $cheque) {
            $payment = \App\Models\Payment::find($cheque->payment_id);
            if ($payment && $payment->status !== 'return') {
                $payment->status = 'return';
                $payment->save();
                $count++;
            }
        }

        $this->info("Successfully updated {$count} historical payments to 'return' status.");
    }
}
