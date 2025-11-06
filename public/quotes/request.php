<?php
require_once '../bootstrap.php';
Auth::requireAuth();

// Redirect staff to admin panel
if (Auth::isStaff()) {
    redirect(url('admin/quote-requests.php'));
}

$customerModel = new Customer();
$customer = $customerModel->findByUserId(Auth::userId());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
        $errors['general'] = 'Invalid security token. Please try again.';
        setErrors($errors);
        redirect(url('quotes/request.php'));
    }
    
    $validator = new Validator();
    $validator->required('vehicle_type', $_POST['vehicle_type'] ?? '')
              ->required('make', $_POST['make'] ?? '')
              ->required('model', $_POST['model'] ?? '');
    
    $errors = $validator->getErrors();
    
    if (empty($errors)) {
        try {
            $quoteRequestModel = new QuoteRequest();
            
            $requestData = [
                'customer_id' => $customer['id'],
                'vehicle_type' => $_POST['vehicle_type'],
                'make' => $_POST['make'],
                'model' => $_POST['model'],
                'year' => $_POST['year'] ?? null,
                'trim' => $_POST['trim'] ?? null,
                'vin' => $_POST['vin'] ?? null,
                'lot_number' => $_POST['lot_number'] ?? null,
                'auction_link' => $_POST['auction_link'] ?? null,
                'budget_min' => $_POST['budget_min'] ?? null,
                'budget_max' => $_POST['budget_max'] ?? null,
                'preferred_color' => $_POST['preferred_color'] ?? null,
                'additional_requirements' => $_POST['additional_requirements'] ?? null
            ];
            
            $requestId = $quoteRequestModel->create($requestData);
            
            clearOld();
            setSuccess('Quote request submitted successfully! We will review and get back to you soon.');
            redirect(url('../quotes.php'));
        } catch (Exception $e) {
            $errors['general'] = 'An error occurred while submitting your request. Please try again.';
            error_log("Quote request creation error: " . $e->getMessage());
        }
    }
    
    setErrors($errors);
    setOld($_POST);
}

