const db = require('./db');

const createSettingsTable = `
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(50) PRIMARY KEY,
    setting_value TEXT
)`;

const insertDefaults = `
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
('deposit_amount', '50'),
('payment_methods', '["Efectivo", "Tarjeta"]'),
('email_reminders', 'true'),
('sms_reminders', 'false'),
('reminder_days', '2'),
('google_calendar_token', '')
`;

db.query(createSettingsTable, (err) => {
    if (err) {
        console.error('Error creating settings table:', err);
        process.exit(1);
    }
    console.log('Settings table ready.');

    db.query(insertDefaults, (err) => {
        if (err) {
            console.error('Error inserting defaults:', err);
            process.exit(1);
        }
        console.log('Default settings inserted.');
        process.exit(0);
    });
});
