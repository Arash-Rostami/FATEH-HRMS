<?php

namespace Database\Factories;

use App\Models\Onboarding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OnboardingFactory extends Factory
{
    protected $model = Onboarding::class;

    public function definition(): array
    {
        return [
            'welcome' => $this->richText('Welcome to our company! We are excited to have you join our team.'),

            'videos' => [
                ['title' => 'Company Culture Overview', 'url' => '/videos/culture.mp4'],
                ['title' => 'Office Tour', 'url' => '/videos/tour.mp4'],
                ['title' => 'Safety Guidelines', 'url' => '/videos/safety.mp4'],
            ],

            'mission' => $this->richText('To deliver exceptional value through innovation and customer-centric solutions.'),

            'vision' => $this->richText('To be the leading provider in our industry, recognized for excellence and integrity.'),

            'guides' => [
                ['title' => 'Employee Handbook 2024', 'url' => '/storage/guides/handbook.pdf', 'ext' => 'pdf'],
                ['title' => 'IT Security Policy', 'url' => '/storage/guides/it-security.docx', 'ext' => 'docx'],
                ['title' => 'Benefits Guide', 'url' => '/storage/guides/benefits.pdf', 'ext' => 'pdf'],
            ],

            'schedule' => $this->richText('
                <h3>9:00 AM - Welcome Coffee</h3>
                <p>Meet your buddy at the reception.</p>

                <h3>10:00 AM - HR Paperwork</h3>
                <p>Complete your documentation in Conference Room A.</p>

                <h3>2:00 PM - IT Setup</h3>
                <p>Get your laptop and accounts configured.</p>
            '),

            'extras' => [
                'parking' => $this->richText('Level B2, Slots 45-50 reserved for new hires. Your access card works 24/7.'),
                'dress_code' => $this->richText('Business casual Monday through Thursday. Casual Friday! No shorts or flip-flops in client areas.'),
                'it_setup' => $this->richText('Contact IT at ext. 404 or <a href="mailto:it@company.com">it@company.com</a>. Include your employee ID in all correspondence.'),
                'lunch_policy' => $this->richText('Cafeteria on 3rd floor. $10 daily subsidy loaded on your badge. Vegan and gluten-free options available daily.'),
            ],

            'is_active' => true,
            'user_id' => User::factory(), // HR admin who last updated
        ];
    }

    private function richText(string $content): string
    {
        return "<p>{$content}</p>";
    }

    /**
     * Indicate the onboarding is inactive (old version).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create with specific department extras.
     */
    public function forDepartment(string $dept): static
    {
        $extras = match($dept) {
            'engineering' => [
                'github_access' => $this->richText('Request access to organization at github.com/company. Use your work email.'),
                'slack_channels' => $this->richText('Join #engineering, #random, and your team-specific channel.'),
            ],
            'sales' => [
                'crm_login' => $this->richText('Salesforce credentials will be emailed by EOD. Check your spam folder.'),
                'targets_q1' => $this->richText('Review Q1 targets in the shared drive before your 1:1 with the Sales Director.'),
            ],
            default => [],
        };

        return $this->state(fn (array $attributes) => [
            'extras' => array_merge($attributes['extras'], $extras),
        ]);
    }
}
