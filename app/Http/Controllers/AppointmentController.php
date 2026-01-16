<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $appointments = Appointment::with(['doctor', 'patient'])->latest()->get();
        } elseif ($user->hasRole('doctor')) {
            $appointments = $user->doctorAppointments()->with('patient')->latest()->get();
        } else {
            // Patient view
            $appointments = Appointment::where('patient_id', $user->patient_id)->with('doctor')->latest()->get();
        }

        return view('appointments.index', compact('appointments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $patients = Auth::user()->hasRole('admin') ? Patient::all() : Auth::user()->patients;
        return view('appointments.create', compact('patients'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'notes' => 'nullable|string',
        ]);

        $appointment = new Appointment($validated);
        $appointment->doctor_id = Auth::id(); // Assign to current doctor
        
        // Sync with Google Calendar (basic implementation)
        // Note: Set env vars GOOGLE_CALENDAR_ID to enable.
        try {
            if (config('google-calendar.calendar_id')) {
                $event = new Event;
                $event->name = 'Cita con ' . Patient::find($validated['patient_id'])->name;
                $event->startDateTime = Carbon::parse($validated['start_time']);
                $event->endDateTime = Carbon::parse($validated['end_time']);
                $event->description = $validated['notes'];
                $savedEvent = $event->save();
                $appointment->google_event_id = $savedEvent->id;
            }
        } catch (\Exception $e) {
            // Log error or ignore if sync fails
        }

        $appointment->save();

        return redirect()->route('appointments.index')->with('success', 'Appointment scheduled.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
