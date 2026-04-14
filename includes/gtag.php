<?php
/**
 * Google Analytics — gtag.js
 * Controlado via GTAG_ID no .env.
 * Se GTAG_ID estiver vazio ou não definido, o snippet não é injetado.
 */
$_gtag_id = defined('GTAG_ID') ? GTAG_ID : '';
if ($_gtag_id): ?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($_gtag_id, ENT_QUOTES) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= htmlspecialchars($_gtag_id, ENT_QUOTES) ?>');
</script>
<?php endif; ?>
