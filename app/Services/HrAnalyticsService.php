<?php

namespace App\Services;

use App\Enums\PresenceStatus;
use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Widgets\Concerns\DepartmentAxis;
use Illuminate\Support\Facades\DB;

class HrAnalyticsService
{
    use DepartmentAxis;

    public function getHrAData(): array
    {
        $rows = DB::table('profiles')
            ->select('position', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('position', 'gender')
            ->get();

        $labels = array_map(fn(Position $p) => $p->getLabel(), Position::cases());
        $index = array_flip(array_map(fn(Position $p) => $p->value, Position::cases()));
        $n = count($index);

        $male = array_fill(0, $n, 0);
        $female = array_fill(0, $n, 0);
        $unknown = array_fill(0, $n, 0);
        $otherMale = $otherFemale = $otherUnknown = 0;

        foreach ($rows as $r) {
            $valid = $r->position !== null && Position::tryFrom($r->position) !== null;
            if ($valid && isset($index[$r->position])) {
                $i = $index[$r->position];
                if ($r->gender === 'male') $male[$i] += $r->count;
                elseif ($r->gender === 'female') $female[$i] += $r->count;
                else $unknown[$i] += $r->count;
            } else {
                if ($r->gender === 'male') $otherMale += $r->count;
                elseif ($r->gender === 'female') $otherFemale += $r->count;
                else $otherUnknown += $r->count;
            }
        }

        if ($otherMale + $otherFemale + $otherUnknown > 0) {
            $labels[] = 'سایر';
            $male[] = $otherMale;
            $female[] = $otherFemale;
            $unknown[] = $otherUnknown;
        }

        $datasets = [
            ['label' => 'آقا', 'data' => $male, 'backgroundColor' => '#3b82f6'],
            ['label' => 'خانم', 'data' => $female, 'backgroundColor' => '#ef4444'],
        ];
        if (array_sum($unknown) > 0) {
            $datasets[] = ['label' => 'نامشخص', 'data' => $unknown, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrBData(): array
    {
        $rows = DB::table('profiles')
            ->select('degree', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('degree', 'gender')
            ->get();

        $labels = array_map(fn(Degree $d) => $d->getLabel(), Degree::cases());
        $index = array_flip(array_map(fn(Degree $d) => $d->value, Degree::cases()));
        $n = count($index);

        $male = array_fill(0, $n, 0);
        $female = array_fill(0, $n, 0);
        $unknown = array_fill(0, $n, 0);
        $otherMale = $otherFemale = $otherUnknown = 0;

        foreach ($rows as $r) {
            $valid = $r->degree !== null && Degree::tryFrom($r->degree) !== null;
            if ($valid && isset($index[$r->degree])) {
                $i = $index[$r->degree];
                if ($r->gender === 'male') $male[$i] += $r->count;
                elseif ($r->gender === 'female') $female[$i] += $r->count;
                else $unknown[$i] += $r->count;
            } else {
                if ($r->gender === 'male') $otherMale += $r->count;
                elseif ($r->gender === 'female') $otherFemale += $r->count;
                else $otherUnknown += $r->count;
            }
        }

        if ($otherMale + $otherFemale + $otherUnknown > 0) {
            $labels[] = 'سایر';
            $male[] = $otherMale;
            $female[] = $otherFemale;
            $unknown[] = $otherUnknown;
        }

        $datasets = [
            ['label' => 'آقا', 'data' => $male, 'backgroundColor' => '#3b82f6'],
            ['label' => 'خانم', 'data' => $female, 'backgroundColor' => '#ef4444'],
        ];
        if (array_sum($unknown) > 0) {
            $datasets[] = ['label' => 'نامشخص', 'data' => $unknown, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrCData(): array
    {
        $rows = DB::table(DB::raw(
            '(SELECT gender, TIMESTAMPDIFF(YEAR, birthdate, NOW()) AS age FROM profiles WHERE birthdate IS NOT NULL) p'
        ))->select('gender', DB::raw("
            SUM(CASE WHEN age < 25 THEN 1 ELSE 0 END) as b1,
            SUM(CASE WHEN age BETWEEN 25 AND 34 THEN 1 ELSE 0 END) as b2,
            SUM(CASE WHEN age BETWEEN 35 AND 44 THEN 1 ELSE 0 END) as b3,
            SUM(CASE WHEN age BETWEEN 45 AND 54 THEN 1 ELSE 0 END) as b4,
            SUM(CASE WHEN age >= 55 THEN 1 ELSE 0 END) as b5
        "))->groupBy('gender')->get();

        $labels = ['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'];
        $male = array_fill(0, 5, 0);
        $female = array_fill(0, 5, 0);
        $unknown = array_fill(0, 5, 0);
        foreach ($rows as $r) {
            $bucket = [(int) $r->b1, (int) $r->b2, (int) $r->b3, (int) $r->b4, (int) $r->b5];
            foreach ($bucket as $i => $v) {
                if ($r->gender === 'male') $male[$i] += $v;
                elseif ($r->gender === 'female') $female[$i] += $v;
                else $unknown[$i] += $v;
            }
        }

        $datasets = [
            ['label' => 'آقا', 'data' => $male, 'backgroundColor' => '#3b82f6'],
            ['label' => 'خانم', 'data' => $female, 'backgroundColor' => '#ef4444'],
        ];
        if (array_sum($unknown) > 0) {
            $datasets[] = ['label' => 'نامشخص', 'data' => $unknown, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrDData(): array
    {
        $rows = DB::table('profiles')
            ->select('marital_status', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('marital_status', 'gender')
            ->get();

        $combos = [
            ['single', 'male', 'مجرد آقا', '#3b82f6'],
            ['single', 'female', 'مجرد خانم', '#ef4444'],
            ['married', 'male', 'متأهل آقا', '#0ea5e9'],
            ['married', 'female', 'متأهل خانم', '#8b5cf6'],
        ];

        $labels = [];
        $data = [];
        $colors = [];
        $known = 0;
        foreach ($combos as [$m, $g, $label, $color]) {
            $c = $rows->first(fn($r) => $r->marital_status === $m && $r->gender === $g)?->count ?? 0;
            $known += $c;
            $labels[] = $label;
            $colors[] = $color;
            $data[] = $c;
        }

        $other = $rows->sum('count') - $known;
        if ($other > 0) {
            $labels[] = 'سایر';
            $colors[] = '#94a3b8';
            $data[] = $other;
        }

        return [
            'datasets' => [
                ['label' => 'بافت تأهل و جنسیت', 'data' => $data, 'backgroundColor' => $colors],
            ],
            'labels' => $labels,
        ];
    }

    public function getHrEData(): array
    {
        $rows = DB::table('profiles')
            ->select('position', 'degree', DB::raw('COUNT(*) as count'))
            ->groupBy('position', 'degree')
            ->get();

        [$keys, $labels, $index] = $this->positionAxis($rows);
        $validPos = $this->positionMap();

        $colors = ['#64748b', '#0ea5e9', '#f59e0b', '#ef4444'];
        $degCases = Degree::cases();
        $degIndex = [];
        foreach ($degCases as $di => $deg) {
            $degIndex[$deg->value] = $di;
        }

        $n = count($keys);
        $datasets = [];
        foreach ($degCases as $di => $deg) {
            $datasets[] = ['label' => $deg->getLabel(), 'data' => array_fill(0, $n, 0), 'backgroundColor' => $colors[$di] ?? '#94a3b8'];
        }
        $otherData = array_fill(0, $n, 0);
        $hasOther = false;

        foreach ($rows as $r) {
            $posKey = ($r->position !== null && isset($validPos[$r->position])) ? $r->position : '__other__';
            $pi = $index[$posKey] ?? null;
            if ($pi === null) continue;
            if ($r->degree !== null && isset($degIndex[$r->degree])) {
                $datasets[$degIndex[$r->degree]]['data'][$pi] += $r->count;
            } else {
                $otherData[$pi] += $r->count;
                $hasOther = true;
            }
        }

        if ($hasOther) {
            $datasets[] = ['label' => 'سایر', 'data' => $otherData, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrFData(): array
    {
        $rows = DB::table(DB::raw("(SELECT degree, TIMESTAMPDIFF(YEAR, birthdate, NOW()) AS age FROM profiles WHERE birthdate IS NOT NULL) AS p"))
            ->select('degree', DB::raw("
                SUM(CASE WHEN age < 25 THEN 1 ELSE 0 END) as b1,
                SUM(CASE WHEN age BETWEEN 25 AND 34 THEN 1 ELSE 0 END) as b2,
                SUM(CASE WHEN age BETWEEN 35 AND 44 THEN 1 ELSE 0 END) as b3,
                SUM(CASE WHEN age BETWEEN 45 AND 54 THEN 1 ELSE 0 END) as b4,
                SUM(CASE WHEN age >= 55 THEN 1 ELSE 0 END) as b5
            "))->groupBy('degree')->get();

        $labels = ['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'];
        $colors = ['#64748b', '#0ea5e9', '#f59e0b', '#ef4444'];

        $degCases = Degree::cases();
        $degIndex = [];
        foreach ($degCases as $di => $deg) {
            $degIndex[$deg->value] = $di;
        }

        $byDeg = [];
        foreach ($rows as $r) {
            $byDeg[$r->degree] = [(int) $r->b1, (int) $r->b2, (int) $r->b3, (int) $r->b4, (int) $r->b5];
        }

        $datasets = [];
        foreach ($degCases as $di => $deg) {
            $data = $byDeg[$deg->value] ?? [0, 0, 0, 0, 0];
            $datasets[] = ['label' => $deg->getLabel(), 'data' => $data, 'backgroundColor' => $colors[$di] ?? '#94a3b8'];
        }

        $other = [0, 0, 0, 0, 0];
        $hasOther = false;
        foreach ($rows as $r) {
            if ($r->degree !== null && isset($degIndex[$r->degree])) continue;
            $hasOther = true;
            foreach ([(int) $r->b1, (int) $r->b2, (int) $r->b3, (int) $r->b4, (int) $r->b5] as $i => $v) $other[$i] += $v;
        }
        if ($hasOther) {
            $datasets[] = ['label' => 'سایر', 'data' => $other, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrGData(): array
    {
        $rows = DB::table(DB::raw("(SELECT position, TIMESTAMPDIFF(YEAR, start_date, NOW()) AS tenure FROM profiles WHERE start_date IS NOT NULL) AS p"))
            ->select('position', DB::raw("
                SUM(CASE WHEN tenure < 1 THEN 1 ELSE 0 END) as t1,
                SUM(CASE WHEN tenure BETWEEN 1 AND 3 THEN 1 ELSE 0 END) as t2,
                SUM(CASE WHEN tenure BETWEEN 4 AND 5 THEN 1 ELSE 0 END) as t3,
                SUM(CASE WHEN tenure BETWEEN 6 AND 10 THEN 1 ELSE 0 END) as t4,
                SUM(CASE WHEN tenure >= 11 THEN 1 ELSE 0 END) as t5
            "))->groupBy('position')->get();

        [$keys, $labels, $index] = $this->positionAxis($rows);
        $validPos = $this->positionMap();

        $bands = ['کمتر از ۱ سال', '۱ تا ۳ سال', '۴ تا ۵ سال', '۶ تا ۱۰ سال', 'بیش از ۱۰ سال'];
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];

        $n = count($keys);
        $datasets = [];
        for ($b = 0; $b < 5; $b++) {
            $datasets[] = ['label' => $bands[$b], 'data' => array_fill(0, $n, 0), 'backgroundColor' => $colors[$b]];
        }

        foreach ($rows as $r) {
            $posKey = ($r->position !== null && isset($validPos[$r->position])) ? $r->position : '__other__';
            $pi = $index[$posKey] ?? null;
            if ($pi === null) continue;
            for ($b = 0; $b < 5; $b++) {
                $datasets[$b]['data'][$pi] += (int) $r->{'t' . ($b + 1)};
            }
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrHData(): array
    {
        $topFields = DB::table('profiles')
            ->whereNotNull('field')
            ->where('field', '!=', '')
            ->select('field', DB::raw('COUNT(*) as cnt'))
            ->groupBy('field')
            ->orderByDesc('cnt')
            ->limit(6)
            ->pluck('field')
            ->all();

        $rows = DB::table('profiles')
            ->whereNotNull('field')
            ->where('field', '!=', '')
            ->select('position', 'field', DB::raw('COUNT(*) as count'))
            ->groupBy('position', 'field')
            ->get();

        [$keys, $labels, $index] = $this->positionAxis($rows);
        $validPos = $this->positionMap();

        $fields = $topFields;
        if ($rows->contains(fn($r) => !in_array($r->field, $topFields, true))) $fields[] = '__other__';
        $fieldIndex = array_flip($fields);
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#0ea5e9', '#94a3b8'];

        $n = count($keys);
        $datasets = [];
        foreach ($fields as $fi => $field) {
            $datasets[] = [
                'label' => $field === '__other__' ? 'سایر تخصص‌ها' : $field,
                'data' => array_fill(0, $n, 0),
                'backgroundColor' => $colors[$fi] ?? '#94a3b8',
            ];
        }

        foreach ($rows as $r) {
            $posKey = ($r->position !== null && isset($validPos[$r->position])) ? $r->position : '__other__';
            $pi = $index[$posKey] ?? null;
            if ($pi === null) continue;
            $fk = in_array($r->field, $topFields, true) ? $r->field : '__other__';
            $fi = $fieldIndex[$fk] ?? null;
            if ($fi === null) continue;
            $datasets[$fi]['data'][$pi] += $r->count;
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrIData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->whereIn('department_id', $codes)
            ->select('department_id', 'position', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id', 'position')
            ->get();

        $colors = ['#8b5cf6', '#6366f1', '#ef4444', '#f59e0b', '#0ea5e9', '#10b981', '#3b82f6', '#64748b'];
        $known = [];
        foreach (Position::cases() as $pi => $pos) {
            $known[] = ['value' => $pos->value, 'label' => $pos->getLabel(), 'color' => $colors[$pi] ?? '#94a3b8'];
        }
        $datasets = $this->bucketSeries($rows, $idx, 'position', $known, ['label' => 'سایر', 'color' => '#94a3b8'], count($codes));

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrJData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->whereIn('department_id', $codes)
            ->select('department_id', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id', 'gender')
            ->get();

        $known = [
            ['value' => 'male', 'label' => 'آقا', 'color' => '#3b82f6'],
            ['value' => 'female', 'label' => 'خانم', 'color' => '#ef4444'],
        ];
        $datasets = $this->bucketSeries($rows, $idx, 'gender', $known, ['label' => 'نامشخص', 'color' => '#94a3b8'], count($codes));

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrKData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::query()->fromSub(function ($q) use ($codes) {
            $q->from('profiles')
                ->whereIn('department_id', $codes)
                ->whereNotNull('birthdate')
                ->select('department_id', DB::raw('TIMESTAMPDIFF(YEAR, birthdate, NOW()) AS age'));
        }, 't')
            ->select('department_id',
                DB::raw('SUM(CASE WHEN age < 25 THEN 1 ELSE 0 END) as b1'),
                DB::raw('SUM(CASE WHEN age BETWEEN 25 AND 34 THEN 1 ELSE 0 END) as b2'),
                DB::raw('SUM(CASE WHEN age BETWEEN 35 AND 44 THEN 1 ELSE 0 END) as b3'),
                DB::raw('SUM(CASE WHEN age BETWEEN 45 AND 54 THEN 1 ELSE 0 END) as b4'),
                DB::raw('SUM(CASE WHEN age >= 55 THEN 1 ELSE 0 END) as b5'))
            ->groupBy('department_id')
            ->get();

        $bands = ['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'];
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];

        $datasets = [];
        for ($b = 0; $b < 5; $b++) {
            $data = array_fill(0, count($codes), 0);
            foreach ($rows as $r) {
                if (!isset($idx[$r->department_id])) continue;
                $data[$idx[$r->department_id]] += (int) ($r->{'b' . ($b + 1)});
            }
            $datasets[] = ['label' => $bands[$b], 'data' => $data, 'backgroundColor' => $colors[$b]];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrLData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->whereIn('department_id', $codes)
            ->select('department_id', 'employment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id', 'employment_status')
            ->get();

        $known = [
            ['value' => 'working', 'label' => 'فعال', 'color' => '#10b981'],
            ['value' => 'probational', 'label' => 'آزمایشی', 'color' => '#f59e0b'],
            ['value' => 'terminated', 'label' => 'خاتمه‌یافته', 'color' => '#ef4444'],
        ];
        $datasets = $this->bucketSeries($rows, $idx, 'employment_status', $known, ['label' => 'نامشخص', 'color' => '#94a3b8'], count($codes));

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrMData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->whereIn('department_id', $codes)
            ->select('department_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN employment_status = 'probational' THEN 1 ELSE 0 END) as cnt"))
            ->groupBy('department_id')
            ->get();

        $data = [];
        foreach ($codes as $code) {
            $row = $rows->firstWhere('department_id', $code);
            $t = $row?->total ?? 0;
            $p = $row?->cnt ?? 0;
            $data[] = $t > 0 ? round($p / $t * 100, 1) : 0;
        }

        return [
            'datasets' => [
                ['label' => 'سهم نیروی آزمایشی (٪)', 'data' => $data, 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $labels,
        ];
    }

    public function getHrNData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->whereIn('profiles.department_id', $codes)
            ->select('profiles.department_id', 'users.presence', DB::raw('COUNT(*) as count'))
            ->groupBy('profiles.department_id', 'users.presence')
            ->get();

        $size = count($codes);
        $pidx = [];
        $datasets = [];
        foreach (PresenceStatus::cases() as $p) {
            $pidx[$p->value] = count($datasets);
            $datasets[] = ['label' => $p->label(), 'data' => array_fill(0, $size, 0), 'backgroundColor' => $p->hex()];
        }

        $restData = null;
        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) continue;
            $di = $idx[$r->department_id];
            $pv = $r->presence;
            if (isset($pidx[$pv])) {
                $datasets[$pidx[$pv]]['data'][$di] += $r->count;
            } else {
                $restData ??= array_fill(0, $size, 0);
                $restData[$di] += $r->count;
            }
        }

        if ($restData !== null) {
            $datasets[] = ['label' => 'نامشخص', 'data' => $restData, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrOData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->whereIn('profiles.department_id', $codes)
            ->select('profiles.department_id', DB::raw("
                SUM(CASE WHEN users.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN users.last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END) as online
            "))->groupBy('profiles.department_id')->get();

        $active = array_fill(0, count($codes), 0);
        $online = array_fill(0, count($codes), 0);
        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) continue;
            $i = $idx[$r->department_id];
            $active[$i] += (int) $r->active;
            $online[$i] += (int) $r->online;
        }

        return [
            'datasets' => [
                ['label' => 'فعال', 'data' => $active, 'backgroundColor' => '#10b981'],
                ['label' => 'آنلاین (۱۵ دقیقه)', 'data' => $online, 'backgroundColor' => '#0ea5e9'],
            ],
            'labels' => $labels,
        ];
    }

    public function getHrPData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->whereIn('department_id', $codes)
            ->whereNotNull('birthdate')
            ->whereNotNull('start_date')
            ->whereRaw('TIMESTAMPDIFF(YEAR, birthdate, NOW()) >= 55')
            ->whereRaw('TIMESTAMPDIFF(YEAR, start_date, NOW()) >= 10')
            ->select('department_id', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id')
            ->get();

        $data = array_fill(0, count($codes), 0);
        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) continue;
            $data[$idx[$r->department_id]] += $r->count;
        }

        return [
            'datasets' => [
                ['label' => 'در معرض ریسک جانشینی', 'data' => $data, 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $labels,
        ];
    }

    public function getHrQData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->whereIn('department_id', $codes)
            ->where(fn($q) => $q->whereNull('field')->orWhere('field', ''))
            ->select('department_id', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id')
            ->get();

        $data = array_fill(0, count($codes), 0);
        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) continue;
            $data[$idx[$r->department_id]] += $r->count;
        }

        return [
            'datasets' => [
                ['label' => 'بدون تخصص ثبت‌شده', 'data' => $data, 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $labels,
        ];
    }

    private function positionAxis($rows): array
    {
        $keys = array_map(fn(Position $p) => $p->value, Position::cases());
        $hasOther = false;
        foreach ($rows as $r) {
            if ($r->position === null || Position::tryFrom($r->position) === null) {
                $hasOther = true;
                break;
            }
        }
        if ($hasOther) $keys[] = '__other__';
        $labels = array_map(fn($k) => $k === '__other__' ? 'سایر' : Position::from($k)->getLabel(), $keys);
        return [$keys, $labels, array_flip($keys)];
    }

    private function positionMap(): array
    {
        return array_flip(array_map(fn(Position $p) => $p->value, Position::cases()));
    }

    private function bucketSeries($rows, array $idx, string $column, array $known, ?array $rest, int $size): array
    {
        $map = [];
        foreach ($known as $i => $k) {
            $map[$k['value']] = $i;
        }
        $acc = [];
        foreach ($known as $k) {
            $acc[] = array_fill(0, $size, 0);
        }
        $restAcc = $rest !== null ? array_fill(0, $size, 0) : null;
        $hasRest = false;
        foreach ($rows as $r) {
            $val = $r->{$column} ?? null;
            $di = $map[$val] ?? null;
            if ($di === null) {
                if ($rest === null) continue;
                $hasRest = true;
                if (isset($idx[$r->department_id])) {
                    $restAcc[$idx[$r->department_id]] += $r->count;
                }
                continue;
            }
            if (isset($idx[$r->department_id])) {
                $acc[$di][$idx[$r->department_id]] += $r->count;
            }
        }
        $datasets = [];
        foreach ($known as $i => $k) {
            $datasets[] = ['label' => $k['label'], 'data' => $acc[$i], 'backgroundColor' => $k['color']];
        }
        if ($rest !== null && $hasRest) {
            $datasets[] = ['label' => $rest['label'], 'data' => $restAcc, 'backgroundColor' => $rest['color']];
        }
        return $datasets;
    }
}
