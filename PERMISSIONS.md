# Permissions & Access Control

## Document & Image Management

### Admin/Staff Permissions
✅ **Can:**
- Upload car images
- Upload vehicle title documents
- Upload Bill of Lading
- Upload Bill of Entry/Duty documents
- Delete any uploaded documents
- View all documents for all orders

### Customer Permissions  
✅ **Can:**
- View their own order documents
- View their car images in the gallery
- Download/view all documents related to their orders

❌ **Cannot:**
- Upload documents or images
- Delete documents or images
- View other customers' documents

## Cost Breakdown Management

### Admin/Staff
✅ **Can:**
- Enter and update cost breakdown for orders:
  - Car purchase cost
  - Transportation cost to Ghana
  - Duty/customs fees
  - Clearing fees
  - Fixing/repair costs
  - Total spent in USD

### Customers
✅ **Can:**
- View their total spending breakdown
- See detailed cost analysis in their profile
- Track total amounts across all their orders

❌ **Cannot:**
- Edit cost information
- Modify pricing data

## Customer Profile

### Customers Can Manage:
✅ Personal information (name, email, phone)
✅ Ghana Card number
✅ Address and city

### Admin Updates:
- Order-related documents
- Cost breakdowns
- Order status updates

## Gallery Access

### Admin/Staff:
- View all customer vehicles
- Manage all images and documents

### Customers:
- View only their own vehicles
- See images uploaded by admin
- Access their order documents

## Important Notes

📝 **For Customers:**
- All vehicle images and documents will be uploaded by our admin team after your car has been purchased and processed
- You will be able to view and download all documents once they are available
- Cost breakdowns will be added by admin as charges are incurred

📝 **For Admin:**
- Upload documents via the "Documents" link for each order
- Enter cost breakdown when creating or editing orders
- Customers will automatically see updates in their gallery and profile

## Security Features

✅ CSRF protection on all forms
✅ Role-based access control
✅ File upload validation (type, size, security)
✅ Secure file naming and storage
✅ SQL injection prevention
✅ XSS protection
✅ Rate limiting on sensitive actions

