<?php
session_start();

session_unset();
session_destroy();

header('Location: login.php');
exit;
session_unset();
session_destroy();
header('Location: ../login.php');
exit;
>>>>>>> 50c55f8a008be9bcda28bc86fc01a2fe49e49c16
