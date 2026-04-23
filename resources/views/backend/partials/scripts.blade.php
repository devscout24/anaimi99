  <!-- Jquery -->
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

  <!-- Template js start -->

  <!-- JAVASCRIPT -->
  <script src="{{ asset('/') }}backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('/') }}backend/assets/libs/simplebar/simplebar.min.js"></script>
  <script src="{{ asset('/') }}backend/assets/libs/node-waves/waves.min.js"></script>
  <script src="{{ asset('/') }}backend/assets/libs/feather-icons/feather.min.js"></script>
  <script src="{{ asset('/') }}backend/assets/js/pages/plugins/lord-icon-2.1.0.js"></script>
  {{-- <script src="{{ asset('/') }}backend/assets/js/plugins.js"></script> --}}
  <script src="{{ asset('/') }}backend/assets/scripts/flatpickr.min.js"></script>
  <script src="{{ asset('/') }}backend/assets/scripts/choices.min.js"></script>

  <!-- apexcharts -->
  <script src="{{ asset('/') }}backend/assets/libs/apexcharts/apexcharts.min.js"></script>

  <!-- Vector map-->
  <script src="{{ asset('/') }}backend/assets/libs/jsvectormap/jsvectormap.min.js"></script>
  <script src="{{ asset('/') }}backend/assets/libs/jsvectormap/maps/world-merc.js"></script>

  <!--Swiper slider js-->
  <script src="{{ asset('/') }}backend/assets/libs/swiper/swiper-bundle.min.js"></script>

  <!-- Dashboard init -->
  <script src="{{ asset('/') }}backend/assets/js/pages/dashboard-ecommerce.init.js"></script>

  <!-- App js -->
  <script src="{{ asset('/') }}backend/assets/js/app.js"></script>

  <!-- Template js end -->

  <!-- Yajra DataTable -->
  <script src="https://cdn.datatables.net/v/bs5/dt-2.3.4/r-3.0.3/datatables.min.js"></script>

  <!-- sweetalert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Dropify -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Dropify/0.2.2/js/dropify.min.js"></script>

  <!-- ckeditor -->
  <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>

  <!-- Set CSRF token for all AJAX requests -->
  <script>
      $(document).ready(function() {
          $.ajaxSetup({
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              }
          });
      });
  </script>

  {{-- ckeditor Initialization --}}
  <script>
      document.querySelectorAll('.ckeditor').forEach((editorElement) => {
          ClassicEditor
              .create(editorElement)
              .catch(error => {
                  console.error(error);
              });
      });
  </script>

  {{-- Dropify Initialization --}}
  <script>
      $(document).ready(function() {
          $('.dropify').dropify({
              messages: {
                  'default': 'Drag and drop here or click',
                  'replace': 'Drag and drop or click to replace',
                  'remove': 'Remove file',
                  'error': 'Ooops! something went wrong.'
              }
          });

      });
  </script>
  {{-- Dropify Initialization --}}

  {{-- SweetAlert2 Notifications --}}
  <script>
      @if (session('success'))
          Swal.fire({
              icon: 'success',
              // title: 'Success',
              text: '{{ session('success') }}',
              timer: 3000,
              showConfirmButton: false,
              position: 'top-end',
              toast: true
          });
      @endif

      @if (session('error'))
          Swal.fire({
              icon: 'error',
              // title: 'Error',
              text: '{{ session('error') }}',
              timer: 3000,
              showConfirmButton: false,
              position: 'top-end',
              toast: true
          });
      @endif

      @if (session('warning'))
          Swal.fire({
              icon: 'warning',
              // title: 'Warning',
              text: '{{ session('warning') }}',
              timer: 3000,
              showConfirmButton: false,
              position: 'top-end',
              toast: true
          });
      @endif

      @if (session('info'))
          Swal.fire({
              icon: 'info',
              // title: 'Info',
              text: '{{ session('info') }}',
              timer: 3000,
              showConfirmButton: false,
              position: 'top-end',
              toast: true
          });
      @endif
  </script>
  {{-- SweetAlert2 Notifications --}}

  {{-- status update --}}
  <script>
      $(document).on('change', '.status-switch', function() {
          let checkbox = $(this);
          let status = checkbox.is(':checked') ? 1 : 0;
          let id = checkbox.data('id');
          let type = checkbox.data('type');

          // Add a small spinner next to the switch
          let spinner = $('<div class="spinner-border spinner-border-sm text-primary ms-2" role="status"></div>');
          checkbox.closest('div').append(spinner);

          $.ajax({
              url: "{{ route('admin.status.update') }}",
              type: "POST",
              data: {
                  id: id,
                  type: type,
                  status: status
              },
              success: function(response) {
                  spinner.remove();
                  Swal.fire({
                      toast: true,
                      position: 'top-end',
                      icon: 'success',
                      text: response.message,
                      showConfirmButton: false,
                      timer: 1500
                  });
              },
              error: function() {
                  spinner.remove();
                  Swal.fire({
                      toast: true,
                      position: 'top-end',
                      icon: 'error',
                      text: 'Something went wrong!',
                      showConfirmButton: false,
                      timer: 1500
                  });
              }
          });
      });
  </script>
  {{-- status update end  --}}
