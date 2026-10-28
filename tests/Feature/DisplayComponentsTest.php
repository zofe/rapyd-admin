<?php

namespace Zofe\Rapyd\Tests\Feature;

use Zofe\Rapyd\Tests\TestCase;

/** The components a list or a page prints a value with: a boolean as a word, a date as a date. */
class DisplayComponentsTest extends TestCase
{
    public function test_a_boolean_reads_as_a_word_with_an_icon()
    {
        $this->blade('<x-rpd::bool :value="true" />')->assertSee('Yes')->assertSee('fa-check-circle', false);
        $this->blade('<x-rpd::bool :value="0" />')->assertSee('No')->assertDontSee('fa-check-circle', false);
        $this->blade('<x-rpd::bool :value="$v" yes="Paid" no="Unpaid" />', ['v' => 1])->assertSee('Paid');
    }

    public function test_a_date_prints_from_a_carbon_a_string_or_nothing_at_all()
    {
        $this->blade('<x-rpd::date-formatted :date="$d" />', ['d' => now()->subDays(2)])
            ->assertSee(now()->subDays(2)->format('d/m/Y'))
            ->assertSee('2 days ago');

        $this->blade('<x-rpd::date-formatted :date="$d" format="d/m/Y" :ago="false" />', ['d' => '2026-03-01 10:30:00'])
            ->assertSee('01/03/2026')
            ->assertDontSee('ago');

        $this->assertSame('', trim((string) $this->blade('<x-rpd::date-formatted :date="$d" />', ['d' => null])), 'a null date prints nothing');
    }
}
