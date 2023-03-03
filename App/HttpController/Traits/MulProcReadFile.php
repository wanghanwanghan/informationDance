<?php

namespace App\HttpController\Traits;

trait MulProcReadFile
{
    // Multi-process file reading

    private $mp_start = 0;// 从第0页开始
    private $mp_step = 5;// 每页取5行
    private $mp_max_id = 0;// 文件的最大行 以 wc -l 的结果为准 最后一行要带上换行符

    private $mp_index = 0;// 当前进程号 从0开始
    private $mp_total = 5;// 共有多少个进程

    private $stopRead = false;

    function setMpStep(int $num): void
    {
        $this->mp_step = $num;
    }

    // 设置文件有多少行 必须在 doRead 之前
    function setMpMaxId(int $num): void
    {
        $this->mp_max_id = $num;
    }

    // 设置当前进程号 必须在 doRead 之前
    function setMpIndex(int $num): void
    {
        $this->mp_index = $num;
    }

    // 设置最大进程数 必须在 doRead 之前
    function setMpTotal(int $num): void
    {
        $this->mp_total = $num;
    }

    function doRead($fPath): ?array
    {
        if ($this->stopRead) return null;

        $p_start = $this->mp_max_id - ($this->mp_start * $this->mp_total + $this->mp_index) * $this->mp_step;
        $p_end = $p_start - $this->mp_step;

        if ($p_end >= 0) {
            $jump = $p_end;
        } else {
            $jump = $p_end = 0;
        }

        // ======================== 读文件 ========================
        $handle = new \SplFileObject($fPath, 'r');// 路径

        // 先跳过n行
        while ($jump--) {
            $current = $handle->current();
            $handle->next();
        }

        if ($p_end === 0) {
            $this->mp_step = $p_start;
        }

        $raw = [];

        for ($i = 1; $i <= $this->mp_step; $i++) {
            $current = $handle->current();
            if (!$current) break;
            $raw[] = $current;
            $handle->next();
        }
        // ======================== 读文件 ========================

        if ($p_end === 0) {
            $this->stopRead = true;
            return $raw;
        }

        $this->mp_start++;

        return $raw;
    }


}
