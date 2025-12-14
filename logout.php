<?php
session_start();
<<<<<<< HEAD
session_unset();
session_destroy();
header("Location: index.php");
exit();
=======
session_destroy();
header("Location: index.php"); 
>>>>>>> 302b6cc1279181be56d9673dd8460378816a0337
?>