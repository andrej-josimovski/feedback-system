<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Reports') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900">Create report</h3>
                <form method="POST" action="{{ route('admin.reports.store') }}" class="mt-4 grid gap-4 lg:grid-cols-5">
                    @csrf
                    <div class="lg:col-span-2">
                        <x-input-label for="title" :value="__('Title')" />
                        <x-text-input id="title" name="title" class="mt-1 block w-full" value="{{ old('title') }}" required />
                        <x-input-error :messages="$errors->get('title')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="product_id" :value="__('Product')" />
                        <select id="product_id" name="product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('product_id')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="period_from" :value="__('From')" />
                        <x-text-input id="period_from" type="date" name="period_from" class="mt-1 block w-full" value="{{ old('period_from') }}" required />
                        <x-input-error :messages="$errors->get('period_from')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="period_to" :value="__('To')" />
                        <x-text-input id="period_to" type="date" name="period_to" class="mt-1 block w-full" value="{{ old('period_to') }}" required />
                        <x-input-error :messages="$errors->get('period_to')" class="mt-2" />
                    </div>
                    <div class="lg:col-span-5">
                        <x-primary-button>{{ __('Create report') }}</x-primary-button>
                    </div>
                </form>
            </section>

            <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Report</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">AI</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($reports as $report)
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900">{{ $report->title }}</div>
                                        <div class="text-sm text-gray-500">{{ str_replace('_', ' ', $report->status) }}</div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $report->product?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $report->period_from->format('M j, Y') }} - {{ $report->period_to->format('M j, Y') }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $report->ai_status ?? 'not started' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('admin.reports.show', $report) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-900">Open</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
