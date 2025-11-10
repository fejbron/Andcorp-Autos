# 🚗 Andcorp Autos - Car Import Management System

A comprehensive web application for managing international car purchases from major dealerships and insurance companies, with complete tracking from purchase to delivery in Ghana.

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production-success.svg)](https://app.andcorpautos.com)

---

## 📋 Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Project Structure](#project-structure)
- [User Roles](#user-roles)
- [Order Workflow](#order-workflow)
- [Security Features](#security-features)
- [Configuration](#configuration)
- [Support](#support)

---

## ✨ Features

### 🎯 Core Features

#### Customer Portal
- **Dashboard**: Real-time overview of all orders and quotes
- **Order Tracking**: Track vehicles through all stages with visual progress bar
- **Quote Requests**: Submit quotes for desired vehicles
- **Document Management**: Upload and view order-related documents
- **Deposit Management**: Submit deposits with photo proof
- **Support Tickets**: Create and manage support requests
- **Profile Management**: Update personal information and credentials
- **Email Notifications**: Automated updates for order status changes

#### Admin Dashboard
- **Comprehensive Dashboard**: Statistics cards showing orders, customers, revenue
- **Order Management**: Create, view, edit, and track all orders
- **Customer Management**: View and manage customer accounts with search
- **Quote Request System**: Review, quote, and convert quotes to orders
- **Deposit Verification**: Review and approve customer deposits
- **Document Upload**: Upload vehicle photos and documents for customers
- **Support Ticket System**: Manage customer support requests
- **Gallery Management**: Upload and manage vehicle images
- **Financial Reports**: Revenue tracking and financial summaries
- **Activity Logging**: Track all system activities

### 🔐 Authentication & Security

- **User Authentication**: Secure login/logout with session management
- **Password Reset**: Complete forgot password flow with email verification
- **Password Strength Indicator**: Real-time password strength feedback
- **Rate Limiting**: Anti-spam protection on login and password reset
- **CSRF Protection**: All forms protected against CSRF attacks
- **Role-Based Access Control**: Customer, Staff, and Admin roles
- **Session Expiration**: Automatic logout with redirect to intended page
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **Activity Logging**: Track user actions for audit trail

### 💰 Financial Management

- **Multi-Currency Support**: Primarily GHS (Ghana Cedis)
- **Deposit Tracking**: 
  - Submit deposits with bank transfer proof
  - Admin verification workflow
  - Real-time order balance calculation
- **Cost Breakdown**:
  - Vehicle purchase price (from auction)
  - Shipping costs
  - Customs and duty fees
  - Clearing fees
  - Inspection costs
  - Repair costs
  - Additional charges
- **Financial Summary**: Real-time calculation of:
  - Total cost
  - Total deposits paid
  - Balance due
- **Revenue Reports**: Admin dashboard with revenue analytics

### 📦 Order Management

- **Complete Order Lifecycle**:
  1. **Pending**: Order created, awaiting vehicle purchase
  2. **Purchased**: Vehicle acquired from dealership
  3. **Delivered to Port of Load**: Vehicle delivered to origin port
  4. **Origin customs clearance**: Customs clearance at origin country
  5. **Shipping**: En route to Ghana
  6. **Arrived in Ghana**: Vehicle has arrived at Ghana port
  7. **Ghana Customs Clearance**: Customs clearance in Ghana
  8. **Inspection**: Vehicle inspection
  9. **Repair**: Any necessary repairs
  10. **Ready**: Ready for customer delivery
  11. **Delivered**: Delivered to customer
  12. **Cancelled**: Order cancelled

- **Order Features**:
  - VIN tracking
  - Auction source tracking (Copart, IAA, SCA, TGNA, Manheim, Texas Metro)
  - Vehicle details (make, model, year, color, trim)
  - Document attachments
  - Status history
  - Customer notes

### 📄 Document Management

- **Document Types**:
  - Bill of Lading
  - Title Certificate
  - Customs Declaration
  - Inspection Report
  - Payment Receipt
  - Insurance Documents
  - Shipping Documents
  - Evidence of Delivery
  
- **Features**:
  - Secure file upload (JPG, PNG, PDF)
  - File size validation
  - Organized by document type
  - Download capability
  - Admin and customer access

### 💬 Support Ticket System

- **Customer Features**:
  - Create support tickets
  - Link tickets to specific orders
  - Add replies to tickets
  - Track ticket status
  - Priority levels (Low, Normal, High, Urgent)

- **Admin Features**:
  - View all tickets with filtering
  - Assign tickets to staff
  - Update ticket status (Open, Pending, Resolved, Closed)
  - Reply to tickets
  - Ticket categories (General, Order, Payment, Shipping, Technical)

### 📧 Email Notification System

- **Automated Emails**:
  - Order status updates
  - Deposit verification confirmations
  - Quote approvals
  - Password reset links
  - Support ticket updates

- **Email Features**:
  - Branded email templates with logo
  - Configurable sender information
  - Admin control over notification types
  - Status-specific notifications
  - Beautiful HTML templates

### 🎨 User Interface

- **Modern Design**:
  - Responsive Bootstrap 5 layout
  - Mobile-friendly interface
  - Clean, professional theme
  - Color-coded status indicators
  - Interactive progress bars
  - Modal dialogs
  - Toast notifications

- **Admin Interface**:
  - Statistics dashboard with cards
  - Data tables with search and filtering
  - Inline editing capabilities
  - Batch operations
  - Export capabilities

### 🔍 Search & Filtering

- **Global Search**:
  - Search orders by number, VIN, customer name
  - Search customers by name, email, phone
  - Search deposits by reference number
  - Search quotes by request number
  - Search tickets by ticket number

- **Advanced Filtering**:
  - Filter by status
  - Filter by date range
  - Filter by customer
  - Filter by order type

### 📊 Reporting & Analytics

- **Admin Reports**:
  - Total sales (verified deposits)
  - Active vs completed orders
  - Customer statistics
  - Order status breakdown
  - Monthly revenue trends
  - Top customers

- **Financial Reports**:
  - Total deposits by status
  - Pending deposit amounts
  - Revenue by month
  - Outstanding balances

### 🖼️ Gallery Management

- **Features**:
  - Upload vehicle images
  - Organize by categories
  - Responsive image grid
  - Lightbox view
  - Admin upload interface

---

## 🛠️ Technology Stack

### Backend
- **Language**: PHP 8.0+
- **Database**: MySQL 5.7+ / MariaDB 10.3+
- **Architecture**: MVC pattern (custom framework)
- **Session Management**: PHP Sessions with security hardening
- **Authentication**: Custom Auth class with bcrypt hashing

### Frontend
- **HTML5 & CSS3**: Semantic markup and modern styling
- **JavaScript**: Vanilla JS for interactivity
- **Bootstrap 5.3**: Responsive UI framework
- **Bootstrap Icons**: Icon library
- **Google Fonts**: Inter font family

### Database
- **PDO**: Prepared statements for SQL injection prevention
- **Transactions**: For data consistency
- **Foreign Keys**: Referential integrity
- **Indexes**: Optimized queries

### Security
- **CSRF Protection**: Token-based validation
- **Password Hashing**: bcrypt (PASSWORD_DEFAULT)
- **Input Sanitization**: Custom Security class
- **XSS Prevention**: htmlspecialchars output escaping
- **Rate Limiting**: Session-based rate limiting
- **SQL Injection Prevention**: PDO prepared statements

---

## 📥 Installation

### Prerequisites

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx web server with mod_rewrite
- 50MB+ disk space
- SSL certificate (recommended for production)

### Setup Instructions

1. **Clone or Download the Project**
   ```bash
   cd /path/to/web/root
   git clone <repository-url> andcorp-autos
   cd andcorp-autos
   ```

2. **Configure Database**
   
   Edit `config/database.php`:
   ```php
   return [
       'host' => 'localhost',
       'port' => '3306',
       'database' => 'your_database_name',
       'username' => 'your_database_user',
       'password' => 'your_database_password',
       'charset' => 'utf8mb4',
       'collation' => 'utf8mb4_unicode_ci',
   ];
   ```

3. **Create Database**
   ```bash
   mysql -u root -p
   CREATE DATABASE andcorp_autos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   ```

4. **Import Database Schema**
   ```bash
   mysql -u your_user -p andcorp_autos < database/schema.sql
   ```

5. **Import Additional Tables**
   ```bash
   mysql -u your_user -p andcorp_autos < database/email_notification_settings.sql
   mysql -u your_user -p andcorp_autos < database/password_resets.sql
   mysql -u your_user -p andcorp_autos < database/tickets_schema.sql
   mysql -u your_user -p andcorp_autos < database/deposits_tracking.sql
   ```

6. **Set Directory Permissions**
   ```bash
   chmod 755 public/
   chmod 755 public/uploads/
   chmod 755 storage/
   chmod 755 storage/cache/
   ```

7. **Configure Web Server**

   **For Apache** (create/edit `.htaccess` in public/):
   ```apache
   RewriteEngine On
   RewriteBase /
   RewriteCond %{REQUEST_FILENAME} !-f
   RewriteCond %{REQUEST_FILENAME} !-d
   RewriteRule ^(.*)$ index.php [QSA,L]
   ```

   **For Nginx**:
   ```nginx
   location / {
       try_files $uri $uri/ /index.php?$query_string;
   }
   ```

8. **Seed Sample Data (Optional)**
   ```bash
   mysql -u your_user -p andcorp_autos < database/seed.sql
   ```

9. **Access the Application**
   - Customer Portal: `http://yourdomain.com/public/`
   - Admin Dashboard: `http://yourdomain.com/public/admin/dashboard.php`

### Default Credentials (After Seeding)

**Admin Account**
- Email: `admin@example.com`
- Password: `admin123` (change immediately!)

**Test Customer Account**
- Email: `customer@example.com`
- Password: `customer123`

---

## 📁 Project Structure

```
Andcorp-test/
├── app/                          # Application core
│   ├── Auth.php                 # Authentication & authorization
│   ├── Cache.php                # Caching system
│   ├── Database.php             # Database connection
│   ├── Notification.php         # Email notifications
│   ├── Security.php             # Security utilities
│   ├── Validator.php            # Input validation
│   └── Models/                  # Database models
│       ├── Customer.php
│       ├── Deposit.php
│       ├── Order.php
│       ├── QuoteRequest.php
│       ├── Settings.php
│       ├── SupportTicket.php
│       ├── User.php
│       └── Vehicle.php
│
├── config/                       # Configuration
│   ├── database.php             # Database config
│   └── database.example.php     # Example config
│
├── database/                     # SQL files
│   ├── schema.sql               # Main database schema
│   ├── seed.sql                 # Sample data
│   ├── indexes.sql              # Database indexes
│   ├── email_notification_settings.sql
│   ├── password_resets.sql
│   ├── tickets_schema.sql
│   └── deposits_tracking.sql
│
├── public/                       # Web root (document root)
│   ├── admin/                   # Admin dashboard
│   │   ├── dashboard.php
│   │   ├── orders/
│   │   ├── customers.php
│   │   ├── deposits/
│   │   ├── quote-requests/
│   │   ├── tickets/
│   │   ├── reports.php
│   │   └── settings.php
│   │
│   ├── assets/                  # Static assets
│   │   ├── css/
│   │   │   └── modern-theme.css
│   │   └── images/
│   │       ├── logo.png
│   │       └── favicon.png
│   │
│   ├── includes/                # Shared templates
│   │   ├── head.php
│   │   ├── navbar.php
│   │   ├── footer.php
│   │   └── order-card.php
│   │
│   ├── orders/                  # Customer orders
│   │   ├── view.php
│   │   └── documents.php
│   │
│   ├── quotes/                  # Quote requests
│   │   ├── request.php
│   │   └── view.php
│   │
│   ├── tickets/                 # Support tickets
│   │   ├── create.php
│   │   └── view.php
│   │
│   ├── uploads/                 # User uploads
│   │   ├── cars/
│   │   ├── documents/
│   │   └── deposit_slips/
│   │
│   ├── bootstrap.php            # App initialization
│   ├── index.php                # Homepage
│   ├── login.php                # Login page
│   ├── register.php             # Registration
│   ├── forgot-password.php      # Password reset request
│   ├── reset-password.php       # Password reset form
│   ├── dashboard.php            # Customer dashboard
│   ├── profile.php              # User profile
│   └── logout.php               # Logout
│
└── storage/                      # Storage
    ├── cache/                   # Cache files
    └── uploads/                 # Additional uploads
```

---

## 👥 User Roles

### Customer
**Permissions:**
- View own orders and quotes
- Submit quote requests
- Upload deposit slips
- Create support tickets
- Upload documents
- Update profile
- View financial summaries

**Access:**
- Dashboard
- Orders
- Quotes
- Tickets
- Profile
- Gallery

### Staff
**Permissions:**
- All customer permissions
- View all orders
- Update order status
- Manage deposits (verify/reject)
- Respond to tickets
- View all customers
- Upload order documents

**Access:**
- Admin Dashboard
- All admin sections (view/edit)
- Cannot delete records
- Cannot access system settings

### Admin
**Permissions:**
- All staff permissions
- Delete records
- Manage users
- System settings
- Email configuration
- Full system access

**Access:**
- Complete system control
- User management
- System settings
- All CRUD operations

---

## 🔄 Order Workflow

### 1. Quote Request (Optional)
- Customer submits vehicle details
- Admin reviews and provides quote
- Customer can approve or decline

### 2. Order Creation
- Admin creates order (or converts from quote)
- Status: **Pending**
- Customer receives notification

### 3. Vehicle Purchase
- Admin marks as **Purchased**
- Purchase price added to costs
- Customer notified

### 4. Delivered to Port of Load
- Status: **Delivered to Port of Load**
- Vehicle transported to origin port
- Documentation prepared for shipping

### 5. Origin Customs Clearance
- Status: **Origin customs clearance**
- Export customs clearance at origin country
- Export documentation processed

### 6. Shipping
- Status: **Shipping**
- Vehicle en route to Ghana
- Regular tracking updates to customer

### 7. Arrived in Ghana
- Status: **Arrived in Ghana**
- Vehicle has arrived at Ghana port
- Ready for customs clearance

### 8. Ghana Customs Clearance
- Status: **Ghana Customs Clearance**
- Duty and clearing fees calculated
- Import documentation processed
- Customer pays any balance

### 9. Vehicle Inspection
- Status: **Inspection**
- Detailed inspection report uploaded
- Photos added to order

### 10. Repairs (if needed)
- Status: **Repair**
- Shop updates provided
- Repair costs tracked

### 11. Ready for Delivery
- Status: **Ready**
- Customer notified
- Delivery arranged

### 12. Delivery
- Status: **Delivered**
- Evidence of delivery uploaded
- Order complete

---

## 🔐 Security Features

### Authentication
- Session-based authentication
- Secure password hashing (bcrypt)
- Password strength requirements
- Account lockout after failed attempts
- Session timeout with redirect preservation

### Password Security
- Minimum 8 characters
- Uppercase, lowercase, and number requirements
- Real-time strength indicator
- Secure password reset with email verification
- Token expiration (1 hour)
- Single-use reset tokens

### Form Security
- CSRF tokens on all forms
- Token regeneration after validation
- XSS prevention with htmlspecialchars
- Input sanitization and validation
- File upload validation (type, size)

### Rate Limiting
- Login: 5 attempts per 15 minutes
- Password reset request: 3 attempts per 15 minutes
- Password reset: 5 attempts per 15 minutes

### Data Protection
- SQL injection prevention (PDO prepared statements)
- Parameterized queries throughout
- Foreign key constraints
- Database transactions for critical operations
- Input validation before database operations

### Access Control
- Role-based permissions
- Protected admin routes
- Session validation on every request
- Automatic logout on inactivity

---

## ⚙️ Configuration

### Email Notifications

Configure in `app/Notification.php` or via Admin Settings page:

```php
// Admin Settings Page allows configuration of:
- Email notifications enabled/disabled
- Event-specific notifications
- Status-specific notifications
- From name: "Andcorp Autos"
- From address: "noreply@andcorpautos.com"
- Reply-to: "info@andcorpautos.com"
```

### Database Connection

Edit `config/database.php`:

```php
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'NAME_OF_DB',
    'username' => getenv('DB_USERNAME') ?: 'NAME_OF_DB_USER',
    'password' => getenv('DB_PASSWORD') ?: 'PASSWORD_OF_DB_USER',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];
```

### Session Configuration

In `public/bootstrap.php`:

```php
ini_set('session.gc_maxlifetime', 7200);    // 2 hours
ini_set('session.cookie_lifetime', 7200);    // 2 hours
ini_set('session.cookie_secure', '1');       // HTTPS only
ini_set('session.cookie_httponly', '1');     // Prevent JavaScript access
ini_set('session.cookie_samesite', 'Lax');   // CSRF protection
```

---

## 🚀 Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions for:
- Shared hosting (cPanel/Namecheap)
- VPS/Dedicated servers
- Production configuration
- SSL setup
- Performance optimization

---

## 📱 Browser Support

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)
- Mobile browsers (iOS Safari, Chrome Mobile)

---

## 🐛 Known Issues

None currently. Please report issues to technical support.

---

## 🔄 Updates & Maintenance

### Recent Updates

#### v2.0.0 (Current)
- ✅ Added support ticket system
- ✅ Implemented password reset functionality
- ✅ Added password strength indicator
- ✅ Implemented rate limiting
- ✅ Enhanced email notifications with branding
- ✅ Added deposit verification workflow
- ✅ Improved financial summaries
- ✅ Added evidence of delivery document type
- ✅ Enhanced security features
- ✅ Improved mobile responsiveness
- ✅ Added customer search functionality
- ✅ Implemented activity logging

### Planned Features
- [ ] SMS notifications integration
- [ ] Payment gateway integration
- [ ] Advanced reporting dashboard
- [ ] Multi-language support
- [ ] Mobile app (iOS/Android)
- [ ] Real-time chat support
- [ ] Document e-signature
- [ ] API for third-party integrations

---

## 📞 Support

### Technical Support
- **Email**: info@andcorpautos.com
- **Phone**: +233 24 949 4091
- **Website**: [https://andcorpautos.com](https://andcorpautos.com)

### Documentation
- **Main Documentation**: README.md (this file)
- **Deployment Guide**: DEPLOYMENT.md
- **API Documentation**: Coming soon

### Bug Reports
Please include:
- Detailed description of the issue
- Steps to reproduce
- Browser and OS information
- Screenshots (if applicable)
- Error messages

---

## 📄 License

Proprietary - All rights reserved © 2025 Andcorp Autos

This software is the property of Andcorp Autos. Unauthorized copying, distribution, or use of this software is strictly prohibited.

---

## 👨‍💻 Development

### Requirements
- PHP 8.0+
- MySQL 5.7+
- Composer (for future dependencies)
- Git

### Development Setup
```bash
# Clone repository
git clone <repo-url>

# Configure database
cp config/database.example.php config/database.php
# Edit database.php with your credentials

# Import schema
mysql -u root -p < database/schema.sql

# Start development server
php -S localhost:8000 -t public/
```

### Code Standards
- PSR-12 coding standard
- Prepared statements for all database queries
- CSRF protection on all forms
- Input validation and sanitization
- Comprehensive error logging

---

## 🙏 Acknowledgments

- Bootstrap team for the excellent UI framework
- PHP community for security best practices
- All contributors and testers

---

## 📊 Statistics

- **Lines of Code**: ~15,000+
- **Database Tables**: 15+
- **Features**: 50+
- **User Roles**: 3
- **Order Statuses**: 12
- **Document Types**: 8
- **Support Priority Levels**: 4

---

**Built with ❤️ for Andcorp Autos**

*Last Updated: January 2025*
