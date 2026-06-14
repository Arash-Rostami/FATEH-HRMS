<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Task;
use Illuminate\Support\Facades\DB;
use App\Models\User;

// Ensure we have a user
$user = User::first();
if (!$user) {
    echo "No user found to test with.\n";
    die();
}

auth()->login($user);

DB::enableQueryLog();

$start = microtime(true);

$columns = ['todo', 'in-progress', 'done'];
$activeTab = 'my-tasks';
$userId = $user->id;
$page = ['todo' => 1, 'in-progress' => 1, 'done' => 1];
$perPage = 4;
$relationsToLoad = ['assignee:id,name', 'creator:id,name'];
$columnsToSelect = ['id', 'title', 'description', 'status', 'deadline', 'created_at', 'user_id', 'assigned_to'];

$totalCount = [];
$tasks = [];

foreach ($columns as $column) {
    $query = Task::query()
        ->where('status', $column)
        ->when($activeTab === 'my-tasks', fn($q) => $q->where(fn($sub) => $sub->where('assigned_to', $userId)
            ->orWhere(fn($sub2) => $sub2->where('user_id', $userId)->whereNull('assigned_to'))
        ))
        ->when($activeTab === 'assigned-tasks', fn($q) => $q->where('user_id', $userId)
            ->whereNotNull('assigned_to')
            ->where('assigned_to', '!=', $userId)
        );

    $totalCount[$column] = (clone $query)->count();
    $tasks[$column] = $query
        ->orderBy('created_at', 'desc')
        ->skip(($page[$column] - 1) * $perPage)
        ->take($perPage)
        ->with($relationsToLoad)
        ->get($columnsToSelect)
        ->toArray();
}

$end = microtime(true);

$queries = DB::getQueryLog();
echo "Original Method:\n";
echo "Queries executed: " . count($queries) . "\n";
echo "Time taken: " . ($end - $start) . " seconds\n";

DB::flushQueryLog();

// Optimized Method
$start = microtime(true);

$totalCount2 = [];
$tasks2 = [];

$baseQuery = Task::query()
    ->when($activeTab === 'my-tasks', fn($q) => $q->where(fn($sub) => $sub->where('assigned_to', $userId)
        ->orWhere(fn($sub2) => $sub2->where('user_id', $userId)->whereNull('assigned_to'))
    ))
    ->when($activeTab === 'assigned-tasks', fn($q) => $q->where('user_id', $userId)
        ->whereNotNull('assigned_to')
        ->where('assigned_to', '!=', $userId)
    );

$counts = (clone $baseQuery)
    ->select('status', DB::raw('count(*) as aggregate'))
    ->whereIn('status', $columns)
    ->groupBy('status')
    ->pluck('aggregate', 'status');

foreach ($columns as $column) {
    $totalCount2[$column] = $counts->get($column, 0);
}

// execute 3 queries for data
foreach ($columns as $column) {
    $tasks2[$column] = (clone $baseQuery)
        ->where('status', $column)
        ->orderBy('created_at', 'desc')
        ->skip(($page[$column] - 1) * $perPage)
        ->take($perPage)
        ->with($relationsToLoad)
        ->get($columnsToSelect)
        ->toArray();
}

$end = microtime(true);

$queries2 = DB::getQueryLog();
echo "\nOptimized Method (1 query for counts, 3 for data):\n";
echo "Queries executed: " . count($queries2) . "\n";
echo "Time taken: " . ($end - $start) . " seconds\n";

if ($totalCount !== $totalCount2) {
    echo "Counts do not match!\n";
}
