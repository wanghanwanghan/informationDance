<?php

namespace App\HttpController\Service\XinDong\Score;

use Carbon\Carbon;

class qpf
{
    //企配分

    private $frame = [
        'ys',//营收
        'hy',//行业
        'nx',//年限
        'dy',//地域
        'ys_ent',
        'hy_ent',
        'nx_ent',
        'dy_ent',
    ];

    private $num = 0;

    function __construct(...$args)
    {
        //8个参数，前4个是参考系，后4个是要出分数的企业参数
        $args = array_map(function ($row) {
            return trim($row);
        }, $args);
        $this->frame = array_combine($this->frame, $args);
    }

    private function expr_ys(): float
    {
        $saki = substr($this->frame['ys'], 1);
        $moto = substr($this->frame['ys_ent'], 1);
        return round((100 - 0.5 * (abs($saki - $moto))) * 0.5, 2);
    }

    private function expr_hy(): float
    {
        $saki = substr($this->frame['hy'], 0, 1);
        $moto = substr($this->frame['hy_ent'], 0, 1);

        $hy = $this->frame['hy'];
        $hy_ent = $this->frame['hy_ent'];

        if (!(is_numeric($saki) && is_numeric($moto)) && !(!is_numeric($saki) && !is_numeric($moto))) {
            is_numeric($saki) ? $hy_ent = substr($hy_ent, 1) : $hy = substr($hy, 1);
        }

        strlen($hy) >= strlen($hy_ent) ?
            //补齐绝不会出现的字符
            $hy_ent = str_pad($hy_ent, strlen($hy), '&', STR_PAD_RIGHT) :
            $hy = str_pad($hy, strlen($hy_ent), '&', STR_PAD_RIGHT);

        $subc = [
            10,//一级不同
            8,//二级不同
            5,
            1,
        ];
        $sub = 0;

        for ($i = 0; $i < strlen($hy); $i++) {
            if ($hy[$i] !== $hy_ent[$i]) {
                $sub = $subc[$i];
                break;
            }
        }

        return round((100 - $sub) * 0.3, 2);
    }

    private function expr_nx(): float
    {
        $nx = $this->frame['nx'];//int
        $nx_ent = $this->frame['nx_ent'];//year

        $sub = round(abs(Carbon::now()->format('Y') - $nx_ent - $nx));

        return round((100 - $sub) * 0.12, 2);
    }

    private function expr_dy(): float
    {
        $dy = $this->frame['dy'];
        $dy_ent = $this->frame['dy_ent'];

        strlen($dy) >= strlen($dy_ent) ?
            //补齐绝不会出现的字符
            $dy_ent = str_pad($dy_ent, strlen($dy), '&', STR_PAD_RIGHT) :
            $dy = str_pad($dy, strlen($dy_ent), '&', STR_PAD_RIGHT);

        $subc = [
            90,
            70,
            50,
            30,
            10,
            0,
        ];
        $sub = 0;

        for ($i = 0; $i < strlen($dy); $i++) {
            if ($dy[$i] !== $dy_ent[$i]) {
                $sub = $subc[$i];
                break;
            }
        }

        return round((100 - 0.5 * $sub) * 0.08, 2);
    }

    function expr(): float
    {
        $this->num += $this->expr_ys();
        $this->num += $this->expr_hy();
        $this->num += $this->expr_nx();
        $this->num += $this->expr_dy();

        return round($this->num, 2);
    }


}
