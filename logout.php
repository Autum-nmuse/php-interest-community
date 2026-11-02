<?php
require 'config.php';
session_unset();
session_destroy();
session_start();
set_flash('已退出登录。');
redirect('index.php');
