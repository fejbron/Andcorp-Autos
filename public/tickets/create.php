<?php
require_once '../bootstrap.php';
Auth::requireAuth();

$ticketModel = new SupportTicket();
$customerModel = new Customer();
$orderModel = new Order();

// Get customer
$customer = $customerModel->findByUserId(Auth::userId());

if (!$customer) {
    setErrors(['general' => 'Customer account not found.']);
    redirect(url('dashboard.php'));
}

// Get customer's orders for the dropdown
$orders = $orderModel->getByCustomer($customer['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate security token at the top
    Security::generateToken();
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        setErrors(['general' => 'Invalid security token. Please try again.']);
        redirect(url('tickets/create.php'));
    }
    
    $errors = [];
    
    // Validate subject
    $subject = Security::sanitizeString($_POST['subject'] ?? '', 255);
    if (empty($subject)) {
        $errors['subject'] = 'Subject is required';
    }
    
    // Validate message
    $message = Security::sanitizeString($_POST['message'] ?? '', 5000);
    if (empty($message)) {
        $errors['message'] = 'Message is required';
    }
    
    // Validate category
    $category = Security::sanitizeString($_POST['category'] ?? '', 20);
    $validCategories = ['general', 'order', 'payment', 'shipping', 'technical', 'other'];
    if (!in_array($category, $validCategories)) {
        $category = 'general';
    }
    
    // Validate priority
    $priority = Security::sanitizeString($_POST['priority'] ?? '', 20);
    $validPriorities = ['low', 'normal', 'high', 'urgent'];
    if (!in_array($priority, $validPriorities)) {
        $priority = 'normal';
    }
    
    // Optional order ID
    $orderId = Security::sanitizeInt($_POST['order_id'] ?? 0);
    if ($orderId > 0) {
        // Verify order belongs to customer
        $order = $orderModel->findById($orderId);
        if (!$order || $order['customer_id'] != $customer['id']) {
            $orderId = null;
        }
    } else {
        $orderId = null;
    }
    
    if (empty($errors)) {
        $ticketData = [
            'customer_id' => $customer['id'],
            'customer_user_id' => Auth::userId(),
            'order_id' => $orderId,
            'subject' => $subject,
            'category' => $category,
            'priority' => $priority,
            'message' => $message
        ];
        
        $ticketId = $ticketModel->create($ticketData);
        
        if ($ticketId) {
            setSuccess('Your support ticket has been created successfully. Our team will respond shortly.');
            clearOld();
            redirect(url('tickets/view.php?id=' . $ticketId));
        } else {
            $errors['general'] = 'Failed to create ticket. Please try again.';
        }
    }
    
    if (!empty($errors)) {
        setErrors($errors);
    }
}

// Generate token for form
Security::generateToken();

$title = "Create Support Ticket";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - Andcorp Autos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="container-modern">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Create Support Ticket</h1>
                            <p class="lead mb-0">Submit a support request and our team will assist you</p>
                        </div>
                        <div>
                            <a href="<?php echo url('tickets.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                <?php if ($errorMsg = error('general')): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo $errorMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Ticket Form -->
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-ticket-perforated"></i> Ticket Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars(url('tickets/create.php')); ?>">
                            <?php echo Security::csrfField(); ?>
                            
                            <!-- Subject -->
                            <div class="mb-3">
                                <label for="subject" class="form-label">
                                    Subject <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control <?php echo error('subject') ? 'is-invalid' : ''; ?>" 
                                       id="subject" 
                                       name="subject" 
                                       value="<?php echo old('subject'); ?>" 
                                       required>
                                <?php if ($err = error('subject')): ?>
                                    <div class="invalid-feedback"><?php echo $err; ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category" class="form-label">
                                    Category <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="general" <?php echo old('category') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                                    <option value="order" <?php echo old('category') === 'order' ? 'selected' : ''; ?>>Order Related</option>
                                    <option value="payment" <?php echo old('category') === 'payment' ? 'selected' : ''; ?>>Payment Issue</option>
                                    <option value="shipping" <?php echo old('category') === 'shipping' ? 'selected' : ''; ?>>Shipping/Delivery</option>
                                    <option value="technical" <?php echo old('category') === 'technical' ? 'selected' : ''; ?>>Technical Support</option>
                                    <option value="other" <?php echo old('category') === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <!-- Order (Optional) -->
                            <?php if (!empty($orders)): ?>
                                <div class="mb-3">
                                    <label for="order_id" class="form-label">
                                        Related Order (Optional)
                                    </label>
                                    <select class="form-select" id="order_id" name="order_id">
                                        <option value="">-- None --</option>
                                        <?php foreach ($orders as $order): ?>
                                            <option value="<?php echo $order['id']; ?>" 
                                                    <?php echo old('order_id') == $order['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($order['order_number']); ?>
                                                <?php if (!empty($order['vehicle_year']) || !empty($order['vehicle_make']) || !empty($order['vehicle_model'])): ?>
                                                    - <?php echo htmlspecialchars(trim(($order['vehicle_year'] ?? '') . ' ' . ($order['vehicle_make'] ?? '') . ' ' . ($order['vehicle_model'] ?? ''))); ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select an order if this ticket is related to a specific order</div>
                                </div>
                            <?php endif; ?>

                            <!-- Priority -->
                            <div class="mb-3">
                                <label for="priority" class="form-label">
                                    Priority <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="priority" name="priority" required>
                                    <option value="low" <?php echo old('priority') === 'low' ? 'selected' : ''; ?>>Low</option>
                                    <option value="normal" <?php echo old('priority') === 'normal' || !old('priority') ? 'selected' : ''; ?>>Normal</option>
                                    <option value="high" <?php echo old('priority') === 'high' ? 'selected' : ''; ?>>High</option>
                                    <option value="urgent" <?php echo old('priority') === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                                </select>
                                <div class="form-text">
                                    <strong>Urgent:</strong> Critical issue requiring immediate attention<br>
                                    <strong>High:</strong> Important issue affecting your order<br>
                                    <strong>Normal:</strong> General questions or concerns<br>
                                    <strong>Low:</strong> Minor inquiries
                                </div>
                            </div>

                            <!-- Message -->
                            <div class="mb-3">
                                <label for="message" class="form-label">
                                    Message <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control <?php echo error('message') ? 'is-invalid' : ''; ?>" 
                                          id="message" 
                                          name="message" 
                                          rows="8" 
                                          required><?php echo old('message'); ?></textarea>
                                <?php if ($err = error('message')): ?>
                                    <div class="invalid-feedback"><?php echo $err; ?></div>
                                <?php endif; ?>
                                <div class="form-text">Please provide as much detail as possible</div>
                            </div>

                            <!-- Submit -->
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo url('tickets.php'); ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Submit Ticket
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

