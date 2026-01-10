<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?? 'Login' ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
  <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="col-md-5 col-lg-4">

      <div class="card shadow-sm border-0">
        <div class="card-body p-4">

          <h4 class="mb-1 fw-bold">TicketApp</h4>
          <p class="text-muted mb-4">Silakan login untuk melanjutkan</p>

          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
              <?= session()->getFlashdata('error') ?>
            </div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
              <?= session()->getFlashdata('success') ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= base_url('login') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-control" placeholder="contoh@email.com" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">
              Login
            </button>
          </form>

          <hr class="my-4">

          <div class="text-muted small">
            <div><b>Default seed</b>:</div>
            <div>superadmin@ticketapp.com</div>
            <div>admin.jkt@ticketapp.com</div>
            <div>customer1@ticketapp.com</div>
            <div>Password: <code>Password123!</code></div>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

</body>
</html>
