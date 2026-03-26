
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="{{asset('css/partials/sidenav.css')}}">
    <link rel="stylesheet" href="{{ asset('css/timekeeper/cashadvance.css') }}">
</head>
<body>
<nav class="sidenav">
    <div class="logo">LOGO</div>
    <a href="{{ route('timekeeper.index') }}">📊 DASHBOARD</a>
    <a href="{{ route('timekeeper.users') }}">👥 USERS</a>
    <a href="{{ route('timekeeper.attendance') }}">📋 ATTENDANCE</a>
    <a href="{{ route('timekeeper.employees') }}">👔 EMPLOYEES</a>
    <a href="{{ url('timekeeper/cashadvance') }}" class="active">📃 CASH ADVANCES</a>

    <div class="sidenav-logout">
            <form action="{{ route('logout') }}" method="POST" style="margin: 0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </form>
</nav>

<div class="main-content">
    @include('timekeeper.partials.header')
    
    <!-- FILTER FORM -->
    <form method="GET"
          action="{{ route('timekeeper.cashadvance') }}"
          class="filter-form-row">

        <div class="flex-box" style="margin-bottom:15px; display:flex; align-items:center;">
    <h1 style="flex:1; margin:0;">Cash Advances</h1>
    <button type="button" class="add-btn" onclick="openCashAdvanceModal()">Add Cash Advance</button>
</div>
        <input type="text" name="search" class="search-box" placeholder="Search by Name" value="{{ request('search') }}">
        
        <select name="status" class="filter-box" onchange="this.form.submit()">
            <option value="">Filter by Status</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>

        
    </form>

    <!-- TABLE -->
    <div class="table-container mt-5">
        <div class="card-body table-responsive">

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Amount</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($cashAdvances as $cash)
                        <tr>
                            <td>{{ $cash->id }}</td>

                            <td>
                                {{ $cash->first_name }}
                                @if($cash->middle_name)
                                    {{ strtoupper(substr($cash->middle_name,0,1)) }}.
                                @endif
                                {{ $cash->last_name }}
                            </td>

                            <td>₱ {{ number_format($cash->amount, 2) }}</td>

                            <td>{{ $cash->reason ?? 'N/A' }}</td>
                            
                            <td>
                                <span class="status-badge status-{{ strtolower($cash->status) }}">
                                    {{ ucfirst($cash->status) }}
                                </span>
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($cash->created_at)->format('M d, Y | h:i A') }}
                            </td>
                            
                            <td>
                                <button class="edit-btn" onclick="editCashAdvanceModal({{ $cash->id }})">
                                    ✏️ Edit
                                </button>
                                <button class="delete-btn" onclick="confirmDelete({{ $cash->id }})">
                                    🗑️ Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                No Cash Advance Records Found
                            </td>
                        </tr>
                    @endforelse
                </tbody>

            </table>

        </div>
    </div>

    <!-- PAGINATION -->
    <div class="pagination">

        {{-- FIRST --}}
        @if ($cashAdvances->onFirstPage())
            <span class="disabled">««</span>
        @else
            <a href="{{ $cashAdvances->url(1) }}">««</a>
        @endif

        {{-- PREVIOUS --}}
        @if ($cashAdvances->onFirstPage())
            <span class="disabled">«</span>
        @else
            <a href="{{ $cashAdvances->previousPageUrl() }}">«</a>
        @endif

        {{-- PAGES --}}
        @php
            $currentPage = $cashAdvances->currentPage();
            $lastPage = $cashAdvances->lastPage();
            $start = max(1, $currentPage - 2);
            $end = min($lastPage, $currentPage + 2);
        @endphp

        @for ($page = $start; $page <= $end; $page++)
            @if ($page == $currentPage)
                <span class="active">{{ $page }}</span>
            @else
                <a href="{{ $cashAdvances->url($page) }}">{{ $page }}</a>
            @endif
        @endfor

        {{-- NEXT --}}
        @if ($cashAdvances->hasMorePages())
            <a href="{{ $cashAdvances->nextPageUrl() }}">»</a>
        @else
            <span class="disabled">»</span>
        @endif

        {{-- LAST --}}
        @if ($cashAdvances->hasMorePages())
            <a href="{{ $cashAdvances->url($cashAdvances->lastPage()) }}">»»</a>
        @else
            <span class="disabled">»»</span>
        @endif

    </div>

</div>

