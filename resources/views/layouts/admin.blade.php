<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Dashboard') - Aplikasi Antrian</title>
    
    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}">
    
    @livewireStyles
    @yield('styles')
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-box">
                    <i class="fas fa-layer-group"></i>
                </div>
                <h2>ANTRIAN</h2>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="{{ url('/admin') }}" class="nav-link {{ request()->is('admin') ? 'active' : '' }}">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(auth()->user()->role === 'superadmin')
                <li class="nav-item">
                    <a href="{{ url('/admin/polis') }}" class="nav-link {{ request()->is('admin/polis*') ? 'active' : '' }}">
                        <i class="fas fa-hospital"></i>
                        <span>Manajemen Poli</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/admin/users') }}" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
                        <i class="fas fa-user-md"></i>
                        <span>Manajemen Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/admin/patients') }}" class="nav-link {{ request()->is('admin/patients*') ? 'active' : '' }}">
                        <i class="fas fa-file-medical"></i>
                        <span>Rekam Medis</span>
                    </a>
                </li>
                @endif

                <li class="nav-item">
                    <a href="{{ url('/staff') }}" class="nav-link {{ request()->is('staff') ? 'active' : '' }}">
                        <i class="fas fa-headset"></i>
                        <span>Loket Petugas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/kiosk') }}" class="nav-link" target="_blank">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Layar Kiosk</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ url('/display') }}" class="nav-link" target="_blank">
                        <i class="fas fa-display"></i>
                        <span>Layar Display</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}" 
                       onclick="event.preventDefault(); this.closest('form').submit();"
                       class="nav-link" style="color: #d9534f;">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Keluar</span>
                    </a>
                </form>
            </div>
        </aside>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Topbar -->
            <header class="topbar">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <button class="toggle-sidebar" id="toggleSidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <h1>@yield('header_title', 'Dashboard Overview')</h1>
                    </div>
                </div>
                
                <div class="topbar-actions" style="display: flex; align-items: center; gap: 20px;">
                    <div class="user-profile">
                        <div class="user-info d-none-mobile" style="text-align: right; line-height: 1.2;">
                            <div style="font-weight: 700; color: var(--accent-color);">{{ auth()->user()->name }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">{{ ucfirst(auth()->user()->role) }}</div>
                        </div>
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=91D06C&color=346739" alt="User Avatar" class="user-avatar">
                    </div>
                </div>
            </header>

            <!-- Content Area -->
            <div class="page-content">
                @yield('content')
            </div>
            
            <footer style="margin-top: 3rem; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                &copy; {{ date('Y') }} Aplikasi Antrian Modern. Built with Passion by Fariz.
            </footer>
        </main>
    </div>

    <!-- Scripts -->
    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            const overlay = document.getElementById('sidebarOverlay');

            if (toggleBtn && sidebar && overlay) {
                toggleBtn.addEventListener('click', () => {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });

                overlay.addEventListener('click', () => {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }
        });
    </script>
    @yield('scripts')
</body>
</html>
