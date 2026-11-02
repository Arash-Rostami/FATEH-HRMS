<?php

namespace Tests;

use Filament\Tables\Table;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Table::configureUsing(fn (Table $table) => $table->deferLoading(false));
    }
}
