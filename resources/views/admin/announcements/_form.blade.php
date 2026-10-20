@php
    $isEdit = $announcement->exists;
    $action = $isEdit ? route('admin.announcements.update', $announcement) : route('admin.announcements.store');
    $selectedBranches = old('branches', $selectedBranchIds ?? []);
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-card title="What to say" subtitle="At least 10 characters so the page does not look empty.">
        <div class="space-y-5">
            <x-form-field name="type" label="Post type" required>
                <x-select name="type" required>
                    @foreach (['notice' => 'Offer or notice', 'event' => 'Employee event', 'job' => 'Job opening'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('type', $announcement->type ?? 'notice') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <x-form-field name="title" label="Title" required>
                <x-input name="title" :value="old('title', $announcement->title)" required maxlength="255" placeholder="Ramadan timings at Downtown Station" />
            </x-form-field>

            <x-form-field name="body" label="Message" required>
                <x-textarea name="body" rows="6" required placeholder="Tell customers what is happening and what to expect.">{{ old('body', $announcement->body) }}</x-textarea>
            </x-form-field>

            <x-form-field name="image_path" label="Image URL" hint="Optional. Used as the banner on the home page.">
                <x-input name="image_path" :value="old('image_path', $announcement->image_path)" maxlength="255" placeholder="https://example.com/banner.jpg" />
            </x-form-field>
        </div>
    </x-card>

    <x-card title="Who sees it" subtitle="A network-wide notice skips the station list entirely.">
        <div class="space-y-5">
            <x-form-field name="scope" label="Audience" required>
                <x-select name="scope" required data-scope-toggle>
                    @foreach ($scopes as $value => $label)
                        <option value="{{ $value }}" @selected(old('scope', $announcement->scope->value) === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>
            </x-form-field>

            <div data-branch-picker>
                <p class="text-sm font-medium text-slate-700">Stations</p>
                <p class="mt-0.5 text-xs text-slate-500">Required for a targeted announcement. Leave every box unticked for a network-wide notice.</p>

                @if ($branches->isEmpty())
                    <p class="mt-3 rounded-lg bg-slate-50 px-4 py-3 text-sm text-slate-500">
                        There are no stations in the network yet.
                    </p>
                @else
                    <ul class="mt-3 grid gap-1 sm:grid-cols-2">
                        @foreach ($branches as $branch)
                            <li>
                                <label class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-slate-50">
                                    <input
                                        type="checkbox"
                                        name="branches[]"
                                        value="{{ $branch->id }}"
                                        @checked(in_array($branch->id, array_map('intval', (array) $selectedBranches), true))
                                        class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    <span class="text-sm text-slate-700">{{ $branch->name }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </x-card>

    <x-card title="When it runs" subtitle="Leave the expiry blank to run it until you turn it off.">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="publish_at" label="Publish from">
                <x-input name="publish_at" type="datetime-local" :value="old('publish_at', $announcement->publish_at?->format('Y-m-d\TH:i'))" />
            </x-form-field>

            <x-form-field name="expires_at" label="Expires" hint="Must be on or after the publish date.">
                <x-input name="expires_at" type="datetime-local" :value="old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i'))" />
            </x-form-field>
        </div>
    </x-card>

    <x-card title="Visibility">
        <div class="space-y-3">
            <label class="flex items-start gap-3">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published ?? true))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Published</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Untick to keep it as a draft nobody can see.</span>
                </span>
            </label>

            <label class="flex items-start gap-3">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $announcement->is_featured))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-violet-600 focus:ring-violet-500">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Feature on the home page</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Featured notices sit above the other announcements.</span>
                </span>
            </label>
        </div>
    </x-card>

    <div class="flex items-center gap-2">
        <x-button type="submit">{{ $isEdit ? 'Save changes' : 'Publish announcement' }}</x-button>
        <x-button :href="route('admin.announcements.index')" variant="secondary">Cancel</x-button>
    </div>
</form>

@push('scripts')
    <script>
        // Hide the station picker while the audience is network-wide, since a
        // global announcement must not carry branch rows.
        (() => {
            const scope = document.querySelector('[data-scope-toggle]');
            const picker = document.querySelector('[data-branch-picker]');
            if (!scope || !picker) return;

            const sync = () => {
                const isGlobal = scope.value === 'global';
                picker.hidden = isGlobal;
                picker.querySelectorAll('input[type="checkbox"]').forEach((box) => {
                    box.disabled = isGlobal;
                });
            };

            scope.addEventListener('change', sync);
            sync();
        })();
    </script>
@endpush
