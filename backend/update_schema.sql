USE crm_doctor;

CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
);

-- Insert default settings if not exist
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('deposit_amount', '50'),
('payment_methods', '["Efectivo", "Tarjeta"]'),
('email_reminders', 'true'),
('sms_reminders', 'false'),
('reminder_days', '2'),
('google_calendar_token', '');
