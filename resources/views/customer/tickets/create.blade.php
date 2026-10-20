@extends('layouts.app')

@section('title', 'Open an inquiry')

@section('content')
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6 lg:px-8">
        <header>
            <h1 class="text-3xl font-semibold tracking-tight text-slate-900">Open an inquiry</h1>
            <p class="mt-1 text-slate-600">
                Tell the branch what happened. You will get a reply on this page and by email, and you can keep
                writing back until it is resolved.
            </p>
        </header>

        <x-card class="mt-6">
            <form method="POST" action="{{ route('my.tickets.store') }}" class="space-y-5">
                @csrf

                <x-form-field name="branch_id" label="Which station?" required>
                    <x-select id="branch_id" name="branch_id" required>
                        <option value="">Choose a station</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === $branch->id)>
                                {{ $branch->name }} — {{ $branch->city }}
                            </option>
                        @endforeach
                    </x-select>
                </x-form-field>

                <x-form-field name="category" label="What is this about?" required>
                    <x-select id="category" name="category" required>
                        <option value="">Choose a category</option>
                        @foreach ($categories as $value => $label)
                            <option value="{{ $value }}" @selected(old('category') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-form-field>

                <x-form-field name="subject" label="Subject" required hint="A short summary, e.g. “Card charged twice”.">
                    <x-input id="subject" name="subject" :value="old('subject')" required maxlength="180" />
                </x-form-field>

                <x-form-field name="priority" label="How urgent is this?" required>
                    <x-select id="priority" name="priority" required>
                        @foreach (\App\Enums\TicketPriority::options() as $value => $label)
                            <option value="{{ $value }}" @selected(old('priority', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-form-field>

                <x-form-field name="message" label="Details" required hint="Include dates, times, pump or bay numbers if you have them.">
                    <x-textarea id="message" name="message" rows="6" required maxlength="5000">{{ old('message') }}</x-textarea>
                </x-form-field>

                <div class="flex items-center gap-3 border-t border-slate-100 pt-5">
                    <x-button type="submit">Submit inquiry</x-button>
                    <x-button :href="route('my.tickets.index')" variant="ghost">Cancel</x-button>
                </div>
            </form>
        </x-card>
    </div>
@endsection
