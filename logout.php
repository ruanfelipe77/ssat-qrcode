<?php
// src/controllers/LogoutController.php

session_start();
session_unset();
session_destroy();

header('Location: ../../public/login.php');
exit;
