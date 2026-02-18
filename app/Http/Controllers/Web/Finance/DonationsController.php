<?php

namespace App\Http\Controllers\Web\Finance;

use App\Http\Controllers\Controller;
use App\Models\DonationCash;
use App\Models\MonthlyActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DonationsController extends Controller
{
    public function index()
    {
        $donations = DonationCash::with(['monthlyActivity', 'creator'])->orderByDesc('date')->get();
        $activities = MonthlyActivity::orderBy('month')->orderBy('day')->get();

        return view('pages.finance.donations.index', compact('donations', 'activities'));
    }

    public function create()
    {
        $activities = MonthlyActivity::orderBy('month')->orderBy('day')->get();

        return view('pages.finance.donations.create', compact('activities'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'donor_type' => ['required', 'string', 'max:255'],
            'donor_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:100'],
            'receipt_no' => ['nullable', 'string', 'max:255'],
            'purpose_type' => ['required', 'string', 'max:255'],
            'monthly_activity_id' => ['nullable', 'exists:monthly_activities,id'],
            'finance_status' => ['required', 'string', 'max:50'],
        ]);

        $date = Carbon::parse($data['date']);

        DonationCash::create([
            'donor_type' => $data['donor_type'],
            'donor_name' => $data['donor_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'day' => (int) $date->format('d'),
            'date' => $data['date'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'receipt_no' => $data['receipt_no'] ?? null,
            'purpose_type' => $data['purpose_type'],
            'monthly_activity_id' => $data['monthly_activity_id'] ?? null,
            'finance_status' => $data['finance_status'],
            'created_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.finance.donations.index')
            ->with('status', __('app.roles.finance.donations.created'));
    }

    public function edit(DonationCash $donationCash)
    {
        $activities = MonthlyActivity::orderBy('month')->orderBy('day')->get();

        return view('pages.finance.donations.edit', compact('donationCash', 'activities'));
    }

    public function update(Request $request, DonationCash $donationCash)
    {
        $data = $request->validate([
            'donor_type' => ['required', 'string', 'max:255'],
            'donor_name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'string', 'max:100'],
            'receipt_no' => ['nullable', 'string', 'max:255'],
            'purpose_type' => ['required', 'string', 'max:255'],
            'monthly_activity_id' => ['nullable', 'exists:monthly_activities,id'],
            'finance_status' => ['required', 'string', 'max:50'],
        ]);

        $date = Carbon::parse($data['date']);

        $donationCash->update([
            'donor_type' => $data['donor_type'],
            'donor_name' => $data['donor_name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'day' => (int) $date->format('d'),
            'date' => $data['date'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'receipt_no' => $data['receipt_no'] ?? null,
            'purpose_type' => $data['purpose_type'],
            'monthly_activity_id' => $data['monthly_activity_id'] ?? null,
            'finance_status' => $data['finance_status'],
        ]);

        return redirect()
            ->route('role.finance.donations.index')
            ->with('status', __('app.roles.finance.donations.updated', ['donation' => $donationCash->donor_name]));
    }
}
