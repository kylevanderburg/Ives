<?php
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
?>
<!-- header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <title>Ives — Compose Your Calendar</title>
  <?php $BASE = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); ?>
  <link rel="preload" as="image" href="<?= $BASE ?>/ives2.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-link {
      display: inline-block;
      width: 64px;
      height: 64px;
      background-image: url('/ives.svg');
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      transition: background-image 0.2s ease-in-out;
    }

    .logo-link:hover {
      background-image: url('/ives2.svg');
    }

    @media (max-width: 576px) {
      header h1 {
        font-size: 1.5rem;
      }

      header p {
        font-size: 0.9rem;
      }
    }

      body::after {
        content: "";
        display: none;
        background-image: url('/ives2.svg');
      }
  </style>
</head>
<body class="bg-light">
  <header class="d-flex align-items-center justify-content-center py-2 border-bottom bg-white mb-3">
    <a href="/" class="logo-link me-2" aria-label="Ives Home" title="Ives Logo"></a>
    <div class="text-start">
      <h1 class="h5 mb-0">Ives</h1>
      <p class="text-muted fst-italic mb-0 small">Compose Your Calendar</p>
    </div>
  </header>

  <main class="container px-3 px-md-4">
    <!--<div class="alert alert-warning small text-center">This app is currently in development mode.</div>-->
