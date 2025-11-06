# Customer Deposits Tracking System

## Overview

The **Deposits Tracking System** allows admins to record, track, and verify customer payments/deposits with complete details including date, time, bank information, and transaction references. This provides transparency and accurate financial tracking for both customers and staff.

## Features

### 🔹 **For Admins/Staff:**
- ✅ Record deposits with date, time, and bank details
- ✅ Track payment methods (Bank Transfer, Mobile Money, Cash, Cheque, Card, etc.)
- ✅ Add transaction references and bank information
- ✅ Verify or reject deposits
- ✅ View all deposits with filtering options
- ✅ See deposit history for each order
- ✅ Dashboard statistics for deposits
- ✅ Search deposits by order number, customer, bank, or reference

### 🔹 **For Customers:**
- ✅ View all their deposits on profile page
- ✅ See deposit status (Pending, Verified, Rejected)
- ✅ Track total verified deposits
- ✅ View deposit history per order

## Database Schema

### **Deposits Table**

```sql
CREATE TABLE deposits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method ENUM('bank_transfer', 'mobile_money', 'cash', 'cheque', 'card', 'other'),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    reference_number VARCHAR(100),
    transaction_date DATE NOT NULL,
    transaction_time TIME NOT NULL,
    deposit_slip VARCHAR(255),
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at DATETIME,
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Updated Orders Table**

Added `total_deposits` field to track the sum of all verified deposits for an order.

```sql
ALTER TABLE orders 
ADD COLUMN total_deposits DECIMAL(10, 2) DEFAULT 0.00 AFTER deposit_amount;
```

## How It Works

### **1. Recording a Deposit (Admin)**

Admins can record deposits in two ways:

#### **Option A: From Order Edit Page**
1. Navigate to **Admin → Orders**
2. Click **Edit** on any order
3. In the **Deposits** section, click **"Add Deposit"**
4. Fill in the deposit details
5. Submit

#### **Option B: From Deposits Page**
1. Navigate to **Admin → Deposits**
2. Select an order
3. Click **"Add Deposit"**
4. Fill in the deposit details
5. Submit

### **2. Deposit Information Required**

| Field | Required | Description |
|-------|----------|-------------|
| **Amount** | Yes | Deposit amount |
| **Currency** | Yes | USD, GHS, EUR, etc. |
| **Transaction Date** | Yes | Date of the deposit |
| **Transaction Time** | Yes | Time of the deposit |
| **Payment Method** | Yes | Bank Transfer, Mobile Money, Cash, Cheque, Card, Other |
| **Bank Name** | No | Name of the bank |
| **Account Number** | No | Account number used |
| **Reference Number** | No | Transaction/Reference ID |
| **Notes** | No | Additional notes |
| **Status** | Yes | Pending or Verified (default: Verified) |

### **3. Deposit Status**

| Status | Description | Badge Color |
|--------|-------------|-------------|
| **Pending** | Deposit awaiting verification | Yellow/Warning |
| **Verified** | Deposit confirmed and counted | Green/Success |
| **Rejected** | Deposit rejected (not counted) | Red/Danger |

**Important:** Only **verified** deposits count towards the order's `total_deposits` and reduce the `balance_due`.

### **4. Automatic Calculations**

When a deposit is added or updated:
- ✅ `total_deposits` is recalculated for the order
- ✅ `balance_due` is updated automatically
- ✅ Customer's profile shows updated deposit totals

```
balance_due = total_cost - total_deposits
```

## Admin Features

### **Deposits Management Page**

**URL:** `admin/deposits.php`

#### **Features:**
- 📊 Statistics cards showing:
  - Total Verified Deposits
  - Pending Deposits Count
  - Pending Amount
  - Total Transactions
- 🔍 Filter deposits by status
- 🔎 Search by order number, customer name, bank, or reference
- 📋 Table view with all deposit details
- 🛠️ Quick actions: View, Verify, Reject

#### **Dashboard Integration**

The admin dashboard displays:
- Verified deposits total
- Pending deposits count
- Pending deposits amount
- Total transaction count

### **Order Management Integration**

On the order edit page:
- See all deposits for that specific order
- View deposit date/time, amount, method, bank, reference, and status
- Quick "Add Deposit" button
- Updated financial summary showing total deposits and balance due

## Customer Features

### **Profile Page**

Customers can view:
- Complete deposit history
- Deposit date and order number
- Amount and currency
- Status (Pending/Verified/Rejected)
- Total verified deposits sum

**URL:** `profile.php`

### **Order View Page**

On individual order pages, customers can see:
- Total deposits made for that order
- Balance due
- (Future: Detailed deposit breakdown)

## File Structure

```
public/
├── admin/
│   ├── deposits.php              # Main deposits management page
│   └── deposits/
│       └── add.php               # Add new deposit form
├── profile.php                   # Customer profile (includes deposits)
└── includes/
    └── navbar.php                # Updated with Deposits link

