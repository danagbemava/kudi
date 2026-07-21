<?php

namespace App\Services;

use App\Enums\TransactionStatus;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Lottery;

class PaymentService
{
    /**
     * Record a simulated customer payment as a pending transaction.
     * The wallet is only credited when the payment is processed.
     */
    public function create(Merchant $merchant, int $amount, string $reference): Transaction
    {
        $fee = Money::fee($amount);

        return $merchant->transactions()->create([
            'reference' => $reference,
            'gross_amount' => $amount,
            'fee_amount' => $fee,
            'net_amount' => $amount - $fee,
            'status' => TransactionStatus::Pending,
        ]);
    }

    /**
     * Resolve a pending transaction, simulating the payment gateway callback.
     * On success the merchant wallet is credited with the net amount.
     */
    public function process(Transaction $transaction): Transaction
    {
        [$chances, $outOf] = config('kudi.gateway_success_odds', [9, 10]);

        $succeeded = Lottery::odds($chances, $outOf)->choose();

        return DB::transaction(function () use ($transaction, $succeeded) {
            if ($succeeded) {
                $wallet = $transaction->merchant->wallet()->lockForUpdate()->first();
                $wallet->increment('balance', $transaction->net_amount);
                $transaction->update(['status' => TransactionStatus::Successful]);
            } else {
                $transaction->update(['status' => TransactionStatus::Failed]);
            }

            return $transaction->refresh();
        });
    }
}
