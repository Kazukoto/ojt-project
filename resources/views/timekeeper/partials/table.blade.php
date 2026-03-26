<div class="table-container">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Last Name</th>
            <th>First Name</th>
            <th>Position</th>
            <th>Gender</th>
            <th>More</th>
        </tr>
        </thead>
        <tbody>
        @forelse($employees as $employee)
            <tr>
                <td>{{ $employee->id }}</td>
                <td>{{ $employee->last_name }}</td>
                <td>{{ $employee->first_name }}</td>
                <td>{{ $employee->position ? $employee->position->name : 'N/A' }}</td>
                <td>{{ $employee->gender ?? 'N/A' }}</td>
                <td>
                    <select class="action-btn" onchange="handleAction(this, {{ $employee->id }})">
                        <option value="">Action</option>
                        <option value="edit">Edit</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button class="view-btn"
                            onclick="viewEmployee({{ $employee->id }})">
                        View Details
                    </button>
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
</div>

<!-- Pagination -->
<div style="margin-top: 20px;">
    {{ $employees->links() }}
</div>

<script>
function handleAction(select, employeeId) {
    const action = select.value;
    if (action === 'edit') {
        window.location.href = `/timekeeper/employees/${employeeId}/edit`;
    } else if (action === 'delete') {
        if (confirm('Are you sure you want to delete this employee?')) {
            deleteEmployee(employeeId);
        }
    }
    select.value = ''; // Reset dropdown
}

function deleteEmployee(id) {
    fetch(`/timekeeper/employees/${id}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Employee deleted successfully');
            location.reload(); // Reload to update table
        } else {
            alert('Failed to delete employee');
        }
    })
    .catch(() => alert('Error deleting employee'));
}
</script>