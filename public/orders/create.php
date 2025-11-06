<?php
require_once '../bootstrap.php';
Auth::requireAuth();

// Redirect admin/staff to admin order creation
if (Auth::isStaff()) {
    redirect(url('admin/orders/create.php'));
}

// Customers cannot create orders directly - they must request quotes first
if (Auth::isCustomer()) {
    setErrors(['general' => 'Please request a quote first. Our team will create an order for you once approved.']);
    redirect(url('quotes/request.php'));
}

$customerModel = new Customer();
$customer = $customerModel->findByUserId(Auth::userId());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('orders/create.php'));
    }
    
    $validator = new Validator();
    $validator->required('auction_source', $_POST['auction_source'] ?? '')
              ->in('auction_source', $_POST['auction_source'] ?? '', ['copart', 'iaa']);
    $validator->required('make', $_POST['make'] ?? '')
              ->maxLength('make', $_POST['make'] ?? '', 100);
    $validator->required('model', $_POST['model'] ?? '')
              ->maxLength('model', $_POST['model'] ?? '', 100);
    $validator->required('year', $_POST['year'] ?? '')
              ->integer('year', $_POST['year'] ?? '')
              ->range('year', $_POST['year'] ?? '', 1990, date('Y') + 1);
    
    if (!empty($_POST['listing_url'])) {
        $validator->url('listing_url', $_POST['listing_url'] ?? '');
    }
    
    if (!empty($_POST['vin'])) {
        $validator->maxLength('vin', $_POST['vin'] ?? '', 17);
    }
    
    $errors = $validator->getErrors();
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance()->getConnection();
            $db->beginTransaction();
            
            // Create order
            $orderModel = new Order();
            $orderNumber = $orderModel->generateOrderNumber();
            
            $orderId = $orderModel->create([
                'customer_id' => $customer['id'],
                'order_number' => $orderNumber,
                'status' => 'pending',
                'notes' => !empty($_POST['notes']) ? Security::sanitizeString($_POST['notes'], 5000) : null
            ]);
            
            // Create vehicle record
            $vehicleModel = new Vehicle();
            $vehicleModel->create([
                'order_id' => $orderId,
                'auction_source' => Security::validateEnum($_POST['auction_source'], ['copart', 'iaa']) ? $_POST['auction_source'] : 'copart',
                'listing_url' => !empty($_POST['listing_url']) ? Security::sanitizeUrl($_POST['listing_url']) : null,
                'lot_number' => !empty($_POST['lot_number']) ? Security::sanitizeString($_POST['lot_number'], 100) : null,
                'vin' => !empty($_POST['vin']) ? Security::sanitizeString(strtoupper($_POST['vin']), 17) : null,
                'make' => Security::sanitizeString($_POST['make'], 100),
                'model' => Security::sanitizeString($_POST['model'], 100),
                'year' => Security::sanitizeInt($_POST['year'], 1990, date('Y') + 1),
                'color' => !empty($_POST['color']) ? Security::sanitizeString($_POST['color'], 50) : null,
                'mileage' => !empty($_POST['mileage']) ? Security::sanitizeInt($_POST['mileage'], 0) : null,
                'engine_type' => !empty($_POST['engine_type']) ? Security::sanitizeString($_POST['engine_type'], 100) : null,
                'transmission' => !empty($_POST['transmission']) && Security::validateEnum($_POST['transmission'], ['Automatic', 'Manual', 'CVT']) ? $_POST['transmission'] : null,
                'condition_description' => !empty($_POST['condition_description']) ? Security::sanitizeString($_POST['condition_description'], 2000) : null
            ]);
            
            // Log activity
            Auth::logOrderActivity(Auth::userId(), $orderId, 'order_created', 'New order created: ' . $orderNumber);
            
            // Send notification
            $notificationModel = new Notification();
            $notificationModel->create(
                Auth::userId(),
                $orderId,
                'email',
                'Order Created Successfully',
                "Your order {$orderNumber} has been created. We will start processing your vehicle purchase from {$_POST['auction_source']} shortly."
            );
            
            $db->commit();
            
            clearOld();
            setSuccess('Order created successfully! Order number: ' . $orderNumber);
            redirect(url('orders/view.php?id=' . $orderId));
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $errors['general'] = 'An error occurred while creating the order. Please try again.';
            error_log("Order creation error: " . $e->getMessage());
        }
    }
    
    setErrors($errors);
    setOld($_POST);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Order - Andcorp Autos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container my-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card-modern animate-in">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Create New Order</h4>
                    </div>
                    <div class="card-body">
                        <?php if (error('general')): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <strong>How it works:</strong> 
                            Fill in the vehicle details from your chosen Copart or IAA listing. Our team will review and process your order.
                        </div>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <?php echo Security::csrfField(); ?>
                            <h5 class="mb-3">Auction Information</h5>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="auction_source" class="form-label">Auction Source *</label>
                                    <select class="form-select <?php echo hasError('auction_source') ? 'is-invalid' : ''; ?>" 
                                            id="auction_source" name="auction_source" required>
                                        <option value="">Select Auction</option>
                                        <option value="copart" <?php echo old('auction_source') === 'copart' ? 'selected' : ''; ?>>Copart</option>
                                        <option value="iaa" <?php echo old('auction_source') === 'iaa' ? 'selected' : ''; ?>>IAA (Insurance Auto Auctions)</option>
                                        <option value="sca" <?php echo old('auction_source') === 'sca' ? 'selected' : ''; ?>>SCA Auction</option>
                                        <option value="tgna" <?php echo old('auction_source') === 'tgna' ? 'selected' : ''; ?>>The Great Northern Auction (TGNA)</option>
                                        <option value="manheim" <?php echo old('auction_source') === 'manheim' ? 'selected' : ''; ?>>Manheim Auctions</option>
                                    </select>
                                    <?php if (error('auction_source')): ?>
                                        <div class="invalid-feedback"><?php echo error('auction_source'); ?></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="lot_number" class="form-label">Lot Number</label>
                                    <input type="text" class="form-control" id="lot_number" name="lot_number" 
                                           value="<?php echo old('lot_number'); ?>" placeholder="e.g., 12345678">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="listing_url" class="form-label">Listing URL</label>
                                <input type="url" class="form-control" id="listing_url" name="listing_url" 
                                       value="<?php echo old('listing_url'); ?>" 
                                       placeholder="https://www.copart.com/lot/...">
                                <div class="form-text">Paste the full URL of the vehicle listing</div>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Vehicle Details</h5>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="year" class="form-label">Year *</label>
                                    <input type="number" class="form-control <?php echo hasError('year') ? 'is-invalid' : ''; ?>" 
                                           id="year" name="year" value="<?php echo old('year'); ?>" 
                                           min="1990" max="<?php echo date('Y') + 1; ?>" required placeholder="2020">
                                    <?php if (error('year')): ?>
                                        <div class="invalid-feedback"><?php echo error('year'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="make" class="form-label">Make *</label>
                                    <input type="text" class="form-control <?php echo hasError('make') ? 'is-invalid' : ''; ?>" 
                                           id="make" name="make" value="<?php echo old('make'); ?>" required placeholder="Toyota">
                                    <?php if (error('make')): ?>
                                        <div class="invalid-feedback"><?php echo error('make'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="model" class="form-label">Model *</label>
                                    <input type="text" class="form-control <?php echo hasError('model') ? 'is-invalid' : ''; ?>" 
                                           id="model" name="model" value="<?php echo old('model'); ?>" required placeholder="Camry">
                                    <?php if (error('model')): ?>
                                        <div class="invalid-feedback"><?php echo error('model'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vin" class="form-label">VIN (Vehicle Identification Number)</label>
                                    <input type="text" class="form-control" id="vin" name="vin" 
                                           value="<?php echo old('vin'); ?>" placeholder="1HGBH41JXMN109186" maxlength="17">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="color" name="color" 
                                           value="<?php echo old('color'); ?>" placeholder="Silver">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="mileage" class="form-label">Mileage</label>
                                    <input type="number" class="form-control" id="mileage" name="mileage" 
                                           value="<?php echo old('mileage'); ?>" placeholder="45000">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="engine_type" class="form-label">Engine Type</label>
                                    <input type="text" class="form-control" id="engine_type" name="engine_type" 
                                           value="<?php echo old('engine_type'); ?>" placeholder="2.5L 4-Cylinder">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="transmission" class="form-label">Transmission</label>
                                    <select class="form-select" id="transmission" name="transmission">
                                        <option value="">Select</option>
                                        <option value="Automatic" <?php echo old('transmission') === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                                        <option value="Manual" <?php echo old('transmission') === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                                        <option value="CVT" <?php echo old('transmission') === 'CVT' ? 'selected' : ''; ?>>CVT</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="condition_description" class="form-label">Condition Description</label>
                                <textarea class="form-control" id="condition_description" name="condition_description" 
                                          rows="3" placeholder="Describe the vehicle's condition as shown in the listing..."><?php echo old('condition_description'); ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Additional Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Any special requests or information..."><?php echo old('notes'); ?></textarea>
                            </div>

                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> <strong>Note:</strong> 
                                Our team will review your order and provide you with pricing and payment details within 24 hours.
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo url('dashboard.php'); ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Submit Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-modern mt-4 animate-in">
                    <div class="card-body">
                        <h6><i class="bi bi-question-circle"></i> Need Help Finding a Vehicle?</h6>
                        <p class="mb-2">Browse vehicles on these auction sites:</p>
                        <ul class="mb-0">
                            <li><a href="https://www.copart.com" target="_blank">Copart.com</a></li>
                            <li><a href="https://www.iaai.com" target="_blank">IAAI.com</a></li>
                        </ul>
                        <p class="mt-2 small text-muted">Copy the vehicle details and listing URL to this form.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>
