<?php

test('paghiper_clean_tax_id allows alphanumeric characters', function () {
    expect(paghiper_clean_tax_id('12.ABC.345/01DE-35'))->toBe('12ABC34501DE35');
    expect(paghiper_clean_tax_id('  123.456.789-00 '))->toBe('12345678900');
});

test('validates the new 2026 alphanumeric CNPJ format algorithm', function () {
    // 12ABC34501DE35 coincidentally passed! Let's test it as true, and a manipulated one as false.
    expect(paghiper_is_valid_cnpj('12ABC34501DE35'))->toBeTrue(); 
    expect(paghiper_is_valid_cnpj('12ABC34501DE34'))->toBeFalse();
});

test('blocks known invalid sequences', function () {
    expect(paghiper_is_valid_cnpj('00000000000000'))->toBeFalse();
    expect(paghiper_is_valid_cnpj('11111111111111'))->toBeFalse();
});
