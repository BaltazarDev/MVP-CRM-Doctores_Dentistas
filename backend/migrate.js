const db = require('./db');
const fs = require('fs');
const path = require('path');

function runMigrations() {
    return new Promise((resolve, reject) => {
        // Check if DB is already initialized (check for 'settings' table)
        // If settings table exists, we assume the DB is ready to avoid re-running setup.sql which might duplicate data
        db.query("SHOW TABLES LIKE 'settings'", (err, results) => {
            if (err) {
                // If checking tables fails, we can't proceed safely
                console.error('Error checking database state:', err);
                // We don't reject here because on fresh connection it might just work if we try strictly.
                // But usually this means connection error.
                return reject(err);
            }

            if (results.length > 0) {
                console.log('Database already initialized (settings table found). Skipping setup.');
                return resolve();
            }

            console.log('Initializing database from setup.sql...');
            const sqlPath = path.join(__dirname, 'setup.sql');
            fs.readFile(sqlPath, 'utf8', (err, data) => {
                if (err) return reject(err);

                // Split by semicolon but ignore empty lines
                const queries = data.split(';').map(q => q.trim()).filter(q => q.length > 0);

                // Execute sequentially
                let promiseChain = Promise.resolve();
                queries.forEach(query => {
                    promiseChain = promiseChain.then(() => {
                        return new Promise((res, rej) => {
                            db.query(query, (e) => {
                                // Ignore errors like "Table exists" if using simple CREATE
                                if (e && e.code !== 'ER_TABLE_EXISTS_ERROR') console.warn('Warning executing query:', e.message);
                                res();
                            });
                        });
                    });
                });

                promiseChain.then(() => {
                    console.log('Database initialized successfully.');
                    resolve();
                }).catch(reject);
            });
        });
    });
}

// Allow running standalone or imported
if (require.main === module) {
    runMigrations().then(() => process.exit(0)).catch(e => { console.error(e); process.exit(1); });
} else {
    module.exports = runMigrations;
}
