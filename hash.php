<?php
$password = 'admin123'; // غيرها لكلمة المرور اللي عايزاها
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
echo $hashed_password;
?>