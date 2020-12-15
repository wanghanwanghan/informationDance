<?php

namespace App\HttpController\Service\NewGraph;

use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\LinePlot;
use Amenadiel\JpGraph\Plot\PiePlot;
use App\HttpController\Service\ServiceBase;
use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\BarPlot;
use Amenadiel\JpGraph\Plot\GroupBarPlot;
use wanghanwanghan\someUtils\control;

class NewGraphService extends ServiceBase
{
    private $color = ['red','orange','green','blue','purple','navy','pink'];
    private $width = 1200;
    private $height = 600;
    private $title = '';
    private $titleSize = 14;
    private $legends = [];
    private $labels = [];
    private $xLabels = [];
    private $xTitle = '';
    private $yTitle = '';

    private function getColorNum(): int
    {
        return count($this->color) - 1;
    }

    function setWidth(int $width): NewGraphService
    {
        $this->width = $width;
        return $this;
    }

    function setHeight(int $height): NewGraphService
    {
        $this->height = $height;
        return $this;
    }

    function setTitle(string $title): NewGraphService
    {
        $this->title = $title;
        return $this;
    }

    function setTitleSize(int $size): NewGraphService
    {
        $this->titleSize = $size;
        return $this;
    }

    function setLegends(array $legends = []): NewGraphService
    {
        $this->legends = $legends;
        return $this;
    }

    function setXLabels(array $xLabels = []): NewGraphService
    {
        $this->xLabels = $xLabels;
        return $this;
    }

    function setXTitle(string $xTitle): NewGraphService
    {
        $this->xTitle = $xTitle;
        return $this;
    }

    function setYTitle(string $YTitle): NewGraphService
    {
        $this->yTitle = $YTitle;
        return $this;
    }

    function setLabels(array $labels): NewGraphService
    {
        $this->labels = $labels;
        return $this;
    }


    //生成一个柱状图的地址
    function bar($data = []): string
    {
        $graph = new Graph($this->width, $this->height);
        $graph->SetScale('textlin');

        $graph->legend->Pos(0.02, 0.15);
        $graph->legend->SetShadow('darkgray@0.5');
        $graph->legend->SetFillColor('lightblue@0.3');

        $graph->img->SetAutoMargin();

        //设置标题
        empty($this->title) ?: $graph->title->Set($this->title);
        //设置横坐标标题
        empty($this->xTitle) ?: $graph->xaxis->title->Set($this->xTitle);
        //设置纵坐标标题
        empty($this->yTitle) ?: $graph->xaxis->title->Set($this->yTitle);

        //横坐标显示
        empty($this->xLabels) ?: $graph->xaxis->SetTickLabels($this->xLabels);

        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, $this->titleSize);
        $graph->xaxis->title->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetColor('black');
        $graph->ygrid->SetColor('black@0.5');
        $graph->legend->SetFont(FF_USERFONT1, FS_NORMAL);

        $BarPlotObjArr = [];

        foreach ($data as $key => $oneDataArray) {
            $bar = new BarPlot($oneDataArray);
            //显示柱状图上的数
            $bar->value->Show();
            $bar->SetFillColor($this->color[$key%$this->getColorNum()] . '@0.4');
            empty($this->legends) ?: $bar->SetLegend($this->legends[$key]);
            $BarPlotObjArr[] = $bar;
        }

        $gbarplot = new GroupBarPlot($BarPlotObjArr);
        $gbarplot->SetWidth(0.6);
        $graph->Add($gbarplot);
        $fileName = control::getUuid(12) . '.jpg';
        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return REPORT_IMAGE_TEMP_PATH . $fileName;
    }

    //生成一个饼图的地址
    function pie($data = []): string
    {
        $graph = new PieGraph($this->width, $this->height);
        $graph->SetShadow();
        $graph->SetUserFont1(SIMSUN_TTC);

        empty($this->title) ?: $graph->title->Set($this->title);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, $this->titleSize);
        $graph->title->SetColor('black');

        $p1 = new PiePlot($data);
        $p1->SetCenter(0.5, 0.5);
        $p1->SetSize(0.3);

        empty($this->labels) ?: $p1->SetLabels($this->labels);

        $p1->SetLabelPos(1);

        $p1->SetLabelType(PIE_VALUE_PER);
        $p1->value->Show();
        $p1->value->SetFont(FF_USERFONT1, FS_NORMAL, 15);
        $p1->value->SetColor('darkgray');

        $graph->Add($p1);

        $fileName = control::getUuid(12) . '.jpg';
        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return REPORT_IMAGE_TEMP_PATH . $fileName;
    }

    //生成一个折线图的地址
    function line($data = []): string
    {
        $graph = new Graph($this->width, $this->height);
        $graph->SetMarginColor('white');
        $graph->SetScale('textlin');
        $graph->SetFrame(false);
        $graph->SetMargin(60, 50, 0, 0);
        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->legend->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, $this->titleSize);
        $graph->xaxis->SetFont(FF_USERFONT1, FS_NORMAL);

        empty($this->title) ?: $graph->title->Set($this->title);

        $graph->yaxis->HideZeroLabel();
        $graph->ygrid->SetFill(true, '#EFEFEF@0.5', '#BBCCFF@0.5');
        $graph->xgrid->Show();

        empty($this->xLabels) ?: $graph->xaxis->SetTickLabels($this->xLabels);

        // Create line
        foreach ($data as $key => $one)
        {
            $p = new LinePlot($one);
            $p->SetColor($this->color[$key%$this->getColorNum()]);
            empty($this->legends) ?: $p->SetLegend($this->legends[$key]);
            $graph->Add($p);
        }

        $graph->legend->SetShadow('gray@0.4', 5);
        $graph->legend->SetPos(0.1, 0.1, 'right', 'top');

        $fileName = control::getUuid(12) . '.jpg';
        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return REPORT_IMAGE_TEMP_PATH . $fileName;
    }
}
