@forelse($employees as $employee)
<tr>
    <td>{{ $employee->id}}</td>
    <td>
        <div class="employee-info">
            <div class="employee-avatar"></div>
            <span>
                {{ $employee->last_name }},
                {{ $employee->first_name }},
                {{ $employee->middle_name }},
                {{ $employee->suffixes }}
            </span>
        </div>
    </td>
    <td>{{ $employee->gender }}</td>
    <td>{{ $employee->position }}</td>
    <td>{{ $employee->created_at->format('M d, Y | H:i s a') }}</td>
</tr>
@empty
<tr>
    <td colspan="5" style="text-align:center;">No employees found</td>
</tr>
@endforelse
