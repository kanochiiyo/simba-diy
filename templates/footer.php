<?php
$jsPath = file_exists(__DIR__ . '/../js/script.js') ? '../js/script.js' : 'js/script.js';
?>
<script src="<?php echo $jsPath; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"></script>
</body>

</html>