$title = "Request a Quote";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Quote - Andcorp Autos</title>
    <link rel="icon" type="image/png" href="<?php echo url('assets/images/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo url('assets/images/logo.png'); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/modern-theme.css'); ?>">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Page Header -->
                <div class="page-header animate-in">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5">Request a Quote</h1>
                            <p class="lead mb-0">Tell us about the vehicle you want to import</p>
                        </div>
                        <div>
                            <a href="<?php echo url('quotes.php'); ?>" class="btn btn-secondary btn-modern">
                                <i class="bi bi-arrow-left"></i> Back to My Requests
                            </a>
                        </div>
                    </div>
                </div>

                <?php if (error('general')): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i> <?php echo error('general'); ?>
                    </div>
                <?php endif; ?>

                <!-- Quote Request Form -->
                <div class="card-modern">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-car-front"></i> Vehicle Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <?php echo Security::csrfField(); ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vehicle_type" class="form-label">Vehicle Type *</label>
                                    <select class="form-select <?php echo hasError('vehicle_type') ? 'is-invalid' : ''; ?>" 
                                            id="vehicle_type" name="vehicle_type" required>
                                        <option value="">Select Type</option>
                                        <option value="Sedan" <?php echo old('vehicle_type') === 'Sedan' ? 'selected' : ''; ?>>Sedan</option>
                                        <option value="SUV" <?php echo old('vehicle_type') === 'SUV' ? 'selected' : ''; ?>>SUV</option>
                                        <option value="Truck" <?php echo old('vehicle_type') === 'Truck' ? 'selected' : ''; ?>>Truck</option>
                                        <option value="Van" <?php echo old('vehicle_type') === 'Van' ? 'selected' : ''; ?>>Van</option>
                                        <option value="Coupe" <?php echo old('vehicle_type') === 'Coupe' ? 'selected' : ''; ?>>Coupe</option>
                                        <option value="Convertible" <?php echo old('vehicle_type') === 'Convertible' ? 'selected' : ''; ?>>Convertible</option>
                                        <option value="Wagon" <?php echo old('vehicle_type') === 'Wagon' ? 'selected' : ''; ?>>Wagon</option>
                                        <option value="Other" <?php echo old('vehicle_type') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                    <?php if (error('vehicle_type')): ?>
                                        <div class="invalid-feedback"><?php echo error('vehicle_type'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="make" class="form-label">Make *</label>
                                    <input type="text" class="form-control <?php echo hasError('make') ? 'is-invalid' : ''; ?>" 
                                           id="make" name="make" value="<?php echo old('make'); ?>" 
                                           placeholder="e.g., Toyota, Honda, Ford" required>
                                    <?php if (error('make')): ?>
                                        <div class="invalid-feedback"><?php echo error('make'); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="model" class="form-label">Model *</label>
                                    <input type="text" class="form-control <?php echo hasError('model') ? 'is-invalid' : ''; ?>" 
                                           id="model" name="model" value="<?php echo old('model'); ?>" 
                                           placeholder="e.g., Camry, Accord, F-150" required>
                                    <?php if (error('model')): ?>
                                        <div class="invalid-feedback"><?php echo error('model'); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="year" class="form-label">Year</label>
                                    <input type="number" class="form-control" id="year" name="year" 
                                           value="<?php echo old('year'); ?>" 
                                           placeholder="e.g., 2021" min="1990" max="<?php echo date('Y') + 1; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="trim" class="form-label">Trim/Version</label>
                                <input type="text" class="form-control" id="trim" name="trim" 
                                       value="<?php echo old('trim'); ?>" 
                                       placeholder="e.g., XLE Premium, EX-L, Platinum">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="vin" class="form-label">VIN (if known)</label>
                                    <input type="text" class="form-control" id="vin" name="vin" 
                                           value="<?php echo old('vin'); ?>" 
                                           placeholder="17-character VIN" maxlength="17">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="lot_number" class="form-label">Lot Number (if known)</label>
                                    <input type="text" class="form-control" id="lot_number" name="lot_number" 
                                           value="<?php echo old('lot_number'); ?>" 
                                           placeholder="Auction lot number">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="auction_link" class="form-label">Auction Link (Copart/IAA)</label>
                                <input type="url" class="form-control" id="auction_link" name="auction_link" 
                                       value="<?php echo old('auction_link'); ?>" 
                                       placeholder="https://www.copart.com/lot/...">
                                <div class="form-text">Paste the link to the vehicle from Copart or IAA</div>
                            </div>

                            <hr class="my-4">

                            <h5 class="mb-3"><i class="bi bi-cash-stack"></i> Budget & Preferences</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="budget_min" class="form-label">Minimum Budget (GHS)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">GHS</span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="budget_min" name="budget_min" value="<?php echo old('budget_min'); ?>" 
                                               placeholder="0.00">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="budget_max" class="form-label">Maximum Budget (GHS)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">GHS</span>
                                        <input type="number" step="0.01" class="form-control" 
                                               id="budget_max" name="budget_max" value="<?php echo old('budget_max'); ?>" 
                                               placeholder="0.00">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="preferred_color" class="form-label">Preferred Color</label>
                                <input type="text" class="form-control" id="preferred_color" name="preferred_color" 
                                       value="<?php echo old('preferred_color'); ?>" 
                                       placeholder="e.g., Black, White, Silver">
                            </div>

                            <div class="mb-3">
                                <label for="additional_requirements" class="form-label">Additional Requirements</label>
                                <textarea class="form-control" id="additional_requirements" name="additional_requirements" 
                                          rows="4" placeholder="Any specific requirements or questions..."><?php echo old('additional_requirements'); ?></textarea>
                                <div class="form-text">Tell us about any specific features, condition requirements, or questions you have</div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send"></i> Submit Quote Request
                                </button>
                                <a href="<?php echo url('quotes.php'); ?>" class="btn btn-secondary">
                                    Cancel
                                </a>
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

