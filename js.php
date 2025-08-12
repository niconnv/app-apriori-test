<!-- jQuery sudah dimuat di head, tidak perlu dimuat ulang -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/feather-icons@4.28.0/dist/feather.min.js"></script>
<!-- Bootstrap JS sudah dimuat di head -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- <script src="assets/js/core.js"></script> -->

<script>
// Global currency formatting function
function formatRupiah(amount) {
  return parseInt(amount).toLocaleString('id-ID');
}

// Format currency with Rp prefix
function formatRupiahWithPrefix(amount) {
  return 'Rp ' + formatRupiah(amount);
}
</script>