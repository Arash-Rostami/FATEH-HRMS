<?php

namespace App\Traits;

trait TaskBoardValidation
{
    protected $rules = [
        'newTitle' => 'required|string|max:255',
        'newDescription' => 'nullable|string',
        'deadlineYear' => 'nullable|integer',
        'deadlineMonth' => 'nullable|integer|min:1|max:12',
        'deadlineDay' => 'nullable|integer|min:1|max:31',
    ];

    protected $messages = [
        'newTitle.required' => 'فیلد عنوان الزامی است.',
        'newTitle.string' => 'عنوان باید یک متن باشد.',
        'newTitle.max' => 'عنوان نباید بیش از :max کاراکتر باشد.',
        'newDescription.string' => 'توضیحات باید یک متن باشد.',
        'deadlineYear.integer' => 'سال سررسید باید یک عدد صحیح باشد.',
        'deadlineMonth.integer' => 'ماه سررسید باید یک عدد صحیح باشد.',
        'deadlineMonth.min' => 'ماه باید بین 1 تا 12 باشد.',
        'deadlineMonth.max' => 'ماه باید بین 1 تا 12 باشد.',
        'deadlineDay.integer' => 'روز سررسید باید یک عدد صحیح باشد.',
        'deadlineDay.min' => 'روز باید بین 1 تا 31 باشد.',
        'deadlineDay.max' => 'روز باید بین 1 تا 31 باشد.',
    ];

    protected $validationAttributes = [
        'newTitle' => 'عنوان',
        'newDescription' => 'توضیحات',
        'deadlineYear' => 'سال سررسید',
        'deadlineMonth' => 'ماه سررسید',
        'deadlineDay' => 'روز سررسید',
    ];
}
