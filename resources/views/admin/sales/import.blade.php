@extends('layouts.admin')

@section('title', 'Import sales from CSV')
@section('subtitle', 'Upload a day or a month of figures recorded elsewhere.')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-card title="Upload a file">
                <form method="POST" action="{{ route('admin.sales.import.store') }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf

                    <x-form-field name="file" label="CSV file" required hint="Comma separated, up to 5 MB. The header row must match the template exactly.">
                        <input
                            type="file"
                            name="file"
                            accept=".csv,text/csv,text/plain"
                            required
                            class="block w-full rounded-lg border border-slate-300 text-sm shadow-sm file:mr-4 file:rounded-l-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        @error('file')
                            <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                        @enderror
                    </x-form-field>

                    <div class="rounded-lg bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Expected columns, in order</p>
                        <p class="mt-1.5 font-mono text-xs text-slate-700">{{ $template }}</p>
                    </div>

                    <div class="flex items-center gap-2 border-t border-slate-200 pt-5">
                        <x-button type="submit">Import</x-button>
                        <x-button :href="route('admin.sales.import.template')" variant="secondary">Download blank template</x-button>
                    </div>
                </form>
            </x-card>

            @if ($result)
                <x-card title="Import result">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-emerald-50 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-emerald-700">Imported</p>
                            <p class="mt-1 text-2xl font-semibold text-emerald-900">{{ $result['imported'] }}</p>
                        </div>
                        <div class="rounded-lg bg-slate-50 px-4 py-3">
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Skipped</p>
                            <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $result['skipped'] }}</p>
                        </div>
                        <div class="rounded-lg px-4 py-3 {{ $result['errors'] ? 'bg-rose-50' : 'bg-slate-50' }}">
                            <p class="text-xs font-medium uppercase tracking-wide {{ $result['errors'] ? 'text-rose-700' : 'text-slate-500' }}">Errors</p>
                            <p class="mt-1 text-2xl font-semibold {{ $result['errors'] ? 'text-rose-900' : 'text-slate-900' }}">
                                {{ count($result['errors']) }}
                            </p>
                        </div>
                    </div>

                    @if ($result['errors'])
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-slate-800">What went wrong</p>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($result['errors'] as $error)
                                    <li class="flex gap-2 text-sm text-rose-700">
                                        <span aria-hidden="true">&bull;</span>
                                        <span>{{ $error }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($result['skipped'] > 0)
                        <div class="mt-5">
                            <p class="text-sm font-semibold text-slate-800">Rows that were skipped</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Each skipped line is listed above with its reason, such as an unknown product, a future date, or sales that already exist for that item and date.
                            </p>
                        </div>
                    @endif
                </x-card>
            @endif
        </div>

        <aside class="space-y-6">
            <x-card title="How the import works">
                <ol class="space-y-3 text-sm text-slate-600">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">1</span>
                        <span>Each row is matched to a catalog item by <span class="font-mono text-xs">product</span> name.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">2</span>
                        <span>Sales are always booked against <strong>your</strong> branch, whatever the file says.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">3</span>
                        <span>A row for a date that already has sales is skipped, not merged, so nothing is double counted.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">4</span>
                        <span>Anything that cannot be parsed is reported above and the rest still imports.</span>
                    </li>
                </ol>
            </x-card>

            <x-card title="Rather record by hand?">
                <p class="text-sm text-slate-600">
                    The day entry screen is quicker when you are entering a single day from the till.
                </p>
                <x-button :href="route('admin.sales.create')" variant="secondary" class="mt-4 w-full">Record a day</x-button>
            </x-card>
        </aside>
    </div>
@endsection
