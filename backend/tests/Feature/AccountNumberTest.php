<?php

use App\Enums\InstitutionType;
use App\Models\Institution;
use App\Models\User;
use App\Support\AccountNumber;

/**
 * An account number is the only field on a merchant that decides where real
 * money lands. Accepting anything a keyboard can produce means a settlement can
 * be run against a payout target that cannot exist.
 *
 * What "valid" means depends on the institution: a mobile money wallet is
 * addressed by a Ghanaian mobile number, a bank account by a digit string whose
 * length varies between banks.
 */
describe('AccountNumber::normalise', function () {
    it('strips the separators people paste in', function () {
        expect(AccountNumber::normalise(' 024 123 4567 '))->toBe('0241234567')
            ->and(AccountNumber::normalise('1033-6620-13'))->toBe('1033662013');
    });
});

describe('mobile money', function () {
    it('accepts a Ghanaian mobile number', function (string $number) {
        expect(AccountNumber::isValidFor(InstitutionType::MobileMoney, $number))->toBeTrue();
    })->with(['0241234567', '0201234567', '0551234567', '0271234567', '0501234567']);

    it('rejects anything that is not one', function (string $number) {
        expect(AccountNumber::isValidFor(InstitutionType::MobileMoney, $number))->toBeFalse();
    })->with([
        'missing leading zero' => '241234567',
        'too short' => '024123456',
        'too long' => '02412345678',
        'not a mobile prefix' => '0301234567',
        'letters' => '024ABC4567',
        'empty' => '',
    ]);
});

describe('bank accounts', function () {
    it('accepts digit strings of a plausible length', function (string $number) {
        expect(AccountNumber::isValidFor(InstitutionType::Bank, $number))->toBeTrue();
    })->with(['1033662013', '00112233445', '1234567890123456']);

    it('rejects letters, punctuation and implausible lengths', function (string $number) {
        expect(AccountNumber::isValidFor(InstitutionType::Bank, $number))->toBeFalse();
    })->with([
        'too short' => '123456',
        'too long' => '123456789012345678901',
        'letters' => 'GB29NWBK',
        'empty' => '',
    ]);
});

describe('registering a merchant', function () {
    beforeEach(function () {
        $this->actingAs(User::factory()->create());
        Institution::factory()->create(['type' => InstitutionType::Bank]);
        Institution::factory()->mobileMoney()->create();
    });

    it('rejects a mobile money wallet that is not a mobile number', function () {
        $momo = Institution::where('type', InstitutionType::MobileMoney)->firstOrFail();

        $this->postJson('/api/merchants', [
            'name' => 'Ama Serwaa',
            'email' => 'ama@example.test',
            'business_name' => 'Kejetia Fresh Foods',
            'institution_id' => $momo->id,
            'account_number' => '7840523035',
        ])->assertStatus(422)->assertJsonValidationErrors('account_number');
    });

    it('accepts one that is, and stores it without separators', function () {
        $momo = Institution::where('type', InstitutionType::MobileMoney)->firstOrFail();

        $this->postJson('/api/merchants', [
            'name' => 'Ama Serwaa',
            'email' => 'ama@example.test',
            'business_name' => 'Kejetia Fresh Foods',
            'institution_id' => $momo->id,
            'account_number' => '024 123 4567',
        ])->assertCreated();

        $this->assertDatabaseHas('merchants', ['account_number' => '0241234567']);
    });

    it('rejects a bank account containing letters', function () {
        $bank = Institution::where('type', InstitutionType::Bank)->firstOrFail();

        $this->postJson('/api/merchants', [
            'name' => 'Kofi Boateng',
            'email' => 'kofi@example.test',
            'business_name' => 'Accra Tech Hub',
            'institution_id' => $bank->id,
            'account_number' => 'ACCT-90210',
        ])->assertStatus(422)->assertJsonValidationErrors('account_number');
    });
});
