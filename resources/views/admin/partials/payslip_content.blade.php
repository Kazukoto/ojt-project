@if($payslipData)
@php
    $hasRestDay   = isset($payslipData['rest_day_pay'])     && $payslipData['rest_day_pay']     !== '0.00';
    $hasRestDayOT = isset($payslipData['rest_day_ot_pay'])  && $payslipData['rest_day_ot_pay']  !== '0.00';
    $hasRegHol    = isset($payslipData['reg_holiday_pay'])  && $payslipData['reg_holiday_pay']  !== '0.00';
    $hasSpecHol   = isset($payslipData['spec_holiday_pay']) && $payslipData['spec_holiday_pay'] !== '0.00';
    $regHolDays   = isset($payslipData['reg_holiday_hours'])  && $payslipData['reg_holiday_hours']  > 0 ? round($payslipData['reg_holiday_hours']  / 8, 2) : 0;
    $specHolDays  = isset($payslipData['spec_holiday_hours']) && $payslipData['spec_holiday_hours'] > 0 ? round($payslipData['spec_holiday_hours'] / 8, 2) : 0;
@endphp

<div class="export-bar">
    <a href="{{ route('superadmin.payslip.export', [
        'employee_id' => $payslipData['employee_no'],
        'start_date'  => $payslipData['start_date'],
        'end_date'    => $payslipData['end_date'],
    ]) }}" class="btn-export" target="_blank">📄 Export PDF</a>
</div>

