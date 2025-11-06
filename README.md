# Car Dealership Management System

A comprehensive web application for managing on-demand car purchases from Copart and IAA auctions with complete tracking from purchase to delivery.

## Features

- **Customer Portal**: Track orders in real-time through all stages
- **Order Management**: Complete workflow from listing to delivery
- **Purchase Tracking**: Updates on Copart/IAA purchases
- **Shipping Updates**: Track shipment to Ghana
- **Customs & Clearing**: Manage duty and clearing fees
- **Inspection Reports**: Detailed vehicle inspection with photos
- **Repair Tracking**: Shop updates and repair progress
- **Notifications**: Email/SMS alerts for customers
- **Admin Dashboard**: Complete order and customer management

## Workflow Stages

1. **Listing Selection**: Customer selects vehicle from Copart/IAA
2. **Purchase**: Buy vehicle and provide updates
3. **Shipping**: Track shipment to Ghana with updates
4. **Customs Clearance**: Calculate and collect duty/clearing fees
5. **Inspection**: Thorough vehicle inspection with detailed report
6. **Repair/Shop**: Track repairs and fixes
7. **Delivery**: Final delivery to customer

## Technology Stack

- **Backend**: PHP 8.1+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Bootstrap 5
- **Authentication**: Session-based with role management

## Installation

### Prerequisites

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx)
- Composer (PHP package manager)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Andcorp-test
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` file with your database credentials:
   ```
   DB_HOST=localhost
   DB_DATABASE=car_dealership
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Create database**
   ```bash
   mysql -u root -p
   CREATE DATABASE car_dealership;
   exit;
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Seed initial data**
   ```bash
   php artisan db:seed
   ```

7. **Start development server**
   ```bash
   php artisan serve
   ```

8. **Access the application**
   - Customer Portal: http://localhost:8000
   - Admin Dashboard: http://localhost:8000/admin

## Default Credentials

**Admin Account**
- Email: admin@andcorp.com
- Password: admin123

**Test Customer Account**
- Email: customer@example.com
- Password: customer123

## Project Structure

```
├── app/
│   ├── Controllers/      # Application controllers
│   ├── Models/          # Database models
│   └── Middleware/      # Authentication & authorization
├── config/              # Configuration files
├── database/
│   ├── migrations/      # Database migrations
│   └── seeders/        # Database seeders
├── public/             # Public assets (CSS, JS, images)
├── resources/
│   └── views/          # Blade templates
├── routes/             # Application routes
└── storage/            # File uploads & logs
```

## Key Features Documentation

### Order Status Flow

1. **PENDING**: Order created, awaiting purchase
2. **PURCHASED**: Vehicle purchased from auction
3. **SHIPPING**: In transit to Ghana
4. **CUSTOMS**: Awaiting customs clearance
5. **INSPECTION**: Vehicle inspection in progress
6. **REPAIR**: In shop for repairs
7. **READY**: Ready for delivery
8. **DELIVERED**: Delivered to customer

### User Roles

- **Customer**: View own orders, track status, view reports
- **Staff**: Manage orders, update statuses, upload reports
- **Admin**: Full system access, user management, reports

## Configuration

### Email Notifications

Configure in `.env`:
```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### SMS Notifications (Optional)

Configure your SMS provider API credentials in `.env`:
```
SMS_API_KEY=your-api-key
SMS_SENDER_ID=ANDCORP
```

## Security

- Password hashing with bcrypt
- CSRF protection on all forms
- SQL injection prevention with prepared statements
- XSS protection
- Role-based access control

## Support

For support, email support@andcorp.com

## License

Proprietary - All rights reserved
