CREATE DATABASE IF NOT EXISTS crm_doctor;
USE crm_doctor;

CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    email VARCHAR(255),
    birthdate DATE,
    address TEXT,
    notes TEXT,
    registration_date DATE DEFAULT (CURRENT_DATE)
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    type VARCHAR(50) NOT NULL,
    notes TEXT,
    deposit BOOLEAN DEFAULT TRUE,
    deposit_paid BOOLEAN DEFAULT FALSE,
    status ENUM('pendiente', 'confirmada', 'cancelada', 'completada') DEFAULT 'pendiente',
    google_calendar_sync BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    upload_date DATE DEFAULT (CURRENT_DATE),
    size VARCHAR(50),
    file_path VARCHAR(255),
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Insert sample data
INSERT INTO patients (name, phone, email, birthdate, address, notes, registration_date) VALUES 
('Ana García', '555-1234', 'ana@example.com', '1985-05-15', 'Calle Principal 123', 'Alergia a la penicilina', '2023-06-10'),
('Juan López', '555-5678', 'juan@example.com', '1990-08-22', 'Avenida Central 456', 'Historial de hipertensión', '2023-07-05'),
('María González', '555-9012', 'maria@example.com', '1978-12-03', 'Boulevard Norte 789', 'Diabetes tipo 2', '2023-07-20'),
('Carlos Rodríguez', '555-3456', 'carlos@example.com', '1995-02-28', 'Plaza Sur 101', 'Ninguna', '2023-08-01');

INSERT INTO appointments (patient_id, date, time, type, notes, deposit, deposit_paid, status, google_calendar_sync) VALUES 
(1, '2023-08-15', '10:00', 'Consulta', 'Dolor de muelas', TRUE, TRUE, 'confirmada', TRUE),
(2, '2023-08-15', '14:30', 'Limpieza', 'Limpieza dental regular', TRUE, TRUE, 'confirmada', TRUE),
(3, '2023-08-16', '11:00', 'Revisión', 'Revisión de ortodoncia', TRUE, FALSE, 'pendiente', FALSE),
(4, '2023-08-17', '16:00', 'Extracción', 'Extracción de muela del juicio', TRUE, TRUE, 'confirmada', TRUE),
(1, '2023-08-10', '09:30', 'Consulta', 'Seguimiento de tratamiento', TRUE, TRUE, 'completada', TRUE),
(2, '2023-08-05', '15:00', 'Limpieza', 'Primera limpieza del año', TRUE, TRUE, 'completada', TRUE);