<div class="payslip-card">
    <div class="slip-header">
        <div class="slip-name">{{ strtoupper($payslipData['employee_name']) }}</div>
        <div class="slip-role">{{ strtoupper($payslipData['position']) }} &nbsp;|&nbsp; #{{ $payslipData['employee_no'] }}</div>
        <div class="slip-badges">
            <span class="slip-badge badge-green">DAYS WORKED: {{ $payslipData['days_worked'] }} DAY/S</span>
            @if($payslipData['total_ot'] !== '0.00')
                <span class="slip-badge badge-red">OT: {{ $payslipData['total_ot'] }} HRS</span>
            @endif
            @if($hasRestDay)
                <span class="slip-badge badge-orange">REST DAYS: {{ $payslipData['rest_days_worked'] }} DAY/S</span>
            @endif
            @if($hasRegHol)
                <span class="slip-badge badge-purple">REG. HOLIDAY: {{ $regHolDays > 0 ? $regHolDays . ' DAY/S' : 'YES' }}</span>
            @endif
            @if($hasSpecHol)
                <span class="slip-badge badge-blue">SPECIAL HOLIDAY: {{ $specHolDays > 0 ? $specHolDays . ' DAY/S' : 'YES' }}</span>
            @endif
        </div>
    </div>

    <div class="slip-period">
        <div>PERIOD: <strong>{{ date('M d, Y', strtotime($payslipData['start_date'])) }} — {{ date('M d, Y', strtotime($payslipData['end_date'])) }}</strong></div>
        <div>MODE: <strong>CASH / CHEQUE / BANK DEPOSIT</strong></div>
    </div>

    <table class="slip-table">
        <thead>
            <tr>
                <th class="td-label">ITEM</th>
                <th class="td-value">AMOUNT</th>
                <th class="td-label2">ITEM</th>
                <th class="td-value2">AMOUNT</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="td-label">ALLOWANCE PER DAY</td>
                <td class="td-value">₱ {{ $payslipData['allowance_per_day'] }}</td>
                <td class="td-label2">Other Additional Payments</td>
                <td class="td-value2">—</td>
            </tr>
            <tr>
                <td class="td-label">TOTAL ALLOWANCE</td>
                <td class="td-value">₱ {{ $payslipData['total_allowance'] }}</td>
                <td class="td-label2 td-sm">(Breakdown shown below)</td>
                <td class="td-value2"></td>
            </tr>
            <tr>
                <td class="td-label">BASIC RATE PER DAY</td>
                <td class="td-value">₱ {{ $payslipData['daily_rate'] }}</td>
                <td class="td-label2">NSD RENDERED HOURS</td>
                <td class="td-value2">{{ $payslipData['total_nsd'] != '0.00' ? $payslipData['total_nsd'] . ' hrs' : '—' }}</td>
            </tr>
            <tr>
                <td class="td-label">TOTAL BASIC PAY</td>
                <td class="td-value">₱ {{ $payslipData['basic_pay'] }}</td>
                <td class="td-label2">NSD PAY</td>
                <td class="td-value2">{{ $payslipData['nsd_pay'] != '0.00' ? '₱ ' . $payslipData['nsd_pay'] : '—' }}</td>
            </tr>
            <tr>
                <td class="td-label">OVERTIME RATE PER HOUR</td>
                <td class="td-value">₱ {{ $payslipData['overtime_rate_per_hour'] }}</td>
                <td class="td-label2">REST DAY RATE PER HOUR <span style="font-size:10px;color:#94a3b8;font-weight:400;">(169%)</span></td>
                <td class="td-value2">{{ $hasRestDay ? '₱ ' . $payslipData['rest_day_rate_per_hour'] : '—' }}</td>
            </tr>
            <tr>
                <td class="td-label">TOTAL OVERTIME PAY</td>
                <td class="td-value">₱ {{ $payslipData['total_overtime_pay'] }}</td>
                <td class="td-label2">REST DAY OT RATE PER HOUR</td>
                <td class="td-value2">{{ $hasRestDayOT ? '₱ ' . $payslipData['rest_day_ot_rate'] : '—' }}</td>
            </tr>
            <tr class="{{ $hasRestDay ? 'row-restday' : '' }}">
                <td class="td-label">REST DAY HOURS RENDERED</td>
                <td class="td-value">{{ $hasRestDay ? ($payslipData['rest_day_hours'] . ' hrs') : '—' }}</td>
                <td class="td-label2">REST DAY PAY</td>
                <td class="td-value2">{{ $hasRestDay ? '₱ ' . $payslipData['rest_day_pay'] : '—' }}</td>
            </tr>
            <tr class="{{ $hasRestDayOT ? 'row-restday' : '' }}">
                <td class="td-label">REST DAY OT HOURS</td>
                <td class="td-value">{{ $hasRestDayOT ? ($payslipData['rest_day_ot_hours'] . ' hrs') : '—' }}</td>
                <td class="td-label2">REST DAY OT PAY</td>
                <td class="td-value2">{{ $hasRestDayOT ? '₱ ' . $payslipData['rest_day_ot_pay'] : '—' }}</td>
            </tr>
            <tr class="{{ ($hasRegHol || $hasSpecHol) ? 'row-holiday' : '' }}">
                <td class="td-label">REGULAR HOLIDAY PAY (200%)</td>
                <td class="td-value">{{ $hasRegHol ? '₱ ' . $payslipData['reg_holiday_pay'] : '—' }}</td>
                <td class="td-label2">SPECIAL HOLIDAY PAY (130%)</td>
                <td class="td-value2">{{ $hasSpecHol ? '₱ ' . $payslipData['spec_holiday_pay'] : '—' }}</td>
            </tr>

            {{-- ✅ TOTAL DEDUCTIONS now shows the value --}}
            <tr class="row-section-yellow">
                <td style="background:#fef9c3;color:#854d0e;font-weight:700;">TOTAL DEDUCTIONS</td>
                <td style="background:#fef9c3;color:#991b1b;font-weight:700;">₱ {{ $payslipData['total_deductions'] }}</td>
                <td colspan="2" style="background:#fee2e2;color:#991b1b;">EMPLOYEES STATUTORIES</td>
            </tr>

            <tr>
                <td class="td-label">SSS</td>
                <td class="td-value">{{ $payslipData['sss'] != '0.00' ? '₱ ' . $payslipData['sss'] : '—' }}</td>
                <td class="td-label2">NET PAY (GRAND TOTAL)</td>
                <td class="td-value2" style="font-weight:700;color:#166534;">₱ {{ $payslipData['grand_total'] }}</td>
            </tr>
            <tr>
                <td class="td-label">Phil-Health</td>
                <td class="td-value">{{ $payslipData['philhealth'] != '0.00' ? '₱ ' . $payslipData['philhealth'] : '—' }}</td>
                <td colspan="2" style="padding:0;border-left:1px solid #f0f0f0;">
                    <span class="highlight-green">EMPLOYER'S CRF CONTRIBUTION</span>
                </td>
            </tr>
            <tr>
                <td class="td-label">Pag-IBIG</td>
                <td class="td-value">{{ $payslipData['pagibig'] != '0.00' ? '₱ ' . $payslipData['pagibig'] : '—' }}</td>
                <td class="sig-cell" colspan="2" rowspan="2">
                    <strong>{{ strtoupper($payslipData['employee_name']) }}</strong>
                    <small>SIGNATURE OVER PRINTED NAME<br>(EMPLOYEE'S ACKNOWLEDGMENT)</small>
                </td>
            </tr>
            <tr>
                <td class="td-label">Cash Advance</td>
                <td class="td-value">{{ $payslipData['cash_advance'] != '0.00' ? '₱ ' . $payslipData['cash_advance'] : '—' }}</td>
            </tr>
            <tr class="row-gross">
                <td colspan="2">GROSS PAY &nbsp;&nbsp; ₱ {{ $payslipData['gross_pay'] }}</td>
                <td colspan="2" style="border-left:1px solid #bbf7d0;border-top:2px solid #22c55e;"></td>
            </tr>
        </tbody>
    </table>
</div>

@else
<div class="empty-state">
    <div class="empty-icon">📋</div>
    <p>Select an employee from the left to view their payslip</p>
</div>
@endif