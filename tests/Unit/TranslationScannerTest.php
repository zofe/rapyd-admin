<?php

namespace Zofe\Rapyd\Tests\Unit;

use Zofe\Rapyd\Localization\TranslationScanner;
use Zofe\Rapyd\Tests\TestCase;

class TranslationScannerTest extends TestCase
{
    public function test_phrases_come_from_translation_calls_and_static_component_attributes()
    {
        $blade = <<<'BLADE'
            <x-rpd::table title="Suppliers" :items="$items">
                <x-slot name="filters"><x-rpd::input model="search" placeholder="Search..." :label="__('dynamic')" /></x-slot>
                <x-rpd::sort model="id" label="Id" />
                <a href="#">{{ __('Back') }}</a> @lang("Add") {{ trans('auth::user.name') }} {{ __('dashboard.title') }}
                <x-rpd::modal name="ship" title="Ship the order" actionLabel="Ship" />
                {{ __("It's fine") }}
            </x-slot>
            BLADE;

        $phrases = (new TranslationScanner)->phrasesIn($blade);

        // the __() calls in text order (a bound :label="__('…')" is one of them), then the static attributes
        $this->assertSame(['dynamic', 'Back', 'Add', "It's fine", 'Suppliers', 'Search...', 'Id', 'Ship the order', 'Ship'], $phrases);
    }

    public function test_keys_of_php_language_files_are_not_phrases()
    {
        $scanner = new TranslationScanner;
        $this->assertFalse($scanner->isPhrase('auth::user.name'));
        $this->assertFalse($scanner->isPhrase('dashboard.title'));
        $this->assertFalse($scanner->isPhrase('$label'));
        $this->assertTrue($scanner->isPhrase('Business Name'));
        $this->assertTrue($scanner->isPhrase('e.g. CA, BA'));
        $this->assertTrue($scanner->isPhrase('search...'));
    }

    public function test_raw_html_text_is_reported_with_its_line()
    {
        $blade = <<<'BLADE'
            @php($x = ['a' => null, ])
            @if($user->company->isEmpty())
                <a class="btn">Back</a>
            @endif
            <th>{{ __('Name') }}</th>
            <th>Vat number</th>
            {{-- <b>a comment</b> --}}
            <script>console.log('hello world');</script>
            <span>logged as &nbsp;</span>
            <option value="">{{ $placeholder }}</option>
            BLADE;

        $literals = (new TranslationScanner)->literalsIn($blade);

        $this->assertSame([[3, 'Back'], [6, 'Vat number'], [9, 'logged as']], $literals);
    }
}
