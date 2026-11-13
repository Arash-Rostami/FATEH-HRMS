<?php

namespace Tests\Feature\Services;

use App\Services\Template\Words\Greetings;
use App\Services\Template\Words\Quotes;
use App\Services\Template\Words\Sms;
use Tests\TestCase;

class TemplateServiceTest extends TestCase
{
    public function test_every_bank_answers_all_for_and_pick_the_same_way(): void
    {
        foreach ([Quotes::class => 'general', Sms::class => 'birthday', Greetings::class => 'morning'] as $bank => $key) {
            $this->assertArrayHasKey($key, $bank::all());
            $this->assertSame($bank::all()[$key], $bank::for($key));
            $this->assertContains($bank::pick($key, ['name' => '{name}']), $bank::for($key));
        }
    }

    public function test_an_unknown_key_gives_an_empty_list_and_an_empty_pick(): void
    {
        foreach ([Quotes::class, Sms::class, Greetings::class] as $bank) {
            $this->assertSame([], $bank::for('nope'));
            $this->assertSame('', $bank::pick('nope'));
        }
    }

    public function test_pick_fills_the_placeholders_it_is_given(): void
    {
        $line = Greetings::pick('night', ['name' => 'Sara']);

        $this->assertStringNotContainsString('{name}', $line);
        $this->assertStringContainsString('Sara', $line);
    }
}
