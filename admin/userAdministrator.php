<?php
header('Location: staffAccounts.php' . (isset($_GET['role']) ? '?role=' . urlencode((string) $_GET['role']) : ''));
exit;
