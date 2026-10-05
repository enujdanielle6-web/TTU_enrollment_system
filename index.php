<?php
// Redirect root requests to the public directory where the front controller (index.php) resides.
header("Location: public/");
exit;
