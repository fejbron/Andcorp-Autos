-- Sample data for testing
-- Insert default admin user (password: admin123 - hashed with bcrypt)
INSERT INTO users (email, password, role, first_name, last_name, phone) VALUES
('admin@andcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'User', '+233123456789'),
('staff@andcorp.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Staff', 'Member', '+233123456788'),
('customer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'John', 'Doe', '+233123456787');

-- Insert sample customer
INSERT INTO customers (user_id, address, city, country, identification_number) VALUES
(3, '123 Main Street', 'Accra', 'Ghana', 'GHA-123456789');

-- Insert sample order
INSERT INTO orders (customer_id, order_number, status, total_cost, deposit_amount, balance_due, notes) VALUES
(1, 'ORD-2025-0001', 'shipping', 15000.00, 5000.00, 10000.00, 'Sample order for testing');

-- Insert sample vehicle
INSERT INTO vehicles (order_id, auction_source, listing_url, lot_number, vin, make, model, year, color, mileage, purchase_price, purchase_date) VALUES
(1, 'copart', 'https://www.copart.com/lot/12345', 'LOT12345', '1HGBH41JXMN109186', 'Toyota', 'Camry', 2020, 'Silver', 45000, 12000.00, '2025-10-15');

-- Insert sample purchase update
INSERT INTO purchase_updates (order_id, update_type, title, description, created_by) VALUES
(1, 'won', 'Auction Won', 'Successfully won the auction for Toyota Camry 2020. Payment processing.', 1);

-- Insert sample shipping update
INSERT INTO shipping_updates (order_id, shipping_company, tracking_number, departure_port, expected_arrival_date, status, shipping_cost) VALUES
(1, 'Ghana Shipping Line', 'GSL-2025-12345', 'Newark, NJ', '2025-11-20', 'in_transit', 1800.00);
