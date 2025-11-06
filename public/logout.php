<?php
require_once 'bootstrap.php';

Auth::logout();
redirect(url('login.php'));
