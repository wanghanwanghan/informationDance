<?php

namespace App\HttpController\Service\NewGraph;

use Amenadiel\JpGraph\Graph\PieGraph;
use Amenadiel\JpGraph\Plot\PiePlot;
use App\HttpController\Service\ServiceBase;
use Amenadiel\JpGraph\Graph\Graph;
use Amenadiel\JpGraph\Plot\BarPlot;
use Amenadiel\JpGraph\Plot\GroupBarPlot;
use wanghanwanghan\someUtils\control;

class NewGraphService extends ServiceBase
{
    private $width = 1200;
    private $height = 600;
    private $titleSize = 14;

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

    function setTitleSize(int $size): NewGraphService
    {
        $this->titleSize = $size;
        return $this;
    }

    //生成一个柱状图的地址
    function bar($data = [], $labels = [], $extension = []): string
    {
        $graph = new Graph($this->width, $this->height);
        $graph->SetScale('textlin');

        $graph->legend->Pos(0.02, 0.15);
        $graph->legend->SetShadow('darkgray@0.5');
        $graph->legend->SetFillColor('lightblue@0.3');

        $graph->img->SetAutoMargin();

        //设置标题
        !isset($extension['title']) ?: $graph->title->Set($extension['title']);
        //设置横坐标标题
        !isset($extension['xTitle']) ?: $graph->xaxis->title->Set($extension['xTitle']);
        //设置纵坐标标题
        !isset($extension['yTitle']) ?: $graph->xaxis->title->Set($extension['yTitle']);

        //横坐标显示
        $graph->xaxis->SetTickLabels($labels);

        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, $this->titleSize);
        $graph->xaxis->title->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetColor('black');
        $graph->ygrid->SetColor('black@0.5');
        $graph->legend->SetFont(FF_USERFONT1, FS_NORMAL);

        $BarPlotObjArr = [];

        $color = ['red', 'orange', 'yellow', 'green', 'blue'];

        foreach ($data as $key => $oneDataArray) {
            $bar = new BarPlot($oneDataArray);
            //显示柱状图上的数
            $bar->value->Show();
            $bar->SetFillColor($color[$key] . '@0.4');
            $bar->SetLegend($extension['legend'][$key]);
            $BarPlotObjArr[] = $bar;
        }

        $gbarplot = new GroupBarPlot($BarPlotObjArr);
        $gbarplot->SetWidth(0.6);
        $graph->Add($gbarplot);
        $fileName = control::getUuid(12) . '.jpg';
        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return $fileName;
    }

    //生成一个饼图的地址
    function pie($data = []): string
    {
        $data = [10,20,30,40,50];

        // Create the Pie Graph.
        $graph = new PieGraph($this->width, $this->height);

        // Set A title for the plot
        $graph->title->Set('Label guide lines');
        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, 12);
        $graph->title->SetColor('darkblue');
        $graph->legend->Pos(0.1, 0.2);
        $graph->legend->SetFont(FF_USERFONT1, FS_NORMAL);

        // Create pie plot
        $p1 = new PiePlot($data);
        $p1->SetCenter(0.5, 0.55);
        $p1->SetSize(0.3);

        $p1->SetLegends(['胖飞','飞飞','坑飞','坑坑飞','胡坑飞','胡胖飞']);

        // Enable and set policy for guide-lines. Make labels line up vertically
        // and force guide lines to always beeing used
        $p1->SetGuideLines(true, false, true);
        $p1->SetGuideLinesAdjust(1.5);

        // Setup the labels
        $p1->SetLabelType(PIE_VALUE_PER);
        $p1->value->Show();
        $p1->value->SetFont(FF_ARIAL, FS_NORMAL, 9);
        $p1->value->SetFormat('%2.1f%%');

        // Add and stroke
        $graph->Add($p1);
        $fileName = control::getUuid(12) . '.jpg';
        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return $fileName;
    }

}
