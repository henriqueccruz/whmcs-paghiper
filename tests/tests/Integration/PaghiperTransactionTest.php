<?php

use WHMCS\Database\Capsule;
use WHMCS\Billing\Invoice;

beforeEach(function () {
    // Clean up our fake invoices before each test
    Capsule::table('tblinvoices')->where('userid', 999999)->delete();
    Capsule::table('mod_paghiper')->where('order_id', 9999999)->delete();
});

test('PaghiperTransaction respects reissue_unpaid tolerance days and generates new due date', function () {
    $due_date = date('Y-m-d', strtotime('-5 days'));
    
    Capsule::table('tblinvoices')->insert([
        'id' => 9999999,
        'userid' => 999999,
        'date' => date('Y-m-d'),
        'duedate' => $due_date,
        'datepaid' => '0000-00-00 00:00:00',
        'subtotal' => 100.00,
        'credit' => 0.00,
        'tax' => 0.00,
        'tax2' => 0.00,
        'total' => 100.00,
        'taxrate' => 0.00,
        'taxrate2' => 0.00,
        'status' => 'Unpaid',
        'paymentmethod' => 'paghiper_pix',
        'notes' => ''
    ]);

    Capsule::table('tblinvoiceitems')->insert([
        'invoiceid' => 9999999,
        'userid' => 999999,
        'type' => 'Item',
        'relid' => 0,
        'description' => 'Test Item',
        'amount' => 100.00,
        'taxed' => 0,
        'duedate' => $due_date,
        'paymentmethod' => 'paghiper_pix'
    ]);

    $gateway_config = [
        'name' => 'PagHiper Test',
        'reissue_unpaid' => 10,
        'porcento' => 0,
        'taxa' => 0,
        'apiKey' => 'MOCK_API_KEY',
        'token' => 'MOCK_TOKEN'
    ];

    $invoiceData = Invoice::with('items')->find(9999999)->toArray();
    $invoiceData['balance'] = 100.00;

    require_once __DIR__ . '/../../modules/gateways/paghiper/classes/PaghiperTransaction.php';
    
    $transaction = new PaghiperTransaction([
        'gateway' => 'paghiper_pix',
        'gatewayConf' => $gateway_config,
        'invoiceData' => $invoiceData,
        'whmcsVersion' => '9.0.0',
        'isPix' => true
    ]);

    $result = $transaction->process();
    expect($result)->not->toBe(['result' => 'reject', 'error' => 'reissue_not_allowed']);

    $gateway_config['reissue_unpaid'] = -1;
    
    $transaction_blocked = new PaghiperTransaction([
        'gateway' => 'paghiper_pix',
        'gatewayConf' => $gateway_config,
        'invoiceData' => $invoiceData,
        'whmcsVersion' => '9.0.0',
        'isPix' => true
    ]);
    
    $result_blocked = $transaction_blocked->process();
    expect($result_blocked['error'])->toBe('reissue_not_allowed');
    
    Capsule::table('tblinvoices')->where('userid', 999999)->delete();
    Capsule::table('tblinvoiceitems')->where('userid', 999999)->delete();
});
