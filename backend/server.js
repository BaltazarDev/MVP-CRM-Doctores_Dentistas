const express = require('express');
const cors = require('cors');
const bodyParser = require('body-parser');
const multer = require('multer');
const path = require('path');
const db = require('./db');
require('dotenv').config();
const { google } = require('googleapis');

const app = express();
const port = 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// Configure Multer
const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        cb(null, 'uploads/')
    },
    filename: function (req, file, cb) {
        // Sanitize filename to avoid weird chars
        const safeName = file.originalname.replace(/[^a-zA-Z0-9.-]/g, '_');
        cb(null, Date.now() + '-' + safeName)
    }
});
const upload = multer({ storage: storage });

// Google Calendar Config
const oauth2Client = new google.auth.OAuth2(
    process.env.CLIENT_ID || 'dummy_client_id',
    process.env.CLIENT_SECRET || 'dummy_client_secret',
    process.env.REDIRECT_URI || 'http://localhost:3000/auth/google/callback'
);

// Helper to get stored token
function getStoredToken() {
    return new Promise((resolve) => {
        db.query('SELECT setting_value FROM settings WHERE setting_key = "google_calendar_token"', (err, results) => {
            if (err || results.length === 0 || !results[0].setting_value) resolve(null);
            else resolve(JSON.parse(results[0].setting_value));
        });
    });
}

// --- API Endpoints ---

// Dashboard Stats
app.get('/api/dashboard', (req, res) => {
    const p1 = new Promise((resolve, reject) => db.query('SELECT COUNT(*) as c FROM patients', (e, r) => e ? reject(e) : resolve(r[0].c)));
    const today = new Date().toISOString().split('T')[0];
    const p2 = new Promise((resolve, reject) => db.query("SELECT COUNT(*) as c FROM appointments WHERE date = ? AND status != 'cancelada'", [today], (e, r) => e ? reject(e) : resolve(r[0].c)));
    const p3 = new Promise((resolve, reject) => db.query("SELECT COUNT(*) as c FROM appointments WHERE deposit_paid = 1", (e, r) => e ? reject(e) : resolve(r[0].c)));
    const date = new Date(), fd = new Date(date.getFullYear(), date.getMonth(), 1).toISOString().split('T')[0], ld = new Date(date.getFullYear(), date.getMonth() + 1, 0).toISOString().split('T')[0];
    const p4 = new Promise((resolve, reject) => db.query("SELECT COUNT(*) as c FROM appointments WHERE status = 'completada' AND date BETWEEN ? AND ?", [fd, ld], (e, r) => e ? reject(e) : resolve(r[0].c)));

    Promise.all([p1, p2, p3, p4])
        .then(([patients, todayApp, deposits, revenue]) => {
            // Get deposit amount setting to calculate accurate totals (if needed, here hardcoded logic persists)
            res.json({ totalPatients: patients, todayAppointments: todayApp, totalDeposits: deposits * 50, monthRevenue: revenue * 250 });
        })
        .catch(err => res.status(500).json({ error: err.message }));
});

// Patients
app.get('/api/patients', (req, res) => {
    db.query('SELECT * FROM patients ORDER BY name', (err, results) => {
        if (err) return res.status(500).send(err);
        res.json(results);
    });
});
app.post('/api/patients', (req, res) => {
    const { name, phone, email, birthdate, address, notes } = req.body;
    db.query('INSERT INTO patients (name, phone, email, birthdate, address, notes) VALUES (?,?,?,?,?,?)', [name, phone, email, birthdate, address, notes], (err, r) => {
        if (err) return res.status(500).send(err);
        res.json({ id: r.insertId, ...req.body });
    });
});
app.delete('/api/patients/:id', (req, res) => {
    db.query('DELETE FROM patients WHERE id = ?', [req.params.id], (err) => {
        if (err) return res.status(500).send(err);
        res.json({ message: 'Deleted' });
    });
});

