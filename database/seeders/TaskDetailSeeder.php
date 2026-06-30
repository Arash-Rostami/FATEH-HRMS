<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskDetail;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskDetailSeeder extends Seeder
{
    public function run(): void
    {
        if (TaskDetail::count() > 0) {
            return;
        }

        $tasks = Task::all();
        $users = User::all();

        if ($tasks->isEmpty() || $users->isEmpty()) {
            return;
        }

        $departments = ['IT', 'HR', 'Finance', 'Operations', 'Legal', 'Planning', 'Audit', 'Support'];
        $units = ['تهیه', 'بازرسی', 'اجرای', 'پشتیبانی', 'مالی'];
        $sections = ['بخش مرکزی', 'بخش شمال', 'بخش جنوب', 'بخش معاونت'];
        $projects = ['پروژه توسعه', 'پروژه نوسازی', 'پروژه یکپارچه‌سازی', 'پروژه پایش'];
        $schemes = ['طرح اولیه', 'طرح تکمیلی', 'طرح pilots', 'طرح ملی'];
        $domains = ['internal', 'external', 'partner', 'vendor'];
        $states = ['draft', 'in_review', 'approved', 'in_progress', 'done', 'cancelled'];

        foreach ($tasks as $task) {
            TaskDetail::create([
                'task_id' => $task->id,
                'department_id' => fake('fa_IR')->randomElement($departments),
                'unit' => fake('fa_IR')->randomElement($units),
                'section' => fake('fa_IR')->randomElement($sections),
                'project' => fake('fa_IR')->randomElement($projects),
                'scheme' => fake('fa_IR')->randomElement($schemes),
                'action_source_domain' => fake('fa_IR')->randomElement($domains),
                'action_source' => fake('fa_IR')->sentence(),
                'collaborators' => $users->random(rand(0, 3))->pluck('id')->values()->all(),
                'responsible_user_id' => fake('fa_IR')->optional(0.8)->randomElement($users->pluck('id')->all()),
                'state' => fake('fa_IR')->randomElement($states),
                'attachments' => fake('fa_IR')->optional(0.5)->passthrough([
                    ['name' => fake('fa_IR')->word().'.pdf', 'path' => 'attachments/'.fake('fa_IR')->word().'.pdf'],
                ]),
            ]);
        }
    }
};