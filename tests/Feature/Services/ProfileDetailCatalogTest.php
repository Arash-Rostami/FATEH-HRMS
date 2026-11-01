<?php

namespace Tests\Feature\Services;

use App\Enums\Language;
use App\Enums\Nationality;
use App\Enums\ProfileDetailGroup;
use App\Services\CityService;
use App\Services\ProfileDetailCatalog;
use Illuminate\Support\Once;
use ReflectionMethod;
use Tests\TestCase;

class ProfileDetailCatalogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Once::flush();
    }

    protected function tearDown(): void
    {
        Once::flush();
        parent::tearDown();
    }

    public function test_definitions_returns_the_full_catalog_with_expected_key_count(): void
    {
        $defs = ProfileDetailCatalog::definitions();

        $this->assertSame(69, count($defs));
        $this->assertArrayHasKey('display_name', $defs);
        $this->assertArrayHasKey('birthplace', $defs);
        $this->assertArrayHasKey('city_code', $defs);
        $this->assertArrayHasKey('unit', $defs);
        $this->assertArrayHasKey('section', $defs);
    }

    public function test_every_definition_carries_section_and_label_and_type(): void
    {
        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            $this->assertArrayHasKey('section', $def, "missing section for {$key}");
            $this->assertArrayHasKey('label', $def, "missing label for {$key}");
            $this->assertArrayHasKey('type', $def, "missing type for {$key}");
            $this->assertContains($def['section'], array_column(ProfileDetailGroup::cases(), 'value'));
        }
    }

    public function test_only_display_title_unit_section_and_rank_order_are_admin_only(): void
    {
        $adminOnly = [];
        foreach (ProfileDetailCatalog::definitions() as $key => $def) {
            if ($def['admin_only'] ?? false) {
                $adminOnly[] = $key;
            }
        }

        $this->assertSame(['display_title', 'unit', 'section', 'rank_order'], $adminOnly);
    }

    public function test_keys_matches_definitions_keys(): void
    {
        $this->assertSame(array_keys(ProfileDetailCatalog::definitions()), ProfileDetailCatalog::keys());
    }

    public function test_definition_returns_def_for_known_key_and_null_for_unknown(): void
    {
        $this->assertNotNull(ProfileDetailCatalog::definition('birthplace'));
        $this->assertSame('select', ProfileDetailCatalog::definition('birthplace')['type']);
        $this->assertNull(ProfileDetailCatalog::definition('does_not_exist'));
    }

    public function test_attributes_maps_default_prefixed_keys_to_labels_for_all_definitions(): void
    {
        $attrs = ProfileDetailCatalog::attributes();

        $this->assertSame(count(ProfileDetailCatalog::definitions()), count($attrs));
        $this->assertArrayHasKey('values.birthplace', $attrs);
        $this->assertSame(ProfileDetailCatalog::definition('birthplace')['label'], $attrs['values.birthplace']);
        $this->assertArrayHasKey('values.unit', $attrs);
        $this->assertArrayHasKey('values.section', $attrs);
    }

    public function test_attributes_respects_custom_prefix(): void
    {
        $attrs = ProfileDetailCatalog::attributes('profile');

        $this->assertArrayHasKey('profile.birthplace', $attrs);
        $this->assertArrayNotHasKey('values.birthplace', $attrs);
    }

    public function test_user_definitions_excludes_admin_only_entries(): void
    {
        $user = ProfileDetailCatalog::userDefinitions();

        $this->assertSame(65, count($user));
        $this->assertArrayNotHasKey('unit', $user);
        $this->assertArrayNotHasKey('section', $user);
        $this->assertArrayHasKey('birthplace', $user);
    }

    public function test_user_attributes_excludes_admin_only_and_uses_default_prefix(): void
    {
        $attrs = ProfileDetailCatalog::userAttributes();

        $this->assertSame(count(ProfileDetailCatalog::userDefinitions()), count($attrs));
        $this->assertArrayHasKey('values.birthplace', $attrs);
        $this->assertArrayNotHasKey('values.unit', $attrs);
    }

    public function test_user_attributes_respects_custom_prefix(): void
    {
        $attrs = ProfileDetailCatalog::userAttributes('profile');

        $this->assertArrayHasKey('profile.birthplace', $attrs);
        $this->assertArrayNotHasKey('values.birthplace', $attrs);
    }

    public function test_user_rules_excludes_admin_only_and_uses_default_prefix(): void
    {
        $rules = ProfileDetailCatalog::userRules();

        $this->assertSame(count(ProfileDetailCatalog::userDefinitions()), count($rules));
        $this->assertArrayHasKey('values.birthplace', $rules);
        $this->assertArrayNotHasKey('values.unit', $rules);
    }

    public function test_user_rules_respects_custom_prefix(): void
    {
        $rules = ProfileDetailCatalog::userRules('profile');

        $this->assertArrayHasKey('profile.birthplace', $rules);
        $this->assertArrayNotHasKey('values.birthplace', $rules);
    }

    public function test_grouped_groups_all_definitions_by_section_in_ordered_sequence(): void
    {
        $grouped = ProfileDetailCatalog::grouped();

        $this->assertSame(array_column(ProfileDetailGroup::ordered(), 'value'), array_keys($grouped));
        $this->assertContains('unit', array_keys($grouped['employment']));
        $this->assertContains('section', array_keys($grouped['employment']));
        $this->assertContains('birthplace', array_keys($grouped['identity']));
        $this->assertContains('city_code', array_keys($grouped['other']));
    }

    public function test_user_grouped_groups_user_definitions_and_keeps_all_sections_populated(): void
    {
        $grouped = ProfileDetailCatalog::userGrouped();

        $this->assertSame(array_column(ProfileDetailGroup::ordered(), 'value'), array_keys($grouped));
        $this->assertArrayNotHasKey('unit', $grouped['employment']);
        $this->assertArrayNotHasKey('section', $grouped['employment']);
        $this->assertNotEmpty($grouped['employment']);
        foreach ($grouped as $section => $fields) {
            $this->assertNotEmpty($fields, "{$section} unexpectedly empty");
        }
    }

    public function test_grouped_drops_empty_sections_when_none_present(): void
    {
        $grouped = ProfileDetailCatalog::grouped();

        foreach ($grouped as $section => $fields) {
            $this->assertNotEmpty($fields, "{$section} should not appear empty");
        }
        $this->assertSame(10, count($grouped));
    }

    public function test_select_groups_maps_each_group_label_to_its_field_label_list(): void
    {
        $groups = ProfileDetailCatalog::selectGroups();

        $this->assertSame(ProfileDetailGroup::Identity->getLabel(), 'اطلاعات هویتی تکمیلی');
        $this->assertArrayHasKey(ProfileDetailGroup::Identity->getLabel(), $groups);
        $this->assertArrayHasKey(ProfileDetailGroup::Other->getLabel(), $groups);
        $this->assertSame(
            collect(ProfileDetailCatalog::grouped()['identity'])->map(fn($d) => $d['label'])->all(),
            $groups[ProfileDetailGroup::Identity->getLabel()],
        );
        $this->assertContains('محل تولد', $groups[ProfileDetailGroup::Identity->getLabel()]);
        $this->assertContains('کد شهر', $groups[ProfileDetailGroup::Other->getLabel()]);
    }

    public function test_rules_includes_all_definitions_with_default_prefix(): void
    {
        $rules = ProfileDetailCatalog::rules();

        $this->assertSame(count(ProfileDetailCatalog::definitions()), count($rules));
        $this->assertArrayHasKey('values.birthplace', $rules);
        $this->assertArrayHasKey('values.unit', $rules);
        $this->assertArrayHasKey('values.height', $rules);
        $this->assertArrayHasKey('values.training_courses', $rules);
        $this->assertArrayHasKey('values.marriage_date', $rules);
    }

    public function test_rules_respects_custom_prefix(): void
    {
        $rules = ProfileDetailCatalog::rules('profile');

        $this->assertArrayHasKey('profile.birthplace', $rules);
        $this->assertArrayNotHasKey('values.birthplace', $rules);
    }

    public function test_rule_for_number_type(): void
    {
        $rules = ProfileDetailCatalog::rules();

        $this->assertSame('nullable|numeric|min:0|max:1000000', $rules['values.height']);
        $this->assertSame('nullable|numeric|min:0|max:1000000', $rules['values.weight']);
    }

    public function test_rule_for_textarea_type(): void
    {
        $this->assertSame('nullable|string|max:2000', ProfileDetailCatalog::rules()['values.training_courses']);
        $this->assertSame('nullable|string|max:2000', ProfileDetailCatalog::rules()['values.achievements']);
    }

    public function test_rule_for_date_type(): void
    {
        $this->assertSame('nullable|string|max:20', ProfileDetailCatalog::rules()['values.marriage_date']);
        $this->assertSame('nullable|string|max:20', ProfileDetailCatalog::rules()['values.prior_cooperation_start']);
    }

    public function test_rule_for_text_type_falls_through_to_default(): void
    {
        $this->assertSame('nullable|string|max:2000', ProfileDetailCatalog::rules()['values.company_name']);
        $this->assertSame('nullable|string|max:2000', ProfileDetailCatalog::rules()['values.job_title']);
        $this->assertSame('nullable|string|max:2000', ProfileDetailCatalog::rules()['values.city_code']);
    }

    public function test_rule_for_text_type_respects_per_definition_max_override(): void
    {
        $this->assertSame('nullable|string|max:80', ProfileDetailCatalog::rules()['values.display_title']);
    }

    public function test_rule_for_select_type_with_options_yields_in_clause_of_option_keys(): void
    {
        $rule = ProfileDetailCatalog::rules()['values.blood_type'];

        $this->assertStringStartsWith('nullable|string|in:', $rule);
        $this->assertStringContainsString('O+', $rule);
        $this->assertStringContainsString('AB-', $rule);
        $this->assertSame(
            'nullable|string|in:' . implode(',', array_keys(ProfileDetailCatalog::definition('blood_type')['options'])),
            $rule,
        );
    }

    public function test_rule_for_select_type_with_empty_options_yields_empty_in_clause(): void
    {
        $this->assertSame('nullable|string|in:', ProfileDetailCatalog::rules()['values.unit']);
        $this->assertSame('nullable|string|in:', ProfileDetailCatalog::rules()['values.section']);
    }

    public function test_rule_for_select_with_cities_options_uses_sorted_city_keys(): void
    {
        $rule = ProfileDetailCatalog::rules()['values.birthplace'];
        $cities = array_values(array_unique(CityService::all()));
        sort($cities);

        $this->assertSame('nullable|string|in:' . implode(',', $cities), $rule);
    }

    public function test_get_sorted_options_cities_returns_sorted_combined_map(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'getSortedOptions');
        $method->setAccessible(true);
        $result = $method->invoke(null, 'cities');

        $cities = array_values(array_unique(CityService::all()));
        sort($cities);
        $expected = array_combine($cities, $cities);

        $this->assertSame($expected, $result);
    }

    public function test_get_sorted_options_languages_returns_sorted_enum_values(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'getSortedOptions');
        $method->setAccessible(true);
        $result = $method->invoke(null, 'languages');

        $labels = array_column(Language::cases(), 'value');
        sort($labels);
        $expected = array_combine($labels, $labels);

        $this->assertSame($expected, $result);
    }

    public function test_get_sorted_options_nationalities_returns_sorted_enum_values(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'getSortedOptions');
        $method->setAccessible(true);
        $result = $method->invoke(null, 'nationalities');

        $labels = array_column(Nationality::cases(), 'value');
        sort($labels);
        $expected = array_combine($labels, $labels);

        $this->assertSame($expected, $result);
    }

    public function test_get_sorted_options_caches_result_across_calls(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'getSortedOptions');
        $method->setAccessible(true);

        $first = $method->invoke(null, 'cities');
        $second = $method->invoke(null, 'cities');

        $this->assertSame($first, $second);
    }

    public function test_has_not_returns_dard_nadard_map(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'hasNot');
        $method->setAccessible(true);

        $this->assertSame(['دارد' => 'دارد', 'ندارد' => 'ندارد'], $method->invoke(null));
    }

    public function test_list_combines_items_to_key_value_map(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'list');
        $method->setAccessible(true);

        $this->assertSame(['a' => 'a', 'b' => 'b'], $method->invoke(null, ['a', 'b']));
        $this->assertSame(range(0, 10), array_keys($method->invoke(null, range(0, 10))));
    }

    public function test_rule_for_directly_returns_expected_string_per_type_branch(): void
    {
        $method = new ReflectionMethod(ProfileDetailCatalog::class, 'ruleFor');
        $method->setAccessible(true);

        $this->assertSame('nullable|numeric|min:0|max:1000000', $method->invoke(null, ['type' => 'number']));
        $this->assertSame('nullable|string|max:2000', $method->invoke(null, ['type' => 'textarea']));
        $this->assertSame('nullable|string|max:20', $method->invoke(null, ['type' => 'date']));
        $this->assertSame('nullable|string|in:a,b', $method->invoke(null, ['type' => 'select', 'options' => ['a' => 'a', 'b' => 'b']]));
        $this->assertSame('nullable|string|in:', $method->invoke(null, ['type' => 'select', 'options' => []]));
        $this->assertSame('nullable|string|in:', $method->invoke(null, ['type' => 'select']));
        $this->assertSame('nullable|string|max:2000', $method->invoke(null, ['type' => 'text']));
        $this->assertSame('nullable|string|max:2000', $method->invoke(null, ['type' => 'unknown-type']));
    }

    public function test_definitions_select_options_for_religion_are_combined_list(): void
    {
        $religion = ProfileDetailCatalog::definition('religion')['options'];

        $this->assertSame(['اسلام' => 'اسلام', 'مسیحیت' => 'مسیحیت', 'یهودیت' => 'یهودیت', 'زرتشتی' => 'زرتشتی', 'سایر' => 'سایر'], $religion);
    }

    public function test_definitions_for_hasnot_options(): void
    {
        $this->assertSame(['دارد' => 'دارد', 'ندارد' => 'ندارد'], ProfileDetailCatalog::definition('foreign_residency')['options']);
        $this->assertSame(['دارد' => 'دارد', 'ندارد' => 'ندارد'], ProfileDetailCatalog::definition('clearance_certificate')['options']);
    }

    public function test_searchable_flag_set_on_city_language_nationality_selects(): void
    {
        $this->assertTrue(ProfileDetailCatalog::definition('birthplace')['searchable']);
        $this->assertTrue(ProfileDetailCatalog::definition('service_city')['searchable']);
        $this->assertTrue(ProfileDetailCatalog::definition('education_city')['searchable']);
        $this->assertTrue(ProfileDetailCatalog::definition('nationality')['searchable']);
        $this->assertTrue(ProfileDetailCatalog::definition('fluent_language')['searchable']);
        $this->assertArrayNotHasKey('searchable', ProfileDetailCatalog::definition('religion'));
    }
}