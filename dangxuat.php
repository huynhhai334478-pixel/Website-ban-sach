<?php
session_start();
session_unset(); 
session_destroy(); 

session_start();
$_SESSION['toast'] = [
    'type' => 'info',
    'message' => 'Bạn đã đăng xuất tài khoản thành công!'
];

header("Location: index.php"); 
exit();
?>