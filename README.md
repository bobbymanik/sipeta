TVRI Broadcasting Reporting System
A comprehensive full-stack web application for managing broadcasting reports, user accounts, and system administration for TVRI (Television Republik Indonesia).

Features
User Management
3 User Levels: Leader, Admin, Technician
Role-based Access Control: Different permissions for each user level
Secure Authentication: Password hashing, session management, CSRF protection
Password Reset: Email-based password recovery system
Report Modules
Broadcasting Logbook: Daily broadcasting activities tracking
Downtime DVB T2 TX Riau: Transmission downtime reporting
Transmission Problem Report: Issue reporting with email notifications
Admin Features
User Account Management: Add, edit, deactivate users
Audit Trail: Complete change history tracking
Export Functionality: PDF and Excel export capabilities
Dropdown Management: Configure staff, programs, and transmission units
Email Notifications: Automated system notifications
Security Features
CSRF Protection: Secure form submissions
Rate Limiting: Login attempt protection
Security Headers: XSS, clickjacking protection
Input Sanitization: SQL injection prevention
Session Management: Secure session handling
Installation
Prerequisites
PHP 7.4 or higher
MySQL 5.7 or higher
Web server (Apache/Nginx)
Setup Instructions
Database Setup

# Create database and import schema
mysql -u root -p < database/schema.sql
mysql -u root -p tvri_reporting < database/seed_data.sql
Configuration

// Update config/database.php with your database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'tvri_reporting');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
File Permissions

# Set appropriate permissions
chmod 755 /path/to/project
chmod 644 /path/to/project/*.php
Web Server Configuration

Point document root to project directory
Enable PHP and MySQL modules
Configure URL rewriting if needed
Default User Accounts
Role	Email	Password
Leader	pimpinan@tvri.co.id	pimpinan123
Admin	admin@tvri.co.id	admin123
Technician	teknisi@tvri.co.id	teknisi123
User Permissions
Leader
✅ View all reports and dashboards
✅ Access audit trail
❌ Create, edit, or delete reports
❌ Manage users or system settings
Admin
✅ Full system access
✅ User management (add, edit, deactivate)
✅ Report management (view, delete, export)
✅ System configuration (dropdown options)
✅ Audit trail access
✅ Email notification management
Technician
✅ Create new reports
✅ View own submitted reports
❌ Edit or delete existing reports
❌ Access other users’ reports
❌ System administration features
File Structure
tvri-broadcasting-system/
├── auth/                   # Authentication files
│   ├── login.php
│   ├── logout.php
│   └── reset_password.php
├── config/                 # Configuration files
│   └── database.php
├── database/              # Database files
│   ├── schema.sql
│   └── seed_data.sql
├── dashboard/             # User dashboards
│   ├── admin.php
│   ├── leader.php
│   └── technician.php
├── reports/               # Report modules
│   ├── broadcasting_logbook.php
│   ├── downtime_dvb.php
│   ├── transmission_problem.php
│   ├── view_reports.php
│   └── view_report_details.php
├── admin/                 # Admin features
│   ├── user_management.php
│   ├── manage_options.php
│   ├── audit_trail.php
│   └── export_reports.php
├── includes/              # Shared functions
│   ├── functions.php
│   ├── auth_check.php
│   └── email_functions.php
├── index.html            # Login page
├── style.css             # Complete styling
├── script.js             # JavaScript functionality
└── README.md             # This file
Database Schema
Core Tables
users: User accounts and authentication
broadcasting_logbook: Daily broadcasting reports
downtime_dvb: Transmission downtime reports
transmission_problems: Problem reports with solutions
Configuration Tables
staff_options: Staff member listings
program_options: National and local programs
transmission_units: Transmission unit locations
System Tables
audit_trail: Change history tracking
email_notifications: Email log
password_reset_tokens: Password recovery tokens
API Endpoints
Authentication
POST /auth/login.php: User login
POST /auth/reset_password.php: Password reset request
GET /auth/logout.php: User logout
Reports
POST /reports/broadcasting_logbook.php: Submit broadcasting report
POST /reports/downtime_dvb.php: Submit downtime report
POST /reports/transmission_problem.php: Submit problem report
GET /reports/view_reports.php: View reports (filtered)
GET /reports/view_report_details.php: Get report details
Admin Functions
POST /admin/user_management.php: User CRUD operations
POST /admin/manage_options.php: Dropdown option management
GET /admin/export_reports.php: Export reports (PDF/Excel)
GET /admin/audit_trail.php: View audit history
Security Considerations
Password Security: All passwords are hashed using PHP’s password_hash()
CSRF Protection: All forms include CSRF tokens
SQL Injection Prevention: Prepared statements used throughout
XSS Protection: Input sanitization and output escaping
Session Security: Secure session configuration and timeout
Rate Limiting: Login attempt protection
Security Headers: Comprehensive HTTP security headers
Email Notifications
The system includes automated email notifications for:

New transmission problem reports (to admins)
User account creation/modification
Password reset requests
System maintenance announcements
Weekly report summaries
Export Features
PDF Export
Formatted reports with TVRI branding
Print-optimized layout
Complete report details
Excel Export
CSV format compatible with Excel
Structured data for analysis
Filterable columns
Customization
Adding New Report Types
Create new database table
Add report form PHP file
Update navigation and permissions
Add to export functionality
Modifying User Roles
Update permissions in includes/auth_check.php
Modify dashboard access controls
Update navigation menus
Email Templates
Customize email templates in includes/email_functions.php
Add new notification types as needed
Configure SMTP settings for production
Troubleshooting
Common Issues
Database Connection Error

Check database credentials in config/database.php
Ensure MySQL service is running
Verify database exists and user has permissions
Login Issues

Verify user accounts exist in database
Check password hashing compatibility
Clear browser cookies/session
Permission Denied

Check file permissions (755 for directories, 644 for files)
Verify web server user has access
Check PHP error logs
Email Not Sending

Configure SMTP settings for production
Check email function in includes/functions.php
Verify email addresses are valid
Debug Mode
Enable debug mode by adding to config/database.php:

define('DEBUG_MODE', true);
error_reporting(E_ALL);
ini_set('display_errors', 1);
Production Deployment
Security Checklist
[ ] Change default passwords
[ ] Configure HTTPS/SSL
[ ] Set up proper SMTP server
[ ] Enable error logging (disable display)
[ ] Configure database backups
[ ] Set up monitoring and alerts
[ ] Review file permissions
[ ] Configure firewall rules
Performance Optimization
Enable PHP OPcache
Configure database indexing
Set up caching (Redis/Memcached)
Optimize images and assets
Enable gzip compression
Support
For technical support or feature requests, please contact the development team or create an issue in the project repository.

License
This project is developed for TVRI (Television Republik Indonesia) internal use. All rights reserved.