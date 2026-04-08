<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; background: #fff; padding: 24px; }

        .badges { margin-bottom: 10px; }
        .badge { display: inline-block; padding: 5px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; color: #fff; letter-spacing: 0.3px; margin-right: 6px; }
        .badge-green  { background: #22c55e; }
        .badge-red    { background: #ef4444; }
        .badge-orange { background: #f97316; }
        .badge-purple { background: #7c3aed; }
        .badge-blue   { background: #2563eb; }

        .emp-name { font-size: 22px; font-weight: 700; color: #111; margin-bottom: 2px; }
        .emp-sub  { font-size: 12px; color: #555; margin-bottom: 10px; }

        .meta-row { font-size: 11px; color: #444; margin-bottom: 14px; border-top: 1px solid #ddd; border-bottom: 1px solid #ddd; padding: 6px 0; }
        .meta-row strong { color: #111; }

        .payslip-table { width: 100%; border-collapse: collapse; }
        .payslip-table thead th { background: #6c63ff; color: #fff; padding: 8px 10px; text-align: left; font-size: 11px; font-weight: 700; letter-spacing: 0.4px; }
        .payslip-table tbody td { padding: 7px 10px; border-bottom: 1px solid #f0f0f0; font-size: 11.5px; vertical-align: middle; }

        .lbl  { font-weight: 600; color: #333; width: 28%; }
        .amt  { color: #222; width: 22%; }
        .rlbl { font-weight: 600; color: #333; width: 28%; border-left: 1px solid #e5e7eb; }
        .rval { color: #222; width: 22%; }

        /* Rest day */
        .row-rest td          { background: #fff7ed; }
        .row-rest .lbl        { color: #c2410c; font-weight: 700; }
        .row-rest .amt        { color: #c2410c; font-weight: 700; }
        .row-rest .rlbl       { color: #c2410c; font-weight: 700; }
        .row-rest .rval       { color: #c2410c; font-weight: 700; }

        /* Holiday */
        .row-holiday td       { background: #fdf4ff; }
        .row-holiday .lbl     { color: #7e22ce; font-weight: 700; }
        .row-holiday .amt     { color: #7e22ce; font-weight: 700; }
        .row-holiday .rlbl    { color: #c2410c; font-weight: 700; border-left: 1px solid #e9d5ff; }
        .row-holiday .rval    { color: #c2410c; font-weight: 700; }

        /* Section banners */
        .row-yellow td { background: #fef9c3; font-weight: 700; font-size: 11px; color: #92400e; padding: 6px 10px; }
        .row-red    td { background: #fee2e2; font-weight: 700; font-size: 11px; color: #991b1b; padding: 6px 10px; }
        .row-green  td { background: #dcfce7; font-weight: 700; font-size: 11px; color: #166534; padding: 6px 10px; }

        /* Gross pay */
        .row-gross td { font-weight: 700; font-size: 13px; color: #16a34a; padding: 8px 10px; border-top: 2px solid #22c55e; }

        .divider { border-left: 1px solid #e5e7eb; }
        .italic-sm { color: #888; font-style: italic; font-weight: 400; font-size: 10px; }
    </style>
</head>
<body>

@php
    $hasRestDay   = isset($rest_day_hours)    && $rest_day_hours    != '0.00';
    $hasRestDayOT = isset($rest_day_ot_hours) && $rest_day_ot_hours != '0.00';
    $hasRegHol    = isset($reg_holiday_pay)   && $reg_holiday_pay   != '0.00';
    $hasSpecHol   = isset($spec_holiday_pay)  && $spec_holiday_pay  != '0.00';
    $regHolDays   = isset($reg_holiday_hours)  && $reg_holiday_hours  > 0 ? round($reg_holiday_hours  / 8, 2) : 0;
    $specHolDays  = isset($spec_holiday_hours) && $spec_holiday_hours > 0 ? round($spec_holiday_hours / 8, 2) : 0;
@endphp

{{-- Badges --}}
<div class="badges">
    <span class="badge badge-green">DAYS WORKED: {{ $days_worked }} DAY/S</span>
    @if($total_ot != '0.00')
        <span class="badge badge-red">OT: {{ $total_ot }} HRS</span>
    @endif
    @if($hasRestDay)
        <span class="badge badge-orange">REST DAYS: {{ $rest_days_worked }} DAY/S</span>
    @endif
    @if($hasRegHol)
        <span class="badge badge-purple">REG. HOLIDAY: {{ $regHolDays > 0 ? $regHolDays . ' DAY/S' : 'YES' }}</span>
    @endif
    @if($hasSpecHol)
        <span class="badge badge-blue">SPECIAL HOLIDAY: {{ $specHolDays > 0 ? $specHolDays . ' DAY/S' : 'YES' }}</span>
    @endif
</div>

{{-- Employee info --}}
<div class="emp-name">{{ strtoupper($employee->first_name . ' ' . $employee->last_name) }}</div>
<div class="emp-sub">{{ strtoupper($employee->position) }} | #{{ $employee->id }}</div>

{{-- Date / mode --}}
<div class="meta-row">
    <strong>DATE OF PAYMENT:</strong>
    {{ \Carbon\Carbon::parse($start)->format('m-d-Y') }} — {{ \Carbon\Carbon::parse($end)->format('m-d-Y') }}
    &nbsp;&nbsp;&nbsp;
    <strong>MODE OF PAYMENT:</strong> CASH / CHEQUE / BANK DEPOSIT
</div>

<table class="payslip-table">
    <thead>
        <tr>
            <th class="lbl">ITEM</th>
            <th class="amt">AMOUNT</th>
            <th class="rlbl" style="border-left:none;"></th>
            <th class="rval"></th>
        </tr>
    </thead>
    <tbody>

        {{-- Row 1: Allowance per day --}}
        <tr>
            <td class="lbl">ALLOWANCE PER DAY</td>
            <td class="amt">₱ {{ $allowance_per_day }}</td>
            <td class="rlbl">Other Additional Payments</td>
            <td class="rval">---</td>
        </tr>

        {{-- Row 2: Total Allowance --}}
        <tr>
            <td class="lbl">TOTAL ALLOWANCE</td>
            <td class="amt">₱ {{ $allowance_total }}</td>
            <td class="rlbl italic-sm">(Breakdown shown below)</td>
            <td class="rval"></td>
        </tr>

        {{-- Row 3: Basic Rate | NSD Hours --}}
        <tr>
            <td class="lbl">BASIC RATE PER DAY</td>
            <td class="amt">₱ {{ $daily_rate }}</td>
            <td class="rlbl">NSD RENDERED HOURS</td>
            <td class="rval">{{ $total_nsd != '0.00' ? $total_nsd . ' hrs' : '---' }}</td>
        </tr>

        {{-- Row 4: Total Basic Pay | NSD Pay --}}
        <tr>
            <td class="lbl">TOTAL BASIC PAY</td>
            <td class="amt">₱ {{ $basic_pay }}</td>
            <td class="rlbl">NSD PAY</td>
            <td class="rval">{{ $nsd_pay != '0.00' ? '₱ ' . $nsd_pay : '---' }}</td>
        </tr>

        {{-- Row 5: OT Rate | Rest Day Rate --}}
        <tr>
            <td class="lbl">OVERTIME RATE PER HOUR</td>
            <td class="amt">₱ {{ $overtime_rate_per_hour }}</td>
            <td class="rlbl">REST DAY RATE PER HOUR <span class="italic-sm">(169%)</span></td>
            <td class="rval">{{ $hasRestDay ? '₱ ' . $rest_day_rate_per_hour : '---' }}</td>
        </tr>

        {{-- Row 6: Total OT Pay | Rest Day OT Rate --}}
        <tr>
            <td class="lbl">TOTAL OVERTIME PAY</td>
            <td class="amt">₱ {{ $total_overtime_pay }}</td>
            <td class="rlbl">REST DAY OT RATE PER HOUR</td>
            <td class="rval">{{ $hasRestDayOT ? '₱ ' . $rest_day_ot_rate : '---' }}</td>
        </tr>

        {{-- Row 7: Rest Day Hours | Rest Day Pay --}}
        <tr class="{{ $hasRestDay ? 'row-rest' : '' }}">
            <td class="lbl">REST DAY HOURS RENDERED</td>
            <td class="amt">{{ $hasRestDay ? $rest_day_hours . ' hrs' : '---' }}</td>
            <td class="rlbl">REST DAY PAY (169%)</td>
            <td class="rval">{{ $hasRestDay ? '₱ ' . $rest_day_pay : '---' }}</td>
        </tr>

        {{-- Row 8: Rest Day OT Hours | Rest Day OT Pay --}}
        <tr class="{{ $hasRestDayOT ? 'row-rest' : '' }}">
            <td class="lbl">REST DAY OT HOURS</td>
            <td class="amt">{{ $hasRestDayOT ? $rest_day_ot_hours . ' hrs' : '---' }}</td>
            <td class="rlbl">REST DAY OT PAY</td>
            <td class="rval">{{ $hasRestDayOT ? '₱ ' . $rest_day_ot_pay : '---' }}</td>
        </tr>

        {{-- Row 9: Regular Holiday | Special Holiday (DATA ROW with actual values) --}}
        <tr class="{{ ($hasRegHol || $hasSpecHol) ? 'row-holiday' : '' }}">
            <td class="lbl">REGULAR HOLIDAY PAY (x2.0)</td>
            <td class="amt">{{ $hasRegHol ? '₱ ' . $reg_holiday_pay : '---' }}</td>
            <td class="rlbl">SPECIAL HOLIDAY PAY (x1.30)</td>
            <td class="rval">{{ $hasSpecHol ? '₱ ' . $spec_holiday_pay : '---' }}</td>
        </tr>

        {{-- Section: Deductions --}}
        <tr class="row-yellow">
            <td colspan="2">TOTAL DEDUCTION</td>
            <td colspan="2" style="background:#fee2e2;color:#991b1b;border-left:1px solid #fecaca;">EMPLOYEES STATUTORIES SHOWN BELOW</td>
        </tr>

        {{-- SSS | Net Pay --}}
        <tr>
            <td class="lbl">SSS</td>
            <td class="amt">{{ $sss != '0.00' ? '₱ ' . $sss : '---' }}</td>
            <td colspan="2" class="divider">
                <table width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td style="font-weight:700;font-size:11px;color:#166534;background:#dcfce7;padding:6px 10px;width:55%;">NET PAY (GRAND TOTAL)</td>
                        <td style="font-weight:700;font-size:11px;color:#166534;background:#dcfce7;padding:6px 10px;">₱ {{ $grand_total }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Phil-Health | Employer CRF --}}
        <tr>
            <td class="lbl">Phil-Health</td>
            <td class="amt">{{ $philhealth != '0.00' ? '₱ ' . $philhealth : '---' }}</td>
            <td colspan="2" class="divider">
                <table width="100%" style="border-collapse:collapse;">
                    <tr>
                        <td colspan="2" style="font-weight:700;font-size:11px;color:#065f46;background:#d1fae5;padding:6px 10px;">EMPLOYERS CRF CONTRIBUTION</td>
                    </tr>
                </table>
            </td>
        </tr>

        {{-- Pag-IBIG | Signature name --}}
        <tr>
            <td class="lbl">Pag-ibig</td>
            <td class="amt">{{ $pagibig != '0.00' ? '₱ ' . $pagibig : '---' }}</td>
            <td class="rlbl" style="text-align:center;font-weight:700;">
                {{ strtoupper($employee->first_name . ' ' . $employee->last_name) }}
            </td>
            <td class="rval"></td>
        </tr>

        {{-- Cash Advance | Signature label --}}
        <tr>
            <td class="lbl">Cash-Advanced</td>
            <td class="amt">{{ $cash_advance != '0.00' ? '₱ ' . $cash_advance : '---' }}</td>
            <td class="rlbl" style="text-align:center;font-size:10px;color:#888;font-weight:400;">
                SIGNATURE OVER PRINTED NAME<br>(EMPLOYEE'S ACKNOWLEDGMENT)
            </td>
            <td class="rval"></td>
        </tr>

        {{-- Gross Pay --}}
        <tr class="row-gross">
            <td colspan="2">GROSS PAY &nbsp;&nbsp; ₱ {{ $gross_pay }}</td>
            <td colspan="2" class="divider"></td>
        </tr>

    </tbody>
</table>

</body>
</html>