<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $report->title }}</h2>
            <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">Back to reports</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-700">{{ session('status') }}</div>
            @endif

            <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <div class="grid gap-4 md:grid-cols-4">
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Product</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $report->product?->name }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Period</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $report->period_from->format('M j, Y') }} - {{ $report->period_to->format('M j, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Report status</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ str_replace('_', ' ', $report->status) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium uppercase tracking-wider text-gray-500">AI status</div>
                        <div class="mt-1 text-sm font-semibold text-gray-900">{{ $report->ai_status ?? 'not started' }}</div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('admin.reports.analyze', $report) }}">
                        @csrf
                        <x-primary-button>{{ __('Generate AI summary') }}</x-primary-button>
                    </form>
                    <form method="POST" action="{{ route('admin.reports.publish', $report) }}">
                        @csrf
                        @method('PATCH')
                        <x-secondary-button type="submit">{{ __('Publish') }}</x-secondary-button>
                    </form>
                </div>

                @if ($report->ai_error)
                    <div class="mt-6 rounded-md bg-red-50 p-4 text-sm text-red-700">{{ $report->ai_error }}</div>
                @endif
            </section>

            @if ($report->ai_analysis)
                <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-900">AI summary</h3>
                    <p class="mt-3 text-sm leading-6 text-gray-700">{{ $report->ai_analysis['summary_message'] ?? 'Summary generated.' }}</p>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-md bg-gray-50 p-4">
                            <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Feedback count</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $report->ai_analysis['feedback_count'] ?? 0 }}</div>
                        </div>
                        <div class="rounded-md bg-gray-50 p-4">
                            <div class="text-xs font-medium uppercase tracking-wider text-gray-500">Average rating</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900">{{ $report->ai_analysis['average_rating'] ?? 'N/A' }}</div>
                        </div>
                    </div>
                </section>
            @endif

            <section class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900">Report sections</h3>
                <div class="mt-4 space-y-4">
                    @forelse ($report->sections->sortBy('order') as $section)
                        <article class="rounded-md border border-gray-200 p-4">
                            <div x-data="{ editing: false }">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-semibold text-gray-900">{{ $section->theme }}</h4>
                                    <x-secondary-button type="button" @click="editing = !editing">{{ __('Edit') }}</x-secondary-button>
                                </div>

                                {{-- View mode --}}
                                <div x-show="!editing">
                                    <p class="mt-2 text-sm leading-6 text-gray-700">{{ $section->ai_summary }}</p>
                                    @if ($section->admin_summary)
                                        <div class="mt-2 text-sm text-gray-700"><span class="font-medium">Admin summary:</span> {{ $section->admin_summary }}</div>
                                    @endif
                                    @if ($section->issues)
                                        <div class="mt-3 text-sm text-gray-700"><span class="font-medium">Issues:</span> {{ implode(', ', $section->issues) }}</div>
                                    @endif
                                    @if ($section->proposals)
                                        <div class="mt-2 text-sm text-gray-700"><span class="font-medium">Proposals:</span> {{ implode(', ', $section->proposals) }}</div>
                                    @endif
                                </div>

                                {{-- Edit mode --}}
                                <div x-show="editing" class="mt-4">
                                    <form method="POST" action="{{ route('admin.reports.sections.update', [$report, $section]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="space-y-3">
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">Theme</label>
                                                <input type="text" name="theme" value="{{ $section->theme }}" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">AI Summary</label>
                                                <textarea name="ai_summary" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">{{ $section->ai_summary }}</textarea>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">Admin Summary</label>
                                                <textarea name="admin_summary" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">{{ $section->admin_summary }}</textarea>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">Issues (one per line)</label>
                                                <textarea name="issues" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">{{ implode("\n", $section->issues ?? []) }}</textarea>
                                            </div>
                                            <div>
                                                <label class="text-xs font-medium text-gray-500">Proposals (one per line)</label>
                                                <textarea name="proposals" rows="3" class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm">{{ implode("\n", $section->proposals ?? []) }}</textarea>
                                            </div>
                                            <div class="flex gap-2">
                                                <x-primary-button type="submit">Save</x-primary-button>
                                                <x-secondary-button type="button" @click="editing = false">Cancel</x-secondary-button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="text-sm text-gray-500">No sections generated yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
