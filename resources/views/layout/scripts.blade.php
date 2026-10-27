      <!-- JAVASCRIPT -->
        <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('backend/assets/libs/metismenu/metisMenu.min.js') }}"></script>
        <script src="{{ asset('backend/assets/libs/simplebar/simplebar.min.js') }}"></script>
        <script src="{{ asset('backend/assets/libs/node-waves/waves.min.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>

        <!-- apexcharts -->
        <script src="{{ asset('backend/assets/libs/apexcharts/apexcharts.min.js') }}"></script>

        <!-- jquery.vectormap map -->
        <script src="{{ asset('backend/assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
        <script src="{{ asset('backend/assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-us-merc-en.js') }}"></script>
        
        {{-- Yajra Data Table Scripts --}}
        <!-- Local vendor baseline -->
        <script src="{{ asset('vendor/datatables/js/jquery.dataTables-1.13.6.min.js') }}"></script>
        <script src="{{ asset('uppy/dist/uppy.min.js') }}"></script>

        <script src="{{ asset('vendor/datatables/js/dataTables.responsive-2.4.1.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/responsive.bootstrap4-2.4.1.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/dataTables.buttons-2.2.3.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/jszip-3.1.3.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/pdfmake-0.1.53.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/vfs_fonts-0.1.53.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/buttons.html5-2.2.3.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/buttons.colVis-2.2.3.min.js') }}"></script>
        <script src="{{ asset('vendor/datatables/js/buttons.print-2.2.3.min.js') }}"></script>
        
        <!-- Datatable init js -->
        <script src="{{ asset('backend/assets/js/pages/datatables.init.js') }}"></script>
        <script src="{{ asset('backend/datatables/buttons.server-side.js') }}"></script>

        {{-- Yajra Data Table Scripts end--}}

        <script src="{{ asset('backend/assets/js/pages/dashboard.init.js') }}"></script>

        <!-- App js -->
        <script src="{{ asset('backend/assets/js/app.js') }}"></script>

        <script src="{{ asset('backend/assets/libs/select2/js/select2.min.js') }}"></script>
        <script src="{{ asset('backend/assets/js/pages/form-advanced.init.js') }}"></script>

        <script src="{{ asset('notiflix/notiflix-3.2.8.min.js') }}"></script>

<script>
 if (window.Notiflix && Notiflix.Notify) {
     Notiflix.Notify.init({
         position: 'right-top',
         timeout: 3000,
         showOnlyTheLastOne: true
     });

     @if(Session::has('message'))
     var type = "{{ Session::get('alert-type','info') }}";
     var message = "{{ Session::get('message') }}";

     switch(type){
         case 'info':
         Notiflix.Notify.info(message);
         break;

         case 'success':
         Notiflix.Notify.success(message);
         break;

         case 'warning':
         Notiflix.Notify.warning(message);
         break;

         case 'error':
         Notiflix.Notify.failure(message);
         break;

         default:
         Notiflix.Notify.success(message);
         break;
     }
     @endif

     @if($errors->any())
     var validationMessage = @json($errors->first());
     Notiflix.Notify.failure(validationMessage);
     @endif
 }
 
</script>

<script>
    // Show a clear, actionable message when session/auth expires on any AJAX call.
    $(document).ajaxError(function(event, xhr) {
        if (!xhr) {
            return;
        }

        if (xhr.status === 419 || xhr.status === 401) {
            const message = 'Your login session has expired due to inactivity.';
            const action = 'Action: refresh this page and sign in again.';

            if (window.Notiflix && Notiflix.Notify) {
                Notiflix.Notify.failure(message + ' ' + action);
            } else {
                alert(message + ' ' + action);
            }
        }
    });
</script>

 <!-- field Validation -->
 <script src="{{ asset('backend/assets/js/validate.min.js') }}"></script>

 <script src="{{ asset('backend/assets/js/code.js') }}"></script>
 <script src="{{ asset('backend/assets/js/handlebars.js') }}"></script>
 <script src="{{ asset('backend/assets/js/notify.min.js') }}"></script>

 <!--Only Text and Numbers Script-->
<script>
    function isNumberKey(evt){
        var charCode = (evt.which) ? evt.which : evt.keyCode
        if (charCode > 31 && (charCode < 48 || charCode > 57))
            return false;
        return true;
    }

    // Allow only alphabetic characters, space, dot, and hyphen
    function isCharKey(evt){
        var keyunicode = event.charCode || event.keyCode;
        // Check for alphabetic characters, backspace, space, dot, and hyphen
        return (
            (keyunicode >= 65 && keyunicode <= 90) ||  // A-Z
            (keyunicode >= 97 && keyunicode <= 122) || // a-z
            keyunicode == 32 ||   // Space
            keyunicode == 46 ||   // Dot (.)
            keyunicode == 45      // Hyphen (-)
        );
    }
</script>

<script>
    //calling datatable function 
    $(document).ready( function () {
    $('#myTable').DataTable(
        {
            paging: true,
           lengthChange: true,
           searching: true,
           ordering: true,
           info: true,
           autoWidth: false,
            dom: 'lBfrtip',

           responsive: true,
           buttons: [ "csv","print",
           {
            extend: 'copy',
            exportOptions: {
                columns:  [ 0, 1, 2, 3, 4, 5, 6, 7]
            }
        },
        {
            extend: 'excel',
            exportOptions: {
                columns:  [ 0, 1, 2, 3, 4, 5, 6, 7]
            }
        },
        {
            extend: 'pdf',
            exportOptions: {
                columns: [ 0, 1, 2, 3, 4, 5, 6, 7]
            }
        },


        'colvis' ],
         })
         .buttons()
          .container()
          .appendTo("#example_wrapper .col-md-6:eq(0)");
          

    });
</script>

<script>
    //calling datatable function 
    $(document).ready( function () {
    $('#NewTable').DataTable(
        {
            
           
         })
         .buttons()
          .container()
          .appendTo("#example_wrapper .col-md-6:eq(0)");
          

    });
</script>

{{-- //Download JS --}}
<script src="{{ asset('vendor/jspdf/jspdf.umd-2.5.1.min.js') }}"></script>
<script src="{{ asset('vendor/html2canvas/html2canvas-1.4.1.min.js') }}"></script>

<script>
    async function downloadPDF() {
        const { jsPDF } = window.jspdf;

        // Capture Sale area
        const Sale = document.getElementById("invoiceArea");
        const canvas = await html2canvas(Sale, { scale: 2 }); // higher scale = better quality
        const imgData = canvas.toDataURL("image/png");

        // Create PDF
        const pdf = new jsPDF("p", "mm", "a4");
        const pageWidth = pdf.internal.pageSize.getWidth();
        const pageHeight = pdf.internal.pageSize.getHeight();

        // Calculate image dimensions to fit page
        const imgWidth = pageWidth;
        const imgHeight = canvas.height * imgWidth / canvas.width;

        let position = 0;
        pdf.addImage(imgData, "PNG", 0, position, imgWidth, imgHeight);

        // Save file
        pdf.save("Sale.pdf");
    }
</script>

<script type="text/javascript">
// Disable right-click
document.addEventListener('contextmenu', e => e.preventDefault());

// Disable common DevTools shortcuts
document.addEventListener('keydown', e => {
    if (e.ctrlKey && (
        e.key.toLowerCase() === 'u' || // View source
        e.key.toLowerCase() === 's' || // Save
        e.key.toLowerCase() === 'i' || // DevTools
        e.key.toLowerCase() === 'j'    // Console
    )) {
        e.preventDefault();
    }
});
</script>

