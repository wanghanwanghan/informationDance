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
    private $margin = [];
    private $xLabelAngle = '';

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

    function setMargin(array $margin = []): NewGraphService
    {
        $this->margin = $margin;
        return $this;
    }

    function setXLabelAngle(int $angle): NewGraphService
    {
        $this->xLabelAngle = $angle;
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

        empty($this->margin) ? $graph->img->SetAutoMargin() : $graph->SetMargin(...$this->margin);

        //设置标题
        empty($this->title) ?: $graph->title->Set($this->title);
        //设置横坐标标题
        empty($this->xTitle) ?: $graph->xaxis->title->Set($this->xTitle);
        //设置纵坐标标题
        empty($this->yTitle) ?: $graph->yaxis->title->Set($this->yTitle);

        //横坐标显示
        empty($this->xLabels) ?: $graph->xaxis->SetTickLabels($this->xLabels);
        empty($this->xLabelAngle) ?: $graph->xaxis->SetLabelAngle($this->xLabelAngle);

        $graph->SetUserFont1(SIMSUN_TTC);
        $graph->title->SetFont(FF_USERFONT1, FS_NORMAL, $this->titleSize);
        $graph->xaxis->title->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->xaxis->SetColor('black');
        $graph->yaxis->title->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->yaxis->SetFont(FF_USERFONT1, FS_NORMAL);
        $graph->yaxis->SetColor('black');
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

        $p1->SetGuideLines(true, false, true);
        $p1->SetGuideLinesAdjust(1.5);

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

    //test
    function testtest()
    {
        $month = [
            'Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec', ];

// Create some datapoints
        $steps = 100;
        for ($i = 0; $i < $steps; ++$i) {
            $databarx[] = sprintf('198%d %s', floor($i / 12), $month[$i % 12]);
            $datay[$i]  = log(pow($i, $i / 10) + 1) * sin($i / 15) + 35;
            if ($i % 6 == 0 && $i < $steps - 6) {
                $databary[] = abs(25 * sin($i) + 5);
            } else {
                $databary[] = 0;
            }
        }

// new Graph\Graph with a background image and drop shadow
        $__width  = 450;
        $__height = 300;
        $graph    = new Graph($__width, $__height);
        $graph->SetShadow();

// Use text X-scale so we can text labels on the X-axis
        $graph->SetScale('textlin');

// Y2-axis is linear
        $graph->SetY2Scale('lin');

// Color the two Y-axis to make them easier to associate
// to the corresponding plot (we keep the axis black though)
        $graph->yaxis->SetColor('black', 'red');
        $graph->y2axis->SetColor('black', 'orange');

// Set title and subtitle
        $graph->title->Set('Combined bar and line plot');
        $graph->subtitle->Set("100 data points, X-Scale: 'text'");

// Use built in font (don't need TTF support)
        $graph->title->SetFont(FF_FONT1, FS_BOLD);

// Make the margin around the plot a little bit bigger then default
        $graph->img->SetMargin(40, 140, 40, 80);

// Slightly adjust the legend from it's default position in the
// top right corner to middle right side
        $graph->legend->Pos(0.03, 0.5, 'right', 'center');

// Display every 6:th tickmark
        $graph->xaxis->SetTextTickInterval(6);

// Label every 2:nd tick mark
        $graph->xaxis->SetTextLabelInterval(2);

// Setup the labels
        $graph->xaxis->SetTickLabels($databarx);
        $graph->xaxis->SetLabelAngle(90);

// Create a red line plot
        $p1 = new LinePlot($datay);
        $p1->SetColor('red');
        $p1->SetLegend('Pressure');

// Create the bar plot
        $b1 = new BarPlot($databary);
        $b1->SetLegend('Temperature');
        $b1->SetFillColor('orange');
        $b1->SetAbsWidth(8);

// Drop shadow on bars adjust the default values a little bit
        $b1->SetShadow('steelblue', 2, 2);

// The order the plots are added determines who's ontop
        $graph->Add($p1);
        $graph->AddY2($b1);

// Finally output the  image
        $fileName = 'wanghan.jpg';
        $graph->Stroke(REPORT_IMAGE_TEMP_PATH . $fileName);

        return REPORT_IMAGE_TEMP_PATH . $fileName;
    }
}
