<nav class="sidenav">
    <div class="logo">
        <img src="{{ asset('images/sample.jpg') }}" alt="Logo" onerror="console.log('Image failed to load')">
    </div>

    <a href="{{ url('admin/') }}"               class="{{ request()->is('admin') ? 'active' : '' }}">📊 DASHBOARD</a>
    <a href="{{ url('admin/users') }}"           class="{{ request()->is('admin/users*') ? 'active' : '' }}">👩🏻‍🚒 USERS</a>
    <a href="{{ url('admin/payroll') }}"         class="{{ request()->is('admin/payroll*') ? 'active' : '' }}">💸 PAYROLL</a>
    <a href="{{ url('admin/payslip') }}"         class="{{ request()->is('admin/payslip*') ? 'active' : '' }}">👔 PAYSLIPS</a>
    <a href="{{ url('admin/cashadvance') }}"     class="{{ request()->is('admin/cashadvance*') ? 'active' : '' }}">👌🏻 STATUTORY</a>
    <a href="{{ url('admin/attendance') }}"      class="{{ request()->is('admin/attendance*') ? 'active' : '' }}">👥 ATTENDANCE</a>
    <a href="{{ url('admin/holiday') }}"         class="{{ request()->is('admin/holiday*') ? 'active' : '' }}">🎅🏻 HOLIDAYS</a>
    <a href="{{ url('admin/employees') }}"       class="{{ request()->is('admin/employees*') ? 'active' : '' }}">📋 EMPLOYEES</a>
    <a href="{{ url('admin/rateapproval') }}"    class="{{ request()->is('admin/rateapproval*') ? 'active' : '' }}">⭐ RATE APPROVAL</a>
    <a href="{{ url('admin/archive') }}"         class="{{ request()->is('admin/archive*') ? 'active' : '' }}">👻 ARCHIVES</a>
    <a href="{{ url('admin/rolemanagement') }}"  class="{{ request()->is('admin/rolemanagement*') ? 'active' : '' }}">🙎🏻‍♀️ ROLES</a>

    <div class="sidenav-logout">
        <form action="{{ route('logout') }}" method="POST" style="margin:0;" id="logoutForm">
            @csrf
            <button type="button" class="logout-btn" onclick="confirmLogout()">🚪 Logout</button>
        </form>
    </div>
</nav>

{{-- ── Logout Confirmation Modal ── --}}
<div id="logoutModal" style="
    display:none; position:fixed; inset:0;
    background:rgba(15,10,40,0.55); z-index:99999;
    backdrop-filter:blur(4px);
    justify-content:center; align-items:center;">
    <div style="
        background:#fff; border-radius:16px;
        padding:32px 28px; max-width:380px; width:90%;
        box-shadow:0 25px 60px rgba(0,0,0,0.22);
        animation:modalFade .22s ease;
        text-align:center;">

        <div style="
            width:64px; height:64px; border-radius:50%;
            background:#fee2e2; display:flex;
            align-items:center; justify-content:center;
            font-size:28px; margin:0 auto 16px;">
            🚪
        </div>

        <h2 style="font-size:18px; font-weight:700; color:#1e293b; margin-bottom:8px;">
            Confirm Logout
        </h2>
        <p style="font-size:14px; color:#64748b; margin-bottom:24px; line-height:1.5;">
            Are you sure you want to logout?<br>You will be redirected to the login page.
        </p>

        <div style="display:flex; gap:10px; justify-content:center;">
            <button onclick="closeLogoutModal()" style="
                padding:10px 24px; border-radius:8px;
                border:1.5px solid #e2e8f0; background:#fff;
                color:#475569; font-size:14px; font-weight:600;
                cursor:pointer; transition:all .2s;">
                Cancel
            </button>
            <button onclick="document.getElementById('logoutForm').submit()" style="
                padding:10px 24px; border-radius:8px;
                border:none; background:linear-gradient(135deg,#ef4444,#dc2626);
                color:#fff; font-size:14px; font-weight:600;
                cursor:pointer; transition:all .2s;
                box-shadow:0 4px 12px rgba(239,68,68,0.35);">
                Yes, Logout
            </button>
        </div>
    </div>
</div>

<style>
@keyframes modalFade {
    from { opacity:0; transform:translateY(-14px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
</style>

<script>
function confirmLogout() {
    document.getElementById('logoutModal').style.display = 'flex';
}
function closeLogoutModal() {
    document.getElementById('logoutModal').style.display = 'none';
}
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('logoutModal').addEventListener('click', function(e) {
        if (e.target === this) closeLogoutModal();
    });
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLogoutModal();
    });
});
</script>