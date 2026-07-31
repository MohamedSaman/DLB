<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class RecalculateCustomerDues extends Command
{
    protected $signature = 'customer:recalculate-dues';
    protected $description = 'Recalculate customer due amounts and payment allocations chronologically';

    public function handle()
    {
        $this->info('Starting customer dues recalculation...');

        $customers = Customer::all();

        foreach ($customers as $customer) {
            $this->info("Processing Customer: {$customer->name} (ID: {$customer->id})");

            DB::beginTransaction();
            try {
                // Delete existing payment allocations for this customer's payments
                $paymentIds = Payment::where('customer_id', $customer->id)->pluck('id');
                if ($paymentIds->isNotEmpty()) {
                    DB::table('payment_allocations')->whereIn('payment_id', $paymentIds)->delete();
                }

                $openingBalance = floatval($customer->opening_balance ?? 0);
                $openingBalancePaid = 0;
                
                // Get all sales for this customer, ordered chronologically
                $sales = Sale::where('customer_id', $customer->id)
                    ->with('returns')
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                $salesData = [];
                foreach ($sales as $sale) {
                    $isReturned = $sale->delivery_status === 'cancelled'
                        || str_contains(strtolower((string) ($sale->notes ?? '')), 'invoice fully returned by delivery man')
                        || str_contains(strtolower((string) ($sale->notes ?? '')), 'returned by delivery man');

                    if ($isReturned) {
                        $netAmount = 0;
                    } else {
                        $netAmount = max(0, floatval($sale->total_amount));
                    }
                    
                    $salesData[$sale->id] = [
                        'sale' => $sale,
                        'net_amount' => $netAmount,
                        'paid_amount' => 0,
                        'due_amount' => $netAmount,
                        'payment_status' => $netAmount > 0 ? 'pending' : 'paid'
                    ];
                }

                // Get all completed payments for this customer, ordered chronologically
                $payments = Payment::where('customer_id', $customer->id)
                    ->whereIn('status', ['paid', 'approved', 'completed'])
                    ->orderBy('payment_date', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();

                $totalOverpaid = 0;

                foreach ($payments as $payment) {
                    $remainingPayment = floatval($payment->amount);
                    
                    // 1. Allocate to opening balance first
                    if ($remainingPayment > 0 && $openingBalancePaid < $openingBalance) {
                        $obDue = $openingBalance - $openingBalancePaid;
                        if ($remainingPayment >= $obDue) {
                            $openingBalancePaid = $openingBalance;
                            $remainingPayment -= $obDue;
                            
                            DB::table('payment_allocations')->insert([
                                'payment_id' => $payment->id,
                                'sale_id' => null,
                                'allocated_amount' => $obDue,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $openingBalancePaid += $remainingPayment;
                            DB::table('payment_allocations')->insert([
                                'payment_id' => $payment->id,
                                'sale_id' => null,
                                'allocated_amount' => $remainingPayment,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $remainingPayment = 0;
                        }
                    }

                    // 2. Allocate to sales chronologically
                    foreach ($salesData as $saleId => &$data) {
                        if ($remainingPayment <= 0) break;
                        
                        if ($data['due_amount'] > 0) {
                            if ($remainingPayment >= $data['due_amount']) {
                                $allocationAmount = $data['due_amount'];
                                $data['paid_amount'] += $allocationAmount;
                                $data['due_amount'] = 0;
                                $data['payment_status'] = 'paid';
                                $remainingPayment -= $allocationAmount;
                                
                                DB::table('payment_allocations')->insert([
                                    'payment_id' => $payment->id,
                                    'sale_id' => $saleId,
                                    'allocated_amount' => $allocationAmount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            } else {
                                $allocationAmount = $remainingPayment;
                                $data['paid_amount'] += $allocationAmount;
                                $data['due_amount'] -= $allocationAmount;
                                $data['payment_status'] = 'partial';
                                $remainingPayment = 0;
                                
                                DB::table('payment_allocations')->insert([
                                    'payment_id' => $payment->id,
                                    'sale_id' => $saleId,
                                    'allocated_amount' => $allocationAmount,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                    unset($data);

                    // 3. Keep track of overpaid amount
                    if ($remainingPayment > 0) {
                        $totalOverpaid += $remainingPayment;
                    }
                }

                // Save updated sale amounts
                $totalSalesDue = 0;
                foreach ($salesData as $saleId => $data) {
                    $sale = $data['sale'];
                    $dueAmount = round($data['due_amount'], 2);
                    $sale->due_amount = max(0, $dueAmount);
                    $sale->payment_status = $data['payment_status'];
                    $sale->save();
                    
                    $totalSalesDue += $sale->due_amount;
                }

                // Calculate returned cheques amount
                $returnedChequesAmount = \App\Models\Cheque::where('customer_id', $customer->id)
                    ->where('status', 'return')
                    ->sum('cheque_amount');

                // Update customer record
                $customer->opening_balance_paid = round($openingBalancePaid, 2);
                $customer->due_amount = round($totalSalesDue, 2);
                $customer->overpaid_amount = round($totalOverpaid, 2);
                
                $totalDue = ($openingBalance - $openingBalancePaid) + $totalSalesDue + $returnedChequesAmount - $totalOverpaid;
                $customer->total_due = round($totalDue, 2);
                
                $customer->save();

                DB::commit();
                $this->info("  -> OB Paid: {$openingBalancePaid}/{$openingBalance} | Sales Due: {$totalSalesDue} | Overpaid: {$totalOverpaid} | Total Due: {$totalDue}");
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("  -> Failed: " . $e->getMessage());
            }
        }

        $this->info('Done recalculating customer dues!');
        return 0;
    }
}
