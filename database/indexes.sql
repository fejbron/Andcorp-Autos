-- Additional indexes for performance optimization
-- Run this after the main schema.sql

-- Composite indexes for common query patterns
CREATE INDEX IF NOT EXISTS idx_orders_customer_status ON orders(customer_id, status);
CREATE INDEX IF NOT EXISTS idx_orders_status_created ON orders(status, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_orders_created_desc ON orders(created_at DESC);

-- Users table indexes
CREATE INDEX IF NOT EXISTS idx_users_active_role ON users(is_active, role);
CREATE INDEX IF NOT EXISTS idx_users_created ON users(created_at DESC);

-- Customers table indexes
CREATE INDEX IF NOT EXISTS idx_customers_user ON customers(user_id);
CREATE INDEX IF NOT EXISTS idx_customers_country ON customers(country);

-- Vehicles table indexes
CREATE INDEX IF NOT EXISTS idx_vehicles_make_model ON vehicles(make, model);
CREATE INDEX IF NOT EXISTS idx_vehicles_year ON vehicles(year);
CREATE INDEX IF NOT EXISTS idx_vehicles_auction_lot ON vehicles(auction_source, lot_number);

-- Activity logs indexes
CREATE INDEX IF NOT EXISTS idx_activity_user_date ON activity_logs(user_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_activity_order ON activity_logs(order_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_activity_action ON activity_logs(action, created_at DESC);

-- Notifications indexes
CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, read_at);
CREATE INDEX IF NOT EXISTS idx_notifications_order ON notifications(order_id);
CREATE INDEX IF NOT EXISTS idx_notifications_created ON notifications(created_at DESC);

-- Purchase updates indexes
CREATE INDEX IF NOT EXISTS idx_purchase_order_created ON purchase_updates(order_id, created_at DESC);

-- Shipping updates indexes
CREATE INDEX IF NOT EXISTS idx_shipping_status ON shipping_updates(status);
CREATE INDEX IF NOT EXISTS idx_shipping_tracking ON shipping_updates(tracking_number);

-- Repair updates indexes
CREATE INDEX IF NOT EXISTS idx_repair_order_status ON repair_updates(order_id, status);
CREATE INDEX IF NOT EXISTS idx_repair_category ON repair_updates(repair_category);

-- Inspection reports indexes
CREATE INDEX IF NOT EXISTS idx_inspection_order_date ON inspection_reports(order_id, inspection_date DESC);
CREATE INDEX IF NOT EXISTS idx_inspection_condition ON inspection_reports(overall_condition);

-- Payments indexes (if payments table exists)
-- CREATE INDEX IF NOT EXISTS idx_payments_order_date ON payments(order_id, payment_date DESC);
-- CREATE INDEX IF NOT EXISTS idx_payments_status ON payments(payment_status);

-- Full-text search indexes (optional, for advanced search)
-- ALTER TABLE users ADD FULLTEXT(first_name, last_name, email);
-- ALTER TABLE vehicles ADD FULLTEXT(make, model, vin);
-- ALTER TABLE orders ADD FULLTEXT(order_number, notes);

