<?php

use App\Support\PhoneHelper;

// --- format() with country-specific masks ---

test('format Peru phone number (groups of 3)', function () {
    expect(PhoneHelper::format('51999888777'))->toBe('+51 999 888 777');
});

test('format Guatemala phone number (groups of 4)', function () {
    expect(PhoneHelper::format('50234850199'))->toBe('+502 3485 0199');
});

test('format US phone number (3-3-4 mask)', function () {
    expect(PhoneHelper::format('12125551234'))->toBe('+1 212 555 1234');
});

test('format Mexico mobile phone with prefix 1', function () {
    expect(PhoneHelper::format('5215512345678'))->toBe('+52 1 551 234 5678');
});

test('format strips leading plus sign', function () {
    expect(PhoneHelper::format('+51999888777'))->toBe('+51 999 888 777');
});

test('format Colombia phone number (3-3-4)', function () {
    expect(PhoneHelper::format('573001234567'))->toBe('+57 300 123 4567');
});

test('format Chile phone number (1-4-4)', function () {
    expect(PhoneHelper::format('56912345678'))->toBe('+56 9 1234 5678');
});

test('format Argentina phone number (1-2-4-4)', function () {
    expect(PhoneHelper::format('5491112345678'))->toBe('+54 9 11 1234 5678');
});

test('format Brazil phone number (2-5-4)', function () {
    expect(PhoneHelper::format('5511912345678'))->toBe('+55 11 91234 5678');
});

test('format Venezuela phone number (3-3-4)', function () {
    expect(PhoneHelper::format('584121234567'))->toBe('+58 412 123 4567');
});

test('format Ecuador phone number (1-4-4)', function () {
    expect(PhoneHelper::format('593912345678'))->toBe('+593 9 1234 5678');
});

test('format Paraguay phone number (groups of 3)', function () {
    expect(PhoneHelper::format('595981234567'))->toBe('+595 981 234 567');
});

test('format Bolivia phone number (groups of 4)', function () {
    expect(PhoneHelper::format('59171234567'))->toBe('+591 7123 4567');
});

test('format Honduras phone number (groups of 4)', function () {
    expect(PhoneHelper::format('50412345678'))->toBe('+504 1234 5678');
});

test('format Cuba phone number (groups of 4)', function () {
    expect(PhoneHelper::format('5351234567'))->toBe('+53 5123 4567');
});

test('format Dominican Republic via NANP prefix', function () {
    expect(PhoneHelper::format('18095551234'))->toBe('+1 809 555 1234');
});

test('format Puerto Rico via NANP prefix', function () {
    expect(PhoneHelper::format('17875551234'))->toBe('+1 787 555 1234');
});

test('format returns phone with plus for unknown country code', function () {
    expect(PhoneHelper::format('99912345678'))->toBe('+99912345678');
});

test('format handles empty string', function () {
    expect(PhoneHelper::format(''))->toBe('');
});

test('format falls back to generic grouping when mask does not match digit count', function () {
    // Spain has no mask, falls back to chunk_split by 4
    expect(PhoneHelper::format('34612345678'))->toBe('+34 6123 4567 8');
});

// --- detectCountryIso() ---

test('detect country ISO for Peru', function () {
    expect(PhoneHelper::detectCountryIso('51999888777'))->toBe('PE');
});

test('detect country ISO for Guatemala (3-digit code)', function () {
    expect(PhoneHelper::detectCountryIso('50234850199'))->toBe('GT');
});

test('detect country ISO for El Salvador (3-digit code)', function () {
    expect(PhoneHelper::detectCountryIso('50371234567'))->toBe('SV');
});

test('detect country ISO for US (1-digit code)', function () {
    expect(PhoneHelper::detectCountryIso('12125551234'))->toBe('US');
});

test('detect country ISO for Mexico', function () {
    expect(PhoneHelper::detectCountryIso('5215512345678'))->toBe('MX');
});

test('detect country ISO strips plus sign', function () {
    expect(PhoneHelper::detectCountryIso('+51999888777'))->toBe('PE');
});

test('detect country ISO returns null for unknown code', function () {
    expect(PhoneHelper::detectCountryIso('99912345678'))->toBeNull();
});

// --- detectCountryName() ---

test('detect country name for Peru', function () {
    expect(PhoneHelper::detectCountryName('51999888777'))->toBe('Perú');
});

test('detect country name for Guatemala', function () {
    expect(PhoneHelper::detectCountryName('50234850199'))->toBe('Guatemala');
});

test('detect country name returns null for unknown code', function () {
    expect(PhoneHelper::detectCountryName('99912345678'))->toBeNull();
});

// --- countryNameFromIso() ---

test('country name from ISO PE', function () {
    expect(PhoneHelper::countryNameFromIso('PE'))->toBe('Perú');
});

test('country name from ISO GT', function () {
    expect(PhoneHelper::countryNameFromIso('GT'))->toBe('Guatemala');
});

test('country name from ISO is case insensitive', function () {
    expect(PhoneHelper::countryNameFromIso('pe'))->toBe('Perú');
});

