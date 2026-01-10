<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Master Tiket</h4>
  <button class="btn btn-damri" id="btnAdd">+ Tambah Tiket</button>
</div>


<div class="card">
  <div class="card-body">
    <table id="tableTickets" class="table table-bordered table-striped w-100">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Daerah</th>
          <th>Asal</th>
          <th>Tujuan</th>
          <th>Kelas</th>
          <th>Harga</th>
          <th>Stok</th>
          <th>Tgl</th>
          <th>Jam</th>
          <th>Aksi</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Modal Add Ticket -->
<div class="modal fade" id="modalTicket" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="formTicket">
      <?= csrf_field() ?>

      <input type="hidden" name="id" id="ticket_id">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Tiket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Asal</label>
              <select name="origin" id="origin" class="form-select" required>
                <option value="">-- pilih asal --</option>
              </select>

              <!-- region_id = ID dari origin -->
              <input type="hidden" name="region_id" id="origin_region_id">
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Tujuan</label>
              <select name="destination" id="destination" class="form-select" required>
                <option value="">-- pilih tujuan --</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Class</label>
              <select name="class" id="class" class="form-select" required>
                <option value="">-- pilih class --</option>
                <option value="ekonomi">Ekonomi</option>
                <option value="non-ekonomi">Non-ekonomi</option>
                <option value="luxury">Luxury</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label class="form-label">Harga (auto dari Master Price)</label>
              <input type="text" id="price_view" class="form-control" disabled>
              <input type="hidden" name="master_price_id" id="master_price_id">
            </div>
          </div>

          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Stok</label>
              <input type="number" name="stock" id="stock" class="form-control" min="0" value="0" required>
            </div>

            <div class="col-md-8 mb-3">
              <label class="form-label">No Polisi Kendaraan</label>
              <input type="text" name="no_polisi_kendaraan" id="no_polisi_kendaraan" class="form-control" required>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Tanggal Keberangkatan</label>
              <input type="date" name="date_keberangkatan" id="date_keberangkatan" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Jam Keberangkatan</label>
              <input type="time" step="1" name="jam_keberangkatan" id="jam_keberangkatan" class="form-control" required>
            </div>
          </div>

          <div class="alert alert-warning d-none" id="alertPrice"></div>

        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary" id="btnSubmit">Simpan</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
let table, modal;
let mode = 'add';

const ui = {
  origin: '#origin',
  destination: '#destination',
  cls: '#class',
  originRegionId: '#origin_region_id',
  masterPriceId: '#master_price_id',
  priceView: '#price_view',
  alertPrice: '#alertPrice',
  form: '#formTicket',
  btnSubmit: '#btnSubmit',
};

const rupiah = (num) => new Intl.NumberFormat('id-ID').format(parseFloat(num || 0));

function resetForm() {
  mode = 'add';
  $('.modal-title').text('Tambah Tiket');
  $(ui.form)[0].reset();

  $('#ticket_id').val('');
  $(ui.originRegionId).val('');
  $(ui.masterPriceId).val('');
  $(ui.priceView).val('');
  hideAlert();
}

function showAlert(msg) {
  $(ui.alertPrice).removeClass('d-none').text(msg);
}

function hideAlert() {
  $(ui.alertPrice).addClass('d-none').text('');
}

function loadRegions() {
  return $.get("<?= base_url('regions/list') ?>", function(res){
    if (!res.success) return;

    let html = '<option value="">-- pilih --</option>';
    res.data.forEach(r => {
      html += `<option value="${r.code}" data-id="${r.id}">${r.code} - ${r.name}</option>`;
    });

    $(ui.origin).html(html);
    $(ui.destination).html(html);
  }, 'json');
}

