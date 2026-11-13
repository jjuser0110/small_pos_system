<!-- Core JS -->
<!-- build:js assets/vendor/js/core.js -->
<script src="{{ asset('assets/vendor/libs/jquery/jquery.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/i18n/i18n.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/typeahead-js/typeahead.js') }}"></script>
<script src="{{ asset('assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<!-- Vendors JS -->
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js') }}"></script>
<!-- Flat Picker -->
<script src="{{ asset('assets/vendor/libs/moment/moment.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/flatpickr/flatpickr.js') }}"></script>
<!-- Form Validation -->
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/bundle/popular.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-bootstrap5/index.min.js') }}"></script>
<script src="{{ asset('assets/vendor/libs/@form-validation/umd/plugin-auto-focus/index.min.js') }}"></script>

<!-- Main JS -->
<script src="{{ asset('assets/js/main.js') }}"></script>
<!-- <script src="{{ asset('assets/js/ui-modals.js') }}"></script> -->

<!-- Page JS -->
<!-- <script src="{{ asset('assets/js/dashboards-analytics.js') }}"></script> -->
<!-- <script src="{{ asset('assets/js/tables-datatables-basic.js') }}"></script> -->

@yield('page-js')

@yield('scripts')
<script>
    // Searchable dropdowns everywhere: every form select becomes a select2 box
    // (type to search). Add class "no-select2" to keep a plain dropdown.
    window.initSelect2 = function (root) {
        if (!window.jQuery || !jQuery.fn.select2) return;
        jQuery(root || document).find('select.form-select, select.select2').each(function () {
            var $s = jQuery(this);
            if ($s.data('select2') || $s.hasClass('no-select2') || $s.closest('.dataTables_length').length) return;
            var $modal = $s.closest('.modal');
            var blank = $s.find('option[value=""]').first();
            $s.select2({
                width: '100%',
                dropdownParent: $modal.length ? $modal : jQuery(document.body),
                placeholder: blank.length ? blank.text() : undefined,
            });
        });
    };
    jQuery(function () { window.initSelect2(document); });
</script>
<script>
    function showLoading(){
        document.getElementById('loading-screen').classList.remove('d-none');
    }

    function hideLoading(){
        document.getElementById('loading-screen').classList.add('d-none');
    }

    window.addEventListener("pageshow", function (event) {
        hideLoading();
    });
</script>
@include('layouts.shift-timer')
