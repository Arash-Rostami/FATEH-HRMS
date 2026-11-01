<?php

namespace Tests\Feature\Enums;

use App\Enums\ReleaseRequestStatus;
use Tests\TestCase;

class ReleaseRequestStatusEnumTest extends TestCase
{
    public function test_cases_returns_all_four_cases_in_declaration_order(): void
    {
        $cases = ReleaseRequestStatus::cases();

        $this->assertSame(
            [ReleaseRequestStatus::Open, ReleaseRequestStatus::InReview, ReleaseRequestStatus::Resolved, ReleaseRequestStatus::Rejected],
            $cases,
        );
    }

    public function test_backed_values_match_the_string_contracts(): void
    {
        $this->assertSame('open', ReleaseRequestStatus::Open->value);
        $this->assertSame('in_review', ReleaseRequestStatus::InReview->value);
        $this->assertSame('resolved', ReleaseRequestStatus::Resolved->value);
        $this->assertSame('rejected', ReleaseRequestStatus::Rejected->value);
    }

    public function test_from_resolves_valid_backed_values_to_their_cases(): void
    {
        $this->assertSame(ReleaseRequestStatus::Open, ReleaseRequestStatus::from('open'));
        $this->assertSame(ReleaseRequestStatus::InReview, ReleaseRequestStatus::from('in_review'));
        $this->assertSame(ReleaseRequestStatus::Resolved, ReleaseRequestStatus::from('resolved'));
        $this->assertSame(ReleaseRequestStatus::Rejected, ReleaseRequestStatus::from('rejected'));
    }

    public function test_try_from_resolves_valid_values_and_returns_null_for_invalid(): void
    {
        $this->assertSame(ReleaseRequestStatus::Open, ReleaseRequestStatus::tryFrom('open'));
        $this->assertSame(ReleaseRequestStatus::InReview, ReleaseRequestStatus::tryFrom('in_review'));
        $this->assertSame(ReleaseRequestStatus::Resolved, ReleaseRequestStatus::tryFrom('resolved'));
        $this->assertSame(ReleaseRequestStatus::Rejected, ReleaseRequestStatus::tryFrom('rejected'));
        $this->assertNull(ReleaseRequestStatus::tryFrom('closed'));
        $this->assertNull(ReleaseRequestStatus::tryFrom(''));
        $this->assertNull(ReleaseRequestStatus::tryFrom('Open'));
    }

    public function test_from_throws_value_error_for_an_invalid_value(): void
    {
        $threw = false;
        try {
            ReleaseRequestStatus::from('not-a-status');
        } catch (\ValueError) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected ReleaseRequestStatus::from() to throw ValueError for an invalid value.');
    }

    public function test_get_label_returns_persian_label_for_each_case(): void
    {
        $this->assertSame('باز', ReleaseRequestStatus::Open->getLabel());
        $this->assertSame('در حال بررسی', ReleaseRequestStatus::InReview->getLabel());
        $this->assertSame('حل‌شده', ReleaseRequestStatus::Resolved->getLabel());
        $this->assertSame('عدم امکان', ReleaseRequestStatus::Rejected->getLabel());
    }

    public function test_get_color_returns_filament_color_token_for_each_case(): void
    {
        $this->assertSame('info', ReleaseRequestStatus::Open->getColor());
        $this->assertSame('warning', ReleaseRequestStatus::InReview->getColor());
        $this->assertSame('success', ReleaseRequestStatus::Resolved->getColor());
        $this->assertSame('danger', ReleaseRequestStatus::Rejected->getColor());
    }

    public function test_get_icon_returns_heroicon_identifier_for_each_case(): void
    {
        $this->assertSame('heroicon-o-chat-bubble-left-right', ReleaseRequestStatus::Open->getIcon());
        $this->assertSame('heroicon-o-clock', ReleaseRequestStatus::InReview->getIcon());
        $this->assertSame('heroicon-o-check-circle', ReleaseRequestStatus::Resolved->getIcon());
        $this->assertSame('heroicon-o-x-circle', ReleaseRequestStatus::Rejected->getIcon());
    }

    public function test_get_material_icon_returns_material_symbol_for_each_case(): void
    {
        $this->assertSame('forum', ReleaseRequestStatus::Open->getMaterialIcon());
        $this->assertSame('schedule', ReleaseRequestStatus::InReview->getMaterialIcon());
        $this->assertSame('check_circle', ReleaseRequestStatus::Resolved->getMaterialIcon());
        $this->assertSame('cancel', ReleaseRequestStatus::Rejected->getMaterialIcon());
    }

    public function test_get_material_color_returns_material_css_variable_for_each_case(): void
    {
        $this->assertSame('var(--md-sys-color-primary)', ReleaseRequestStatus::Open->getMaterialColor());
        $this->assertSame('var(--md-sys-color-tertiary)', ReleaseRequestStatus::InReview->getMaterialColor());
        $this->assertSame('var(--md-sys-color-secondary)', ReleaseRequestStatus::Resolved->getMaterialColor());
        $this->assertSame('var(--md-sys-color-error)', ReleaseRequestStatus::Rejected->getMaterialColor());
    }

    public function test_options_maps_backed_values_to_their_labels(): void
    {
        $expected = [
            'open' => 'باز',
            'in_review' => 'در حال بررسی',
            'resolved' => 'حل‌شده',
            'rejected' => 'عدم امکان',
        ];

        $this->assertSame($expected, ReleaseRequestStatus::options());
    }

    public function test_selectable_options_excludes_rejected(): void
    {
        $expected = [
            'open' => 'باز',
            'in_review' => 'در حال بررسی',
            'resolved' => 'حل‌شده',
        ];

        $this->assertSame($expected, ReleaseRequestStatus::selectableOptions());
    }
}
