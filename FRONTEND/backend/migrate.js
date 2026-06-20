const sqlite3 = require('sqlite3').verbose();
const db = new sqlite3.Database('./database.sqlite');
db.serialize(() => {
  db.run("ALTER TABLE users ADD COLUMN bank_name TEXT", (err) => { if(err) console.log(err.message); else console.log("Added bank_name"); });
  db.run("ALTER TABLE users ADD COLUMN bank_account TEXT", (err) => { if(err) console.log(err.message); else console.log("Added bank_account"); });
  db.run("ALTER TABLE users ADD COLUMN bank_account_name TEXT", (err) => { if(err) console.log(err.message); else console.log("Added bank_account_name"); });
});
db.close();
