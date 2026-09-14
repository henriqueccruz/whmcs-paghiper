<?php

use WHMCS\Database\Capsule;
use WHMCS\Billing\Invoice;

beforeEach(function () {
    Capsule::table('tblclients')->where('id', 999999)->delete();
    Capsule::table('tblcustomfieldsvalues')->where('relid', 999999)->delete();
    Capsule::table('tblinvoices')->where('userid', 999999)->delete();
    Capsule::table('tblinvoiceitems')->where('userid', 999999)->delete();
    Capsule::table('mod_paghiper')->where('order_id', 9999999)->delete();

    Capsule::table('tblclients')->insert([
        'id' => 999999,
        'firstname' => 'Test',
        'lastname' => 'Client',
        'email' => 'test@example.com',
        'address1' => 'Street',
        'city' => 'City',
        'state' => 'SP',
        'postcode' => '01000-000',
        'country' => 'BR',
        'phonenumber' => '11999999999'
    ]);

    $cpf_field_ids = explode('|', getGatewayVariables('paghiper_pix')['cpf_cnpj'] ?? '1');
    Capsule::table('tblcustomfields')->where('id', trim($cpf_field_ids[0]))->delete();
    Capsule::table('tblcustomfields')->insert(['id' => trim($cpf_field_ids[0]), 'type' => 'client', 'relid' => 0, 'fieldname' => 'CPF', 'fieldtype' => 'text']);
    Capsule::table('tblcustomfieldsvalues')->insert([
        'fieldid' => trim($cpf_field_ids[0]),
        'relid' => 999999,
        'value' => '51184608598' 
    ]);
});

afterAll(function () {
    Capsule::table('tblclients')->where('id', 999999)->delete();
    Capsule::table('tblcustomfieldsvalues')->where('relid', 999999)->delete();
    Capsule::table('tblinvoices')->where('userid', 999999)->delete();
    Capsule::table('tblinvoiceitems')->where('userid', 999999)->delete();
    Capsule::table('mod_paghiper')->where('order_id', 9999999)->delete();
});

function createMockInvoice($days_due) {
    $due_date = date('Y-m-d', strtotime($days_due . ' days'));
    Capsule::table('tblinvoices')->insert([
        'id' => 9999999, 'userid' => 999999, 'date' => date('Y-m-d'), 'duedate' => $due_date,
        'datepaid' => '0000-00-00 00:00:00', 'subtotal' => 100.00, 'credit' => 0.00,
        'tax' => 0.00, 'tax2' => 0.00, 'total' => 100.00, 'taxrate' => 0.00, 'taxrate2' => 0.00,
        'status' => 'Unpaid', 'paymentmethod' => 'paghiper_pix', 'notes' => ''
    ]);
    Capsule::table('tblinvoiceitems')->insert([
        'invoiceid' => 9999999, 'userid' => 999999, 'type' => 'Item', 'relid' => 0,
        'description' => 'Test Item', 'amount' => 100.00, 'taxed' => 0, 'duedate' => $due_date,
        'paymentmethod' => 'paghiper_pix'
    ]);
}

test('permite emissao se fatura estiver vencida MAS dentro da tolerancia de dias', function () {
    createMockInvoice(-5);
    $gateway_config = [
        'name' => 'PagHiper Test', 'reissue_unpaid' => 10, 'porcento' => 0, 'taxa' => 0,
        'apiKey' => 'MOCK_API_KEY', 'token' => 'MOCK_TOKEN',
        'cpf_cnpj' => getGatewayVariables('paghiper_pix')['cpf_cnpj'] ?? '1'
    ];
    require_once __DIR__ . '/../../../modules/gateways/paghiper/classes/PaghiperTransaction.php';
    $transaction = new PaghiperTransaction([
        'gateway' => 'paghiper_pix', 'gatewayConf' => $gateway_config, 'invoiceID' => 9999999,
        'whmcsVersion' => '9.0.0', 'isPix' => true
    ]);
    ob_start();
    try { $transaction->process(); } catch (Exception $e) {}
    $html_output = ob_get_clean();
    expect($html_output)->not->toContain('Transação vencida');
});
