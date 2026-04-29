<?php
<<<<<<< HEAD
session_start();
=======
require_once __DIR__ . '/includes/session.php';
>>>>>>> ffeb3514bac80d7341dced7515461cff6a741bfd
session_unset();
session_destroy();
header("Location: index.php");
exit();
?>

