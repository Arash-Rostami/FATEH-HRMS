import re

with open('app/Livewire/Dashboard/TaskBoard/Main.php', 'r') as f:
    content = f.read()

# Add use statement
content = content.replace('use Illuminate\\Database\\Eloquent\\Builder;', 'use Illuminate\\Database\\Eloquent\\Builder;\nuse Illuminate\\Support\\Facades\\DB;')

# Update loadTasks method
new_load_tasks = """    public function loadTasks(): void
    {
        $userId = auth()->id();

        $baseQuery = Task::query()
            ->when($this->activeTab === 'my-tasks', fn($q) => $q->where(fn($sub) => $sub->where('assigned_to', $userId)
                ->orWhere(fn($sub2) => $sub2->where('user_id', $userId)->whereNull('assigned_to'))
            ))
            ->when($this->activeTab === 'assigned-tasks', fn($q) => $q->where('user_id', $userId)
                ->whereNotNull('assigned_to')
                ->where('assigned_to', '!=', $userId)
            );

        $counts = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->whereIn('status', $this->columns)
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        foreach ($this->columns as $column) {
            $this->totalCount[$column] = $counts->get($column, 0);
            $this->tasks[$column] = (clone $baseQuery)
                ->where('status', $column)
                ->orderBy('created_at', 'desc')
                ->skip(($this->page[$column] - 1) * $this->perPage)
                ->take($this->perPage)
                ->with($this->relationsToLoad)
                ->get($this->columnsToSelect)
                ->toArray();
        }
    }"""

# Replace loadTasks
import re
content = re.sub(r'    public function loadTasks\(\): void\n    \{.*?    \}', new_load_tasks, content, flags=re.DOTALL)

with open('app/Livewire/Dashboard/TaskBoard/Main.php', 'w') as f:
    f.write(content)
