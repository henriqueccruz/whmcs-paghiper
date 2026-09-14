<?php
require_once __DIR__ . '/../modules/gateways/paghiper/inc/helpers/gateway_functions.php';

test('blocks CPFs with letters', function () {
    // If a user types AAAAAAAAA00, paghiper_clean_tax_id keeps the letters.
    // intval('A') is 0, so the math would treat it as 00000000000 and the check digits would match!
    expect(paghiper_is_valid_cpf('AAAAAAAAA00'))->toBeFalse();
    expect(paghiper_is_valid_cpf('123ABC45600'))->toBeFalse();
});
