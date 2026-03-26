
<header class="app-header">
    <div class="header-left">
        <h1>Users</h1>
    </div>
    <div class="header-right">

        <!-- Notification Icon (optional) -->
        <div class="header-icon">🔔</div>

        <!-- User Dropdown -->
        <div class="user-dropdown">
            <button class="user-btn" onclick="toggleUserMenu()">
                <div class="user-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U',0,1)) }}
                </div>
                <span class="user-name">
                    {{ auth()->user()->name ?? 'User' }}
                </span>
                ▾
            </button>

            <div id="userMenu" class="user-menu">
                <a href="#">Profile</a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        Logout
                    </button>
                </form>
            </div>
        </div>

    </div>
</header>
