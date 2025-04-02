# Ives — Compose Your Calendar

**Ives** is a self-hosted, FERPA-conscious scheduling tool designed for educators, musicians, and academic professionals. Inspired by Charles Ives — the American composer and insurance salesman — Ives helps you find time amidst creative and professional chaos.

## ✨ Features

- 🔁 **Multi-user support** with clean URLs (`/kyle/studio-lesson`)
- 📅 Microsoft Outlook calendar integration via Microsoft Graph API
- 🕒 Customizable appointment types per user
- 🎥 Zoom, Teams, or in-person meeting options
- 🔐 No student data stored (FERPA-friendly)
- 🌈 Custom logos, branding, and display names
- 🧼 Clean Bootstrap UI
- 🛠 No database, no accounts, easy to deploy

---

## ⚙️ Requirements

- PHP 8.0+
- Composer
- A Microsoft Azure app with the following permissions:
  - `offline_access`
  - `Calendars.ReadWrite`
  - `User.Read`
- A hosting environment (Apache/Nginx + mod_rewrite recommended)

---

## 🚀 Installation

1. **Clone the repository**

```bash
git clone https://github.com/kylevanderburg/Ives.git
cd Ives
```

2. **Install dependencies**

```bash
composer install
```

3. **Set up your config**

```bash
cp config.sample.php config.php
```

Edit `config.php` with:
- Microsoft app credentials
- Zoom meeting link
- In-person location
- App name/logo
- Authorized emails

4. **Set up your users**

Create a `users.php` file to map usernames to calendar owners:

```php
return [
  'kyle' => [
    'email' => 'Your Email',
    'label' => 'Dr. Kyle Vanderburg',
    'types' => ['studio-lesson', 'advising']
  ],
  ...
];
```

5. **Create token storage**

```bash
mkdir token
chmod 755 token
```

6. **Protect sensitive files**

Add to `.gitignore`:

```gitignore
/vendor/
token/
config.php
users.php
```

7. **Authorize your users**

Visit `/auth.php`, log in with your Microsoft account, and save the token.

8. **Start scheduling**

Use URLs like:

```
https://yourdomain.com/kyle
https://yourdomain.com/kyle/studio-lesson
```

---

## 🛠 Optional Configuration

In `config.php`, customize:

```php
'app_name' => 'Ives',
'app_logo' => '/assets/ives.svg',
'in_person_location' => 'Room 115C',
'zoom_link' => 'https://your.zoom.us/j/123456789',
```

---

## 🧪 Development Notes

- No database required — file-based configs
- Tokens are saved per user in `/token/` directory
- Reauthorization needed if token expires
- Supports multiple Microsoft accounts

---

## 🧭 Roadmap

- [x] Multi-user support
- [x] Per-user appointment types
- [x] Zoom + Teams + in-person integration
- [ ] Public user directory (`/`)
- [ ] Token dashboard (`/admin/tokens`)
- [ ] Per-user customization (colors, photos, bios)
- [ ] ICS calendar export
- [ ] Google Calendar support

---

## 📄 License

MIT License. See `LICENSE`.

---

## 🙏 Credits

Built by Kyle Vanderburg to support academic music professionals with simpler scheduling.  
Inspired by composer Charles Ives.  
Logo by [ChatGPT].
