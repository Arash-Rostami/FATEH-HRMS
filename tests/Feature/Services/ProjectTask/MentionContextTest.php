<?php

namespace Tests\Feature\Services\ProjectTask;

use App\Services\ProjectTask\MentionContext;
use App\Services\ProjectTask\MentionResolver;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MentionContextTest extends TestCase
{
    private function participants(array $pairs): Collection
    {
        return collect($pairs)->map(fn($p) => (object) ['id' => $p[0], 'name' => $p[1]]);
    }

    private function context(array $pairs): MentionContext
    {
        return (new MentionResolver())->context($this->participants($pairs));
    }

    public function test_context_mentioned_users_matches_exact_name(): void
    {
        $found = $this->context([[1, 'Sara Ahmadi'], [2, 'Ali Rezaei']])->mentionedUsers('hey @Sara Ahmadi check this');

        $this->assertCount(1, $found);
        $this->assertSame(1, $found->first()->id);
    }

    public function test_context_mentioned_users_returns_empty_for_empty_participants(): void
    {
        $found = (new MentionResolver())->context(collect())->mentionedUsers('@Anyone here?');

        $this->assertCount(0, $found);
    }

    public function test_context_mentioned_users_prefers_longest_name_on_overlap(): void
    {
        $found = $this->context([[1, 'John'], [2, 'John Smith']])->mentionedUsers('@John Smith is on it');

        $this->assertCount(1, $found);
        $this->assertSame(2, $found->first()->id, 'the longer, more specific name must win over a shorter prefix');
    }

    public function test_render_short_circuits_to_escaped_body_when_mentioned_is_empty(): void
    {
        $html = $this->context([[1, 'Sara Ahmadi']])->render('no mention here <b>x</b>', collect());

        $this->assertSame('no mention here &lt;b&gt;x&lt;/b&gt;', $html);
    }

    public function test_render_wraps_mentioned_name_in_span(): void
    {
        $ctx = $this->context([[1, 'Sara Ahmadi']]);
        $mentioned = $ctx->mentionedUsers('hi @Sara Ahmadi');

        $html = $ctx->render('hi @Sara Ahmadi', $mentioned);

        $this->assertStringContainsString('<span class="font-bold text-[var(--md-sys-color-primary)]">@Sara Ahmadi</span>', $html);
    }

    public function test_context_highlight_matches_legacy_resolver_output_for_a_malicious_name(): void
    {
        $participants = $this->participants([[1, '"><script>alert(1)</script>']]);
        $body = 'hey @"><script>alert(1)</script> check this';

        $legacy = (new MentionResolver())->highlight($body, $participants);
        $context = (new MentionResolver())->context($participants)->highlight($body);

        $this->assertSame($legacy, $context);
        $this->assertStringNotContainsString('<script>', $context);
        $this->assertStringContainsString('&lt;script&gt;', $context);
    }
}