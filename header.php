<!-- header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Ives — Compose Your Calendar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .logo-link {
      display: inline-block;
      width: 64px;
      height: 64px;
      background-image: url('ives.png');
      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
      transition: background-image 0.2s ease-in-out;
    }

    .logo-link:hover {
      background-image: url('ives2.png');
    }
  </style>
</head>
<body class="bg-light">
  <header class="py-4 text-center border-bottom bg-white mb-4">
  <a href="/" class="logo-link d-inline-block" aria-label="Ives Home"></a>
    <h1 class="h3 mt-2">Ives</h1>
    <p class="text-muted fst-italic">Compose Your Calendar</p>
  </header>
  <main class="container">
