<?php

namespace App\Http\Controllers\Timekeeping;

use App\Http\Controllers\Controller;
use App\Models\AttendanceImportBatch;
use App\Models\AttendanceRecord;
use App\Models\Branch;
use App\Models\Employee;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceImportController extends Controller
{
    private const TIMEZONE = 'Asia/Manila';

    public function template(Request $request): StreamedResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $branch = $this->branchForRequest($request, $request->query('branch_id'));
        $employees = Employee::query()->active()->where('branch_id', $branch->id)
            ->orderBy('employee_number')->limit(2)->get();
        $sampleDate = CarbonImmutable::now(self::TIMEZONE)->toDateString();

        return response()->streamDownload(function () use ($employees, $sampleDate): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['employee_number', 'shift_date', 'time_in', 'time_out']);
            foreach ($employees as $index => $employee) {
                fputcsv($output, [$employee->employee_number, $sampleDate, $index === 0 ? '08:00' : '09:00', $index === 0 ? '17:00' : '18:00']);
            }
            fclose($output);
        }, 'biometric-attendance-sample.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $data = $request->validate([
            'branch_id' => [$request->user()->isSuperAdmin() ? 'required' : 'nullable', 'integer'],
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);
        $branch = $this->branchForRequest($request, $data['branch_id'] ?? null);
        $file = $request->file('file');
        $token = bin2hex(random_bytes(20));
        $oldImport = $request->session()->get('attendance_import');
        if (is_array($oldImport) && isset($oldImport['token'])) {
            Storage::disk('local')->delete('timekeeping/import-staging/'.$oldImport['token'].'.csv');
        }
        Storage::disk('local')->put('timekeeping/import-staging/'.$token.'.csv', file_get_contents($file->getRealPath()));
        $import = ['token' => $token, 'branch_id' => $branch->id, 'filename' => $file->getClientOriginalName()];
        $request->session()->put('attendance_import', $import);

        return $this->previewView($request, $import, $branch);
    }

    public function confirm(Request $request): RedirectResponse|View
    {
        abort_unless($request->user()->canManageTimekeeping(), 403);
        $import = $request->session()->get('attendance_import');
        abort_unless(is_array($import) && isset($import['token'], $import['branch_id']), 419, 'Upload the file again to continue.');
        $branch = $this->branchForRequest($request, $import['branch_id']);
        $path = 'timekeeping/import-staging/'.$import['token'].'.csv';
        abort_unless(Storage::disk('local')->exists($path), 419, 'The staged file has expired. Upload it again.');
        $parsed = $this->parseFile(Storage::disk('local')->path($path), $branch->id);

        if ($parsed['errors'] !== [] || $parsed['rows'] === []) {
            return $this->previewView($request, $import, $branch, $parsed);
        }

        DB::transaction(function () use ($parsed, $branch, $request, $import): void {
            $batch = AttendanceImportBatch::create([
                'branch_id' => $branch->id,
                'uploaded_by' => $request->user()->id,
                'original_filename' => $import['filename'],
                'row_count' => count($parsed['rows']),
            ]);
            foreach ($parsed['rows'] as $row) {
                AttendanceRecord::create([
                    'employee_id' => $row['employee_id'],
                    'import_batch_id' => $batch->id,
                    'shift_date' => $row['shift_date'],
                    'time_in_at' => $row['time_in_at'],
                    'time_out_at' => $row['time_out_at'],
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);
            }
        });

        Storage::disk('local')->delete($path);
        $request->session()->forget('attendance_import');

        return redirect()->route('timekeeping.attendance.index', ['date' => $parsed['rows'][0]['shift_date']])
            ->with('status', count($parsed['rows']).' biometric attendance records imported for '.$branch->name.'.');
    }

    /** @param array<string, mixed> $import
     *  @param array{rows: array<int, array<string, mixed>>, errors: array<int, string>}|null $parsed
     */
    private function previewView(Request $request, array $import, Branch $branch, ?array $parsed = null): View
    {
        $path = 'timekeeping/import-staging/'.$import['token'].'.csv';
        $parsed ??= Storage::disk('local')->exists($path)
            ? $this->parseFile(Storage::disk('local')->path($path), $branch->id)
            : ['rows' => [], 'errors' => ['The uploaded file expired. Please upload it again.']];

        return view('timekeeping.attendance.import-preview', [
            'branch' => $branch,
            'filename' => $import['filename'],
            'rows' => $parsed['rows'],
            'errors' => $parsed['errors'],
        ]);
    }

    /** @return array{rows: array<int, array<string, mixed>>, errors: array<int, string>} */
    private function parseFile(string $path, int $branchId): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return ['rows' => [], 'errors' => ['Could not read the uploaded CSV file.']];
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            return ['rows' => [], 'errors' => ['The CSV file is empty.']];
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(static fn ($header): string => strtolower(trim((string) $header)), $headers);
        if ($headers !== ['employee_number', 'shift_date', 'time_in', 'time_out']) {
            fclose($handle);
            return ['rows' => [], 'errors' => ['Use the sample CSV header exactly: employee_number, shift_date, time_in, time_out.']];
        }

        $rows = [];
        $errors = [];
        $seen = [];
        $line = 1;
        while (($values = fgetcsv($handle)) !== false) {
            $line++;
            if ($values === [null] || count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                continue;
            }
            if ($line > 501) {
                $errors[] = 'CSV files are limited to 500 attendance rows.';
                break;
            }
            if (count($values) !== 4) {
                $errors[] = "Row {$line}: expected exactly four columns.";
                continue;
            }

            [$employeeNumber, $date, $timeIn, $timeOut] = array_map(static fn ($value): string => trim((string) $value), $values);
            $employee = Employee::query()->active()->where('employee_number', $employeeNumber)
                ->where('branch_id', $branchId)->first();
            if ($employee === null) {
                $errors[] = "Row {$line}: employee number {$employeeNumber} is not active in this branch.";
                continue;
            }

            try {
                if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || (new DateTimeImmutable($date))->format('Y-m-d') !== $date) {
                    throw new \InvalidArgumentException;
                }
                $in = $this->localDateTime($date, $timeIn);
                $out = $this->localDateTime($date, $timeOut);
                if ($out->equalTo($in)) {
                    throw new \InvalidArgumentException;
                }
                if ($out->lessThan($in)) {
                    $out = $out->addDay();
                }
            } catch (\Throwable) {
                $errors[] = "Row {$line}: use a valid date (YYYY-MM-DD) and 24-hour times (HH:MM or HH:MM:SS); time out must differ from time in.";
                continue;
            }

            $key = $employee->id.':'.$date;
            if (isset($seen[$key]) || AttendanceRecord::query()->where('employee_id', $employee->id)->whereDate('shift_date', $date)->exists()) {
                $errors[] = "Row {$line}: attendance already exists for {$employeeNumber} on {$date}; duplicate rows are not imported.";
                continue;
            }
            $seen[$key] = true;
            $rows[] = [
                'employee_id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'employee_name' => $employee->displayName(),
                'shift_date' => $date,
                'time_in' => $in->format('g:i A'),
                'time_out' => $out->format('M j, g:i A'),
                'time_in_at' => $in->utc()->format('Y-m-d H:i:s'),
                'time_out_at' => $out->utc()->format('Y-m-d H:i:s'),
            ];
        }
        fclose($handle);

        return ['rows' => $rows, 'errors' => $errors];
    }

    private function localDateTime(string $date, string $time): CarbonImmutable
    {
        if (! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            throw new \InvalidArgumentException;
        }
        $format = strlen($time) === 5 ? '!Y-m-d H:i' : '!Y-m-d H:i:s';
        $dateTime = CarbonImmutable::createFromFormat($format, "{$date} {$time}", self::TIMEZONE);
        if ($dateTime === false || $dateTime->format(strlen($time) === 5 ? 'Y-m-d H:i' : 'Y-m-d H:i:s') !== "{$date} {$time}") {
            throw new \InvalidArgumentException;
        }

        return $dateTime;
    }

    private function branchForRequest(Request $request, mixed $branchId): Branch
    {
        $user = $request->user();
        $id = $user->isSuperAdmin() ? $branchId : $user->branch_id;
        abort_if(blank($id), 422, 'Choose a branch before downloading or importing attendance.');
        $branch = Branch::query()->active()->findOrFail($id);
        abort_unless($user->isSuperAdmin() || (int) $branch->id === (int) $user->branch_id, 403);

        return $branch;
    }
}
