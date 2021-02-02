<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vote;
use Illuminate\Http\JsonResponse;

class RankingController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param int $tg_id
     * @return JsonResponse
     */
    public function show(int $tg_id)
    {
        $user = User::where('tg_id', '=', $tg_id)->firstOrFail();

        $data = [
            'columns' => [
                ['x'],
                ['y0'],
                ['y1'],
            ],
            'subchart' => [
                'show' => true,
                'defaultZoom' => [
                    strtotime("28 days ago") * 1000,
                    strtotime(" 0 days ago") * 1000
                ]
            ],
            'types' => ['y0' => 'bar', 'y1' => 'bar', 'x' => 'x'],
            'names' => ['y0' => '通過', 'y1' => '駁回'],
            'colors' => ['y0' => '#7FA45F', 'y1' => '#B85052'],
            'hidden' => [],
            'strokeWidth' => 2,
            'xTickFormatter' => 'statsFormat("hour")',
            'xTooltipFormatter' => 'statsFormat("hour")',
            'xRangeFormatter' => 'null',
            'yTooltipFormatter' => 'statsFormatTooltipValue',
            'stacked' => true,
            'sideLegend' => 'statsNeedSideLegend()',
            'tooltipOnHover' => true,
        ];

        $name = "{$user->dep()} {$user->name}";
        $step = 6 * 60 * 60;

        $data['title'] = $name;
        $begin = strtotime(explode(' ', $user->created_at, 2)[0] . " 00:00");
        $end = strtotime("today 24:00");

        for ($i = $begin; $i <= $end; $i += $step) {
            $data['columns'][0][] = $i * 1000;
            $data['columns'][1][] = 0;
            $data['columns'][2][] = 0;
        }

        $VOTES = Vote::where('stuid', '=', $user->stuid)->get();
        foreach ($VOTES as $vote) {
            $ts = strtotime($vote['created_at']);
            $y = $vote['vote'] == 1 ? 1 : 2;
            $time = 1 + floor(($ts - $begin) / $step);
            $data['columns'][$y][$time]++;
        }

        return response()->json($data);
    }
}
