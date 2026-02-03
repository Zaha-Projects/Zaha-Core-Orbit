<?php

namespace App\Http\Controllers\Roles\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    private const PAYABLE_TYPES = [
        \App\Models\DonationCash::class,
        \App\Models\Booking::class,
        \App\Models\ZahaTimeBooking::class,
    ];

    public function index()
    {
        $payments = Payment::orderByDesc('paid_at')->get();

        return view('roles.finance.payments.index', compact('payments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'payable_type' => ['required', 'string', 'in:' . implode(',', self::PAYABLE_TYPES)],
            'payable_id' => ['required', 'integer'],
            'method' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
        ]);

        Payment::create([
            'payable_type' => $data['payable_type'],
            'payable_id' => $data['payable_id'],
            'method' => $data['method'],
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => $data['paid_at'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->back()
            ->with('status', __('app.roles.finance.payments.created'));
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'method' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'paid_at' => ['required', 'date'],
        ]);

        $payment->update([
            'method' => $data['method'],
            'amount' => $data['amount'],
            'reference' => $data['reference'] ?? null,
            'paid_at' => $data['paid_at'],
        ]);

        return redirect()
            ->back()
            ->with('status', __('app.roles.finance.payments.updated'));
    }
}
