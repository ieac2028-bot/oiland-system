<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Attendance cutoff report</title>
    <style>
        body { font-family: Arial, sans-serif; color: #1e293b; font-size: 10pt; }
        h1 { font-size: 18pt; margin-bottom: 4px; }
        p { color: #475569; margin: 0 0 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        th { background: #e2e8f0; }
    </style>
</head>
<body>
    <h1>{{ config('app.name') }} · Attendance cutoff report</h1>
    <p>{{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }} · Attendance summary only, not payroll calculations</p>
    <table>
        <thead><tr><th>Employee ID</th><th>Employee</th><th>Position</th><th>Branch / department</th><th>Workdays</th><th>Present</th><th>Absent</th><th>Late</th><th>Tardy min</th><th>Undertime min</th><th>Regular hrs</th><th>Approved OT hrs</th><th>Leave</th><th>Incomplete</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                @php($employee = $row['employee'])
                @php($summary = $row['summary'])
                <tr>
                    <td>{{ $employee->employee_number }}</td><td>{{ $employee->user->name }}</td><td>{{ $employee->job_title ?? '—' }}</td>
                    <td>{{ $employee->user->branch?->name ?? '—' }} / {{ $employee->department ?? '—' }}</td>
                    <td>{{ $summary['scheduled_workdays'] }}</td><td>{{ $summary['present_days'] }}</td><td>{{ $summary['absences'] }}</td>
                    <td>{{ $summary['late_occurrences'] }}</td><td>{{ $summary['tardiness_minutes'] }}</td><td>{{ $summary['undertime_minutes'] }}</td>
                    <td>{{ number_format($summary['regular_hours'], 2) }}</td><td>{{ number_format($summary['approved_overtime_hours'], 2) }}</td>
                    <td>{{ $summary['approved_leave_days'] }}</td><td>{{ $summary['incomplete_records'] }}</td>
                </tr>
            @empty
                <tr><td colspan="14">No employees matched this report.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
