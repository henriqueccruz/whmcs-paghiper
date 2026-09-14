<?php

// Boot WHMCS for Integration Tests
require_once __DIR__ . '/../../init.php';
require_once __DIR__ . '/../../modules/gateways/paghiper/inc/helpers/gateway_functions.php';

// Disable WHMCS exit/die on errors so tests don't randomly abort
$GLOBALS['customadminpath'] = 'admin'; // Just in case
