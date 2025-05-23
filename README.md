# Veterinary Clinic Management System

A comprehensive web-based system for managing veterinary clinic operations, appointments, and client records.

## Features

### Admin Dashboard
- Real-time statistics of client registrations
- Visual analytics with daily, weekly, and monthly trends
- Quick access to key functions

### Client Management
- View and manage client lists
- Pagination for large client databases
- Client registration date tracking

### Pet Management
- Register new pets
- Manage pet breeds
- Link pets to clients

### Appointment System
- Schedule new appointments
- View appointment history
- Real-time appointment status updates
- Notes and comments for each appointment

## Technology Stack

- **Frontend**:
  - HTML5
  - Tailwind CSS
  - JavaScript
  - Chart.js for analytics

- **Backend**:
  - PHP 7.4+
  - MySQL/MariaDB
  - PDO for database connections

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/structured_vet_system.git
```

2. Set up your XAMPP environment:
   - Place the project in `c:\xampp\htdocs\`
   - Start Apache and MySQL services

3. Database setup:
   - Import the provided SQL file into phpMyAdmin
   - Configure database connection in `app/config/Connection.php`

4. Access the system:
   - Navigate to `http://localhost/structured_vet_system/`

## Project Structure

```
structured_vet_system/
├── app/
│   ├── config/
│   │   ├── Auth.php
│   │   └── Connection.php
│   └── function/
│       └── admin/
├── resource/
│   ├── AHeader.php
│   ├── AFooter.php
│   ├── UHeader.php
│   └── UFooter.php
└── view/
    └── admin/
        ├── AdminDashboard.php
        ├── AdminClientList.php
        └── AdminPet.php
```

## Security Features

- User authentication and authorization
- Input sanitization
- PDO prepared statements
- Session management

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE.md file for details.

## Authors

- Your Name - *Initial work*

## Acknowledgments

- Tailwind CSS for the UI framework
- Chart.js for data visualization
- FontAwesome for icons
