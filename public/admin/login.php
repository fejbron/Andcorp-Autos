<?php
/**
 * Redirect from old admin/login.php to new login.php
 * This file exists for backward compatibility
 */
require_once '../bootstrap.php';
redirect(url('login.php'));

