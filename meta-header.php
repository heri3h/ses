<link rel="shortcut icon" href="https://skuy.me/pastime/img/favi.png" type="image/x-icon" />

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-18426964855"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-18426964855');
</script>


<!-- Event snippet for Tayangan halaman conversion page
In your html page, add the snippet and call gtag_report_conversion when someone clicks on the chosen link or button. -->
<script>
function gtag_report_conversion(url) {
  var callback = function () {
    if (typeof(url) != 'undefined') {
      window.location = url;
    }
  };
  gtag('event', 'conversion', {
      'send_to': 'AW-18426964855/tHxoCMzLkO0cEPfW1NJE',
      'value': 1.0,
      'currency': 'IDR',
      'event_callback': callback
  });
  return false;
}
</script>
