<?php
/**
 * Hook to enforce mutually exclusive "issue_all" configuration
 * between PagHiper Boleto and PagHiper PIX.
 */

if (!defined("WHMCS")) die("This file cannot be accessed directly");

use Illuminate\Database\Capsule\Manager as Capsule;

add_hook('AdminAreaPage', 1, function($vars) {
    if ($vars['filename'] == 'configgateways' && isset($_POST['action']) && $_POST['action'] == 'save') {
        
        $module = $_REQUEST['module'] ?? '';
        
        if ($module == 'paghiper' && isset($_POST['field']['issue_all']) && $_POST['field']['issue_all'] == '1') {
            // PagHiper Boleto just enabled issue_all. Disable it on PIX.
            Capsule::table('tblpaymentgateways')
                ->where('gateway', 'paghiper_pix')
                ->where('setting', 'issue_all')
                ->update(['value' => '0']);
        } 
        elseif ($module == 'paghiper_pix' && isset($_POST['field']['issue_all']) && $_POST['field']['issue_all'] == '1') {
            // PagHiper PIX just enabled issue_all. Disable it on Boleto.
            Capsule::table('tblpaymentgateways')
                ->where('gateway', 'paghiper')
                ->where('setting', 'issue_all')
                ->update(['value' => '0']);
        }
    }
});