// Appointments
app.get('/api/appointments', (req, res) => {
    db.query(`SELECT a.*, p.name as patientName FROM appointments a JOIN patients p ON a.patient_id = p.id ORDER BY a.date DESC, a.time ASC`, (err, results) => {
        if (err) return res.status(500).send(err);
        const fmt = results.map(a => ({
            ...a, deposit: !!a.deposit, depositPaid: !!a.deposit_paid, deposit_paid: !!a.deposit_paid, googleCalendarSync: !!a.google_calendar_sync
        }));
        res.json(fmt);
    });
});
app.post('/api/appointments', async (req, res) => {
    const { patientId, date, time, type, notes, deposit, depositPaid } = req.body;

    try {
        let amount = 50; // Default
        if (deposit) {
            // Get amount from settings
            const settings = await new Promise((resolve) => {
                db.query('SELECT setting_value FROM settings WHERE setting_key = "deposit_amount"', (err, r) => {
                    if (err || !r.length) resolve(null);
                    else resolve(r[0].setting_value);
                });
            });
            if (settings) amount = parseFloat(settings) || 50;
        }

        const isPaid = deposit && depositPaid;

        // Insert Appointment
        db.query('INSERT INTO appointments (patient_id, date, time, type, notes, deposit, deposit_paid, status) VALUES (?,?,?,?,?,?,?,?)',
            [patientId, date, time, type, notes, deposit ? 1 : 0, isPaid ? 1 : 0, 'pendiente'],
            (err, r) => {
                if (err) return res.status(500).send(err);
                const appId = r.insertId;

                // If Paid, register in payments table
                if (isPaid) {
                    db.query('INSERT INTO payments (patient_id, amount, date, concept, type) VALUES (?,?,?,?,?)',
                        [patientId, amount, new Date().toISOString().split('T')[0], `Anticipo Cita ${date} - ${type}`, 'Anticipo'],
                        (err2) => {
                            if (err2) console.error('Error auto-registering payment:', err2);
                        });
                }

                res.json({ id: appId, message: 'Created' });
            });
    } catch (e) {
        res.status(500).send(e.message);
    }
});
app.put('/api/appointments/:id', (req, res) => {
    const { status, depositPaid, googleCalendarSync } = req.body;
    let sql = 'UPDATE appointments SET ';
    const vals = [];
    if (status) { sql += 'status=?,'; vals.push(status); }
    if (depositPaid !== undefined) { sql += 'deposit_paid=?,'; vals.push(depositPaid ? 1 : 0); }
    if (googleCalendarSync !== undefined) { sql += 'google_calendar_sync=?,'; vals.push(googleCalendarSync ? 1 : 0); }
    sql = sql.slice(0, -1) + ' WHERE id=?';
    vals.push(req.params.id);
    db.query(sql, vals, (err) => {
        if (err) return res.status(500).send(err);
        res.json({ message: 'Updated' });
    });
});

// Files
app.get('/api/files/patient/:id', (req, res) => {
    db.query('SELECT * FROM files WHERE patient_id = ?', [req.params.id], (err, r) => err ? res.status(500).send(err) : res.json(r));
});
app.post('/api/upload', upload.single('file'), (req, res) => {
    if (!req.file) return res.status(400).send('No file');
    const { patientId } = req.body;
    const size = (req.file.size / 1024 / 1024).toFixed(2) + ' MB';
    const type = req.file.originalname.split('.').pop();
    const cleanPath = req.file.path.replace(/\\/g, '/'); // Normalize path for Windows/Web

    db.query('INSERT INTO files (patient_id, file_name, file_type, size, file_path) VALUES (?,?,?,?,?)',
        [patientId, req.file.originalname, type, size, cleanPath], (err, r) => {
            if (err) return res.status(500).send(err);
            res.json({ id: r.insertId, file_name: req.file.originalname, size, file_path: cleanPath });
        });
});

// Settings
app.get('/api/settings', (req, res) => {
    db.query('SELECT * FROM settings', (err, results) => {
        if (err) return res.status(500).send(err);
        const settings = {};
        results.forEach(row => {
            try { settings[row.setting_key] = JSON.parse(row.setting_value); }
            catch (e) { settings[row.setting_key] = row.setting_value; }
        });
        res.json(settings);
    });
});
app.post('/api/settings', (req, res) => {
    const settings = req.body;
    const queries = Object.keys(settings).map(key => {
        const val = typeof settings[key] === 'object' ? JSON.stringify(settings[key]) : settings[key];
        return new Promise((resolve, reject) => {
            db.query('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?',
                [key, val, val], (err) => err ? reject(err) : resolve());
        });
    });
    Promise.all(queries).then(() => res.json({ message: 'Saved' })).catch(e => res.status(500).send(e));
});

// Google Calendar Logic
app.get('/auth/google', (req, res) => {
    const url = oauth2Client.generateAuthUrl({
        access_type: 'offline',
        scope: ['https://www.googleapis.com/auth/calendar']
    });
    res.redirect(url);
});

app.get('/auth/google/callback', async (req, res) => {
    const { code } = req.query;
    try {
        const { tokens } = await oauth2Client.getToken(code);
        // Save tokens to DB
        await new Promise((resolve, reject) => {
            const val = JSON.stringify(tokens);
            db.query('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?',
                ['google_calendar_token', val, val], (err) => err ? reject(err) : resolve());
        });
        res.send('Authentication successful! You can close this window.');
    } catch (error) {
        console.error('Error retrieving tokens:', error);
        res.status(500).send('Authentication failed');
    }
});

