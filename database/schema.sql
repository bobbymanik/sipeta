-- TVRI Broadcasting Reporting System Database Schema
CREATE DATABASE IF NOT EXISTS tvri_reporting;
USE tvri_reporting;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_level ENUM('Leader', 'Admin', 'Technician') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE
);

-- Staff options table
CREATE TABLE staff_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('shift_staff', 'report_staff') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Program options table
CREATE TABLE program_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('national', 'local') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transmission unit options table
CREATE TABLE transmission_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Broadcasting logbook table
CREATE TABLE broadcasting_logbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    report_date DATE NOT NULL,
    shift ENUM('Shift 1 (00.00-08.00)', 'Shift 2 (08.00-16.00)', 'Shift 3 (16.00-00.00)') NOT NULL,
    shift_staff JSON NOT NULL, -- Array of staff IDs
    national_programs JSON NOT NULL, -- Array of program IDs
    local_programs JSON NOT NULL, -- Array of program IDs
    audio_quality ENUM('Normal', 'Problem') NOT NULL,
    video_quality ENUM('Normal', 'Problem') NOT NULL,
    problem_duration VARCHAR(255),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Downtime DVB T2 TX table
CREATE TABLE downtime_dvb (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transmission_units JSON NOT NULL, -- Array of unit IDs
    downtime_start TIME NOT NULL,
    downtime_finish TIME NOT NULL,
    total_downtime_minutes INT GENERATED ALWAYS AS (
        TIME_TO_SEC(TIMEDIFF(downtime_finish, downtime_start)) / 60
    ) STORED,
    downtime_problem TEXT NOT NULL,
    report_name JSON NOT NULL, -- Array of staff IDs
    report_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Transmission problem reports table
CREATE TABLE transmission_problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    shift ENUM('Shift 1 (00.00-08.00)', 'Shift 2 (08.00-16.00)', 'Shift 3 (16.00-00.00)') NOT NULL,
    shift_staff JSON NOT NULL, -- Array of staff IDs
    transmission_unit INT NOT NULL,
    problem_time TIME NOT NULL,
    problem_description TEXT NOT NULL,
    solution_description TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (transmission_unit) REFERENCES transmission_units(id)
);

-- Audit trail table
CREATE TABLE audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    changed_by INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Password reset tokens table
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Email notifications log
CREATE TABLE email_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed') DEFAULT 'sent'
);

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_broadcasting_date ON broadcasting_logbook(report_date);
CREATE INDEX idx_downtime_date ON downtime_dvb(report_date);
CREATE INDEX idx_transmission_date ON transmission_problems(report_date);
CREATE INDEX idx_audit_table_record ON audit_trail(table_name, record_id);