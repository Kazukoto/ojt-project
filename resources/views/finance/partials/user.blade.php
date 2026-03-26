@forelse($users as $user)
<tr class="clickable-row" data-user='@json($user)'>
    <td>{{ $user->id }}</td>
    <td>{{ $user->first_name }} {{ $user->last_name }}</td>
    <td>{{ $user->position }}</td>
    <td>{{ $user->morning_status ?? 'N/A' }}</td>
    <td>{{ $user->afternoon_status ?? 'N/A' }}</td>
    <td>{{ $user->ot_hours ?? 'N/A' }}</td>
</tr>
@empty
<tr>
    <td colspan="6" class="text-center">No records exist</td>
</tr>
@endforelse
