<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 24px; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
        }

        header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        h1 { font-size: 15px; margin: 0 0 3px; }

        .meta { color: #475569; font-size: 9px; }

        .meta span { margin-right: 12px; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
        }

        thead th {
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 6px 7px;
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #475569;
        }

        tbody td {
            border-bottom: 1px solid #e2e8f0;
            padding: 5px 7px;
            vertical-align: top;
        }

        .num { text-align: right; white-space: nowrap; }

        tfoot td {
            border-top: 2px solid #0f172a;
            border-bottom: none;
            padding: 7px;
            font-weight: bold;
            background: #f8fafc;
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: #64748b;
            border: 1px dashed #cbd5e1;
        }

        footer {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            color: #64748b;
            font-size: 8.5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>{{ $title }}</h1>
            <div class="meta">
                @if ($scopeLabel)
                    <span><strong>Scope:</strong> {{ $scopeLabel }}</span>
                @endif
                @if ($period)
                    <span><strong>Period:</strong> {{ $period }}</span>
                @endif
            </div>
        </div>
        <div class="meta" style="text-align: right">
            <span>{{ config('app.name') }}</span><br>
            <span>Generated {{ $generatedAt }}</span>
        </div>
    </header>

    @if (count($rows) > 0)
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Branch</th>
                    <th>Item</th>
                    <th class="num">Quantity</th>
                    <th class="num">Unit price</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row[0] }}</td>
                        <td>{{ $row[1] }}</td>
                        <td>{{ $row[2] }}</td>
                        <td class="num">{{ $row[3] }}</td>
                        <td class="num">{{ $row[4] }}</td>
                        <td class="num">{{ $row[5] }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">{{ count($rows) }} line(s)</td>
                    <td class="num">{{ $totals['quantity'] }}</td>
                    <td></td>
                    <td class="num">{{ $totals['revenue'] }}</td>
                </tr>
            </tfoot>
        </table>
    @else
        <div class="empty">No sales matched the selected filters.</div>
    @endif

    <footer>
        {{ config('app.name') }} · Confidential internal document · All amounts in {{ config('app.currency') }}
    </footer>
</body>
</html>
