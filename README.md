# Laboratory Deployment & Inventory Management System

A comprehensive web-based system for managing laboratory equipment inventory and deployment operations. Built with PHP, MySQL, and Bootstrap for a modern, responsive user experience.

## Features

### 🔐 User Authentication & Management
- Secure login/logout system
- User registration with role-based access (Admin, Manager, User)
- Session management with security features
- Password hashing and CSRF protection

### 📦 Inventory Management
- Complete CRUD operations for inventory items
- Item categorization and classification
- Multiple status tracking (Available, Deployed, Maintenance, Retired)
- Supplier and location management
- Image upload support for items
- Condition tracking and maintenance logs
- Serial number and warranty tracking

### 🚛 Deployment System
- Create and manage deployment projects
- Equipment checkout/check-in tracking
- Location-based deployment tracking
- Priority levels and status management
- Resource allocation and scheduling
- Duration tracking and cost management

### 📊 Dashboard & Analytics
- Real-time statistics and KPIs
- Interactive charts and graphs
- Recent activity tracking
- Quick action panels
- Responsive data tables

### 📋 Reporting Features
- Inventory status reports
- Deployment history and analytics
- Maintenance schedules and logs
- CSV export functionality
- Print-friendly layouts

### 🎨 Modern UI/UX
- Bootstrap 5 responsive design
- Dark/Light theme elements
- Interactive data tables with DataTables
- Chart.js for data visualization
- Font Awesome icons
- Mobile-friendly interface

## System Requirements

- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Browser**: Modern browser with JavaScript enabled

## Installation Instructions

### 1. Setup Environment