<!-- ========================= -->
<!-- ADD CASH ADVANCE MODAL -->
<!-- ========================= -->
<div id="cashAdvanceModal" class="modal-overlay">
    <div class="modal-box">

        <h2>Add Cash Advance</h2>
        <hr>

        <form method="POST" action="{{ route('timekeeper.cashadvance.store') }}" id="cashAdvanceForm">
            @csrf

            <table>
                <tbody>

                    <!-- EMPLOYEE DROPDOWN -->
                    <tr>
                        <td>
                            <strong>Employee:</strong>
                        </td>
                        <td>
                            <select name="employee_id" required>
                                <option value="">Select Employee</option>
                                @forelse($employees as $emp)
                                    <option value="{{ $emp->id }}">
                                        {{ $emp->first_name }}
                                        @if($emp->middle_name)
                                            {{ strtoupper(substr($emp->middle_name, 0, 1)) }}.
                                        @endif
                                        {{ $emp->last_name }}
                                        @if($emp->suffixes)
                                            {{ $emp->suffixes }}
                                        @endif
                                    </option>
                                @empty
                                    <option disabled>No employees available</option>
                                @endforelse
                            </select>
                        </td>
                    </tr>

                    <!-- AMOUNT -->
                    <tr>
                        <td>
                            <strong>Amount:</strong>
                        </td>
                        <td>
                            <input type="number" 
                                   name="amount" 
                                   step="0.01" 
                                   placeholder="0.00"
                                   value="{{ old('amount') }}"
                                   required>
                        </td>
                    </tr>

                    <!-- REASON -->
                    <tr>
                        <td>
                            <strong>Reason:</strong>
                        </td>
                        <td>
                            <textarea name="reason" 
                                      required 
                                      placeholder="Enter reason for cash advance"
                                      rows="3">{{ old('reason') }}</textarea>
                        </td>
                    </tr>

                    <!-- STATUS (Optional - Remove if not needed) -->
                    <tr>
                        <td>
                            <strong>Status:</strong>
                        </td>
                        <td>
                            <select name="status">
                                <option value="">Select Status</option>
                                <option value="pending" @selected(old('status') === 'pending')>Pending</option>
                                <option value="approved" @selected(old('status') === 'approved')>Approved</option>
                                <option value="rejected" @selected(old('status') === 'rejected')>Rejected</option>
                            </select>
                        </td>
                    </tr>

                </tbody>
            </table>

            <!-- BUTTONS -->
            <div style="margin-top: 20px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="save-btn">Update</button>
                <button type="button" class="modal-close" onclick="closeCashAdvanceModal()">X</button>
            </div>

        </form>
    </div>
</div>

<!-- ========================= -->
<!-- EDIT CASH ADVANCE MODAL -->
<!-- ========================= -->
<div id="editCashAdvanceModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">

        <h2>Edit Cash Advance</h2>
        <hr>
        <form id="editCashAdvanceForm" 
              method="POST">

            @csrf
            @method('PUT')

            <table>
                <tbody>

                    <!-- EMPLOYEE DROPDOWN -->
                    <tr>
                            <tr>
                                <td><strong>Employee:</strong></td>
                                <td>
                                    <!-- Visible but NOT editable -->
                                    <input type="text"
                                           id="edit_employee_name"
                                           readonly
                                           style="background:#f3f3f3; cursor:not-allowed;">

                                    <!-- Hidden actual employee_id that gets submitted -->
                                    <input type="hidden"
                                           id="edit_employee_id"
                                           name="employee_id">
                                </td>
                            </tr>
                    </tr>

                    <!-- AMOUNT -->
                    <tr>
                        <td><strong>Amount:</strong></td>
                        <td>
                            <input type="number"
                                   id="edit_amount"
                                   name="amount"
                                   step="0.01"
                                   required>
                        </td>
                    </tr>

                    <!-- REASON -->
                    <tr>
                        <td><strong>Reason:</strong></td>
                        <td>
                            <textarea id="edit_reason"
                                      name="reason"
                                      required></textarea>
                        </td>
                    </tr>

                    <!-- STATUS -->
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td> 
                            <select id="edit_status" 
                                    name="status" 
                                    required>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </td>
                    </tr>

                </tbody>
            </table>

            <div style="margin-top: 20px; text-align: right; display: flex; justify-content: flex-end; gap: 10px;">

                <button type="submit"
                        class="save-btn">
                    Update
                </button>
                <button type="button" class="modal-close" onclick="closeEditCashAdvanceModal()">X</button>
            </div>

        </form>
    </div>
</div>

<!-- ========================= -->
<!-- DELETE FORM -->
<!-- ========================= -->
<form id="deleteCashAdvanceForm"
      method="POST"
      style="display:none;">
    @csrf
    @method('DELETE')
</form>

<!-- ========================= -->
<!-- SCRIPT -->
<!-- ========================= -->
<script>

function openCashAdvanceModal() {
    document.getElementById('cashAdvanceModal').style.display = 'flex';
}

function closeCashAdvanceModal() {
    document.getElementById('cashAdvanceModal').style.display = 'none';
}

function editCashAdvanceModal(id) {
    fetch(`/timekeeper/cashadvance/${id}/modal`)
        .then(response => response.json())
        .then(data => {
            // Populate form fields
            document.getElementById('edit_employee_name').value =  data.first_name + ' ' + data.last_name;
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_reason').value = data.reason;
            document.getElementById('edit_status').value = data.status;
            
            // Set the form action to update endpoint
            document.getElementById('editCashAdvanceForm').action = `/timekeeper/cashadvance/${id}`;
            
            // Show modal
            document.getElementById('editCashAdvanceModal').style.display = 'flex';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load cash advance data');
        });
}

function closeEditCashAdvanceModal() {
    document.getElementById('editCashAdvanceModal').style.display = 'none';
}

function confirmDelete(id) {
    if (!confirm('Are you sure you want to delete this cash advance record?')) return;

    const form = document.getElementById('deleteCashAdvanceForm');
    form.action = `/timekeeper/cashadvance/${id}`;
    form.submit();
}

</script>

</body>
</html>