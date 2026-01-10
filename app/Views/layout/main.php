<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <title><?= $title ?? 'TicketApp' ?></title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- DataTables -->
  <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">

  <!-- DAMRI Theme -->
  <style>
    :root{
      --damri-blue: #0B4DBA;
      --damri-yellow: #F2B300;
      --damri-bg: #F6F8FC;
      --damri-text: #0f172a;
    }

    body{
      background: var(--damri-bg);
      color: var(--damri-text);
    }

    /* navbar */
    .navbar-damri{
      background: var(--damri-blue);
      border-bottom: 4px solid var(--damri-yellow);
    }

    .navbar-brand img{
      height: 34px;
      width: auto;
    }

    .nav-link{
      font-weight: 500;
      opacity: .92;
    }
    .nav-link:hover{
      opacity: 1;
      text-decoration: underline;
      text-underline-offset: 6px;
    }

    .nav-link.active{
      font-weight: 700;
      color: #fff !important;
      opacity: 1;
      position: relative;
    }

    .nav-link.active::after{
      content: "";
      position: absolute;
      left: .25rem;
      right: .25rem;
      bottom: -8px;
      height: 3px;
      background: var(--damri-yellow);
      border-radius: 99px;
    }

    /* button theme */
    .btn-damri{
      background: var(--damri-yellow);
      border: 1px solid var(--damri-yellow);
      color: #111827;
      font-weight: 700;
    }
    .btn-damri:hover{
      filter: brightness(0.95);
      color: #111827;
    }

    .btn-damri-outline{
      border: 1px solid rgba(255,255,255,.7);
      color: #fff;
      font-weight: 600;
    }
    .btn-damri-outline:hover{
      background: rgba(255,255,255,.12);
      color: #fff;
    }

    /* card style */
    .card{
      border: 0;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
    }

    .card-header{
      background: #fff;
      border-bottom: 1px solid rgba(15,23,42,.08);
      font-weight: 700;
    }

    /* badges */
    .badge.bg-damri{
      background: var(--damri-blue) !important;
    }

    /* DataTables */
    table.dataTable{
      border-collapse: separate !important;
      border-spacing: 0;
    }

    /* subtle section title */
    .page-title{
      font-weight: 800;
      letter-spacing: -.02em;
    }

    /* DAMRI ticket card */
  .ticket-card{
    border-radius: 16px;
    overflow: hidden;
    transition: all .18s ease;
    position: relative;
    background: #fff;
  }

  .ticket-card:hover{
    transform: translateY(-2px);
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
  }

  .ticket-card::before{
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 6px;
    height: 100%;
    background: var(--damri-yellow);
  }

  .ticket-card .ticket-top{
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 10px;
  }

  .ticket-card .route{
    font-weight: 800;
    font-size: 1.05rem;
    letter-spacing: -0.01em;
  }

  .ticket-card .meta{
    font-size: .88rem;
    color: rgba(15,23,42,.72);
    line-height: 1.45;
  }

  .ticket-card .price{
    font-size: 1.2rem;
    font-weight: 900;
    color: var(--damri-blue);
  }

  .ticket-card .badge-stock{
    font-weight: 800;
    border-radius: 999px;
    padding: .35rem .6rem;
  }

  .ticket-card.out-of-stock{
    filter: grayscale(1);
    opacity: .65;
  }

  .ticket-card .btn-order{
    font-weight: 800;
    border-radius: 12px;
    padding: .6rem .9rem;
  }

  .ticket-card .divider{
    border-top: 1px dashed rgba(15,23,42,.15);
    margin: .75rem 0;
  }

  </style>
</head>

<body>

<?php
  $role = session()->get('role_code');
  $path = trim(parse_url(current_url(), PHP_URL_PATH), '/');
?>

<nav class="navbar navbar-expand-lg navbar-dark navbar-damri">
  <div class="container">

    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= base_url('/') ?>">
      <span class="fw-bold">TicketApp</span>
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">

      <!-- LEFT MENU -->
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">

        <?php if ($role === 'customer'): ?>
          <li class="nav-item">
            <a class="nav-link <?= str_starts_with($path, 'orders') ? 'active' : '' ?>" href="<?= base_url('orders') ?>">
              Order Tiket
            </a>
          </li>
        <?php endif; ?>

        <?php if (in_array($role, ['superadmin','regional_admin'])): ?>
          <li class="nav-item">
            <a class="nav-link <?= str_starts_with($path, 'tickets') ? 'active' : '' ?>" href="<?= base_url('tickets') ?>">
              Master Ticket
            </a>
          </li>
        <?php endif; ?>

        <?php if ($role === 'superadmin'): ?>
          <li class="nav-item">
            <a class="nav-link <?= str_starts_with($path, 'master-prices') ? 'active' : '' ?>" href="<?= base_url('master-prices') ?>">
              Master Price
            </a>
          </li>
        <?php endif; ?>

      </ul>

      <!-- RIGHT MENU -->
      <div class="d-flex align-items-center gap-3 text-white">
        <div class="small text-end d-none d-lg-block">
          <div class="fw-semibold"><?= esc(session()->get('name')) ?></div>
          <div class="text-white-50"><?= esc($role) ?></div>
        </div>
        <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-damri-outline">
          Logout
        </a>
      </div>

    </div>
  </div>
</nav>

<main class="container py-4">
  <?= $this->renderSection('content') ?>
</main>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<?= $this->renderSection('scripts') ?>
</body>
</html>