app.get('/api/calendar/status', async (req, res) => {
    const token = await getStoredToken();
    res.json({ connected: !!token });
});

app.post('/api/calendar/sync', async (req, res) => {
    const token = await getStoredToken();
    if (!token) return res.status(401).json({ message: 'Not connected to Google Calendar' });

    oauth2Client.setCredentials(token);
    const calendar = google.calendar({ version: 'v3', auth: oauth2Client });

    // Get unsynced appointments
    db.query('SELECT a.*, p.name as patientName, p.email as patientEmail FROM appointments a JOIN patients p ON a.patient_id = p.id WHERE a.google_calendar_sync = 0 AND a.status != "cancelada"', async (err, appointments) => {
        if (err) return res.status(500).send(err);

        let successCount = 0;
        for (const appt of appointments) {
            const startDateTime = new Date(`${appt.date}T${appt.time}`);
            const endDateTime = new Date(startDateTime.getTime() + 60 * 60 * 1000); // 1 hour duration

            const event = {
                summary: `${appt.type} - ${appt.patientName}`,
                description: appt.notes || 'Consulta médica',
                start: { dateTime: startDateTime.toISOString() },
                end: { dateTime: endDateTime.toISOString() },
            };

            try {
                await calendar.events.insert({
                    calendarId: 'primary',
                    resource: event,
                });
                // Mark as synced
                await new Promise((resolve) => db.query('UPDATE appointments SET google_calendar_sync = 1 WHERE id = ?', [appt.id], resolve));
                successCount++;
            } catch (e) {
                console.error('Calendar insert error:', e);
                // Continue with next
            }
        }
        res.json({ synced: successCount, total: appointments.length });
    });
});

// Payments
app.get('/api/payments', (req, res) => {
    const { patientId } = req.query;
    let sql = 'SELECT y.*, p.name as patientName FROM payments y JOIN patients p ON y.patient_id = p.id';
    const params = [];
    if (patientId) {
        sql += ' WHERE y.patient_id = ?';
        params.push(patientId);
    }
    sql += ' ORDER BY y.date DESC';
    db.query(sql, params, (err, results) => {
        if (err) return res.status(500).send(err);
        res.json(results);
    });
});

app.post('/api/payments', (req, res) => {
    const { patientId, amount, date, concept, type } = req.body;
    db.query('INSERT INTO payments (patient_id, amount, date, concept, type) VALUES (?,?,?,?,?)',
        [patientId, amount, date, concept, type], (err, r) => {
            if (err) return res.status(500).send(err);
            res.json({ id: r.insertId, message: 'Payment recorded' });
        });
});

app.delete('/api/payments/:id', (req, res) => {
    db.query('DELETE FROM payments WHERE id = ?', [req.params.id], (err) => {
        if (err) return res.status(500).send(err);
        res.json({ message: 'Deleted' });
    });
});

// Dashboard Income Stats
app.get('/api/dashboard/income', (req, res) => {
    const { start, end } = req.query; // Expect YYYY-MM-DD
    let startDate = start;
    let endDate = end;

    if (!startDate || !endDate) {
        // Default to this month
        const now = new Date();
        startDate = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        endDate = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().split('T')[0];
    }

    const p1 = new Promise((resolve, reject) => {
        db.query("SELECT SUM(amount) as total FROM payments WHERE date BETWEEN ? AND ?", [startDate, endDate], (e, r) => {
            if (e) return reject(e);
            resolve((r && r[0] && r[0].total) ? r[0].total : 0);
        });
    });

    const p2 = new Promise((resolve, reject) => {
        db.query("SELECT type, SUM(amount) as total FROM payments WHERE date BETWEEN ? AND ? GROUP BY type", [startDate, endDate], (e, r) => e ? reject(e) : resolve(r || []));
    });

    const p3 = new Promise((resolve, reject) => {
        db.query("SELECT date, SUM(amount) as total FROM payments WHERE date BETWEEN ? AND ? GROUP BY date ORDER BY date", [startDate, endDate], (e, r) => e ? reject(e) : resolve(r || []));
    });

    Promise.all([p1, p2, p3])
        .then(([total, byType, daily]) => {
            res.json({ total, byType, daily });
        })
        .catch(err => res.status(500).json({ error: err.message }));
});

app.listen(port, () => {
    console.log(`Server running at http://localhost:${port}`);
});
