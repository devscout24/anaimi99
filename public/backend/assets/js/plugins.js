(document.querySelectorAll("[toast-list]") || document.querySelectorAll("[data-choices]") || document.querySelectorAll("[data-provider]")) && (document.writeln("<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/toastify-js'><\/script>"), document.writeln("<script type='text/javascript' src='backend/assets/libs/choices.js/public/assets/scripts/choices.min.js'><\/script>"), document.writeln("<script type='text/javascript' src='backend/assets/libs/flatpickr/flatpickr.min.js'><\/script>"));

//gpt version
// (document.querySelectorAll("[toast-list]") ||
//  document.querySelectorAll("[data-choices]") ||
//  document.querySelectorAll("[data-provider]")) &&
// (
//   document.writeln("<script type='text/javascript' src='https://cdn.jsdelivr.net/npm/toastify-js'><\\/script>"),
//   document.writeln("<script type='text/javascript' src='{{ asset('backend/assets/scripts/choices.min.js') }}'><\\/script>"),
//   document.writeln("<script type='text/javascript' src='{{ asset('backend/assets/scripts/flatpickr.min.js') }}'><\\/script>")
// );
