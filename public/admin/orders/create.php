<?php
require_once '../../bootstrap.php';
Auth::requireStaff();

$customerModel = new Customer();
$customers = $customerModel->getAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('admin/orders/create.php'));
    }
    
    $validator = new Validator();
    $validator->required('customer_id', $_POST['customer_id'] ?? '')
              ->integer('customer_id', $_POST['customer_id'] ?? '');
    $validator->required('auction_source', $_POST['auction_source'] ?? '')
              ->in('auction_source', $_POST['auction_source'] ?? '', ['copart', 'iaa']);
    $validator->required('make', $_POST['make'] ?? '')
              ->maxLength('make', $_POST['make'] ?? '', 100);
    $validator->required('model', $_POST['model'] ?? '')
              ->maxLength('model', $_POST['model'] ?? '', 100);
    $validator->required('year', $_POST['year'] ?? '')
              ->integer('year', $_POST['year'] ?? '')
              ->range('year', $_POST['year'] ?? '', 1990, date('Y') + 1);
    
    if (!empty($_POST['status'])) {
        $validator->in('status', $_POST['status'], ['Pending', 'Purchased', 'Shipping', 'Customs', 'Inspection', 'Repair', 'Ready', 'Delivered', 'Cancelled']);
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
                'customer_id' => Security::sanitizeInt($_POST['customer_id']),
                'order_number' => $orderNumber,
                'status' => Security::sanitizeStatus($_POST['status'] ?? 'Pending'),
                'total_cost' => Security::sanitizeFloat($_POST['total_cost'] ?? 0, 0),
                'deposit_amount' => Security::sanitizeFloat($_POST['deposit_amount'] ?? 0, 0),
                'balance_due' => (Security::sanitizeFloat($_POST['total_cost'] ?? 0, 0)) - (Security::sanitizeFloat($_POST['deposit_amount'] ?? 0, 0)),
                'currency' => 'GHS', // Ghana Cedis only
                'notes' => !empty($_POST['notes']) ? Security::sanitizeString($_POST['notes'], 5000) : null
            ]);
            
            // Create vehicle record
            $vehicleModel = new Vehicle();
            $vehicleModel->create([
                'order_id' => $orderId,
                'auction_source' => $_POST['auction_source'],
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
                'condition_description' => !empty($_POST['condition_description']) ? Security::sanitizeString($_POST['condition_description'], 2000) : null,
                'purchase_price' => !empty($_POST['purchase_price']) ? Security::sanitizeFloat($_POST['purchase_price'], 0) : null,
                'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null
            ]);
            
            // Log activity
            Auth::logOrderActivity(Auth::userId(), $orderId, 'order_created', 'Order created by staff: ' . $orderNumber);
            
            // Send notification to customer
            $customer = $customerModel->findById(Security::sanitizeInt($_POST['customer_id']));
            if ($customer) {
                $notificationModel = new Notification();
                $notificationModel->create(
                    $customer['user_id'],
                    $orderId,
                    'email',
                    'New Order Created',
                    "A new order {$orderNumber} has been created for you. We will keep you updated on the progress."
                );
            }
            
            $db->commit();
            
            clearOld();
            setSuccess('Order created successfully! Order number: ' . $orderNumber . '. You can now upload car images and documents.');
            redirect(url('admin/orders/edit.php?id=' . $orderId));
        } catch (Exception $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            $errors['general'] = 'An error occurred while creating the order. Please try again.';
            error_log("Admin order creation error: " . $e->getMessage());
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
    <title>Create New Order - Andcorp Autos Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Create New Order</h1>
                            <p class="lead">Create a new order for a customer</p>
                        </div>
                        <div>
                            <a href="<?php echo url('admin/orders.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to Orders
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-modern animate-in">
                    <div class="card-body">
                        <?php if (error('general')): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <?php echo Security::csrfField(); ?>
                            <!-- Customer Selection -->
                            <h5 class="mb-3">Customer Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="customer_id" class="form-label">Select Customer *</label>
                                    <select class="form-select <?php echo hasError('customer_id') ? 'is-invalid' : ''; ?>" 
                                            id="customer_id" name="customer_id" required>
                                        <option value="">Choose a customer...</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?php echo $customer['id']; ?>" 
                                                    <?php echo old('customer_id') == $customer['id'] ? 'selected' : ''; ?>>
                                                <?php echo $customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['email'] . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (error('customer_id')): ?>
                                        <div class="invalid-feedback"><?php echo error('customer_id'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Order Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <?php $selectedStatus = ucfirst(strtolower(old('status', 'Pending'))); ?>
                                        <option value="Pending" <?php echo $selectedStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Purchased" <?php echo $selectedStatus === 'Purchased' ? 'selected' : ''; ?>>Purchased</option>
                                        <option value="Shipping" <?php echo $selectedStatus === 'Shipping' ? 'selected' : ''; ?>>Shipping</option>
                                        <option value="Customs" <?php echo $selectedStatus === 'Customs' ? 'selected' : ''; ?>>Customs</option>
                                        <option value="Inspection" <?php echo $selectedStatus === 'Inspection' ? 'selected' : ''; ?>>Inspection</option>
                                        <option value="Repair" <?php echo $selectedStatus === 'Repair' ? 'selected' : ''; ?>>Repair</option>
                                        <option value="Ready" <?php echo $selectedStatus === 'Ready' ? 'selected' : ''; ?>>Ready</option>
                                        <option value="Delivered" <?php echo $selectedStatus === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Cancelled" <?php echo $selectedStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                            </div>

                            <hr class="my-4">

                            <!-- Auction Information -->
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
                                       value="<?php echo old('listing_url'); ?>" placeholder="https://www.copart.com/lot/...">
                            </div>

                            <hr class="my-4">

                            <!-- Vehicle Details -->
                            <h5 class="mb-3">Vehicle Details</h5>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="year" class="form-label">Year *</label>
                                    <input type="number" class="form-control <?php echo hasError('year') ? 'is-invalid' : ''; ?>" 
                                           id="year" name="year" value="<?php echo old('year'); ?>" 
                                           min="1990" max="<?php echo date('Y') + 1; ?>" required>
                                    <?php if (error('year')): ?>
                                        <div class="invalid-feedback"><?php echo error('year'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="make" class="form-label">Make *</label>
                                    <input type="text" class="form-control <?php echo hasError('make') ? 'is-invalid' : ''; ?>" 
                                           id="make" name="make" value="<?php echo old('make'); ?>" required>
                                    <?php if (error('make')): ?>
                                        <div class="invalid-feedback"><?php echo error('make'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-5 mb-3">
                                    <label for="model" class="form-label">Model *</label>
                                    <input type="text" class="form-control <?php echo hasError('model') ? 'is-invalid' : ''; ?>" 
                                           id="model" name="model" value="<?php echo old('model'); ?>" required>
                                    <?php if (error('model')): ?>
                                        <div class="invalid-feedback"><?php echo error('model'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vin" class="form-label">VIN (Vehicle Identification Number)</label>
                                    <input type="text" class="form-control" id="vin" name="vin" 
                                           value="<?php echo old('vin'); ?>" maxlength="17">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="color" name="color" 
                                           value="<?php echo old('color'); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="mileage" class="form-label">Mileage</label>
                                    <input type="number" class="form-control" id="mileage" name="mileage" 
                                           value="<?php echo old('mileage'); ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="engine_type" class="form-label">Engine Type</label>
                                    <input type="text" class="form-control" id="engine_type" name="engine_type" 
                                           value="<?php echo old('engine_type'); ?>">
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
                                <textarea class="form-control" id="condition_description" name="condition_description" rows="3"><?php echo old('condition_description'); ?></textarea>
                            </div>

                            <hr class="my-4">

                            <!-- Purchase & Financial Information -->
                            <h5 class="mb-3">Purchase & Financial Information</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <input type="number" step="0.01" class="form-control" id="purchase_price" name="purchase_price" 
                                           value="<?php echo old('purchase_price'); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="purchase_date" class="form-label">Purchase Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" 
                                           value="<?php echo old('purchase_date'); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="total_cost" class="form-label">Total Cost</label>
                                    <input type="number" step="0.01" class="form-control" id="total_cost" name="total_cost" 
                                           value="<?php echo old('total_cost', 0); ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="deposit_amount" class="form-label">Deposit Amount</label>
                                    <input type="number" step="0.01" class="form-control" id="deposit_amount" name="deposit_amount" 
                                           value="<?php echo old('deposit_amount', 0); ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="currency" class="form-label">Currency</label>
                                    <select class="form-select" id="currency" name="currency">
                                        <option value="USD" <?php echo old('currency', 'USD') === 'USD' ? 'selected' : ''; ?>>USD</option>
                                        <option value="GHS" <?php echo old('currency') === 'GHS' ? 'selected' : ''; ?>>GHS</option>
                                        <option value="EUR" <?php echo old('currency') === 'EUR' ? 'selected' : ''; ?>>EUR</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">Order Notes</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo old('notes'); ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo url('admin/orders.php'); ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Create Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php clearErrors(); clearOld(); ?>

