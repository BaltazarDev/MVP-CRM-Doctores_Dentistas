<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Record Payment') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="POST" action="{{ route('payments.store') }}">
                        @csrf

                        <!-- Patient Select -->
                         @if($patient)
                            <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                            <p class="mb-4">Patient: <strong>{{ $patient->name }}</strong></p>
                        @else
                            <div class="mb-4">
                                <x-input-label for="patient_id" :value="__('Patient')" />
                                <select id="patient_id" name="patient_id" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    @foreach($patients as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <!-- Amount -->
                        <div>
                            <x-input-label for="amount" :value="__('Amount ($)')" />
                            <x-text-input id="amount" class="block mt-1 w-full" type="number" step="0.01" name="amount" :value="old('amount')" required />
                        </div>

                        <!-- Concept -->
                        <div class="mt-4">
                            <x-input-label for="concept" :value="__('Concept')" />
                            <x-text-input id="concept" class="block mt-1 w-full" type="text" name="concept" :value="old('concept')" required />
                        </div>

                        <!-- Date -->
                        <div class="mt-4">
                            <x-input-label for="date" :value="__('Date')" />
                            <x-text-input id="date" class="block mt-1 w-full" type="date" name="date" :value="date('Y-m-d')" required />
                        </div>

                        <!-- Method -->
                        <div class="mt-4">
                            <x-input-label for="method" :value="__('Payment Method')" />
                            <select id="method" name="method" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Transfer">Transfer</option>
                                <option value="Insurance">Insurance</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button class="ml-4">
                                {{ __('Save Payment') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
