<?php

namespace App\Http\Controllers;

use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MedicalRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $patientId = $request->query('patient_id');
        if ($patientId) {
            $records = MedicalRecord::where('patient_id', $patientId)->with('doctor')->latest()->get();
        } else {
            $user = Auth::user();
            if ($user->hasRole('admin')) {
                $records = MedicalRecord::with(['patient', 'doctor'])->latest()->paginate(15);
            } else {
                $records = MedicalRecord::where('doctor_id', $user->id)->with('patient')->latest()->paginate(15);
            }
        }

        return view('medical_records.index', compact('records'));
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
        
        return view('medical_records.create', compact('patients', 'patient'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|string',
            'date' => 'required|date',
            'content' => 'required|string',
            'files.*' => 'nullable|file|max:10240', // 10MB max
        ]);

        $record = new MedicalRecord($validated);
        $record->doctor_id = Auth::id();
        $record->save();

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $record->addMedia($file)->toMediaCollection('attachments');
            }
        }

        return redirect()->route('patients.show', $validated['patient_id'])->with('success', 'Record added.');
    }

    /**
     * Display the specified resource.
     */
    public function show(MedicalRecord $medicalRecord)
    {
        $this->authorize('view', $medicalRecord->patient);
        return view('medical_records.show', compact('medicalRecord'));
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
