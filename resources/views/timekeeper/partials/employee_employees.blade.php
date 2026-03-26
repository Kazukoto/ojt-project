<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .edit-btn, 
    .delete-btn 
    {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        margin-right: 8px;
    }

    .edit-btn {
    background: #3b82f6;
    color: white;
    }

    .edit-btn:hover {
        background: #2563eb;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .delete-btn {
        background: #ef4444;
        color: white;
    }

    .delete-btn:hover {
        background: #dc2626;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

</style>
<title>Cash Advances</title>
<table>
            <thead>
            </thead>
            <tbody>
            @forelse($employees as $employee)
            <tr class="clickable-row"
                data-user='@json($employee)'>
                <td>{{ $employee->id}}</td>
                <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                <td>{{ $employee->position }}</td>
                <td>{{ $employee->gender }}</td>
                <td>{{ $employee->rating ?? 'n/a'}} </td>
                <td>
                    <button class="edit-btn" onclick="editEmployee({{ $employee->id }})">✏️ Edit</button>
                    <button class="delete-btn" onclick="confirmDelete({{ $employee->id }})">🗑️ Delete</button>
                </td>
            </tr>
            @empty
<tr>
    <td colspan="6" style="text-align:center; padding:20px;">
        No employees found.
    </td>
</tr>
@endforelse

            </tbody>
        </table>