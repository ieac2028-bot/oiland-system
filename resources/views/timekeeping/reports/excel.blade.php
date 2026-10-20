<?xml version="1.0" encoding="UTF-8"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
    <Worksheet ss:Name="Cutoff report">
        <Table>
            <Row><Cell><Data ss:Type="String">{{ config('app.name') }} — Attendance cutoff report</Data></Cell></Row>
            <Row><Cell><Data ss:Type="String">Period: {{ $start->format('M j, Y') }} – {{ $end->format('M j, Y') }} · Attendance summary only, not payroll calculations</Data></Cell></Row>
            <Row>
                @foreach (['Employee ID', 'Employee', 'Position', 'Branch', 'Department', 'Scheduled days', 'Present days', 'Absences', 'Late occurrences', 'Tardy minutes', 'Undertime minutes', 'Regular hours', 'Approved overtime hours', 'Approved leave days', 'Incomplete records'] as $heading)
                    <Cell><Data ss:Type="String">{{ $heading }}</Data></Cell>
                @endforeach
            </Row>
            @foreach ($rows as $row)
                @php($employee = $row['employee'])
                @php($summary = $row['summary'])
                <Row>
                    <Cell><Data ss:Type="String">{{ $employee->employee_number }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $employee->user->name }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $employee->job_title ?? '' }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $employee->user->branch?->name ?? '' }}</Data></Cell>
                    <Cell><Data ss:Type="String">{{ $employee->department ?? '' }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['scheduled_workdays'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['present_days'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['absences'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['late_occurrences'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['tardiness_minutes'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['undertime_minutes'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ number_format($summary['regular_hours'], 2, '.', '') }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ number_format($summary['approved_overtime_hours'], 2, '.', '') }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['approved_leave_days'] }}</Data></Cell>
                    <Cell><Data ss:Type="Number">{{ $summary['incomplete_records'] }}</Data></Cell>
                </Row>
            @endforeach
        </Table>
    </Worksheet>
</Workbook>
