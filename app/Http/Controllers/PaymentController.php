<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {
            $payments = Payment::with(['patient', 'doctor'])->latest()->paginate(20);
        } else {
            $payments = Payment::where('doctor_id', $user->id)->with('patient')->latest()->paginate(20);
        }
        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $patient = null;
        if ($request->has('patient_id')) {
            $patient = Patient::find($request->patient_id);
        }
        $patients = Auth::user()->hasRole('admin') ? Patient::all() : Auth::user()->patients;
        
        return view('payments.create', compact('patients', 'patient'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'amount' => 'required|numeric|min:0',
            'concept' => 'required|string',
            'date' => 'required|date',
            'method' => 'nullable|string',
        ]);

        $payment = new Payment($validated);
        $payment->doctor_id = Auth::id();
        $payment->save();

        return redirect()->route('patients.show', $validated['patient_id'])->with('success', 'Payment recorded.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
