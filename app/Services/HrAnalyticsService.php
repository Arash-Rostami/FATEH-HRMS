<?php

namespace App\Services;

use App\Enums\PresenceStatus;
use App\Filament\Resources\ProfileResource\Enums\Degree;
use App\Filament\Resources\ProfileResource\Enums\Position;
use App\Filament\Resources\UserResource\Enums\UserType;
use App\Filament\Widgets\Concerns\DepartmentAxis;
use Illuminate\Support\Facades\DB;

class HrAnalyticsService
{
    use DepartmentAxis;

    public function getHrAData(): array
    {
        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('position', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('position', 'gender')
            ->get();

        $labels = [];
        $index = [];
        foreach (Position::cases() as $i => $p) {
            $labels[] = $p->getLabel();
            $index[$p->value] = $i;
        }

        $n = count($labels);
        $male = array_fill(0, $n, 0);
        $female = array_fill(0, $n, 0);
        $unknown = array_fill(0, $n, 0);
        $otherMale = $otherFemale = $otherUnknown = 0;

        foreach ($rows as $r) {
            $pos = $r->position;
            $count = (int)$r->count;

            if ($pos !== null && isset($index[$pos])) {
                $i = $index[$pos];
                if ($r->gender === 'male') {
                    $male[$i] += $count;
                } elseif ($r->gender === 'female') {
                    $female[$i] += $count;
                } else {
                    $unknown[$i] += $count;
                }
            } else {
                if ($r->gender === 'male') {
                    $otherMale += $count;
                } elseif ($r->gender === 'female') {
                    $otherFemale += $count;
                } else {
                    $otherUnknown += $count;
                }
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
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('degree', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('degree', 'gender')
            ->get();

        $labels = [];
        $index = [];
        foreach (Degree::cases() as $i => $d) {
            $labels[] = $d->getLabel();
            $index[$d->value] = $i;
        }

        $n = count($labels);
        $male = array_fill(0, $n, 0);
        $female = array_fill(0, $n, 0);
        $unknown = array_fill(0, $n, 0);
        $otherMale = $otherFemale = $otherUnknown = 0;

        foreach ($rows as $r) {
            $deg = $r->degree;
            $count = (int)$r->count;

            if ($deg !== null && isset($index[$deg])) {
                $i = $index[$deg];
                if ($r->gender === 'male') {
                    $male[$i] += $count;
                } elseif ($r->gender === 'female') {
                    $female[$i] += $count;
                } else {
                    $unknown[$i] += $count;
                }
            } else {
                if ($r->gender === 'male') {
                    $otherMale += $count;
                } elseif ($r->gender === 'female') {
                    $otherFemale += $count;
                } else {
                    $otherUnknown += $count;
                }
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
            "(SELECT profiles.gender, TIMESTAMPDIFF(YEAR, profiles.birthdate, NOW()) AS age FROM profiles JOIN users ON users.id = profiles.user_id AND users.type != '" . UserType::Guest->value . "' WHERE profiles.birthdate IS NOT NULL) p"
        ))->select('gender', DB::raw("
            SUM(CASE WHEN age < 25 THEN 1 ELSE 0 END) as b1,
            SUM(CASE WHEN age BETWEEN 25 AND 34 THEN 1 ELSE 0 END) as b2,
            SUM(CASE WHEN age BETWEEN 35 AND 44 THEN 1 ELSE 0 END) as b3,
            SUM(CASE WHEN age BETWEEN 45 AND 54 THEN 1 ELSE 0 END) as b4,
            SUM(CASE WHEN age >= 55 THEN 1 ELSE 0 END) as b5
        "))->groupBy('gender')->get();

        $labels = ['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'];
        $male = [0, 0, 0, 0, 0];
        $female = [0, 0, 0, 0, 0];
        $unknown = [0, 0, 0, 0, 0];

        foreach ($rows as $r) {
            $b1 = (int)$r->b1;
            $b2 = (int)$r->b2;
            $b3 = (int)$r->b3;
            $b4 = (int)$r->b4;
            $b5 = (int)$r->b5;

            if ($r->gender === 'male') {
                $male[0] += $b1;
                $male[1] += $b2;
                $male[2] += $b3;
                $male[3] += $b4;
                $male[4] += $b5;
            } elseif ($r->gender === 'female') {
                $female[0] += $b1;
                $female[1] += $b2;
                $female[2] += $b3;
                $female[3] += $b4;
                $female[4] += $b5;
            } else {
                $unknown[0] += $b1;
                $unknown[1] += $b2;
                $unknown[2] += $b3;
                $unknown[3] += $b4;
                $unknown[4] += $b5;
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
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('marital_status', 'gender', DB::raw('COUNT(*) as count'))
            ->groupBy('marital_status', 'gender')
            ->get();

        $map = [];
        $total = 0;
        foreach ($rows as $r) {
            $count = (int)$r->count;
            $map["{$r->marital_status}:{$r->gender}"] = $count;
            $total += $count;
        }

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
            $c = $map["{$m}:{$g}"] ?? 0;
            $known += $c;
            $labels[] = $label;
            $colors[] = $color;
            $data[] = $c;
        }

        $other = $total - $known;
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
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->select('position', 'degree', DB::raw('COUNT(*) as count'))
            ->groupBy('position', 'degree')
            ->get();

        [$keys, $labels, $index] = $this->positionAxis($rows);
        $validPos = $this->positionMap();

        $colors = ['#64748b', '#0ea5e9', '#f59e0b', '#ef4444'];
        $degCases = Degree::cases();
        $degIndex = [];
        $datasets = [];
        $n = count($keys);

        foreach ($degCases as $di => $deg) {
            $degIndex[$deg->value] = $di;
            $datasets[] = [
                'label' => $deg->getLabel(),
                'data' => array_fill(0, $n, 0),
                'backgroundColor' => $colors[$di] ?? '#94a3b8',
            ];
        }

        $otherData = array_fill(0, $n, 0);
        $hasOther = false;

        foreach ($rows as $r) {
            $posKey = ($r->position !== null && isset($validPos[$r->position])) ? $r->position : '__other__';
            if (!isset($index[$posKey])) {
                continue;
            }

            $pi = $index[$posKey];
            $count = (int)$r->count;

            if ($r->degree !== null && isset($degIndex[$r->degree])) {
                $datasets[$degIndex[$r->degree]]['data'][$pi] += $count;
            } else {
                $otherData[$pi] += $count;
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
        $rows = DB::table(DB::raw("(SELECT profiles.degree, TIMESTAMPDIFF(YEAR, profiles.birthdate, NOW()) AS age FROM profiles JOIN users ON users.id = profiles.user_id AND users.type != '" . UserType::Guest->value . "' WHERE profiles.birthdate IS NOT NULL) AS p"))
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
        $datasets = [];

        foreach ($degCases as $di => $deg) {
            $degIndex[$deg->value] = $di;
            $datasets[] = [
                'label' => $deg->getLabel(),
                'data' => [0, 0, 0, 0, 0],
                'backgroundColor' => $colors[$di] ?? '#94a3b8',
            ];
        }

        $other = [0, 0, 0, 0, 0];
        $hasOther = false;

        foreach ($rows as $r) {
            $b1 = (int)$r->b1;
            $b2 = (int)$r->b2;
            $b3 = (int)$r->b3;
            $b4 = (int)$r->b4;
            $b5 = (int)$r->b5;

            if ($r->degree !== null && isset($degIndex[$r->degree])) {
                $di = $degIndex[$r->degree];
                $datasets[$di]['data'][0] += $b1;
                $datasets[$di]['data'][1] += $b2;
                $datasets[$di]['data'][2] += $b3;
                $datasets[$di]['data'][3] += $b4;
                $datasets[$di]['data'][4] += $b5;
            } else {
                $hasOther = true;
                $other[0] += $b1;
                $other[1] += $b2;
                $other[2] += $b3;
                $other[3] += $b4;
                $other[4] += $b5;
            }
        }

        if ($hasOther) {
            $datasets[] = ['label' => 'سایر', 'data' => $other, 'backgroundColor' => '#94a3b8'];
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrGData(): array
    {
        $rows = DB::table(DB::raw("(SELECT profiles.position, TIMESTAMPDIFF(YEAR, profiles.start_date, NOW()) AS tenure FROM profiles JOIN users ON users.id = profiles.user_id AND users.type != '" . UserType::Guest->value . "' WHERE profiles.start_date IS NOT NULL) AS p"))
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
            if (!isset($index[$posKey])) {
                continue;
            }

            $pi = $index[$posKey];
            $datasets[0]['data'][$pi] += (int)$r->t1;
            $datasets[1]['data'][$pi] += (int)$r->t2;
            $datasets[2]['data'][$pi] += (int)$r->t3;
            $datasets[3]['data'][$pi] += (int)$r->t4;
            $datasets[4]['data'][$pi] += (int)$r->t5;
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrHData(): array
    {
        $topFields = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->whereNotNull('field')
            ->where('field', '!=', '')
            ->select('field', DB::raw('COUNT(*) as cnt'))
            ->groupBy('field')
            ->orderByDesc('cnt')
            ->limit(6)
            ->pluck('field')
            ->all();

        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->whereNotNull('field')
            ->where('field', '!=', '')
            ->select('position', 'field', DB::raw('COUNT(*) as count'))
            ->groupBy('position', 'field')
            ->get();

        [$keys, $labels, $index] = $this->positionAxis($rows);
        $validPos = $this->positionMap();

        $topFieldSet = array_flip($topFields);
        $hasOtherField = false;

        foreach ($rows as $r) {
            if (!isset($topFieldSet[$r->field])) {
                $hasOtherField = true;
                break;
            }
        }

        $fields = $topFields;
        if ($hasOtherField) {
            $fields[] = '__other__';
        }

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
            if (!isset($index[$posKey])) {
                continue;
            }

            $pi = $index[$posKey];
            $fk = isset($topFieldSet[$r->field]) ? $r->field : '__other__';
            if (!isset($fieldIndex[$fk])) {
                continue;
            }

            $datasets[$fieldIndex[$fk]]['data'][$pi] += (int)$r->count;
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrIData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
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
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
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
                ->join('users', 'profiles.user_id', '=', 'users.id')
                ->where('users.type', '!=', UserType::Guest->value)
                ->whereIn('department_id', $codes)
                ->whereNotNull('birthdate')
                ->select('department_id', DB::raw('TIMESTAMPDIFF(YEAR, birthdate, NOW()) AS age'));
        }, 't')
            ->select(
                'department_id',
                DB::raw('SUM(CASE WHEN age < 25 THEN 1 ELSE 0 END) as b1'),
                DB::raw('SUM(CASE WHEN age BETWEEN 25 AND 34 THEN 1 ELSE 0 END) as b2'),
                DB::raw('SUM(CASE WHEN age BETWEEN 35 AND 44 THEN 1 ELSE 0 END) as b3'),
                DB::raw('SUM(CASE WHEN age BETWEEN 45 AND 54 THEN 1 ELSE 0 END) as b4'),
                DB::raw('SUM(CASE WHEN age >= 55 THEN 1 ELSE 0 END) as b5')
            )
            ->groupBy('department_id')
            ->get();

        $bands = ['کمتر از ۲۵', '۲۵ تا ۳۴', '۳۵ تا ۴۴', '۴۵ تا ۵۴', '۵۵ به بالا'];
        $colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6'];
        $size = count($codes);

        $datasets = [];
        for ($b = 0; $b < 5; $b++) {
            $datasets[] = [
                'label' => $bands[$b],
                'data' => array_fill(0, $size, 0),
                'backgroundColor' => $colors[$b],
            ];
        }

        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) {
                continue;
            }

            $di = $idx[$r->department_id];
            $datasets[0]['data'][$di] += (int)$r->b1;
            $datasets[1]['data'][$di] += (int)$r->b2;
            $datasets[2]['data'][$di] += (int)$r->b3;
            $datasets[3]['data'][$di] += (int)$r->b4;
            $datasets[4]['data'][$di] += (int)$r->b5;
        }

        return ['datasets' => $datasets, 'labels' => $labels];
    }

    public function getHrLData(): array
    {
        [$codes, $labels, $idx] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
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
        [$codes, $labels] = $this->topDepartments();

        $rows = DB::table('profiles')
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->whereIn('department_id', $codes)
            ->select(
                'department_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN employment_status = 'probational' THEN 1 ELSE 0 END) as cnt")
            )
            ->groupBy('department_id')
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[$r->department_id] = $r;
        }

        $data = [];
        foreach ($codes as $code) {
            $row = $map[$code] ?? null;
            $t = (int)($row?->total ?? 0);
            $p = (int)($row?->cnt ?? 0);
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
            ->where('users.type', '!=', UserType::Guest->value)
            ->whereIn('profiles.department_id', $codes)
            ->select('profiles.department_id', 'users.presence', DB::raw('COUNT(*) as count'))
            ->groupBy('profiles.department_id', 'users.presence')
            ->get();

        $size = count($codes);
        $pidx = [];
        $datasets = [];

        foreach (PresenceStatus::cases() as $p) {
            $pidx[$p->value] = count($datasets);
            $datasets[] = [
                'label' => $p->label(),
                'data' => array_fill(0, $size, 0),
                'backgroundColor' => $p->hex(),
            ];
        }

        $restData = null;

        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) {
                continue;
            }

            $di = $idx[$r->department_id];
            $pv = $r->presence;
            $count = (int)$r->count;

            if ($pv !== null && isset($pidx[$pv])) {
                $datasets[$pidx[$pv]]['data'][$di] += $count;
            } else {
                $restData ??= array_fill(0, $size, 0);
                $restData[$di] += $count;
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
            ->where('users.type', '!=', UserType::Guest->value)
            ->whereIn('profiles.department_id', $codes)
            ->select('profiles.department_id', DB::raw("
                SUM(CASE WHEN users.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN users.last_seen >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) THEN 1 ELSE 0 END) as online
            "))->groupBy('profiles.department_id')->get();

        $size = count($codes);
        $active = array_fill(0, $size, 0);
        $online = array_fill(0, $size, 0);

        foreach ($rows as $r) {
            if (!isset($idx[$r->department_id])) {
                continue;
            }

            $i = $idx[$r->department_id];
            $active[$i] += (int)$r->active;
            $online[$i] += (int)$r->online;
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
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
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
            if (isset($idx[$r->department_id])) {
                $data[$idx[$r->department_id]] += (int)$r->count;
            }
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
            ->join('users', 'profiles.user_id', '=', 'users.id')
            ->where('users.type', '!=', UserType::Guest->value)
            ->whereIn('department_id', $codes)
            ->where(fn($q) => $q->whereNull('field')->orWhere('field', ''))
            ->select('department_id', DB::raw('COUNT(*) as count'))
            ->groupBy('department_id')
            ->get();

        $data = array_fill(0, count($codes), 0);
        foreach ($rows as $r) {
            if (isset($idx[$r->department_id])) {
                $data[$idx[$r->department_id]] += (int)$r->count;
            }
        }

        return [
            'datasets' => [
                ['label' => 'بدون تخصص ثبت‌شده', 'data' => $data, 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $labels,
        ];
    }

    private function bucketSeries(
        mixed  $rows,
        array  $idx,
        string $column,
        array  $known,
        ?array $rest,
        int    $size
    ): array
    {
        $map = [];
        $datasets = [];

        foreach ($known as $i => $k) {
            $map[$k['value']] = $i;
            $datasets[] = [
                'label' => $k['label'],
                'data' => array_fill(0, $size, 0),
                'backgroundColor' => $k['color'],
            ];
        }

        $restData = null;

        foreach ($rows as $r) {
            $depId = $r->department_id;
            if (!isset($idx[$depId])) {
                continue;
            }

            $di = $idx[$depId];
            $val = $r->{$column} ?? null;
            $count = (int)$r->count;

            if ($val !== null && isset($map[$val])) {
                $datasets[$map[$val]]['data'][$di] += $count;
            } elseif ($rest !== null) {
                $restData ??= array_fill(0, $size, 0);
                $restData[$di] += $count;
            }
        }

        if ($rest !== null && $restData !== null) {
            $datasets[] = [
                'label' => $rest['label'],
                'data' => $restData,
                'backgroundColor' => $rest['color'],
            ];
        }

        return $datasets;
    }

    private function positionAxis(mixed $rows): array
    {
        $keys = [];
        $labels = [];
        $validMap = [];

        foreach (Position::cases() as $p) {
            $keys[] = $p->value;
            $labels[] = $p->getLabel();
            $validMap[$p->value] = true;
        }

        $hasOther = false;
        foreach ($rows as $r) {
            if ($r->position === null || !isset($validMap[$r->position])) {
                $hasOther = true;
                break;
            }
        }

        if ($hasOther) {
            $keys[] = '__other__';
            $labels[] = 'سایر';
        }

        return [$keys, $labels, array_flip($keys)];
    }

    private function positionMap(): array
    {
        return array_flip(array_map(fn(Position $p) => $p->value, Position::cases()));
    }
}
