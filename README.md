# 🏥 QueueLess – Hospital Queue Management System

## 📌 Project Overview

QueueLess is a web-based Hospital Queue Management System designed to simplify patient registration, appointment management, and hospital queue operations. The system aims to reduce manual queue handling, minimize patient waiting time, and improve hospital workflow efficiency.

## 🎯 Objectives

- Reduce patient waiting time through digital queue management.
- Simplify patient registration and appointment handling.
- Manage patient tokens and consultation queues.
- Organize hospital operations through a centralized system.
- Provide an easy-to-use interface for hospital staff and patients.

## 🛠️ Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Development Environment:** XAMPP
- **Version Control:** Git and GitHub
- **Deployment:** Vercel (PHP execution compatibility must be configured or verified)

## ✨ Features

- **Patient Management:** Register and manage patient information.
- **User Management:** Organize access for different user roles.
- **Appointment Management:** Manage patient appointments.
- **Token Management:** Assign and manage patient tokens.
- **Queue Management:** Track and update patient queue status.
- **Admin Dashboard:** Manage hospital operations.
- **Database Integration:** Store and retrieve application data using MySQL.

*Note: Keep only the features that are implemented in your project.*

## 👥 System Modules

### 1. Admin Module
- Manage users and system operations.
- Monitor patient and appointment information.
- Manage hospital queue activities.

### 2. Receptionist Module
- Register new patients.
- Manage patient appointments.
- Generate or assign patient tokens.

### 3. Doctor Module
- View patient information.
- Monitor the consultation queue.
- Update consultation status.

### 4. Patient Module
- Access relevant appointment information.
- Check token or queue status.

*The availability of each module depends on your actual implementation.*

## ⚙️ Installation and Setup

Follow these steps to run QueueLess locally.

### Step 1: Install XAMPP

Install XAMPP and start the following services:

- Apache
- MySQL

### Step 2: Clone the Repository

```bash
git clone YOUR_GITHUB_REPOSITORY_URL
```

Replace `YOUR_GITHUB_REPOSITORY_URL` with your actual GitHub repository URL.

### Step 3: Move the Project

Place the project folder inside the XAMPP `htdocs` directory.

Example:

```text
C:/xampp/htdocs/QueueLess/
```

### Step 4: Configure the Database

1. Open phpMyAdmin.
2. Create the required MySQL database.
3. Import your project's SQL file, if available.
4. Update the database connection settings in your PHP configuration file.

### Step 5: Run the Project

Open your browser and visit:

```text
http://localhost/QueueLess/
```

Adjust the URL if your project folder or entry file has a different name.

## 🗄️ Database

QueueLess uses MySQL to store and manage application data.

Depending on the implemented features, the database may contain tables for:

- Users
- Patients
- Doctors
- Appointments
- Tokens
- Queue records

The actual tables depend on your database schema.

## 🚀 Deployment

The project has been deployed to Vercel for online access.

**Important:** Traditional PHP applications require a server or runtime that executes PHP. If clicking a button downloads files such as `users.php`, verify that your deployment is configured to execute PHP rather than serve the files as static content.

Also ensure that the production database is hosted and accessible securely. A local XAMPP database is not automatically available to a deployed website.

## 🔐 Security Considerations

- Validate user input on the server.
- Use prepared statements for database queries.
- Store passwords securely using password hashing.
- Restrict access according to user roles.
- Never publish database credentials or secrets in a public repository.
- Protect sensitive patient information.

## 🔮 Future Enhancements

- Real-time queue status updates.
- SMS or email notifications.
- Online appointment booking.
- Estimated waiting-time calculation.
- Improved reporting and analytics.
- Mobile-friendly user interface.

## 👨‍💻 Project Information

**Project Name:** QueueLess – Hospital Queue Management System

**Project Type:** Web Application

**Domain:** Healthcare Management

**Technologies:** HTML, CSS, JavaScript, PHP, MySQL

## 📄 Resume Description

Developed QueueLess, a web-based Hospital Queue Management System using PHP, MySQL, HTML, CSS, and JavaScript to organize patient registration and queue operations. Implemented database-driven functionality and the project modules completed during development.
