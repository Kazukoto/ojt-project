@forelse($users as $user)
<tr id="user-row-{{ $user->id }}" class="clickable-row" data-user='@json($user)'>
    <td>{{ $user->id }}</td>
    <td>{{ $user->first_name }} {{ $user->middle_name ? $user->middle_name . ' ' : '' }}{{ $user->last_name }}</td>
    <td>{{ $user->position }}</td>
    <td>{{ $user->username ?? 'N/A' }}</td>
    <td>
        @php
            $employee = \App\Models\Employee::where('username', $user->username)->first();
            $archived = \App\Models\Archive::where('username', $user->username)->latest()->first();
            if ($employee) {
                $status = $employee->status ?? 'active';
            } elseif ($archived) {
                $status = $archived->status;
            } else {
                $status = 'active';
            }
            $statusColor = $status === 'active' ? '#198754' : ($status === 'terminated' ? '#dc3545' : '#6c757d');
        @endphp
        <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;color:white;background:{{ $statusColor }};">
            {{ ucfirst($status) }}
        </span>
    </td>
    <td onclick="event.stopPropagation();" style="white-space:nowrap;">
        <button onclick="openViewModal({{ $user->id }})"
            style="background:#6366f1;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;margin-right:4px;">
            👁 View
        </button>
        <button onclick="openEditModal({{ $user->id }})"
            style="background:#0ea5e9;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;margin-right:4px;">
            ✏️ Edit
        </button>
        <button onclick="openArchiveModal({{ $user->id }}, '{{ $user->first_name }} {{ $user->last_name }}')"
            style="background:#dc3545;color:#fff;border:none;border-radius:6px;padding:4px 10px;font-size:12px;cursor:pointer;font-weight:600;">
            🚫 Status
        </button>
    </td>
</tr>
@empty
<tr id="empty-row">
    <td colspan="7" class="text-center">No records exist</td>
</tr>
@endforelse