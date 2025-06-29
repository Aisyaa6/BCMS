<?php
session_start();
session_destroy();
header("Location: ../public/index.html"); // or adjust path as needed
exit;
