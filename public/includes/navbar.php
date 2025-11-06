<nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container-fluid">
        <a class="navbar-brand" href="https://andcorpautos.com">
            <img src="<?php echo url('assets/images/logo.png'); ?>" alt="Andcorp Autos" style="height: 45px; width: auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (Auth::isCustomer()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('dashboard.php'); ?>"><i class="bi bi-house"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('quotes.php'); ?>"><i class="bi bi-file-earmark-text"></i> My Requests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('orders.php'); ?>"><i class="bi bi-box-seam"></i> My Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('gallery.php'); ?>"><i class="bi bi-images"></i> Gallery</a>
                    </li>
                <?php endif; ?>
                
                <?php if (Auth::isStaff()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('admin/dashboard.php'); ?>"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('admin/quote-requests.php'); ?>"><i class="bi bi-file-earmark-text"></i> Quote Requests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('admin/orders.php'); ?>"><i class="bi bi-box-seam"></i> Orders</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('admin/deposits.php'); ?>"><i class="bi bi-wallet2"></i> Deposits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('gallery.php'); ?>"><i class="bi bi-images"></i> Gallery</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo url('admin/customers.php'); ?>"><i class="bi bi-people"></i> Customers</a>
                    </li>
                <?php endif; ?>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?php echo Auth::user()['first_name']; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo url('profile.php'); ?>"><i class="bi bi-person"></i> Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo url('logout.php'); ?>"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
