<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Feedback') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('admin.feedback.index') }}" class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 grid gap-4 md:grid-cols-4">
                <div>
                    <x-input-label for="product_id" :value="__('Product')" />
                    <select id="product_id" name="product_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All products</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected(($filters['product_id'] ?? '') == $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="rating" :value="__('Rating')" />
                    <select id="rating" name="rating" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All ratings</option>
                        @for ($rating = 5; $rating >= 1; $rating--)
                            <option value="{{ $rating }}" @selected(($filters['rating'] ?? '') == $rating)>{{ $rating }} / 5</option>
                        @endfor
                    </select>
                </div>
                <div class="md:col-span-2 flex items-end gap-3">
                    <x-primary-button>{{ __('Filter') }}</x-primary-button>
                    <a href="{{ route('admin.feedback.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Clear</a>
                </div>
            </form>

            <section class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rating</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Comment</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($feedback as $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item->product?->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->user?->name ?? 'Anonymous' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->rating }} / 5</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $item->comment ?: 'No comment' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $item->created_at->format('M j, Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No feedback found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
