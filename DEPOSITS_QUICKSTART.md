# Deposits Tracking - Quick Start Guide

## 🚀 Getting Started

### Step 1: Access the Deposits System

**Admin Login:**
- URL: `http://localhost:8888/Andcorp-test/public/login.php`
- Email: `admin@andcorp.com`
- Password: `admin123`

### Step 2: View Deposits

Click **"Deposits"** in the top navigation menu, or go to:
```
http://localhost:8888/Andcorp-test/public/admin/deposits.php
```

### Step 3: Record a New Deposit

Two ways to add deposits:

#### **Method 1: From Order Page**
1. Go to **Admin → Orders**
2. Click **"Edit"** on any order
3. Scroll to **"Deposits"** section
4. Click **"Add Deposit"** button
5. Fill in the form and submit

#### **Method 2: From Deposits Page**
1. Go to **Admin → Deposits**
2. Find the order
3. (Future: Direct add from order ID)

## 📝 Recording a Deposit - Example

Let's record a bank transfer:

1. **Amount:** `5000.00`
2. **Currency:** `USD`
3. **Transaction Date:** `2025-11-05`
4. **Transaction Time:** `14:30`
5. **Payment Method:** `Bank Transfer`
6. **Bank Name:** `GCB Bank Limited`
7. **Account Number:** `1234567890` (optional)
8. **Reference Number:** `TRX-2025-11-05-001`
9. **Status:** `Verified` (default for admin-added deposits)
10. **Notes:** `Customer first deposit for Toyota Camry order`

Click **"Record Deposit"** ✅

## 🎯 What Happens Next?

After recording a deposit:

### **Automatic Updates:**
- ✅ Order's **Total Deposits** increases
- ✅ Order's **Balance Due** decreases
- ✅ Customer sees deposit on their profile
- ✅ Dashboard statistics update

### **Financial Calculation:**
```
Balance Due = Total Cost - Total Deposits
```

**Example:**
- Order Total: $15,000
- Total Deposits: $5,000
- Balance Due: $10,000

## 👁️ Viewing Deposits

### **Admin View:**

**On Dashboard:**
- See total verified deposits
- See pending deposits count
- See pending amount
- See total transactions

**On Deposits Page:**
- Filter by status (All, Pending, Verified, Rejected)
- Search by order number, customer, bank, or reference
- View complete deposit details
- Verify or reject pending deposits

**On Order Edit Page:**
- See all deposits for that specific order
- View transaction dates, amounts, and status
- Quick add deposit button

### **Customer View:**

**On Profile Page:**
- See complete deposit history
- View all deposits across all orders
- Check status of each deposit
- See total verified deposits

**On Order View Page:**
- See total deposits for that order
- View balance due

## 🔍 Searching & Filtering

### **Filter by Status:**
Use the dropdown to filter:
- **All Status** - Show everything
- **Pending** - Awaiting verification
- **Verified** - Confirmed deposits
- **Rejected** - Declined deposits

### **Search:**
Enter any of the following:
- Order number (e.g., `ORD-001`)
- Customer name (e.g., `John Doe`)
- Bank name (e.g., `GCB Bank`)
- Reference number (e.g., `TRX123`)
- Customer email

## 📊 Dashboard Statistics

The admin dashboard shows 4 deposit stat cards:

1. **Verified Deposits** - Total amount of verified deposits
2. **Pending Deposits** - Count of deposits awaiting verification
3. **Pending Amount** - Total amount pending verification
4. **Total Transactions** - Count of all verified deposits

## 🎨 Status Badges

| Status | Badge Color | Meaning |
|--------|-------------|---------|
| **Verified** | Green | Deposit confirmed ✅ |
| **Pending** | Yellow | Awaiting verification ⏳ |
| **Rejected** | Red | Deposit declined ❌ |

## 💡 Tips & Best Practices

### **Recording Deposits:**
- ✅ Always include transaction date and time
- ✅ Add reference numbers for easy tracking
- ✅ Include bank name for bank transfers
- ✅ Add notes for context (e.g., "Customer's mother made payment")
- ✅ Use "Verified" status when deposit is confirmed
- ⚠️ Use "Pending" if you need to verify with bank first

### **Payment Methods:**
- **Bank Transfer** - Wire transfers, online banking
- **Mobile Money** - MTN Mobile Money, Vodafone Cash, etc.
- **Cash** - Physical cash payments
- **Cheque** - Check payments
- **Card** - Credit/debit card payments
- **Other** - Any other payment method

### **Currency:**
- **USD** - US Dollars (default)
- **GHS** - Ghana Cedis
- **EUR** - Euros

### **Reference Numbers:**
Use clear reference formats:
- Bank: `TRX-2025-11-05-001`
- Mobile Money: `MM-987654321`
- Cash: `CASH-001`

## ⚠️ Important Notes

1. **Only Verified Deposits Count:** Only deposits with "Verified" status reduce the balance due
2. **Automatic Calculations:** Don't manually edit `total_deposits` or `balance_due` - they auto-update
3. **Admin Only:** Only staff/admin can record and verify deposits
4. **Audit Trail:** All deposits track who created and verified them

## 🧪 Testing Example

### **Test Scenario: Customer Makes Partial Payment**

**Order Details:**
- Order #: ORD-001
- Customer: John Doe
- Total Cost: $15,000
- Current Balance: $15,000

**Deposit 1:**
- Amount: $5,000
- Date: Nov 5, 2025
- Time: 10:00 AM
- Method: Bank Transfer
- Bank: GCB Bank
- Reference: TRX-001
- Status: Verified

**Result:** Balance = $10,000

**Deposit 2:**
- Amount: $3,000
- Date: Nov 10, 2025
- Time: 2:30 PM
- Method: Mobile Money
- Reference: MM-12345
- Status: Verified

**Result:** Balance = $7,000

**Deposit 3:**
- Amount: $7,000
- Date: Nov 15, 2025
- Time: 11:00 AM
- Method: Cash
- Status: Verified

**Result:** Balance = $0 (Fully Paid!) 🎉

## 📱 URLs Quick Reference

```
Admin Deposits Page:
http://localhost:8888/Andcorp-test/public/admin/deposits.php

Add Deposit (replace ORDER_ID):
http://localhost:8888/Andcorp-test/public/admin/deposits/add.php?order_id=ORDER_ID

Admin Dashboard:
http://localhost:8888/Andcorp-test/public/admin/dashboard.php

Customer Profile:
http://localhost:8888/Andcorp-test/public/profile.php
```

## ❓ Troubleshooting

**Q: Deposit not showing in customer profile?**
- A: Check if deposit status is "Verified"

**Q: Balance not updating?**
- A: Only "Verified" deposits count towards balance

**Q: Can't add deposit?**
- A: Ensure you're logged in as admin/staff

**Q: Customer can't see deposit?**
- A: Check if deposit is linked to correct customer_id

## 🎓 Video Tutorial

(Future: Add video walkthrough)

## 📞 Need Help?

Refer to the full documentation: `DEPOSITS_TRACKING.md`

---

**Last Updated:** November 2025  
**Version:** 1.0

