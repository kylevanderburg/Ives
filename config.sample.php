<?php
// config.sample.php

return [
    // Microsoft Azure App credentials
    'client_id' => 'YOUR_CLIENT_ID_HERE',
    'client_secret' => 'YOUR_CLIENT_SECRET_HERE',
    'tenant_id' => 'YOUR_TENANT_ID_HERE',

    // Redirect URI for OAuth flow
    'redirect_uri' => 'https://yourdomain.com/callback.php',

    // Microsoft Graph scopes
    'scopes' => 'offline_access Calendars.ReadWrite User.Read',

    // Admin notification email (for booking confirmations)
    'notification_email' => 'your@email.com',

    // Optional: logo and name
    'app_name' => 'Ives',
    'app_logo' => 'ives.png',

    // Meeting platform links
    'zoom_link' => 'https://your.zoom.link/here',
    'in_person_location' => 'Smith Hall Room 123',
];
