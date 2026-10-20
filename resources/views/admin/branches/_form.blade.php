@php
    $isEdit = $branch->exists;
    $action = $isEdit ? route('admin.branches.update', $branch) : route('admin.branches.store');
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <x-card title="Identity" subtitle="How the station appears to customers.">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="name" label="Station name" required>
                <x-input name="name" :value="old('name', $branch->name)" required maxlength="255" placeholder="Downtown Station" />
            </x-form-field>

            <x-form-field name="code" label="Station code" required hint="Short unique reference used on reports.">
                <x-input name="code" :value="old('code', $branch->code)" required maxlength="20" placeholder="DXB-001" class="uppercase" />
            </x-form-field>
        </div>
    </x-card>

    <x-card title="Address" subtitle="Used for the public listing and the map.">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-form-field name="address_line1" label="Address line 1" required>
                    <x-input name="address_line1" :value="old('address_line1', $branch->address_line1)" required maxlength="255" placeholder="Sheikh Zayed Road" />
                </x-form-field>
            </div>

            <div class="sm:col-span-2">
                <x-form-field name="address_line2" label="Address line 2">
                    <x-input name="address_line2" :value="old('address_line2', $branch->address_line2)" maxlength="255" placeholder="Optional" />
                </x-form-field>
            </div>

            <x-form-field name="city" label="City" required>
                <x-input name="city" :value="old('city', $branch->city)" required maxlength="100" placeholder="Dubai" />
            </x-form-field>

            <x-form-field name="region" label="Region / emirate" required>
                <x-input name="region" :value="old('region', $branch->region)" required maxlength="100" placeholder="Dubai" />
            </x-form-field>

            <x-form-field name="postal_code" label="Postal code">
                <x-input name="postal_code" :value="old('postal_code', $branch->postal_code)" maxlength="20" placeholder="Optional" />
            </x-form-field>

            <x-form-field name="country" label="Country">
                <x-input name="country" :value="old('country', $branch->country ?? 'United Arab Emirates')" maxlength="100" />
            </x-form-field>

            <x-form-field name="phone" label="Phone">
                <x-input name="phone" :value="old('phone', $branch->phone)" maxlength="40" placeholder="+971 4 000 0000" />
            </x-form-field>

            <x-form-field name="email" label="Email">
                <x-input name="email" type="email" :value="old('email', $branch->email)" maxlength="255" placeholder="Optional" />
            </x-form-field>
        </div>
    </x-card>

    <x-card title="Map position" subtitle="Leave blank to look the address up automatically when you save.">
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form-field name="latitude" label="Latitude" hint="-90 to 90">
                <x-input name="latitude" type="number" step="any" :value="old('latitude', $branch->latitude)" placeholder="24.4539" />
            </x-form-field>

            <x-form-field name="longitude" label="Longitude" hint="-180 to 180">
                <x-input name="longitude" type="number" step="any" :value="old('longitude', $branch->longitude)" placeholder="54.3773" />
            </x-form-field>
        </div>
    </x-card>

    <x-card title="Opening hours" subtitle="24-hour stations ignore these.">
        <label class="mb-5 flex items-start gap-3">
            <input type="checkbox" name="is_24_hours" value="1" @checked(old('is_24_hours', $branch->is_24_hours))
                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            <span>
                <span class="block text-sm font-medium text-slate-700">Open 24 hours</span>
                <span class="mt-0.5 block text-xs text-slate-500">Leave the day-by-day times below blank.</span>
            </span>
        </label>

        <div class="space-y-3">
            @foreach ($weekDays as $dayKey => $dayLabel)
                @php
                    $dayHours = old("opening_hours.$dayKey", $branch->opening_hours[$dayKey] ?? []);
                    $openName = "opening_hours.$dayKey.open";
                    $closeName = "opening_hours.$dayKey.close";
                @endphp
                <div class="grid items-end gap-3 sm:grid-cols-[10rem_1fr_1fr]">
                    <p class="pb-2 text-sm font-medium text-slate-700">{{ $dayLabel }}</p>

                    <x-form-field :name="$openName" label="Opens">
                        <x-input
                            type="time"
                            :name="$openName"
                            :value="old($openName, $dayHours['open'] ?? null)" />
                    </x-form-field>

                    <x-form-field :name="$closeName" label="Closes">
                        <x-input
                            type="time"
                            :name="$closeName"
                            :value="old($closeName, $dayHours['close'] ?? null)" />
                    </x-form-field>
                </div>
            @endforeach
        </div>

        <p class="mt-5 text-xs text-slate-500">Fill in only one time to mean &ldquo;open from that time onwards&rdquo;.</p>
    </x-card>

    <x-card title="Public listing" subtitle="What customers see on the station page.">
        <div class="space-y-5">
            <x-form-field name="description" label="Description">
                <x-textarea name="description" rows="4" maxlength="5000" placeholder="Tell customers what this station offers.">{{ old('description', $branch->description) }}</x-textarea>
            </x-form-field>

            @php
                $oldPhotos = old('photos');
                $photoText = is_array($oldPhotos)
                    ? implode("\n", $oldPhotos)
                    : ($oldPhotos ?? ($branch->photos ? implode("\n", $branch->photos) : ''));
            @endphp

            <x-form-field name="photos" label="Photo URLs" hint="Optional: up to 8 image links, one per line.">
                <x-textarea
                    name="photos"
                    rows="4"
                    placeholder="https://example.com/station.jpg"
                >{{ $photoText }}</x-textarea>
            </x-form-field>

            <x-form-field name="photo_uploads" label="Upload station photos" hint="JPG, PNG, or WebP. Up to 8 images, 5 MB each. Uploaded photos are added to the gallery.">
                <input id="photo_uploads" name="photo_uploads[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
                       class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-brand-700">
            </x-form-field>

            <label class="flex items-start gap-3">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $branch->is_active ?? true))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Station is active</span>
                    <span class="mt-0.5 block text-xs text-slate-500">Inactive stations stay in the database but disappear from public search and cannot be booked against.</span>
                </span>
            </label>
        </div>
    </x-card>

    <div class="flex items-center gap-2">
        <x-button type="submit">{{ $isEdit ? 'Save changes' : 'Create station' }}</x-button>
        <x-button :href="route('admin.branches.index')" variant="secondary">Cancel</x-button>
    </div>
</form>