#### Using XAMPP (Recommended for Development)
1. Download and install [XAMPP](https://www.apachefriends.org/)
2. Start Apache and MySQL services
3. Navigate to `http://localhost/phpmyadmin`

#### Manual Setup
1. Install Apache/Nginx web server
2. Install PHP 7.4+ with required extensions:
   - PDO MySQL
   - GD (for image processing)
   - JSON
   - Session
   - CURL
3. Install MySQL/MariaDB

### 2. Database Setup

1. **Create Database**:
   ```sql
   CREATE DATABASE deployment_system;
   ```

2. **Import Database Schema**:
   - Open phpMyAdmin or your preferred MySQL client
   - Select the `deployment_system` database
   - Import the `database.sql` file
   - This will create all necessary tables and default data

3. **Verify Installation**:
   ```sql
   USE deployment_system;
   SHOW TABLES;
   ```
   You should see 12 tables created.

### 3. File Configuration

1. **Copy Files**:
   ```bash
   # For XAMPP
   cp -r * /xampp/htdocs/Deployment/Deployment/
   
   # For manual setup
   cp -r * /var/www/html/deployment/
   ```

2. **Set File Permissions**:
   ```bash
   chmod 755 assets/images/uploads/
   chmod 644 includes/config.php
   ```

3. **Configure Database Connection**:
   Edit `includes/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USERNAME', 'root');
   define('DB_PASSWORD', ''); // Set your MySQL password
   define('DB_NAME', 'deployment_system');
   ```

### 4. Access the System

1. **Open in Browser**:
   ```
   http://localhost/Deployment/Deployment/
   ```

2. **Default Login Credentials**:
   - **Username**: admin
   - **Password**: password

3. **First-time Setup**:
   - Login with default credentials
   - Change the default password immediately
   - Add your organization's locations, categories, and users
   - Configure system settings

## File Structure

```
Deployment/
├── assets/
│   ├── css/
│   │   └── style.css          # Custom styles
│   ├── js/                    # Custom JavaScript files
│   └── images/
│       └── uploads/           # User-uploaded images
├── includes/
│   ├── config.php            # Database and site configuration
│   ├── database.php          # Database connection class
│   ├── functions.php         # Utility functions
│   ├── header.php            # Common header template
│   └── footer.php            # Common footer template
├── admin/                    # Admin-only pages
├── reports/                  # Report generation files
├── database.sql              # Database schema and initial data
├── index.php                 # Main entry point
├── login.php                 # User authentication
├── register.php              # User registration
├── dashboard.php             # Main dashboard
├── inventory.php             # Inventory listing
├── inventory_add.php         # Add new inventory item
├── deployments.php           # Deployment listing
├── deployment_add.php        # Create new deployment
└── README.md                 # This file
```

## Configuration Options

### Database Settings (`includes/config.php`)
```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'deployment_system');

// Site Configuration
define('SITE_NAME', 'Laboratory Deployment & Inventory System');
define('SITE_URL', 'http://localhost/Deployment/Deployment/');
define('ADMIN_EMAIL', 'admin@labsystem.com');

// Security Configuration
define('PASSWORD_HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_TIMEOUT', 3600); // 1 hour

// Upload Configuration
define('UPLOAD_DIR', 'assets/images/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
```

## User Roles & Permissions

### Administrator
- Full system access
- User management
- System configuration
- Delete permissions
- Reports and analytics

### Manager
- Inventory management
- Deployment operations
- User assignments
- Reports (limited)

### User
- View inventory
- Create deployments (assigned)
- Update assigned items
- Basic reporting

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` function
- **CSRF Protection**: Form tokens prevent cross-site request forgery
- **SQL Injection Prevention**: PDO prepared statements
- **Session Security**: Timeout and regeneration
- **Input Validation**: Server-side validation for all inputs
- **XSS Prevention**: Output escaping with `htmlspecialchars()`

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check MySQL service is running
   - Verify database credentials in `config.php`
   - Ensure database exists and user has permissions

2. **File Upload Issues**:
   - Check upload directory permissions: `chmod 755 assets/images/uploads/`
   - Verify PHP file upload settings in `php.ini`
   - Check disk space availability

3. **Session Problems**:
   - Ensure session directory is writable
   - Check PHP session configuration
   - Clear browser cookies and cache

4. **Permission Denied Errors**:
   - Set proper file permissions: `chmod 644` for files, `chmod 755` for directories
   - Check web server user ownership

### Performance Optimization

1. **Enable PHP OPcache** (production):
   ```ini
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.interned_strings_buffer=8
   opcache.max_accelerated_files=4000
   ```

2. **MySQL Optimization**:
   - Add indexes on frequently queried columns
   - Regular database maintenance
   - Connection pooling for high traffic

3. **Caching**:
   - Implement Redis/Memcached for session storage
   - Use browser caching for static assets
   - Consider implementing application-level caching

## Development

### Adding New Features

1. **Database Changes**:
   - Add migration scripts in SQL format
   - Update the main `database.sql` file
   - Document schema changes

2. **New Pages**:
   - Follow the existing file structure
   - Include proper authentication checks
   - Use the header/footer templates
   - Implement CSRF protection for forms

3. **Styling**:
   - Add custom styles to `assets/css/style.css`
   - Follow Bootstrap conventions
   - Ensure responsive design

### Code Standards

- Use PSR-4 autoloading standards
- Follow PHP-FIG coding standards
- Comment complex functions
- Validate and sanitize all inputs
- Use prepared statements for database queries

## Support & Maintenance

### Regular Maintenance Tasks

1. **Database Backup**:
   ```bash
   mysqldump -u username -p deployment_system > backup_$(date +%Y%m%d).sql
   ```

2. **Log Rotation**:
   - Monitor PHP error logs
   - Archive old activity logs
   - Clear temporary files

3. **Security Updates**:
   - Keep PHP and MySQL updated
   - Review user access periodically
   - Monitor system logs for suspicious activity

### Backup Strategy

1. **Database**: Daily automated backups
2. **Files**: Weekly full backup of application files
3. **Images**: Include uploaded files in backup routine
4. **Configuration**: Backup configuration files separately

## License

This project is developed for laboratory management purposes. Please ensure compliance with your organization's software policies.

## Changelog

### Version 1.0.0
- Initial release with core functionality
- User authentication and management
- Inventory management system
- Deployment tracking
- Dashboard and reporting
- Responsive web interface

---

For technical support or feature requests, please contact your system administrator.