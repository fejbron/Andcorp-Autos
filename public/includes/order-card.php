<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h5 class="mb-0">
                        <?php if ($order['vehicle']): ?>
                            <?php echo $order['vehicle']['year'] . ' ' . $order['vehicle']['make'] . ' ' . $order['vehicle']['model']; ?>
                        <?php else: ?>
                            Order <?php echo $order['order_number']; ?>
                        <?php endif; ?>
                    </h5>
                    <span class="badge bg-<?php echo getStatusBadgeClass($order['status']); ?>">
                        <?php echo getStatusLabel($order['status']); ?>
                    </span>
                </div>
                
                <p class="text-muted mb-2">
                    <small>
                        <strong>Order #:</strong> <?php echo $order['order_number']; ?> |
                        <strong>Date:</strong> <?php echo formatDate($order['created_at']); ?>
                        <?php if ($order['vehicle']): ?>
                            | <strong>Source:</strong> <?php echo strtoupper($order['vehicle']['auction_source']); ?>
                        <?php endif; ?>
                    </small>
                </p>

                <?php if ($order['vehicle']): ?>
                    <p class="mb-2">
                        <small>
                            <?php if ($order['vehicle']['color']): ?>
                                <span class="badge bg-secondary"><?php echo $order['vehicle']['color']; ?></span>
                            <?php endif; ?>
                            <?php if ($order['vehicle']['mileage']): ?>
                                <span class="badge bg-secondary"><?php echo number_format($order['vehicle']['mileage']); ?> miles</span>
                            <?php endif; ?>
                            <?php if ($order['vehicle']['vin']): ?>
                                <span class="badge bg-secondary">VIN: <?php echo substr($order['vehicle']['vin'], -6); ?></span>
                            <?php endif; ?>
                        </small>
                    </p>
                <?php endif; ?>

                <!-- Progress Bar -->
                <?php
                $statuses = ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered'];
                $currentIndex = array_search($order['status'], $statuses);
                $progress = ($currentIndex !== false) ? ($currentIndex / (count($statuses) - 1)) * 100 : 0;
                
                // Determine color based on progress percentage
                if ($progress == 0) {
                    $progressColor = 'secondary'; // Gray for pending
                } elseif ($progress < 30) {
                    $progressColor = 'danger'; // Red for early stages
                } elseif ($progress < 60) {
                    $progressColor = 'warning'; // Yellow/Orange for mid stages
                } elseif ($progress < 100) {
                    $progressColor = 'info'; // Blue for near completion
                } else {
                    $progressColor = 'success'; // Green for delivered
                }
                ?>
                <div class="progress" style="height: 5px;">
                    <div class="progress-bar bg-<?php echo $progressColor; ?>" 
                         role="progressbar" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <small class="text-muted">Progress: <?php echo round($progress); ?>%</small>
            </div>

            <div class="col-md-4 text-end">
                <p class="mb-2">
                    <strong>Total:</strong><br>
                    <span class="h5 text-primary"><?php echo formatCurrency($order['total_cost'], $order['currency']); ?></span>
                </p>
                <?php if ($order['balance_due'] > 0): ?>
                    <p class="mb-2">
                        <small><strong>Balance Due:</strong><br>
                        <span class="text-warning"><?php echo formatCurrency($order['balance_due'], $order['currency']); ?></span></small>
                    </p>
                <?php endif; ?>
                <a href="<?php echo url('orders/view.php?id=' . $order['id']); ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-eye"></i> View Details
                </a>
            </div>
        </div>
    </div>
</div>
