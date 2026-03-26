<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            background: #fff;
            padding: 20px;
        }

        /* ── Report Header ── */
        .report-header {
            text-align: center;
            margin-bottom: 16px;
            border-bottom: 2px solid #6c63ff;
            padding-bottom: 10px;
        }
        .report-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.5px;
        }
        .report-period {
            font-size: 11px;
            color: #6c63ff;
            font-weight: 600;
            margin-top: 3px;
        }
        .report-generated {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 2px;
        }

        /* ── Summary Stats ── */
        .summary-bar {
            display: table;
            width: 100%;
            margin-bottom: 14px;
        }
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 8px 6px;
            background: #f8f7ff;
            border: 1px solid #e0e0ff;
            border-radius: 6px;
        }
        .summary-item .s-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .summary-item .s-value {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 2px;
        }
        .summary-item .s-value.green { color: #16a34a; }

        /* ── Main Table ── */
        .payroll-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5px;
        }

        .payroll-table thead tr {
            background: #6c63ff;
            color: #fff;
        }
        .payroll-table thead th {
            padding: 8px 6px;
            text-align: center;
            font-weight: 700;
            font-size: 9px;
            letter-spacing: 0.3px;
            border: 1px solid #5a52d5;
        }
        .payroll-table thead th.left { text-align: left; }

        /* Sub-header row */
        .payroll-table .subheader td {
            background: #ede9fe;
            color: #4f46e5;
            font-weight: 700;
            font-size: 8.5px;
            text-align: center;
            padding: 4px 6px;
            border: 1px solid #ddd6fe;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .payroll-table tbody td {
            padding: 6px 6px;
            border: 1px solid #f0f0f0;
            vertical-align: middle;
        }
        .payroll-table tbody tr:nth-child(even) td { background: #fafafa; }
        .payroll-table tbody tr:hover td { background: #f0efff; }

        .name-cell { font-weight: 700; color: #1e293b; font-size: 9.5px; }
        .pos-cell  { color: #6b7280; font-size: 8.5px; }
        .num-cell  { text-align: right; color: #374151; }
        .zero-cell { text-align: right; color: #d1d5db; }

        /* Highlighted cells */
        .gross-cell {
            text-align: right;
            font-weight: 700;
            color: #16a34a;
            background: #f0fdf4 !important;
        }
        .grandtotal-cell {
            text-align: right;
            font-weight: 700;
            color: #1e293b;
        }
        .deduct-cell {
            text-align: right;
            color: #dc2626;
        }

        /* ── Total row ── */
        .total-row td {
            background: #6c63ff !important;
            color: #fff !important;
            font-weight: 700;
            font-size: 9.5px;
            padding: 7px 6px;
            border: 1px solid #5a52d5;
        }
        .total-row .num-cell,
        .total-row .gross-cell,
        .total-row .grandtotal-cell,
        .total-row .deduct-cell {
            color: #fff !important;
            background: #6c63ff !important;
            text-align: right;
        }

        /* ── Footer ── */
        .report-footer {
            margin-top: 20px;
            display: table;
            width: 100%;
        }
        .sig-block {
            display: table-cell;
            width: 33%;
            text-align: center;
            padding: 0 10px;
        }
        .sig-line {
            border-top: 1px solid #333;
            margin-top: 30px;
            padding-top: 4px;
            font-size: 9px;
            font-weight: 700;
            color: #1e293b;
        }
        .sig-label {
            font-size: 8px;
            color: #9ca3af;
            margin-top: 2px;
        }
    </style>
</head>
<body>

    {{-- ── Report Header ── --}}
    <div class="report-header">
        <div class="report-title">💸 PAYROLL SUMMARY REPORT</div>
        <div class="report-period">
            Period: {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
        </div>
        <div class="report-generated">Generated: {{ now()->format('F d, Y h:i A') }}</div>
    </div>

    {{-- ── Summary Stats ── --}}
    @php
        $totalEmployees  = $employees->count();
        $totalGross      = $employees->sum(fn($e) => (float) str_replace(',', '', $e->gross_pay   ?? 0));
        $totalGrandTotal = $employees->sum(fn($e) => (float) str_replace(',', '', $e->grand_total ?? 0));
        $totalCA         = $employees->sum(fn($e) => (float) str_replace(',', '', $e->cash_advance?? 0));
        $totalBasic      = $employees->sum(fn($e) => (float) str_replace(',', '', $e->basic_total ?? 0));
        $totalOT         = $employees->sum(fn($e) => (float) str_replace(',', '', $e->ot_total    ?? 0));
        $totalAllowance  = $employees->sum(fn($e) => (float) str_replace(',', '', $e->allowance_total ?? 0));
        $totalRestDay    = $employees->sum(fn($e) => (float) str_replace(',', '', $e->rest_day    ?? 0));
    @endphp

    <table style="width:100%;border-collapse:separate;border-spacing:4px;margin-bottom:14px;">
        <tr>
            <td style="background:#ede9fe;border-radius:6px;padding:8px 10px;text-align:center;border:1px solid #ddd6fe;">
                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;">Total Employees</div>
                <div style="font-size:15px;font-weight:700;color:#4f46e5;">{{ $totalEmployees }}</div>
            </td>
            <td style="background:#f0fdf4;border-radius:6px;padding:8px 10px;text-align:center;border:1px solid #bbf7d0;">
                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;">Total Gross Pay</div>
                <div style="font-size:15px;font-weight:700;color:#16a34a;">&#8369;{{ number_format($totalGross, 2) }}</div>
            </td>
            <td style="background:#fff7ed;border-radius:6px;padding:8px 10px;text-align:center;border:1px solid #fed7aa;">
                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;">Total Basic Pay</div>
                <div style="font-size:15px;font-weight:700;color:#c2410c;">&#8369;{{ number_format($totalBasic, 2) }}</div>
            </td>
            <td style="background:#fef3c7;border-radius:6px;padding:8px 10px;text-align:center;border:1px solid #fde68a;">
                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;">Total Allowance</div>
                <div style="font-size:15px;font-weight:700;color:#92400e;">&#8369;{{ number_format($totalAllowance, 2) }}</div>
            </td>
            <td style="background:#fef2f2;border-radius:6px;padding:8px 10px;text-align:center;border:1px solid #fecaca;">
                <div style="font-size:9px;color:#6b7280;text-transform:uppercase;">Total Cash Advance</div>
                <div style="font-size:15px;font-weight:700;color:#dc2626;">&#8369;{{ number_format($totalCA, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- ── Main Payroll Table ── --}}
    <table class="payroll-table">
        <thead>
            <tr>
                <th class="left" rowspan="2" style="width:14%;">Employee</th>
                <th rowspan="2" style="width:5%;">Days</th>
                {{-- Earnings group --}}
                <th colspan="5" style="background:#4f46e5;">EARNINGS</th>
                {{-- Rest Day --}}
                <th colspan="2" style="background:#c2410c;">REST DAY</th>
                {{-- Holiday --}}
                <th colspan="2" style="background:#0369a1;">HOLIDAY</th>
                {{-- Deductions --}}
                <th colspan="4" style="background:#dc2626;">DEDUCTIONS</th>
                {{-- Totals --}}
                <th rowspan="2" style="width:8%;background:#166534;">GRAND TOTAL</th>
                <th rowspan="2" style="width:8%;background:#15803d;">GROSS PAY</th>
            </tr>
            <tr>
                {{-- Earnings sub-headers --}}
                <th style="background:#6366f1;font-size:8px;">ALLOWANCE</th>
                <th style="background:#6366f1;font-size:8px;">BASIC PAY</th>
                <th style="background:#6366f1;font-size:8px;">OT PAY</th>
                <th style="background:#6366f1;font-size:8px;">NSD PAY</th>
                <th style="background:#6366f1;font-size:8px;">OT 25%</th>
                {{-- Rest Day sub-headers --}}
                <th style="background:#ea580c;font-size:8px;">REG</th>
                <th style="background:#ea580c;font-size:8px;">OT</th>
                {{-- Holiday sub-headers --}}
                <th style="background:#0284c7;font-size:8px;">REGULAR</th>
                <th style="background:#0284c7;font-size:8px;">SPECIAL</th>
                {{-- Deductions sub-headers --}}
                <th style="background:#ef4444;font-size:8px;">SSS</th>
                <th style="background:#ef4444;font-size:8px;">PHIC</th>
                <th style="background:#ef4444;font-size:8px;">HDMF</th>
                <th style="background:#ef4444;font-size:8px;">CASH ADV</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $emp)
            @php
                $isZero = fn($v) => !$v || $v == '0.00' || $v == '—';
            @endphp
            <tr>
                {{-- Name + Position --}}
                <td>
                    <div class="name-cell">{{ $emp->last_name }}, {{ $emp->first_name }}</div>
                    <div class="pos-cell">{{ $emp->position }}</div>
                </td>
                {{-- Days --}}
                <td class="{{ $isZero($emp->days ?? null) ? 'zero-cell' : 'num-cell' }}">
                    {{ $emp->days ?? '—' }}
                </td>
                {{-- Earnings --}}
                <td class="{{ $isZero($emp->allowance_total ?? null) ? 'zero-cell' : 'num-cell' }}">
                    {{ $emp->allowance_total ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->basic_total ?? null) ? 'zero-cell' : 'num-cell' }}">
                    {{ $emp->basic_total ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->ot_total ?? null) ? 'zero-cell' : 'num-cell' }}">
                    {{ $emp->ot_total ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->nsd_110 ?? null) ? 'zero-cell' : 'num-cell' }}">
                    {{ $emp->nsd_110 ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->ot_25 ?? null) ? 'zero-cell' : 'num-cell' }}">
                    {{ $emp->ot_25 ?? '—' }}
                </td>
                {{-- Rest Day --}}
                <td class="{{ $isZero($emp->rest_day ?? null) ? 'zero-cell' : 'num-cell' }}" style="{{ !$isZero($emp->rest_day ?? null) ? 'background:#fff7ed;color:#c2410c;font-weight:600;' : '' }}">
                    {{ $emp->rest_day ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->rest_ot ?? null) ? 'zero-cell' : 'num-cell' }}" style="{{ !$isZero($emp->rest_ot ?? null) ? 'background:#fff7ed;color:#c2410c;font-weight:600;' : '' }}">
                    {{ $emp->rest_ot ?? '—' }}
                </td>
                {{-- Holiday --}}
                <td class="{{ $isZero($emp->reg_holiday ?? null) ? 'zero-cell' : 'num-cell' }}" style="{{ !$isZero($emp->reg_holiday ?? null) ? 'background:#eff6ff;color:#1d4ed8;font-weight:600;' : '' }}">
                    {{ $emp->reg_holiday ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->special_holiday ?? null) ? 'zero-cell' : 'num-cell' }}" style="{{ !$isZero($emp->special_holiday ?? null) ? 'background:#eff6ff;color:#1d4ed8;font-weight:600;' : '' }}">
                    {{ $emp->special_holiday ?? '—' }}
                </td>
                {{-- Deductions --}}
                <td class="{{ $isZero($emp->sss ?? null) ? 'zero-cell' : 'deduct-cell' }}">
                    {{ $emp->sss ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->philhealth ?? null) ? 'zero-cell' : 'deduct-cell' }}">
                    {{ $emp->philhealth ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->pagibig ?? null) ? 'zero-cell' : 'deduct-cell' }}">
                    {{ $emp->pagibig ?? '—' }}
                </td>
                <td class="{{ $isZero($emp->cash_advance ?? null) ? 'zero-cell' : 'deduct-cell' }}">
                    {{ $emp->cash_advance ?? '—' }}
                </td>
                {{-- Totals --}}
                <td class="grandtotal-cell">{{ $emp->grand_total ?? '—' }}</td>
                <td class="gross-cell">&#8369; {{ $emp->gross_pay ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="18" style="text-align:center;padding:20px;color:#9ca3af;">No payroll records found.</td>
            </tr>
            @endforelse

            {{-- Totals Row --}}
            <tr class="total-row">
                <td style="text-align:left;font-size:10px;">TOTAL ({{ $totalEmployees }} employees)</td>
                <td></td>
                <td class="num-cell">{{ number_format($totalAllowance, 2) }}</td>
                <td class="num-cell">{{ number_format($totalBasic, 2) }}</td>
                <td class="num-cell">{{ number_format($totalOT, 2) }}</td>
                <td></td>
                <td></td>
                <td class="num-cell">{{ number_format($totalRestDay, 2) }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td class="num-cell">{{ number_format($totalCA, 2) }}</td>
                <td class="num-cell">{{ number_format($totalGrandTotal, 2) }}</td>
                <td class="num-cell">&#8369; {{ number_format($totalGross, 2) }}</td>
            </tr>
        </tbody>
    </table>

    {{-- ── Signature Block ── --}}
    <table style="width:100%;margin-top:30px;border-collapse:collapse;">
        <tr>
            <td style="width:33%;text-align:center;padding:0 15px;">
                <div style="border-top:1px solid #333;margin-top:30px;padding-top:5px;font-size:9px;font-weight:700;">PREPARED BY</div>
                <div style="font-size:8px;color:#9ca3af;">Signature over Printed Name</div>
            </td>
            <td style="width:33%;text-align:center;padding:0 15px;">
                <div style="border-top:1px solid #333;margin-top:30px;padding-top:5px;font-size:9px;font-weight:700;">CHECKED BY</div>
                <div style="font-size:8px;color:#9ca3af;">Signature over Printed Name</div>
            </td>
            <td style="width:33%;text-align:center;padding:0 15px;">
                <div style="border-top:1px solid #333;margin-top:30px;padding-top:5px;font-size:9px;font-weight:700;">APPROVED BY</div>
                <div style="font-size:8px;color:#9ca3af;">Signature over Printed Name</div>
            </td>
        </tr>
    </table>

</body>
</html>