app/
└── Models/
    └── Deposit.php               # Deposit model

database/
└── deposits_tracking.sql         # Database schema
```

## API/Model Methods

### **Deposit Model** (`app/Models/Deposit.php`)

| Method | Description |
|--------|-------------|
| `create($data)` | Create a new deposit |
| `update($id, $data)` | Update deposit details |
| `verify($id, $verifiedBy)` | Mark deposit as verified |
| `reject($id, $notes)` | Mark deposit as rejected |
| `delete($id)` | Delete a deposit |
| `findById($id)` | Get deposit by ID |
| `getByOrder($orderId)` | Get all deposits for an order |
| `getByCustomer($customerId)` | Get all deposits for a customer |
| `getAll($status, $limit, $offset)` | Get all deposits with optional filters |
| `getPendingCount()` | Get count of pending deposits |
| `getStats()` | Get deposit statistics |
| `search($query)` | Search deposits |

## Security Features

- ✅ **CSRF Protection:** All forms use CSRF tokens
- ✅ **Input Validation:** All inputs are validated and sanitized
- ✅ **Authentication:** Only authenticated staff can manage deposits
- ✅ **Authorization:** Customers can only view their own deposits
- ✅ **SQL Injection Prevention:** Uses prepared statements
- ✅ **Audit Trail:** Tracks who created and verified each deposit

## Installation

### **For Existing Installations:**

Run the following SQL to add the deposits tracking feature:

```bash
mysql -u root -p car_dealership < database/deposits_tracking.sql
```

OR manually run in phpMyAdmin or MySQL command line.

### **For New Installations:**

The `deposits_tracking.sql` is automatically included in the setup.

## Usage Examples

### **Example 1: Recording a Bank Transfer**

1. Admin goes to order #ORD-001
2. Clicks "Add Deposit"
3. Fills in:
   - Amount: $5,000
   - Currency: USD
   - Date: 2025-11-05
   - Time: 14:30
   - Payment Method: Bank Transfer
   - Bank Name: GCB Bank
   - Reference: TRX123456789
   - Status: Verified
4. Submits
5. Order's balance due is automatically updated

### **Example 2: Recording Mobile Money Payment**

1. Admin adds deposit:
   - Amount: 25,000
   - Currency: GHS
   - Date: 2025-11-05
   - Time: 10:15
   - Payment Method: Mobile Money
   - Reference: MM-987654321
   - Status: Verified
2. Customer sees deposit on their profile immediately

### **Example 3: Pending Deposit**

1. Admin records deposit as "Pending"
2. Deposit shows in pending list
3. Admin verifies bank statement
4. Admin changes status to "Verified"
5. Order totals update automatically

## Benefits

### **For Business:**
- ✅ **Accurate Financial Tracking:** Know exactly what has been paid
- ✅ **Transparency:** Complete audit trail of all deposits
- ✅ **Reduced Errors:** Automated calculations prevent mistakes
- ✅ **Better Cash Flow Management:** See pending vs verified deposits
- ✅ **Customer Trust:** Customers can verify their payments

### **For Customers:**
- ✅ **Transparency:** See all their deposits and status
- ✅ **Peace of Mind:** Verify payments were recorded
- ✅ **Easy Tracking:** Know exactly how much they've paid
- ✅ **Status Updates:** See if deposits are verified

## Future Enhancements

Potential features for future versions:

1. **Email Notifications:** Notify customers when deposit is verified
2. **Receipt Generation:** Print/download deposit receipts
3. **Payment Gateway Integration:** Automatically record online payments
4. **Bulk Deposit Import:** Upload multiple deposits via CSV
5. **Deposit Analytics:** Charts and trends for deposits over time
6. **SMS Notifications:** Send SMS when deposit is recorded
7. **Multi-Currency Conversion:** Auto-convert between currencies
8. **Partial Payment Plans:** Set up installment schedules

## Troubleshooting

### **Deposits not showing:**
- Check if the deposit model is instantiated
- Verify database connection
- Check for PHP errors in error log

### **Balance not updating:**
- Ensure deposit status is "verified"
- Check the `updateOrderTotalDeposits()` method
- Verify foreign key relationships

### **Permission denied:**
- Ensure user has admin/staff role
- Check `Auth::requireStaff()` in files

## Support

For questions or issues with the deposits tracking system, refer to:
- Main project README.md
- Database schema: `database/deposits_tracking.sql`
- Model code: `app/Models/Deposit.php`

---

**Created:** November 2025  
**Version:** 1.0  
**Part of:** AndCorp Autos Car Dealership Management System

