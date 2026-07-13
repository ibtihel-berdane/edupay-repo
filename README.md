# Student Tuition Management System  EduPay+

A web application developed to simplify the management of student financial operations within educational institutions. The platform centralizes tuition fee tracking, online payment management, and scholarship (stipend) administration in a secure and user-friendly interface.

## Features

- Student account management
- Tuition fee invoicing and billing
- Payment tracking and history
- Online payment management
- Scholarship (stipend) management
- Financial dashboard and reporting
- Administrator authentication
- Search and filtering of student records
- Responsive web interface

## Objectives

The main objective of this application is to digitize and automate student financial management by:

- Reducing manual administrative work
- Improving payment tracking
- Providing real-time access to financial information
- Facilitating scholarship management
- Ensuring secure management of financial data

## Technologies Used

### Frontend
- HTML5
- CSS3
- JavaScript

### Backend
- PHP

### Database
- MySQL

### Development Tools
- XAMPP
- phpMyAdmin
- Visual Studio Code
  
## Project Structure

```
project/
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
│   └── database.php
│
├── includes/
│
├── pages/
│
├── uploads/
│
├── index.php
└── README.md
```

## Installation

1. Clone the repository.



2. Move the project to your web server directory (e.g., `htdocs` if using XAMPP).

3. Import the SQL database into MySQL using phpMyAdmin.

4. Configure the database connection in:

```php
config/database.php
```

5. Start Apache and MySQL.

6. Open your browser and visit:

```
http://localhost/edupay+/login.php
```

## Main Modules

### Student Management
- Student registration
- Student information updates
- Search and consultation

### Tuition Management
- Invoice generation
- Tuition fee tracking
- Outstanding balance monitoring

### Online Payments
- Payment registration
- Payment validation
- Transaction history

### Scholarship Management
- Scholarship assignment
- Stipend tracking
- Eligibility management

### Administration
- Secure login
- User management
- Financial reports
- Dashboard

## Database

The application relies on a MySQL relational database to store:

- Student information
- Tuition invoices
- Payments
- Scholarships
- User accounts
- Financial records

## Future Improvements

- Email notifications
- Payment gateway integration
- Student portal
- Multi-role access control
- Statistics dashboard
- Mobile application
- QR-code payment support

## Author

Developed as an academic project.

## License

This project is intended for educational purposes.
