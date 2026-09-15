<?php
session_start();
$hash = '$2y$10$PYE591EmpePNdISWozn4ZOOKlM7aF2MSklX1MAwCg9YaxtA05IlFC';
$_SESSION['admin_id'] = 1;
var_dump(password_verify('admin123', $hash));
