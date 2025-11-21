<footer class="footer">
  <div class="container footer-inner">
    <p>
      © <span id="year"></span> – Bialadev Studio (Dylan Bialade). Tous droits réservés.
      · <a href="mentions-legales.php">Mentions légales &amp; Confidentialité</a>
    </p>
  </div>
</footer>

<script>
  // Année auto
  (function () {
    var yearSpan = document.getElementById('year');
    if (yearSpan) {
      yearSpan.textContent = new Date().getFullYear();
    }
  })();
</script>
