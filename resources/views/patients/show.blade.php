<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ $patient->name }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ activeTab: 'records' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Patient Info Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900 dark:text-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-semibold">{{ $patient->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Phone</p>
                        <p class="font-semibold">{{ $patient->phone ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Date of Birth</p>
                        <p class="font-semibold">{{ $patient->dob ? $patient->dob->format('d M Y') : 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Address</p>
                        <p class="font-semibold">{{ $patient->address ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button @click="activeTab = 'records'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'records', 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300': activeTab !== 'records' }" class="inline-block p-4 border-b-2 rounded-t-lg" type="button">Clinical Records</button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button @click="activeTab = 'appointments'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'appointments', 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300': activeTab !== 'appointments' }" class="inline-block p-4 border-b-2 rounded-t-lg" type="button">Appointments</button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button @click="activeTab = 'payments'" :class="{ 'border-blue-500 text-blue-600': activeTab === 'payments', 'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300': activeTab !== 'payments' }" class="inline-block p-4 border-b-2 rounded-t-lg" type="button">Payments</button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    
                    <!-- Clinical Records Tab -->
                    <div x-show="activeTab === 'records'">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Clinical History</h3>
                            <a href="{{ route('medical_records.create', ['patient_id' => $patient->id]) }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-1 px-3 rounded text-sm">Add Record</a>
                        </div>
                        <ul class="space-y-4">
                            @forelse ($patient->medicalRecords as $record)
                                <li class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $record->type }}</span>
                                        <span class="text-sm text-gray-500">{{ $record->date->format('d M Y') }}</span>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $record->content }}</p>
                                    @if($record->getMedia('attachments')->isNotEmpty())
                                        <div class="mt-2 flex gap-2">
                                            @foreach($record->getMedia('attachments') as $media)
                                                <a href="{{ $media->getUrl() }}" target="_blank" class="text-blue-500 text-sm underline">{{ $media->file_name }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <p class="text-gray-500">No records found.</p>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Appointments Tab -->
                    <div x-show="activeTab === 'appointments'" style="display: none;">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Appointments</h3>
                            <a href="{{ route('appointments.create', ['patient_id' => $patient->id]) }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">Schedule</a>
                        </div>
                        <ul class="space-y-4">
                            @forelse ($patient->appointments as $appointment)
                                <li class="border-b border-gray-200 dark:border-gray-700 pb-4">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $appointment->start_time->format('d M Y H:i') }}</span>
                                        <span class="badge {{ $appointment->end_time->isPast() ? 'text-gray-500' : 'text-green-500' }}">
                                            {{ $appointment->end_time->isPast() ? 'Completed' : 'Upcoming' }}
                                        </span>
                                    </div>
                                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $appointment->notes }}</p>
                                </li>
                            @empty
                                <p class="text-gray-500">No appointments found.</p>
                            @endforelse
                        </ul>
                    </div>

                    <!-- Payments Tab -->
                    <div x-show="activeTab === 'payments'" style="display: none;">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Payments</h3>
                            <a href="{{ route('payments.create', ['patient_id' => $patient->id]) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-1 px-3 rounded text-sm">Record Payment</a>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase">Concept</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($patient->payments as $payment)
                                    <tr>
                                        <td class="py-2">{{ $payment->date->format('d/m/Y') }}</td>
                                        <td class="py-2">{{ $payment->concept }}</td>
                                        <td class="py-2 text-right">${{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-gray-500 py-2">No payments recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
