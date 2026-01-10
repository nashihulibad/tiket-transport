<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<h4 class="mb-3">Order Tiket</h4>

<!-- LIST ORDER (TOP) -->
<div class="card mb-4">
  <div class="card-header fw-semibold">List Order Saya</div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle mb-0" id="tableOrders">
        <thead>
          <tr>
            <th>Kode Order</th>
            <th>Kode Tiket</th>
            <th>Rute</th>
            <th>Class</th>
            <th>Jadwal</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody id="orderRows">
          <tr><td colspan="9" class="text-center text-muted">Loading...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- LIST TICKETS (CARDS) -->
<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="mb-0">Daftar Tiket</h5>
  <small class="text-muted">Klik "Pesan" untuk membuat order</small>
</div>

<div class="row" id="ticketCards">
  <div class="col-12">
    <div class="alert alert-info">Loading tickets...</div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function rupiah(num) {
  return new Intl.NumberFormat('id-ID').format(parseFloat(num || 0));
}

function loadOrders() {
  $.get("<?= base_url('orders/list') ?>", function(res){
    if (!res.success) return;

    if (res.data.length === 0) {
      $('#orderRows').html(`<tr><td colspan="9" class="text-center text-muted">Belum ada order</td></tr>`);
      return;
    }

    let html = '';
    res.data.forEach(o => {
      html += `
        <tr>
          <td>${o.order_code}</td>
          <td>${o.ticket_code ?? '-'}</td>
          <td>${o.origin} → ${o.destination}</td>
          <td>${o.class}</td>
          <td>${o.date_keberangkatan} ${o.jam_keberangkatan}</td>
          <td>${o.qty}</td>
          <td>${rupiah(o.subtotal)}</td>
          <td><span class="badge bg-success">${o.status}</span></td>
          <td>
            <button class="btn btn-sm btn-danger btn-cancel" data-id="${o.id}">
              Cancel
            </button>
          </td>
        </tr>
      `;
    });

    $('#orderRows').html(html);
  }, 'json');
}

function loadTickets() {

  $.get("<?= base_url('tickets/public-list') ?>", function(res){
    if (!res.success) return;

    if (res.data.length === 0) {
      $('#ticketCards').html(`
        <div class="col-12">
          <div class="alert alert-warning">Tidak ada tiket tersedia</div>
        </div>
      `);
      return;
    }

    let html = '';
    res.data.forEach(t => {
    const stock = parseInt(t.stock || 0);
    const disabled = stock <= 0 ? 'disabled' : '';
    const cardClass = stock <= 0 ? 'out-of-stock' : '';

    const badgeStock = stock <= 0
      ? `<span class="badge bg-secondary badge-stock">HABIS</span>`
      : `<span class="badge bg-success badge-stock">Stock: ${stock}</span>`;

    html += `
      <div class="col-md-4 mb-3">
        <div class="card ticket-card ${cardClass}">
          <div class="card-body p-3">

            <div class="ticket-top mb-2">
              <div>
                <div class="route">${t.origin} → ${t.destination}</div>
                <div class="meta">
                  Kode: <b>${t.code}</b><br>
                  Class: <b>${t.class}</b>
                </div>
              </div>
              <div class="text-end">
                ${badgeStock}
              </div>
            </div>

            <div class="divider"></div>

            <div class="meta mb-2">
              Jadwal: <b>${t.date_keberangkatan} ${t.jam_keberangkatan}</b><br>
              Kendaraan: <b>${t.no_polisi_kendaraan}</b>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-3">
              <div>
                <div class="text-muted small">Harga</div>
                <div class="price">Rp ${rupiah(t.price)}</div>
              </div>

              <button class="btn btn-damri btn-order btn-order-ticket" data-id="${t.id}" ${disabled}>
                Pesan
              </button>
            </div>

          </div>
        </div>
      </div>
    `;
  });

    $('#ticketCards').html(html);
  }, 'json');
}

$(function(){
  loadOrders();
  loadTickets();

  // Pesan tiket
  $(document).on('click', '.btn-order', function(){
    const ticketId = $(this).data('id');

    $.post("<?= base_url('orders/store') ?>", {
      ticket_id: ticketId,
      qty: 1
    }, function(res){
      alert(res.message);
      if (res.success) {
        loadOrders();
        loadTickets();
      }
    }, 'json');
  });

  // Cancel order
  $(document).on('click', '.btn-cancel', function(){
    const id = $(this).data('id');
    if (!confirm('Yakin cancel order ini?')) return;

    $.post("<?= base_url('orders/cancel') ?>/" + id, {}, function(res){
      alert(res.message);
      if (res.success) {
        loadOrders();
        loadTickets();
      }
    }, 'json');
  });
});
</script>
<?= $this->endSection() ?>
