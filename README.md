# Transportation Reservation Platform

A web-based platform for managing transportation reservations between clients and transporters. This system allows clients to request transport services, transporters to manage their vehicles and accept requests, and administrators to oversee the entire platform.

## Features

### User Roles
- **Clients**: Can request transportation services, view reservation history, and manage their profile
- **Transporters**: Can manage their vehicle fleet, accept/reject transport requests, and track earnings
- **Administrators**: Full system oversight, user management, and platform monitoring

### Core Functionality
- User authentication and authorization
- Vehicle management system
- Reservation request and approval workflow
- Real-time notifications
- Email notifications via SMTP
- Earnings tracking for transporters
- Responsive dashboard for each user role

### Technical Features
- Secure password hashing
- Role-based access control
- PDO database abstraction
- Email queue system
- Environment-based configuration
- Input validation and sanitization

## Technologies Used

- **Backend**: PHP 8.0+
- **Database**: MySQL 8.0+
- **Email**: PHPMailer
- **Environment Management**: vlucas/phpdotenv
- **Frontend**: HTML5, CSS3, JavaScript
- **Architecture**: MVC-inspired structure

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer (PHP dependency manager)
- Web server (Apache/Nginx) or XAMPP/WAMP

### Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd Reservation-transport
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Database Setup**
   - Create a MySQL database named `transport`
   - Import the database schema:
     ```bash
     mysql -u root -p transport < core/config/database.sql
     ```
   - Or use phpMyAdmin to import `core/config/database.sql`

4. **Environment Configuration**
   - Copy `.env` file and update the following variables:
     ```env
     EMAIL_HOST=smtp.gmail.com
     EMAIL_PORT=587
     EMAIL_USER=your-email@gmail.com
     EMAIL_PASS=your-app-password
     APP_URL=http://localhost/Reservation-transport
     ```

5. **Web Server Configuration**
   - Ensure the project is served from `c:/xampp/htdocs/Reservation-transport` (for XAMPP)
   - Or configure your web server to point to the project root
   - Make sure `.htaccess` is enabled for URL rewriting

6. **Permissions**
   - Ensure the web server has write permissions for necessary directories

## Usage

### Accessing the Application
- Navigate to `http://localhost/Reservation-transport`
- The system will redirect to login page

### Default Accounts
The database includes seeded test accounts:

**Admin Account:**
- Email: `admin@transport.local`
- Password: `password123`

**Client Accounts:**
- Alice: `alice@client.local` / `password123`
- Bob: `bob@client.local` / `password123`

**Transporter Accounts:**
- Charlie: `charlie@transporter.local` / `password123`
- Dave: `dave@transporter.local` / `password123`

### Workflow
1. **Clients** log in and create transport requests
2. **Transporters** view available requests and can accept them using their vehicles
3. **Notifications** are sent to both parties
4. **Earnings** are tracked for completed transports
5. **Admins** can monitor all activities and manage users

## Project Structure

```
Reservation-transport/
├── account/          # User account management pages
├── admin/            # Administrator dashboard and management
├── api/              # API endpoints and background workers
├── auth/             # Authentication pages (login, register, etc.)
├── client/           # Client dashboard and reservation management
├── core/             # Core application logic
│   ├── config/       # Database and configuration files
│   └── functions/    # Business logic functions
├── includes/         # Reusable PHP includes
├── public/           # Public assets (CSS, JS)
├── system/           # System pages (404, unauthorized, etc.)
├── transporter/      # Transporter dashboard and vehicle management
├── composer.json     # PHP dependencies
├── index.php         # Application entry point
└── README.md         # This file
```

## Database Schema

The application uses MySQL with the following main tables:
- `users` - User accounts with role-based access
- `vehicles` - Transporter vehicle information
- `reservations` - Transport service requests
- `earnings` - Transporter earnings tracking
- `notifications` - User notification system

## Security Features

- Password hashing using bcrypt
- Prepared statements to prevent SQL injection
- Input validation and sanitization
- Role-based access control
- Session management
- CSRF protection on forms

## Development

### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable and function names
- Include PHPDoc comments for functions and classes

### Adding New Features
1. Plan the feature and identify affected files
2. Update database schema if needed
3. Implement backend logic in appropriate function files
4. Create/update frontend templates
5. Test thoroughly across all user roles

## Troubleshooting

### Common Issues
- **Database connection errors**: Check database credentials in `core/config/database.php`
- **Email not sending**: Verify SMTP settings in `.env` file
- **Permission errors**: Ensure web server has proper file permissions
- **Page not found**: Check `.htaccess` configuration and URL rewriting

### Logs
- Check PHP error logs for application errors
- Database errors are logged to PHP error log
- Email sending status can be monitored in the queue worker

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support or questions, please contact the development team or create an issue in the repository.