test('country name from ISO returns null for unknown', function () {
    expect(PhoneHelper::countryNameFromIso('ZZ'))->toBeNull();
});

// --- flagForPhone() ---

test('flag for Peru phone', function () {
    $flag = PhoneHelper::flagForPhone('51999888777');
    expect($flag)->not->toBeNull();
    expect(PhoneHelper::flagFromIso('PE'))->toBe($flag);
});

test('flag for Guatemala phone', function () {
    expect(PhoneHelper::flagForPhone('50234850199'))->not->toBeNull();
});

test('flag returns null for unknown phone', function () {
    expect(PhoneHelper::flagForPhone('99912345678'))->toBeNull();
});

// --- flagFromIso() ---

test('flag from ISO PE', function () {
    expect(PhoneHelper::flagFromIso('PE'))->not->toBeNull();
});

test('flag from ISO is case insensitive', function () {
    expect(PhoneHelper::flagFromIso('pe'))->toBe(PhoneHelper::flagFromIso('PE'));
});

test('flag from ISO returns null for unknown', function () {
    expect(PhoneHelper::flagFromIso('ZZ'))->toBeNull();
});

// --- 3-digit codes match before 2-digit ---

test('3-digit code 502 matches Guatemala not prefix 50', function () {
    expect(PhoneHelper::detectCountryIso('50234850199'))->toBe('GT');
});

test('3-digit code 591 matches Bolivia not prefix 59', function () {
    expect(PhoneHelper::detectCountryIso('59112345678'))->toBe('BO');
});

test('3-digit code 598 matches Uruguay', function () {
    expect(PhoneHelper::detectCountryIso('59899123456'))->toBe('UY');
});

// --- All Spanish-speaking LATAM countries ---

test('detect Dominican Republic via 1809 prefix', function () {
    expect(PhoneHelper::detectCountryIso('18095551234'))->toBe('DO');
    expect(PhoneHelper::detectCountryName('18295551234'))->toBe('República Dominicana');
    expect(PhoneHelper::detectCountryIso('18495551234'))->toBe('DO');
});

test('detect Puerto Rico via 1787 prefix', function () {
    expect(PhoneHelper::detectCountryIso('17875551234'))->toBe('PR');
    expect(PhoneHelper::detectCountryName('19395551234'))->toBe('Puerto Rico'); // Same in Spanish
});

test('detect all Hispanic LATAM countries', function () {
    expect(PhoneHelper::detectCountryIso('525512345678'))->toBe('MX');
    expect(PhoneHelper::detectCountryIso('50212345678'))->toBe('GT');
    expect(PhoneHelper::detectCountryIso('50312345678'))->toBe('SV');
    expect(PhoneHelper::detectCountryIso('50412345678'))->toBe('HN');
    expect(PhoneHelper::detectCountryIso('50512345678'))->toBe('NI');
    expect(PhoneHelper::detectCountryIso('50612345678'))->toBe('CR');
    expect(PhoneHelper::detectCountryIso('50712345678'))->toBe('PA');
    expect(PhoneHelper::detectCountryIso('5312345678'))->toBe('CU');
    expect(PhoneHelper::detectCountryIso('18095551234'))->toBe('DO');
    expect(PhoneHelper::detectCountryIso('17875551234'))->toBe('PR');
    expect(PhoneHelper::detectCountryIso('573001234567'))->toBe('CO');
    expect(PhoneHelper::detectCountryIso('584121234567'))->toBe('VE');
    expect(PhoneHelper::detectCountryIso('593912345678'))->toBe('EC');
    expect(PhoneHelper::detectCountryIso('51912345678'))->toBe('PE');
    expect(PhoneHelper::detectCountryIso('59171234567'))->toBe('BO');
    expect(PhoneHelper::detectCountryIso('56912345678'))->toBe('CL');
    expect(PhoneHelper::detectCountryIso('541112345678'))->toBe('AR');
    expect(PhoneHelper::detectCountryIso('59891234567'))->toBe('UY');
    expect(PhoneHelper::detectCountryIso('595981234567'))->toBe('PY');
    expect(PhoneHelper::detectCountryIso('5511912345678'))->toBe('BR');
    expect(PhoneHelper::detectCountryIso('240222123456'))->toBe('GQ');
});

test('US number that does not start with NANP Caribbean prefix', function () {
    expect(PhoneHelper::detectCountryIso('12025551234'))->toBe('US');
    expect(PhoneHelper::detectCountryIso('18091234567'))->toBe('DO');
});

// --- All Hispanic LATAM countries have flags ---

test('all Hispanic LATAM countries have flags', function () {
    $hispanicIsos = ['MX', 'GT', 'SV', 'HN', 'NI', 'CR', 'PA', 'CU', 'DO', 'PR', 'CO', 'VE', 'EC', 'PE', 'BO', 'CL', 'AR', 'UY', 'PY'];

    foreach ($hispanicIsos as $iso) {
        expect(PhoneHelper::flagFromIso($iso))->not->toBeNull("Flag missing for {$iso}");
    }
});