function fetchMasterPrice() {
  const origin = $(ui.origin).val();
  const destination = $(ui.destination).val();
  const cls = $(ui.cls).val();
  const regionId = $(ui.originRegionId).val();

  if (!origin || !destination || !cls || !regionId) return;

  if (origin === destination) {
    $(ui.masterPriceId).val('');
    $(ui.priceView).val('');
    showAlert('Asal dan tujuan tidak boleh sama.');
    return;
  }

  $.get("<?= base_url('tickets/master-price') ?>", {
    origin,
    destination,
    class: cls,
    region_id: regionId
  }, function(res){
    if (res.success) {
      hideAlert();
      $(ui.masterPriceId).val(res.data.id);
      $(ui.priceView).val(rupiah(res.data.price));
    } else {
      $(ui.masterPriceId).val('');
      $(ui.priceView).val('');
      showAlert(res.message || 'Master price belum ditemukan.');
    }
  }, 'json').fail(function(){
    $(ui.masterPriceId).val('');
    $(ui.priceView).val('');
    showAlert('Error server saat ambil master price.');
  });
}

$(function () {
  modal = new bootstrap.Modal(document.getElementById('modalTicket'));

  table = $('#tableTickets').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "<?= base_url('tickets/datatables') ?>",
      type: "GET"
    }
  });

  // Add
  $('#btnAdd').on('click', async function() {
    resetForm();
    await loadRegions();
    modal.show();
  });

  // origin change -> set region_id hidden
  $(document).on('change', ui.origin, function() {
    const regionId = $(this).find(':selected').data('id') || '';
    $(ui.originRegionId).val(regionId);
    fetchMasterPrice();
  });

  // destination/class change -> fetch price
  $(document).on('change', `${ui.destination}, ${ui.cls}`, function() {
    fetchMasterPrice();
  });

  // submit add ticket
  $(ui.form).on('submit', function(e) {
    e.preventDefault();

    if (!$(ui.masterPriceId).val()) {
      alert('Master price belum ditemukan. Silakan cek asal/tujuan/class.');
      return;
    }

    const url = (mode === 'edit')
      ? "<?= base_url('tickets/update') ?>/" + $('#ticket_id').val()
      : "<?= base_url('tickets/store') ?>";

    $(ui.btnSubmit).prop('disabled', true).text('Menyimpan...');

    $.post(url, $(this).serialize(), function(res){
      $(ui.btnSubmit).prop('disabled', false).text('Simpan');

      alert(res.message);

      if (res.success) {
        modal.hide();
        table.ajax.reload(null, false);
      }
    }, 'json').fail(function(){
      $(ui.btnSubmit).prop('disabled', false).text('Simpan');
      alert('Terjadi error server, cek logs.');
    });
  });

  $(document).on('click', '.btn-edit', async function () {
    const id = $(this).data('id');

    resetForm();
    mode = 'edit';
    $('.modal-title').text('Edit Tiket');

    // load region dropdown dulu (agar bisa set selected)
    await loadRegions();

    // ambil detail tiket
    $.get("<?= base_url('tickets/show') ?>/" + id, function(res){
      if (!res.success) {
        alert(res.message);
        return;
      }

      const t = res.data;

      // set id
      $('#ticket_id').val(t.id);

      // set dropdown asal & tujuan
      $(ui.origin).val(t.origin).trigger('change'); // trigger change => auto set origin_region_id
      $(ui.destination).val(t.destination);

      // set lainnya
      $(ui.cls).val(t.class);
      $('#stock').val(t.stock);
      $('#no_polisi_kendaraan').val(t.no_polisi_kendaraan);
      $('#date_keberangkatan').val(t.date_keberangkatan);
      $('#jam_keberangkatan').val(t.jam_keberangkatan);

      // fetch master price baru (karena route/class mungkin berubah)
      fetchMasterPrice();

      modal.show();
    }, 'json');
  });

  // delete
  $(document).on('click', '.btn-delete', function () {
    const id = $(this).data('id');
    if (!confirm('Yakin hapus tiket ini?')) return;

    $.post("<?= base_url('tickets/delete') ?>/" + id, {}, function(res){
      alert(res.message);
      if(res.success) table.ajax.reload(null, false);
    }, 'json');
  });
});
</script>
<?= $this->endSection() ?>