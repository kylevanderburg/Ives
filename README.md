# Ives — Compose Your Calendar

**Ives** is a self-hosted, FERPA-conscious scheduling tool designed for educators, musicians, and academic professionals. Inspired by Charles Ives — the American composer and insurance salesman — Ives helps you find time amidst creative and professional chaos.

## Features

- Microsoft Outlook calendar integration via Microsoft Graph API
- Customizable appointment types and durations
- Zoom, Teams, or in-person meeting support
- No student data stored (FERPA-friendly)
- Clean Bootstrap interface
- Logo and branding customizable
- Easy to deploy — no user accounts needed

## Requirements

- PHP 8.0+
- Composer
- A Microsoft Azure app with appropriate API permissions:
  - `offline_access`
  - `Calendars.ReadWrite`
  - `User.Read`
- A hosting environment (e.g. Apache or Nginx)

## Installation

1. **Clone the repository**

```bash
git clone https://github.com/kylevanderburg/Ives.git
cd Ives
```

2. **Install dependencies**

```bash
composer install
```

3. **Create your config**

```bash
cp config.sample.php config.php
```

Then edit `config.php` to include your Microsoft app credentials, Zoom link, and preferred location.

4. **Set up your token storage**

Create a writable `auth/` directory to store the OAuth token:

```bash
mkdir auth
chmod 700 auth
```

5. **Protect sensitive files**

Ensure the following are in your `.gitignore`:

```gitignore
/vendor/
auth/
config.php
```

6. **Secure your auth directory via `.htaccess` (Apache)**

```apache
<FilesMatch "\.json$">
  Order allow,deny
  Deny from all
</FilesMatch>
```

7. **Start the app**

Visit your app’s root (e.g. `http://localhost/index.php`) and complete the OAuth login.

---

## Optional Configuration

You can customize the app name, logo, and footer by editing `config.php`:

```php
'app_name' => 'Ives',
'app_logo' => 'logo.svg',
```

---

## License

MIT License. See `LICENSE` file.

---

## Future Goals

1. Multiple Users
2. Check multiple calendars

---

## Credits

Ives was inspired by composer Charles Ives and built to support academic music professionals with simpler scheduling.

Logo by [ChatGPT].
