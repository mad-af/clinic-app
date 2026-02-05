# Clinic Management System

A comprehensive Clinic Management System built with **Laravel 12** and **FilamentPHP 3**. This application streamlines clinic operations ranging from patient registration and appointment scheduling to medical records, pharmacy inventory, and automated billing.

## 🚀 Key Features

### 🏥 Clinical Operations
- **Doctor Console**: A dedicated dashboard for doctors to manage daily appointments and perform examinations.
- **Patient Management**: Complete patient profiles and history.
- **Medical Records**: Digital recording of diagnoses, notes, and prescriptions.
- **Patient Visits**: Track patient visits, assigned doctors, and performed procedures.

### 📅 Scheduling & Appointments
- **Appointment Management**: Booking system with conflict detection.
- **Doctor Shifts**: Manage doctor working schedules to ensure accurate appointment booking.
- **Availability Checks**: Automated validation of doctor availability based on shifts and existing bookings.

### 💊 Pharmacy & Inventory
- **Medicine Management**: Master data for medicines.
- **Stock Control**: Real-time stock tracking with automated deduction upon prescription.
- **Stock Logs & Adjustments**: Comprehensive audit trail for all stock movements (adjustments, usage, refunds) including user tracking.

### 💰 Billing & Finance
- **Automated Invoicing**: Bills are automatically calculated based on:
  - Procedure costs
  - Doctor service fees
- **Procedure Management**: Manage list of medical procedures and their standard prices.
- **Invoice Tracking**: Monitor payment statuses (Unpaid/Paid).

## 🛠 Tech Stack

- **Framework**: [Laravel 12](https://laravel.com)
- **Admin Panel**: [FilamentPHP 3](https://filamentphp.com)
- **Database**: SQLite (Default) / MySQL
- **Frontend**: Blade / Livewire

## ⚙️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/clinic-app.git
   cd clinic-app
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database Setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```
   *(Note: The project uses SQLite by default. Configure `.env` if using MySQL/PostgreSQL)*

5. **Run the Application**
   ```bash
   npm run build
   php artisan serve
   ```

6. **Access the Admin Panel**
   Visit `http://127.0.0.1:8000/admin` and log in with your credentials.

## 📦 Modules Overview

| Module | Description |
|--------|-------------|
| **Appointments** | Schedule and view patient appointments. |
| **Doctors** | Manage doctor profiles, service fees, and working shifts. |
| **Patients** | Database of patient personal information. |
| **Patient Visits** | Operational flow for handling patient check-ins and billing. |
| **Medical Records** | Clinical notes and prescriptions linked to patients. |
| **Medicines** | Inventory of drugs with stock levels. |
| **Invoices** | Financial records generated from visits. |
| **Stock Adjustments** | Tools for manual stock corrections and auditing. |

## 🔒 Security & Auditing
- **Stock Security**: High-risk stock adjustments are logged with IP, User Agent, and User ID.
- **Role Separation**: Dedicated views for Doctors vs Administrators.

## 📄 License
The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
