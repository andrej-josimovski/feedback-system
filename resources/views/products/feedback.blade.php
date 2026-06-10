<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Leave Feedback') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $product->name }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $product->description }}</p>
                </div>

                <form method="POST" action="{{ route('products.feedback.store', $product) }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-input-label for="rating" :value="__('Rating')" />
                        <select id="rating" name="rating" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @for ($rating = 5; $rating >= 1; $rating--)
                                <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} / 5</option>
                            @endfor
                        </select>
                        <x-input-error :messages="$errors->get('rating')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="comment" :value="__('Comment')" />
                        <textarea id="comment" name="comment" rows="7" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment') }}</textarea>
                        <x-input-error :messages="$errors->get('comment')" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Submit feedback') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
