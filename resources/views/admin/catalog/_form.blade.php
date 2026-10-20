@php
    use App\Enums\CatalogItemType;
    use App\Enums\FuelType;

    $isProduct = ($item->type ?? null) === CatalogItemType::Product;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-card title="Item details">
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <x-form-field name="name" label="Name" required>
                        <x-input name="name" :value="old('name', $item->name)" required maxlength="255" />
                    </x-form-field>
                </div>

                <x-form-field name="type" label="Type" required>
                    <x-select name="type" required>
                        @foreach (CatalogItemType::options() as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $item->type?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-form-field>

                <x-form-field name="sku" label="SKU" hint="Optional internal code, unique across the catalog.">
                    <x-input name="sku" :value="old('sku', $item->sku)" maxlength="40" />
                </x-form-field>

                <x-form-field name="fuel_type" label="Fuel type" hint="Only meaningful for products.">
                    <x-select name="fuel_type">
                        <option value="">Not applicable</option>
                        @foreach (FuelType::options() as $value => $label)
                            <option value="{{ $value }}" @selected(old('fuel_type', $item->fuel_type?->value) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-select>
                </x-form-field>

                <x-form-field name="unit" label="Unit" hint="How the item is measured, e.g. liter or kWh.">
                    <x-input name="unit" :value="old('unit', $item->unit)" maxlength="30" placeholder="liter" />
                </x-form-field>

                <div class="sm:col-span-2">
                    <x-form-field name="short_description" label="Short description" hint="One line, shown in listings and search results.">
                        <x-input name="short_description" :value="old('short_description', $item->short_description)" maxlength="255" />
                    </x-form-field>
                </div>

                <div class="sm:col-span-2">
                    <x-form-field name="description" label="Full description">
                        <x-textarea name="description" rows="5" maxlength="5000">{{ old('description', $item->description) }}</x-textarea>
                    </x-form-field>
                </div>

                <div class="sm:col-span-2">
                    <x-form-field name="image_path" label="Image path" hint="Optional path or URL to an item photo.">
                        <x-input name="image_path" :value="old('image_path', $item->image_path)" maxlength="255" />
                    </x-form-field>
                </div>
            </div>
        </x-card>
    </div>

    <aside class="space-y-6">
        <x-card title="Availability">
            <label class="flex items-start gap-3">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', $item->is_active ?? true))
                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span>
                    <span class="block text-sm font-medium text-slate-700">Active</span>
                    <span class="mt-0.5 block text-xs text-slate-500">
                        Inactive items stay in past sales history but are hidden from the public catalog and cannot be priced.
                    </span>
                </span>
            </label>
        </x-card>

        <x-card title="Save">
            <div class="flex flex-col gap-2">
                <x-button type="submit">
                    {{ $item->exists ? 'Save changes' : 'Add to catalog' }}
                </x-button>
                <x-button :href="route('admin.catalog.index')" variant="secondary">Cancel</x-button>
            </div>
        </x-card>
    </aside>
</div>
