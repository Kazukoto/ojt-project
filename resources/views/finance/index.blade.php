<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Master</title>
    <!--link rel="stylesheet" href="{{ asset('css/partials/header.css') }}"-->
    <link rel="stylesheet" href="{{ asset('css/admin/admin-index.css') }}">
    
</head>
<!--@include('admin.partials.header')-->
@if(Session::has('user_id') && Session::get('role_id') == 4)


<body>
    <link rel="stylesheet" href="{{ asset('css/partials/sidenav.css') }}">
    @include('finance.partials.sidenav')
<div class="container">

<div class="stats-content">
<h1>Dashboard</h1>
<!-- ===== Stats ===== -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon">👤</div>
        <div class="stat-info">
            <h3>{{ number_format($totalUsers ?? 0) }}</h3>
            <p>Total Employee</p>
        </div>
    </div>

    <div class="stat-card red">
        <div class="stat-icon">⏰</div>
        <div class="stat-info">
            <h3>{{number_format($totalOvertime ?? 0)}} Hours</h3>
            <p>Total Overtime (Monthly)</p>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon">👥</div>
        <div class="stat-info">
            <h3>{{ number_format($newEmployees ?? 0) }}</h3>
            <p>Total New Employees (Monthly)</p>
        </div>
    </div>

    <div class="stat-card orange">
        <div class="stat-icon">💰</div>
        <div class="stat-info">
            <h3>₱{{ number_format($totalCashAdvance ?? 0, 2) }}</h3>
            <p>Total Cash Advanced</p>
        </div>
    </div>
</div>
<!-- ===== Table ===== -->
<div class="table-container">
<table>
<thead>
<tr>
    <th>EMPLOYEE NO.</th>
    <th>NAME</th>
    <th>GENDER</th>
    <th>POSITION</th>
    <th>EMPLOYMENT DATE</th>
</tr>
</thead>

<tbody class="main-content">
@forelse($employees as $employee)
<tr class="employee-row"
    data-id="{{ $employee->id }}"
    data-name="{{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }} {{ $employee->suffixes }}"
    data-contact-number="{{ $employee->contact_number }}"
    data-gender="{{ $employee->gender }}"
    data-birthdate="{{ $employee->birthdate }}"
    data-position="{{ $employee->position }}"
    data-address="{{ $employee->house_number }} {{ $employee->purok }}, {{ $employee->barangay }}, {{ $employee->city }}, {{ $employee->province }}"
    data-rating="{{ $employee->rating }}"
>
    <td>{{ $employee->id }}</td>
    <td>
        {{ $employee->last_name }},
        {{ $employee->first_name }}
    </td>
    <td>{{ $employee->gender }}</td>
    <td>{{ $employee->position }}</td>
    <td>{{ $employee->created_at->format('M d, Y') }}</td>
</tr>


@empty
<tr>
    <td colspan="5" style="text-align:center;">No employees found</td>
</tr>
@endforelse
</tbody>
</table>
</div>
<table>

</table>

</div>

<!-- ===== Modal ===== -->
<div id="employeeModal" class="modal">
<div class="modal-content">

<div class="modal-header">
    <h2>Employee Details</h2>
    <span class="close-btn">&times;</span>
</div>

<div class="modal-body-grid">
    <div class="info-card full">
        <label>Name</label>
        <p id="modalName"></p>
    </div>

    <div class="info-card">
        <label>Employee ID</label>
        <p id="modalId"></p>
    </div>

    <div class="info-card">
        <label>Contact Number</label>
        <p id="modalNumber"></p>
    </div>

    <div class="info-card">
        <label>Gender</label>
        <p id="modalGender"></p>
    </div>

    <div class="info-card">
        <label>Birthdate</label>
        <p id="modalBirthdate"></p>
    </div>

    <div class="info-card">
        <label>Position</label>
        <p id="modalPosition"></p>
    </div>

    <div class="info-card">
        <label>Rating</label>
        <p id="modalRating"></p>
    </div>

    <div class="info-card full">
        <label>Address</label>
        <p id="modalAddress"></p>
    </div>
</div>

</div>
</div>

<!-- ===== Script ===== -->
<script>
document.addEventListener("DOMContentLoaded", function(){

    const modal = document.getElementById("employeeModal");
    const closeBtn = document.querySelector(".close-btn");

    const modalId = document.getElementById("modalId");
    const modalName = document.getElementById("modalName");
    const modalNumber = document.getElementById("modalNumber");
    const modalGender = document.getElementById("modalGender");
    const modalBirthdate = document.getElementById("modalBirthdate");
    const modalPosition = document.getElementById("modalPosition");
    const modalRating = document.getElementById("modalRating");
    const modalAddress = document.getElementById("modalAddress");

    document.querySelectorAll(".employee-row").forEach(row=>{
        row.addEventListener("click", function(){

            modalId.textContent = this.dataset.id;
            modalName.textContent = this.dataset.name || "N/A";
            modalNumber.textContent = this.dataset.contactNumber || "N/A";
            modalGender.textContent = this.dataset.gender;
            modalBirthdate.textContent = this.dataset.birthdate;
            modalPosition.textContent = this.dataset.position;
            modalRating.textContent = this.dataset.rating;
            modalAddress.textContent = this.dataset.address;
            
            modal.style.display="block";
        });
    });

    closeBtn.onclick = ()=> modal.style.display="none";

    window.onclick = e=>{
        if(e.target==modal){
            modal.style.display="none";
        }
    };
});

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
