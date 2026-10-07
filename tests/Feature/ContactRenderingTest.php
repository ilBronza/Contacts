<?php
namespace IlBronza\Contacts\Tests\Feature;

use IlBronza\Contacts\Models\Contact;
use IlBronza\Contacts\Models\Contacttype;
use IlBronza\Contacts\Providers\DatatablesFields\DatatableFieldContacts;
use IlBronza\Contacts\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ContactRenderingTest extends TestCase
{
    private function contact(string $value, ?string $scheme): Contact
    {
        $type = new Contacttype;
        $type->name = 'Example';
        $type->href_string = $scheme;
        return (new Contact)->forceFill(['contact' => $value])->setRelation('contacttype', $type);
    }

    private function dom(string $html): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        return new \DOMXPath($dom);
    }

    #[DataProvider('hrefExamples')]
    public function test_builds_safe_links(string $value, ?string $scheme, ?string $expected): void
    {
        $this->assertSame($expected, $this->contact($value, $scheme)->getHref());
    }

    public static function hrefExamples(): array
    {
        return [
            ['person@example.com', 'mailto', 'mailto:person@example.com'],
            ['person+tag@example.com', 'mailto', 'mailto:person%2Btag@example.com'],
            ['person@example.com?subject=bad', 'mailto', null],
            ['+39 (02) 1234-5678 x12', 'tel', 'tel:+390212345678;ext=12'],
            ['example.com/path', 'https', 'https://example.com/path'],
            ['https://example.com/path', 'https', 'https://example.com/path'],
            ['//example.com/path', 'https', 'https://example.com/path'],
            ['javascript:alert(1)', 'https', null],
            ['alert(1)', 'javascript', null],
            ['text/html,<script>bad</script>', 'data', null],
            ['value', null, null],
            ["example.com\n/path", 'https', null],
        ];
    }

    public function test_icons_escape_names_and_reject_attribute_injection(): void
    {
        $contact = $this->contact('person@example.com', 'mailto');
        $type = $contact->getContacttype();
        $type->name = 'Example" onmouseover="alert(1)';
        $type->fa_icon = 'envelope" onclick="alert(1)';
        $icon = $this->dom($type->getIcon())->query('//i')->item(0);
        $this->assertSame($type->name, $icon->getAttribute('title'));
        $this->assertSame('fa-solid fa-address-card', $icon->getAttribute('class'));
        $this->assertFalse($icon->hasAttribute('onmouseover'));
        $this->assertFalse($icon->hasAttribute('onclick'));
    }

    public function test_blade_and_datatable_do_not_render_unsafe_links_or_contact_markup(): void
    {
        $contact = $this->contact('<img src=x onerror=alert(1)>', 'javascript');
        $field = new DatatableFieldContacts('contacts');
        foreach ([$contact->getFullString(), $field->transformValue(collect([$contact]))] as $html) {
            $dom = $this->dom($html);
            $this->assertSame(0, $dom->query('//a')->length);
            $this->assertSame(0, $dom->query('//img')->length);
        }
    }

    public function test_tooltip_title_cannot_inject_html_or_uikit_options(): void
    {
        $value = '<img src=x onerror=alert(1)>; cls: other';
        $field = new DatatableFieldContacts('contacts');
        $field->tooltipClass = 'compact';
        $html = $field->transformValue(collect([$this->contact($value, null)]));
        $attribute = $this->dom($html)->query('//span')->item(0)->getAttribute('uk-tooltip');
        $options = json_decode($attribute, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(e($value), $options['title']);
        $this->assertSame('uk-active compact', $options['cls']);
        $this->assertStringNotContainsString('<img', $options['title']);
    }

    public function test_safe_links_are_rendered_in_both_paths(): void
    {
        $contact = $this->contact('person@example.com', 'mailto');
        $field = new DatatableFieldContacts('contacts');
        foreach ([$contact->getFullString(), $field->transformValue(collect([$contact]))] as $html) {
            $link = $this->dom($html)->query('//a')->item(0);
            $this->assertSame('mailto:person@example.com', $link->getAttribute('href'));
            $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
        }
    }

    public function test_missing_type_renders_plain_text(): void
    {
        $contact = (new Contact)->forceFill(['contact' => 'Legacy contact']);
        $this->assertStringContainsString('Legacy contact', $contact->getFullString());
        $this->assertStringNotContainsString('<a ', $contact->getFullString());
    }
}
