<?php

class IpZone
{
    private $data = [];

    function __construct(array $rawData = [])
    {
        $data = [];
        foreach ($rawData as $section) {
            $checkResult = $this->checkZone($data, $section);
            if (empty($checkResult)) {
                $data[] = $section;
            } else {
                $data = $checkResult;
            }
        }
        $this->data = $this->arraySort($data, 0);
    }

    function getZone(): array
    {
        return $this->data;
    }

    function setZone(array $zoneData): IpZone
    {
        $this->data = $this->arraySort($zoneData, 0);
        return $this;
    }

    function isInZone(string $ip): bool
    {
        if (empty($this->data)) {
            return false;
        }
        $maxIndex = count($this->data);
        $minIndex = 0;
        $long = ip2long($ip);
        $midIndex = floor($maxIndex / 2);
        while (true) {
            $midVal = $this->data[$midIndex];
            $lastMid = $midIndex;
            if ($midVal[0] <= $long && $midVal[1] >= $long) {
                return true;
            }
            if ($midVal[0] > $long) {
                //未命中，在左区间
                $maxIndex = $midIndex;
                $midIndex = floor((($maxIndex - $minIndex) / 2) + $minIndex);
            } else {
                $minIndex = $midIndex;
                $midIndex = floor((($maxIndex - $midIndex) / 2) + $midIndex);
            }
            if ($lastMid == $midIndex) {
                return false;
            }
        }
    }

    private function checkZone($data, $section): array
    {
        foreach ($data as $key => $value) {
            list($start, $end) = $value;
            if ($section[1] >= $end && $section[0] <= $end) {
                //返回一个新区间
                $return = [$start, $end];
                if ($section[0] < $start) {
                    $return[0] = $section[0];
                }
                if ($section[1] > $end) {
                    $return[1] = $section[1];
                }
                $data[$key] = $return;
                return $data;
            }
        }
        return [];
    }

    private function arraySort($arr, $key, $type = 'asc'): array
    {
        $keysValue = $new_array = [];
        foreach ($arr as $k => $v) {
            $keysValue[$k] = $v[$key];
        }
        if ($type == 'asc') {
            asort($keysValue);
        } else {
            arsort($keysValue);
        }
        reset($keysValue);
        foreach ($keysValue as $k => $v) {
            $new_array[$k] = $arr[$k];
        }
        return $new_array;
    }

    static function string2zone(string $string)
    {
        $info = explode('/', $string);
        if (count($info) != 2) {
            return null;
        }
        $start = ip2long($info[0]);
        $startBit = str_pad(decbin($start), 32, '0', STR_PAD_LEFT);
        $endBit = substr($startBit, 0, $info[1]);
        $endBit = str_pad($endBit, 32, '1', STR_PAD_RIGHT);

        return [$start, bindec($endBit)];
    }
}

$arr = [
    '192.168.0.0/24',
    '192.168.31.1/24',
];

$f = [];

foreach ($arr as $value) {
    $f[] = IpZone::string2zone($value);
}

$obj = new IpZone($f);

var_dump($obj->isInZone('192.168.0.1'));
