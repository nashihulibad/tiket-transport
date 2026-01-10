<?= $this->extend('layout/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Master Price</h4>
  <button class="btn btn-damri" id="btnAdd">+ Tambah</button>
</div>

<div class="card">
  <div class="card-body">
    <table id="tableMasterPrice" class="table table-bordered table-striped w-100">
      <thead>
        <tr>
          <th>Daerah</th>
          <th>Asal</th>
          <th>Tujuan</th>
          <th>Class</th>
          <th>Harga</th>
          <th>Status</th>
          <th>Aksi</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalMasterPrice" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="formMasterPrice">
      <?= csrf_field() ?>
      <input type="hidden" id="mp_id">

      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tambah Master Price</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <label class="form-label">Daerah (Region)</label>
            <select name="region_id" id="region_id" class="form-select" required>
              <option value="">-- pilih daerah --</option>
            </select>
          </div>

          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Asal</label>
              <select name="origin" id="origin" class="form-select" required>
                <option value="">-- pilih --</option>
              </select>
            </div>

            <div class="col-6 mb-3">
              <label class="form-label">Tujuan</label>
              <select name="destination" id="destination" class="form-select" required>
                <option value="">-- pilih --</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Class</label>
            <select name="class" id="class" class="form-select" required>
              <option value="">-- pilih class --</option>
              <option value="ekonomi">Ekonomi</option>
              <option value="non-ekonomi">Non-ekonomi</option>
              <option value="luxury">Luxury</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Harga</label>
            <input type="number" name="price" id="price" class="form-control" min="0" value="0" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="is_active" id="is_active" class="form-select">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>

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

function resetForm() {
  mode = 'add';
  $('#formMasterPrice')[0].reset();
  $('#mp_id').val('');
  $('.modal-title').text('Tambah Master Price');
}

function loadRegions() {
  return $.get("<?= base_url('regions/list') ?>", function(res){
    if (!res.success) return;

    let html = '<option value="">-- pilih daerah --</option>';
    let html2 = '<option value="">-- pilih --</option>';

    res.data.forEach(r => {
      html += `<option value="${r.id}" data-code="${r.code}">${r.code} - ${r.name}</option>`;
      html2 += `<option value="${r.code}">${r.code} - ${r.name}</option>`;
    });

    $('#region_id').html(html);
    $('#origin').html(html2);
    $('#destination').html(html2);
  }, 'json');
}

$(function() {
  modal = new bootstrap.Modal(document.getElementById('modalMasterPrice'));

  table = $('#tableMasterPrice').DataTable({
    processing: true,
    serverSide: true,
    ajax: {
      url: "<?= base_url('master-prices/datatables') ?>",
      type: "GET"
    }
  });

  $('#btnAdd').on('click', async function(){
    resetForm();
    await loadRegions();
    modal.show();
  });

  // edit
  $(document).on('click', '.btn-edit', async function(){
    const id = $(this).data('id');

    resetForm();
    mode = 'edit';
    $('.modal-title').text('Edit Master Price');
    await loadRegions();

    $.get("<?= base_url('master-prices/show') ?>/" + id, function(res){
      if (!res.success) {
        alert(res.message);
        return;
      }

      const d = res.data;
      $('#mp_id').val(d.id);
      $('#region_id').val(d.region_id);
      $('#origin').val(d.origin);
      $('#destination').val(d.destination);
      $('#class').val(d.class);
      $('#price').val(d.price);
      $('#is_active').val(d.is_active);

      modal.show();
    }, 'json');
  });

  // submit
  $('#formMasterPrice').on('submit', function(e){
    e.preventDefault();

    const id = $('#mp_id').val();
    const url = (mode === 'edit')
      ? "<?= base_url('master-prices/update') ?>/" + id
      : "<?= base_url('master-prices/store') ?>";

    $('#btnSubmit').prop('disabled', true).text('Menyimpan...');

    $.post(url, $(this).serialize(), function(res){
      $('#btnSubmit').prop('disabled', false).text('Simpan');
      alert(res.message);

      if (res.success) {
        modal.hide();
        table.ajax.reload(null, false);
      }
    }, 'json').fail(function(){
      $('#btnSubmit').prop('disabled', false).text('Simpan');
      alert('Terjadi error server.');
    });
  });

  // delete
  $(document).on('click', '.btn-delete', function(){
    const id = $(this).data('id');
    if (!confirm('Yakin hapus master price ini?')) return;

    $.post("<?= base_url('master-prices/delete') ?>/" + id, {}, function(res){
      alert(res.message);
      if (res.success) table.ajax.reload(null, false);
    }, 'json');
  });
});
</script>
<?= $this->endSection() ?>
