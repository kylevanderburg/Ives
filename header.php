<!-- header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ives — Compose Your Calendar</title>
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
  </style>
</head>
<body class="bg-light">
  <header class="py-4 text-center border-bottom bg-white mb-4">
    <a href="/" class="logo-link d-inline-block mx-auto" aria-label="Ives Home"></a>
    <h1 class="h3 mt-2 mb-0">Ives</h1>
    <p class="text-muted fst-italic mb-0">Compose Your Calendar</p>
  </header>

  <main class="container px-3 px-md-4">
    <div class="alert alert-warning small text-center">This app is currently in development mode.</div>
