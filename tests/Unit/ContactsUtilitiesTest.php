<?php
namespace IlBronza\Contacts\Tests\Unit;

use IlBronza\Contacts\Contacts;
use IlBronza\Contacts\Tests\TestCase;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;

class ContactsUtilitiesTest extends TestCase
{
    public function test_extracts_complete_emails_and_rejects_invalid_candidates(): void
    {
        $input = 'Write to Name+tag@mail.example.technology, <other@example.co.uk>. bad..name@example.com name@example..com';
        $this->assertSame(['Name+tag@mail.example.technology', 'other@example.co.uk'], Contacts::extractEmails($input));
        $this->assertSame([], Contacts::extractEmails('no addresses here'));
    }

    #[DataProvider('phoneExamples')]
    public function test_phone_normalization(string $input, ?string $expected): void
    {
        $this->assertSame($expected, Contacts::extractPhoneNumber($input));
    }

    public static function phoneExamples(): array
    {
        return [
            ['+39 (02) 1234-5678', '+390212345678'],
            ['02 1234 5678 ext. 42', '0212345678X42'],
            ['0212345678x42', '0212345678X42'],
            ['0039 02 1234 5678', '00390212345678'],
            ['1234567', '1234567'],
            ['123456', null],
            ['1234567890123456', null],
            ['++390212345678', null],
            ['abc0212345678', null],
            ['0212345678+42', null],
        ];
    }

    public function test_normalizes_domain_but_preserves_email_local_part(): void
    {
        $this->assertSame('Name+Tag@example.com', Contacts::normalizeAndValidateContact(' Name+Tag@EXAMPLE.COM ', 'email'));
    }

    public function test_custom_type_rules_and_normalizer_are_configurable(): void
    {
        config(['contacts.contactTypes.office' => ['normalizer' => 'phone', 'rules' => ['regex:/\A\+?[0-9]{7,15}\z/']]]);
        $this->assertSame('0212345678', Contacts::normalizeAndValidateContact('02 1234 5678', 'office'));
        $this->assertSame('Customer service', Contacts::normalizeAndValidateContact(' Customer service ', 'custom'));
        $this->expectException(ValidationException::class);
        Contacts::normalizeAndValidateContact('invalid', 'office');
    }
}
