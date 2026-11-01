<?php

namespace Tests\Feature\Traits;

use App\Filament\Resources\AdResource\Schemas\AdFormPresenter;
use Filament\Infolists\Components\TextEntry;
use Tests\TestCase;

class FilamentFormDividerTest extends TestCase
{
    public function test_divider_returns_a_full_width_text_entry_named_divider()
    {
        $entry = AdFormPresenter::divider();

        $this->assertInstanceOf(TextEntry::class, $entry);
        $this->assertSame('divider', $entry->getName());
        $this->assertSame('full', $entry->getColumnSpan()['default']);
    }
}