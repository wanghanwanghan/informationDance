<?php

namespace App\HttpController\Service\Export\Excel;

use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class ExportExcelService extends ServiceBase
{
    private $conf = [
        'path' => TEMP_FILE_PATH,
    ];
    private $header;
    private $data;

    function __construct()
    {
        return parent::__construct();
    }

    function setExcelHeader(array $header): ExportExcelService
    {
        $this->header = $header;
        return $this;
    }

    function setExcelAllData(array $data): ExportExcelService
    {
        $this->data = $data;
        return $this;
    }

    function setExcelStorePath(array $conf, $reset = false): ExportExcelService
    {
        $reset ?
            $this->conf = $conf :
            $this->conf = array_merge($this->conf, $conf);

        return $this;
    }

    function store(): string
    {
        $fileName = control::getUuid() . '.xlsx';

        $xlsxObject = new \Vtiful\Kernel\Excel($this->conf);

        $filePath = $xlsxObject
            ->fileName($fileName, 'sheet1')
            ->header($this->header)
            ->data($this->data)
            ->output();

        return $fileName;
    }


}


