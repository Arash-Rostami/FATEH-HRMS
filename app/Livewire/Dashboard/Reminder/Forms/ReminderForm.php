<?php

namespace App\Livewire\Dashboard\Reminder\Forms;

use App\Models\Reminder;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ReminderForm extends Form
{
    #[Validate('required|string|min:2|max:255')]
    public string $title = '';

    public string $notes = '';

    public $dueYear = '';
    public $dueMonth = '';
    public $dueDay = '';
    public string $dueTime = '09:00';

    #[Validate('required|in:none,daily,weekly,monthly')]
    public string $recurs = 'none';

    public bool $emailEnabled = false;
    public bool $inAppEdge = true;
    public bool $inAppBadge = true;
    public bool $inAppNudge = true;

    public function validated(): array
    {
        return $this->validate();
    }

    public function toChannels(): array
    {
        $channels = [];

        $inApp = array_keys(array_filter([
            'edge' => $this->inAppEdge,
            'badge' => $this->inAppBadge,
            'nudge' => $this->inAppNudge,
        ]));

        if ($inApp !== []) {
            $channels[] = 'inapp:' . implode(',', $inApp);
        }

        if ($this->emailEnabled) {
            $channels[] = 'email';
        }

        return $channels;
    }

    public function fromChannels(array $channels): void
    {
        $this->emailEnabled = in_array('email', $channels, true);
        $this->inAppEdge = Reminder::channelEnabled($channels, 'edge');
        $this->inAppBadge = Reminder::channelEnabled($channels, 'badge');
        $this->inAppNudge = Reminder::channelEnabled($channels, 'nudge');
    }

    protected function rules(): array
    {
        return [
            'dueYear' => 'required',
            'dueMonth' => 'required|integer|min:1|max:12',
            'dueDay' => 'required|integer|min:1|max:31',
            'dueTime' => 'required|date_format:H:i',
        ];
    }

    protected function attributes(): array
    {
        return [
            'title' => 'عنوان',
            'recurs' => 'تکرار',
            'dueYear' => 'سال سررسید',
            'dueMonth' => 'ماه سررسید',
            'dueDay' => 'روز سررسید',
            'dueTime' => 'ساعت سررسید',
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'وارد کردن عنوان الزامی است.',
            'title.string' => 'عنوان باید متن باشد.',
            'title.min' => 'عنوان باید حداقل ۲ کاراکتر باشد.',
            'title.max' => 'عنوان نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'recurs.required' => 'انتخاب نوع تکرار الزامی است.',
            'recurs.in' => 'نوع تکرار معتبر نیست.',
            'dueYear.required' => 'انتخاب سال سررسید الزامی است.',
            'dueMonth.required' => 'انتخاب ماه سررسید الزامی است.',
            'dueMonth.min' => 'ماه باید بین ۱ تا ۱۲ باشد.',
            'dueMonth.max' => 'ماه باید بین ۱ تا ۱۲ باشد.',
            'dueDay.required' => 'انتخاب روز سررسید الزامی است.',
            'dueDay.min' => 'روز باید بین ۱ تا ۳۱ باشد.',
            'dueDay.max' => 'روز باید بین ۱ تا ۳۱ باشد.',
            'dueTime.required' => 'وارد کردن ساعت سررسید الزامی است.',
            'dueTime.date_format' => 'قالب ساعت معتبر نیست.',
        ];
    }
}
