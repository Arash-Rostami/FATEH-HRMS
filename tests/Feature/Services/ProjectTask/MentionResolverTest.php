<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Services\ProjectTask\MentionResolver;
use Tests\TestCase;

class MentionResolverTest extends TestCase
{
    private function participants(array $pairs): \Illuminate\Support\Collection
    {
        return collect($pairs)->map(fn($p) => (object) ['id' => $p[0], 'name' => $p[1]]);
    }

    public function test_mentioned_users_matches_exact_name(): void
    {
        $participants = $this->participants([[1, 'Sara Ahmadi'], [2, 'Ali Rezaei']]);

        $found = (new MentionResolver())->mentionedUsers('hey @Sara Ahmadi can you check this', $participants);

        $this->assertCount(1, $found);
        $this->assertSame(1, $found->first()->id);
    }

    public function test_mentioned_users_returns_empty_when_no_match(): void
    {
        $participants = $this->participants([[1, 'Sara Ahmadi']]);

        $found = (new MentionResolver())->mentionedUsers('no mentions here', $participants);

        $this->assertCount(0, $found);
    }

    public function test_mentioned_users_does_not_false_positive_on_name_prefix(): void
    {
        $participants = $this->participants([[1, 'John']]);

        $found = (new MentionResolver())->mentionedUsers('@Johnny please look at this', $participants);

        $this->assertCount(0, $found, 'a longer word starting with a participant name must not match');
    }

    public function test_mentioned_users_prefers_longest_name_on_overlap(): void
    {
        $participants = $this->participants([[1, 'John'], [2, 'John Smith']]);

        $found = (new MentionResolver())->mentionedUsers('@John Smith is on it', $participants);

        $this->assertCount(1, $found);
        $this->assertSame(2, $found->first()->id, 'the longer, more specific name must win over a shorter prefix');
    }

    public function test_mentioned_users_empty_body_returns_empty(): void
    {
        $participants = $this->participants([[1, 'Sara Ahmadi']]);

        $found = (new MentionResolver())->mentionedUsers('', $participants);

        $this->assertCount(0, $found);
    }

    public function test_mentioned_users_empty_participants_returns_empty(): void
    {
        $found = (new MentionResolver())->mentionedUsers('@Anyone here?', collect());

        $this->assertCount(0, $found);
    }

    public function test_highlight_escapes_plain_body(): void
    {
        $participants = $this->participants([[1, 'Sara Ahmadi']]);

        $html = (new MentionResolver())->highlight('<b>bold</b> text with no mention', $participants);

        $this->assertSame('&lt;b&gt;bold&lt;/b&gt; text with no mention', $html);
    }

    public function test_highlight_wraps_mentioned_name_in_a_span(): void
    {
        $participants = $this->participants([[1, 'Sara Ahmadi']]);

        $html = (new MentionResolver())->highlight('hi @Sara Ahmadi', $participants);

        $this->assertStringContainsString('<span class="font-bold text-[var(--md-sys-color-primary)]">@Sara Ahmadi</span>', $html);
    }

    public function test_highlight_never_emits_raw_html_from_a_malicious_display_name(): void
    {
        $participants = $this->participants([[1, '"><script>alert(1)</script>']]);

        $html = (new MentionResolver())->highlight('hey @"><script>alert(1)</script> check this', $participants);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_highlight_is_safe_against_regex_metacharacters_in_a_name(): void
    {
        $participants = $this->participants([[1, 'A.B']]);

        $html = (new MentionResolver())->highlight('hey @A.B and @AxB too', $participants);

        $this->assertStringContainsString('<span class="font-bold text-[var(--md-sys-color-primary)]">@A.B</span>', $html, 'the dot must be treated as a literal character, not a regex wildcard');
        $this->assertStringNotContainsString('<span class="font-bold text-[var(--md-sys-color-primary)]">@AxB', $html, 'an unescaped dot would wrongly also match @AxB as a wildcard');
    }

    public function test_render_reuses_a_precomputed_mentioned_collection(): void
    {
        $mentioned = collect([(object) ['id' => 1, 'name' => 'Sara Ahmadi']]);

        $html = (new MentionResolver())->render('hi @Sara Ahmadi', $mentioned);

        $this->assertStringContainsString('@Sara Ahmadi</span>', $html);
    }
}
