
      </div>
    </section>
  </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<script src="../assets/dist/js/adminlte.min.js"></script>
<?php
if (!empty($nutritionExtraJs) && is_array($nutritionExtraJs)) {
    foreach ($nutritionExtraJs as $jsPath) {
        echo '<script src="' . barangay_h($jsPath) . '"></script>' . PHP_EOL;
    }
}
if (!empty($nutritionIncludeScriptsCsrf)) {
    $barangay_script_depth = 1;
    require_once __DIR__ . '/../scripts_csrf.php';
}
if (!empty($nutritionPageScript)) {
    echo $nutritionPageScript;
}
?>
</body>
</html>
