<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Approval</title>
    <link rel="stylesheet" href="{{ asset('css/superadmin/rateapproval.css') }}">
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

@if(Session::has('user_id') && Session::get('role_id') == 2)


    @include('admin.partials.sidenav')

    <div class="container">
        <div class="header">
            <h1>⭐ Rate Approval</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- CONTROLS -->
        <div class="controls-bar">
            <input type="text" id="searchInput" class="search-input" placeholder="🔍 Search by name...">

            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="pending">🕐 Pending</button>
            <button class="filter-btn" data-filter="rated">✅ Rated</button>
        </div>

        <!-- SINGLE TABLE -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Position</th>
                    <th>Gender</th>
                    <th>Registered</th>
                    <th>Current Rate</th>
                    <th>Status</th>
                    <th>Set / Edit Rate</th>
                </tr>
            </thead>
            <tbody id="employeeTableBody">
                @forelse($allEmployees as $emp)
                @php
                    $isPending = !$emp->rating || $emp->rating === 'n/a' || $emp->rating === '';
                @endphp
                <tr data-status="{{ $isPending ? 'pending' : 'rated' }}"
                    data-name="{{ strtolower($emp->first_name . ' ' . $emp->last_name) }}">
                    <td>{{ $emp->id }}</td>
                    <td>{{ $emp->first_name }} {{ $emp->last_name }}</td>
                    <td>{{ $emp->position ?? 'N/A' }}</td>
                    <td>{{ $emp->gender ?? 'N/A' }}</td>
                    <td>{{ $emp->created_at->format('M d, Y') }}</td>
                    <td class="current-rate-{{ $emp->id }}">{{ $emp->rating ?? 'n/a' }}</td>
                    <td class="status-cell-{{ $emp->id }}">
                        @if($isPending)
                            <span class="badge badge-pending">Pending</span>
                        @else
                            <span class="badge badge-rated">Rated</span>
                        @endif
                    </td>
                    <td>
                        <div class="rate-form">
                            <input type="text"
                                   class="rate-input"
                                   id="rate-input-{{ $emp->id }}"
                                   placeholder="e.g. 1000"
                                   value="{{ (!$isPending) ? $emp->rating : '' }}">
                            <button class="btn-save"
                                    onclick="saveRate({{ $emp->id }})">
                                {{ $isPending ? 'Save' : 'Update' }}
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                    <tr><td colspan="8" class="no-records">No employees found.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($allEmployees->hasPages())
                    <div class="pagination">
                        {{-- First Page --}}
                        @if ($allEmployees->onFirstPage())
                            <span class="disabled">««</span>
                            <span class="disabled">«</span>
                        @else
                            <a href="{{ $allEmployees->appends(['date' => request('date'), 'search' => request('search'), 'position' => request('position')])->url(1) }}">««</a>
                            <a href="{{ $allEmployees->appends(['date' => request('date'), 'search' => request('search'), 'position' => request('position')])->previousPageUrl() }}">«</a>
                        @endif

                        {{-- Page Numbers --}}
                        @php
                            $currentPage = $allEmployees->currentPage();
                            $lastPage = $allEmployees->lastPage();
                            $start = max(1, $currentPage - 2);
                            $end = min($lastPage, $currentPage + 2);
                        @endphp

                        @for ($page = $start; $page <= $end; $page++)
                            @if ($page == $currentPage)
                                <span class="active">{{ $page }}</span>
                            @else
                                <a href="{{ $allEmployees->appends(['date' => request('date'), 'search' => request('search'), 'position' => request('position')])->url($page) }}">{{ $page }}</a>
                            @endif
                        @endfor

                        {{-- Ellipsis --}}
                        @if($end < $lastPage)
                            <span class="disabled">...</span>
                        @endif

                        {{-- Last Page --}}
                        @if ($allEmployees->hasMorePages())
                            <a href="{{ $allEmployees->appends(['date' => request('date'), 'search' => request('search'), 'position' => request('position')])->nextPageUrl() }}">»</a>
                            <a href="{{ $allEmployees->appends(['date' => request('date'), 'search' => request('search'), 'position' => request('position')])->url($allEmployees->lastPage()) }}">»»</a>
                        @else
                            <span class="disabled">»</span>
                            <span class="disabled">»»</span>
                        @endif
                    </div>
                @endif
    </div>

    <!-- TOAST NOTIFICATION -->
    <div class="toast" id="toast"></div>

    <script>
        // ── AJAX Save Rate ──────────────────────────────────────────
        function saveRate(employeeId) {
            const input = document.getElementById(`rate-input-${employeeId}`);
            const rating = input.value.trim();

            if (!rating) {
                showToast('Please enter a rate value.', true);
                return;
            }

            fetch(`/admin/rateapproval/${employeeId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-HTTP-Method-Override': 'PUT'
                },
                body: JSON.stringify({ rating: rating })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update current rate cell
                    document.querySelector(`.current-rate-${employeeId}`).textContent = rating;

                    // Update status badge
                    document.querySelector(`.status-cell-${employeeId}`).innerHTML =
                        `<span class="badge badge-rated">Rated</span>`;

                    // Update row data-status for filter
                    const row = input.closest('tr');
                    row.dataset.status = 'rated';

                    // Change button text
                    input.closest('.rate-form').querySelector('.btn-save').textContent = 'Update';

                    showToast(data.message || 'Rate updated successfully!');
                } else {
                    showToast('Failed to update rate.', true);
                }
            })
            .catch(() => showToast('Something went wrong.', true));
        }

        // ── Toast ────────────────────────────────────────────────────
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast' + (isError ? ' error' : '');
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 3000);
        }

        // ── Search Filter ────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('input', function () {
            filterTable();
        });

        // ── Status Toggle Buttons ────────────────────────────────────
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                filterTable();
            });
        });

        // ── Combined Filter Function ─────────────────────────────────
        function filterTable() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;

            document.querySelectorAll('#employeeTableBody tr').forEach(row => {
                const name = row.dataset.name ?? '';
                const status = row.dataset.status ?? '';

                const matchesSearch = name.includes(search);
                const matchesFilter = activeFilter === 'all' || status === activeFilter;

                row.style.display = (matchesSearch && matchesFilter) ? '' : 'none';
            });
        }
         function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
            document.getElementById('logoutForm').submit();
            }
        }
    </script>

@else
    <script>
        window.location.href = "{{ route('login') }}";
    </script>
@endif
</body>
</html>