<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\SaleSource;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Sale;
use App\Models\User;
use App\Support\ActivityAction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Imports daily sales figures from a CSV upload.
 *
 * Expected header row:
 * sale_date,product,quantity,unit_price,payment_method
 */
class SalesImportService
{
    /**
     * @var array<int, string>
     */
    public const EXPECTED_HEADERS = ['sale_date', 'product', 'quantity', 'unit_price', 'payment_method'];

    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * @return array{imported: int, skipped: int, errors: array<int, string>, rows: array<int, array<string, string>>}
     */
    public function import(UploadedFile $file, User $actor): array
    {
        $branch = $actor->branch;

        // Sales belong to a branch, so head office (which has no branch of its
        // own) reads and exports the network but never writes into it.
        if ($branch === null) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Only branch staff can import sales figures. Ask the branch you want to correct.'],
                'rows' => [],
            ];
        }

        $rows = $this->readRows($file);

        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['The uploaded file contains no data rows.'], 'rows' => []];
        }

        $header = array_map(
            static fn (string $column): string => strtolower(trim($column)),
            array_keys($rows[0]),
        );

        $missing = array_diff(self::EXPECTED_HEADERS, $header);

        if ($missing !== []) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['Missing required column(s): '.implode(', ', $missing).'.'],
                'rows' => [],
            ];
        }

        $errors = [];
        $parsed = [];
        $skipped = 0;

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;

            $catalogItem = $this->resolveCatalogItem($row);

            if ($catalogItem === null) {
                $errors[] = "Row {$lineNumber}: product '".($row['product'] ?? '')."' is not in the catalog.";
                $skipped++;

                continue;
            }

            $saleDate = $this->resolveDate($row['sale_date'] ?? null);

            if ($saleDate === null) {
                $errors[] = "Row {$lineNumber}: '".($row['sale_date'] ?? '')."' is not a valid sale date.";
                $skipped++;

                continue;
            }

            $quantity = (float) str_replace(',', '', (string) ($row['quantity'] ?? ''));

            if ($quantity <= 0) {
                $errors[] = "Row {$lineNumber}: quantity must be greater than zero.";
                $skipped++;

                continue;
            }

            $unitPrice = (float) str_replace(',', '', (string) ($row['unit_price'] ?? ''));

            if ($unitPrice <= 0) {
                $errors[] = "Row {$lineNumber}: unit price must be greater than zero.";
                $skipped++;

                continue;
            }

            $paymentMethod = strtolower(trim((string) ($row['payment_method'] ?? 'cash')));

            if (! in_array($paymentMethod, PaymentMethod::values(), true)) {
                $paymentMethod = PaymentMethod::Cash->value;
            }

            $parsed[] = [
                'branch_id' => $branch->id,
                'catalog_item_id' => $catalogItem->id,
                'item_name' => $catalogItem->displayName(),
                'sale_date' => $saleDate,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => round($quantity * $unitPrice, 2),
                'currency' => config('app.currency'),
                'payment_method' => $paymentMethod,
                'source' => SaleSource::Import->value,
                'notes' => null,
                'recorded_by' => $actor->id,
            ];
        }

        $imported = DB::transaction(function () use ($parsed): int {
            foreach ($parsed as $attributes) {
                Sale::create($attributes);
            }

            return count($parsed);
        });

        if ($imported > 0) {
            $this->activityLogger->log(
                ActivityAction::SALE_IMPORTED,
                "{$actor->name} imported {$imported} sales row(s) for {$branch->name}.",
                null,
                ['imported' => $imported, 'skipped' => $skipped, 'file' => $file->getClientOriginalName()],
                $actor,
                $branch,
            );
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'rows' => $parsed,
        ];
    }

    /**
     * A blank template that matches EXPECTED_HEADERS, pre-filled with the first
     * few items this branch actually sells so the file is usable as-is.
     */
    public function templateCsv(Branch $branch): string
    {
        $items = $branch->catalogItems()
            ->wherePivot('is_available', true)
            ->where('catalog_items.is_active', true)
            ->orderBy('catalog_items.type')
            ->orderBy('catalog_items.name')
            ->limit(3)
            ->get();

        $lines = [implode(',', self::EXPECTED_HEADERS)];
        $saleDate = today()->subDay()->toDateString();

        foreach ($items as $index => $item) {
            $lines[] = implode(',', [
                $saleDate,
                $this->csvValue($item->name),
                (string) (100 * ($index + 1)),
                '2.45',
                PaymentMethod::Cash->value,
            ]);
        }

        if ($items->isEmpty()) {
            $lines[] = $saleDate.',Green,120,2.45,'.PaymentMethod::Cash->value;
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Quote a value only when it contains a comma, quote or newline.
     */
    protected function csvValue(string $value): string
    {
        return preg_match('/[",\n\r]/', $value) === 1
            ? '"'.str_replace('"', '""', $value).'"'
            : $value;
    }

    /**
     * @return Collection<int, array<string, string>>
     */
    protected function readRows(UploadedFile $file): Collection
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if ($handle === false) {
            return collect();
        }

        $headers = fgetcsv($handle);

        if ($headers === false) {
            fclose($handle);

            return collect();
        }

        // Strip a UTF-8 byte order mark so the first header matches.
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]) ?? (string) $headers[0];
        $headers = array_map(
            static fn (string $column): string => mb_strtolower(trim($column)),
            $headers,
        );

        $rows = [];

        // fgetcsv (rather than str_getcsv per line) so quoted values containing
        // commas or newlines are parsed correctly.
        while (($values = fgetcsv($handle)) !== false) {
            if (count($values) === 1 && trim((string) $values[0]) === '') {
                continue;
            }

            if (count($values) !== count($headers)) {
                continue;
            }

            $rows[] = array_combine(
                $headers,
                array_map(static fn (?string $value): string => trim((string) $value), $values),
            );
        }

        fclose($handle);

        return collect($rows)->values();
    }

    protected function resolveCatalogItem(array $row): ?CatalogItem
    {
        $identifier = trim((string) ($row['product'] ?? ''));

        if ($identifier === '') {
            return null;
        }

        return CatalogItem::query()
            ->where(function ($query) use ($identifier): void {
                $query->where('name', $identifier)
                    ->orWhere('sku', $identifier)
                    ->orWhere('slug', $identifier);
            })
            ->first()
            ?? CatalogItem::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($identifier)])->first();
    }

    protected function resolveDate(?string $value): ?Carbon
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
