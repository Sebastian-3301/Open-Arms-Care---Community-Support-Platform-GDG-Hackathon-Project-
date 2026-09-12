# Open Arms — PHP + MySQL / XAMPP

## Setup
1. Install XAMPP.
2. Start **Apache** and **MySQL**.
3. Put this `OPENARMSTEST` folder inside `xampp/htdocs/`.
4. Open phpMyAdmin.
5. Import `database/database.sql`.
6. Confirm `backend/config/database.php` matches your MySQL settings.
7. Create an admin password hash:
   `php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"`
8. Put that hash in `ADMIN_PASSWORD_HASH` as a PHP/Apache environment variable, or replace the empty fallback in `backend/config/database.php`.
9. Open `http://localhost/OPENARMSTEST/`.
10. Open `http://localhost/OPENARMSTEST/admin` for the admin dashboard.

## Health check
`http://localhost/OPENARMSTEST/api/health`

## Important
The existing frontend JavaScript continues to submit JSON with `fetch()` and `POST`. Six endpoint strings were changed from root-absolute `/api/...` to directory-relative `api/...` because `/api/...` would otherwise target `http://localhost/api/...` when the project is installed under `/OPENARMSTEST/`. No forms, CSS, layout, animations, navigation, or submission logic were replaced.

The Node/SQLite prototype is not required by this implementation.


## If the browser says "Failed to execute 'json' on 'Response'"
Open this exact URL in the browser first:

`http://localhost/OPENARMSTEST/api/health`

It must display:

`{"ok":true}`

If it does not:
- Make sure Apache and MySQL are running in XAMPP.
- Make sure the folder is exactly `xampp/htdocs/OPENARMSTEST/`.
- Make sure Apache has `mod_rewrite` enabled.
- Make sure `AllowOverride All` is permitted for the htdocs directory.
- Check `xampp/apache/logs/error.log`.
- Confirm the database was imported from `database/database.sql`.

The PHP router now converts PHP fatal errors and backend exceptions into JSON instead of allowing an empty response, which prevents the browser's `response.json()` call from failing with `Unexpected end of JSON input`.


## Important compatibility fix
The public forms now call `index.php?api=...` directly instead of relying on Apache URL rewriting for API requests. This avoids empty responses when XAMPP has `AllowOverride`/rewrite configuration issues. The visible website and form fields are otherwise unchanged.
