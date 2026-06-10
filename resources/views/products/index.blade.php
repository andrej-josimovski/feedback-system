<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Products') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($products as $product)
                    <article class="bg-white border border-gray-200 rounded-lg shadow-sm p-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                                <p class="mt-1 text-sm text-gray-500">{{ $product->team?->name }}</p>
                            </div>
                            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                {{ $product->feedbacks_count }} feedback
                            </span>
                        </div>
                        <p class="mt-4 min-h-16 text-sm text-gray-600">{{ $product->description ?: 'No description yet.' }}</p>
                        <a href="{{ route('products.feedback.create', $product) }}" class="mt-5 inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Leave feedback
                        </a>
                    </article>
                @empty
                    <div class="bg-white border border-gray-200 rounded-lg p-6 text-gray-600">No products available.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
