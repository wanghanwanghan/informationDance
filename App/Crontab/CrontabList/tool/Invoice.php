<?php

namespace App\Crontab\CrontabList\tool;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\TaoShu\TaoShuService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class Invoice
{
    public $in = [];
    public $out = [];

    function __construct($in,$out)
    {
        $this->in = $in;
        $this->out = $out;
    }

    private function is_wuye($name,$money)
    {
        if (control::hasString($name,'物业')) return $money;

        return 0;
    }
    private function is_yunshu($name,$money)
    {
        if (control::hasString($name,'物流')) return $money;
        if (control::hasString($name,'快递')) return $money;

        return 0;
    }
    private function is_reli($name,$money)
    {
        if (control::hasString($name,'供热')) return $money;

        return 0;
    }
    private function is_ranqifei($name,$money)
    {
        if (control::hasString($name,'天然气')) return $money;
        if (control::hasString($name,'燃气')) return $money;
        if (control::hasString($name,'液化气')) return $money;

        return 0;
    }
    private function is_dianfei($name,$money)
    {
        if (control::hasString($name,'电网')) return $money;
        if (control::hasString($name,'电力')) return $money;
        if (control::hasString($name,'供电')) return $money;

        return 0;
    }
    private function is_shuifei($name,$money)
    {
        if (control::hasString($name,'自来水')) return $money;
        if (control::hasString($name,'水务')) return $money;

        return 0;
    }
    private function is_fangchan($name,$money)
    {
        if (control::hasString($name,'地产')) return $money;
        if (control::hasString($name,'开发商')) return $money;

        return 0;
    }
    private function is_shebeilingjianjizu($name,$money)
    {
        if (control::hasString($name,'设备')) return $money;
        if (control::hasString($name,'机械')) return $money;
        if (control::hasString($name,'电子')) return $money;
        if (control::hasString($name,'机床')) return $money;
        if (control::hasString($name,'电气')) return $money;

        return 0;
    }
    private function is_jidongchefapiao($name,$money)
    {
        if (control::hasString($name,'汽车')) return $money;
        if (control::hasString($name,'车辆')) return $money;

        return 0;
    }
    private function is_haocai($name,$money)
    {
        if (control::hasString($name,'商城') && $money < 1000) return $money;
        if (control::hasString($name,'商场') && $money < 1000) return $money;
        if (control::hasString($name,'超市') && $money < 500) return $money;
        if (control::hasString($name,'办公用品')) return $money;
        if (control::hasString($name,'耗材')) return $money;
        if (control::hasString($name,'商贸')) return $money;

        return 0;
    }
    private function is_jiaju($name,$money)
    {
        if (control::hasString($name,'家居')) return $money;
        if (control::hasString($name,'家具')) return $money;

        return 0;
    }
    private function is_zhuangxiuzhuangshi($name,$money)
    {
        if (control::hasString($name,'装饰')) return $money;
        if (control::hasString($name,'装修')) return $money;
        if (control::hasStringFront($name,'家居','工程')) return $money;
        if (control::hasStringFront($name,'建筑','工程')) return $money;

        return 0;
    }
    private function is_chashui($name,$money)
    {
        if (control::hasString($name,'茶楼')) return $money;
        if (control::hasString($name,'咖啡')) return $money;

        return 0;
    }
    private function is_chuxing($name,$money)
    {
        if (control::hasString($name,'出行')) return $money;
        if (control::hasString($name,'出租车')) return $money;
        if (control::hasString($name,'车务')) return $money;

        return 0;
    }
    private function is_lipin($name,$money)
    {
        if (control::hasString($name,'商场') && $money > 1000) return $money;
        if (control::hasString($name,'广场')) return $money;
        if (control::hasString($name,'购物')) return $money;
        if (control::hasString($name,'礼品')) return $money;
        if (control::hasString($name,'超市') && $money > 500) return $money;
        if (control::hasString($name,'商城') && $money > 1000) return $money;

        return 0;
    }
    private function is_huiyifei($name,$money)
    {
        if (control::hasString($name,'酒店') && $money > 2000) return $money;
        if (control::hasString($name,'度假村') && $money > 2000) return $money;
        if (control::hasString($name,'会务')) return $money;
        if (control::hasString($name,'会议')) return $money;
        if (control::hasString($name,'展览')) return $money;
        if (control::hasString($name,'体育场')) return $money;

        return 0;
    }
    private function is_youfei($name,$money)
    {
        if (control::hasStringFront($name,'石油','销售')) return $money;
        if (control::hasStringFront($name,'成品油','销售')) return $money;
        if (control::hasStringFront($name,'油品','销售')) return $money;
        if (control::hasString($name,'加油站')) return $money;

        return 0;
    }
    private function is_keyun($name,$money)
    {
        if (control::hasString($name,'客运')) return $money;
        if (control::hasString($name,'去哪') && $money > 1000 && $money < 2000) return $money;
        if (control::hasString($name,'携程') && $money > 1000 && $money < 2000) return $money;
        if (control::hasString($name,'途牛') && $money > 1000 && $money < 2000) return $money;
        if (control::hasString($name,'飞猪') && $money > 1000 && $money < 2000) return $money;

        return 0;
    }
    private function is_huochepiao($name,$money)
    {
        if (control::hasStringFront($name,'铁路','总')) return $money;
        if (control::hasStringFront($name,'铁道','清算')) return $money;
        if (control::hasString($name,'去哪') && $money < 1000) return $money;
        if (control::hasString($name,'携程') && $money < 1000) return $money;
        if (control::hasString($name,'途牛') && $money < 1000) return $money;
        if (control::hasString($name,'飞猪') && $money < 1000) return $money;

        return 0;
    }
    private function is_jipiao($name,$money)
    {
        if (control::hasString($name,'旅行')) return $money;
        if (control::hasString($name,'航空')) return $money;
        if (control::hasString($name,'机场')) return $money;
        if (control::hasString($name,'去哪') && $money > 2000) return $money;
        if (control::hasString($name,'携程') && $money > 2000) return $money;
        if (control::hasString($name,'途牛') && $money > 2000) return $money;
        if (control::hasString($name,'飞猪') && $money > 2000) return $money;

        return 0;
    }
    private function is_canyin($name,$money)
    {
        if (control::hasString($name,'餐饮')) return $money;
        if (control::hasString($name,'饮食')) return $money;
        if (control::hasString($name,'美食')) return $money;
        if (control::hasString($name,'饭店') && $money < 1000) return $money;

        return 0;
    }
    private function is_zhusu($name,$money)
    {
        if (control::hasString($name,'酒店') && $money < 2000) return $money;
        if (control::hasString($name,'客栈')) return $money;
        if (control::hasString($name,'民宿')) return $money;
        if (control::hasString($name,'饭店') && $money < 1000) return $money;
        if (control::hasString($name,'去哪') && $money < 1000) return $money;
        if (control::hasString($name,'携程') && $money < 1000) return $money;
        if (control::hasString($name,'途牛') && $money < 1000) return $money;
        if (control::hasString($name,'飞猪') && $money < 1000) return $money;
        if (control::hasString($name,'度假村') && $money < 2000) return $money;

        return 0;
    }

    //5.2主营商品分析
    public function zyspfx()
    {
        //返回所有销项中，按占比排序，返回最高的前十
        //商品名称，销售金额，占比

        $return=[];

        if (empty($this->out)) return $return;

        foreach ($this->out as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            //看看这个发票是哪年的
            $timestamp=strtotime(trim($one['billingDate']));

            if (!$timestamp) continue;

            $year=date('Y',$timestamp);

            isset($return[$year]) ?
                $return[$year]['zongjine']+=abs($one['totalAmount'])+abs($one['totalTax']) :
                $return[$year]['zongjine']=abs($one['totalAmount'])+abs($one['totalTax']);

            //是否有商品名称
            if (empty($one['goodsName'])) continue;

            $name=$one['goodsName'];

            isset($return[$year][$name]) ?
                $return[$year][$name]+=abs($one['totalAmount'])+abs($one['totalTax']) :
                $return[$year][$name]=abs($one['totalAmount'])+abs($one['totalTax']);
        }

        //array:3 [
        //  2020 => array:2 [
        //    "zongjine" => 2998129.22
        //    "信息技术服务" => 2998129.22
        //  ]
        //  2019 => array:2 [
        //    "zongjine" => 332901.62
        //    "信息技术服务" => 332901.62
        //  ]
        //  2018 => array:2 [
        //    "zongjine" => 332901.62
        //    "信息技术服务" => 332901.62
        //  ]
        //]

        if (empty($return)) return $return;

        //降序
        if (!empty($return)) krsort($return);

        if (count($return) >= 2)
        {
            $arr1=array_shift($return);
            $arr2=array_shift($return);

            $tmp=[];

            foreach($arr1 as $k => $v)
            {
                if(isset($arr2[$k]))
                {
                    $tmp[$k]=$arr1[$k] + $arr2[$k];

                    unset($arr1[$k],$arr2[$k]);
                }
            }

            $return=array_merge($tmp,$arr1,$arr2);

        }else
        {
            $return=array_shift($return);
        }

        //最后形成每条记录
        $tmp=[];

        foreach ($return as $key => $val)
        {
            if ($key=='zongjine') continue;

            $tmp[]=[
                'name'=>$key,
                'jine'=>sprintf('%.1f',$val/10000),
                'zhanbi'=>sprintf('%.1f',$val/$return['zongjine']*100)
            ];
        }

        if (!empty($tmp)) $tmp=control::sortArrByKey($tmp,'zhanbi');

        return array_slice($tmp,0,10);
    }

    //5.4主要成本分析
    public function zycbfx()
    {
        //返回所有进项中，按占比排序，返回最高的前十
        //商品名称，销售金额，占比

        //总费用
        $tmpTotal=0;
        $return=[];

        if (empty($this->in)) return $return;

        //以下是大批的正则匹配公司名，算费用
        //住宿
        $zhusu['jine']=0;
        $zhusu['name']='住宿';
        //餐饮
        $canyin['jine']=0;
        $canyin['name']='餐饮';
        //机票
        $jipiao['jine']=0;
        $jipiao['name']='机票';
        //火车票
        $huochepiao['jine']=0;
        $huochepiao['name']='火车票';
        //客运
        $keyun['jine']=0;
        $keyun['name']='客运';
        //油费
        $youfei['jine']=0;
        $youfei['name']='油费';
        //会议费
        $huiyifei['jine']=0;
        $huiyifei['name']='会议费';
        //礼品
        $lipin['jine']=0;
        $lipin['name']='礼品';
        //出行
        $chuxing['jine']=0;
        $chuxing['name']='出行';
        //茶水
        $chashui['jine']=0;
        $chashui['name']='茶水';
        //装修装饰
        $zhuangxiuzhuangshi['jine']=0;
        $zhuangxiuzhuangshi['name']='装修装饰';
        //家具
        $jiaju['jine']=0;
        $jiaju['name']='家具';
        //耗材
        $haocai['jine']=0;
        $haocai['name']='耗材';
        //机动车发票
        $jidongchefapiao['jine']=0;
        $jidongchefapiao['name']='机动车(含工程特种车辆)';
        //设备零件机组
        $shebeilingjianjizu['jine']=0;
        $shebeilingjianjizu['name']='设备零件机组';
        //房产
        $fangchan['jine']=0;
        $fangchan['name']='房产';
        //水费
        $shuifei['jine']=0;
        $shuifei['name']='水费';
        $shuifeiArr=[];
        //电费
        $dianfei['jine']=0;
        $dianfei['name']='电费';
        $dianfeiArr=[];
        //燃气费
        $ranqifei['jine']=0;
        $ranqifei['name']='燃气费';
        $ranqifeiArr=[];
        //热力
        $reli['jine']=0;
        $reli['name']='热力';
        $reliArr=[];
        //运输
        $yunshu['jine']=0;
        $yunshu['name']='运输';
        $yunshuArr=[];
        //物业
        $wuye['jine']=0;
        $wuye['name']='物业';
        $wuyeArr=[];
        //原材料（总费用 减 其他别的）
        $yuancailiao['name']='原材料/服务(不含人员工资)';
        $yuancailiao['jine']=0;

        foreach ($this->in as $one)
        {
            //判断一下发票状态
            if ($one['state']!=0) continue;

            $tmpTotal+=abs($one['totalAmount'])+abs($one['totalTax']);

            $zhusu['jine']+=$this->is_zhusu(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $canyin['jine']+=$this->is_canyin(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $jipiao['jine']+=$this->is_jipiao(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $huochepiao['jine']+=$this->is_huochepiao(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $keyun['jine']+=$this->is_keyun(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $youfei['jine']+=$this->is_youfei(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $huiyifei['jine']+=$this->is_huiyifei(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $lipin['jine']+=$this->is_lipin(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $chuxing['jine']+=$this->is_chuxing(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $chashui['jine']+=$this->is_chashui(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $zhuangxiuzhuangshi['jine']+=$this->is_zhuangxiuzhuangshi(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $jiaju['jine']+=$this->is_jiaju(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $haocai['jine']+=$this->is_haocai(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $jidongchefapiao['jine']+=$this->is_jidongchefapiao(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $shebeilingjianjizu['jine']+=$this->is_shebeilingjianjizu(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $fangchan['jine']+=$this->is_fangchan(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));

            $tmp=$this->is_shuifei(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $shuifei['jine']+=$tmp;
            if ($tmp!=0) $shuifeiArr[]=['riqi'=>trim($one['billingDate']),'jine'=>sprintf('%.1f',$tmp/10000),'gs'=>trim($one['salesTaxName'])];

            $tmp=$this->is_dianfei(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $dianfei['jine']+=$tmp;
            if ($tmp!=0) $dianfeiArr[]=['riqi'=>trim($one['billingDate']),'jine'=>sprintf('%.1f',$tmp/10000),'gs'=>trim($one['salesTaxName'])];

            $tmp=$this->is_ranqifei(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $ranqifei['jine']+=$tmp;
            if ($tmp!=0) $ranqifeiArr[]=['riqi'=>trim($one['billingDate']),'jine'=>sprintf('%.1f',$tmp/10000),'gs'=>trim($one['salesTaxName'])];

            $tmp=$this->is_reli(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $reli['jine']+=$tmp;
            if ($tmp!=0) $reliArr[]=['riqi'=>trim($one['billingDate']),'jine'=>sprintf('%.1f',$tmp/10000),'gs'=>trim($one['salesTaxName'])];

            $tmp=$this->is_yunshu(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $yunshu['jine']+=$tmp;
            if ($tmp!=0) $yunshuArr[]=['riqi'=>trim($one['billingDate']),'jine'=>sprintf('%.1f',$tmp/10000),'gs'=>trim($one['salesTaxName'])];

            $tmp=$this->is_wuye(trim($one['salesTaxName']),abs($one['totalAmount'])+abs($one['totalTax']));
            $wuye['jine']+=$tmp;
            if ($tmp!=0) $wuyeArr[]=['riqi'=>trim($one['billingDate']),'jine'=>sprintf('%.1f',$tmp/10000),'gs'=>trim($one['salesTaxName'])];
        }

        $yuancailiao['jine']=$tmpTotal - $zhusu['jine']-$canyin['jine']-$jipiao['jine']-$huochepiao['jine']
            -$keyun['jine']-$youfei['jine']-$huiyifei['jine']-$lipin['jine']-$chuxing['jine']
            -$chashui['jine']-$zhuangxiuzhuangshi['jine']-$jiaju['jine']-$haocai['jine']-$jidongchefapiao['jine']
            -$shebeilingjianjizu['jine']-$fangchan['jine']-$shuifei['jine']-$dianfei['jine']-$ranqifei['jine']
            -$reli['jine']-$yunshu['jine']-$wuye['jine'];

        //算算占比
        $zhusu['zhanbi']=sprintf('%.1f',$zhusu['jine']/$tmpTotal*100);
        $canyin['zhanbi']=sprintf('%.1f',$canyin['jine']/$tmpTotal*100);
        $jipiao['zhanbi']=sprintf('%.1f',$jipiao['jine']/$tmpTotal*100);
        $huochepiao['zhanbi']=sprintf('%.1f',$huochepiao['jine']/$tmpTotal*100);
        $keyun['zhanbi']=sprintf('%.1f',$keyun['jine']/$tmpTotal*100);
        $youfei['zhanbi']=sprintf('%.1f',$youfei['jine']/$tmpTotal*100);
        $huiyifei['zhanbi']=sprintf('%.1f',$huiyifei['jine']/$tmpTotal*100);
        $lipin['zhanbi']=sprintf('%.1f',$lipin['jine']/$tmpTotal*100);
        $chuxing['zhanbi']=sprintf('%.1f',$chuxing['jine']/$tmpTotal*100);
        $chashui['zhanbi']=sprintf('%.1f',$chashui['jine']/$tmpTotal*100);
        $zhuangxiuzhuangshi['zhanbi']=sprintf('%.1f',$zhuangxiuzhuangshi['jine']/$tmpTotal*100);
        $jiaju['zhanbi']=sprintf('%.1f',$jiaju['jine']/$tmpTotal*100);
        $haocai['zhanbi']=sprintf('%.1f',$haocai['jine']/$tmpTotal*100);
        $jidongchefapiao['zhanbi']=sprintf('%.1f',$jidongchefapiao['jine']/$tmpTotal*100);//
        $shebeilingjianjizu['zhanbi']=sprintf('%.1f',$shebeilingjianjizu['jine']/$tmpTotal*100);//
        $fangchan['zhanbi']=sprintf('%.1f',$fangchan['jine']/$tmpTotal*100);//
        $yuancailiao['zhanbi']=sprintf('%.1f',$yuancailiao['jine']/$tmpTotal*100);//

        //除了原材料，房产，机动车，零件机组，其他的都算管理费用
        $guanlifeiyong=['jine'=>0,'name'=>'管理费用','zhanbi'=>0];

        $guanlifeiyong['jine']+=$zhusu['jine'];
        $guanlifeiyong['zhanbi']+=$zhusu['zhanbi'];

        $guanlifeiyong['jine']+=$canyin['jine'];
        $guanlifeiyong['zhanbi']+=$canyin['zhanbi'];

        $guanlifeiyong['jine']+=$jipiao['jine'];
        $guanlifeiyong['zhanbi']+=$jipiao['zhanbi'];

        $guanlifeiyong['jine']+=$huochepiao['jine'];
        $guanlifeiyong['zhanbi']+=$huochepiao['zhanbi'];

        $guanlifeiyong['jine']+=$keyun['jine'];
        $guanlifeiyong['zhanbi']+=$keyun['zhanbi'];

        $guanlifeiyong['jine']+=$youfei['jine'];
        $guanlifeiyong['zhanbi']+=$youfei['zhanbi'];

        $guanlifeiyong['jine']+=$huiyifei['jine'];
        $guanlifeiyong['zhanbi']+=$huiyifei['zhanbi'];

        $guanlifeiyong['jine']+=$lipin['jine'];
        $guanlifeiyong['zhanbi']+=$lipin['zhanbi'];

        $guanlifeiyong['jine']+=$chuxing['jine'];
        $guanlifeiyong['zhanbi']+=$chuxing['zhanbi'];

        $guanlifeiyong['jine']+=$chashui['jine'];
        $guanlifeiyong['zhanbi']+=$chashui['zhanbi'];

        $guanlifeiyong['jine']+=$zhuangxiuzhuangshi['jine'];
        $guanlifeiyong['zhanbi']+=$zhuangxiuzhuangshi['zhanbi'];

        $guanlifeiyong['jine']+=$jiaju['jine'];
        $guanlifeiyong['zhanbi']+=$jiaju['zhanbi'];

        $guanlifeiyong['jine']+=$haocai['jine'];
        $guanlifeiyong['zhanbi']+=$haocai['zhanbi'];

        //总占比
        $guanlifeiyong['zhanbi']=sprintf('%.1f',$guanlifeiyong['zhanbi']);

        $return=[
            [
                $yuancailiao,$guanlifeiyong,$fangchan,$jidongchefapiao,$shebeilingjianjizu,
            ],
            [
                'shuifei'=>$shuifeiArr,
                'dianfei'=>$dianfeiArr,
                'ranqifei'=>$ranqifeiArr,
                'reli'=>$reliArr,
                'yunshu'=>$yunshuArr,
                'wuye'=>$wuyeArr,
            ]
        ];

        $arr=control::sortArrByKey($return[0],'zhanbi');

        $return[0]=$arr;

        //金额变成万元
        foreach ($return[0] as &$one)
        {
            $one['jine']=sprintf('%.1f',$one['jine']/10000);
        }
        unset($one);

        return $return;
    }

    //6.1企业开票情况汇总
    public function qykpqkhz()
    {
        //进项销项如果有重合的，在一行显示
        //其他的单独显示
        $return=[
            'zhouqi'=>[],//周期
            'qita'=>[],//其他
        ];

        //首先整理进项数据
        foreach ($this->in as $one)
        {
            isset($return['zhouqi']['min']) ?: $return['zhouqi']['min']=Carbon::now()->format('Ym');
            isset($return['zhouqi']['max']) ?: $return['zhouqi']['max']=197001;
            isset($return['zhouqi']['xxNum']) ?: $return['zhouqi']['xxNum']=0;
            isset($return['zhouqi']['xxJine']) ?: $return['zhouqi']['xxJine']=0;
            isset($return['zhouqi']['jxNum']) ?: $return['zhouqi']['jxNum']=0;
            isset($return['zhouqi']['jxJine']) ?: $return['zhouqi']['jxJine']=0;

            if ($one['state']!=0) continue;

            if (date('Ym',strtotime($one['billingDate'])) < $return['zhouqi']['min'])
            {
                //设置最小年月
                $return['zhouqi']['min']=date('Ym',strtotime($one['billingDate']));
            }

            if (date('Ym',strtotime($one['billingDate'])) > $return['zhouqi']['max'])
            {
                //设置最大年月
                $return['zhouqi']['max']=date('Ym',strtotime($one['billingDate']));
            }

            //进项数加1
            $return['zhouqi']['jxNum']++;

            //进项加金额
            $return['zhouqi']['jxJine']+=abs($one['totalAmount']);
            $return['zhouqi']['jxJine']+=abs($one['totalTax']);
        }

        //其次处理销项数据
        foreach ($this->out as $one)
        {
            isset($return['zhouqi']['jxNum']) ?: $return['zhouqi']['jxNum']=0;
            isset($return['zhouqi']['jxJine']) ?: $return['zhouqi']['jxJine']=0;
            isset($return['zhouqi']['xxNum']) ?: $return['zhouqi']['xxNum']=0;
            isset($return['zhouqi']['xxJine']) ?: $return['zhouqi']['xxJine']=0;

            if ($one['state']!=0) continue;

            $date=date('Ym',strtotime($one['billingDate']));

            if ($date >= $return['zhouqi']['min'] && $date <= $return['zhouqi']['max'])
            {
                //在周期内的
                //销项数加1
                $return['zhouqi']['xxNum']++;

                //销项加金额
                $return['zhouqi']['xxJine']+=abs($one['totalAmount']);
                $return['zhouqi']['xxJine']+=abs($one['totalTax']);
            }

            $year=substr($date,0,4);

            isset($return['qita'][$year]) ?: $return['qita'][$year]=['xxJine'=>0,'xxNum'=>0];

            //销项数加1
            $return['qita'][$year]['xxNum']++;

            //销项加金额
            $return['qita'][$year]['xxJine']+=abs($one['totalAmount']);
            $return['qita'][$year]['xxJine']+=abs($one['totalTax']);
        }

        //金额变万元
        foreach ($return as $key => &$one)
        {
            if ($key=='qita')
            {
                foreach ($one as &$two)
                {
                    if (isset($two['xxJine'])) $two['xxJine']=sprintf('%.1f',$two['xxJine']/10000);
                }
                unset($two);
            }else
            {
                if (isset($one['xxJine'])) $one['xxJine']=sprintf('%.1f',$one['xxJine']/10000);
                if (isset($one['jxJine'])) $one['jxJine']=sprintf('%.1f',$one['jxJine']/10000);
            }
        }
        unset($one);

        return $return;
    }

    //6.2.1年度销项发票情况汇总
    public function ndxxfpqkhz()
    {
        $return=[];

        if (empty($this->out)) return $return;

        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            if (!isset($return[$year]))
            {
                $return[$year]=[
                    'normal'=>[
                        'normalNum'=>0,
                        'normalAmount'=>0,
                        'normalTax'=>0,
                        'numZhanbi'=>0,
                        'AmountZhanbi'=>0,
                    ],
                    'cancel'=>[
                        'cancelNum'=>0,
                        'cancelAmount'=>0,
                        'cancelTax'=>0,
                        'numZhanbi'=>0,
                        'AmountZhanbi'=>0,
                    ],
                    'red'=>[
                        'redNum'=>0,
                        'redAmount'=>0,
                        'redTax'=>0,
                        'numZhanbi'=>0,
                        'AmountZhanbi'=>0,
                    ],
                ];
            }

            if ($one['state']==0)
            {
                //正常发票 normal
                $return[$year]['normal']['normalNum']++;
                $return[$year]['normal']['normalAmount']+=abs($one['totalAmount']);
                $return[$year]['normal']['normalTax']+=abs($one['totalTax']);

            }elseif ($one['state']==1)
            {
                //作废发票 cancel
                $return[$year]['cancel']['cancelNum']++;
                $return[$year]['cancel']['cancelAmount']+=abs($one['totalAmount']);
                $return[$year]['cancel']['cancelTax']+=abs($one['totalTax']);

            }elseif ($one['state']==2)
            {
                //红冲 red
                $return[$year]['red']['redNum']++;
                $return[$year]['red']['redAmount']+=abs($one['totalAmount']);
                $return[$year]['red']['redTax']+=abs($one['totalTax']);

            }else
            {
                continue;
            }
        }

        krsort($return);

        //算占比
        foreach ($return as &$one)
        {
            //总数量
            $num=$one['normal']['normalNum']+$one['cancel']['cancelNum']+$one['red']['redNum'];
            //总金额
            $amount=$one['normal']['normalAmount']+$one['cancel']['cancelAmount']+$one['red']['redAmount'];

            try
            {
                $one['normal']['numZhanbi']=sprintf('%.1f',$one['normal']['normalNum']/$num*100);
            }catch (\Throwable $e)
            {
                $one['normal']['numZhanbi']=0;
            }

            try
            {
                $one['normal']['AmountZhanbi']=sprintf('%.1f',$one['normal']['normalAmount']/$amount*100);
            }catch (\Throwable $e)
            {
                $one['normal']['AmountZhanbi']=0;
            }
        }
        unset($one);

        //金额变成万元
        foreach ($return as &$one)
        {
            foreach ($one as $key => &$val)
            {
                foreach ($val as $k => &$v)
                {
                    if (preg_match('/(Amount|Tax)$/',$k))
                    {
                        $v=sprintf('%.1f',$v/10000);
                    }
                }
                unset($v);
            }
            unset($val);
        }
        unset($one);

        return $return;
    }

    //6.2.2月度销项发票分析
    public function ydxxfpfx()
    {
        $return=[];
        $total=0;

        if (empty($this->out)) return $return;

        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $mouth=(int)$year[1];
            $year=$year[0];

            if (!isset($return[$year]))
            {
                $tmp=[
                    '1'=>0,
                    '2'=>0,
                    '3'=>0,
                    '4'=>0,
                    '5'=>0,
                    '6'=>0,
                    '7'=>0,
                    '8'=>0,
                    '9'=>0,
                    '10'=>0,
                    '11'=>0,
                    '12'=>0,
                ];
                $return[$year]=[
                    'normal'=>$tmp,
                    'cancel'=>$tmp,
                    'red'=>$tmp,
                ];
            }

            if ($one['state']==0)
            {
                //正常发票 normal
                $return[$year]['normal'][$mouth]+=abs($one['totalAmount']);

            }elseif ($one['state']==1)
            {
                //作废发票 cancel
                $return[$year]['cancel'][$mouth]+=abs($one['totalAmount']);

            }elseif ($one['state']==2)
            {
                //红冲 red
                $return[$year]['red'][$mouth]+=abs($one['totalAmount']);

            }else
            {
                continue;
            }

            $total+=abs($one['totalAmount']);
        }

        krsort($return);

        //计算占比
        foreach ($return as &$year)
        {
            foreach ($year as $key => &$type)
            {
                if ($key=='normal')
                {
                    foreach ($type as &$one)
                    {
                        $one=sprintf('%.1f',$one/10000);
                    }
                    unset($one);

                }else
                {
                    foreach ($type as &$one)
                    {
                        if ($total <= 0) continue;
                        $one=sprintf('%.2f',$one/$total*100);
                    }
                    unset($one);
                }
            }
            unset($type);
        }
        unset($year);

        return $return;
    }

    //6.2.5单张开票金额TOP10记录
    public function dzkpjeTOP10jl_xx()
    {
        $return=[];
        //总金额
        $total=[];

        if (empty($this->out)) return $return;

        foreach ($this->out as &$one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $date=implode('',$year);
            $year=current($year);

            if (!isset($return[$year]))
            {
                $return[$year]=[];
                $total[$year]=0;
            }

            if ($one['state']==0)
            {
                $total[$year]+=abs($one['totalAmount']);
                //正常发票 normal
                $one['date']=$date;
                $return[$year][]=$one;
            }else
            {
                continue;
            }
        }
        unset($one);

        krsort($return);

        //二维数组排序
        foreach ($return as $key => &$one)
        {
            $one=control::sortArrByKey($one,'totalAmount');
        }
        unset($one);

        //整理数组
        $target=$name=[];
        foreach ($return as $year => $val)
        {
            isset($target[$year]) ?: $target[$year]=[];

            foreach ($val as $k => $v)
            {
                if (in_array($v['salesTaxName'],$name) || count($target[$year]) > 10) continue;
                array_push($target[$year],$v);
                array_push($name,$v['salesTaxName']);
            }
        }

        $return=$target;

        //计算占比
        foreach ($return as &$one)
        {
            foreach ($one as &$two)
            {
                if ($total[substr($two['date'],0,4)] <= 0)
                {
                    $two['zhanbi']=0;
                }else
                {
                    $two['zhanbi']=sprintf('%.1f',$two['totalAmount']/$total[substr($two['date'],0,4)]*100);
                }
            }
            unset($two);
        }
        unset($one);

        //金额变万元
        foreach ($return as &$one)
        {
            foreach ($one as &$two)
            {
                if (isset($two['totalAmount']))
                {
                    $two['totalAmount']=sprintf('%.1f',$two['totalAmount']/10000);
                }

                if (isset($two['totalTax']))
                {
                    $two['totalTax']=sprintf('%.1f',$two['totalTax']/10000);
                }
            }
            unset($two);
        }
        unset($one);

        return current($return);
    }

    //6.2.6累计开票金额TOP10企业汇总
    public function ljkpjeTOP10qyhz_xx()
    {
        $tacoTuesday=$return=$total=[];

        if (empty($this->out)) return $return;

        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            if ($one['state']==0)
            {
                //取得公司名称
                $name=trim($one['salesTaxName']);

                //判断该公司在当年的开票总数和总额
                if (!isset($total[$year][$name]))
                {
                    $total[$year][$name]['total']=0;
                    $total[$year][$name]['num']=0;
                    $total[$year][$name]['name']=$name;
                    $total[$year][$name]['date']=$year;
                    $total[$year][$name]['date']=$year;
                }
                if (!isset($tacoTuesday[$year]))
                {
                    $tacoTuesday[$year]['total']=0;
                    $tacoTuesday[$year]['num']=0;
                }

                //加上金额
                $total[$year][$name]['total']+=abs($one['totalAmount']);
                $tacoTuesday[$year]['total']+=abs($one['totalAmount']);
                //加上总数
                $total[$year][$name]['num']++;
                $tacoTuesday[$year]['num']++;
                //加上税号
                $total[$year][$name]['salesTaxNo']=$one['salesTaxNo'];

            }else
            {
                continue;
            }
        }

        //这次是在total数组里填装的
        krsort($total);

        //计算占比
        foreach ($total as $key => &$val)
        {
            foreach ($val as &$one)
            {
                if ($tacoTuesday[$key]['total'] <= 0)
                {
                    $one['totalZhanbi']=0;
                }else
                {
                    $one['totalZhanbi']=sprintf('%.1f',$one['total']/$tacoTuesday[$key]['total']*100);
                }

                if ($tacoTuesday[$key]['num'] <= 0)
                {
                    $one['numZhanbi']=0;
                }else
                {
                    $one['numZhanbi']=sprintf('%.1f',$one['num']/$tacoTuesday[$key]['num']*100);
                }
            }
            unset($one);
        }
        unset($val);

        //排序
        foreach ($total as $key => $one)
        {
            $tmp=control::sortArrByKey($one,'total');

            $total[$key]=array_slice($tmp,0,10);
        }
        unset($one);

        //金额变万元
        foreach ($total as &$one)
        {
            foreach ($one as &$two)
            {
                if (isset($two['total']))
                {
                    $two['total']=sprintf('%.1f',$two['total']/10000);
                }
            }
            unset($two);
        }
        unset($one);

        return current($total);
    }

    //6.3.1下游客户稳定性分析
    //1，下游企业司龄分布
    public function xyqyslfb()
    {
        $return=[];

        if (empty($this->out)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year][$name]))
            {
                //调用企业详情接口，查看司龄
                $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $name], 'getRegisterInfo');
                ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

                //错误就不统计了
                if (empty($res)) continue;

                $data=$res;

                if (isset($data['ESDATE']) && !empty($data['ESDATE']) && strlen($data['ESDATE']>=4))
                {
                    //取得成立年
                    $esYear=substr($data['ESDATE'],0,4);

                    //取得司龄
                    $siling=date('Y') - $esYear;

                    if ($siling==0)
                    {
                        //1年及以下
                        $type=1;
                    }elseif ($siling >= 1 && $siling < 3)
                    {
                        //1年-2年
                        $type=2;
                    }elseif ($siling >= 3 && $siling < 5)
                    {
                        //3年-4年
                        $type=3;
                    }elseif ($siling >= 5 && $siling < 8)
                    {
                        //5年-7年
                        $type=4;
                    }elseif ($siling >= 8)
                    {
                        //10年及以上
                        $type=5;
                    }else
                    {
                        $type=1;
                    }

                    //组合公司信息
                    $return[$year][$name]=['type'=>$type,'siling'=>$siling,'date'=>$year];

                }else
                {
                    continue;
                }
            }
        }

        krsort($return);

        //整理数组
        if (!empty($return))
        {
            $tmp=[
                'type1'=>0,
                'type2'=>0,
                'type3'=>0,
                'type4'=>0,
                'type5'=>0,
            ];

            foreach ($return as $key => $one)
            {
                foreach ($one as $two)
                {
                    if (isset($tmp['type'.$two['type']])) $tmp['type'.$two['type']]++;
                }
            }

            $return=$tmp;
        }

        return $return;
    }
    //2，下游企业合作年限分布
    public function xyqyhznxfb()
    {
        $return=$tmp=[];

        if (empty($this->out)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year])) $return[$year]=[];

            //关键
            if (in_array($name,$return[$year])) continue;

            $return[$year][]=$name;
        }

        if (count($return) >= 2)
        {
            //必须是两年的信息，才显示
            //首先取得全部的公司名称
            $all=array_unique(control::array_flatten($return));

            //看看每个公司在多少个数组里有
            foreach ($return as $one)
            {
                for ($i=0;$i<count($all);$i++)
                {
                    if (in_array($all[$i],$one))
                    {
                        isset($tmp[$all[$i]]) ? $tmp[$all[$i]]++ : $tmp[$all[$i]]=1;
                    }
                }
            }

            //整理数组
            $return=[
                'type1'=>0,
                'type2'=>0,
                'type3'=>0,
            ];
            foreach ($tmp as $one)
            {
                switch ($one)
                {
                    case '1':
                        $return['type1']++;
                        break;
                    case '2':
                        $return['type2']++;
                        break;
                    default:
                        $return['type3']++;
                        break;
                }
            }

            return $return;
        }else
        {
            return [];
        }
    }
    //3，下游企业更换情况
    public function xyqyghqk()
    {
        $return=$tmp=[];

        if (empty($this->out)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year])) $return[$year]=[];

            //关键
            if (in_array($name,$return[$year])) continue;

            $return[$year][]=$name;
        }

        krsort($return);

        if (count($return) >= 3)
        {
            $key=array_keys($return);
            $val=array_values($return);

            for ($i=3;$i--;)
            {
                $tmp[$key[$i]]=[0,0];//[新增，退出]
            }

            //2和1对比新增和退出
            $all21=array_unique(array_merge($val[2],$val[1]));

            foreach ($all21 as $one)
            {
                //两个都在，没变
                if (in_array($one,$val[2]) && in_array($one,$val[1])) continue;

                if (in_array($one,$val[2]))
                {
                    $tmp[$key[1]][1]++;//退出

                }else
                {
                    $tmp[$key[1]][0]++;//新增
                }
            }

            //1和0对比新增和退出
            $all10=array_unique(array_merge($val[1],$val[0]));

            foreach ($all10 as $one)
            {
                //两个都在，没变
                if (in_array($one,$val[1]) && in_array($one,$val[0])) continue;

                if (in_array($one,$val[1]))
                {
                    $tmp[$key[0]][1]++;//退出

                }else
                {
                    $tmp[$key[0]][0]++;//新增
                }
            }

            krsort($tmp);

            return $tmp;
        }else
        {
            return [];
        }
    }

    //6.3.2下游客户集中度
    //1，下游企业地域分布
    public function xyqydyfb()
    {
        $return=$tmp=[];

        if (empty($this->out)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->out as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);
            isset($tmp[$year]) ?: $tmp[$year]=[];

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!in_array($name,$tmp[$year]))
            {
                //调用企业详情接口，查看地域
                $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $name], 'getRegisterInfo');
                ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

                //错误就不统计了
                if (empty($res)) continue;

                $data=$res;

                if (!isset($data['PROVINCE']) || empty(trim($data['PROVINCE']))) continue;

                if (isset($return[$year][trim($data['PROVINCE'])]))
                {
                    $return[$year][trim($data['PROVINCE'])]++;
                }else
                {
                    $return[$year][trim($data['PROVINCE'])]=1;
                }

                array_push($tmp[$year],$name);
            }
        }

        krsort($return);

        if (count($return) >= 3) $return=array_slice($return,0,2);

        //这个有点费劲，先整理出所有的地域
        $diyu=[];
        foreach ($return as $key => $val)
        {
            $diyu[]=array_keys($val);
        }

        $diyu=array_unique(control::array_flatten($diyu));

        $diyu_tmp=[];
        foreach ($diyu as $one)
        {
            $diyu_tmp[$one]=0;
        }
        $diyu=$diyu_tmp;

        //然后组成数组
        $return_tmp=[];
        foreach ($return as $key => $val)
        {
            //制作了一个全量的地域数组
            if (!isset($return_tmp[$key]))
            {
                $return_tmp[$key]=$diyu;
            }

            foreach ($val as $k => $v)
            {
                if (isset($return_tmp[$key][$k])) $return_tmp[$key][$k]=$v;
            }
        }

        $return=$return_tmp;

        return $return;
    }
    //2，销售前十企业总占比
    public function xsqsqyzzb()
    {
        $return=$tmp=[];

        if (empty($this->out)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->out as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['purchaserName']);

            if (empty($name)) continue;

            //只要最近两年的
            if (isset($return[$year][$name]))
            {
                $return[$year][$name]+=abs($one['totalAmount']);
            }else
            {
                $return[$year][$name]=abs($one['totalAmount']);
            }

            isset($tmp[$year]) ? $tmp[$year]+=abs($one['totalAmount']) : $tmp[$year]=abs($one['totalAmount']);
        }

        CommonService::getInstance()->log4PHP($tmp);

        if (count($return) >= 3)
        {
            krsort($return);

            $return=array_slice($return,0,2);
        }

        //整理数组
        foreach ($return as $year => &$one)
        {
            foreach ($one as $name => &$val)
            {
                if ($tmp[$year] <= 0)
                {
                    $val=0;
                }else
                {
                    $val=sprintf('%.2f',$val/$tmp[$year]*100);
                }
            }
            unset($val);
        }
        unset($one);

        //排序
        $tmp=[];
        foreach ($return as $year => $val)
        {
            foreach ($val as $name => $zhanbi)
            {
                $tmp[]=['name'=>$name,'zhanbi'=>$zhanbi];
            }

            $tmp=control::sortArrByKey($tmp,'zhanbi');
            $tmp=array_slice($tmp,0,10);

            $wanghan=[];
            foreach ($tmp as $k => $v)
            {
                $wanghan[$v['name']]=$v['zhanbi'];
            }

            $return[$year]=$wanghan;
            $tmp=[];
        }

        return $return;
    }

    //6.3.3企业销售情况预测
    public function qyxsqkyc()
    {
        //按月统计个折线图
        //只要最近两年的

        $return=[];

        if (empty($this->out)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->out as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $mouth=(int)$year[1];
            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year]))
            {
                for ($i=1;$i<=12;$i++)
                {
                    $return[$year][$i]=0;
                }
            }

            $return[$year][$mouth]+=abs($one['totalAmount']);
        }

        krsort($return);

        if (count($return) >= 3) $return=array_slice($return,0,2);

        //金额变万元
        foreach ($return as &$one)
        {
            foreach ($one as &$two)
            {
                $two=sprintf('%.1f',$two/10000);
            }
            unset($two);
        }
        unset($one);

        return $return;
    }

    //6.4.1年度进项发票情况汇总
    public function ndjxfpqkhz()
    {
        $return=[
            'normalNum'=>0,
            'normal'=>0,
            'max'=>0,
            'min'=>Carbon::now()->format('Ymd')
        ];

        if (empty($this->in)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->in as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $date=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($date)!=3) continue;

            $date=implode('',$date);

            if ($date >= $return['max']) $return['max']=$date;
            if ($date <= $return['min']) $return['min']=$date;

            $return['normalNum']++;
            $return['normal']+=abs($one['totalAmount']);
        }

        if (!empty($return)) $return['normal']=sprintf('%.1f',$return['normal']/10000);

        return $return;
    }

    //6.4.2月度进项发票分析
    public function ydjxfpfx()
    {
        $return=[];

        if (empty($this->in)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->in as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $date=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($date)!=3) continue;

            $year=current($date);
            $mout=(int)$date[1];

            if (!isset($return[$year]))
            {
                for ($i=1;$i<=12;$i++)
                {
                    $return[$year][$i]=0;
                }
            }

            $return[$year][$mout]+=abs($one['totalAmount']);
        }

        krsort($return);

        foreach ($return as &$one)
        {
            foreach ($one as &$two)
            {
                $two=sprintf('%.1f',$two/10000);
            }
            unset($two);
        }
        unset($one);

        return $return;
    }

    //6.4.3累计开票金额TOP10企业汇总
    public function ljkpjeTOP10qyhz_jx()
    {
        $tacoTuesday=$return=$total=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            if ($one['state']!=0) continue;

            //取得公司名称
            $name=trim($one['salesTaxName']);

            //判断该公司在当年的开票总数和总额
            if (!isset($total[$year][$name]))
            {
                $total[$year][$name]['total']=0;
                $total[$year][$name]['num']=0;
                $total[$year][$name]['name']=$name;
                $total[$year][$name]['date']=$year;
                $total[$year][$name]['totalTax']=0;
            }
            if (!isset($tacoTuesday[$year]))
            {
                $tacoTuesday[$year]['total']=0;
                $tacoTuesday[$year]['num']=0;
            }

            //加上金额
            $total[$year][$name]['total']+=abs($one['totalAmount']);
            $tacoTuesday[$year]['total']+=abs($one['totalAmount']);
            //加上税额
            $total[$year][$name]['totalTax']+=abs($one['totalTax']);
            //加上总数
            $total[$year][$name]['num']++;
            $tacoTuesday[$year]['num']++;
            //加上税号
            $total[$year][$name]['salesTaxNo']=$one['salesTaxNo'];
        }

        //这次是在total数组里填装的
        krsort($total);

        //计算占比
        foreach ($total as $year => &$val)
        {
            foreach ($val as &$one)
            {
                if ($tacoTuesday[$year]['total'] <= 0)
                {
                    $one['totalZhanbi']=0;
                }else
                {
                    $one['totalZhanbi']=sprintf('%.1f',$one['total']/$tacoTuesday[$year]['total']*100);
                }

                if ($tacoTuesday[$year]['num'] <= 0)
                {
                    $one['numZhanbi']=0;
                }else
                {
                    $one['numZhanbi']=sprintf('%.1f',$one['num']/$tacoTuesday[$year]['num']*100);
                }
            }
            unset($one);
        }
        unset($val);

        //排序
        foreach ($total as $key => $one)
        {
            $tmp=control::sortArrByKey($one,'total');

            $total[$key]=array_slice($tmp,0,10);
        }
        unset($one);

        //金额变万元
        foreach ($total as &$one)
        {
            foreach ($one as &$two)
            {
                $two['totalTax']=sprintf('%.1f',$two['totalTax']/10000);
                $two['total']=sprintf('%.1f',$two['total']/10000);
            }
            unset($two);
        }
        unset($one);

        if (count(current($total)) < 10 && count($total) >= 2)
        {
            $tmp=[];
            foreach ($total as $one)
            {
                foreach ($one as $two)
                {
                    $tmp[]=$two;
                }
            }

            $tmp=control::sortArrByKey($tmp,'total');
            $tmp=array_slice($tmp,0,10);

            return $tmp;

        }else
        {
            return current($total);
        }
    }

    //6.4.4单张金额TOP10企业汇总
    public function dzkpjeTOP10jl_jx()
    {
        $return=[];
        //总金额
        $total=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as &$one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $date=implode('',$year);
            $year=current($year);

            if (!isset($return[$year]))
            {
                $return[$year]=[];
                $total[$year]=0;
            }

            if ($one['state']!=0) continue;

            $total[$year]+=abs($one['totalAmount']);
            $one['date']=$date;
            $return[$year][]=$one;
        }
        unset($one);

        krsort($return);

        //二维数组排序
        foreach ($return as $key => &$one)
        {
            $one=control::sortArrByKey($one,'totalAmount');
        }
        unset($one);

        //整理数组
        $target=$name=[];
        foreach ($return as $year => $val)
        {
            isset($target[$year]) ?: $target[$year]=[];

            foreach ($val as $k => $v)
            {
                if (in_array($v['salesTaxName'],$name) || count($target[$year]) > 10) continue;
                array_push($target[$year],$v);
                array_push($name,$v['salesTaxName']);
            }
        }

        $return=$target;

        //计算占比
        foreach ($return as &$one)
        {
            foreach ($one as &$two)
            {
                if ($total[substr($two['date'],0,4)] <= 0)
                {
                    $two['zhanbi']=0;
                }else
                {
                    $two['zhanbi']=sprintf('%.1f',$two['totalAmount']/$total[substr($two['date'],0,4)]*100);
                }
            }
            unset($two);
        }
        unset($one);

        if (empty($return)) return $return;

        $tmp=[];
        foreach ($return as $year)
        {
            foreach ($year as $one)
            {
                $tmp[]=$one;
            }
        }

        $tmp=control::sortArrByKey($tmp,'totalAmount');
        $tmp=array_slice($tmp,0,10);

        //金额变万元
        foreach ($tmp as &$one)
        {
            $one['totalTax']=sprintf('%.1f',$one['totalTax']/10000);
            $one['totalAmount']=sprintf('%.1f',$one['totalAmount']/10000);

            //修改一下date的值，懒得在别处改了
            $one['date']=substr($one['date'],0,4);
        }
        unset($one);

        return $tmp;
    }

    //6.5.1上游共饮上稳定性分析
    //1，上游供应商司龄分布
    public function sygysslfb()
    {
        $return=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year][$name]))
            {
                //调用企业详情接口，查看司龄
                $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $name], 'getRegisterInfo');
                ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

                //错误就不统计了
                if (empty($res)) continue;

                $data=$res;

                if (isset($data['ESDATE']) && !empty($data['ESDATE']) && strlen($data['ESDATE']>=4))
                {
                    //取得成立年
                    $esYear=substr($data['ESDATE'],0,4);

                    //取得司龄
                    $siling=date('Y') - $esYear;

                    if ($siling==0)
                    {
                        //1年及以下
                        $type=1;
                    }elseif ($siling >= 1 && $siling < 3)
                    {
                        //1年-2年
                        $type=2;
                    }elseif ($siling >= 3 && $siling < 5)
                    {
                        //3年-4年
                        $type=3;
                    }elseif ($siling >= 5 && $siling < 8)
                    {
                        //5年-7年
                        $type=4;
                    }elseif ($siling >= 8)
                    {
                        //10年及以上
                        $type=5;
                    }else
                    {
                        $type=1;
                    }

                    //组合公司信息
                    $return[$year][$name]=['type'=>$type,'siling'=>$siling,'date'=>$year];

                }else
                {
                    continue;
                }
            }
        }

        krsort($return);

        //整理数组
        if (!empty($return))
        {
            $tmp=[
                'type1'=>0,
                'type2'=>0,
                'type3'=>0,
                'type4'=>0,
                'type5'=>0,
            ];

            foreach ($return as $year => $one)
            {
                foreach ($one as $two)
                {
                    if (isset($tmp['type'.$two['type']])) $tmp['type'.$two['type']]++;
                }
            }

            $return=$tmp;
        }

        return $return;
    }
    //2，上游供应商合作年限分布
    public function sygyshznxfb()
    {
        $return=$tmp=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year])) $return[$year]=[];

            //关键
            if (in_array($name,$return[$year])) continue;

            $return[$year][]=$name;
        }

        if (count($return) >= 3)
        {
            //必须是三年的信息，才显示
            //首先取得全部的公司名称
            $all=array_unique(control::array_flatten($return));

            //看看每个公司在多少个数组里有
            foreach ($return as $one)
            {
                for ($i=0;$i<count($all);$i++)
                {
                    if (in_array($all[$i],$one))
                    {
                        isset($tmp[$all[$i]]) ? $tmp[$all[$i]]++ : $tmp[$all[$i]]=1;
                    }
                }
            }

            //整理数组
            $return=[
                'type1'=>0,
                'type2'=>0,
                'type3'=>0,
            ];
            foreach ($tmp as $one)
            {
                switch ($one)
                {
                    case '1':
                        $return['type1']++;
                        break;
                    case '2':
                        $return['type2']++;
                        break;
                    default:
                        $return['type3']++;
                        break;
                }
            }

            return $return;
        }else
        {
            return [];
        }
    }
    //3，上游供应商更换情况
    public function sygysghqk()
    {
        $return=$tmp=[];

        if (empty($this->in)) return $return;

        //首先看看下游有多少个企业
        foreach ($this->in as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year])) $return[$year]=[];

            //关键
            if (in_array($name,$return[$year])) continue;

            $return[$year][]=$name;
        }

        krsort($return);

        if (count($return) >= 3)
        {
            $key=array_keys($return);
            $val=array_values($return);

            for ($i=3;$i--;)
            {
                $tmp[$key[$i]]=[0,0];//[新增，退出]
            }

            //2和1对比新增和退出
            $all21=array_unique(array_merge($val[2],$val[1]));

            foreach ($all21 as $one)
            {
                //两个都在，没变
                if (in_array($one,$val[2]) && in_array($one,$val[1])) continue;

                if (in_array($one,$val[2]))
                {
                    $tmp[$key[1]][1]++;//退出

                }else
                {
                    $tmp[$key[1]][0]++;//新增
                }
            }

            //1和0对比新增和退出
            $all10=array_unique(array_merge($val[1],$val[0]));

            foreach ($all10 as $one)
            {
                //两个都在，没变
                if (in_array($one,$val[1]) && in_array($one,$val[0])) continue;

                if (in_array($one,$val[1]))
                {
                    $tmp[$key[0]][1]++;//退出

                }else
                {
                    $tmp[$key[0]][0]++;//新增
                }
            }

            krsort($tmp);

            return $tmp;
        }else
        {
            return [];
        }
    }

    //6.5.2上游供应商集中度分析
    //1，上游企业地域分布
    public function syqydyfb()
    {
        $return=$tmp=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as $one)
        {
            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);
            isset($tmp[$year]) ?: $tmp[$year]=[];

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!in_array($name,$tmp[$year]))
            {
                //调用企业详情接口，查看地域
                $res = (new TaoShuService())->setCheckRespFlag(true)->post(['entName' => $name], 'getRegisterInfo');
                ($res['code'] === 200 && !empty($res['result'])) ? $res = current($res['result']) : $res = null;

                //错误就不统计了
                if (empty($res)) continue;

                $data=$res;

                if (!isset($data['PROVINCE']) || empty(trim($data['PROVINCE']))) continue;

                if (isset($return[$year][trim($data['PROVINCE'])]))
                {
                    $return[$year][trim($data['PROVINCE'])]++;
                }else
                {
                    $return[$year][trim($data['PROVINCE'])]=1;
                }

                array_push($tmp[$year],$name);
            }
        }

        krsort($return);

        if (count($return) >= 3) $return=array_slice($return,0,2);

        //这个有点费劲，先整理出所有的地域
        $diyu=[];
        foreach ($return as $key => $val)
        {
            $diyu[]=array_keys($val);
        }

        $diyu=array_unique(control::array_flatten($diyu));

        $diyu_tmp=[];
        foreach ($diyu as $one)
        {
            $diyu_tmp[$one]=0;
        }
        $diyu=$diyu_tmp;

        //然后组成数组
        $return_tmp=[];
        foreach ($return as $key => $val)
        {
            //制作了一个全量的地域数组
            if (!isset($return_tmp[$key]))
            {
                $return_tmp[$key]=$diyu;
            }

            foreach ($val as $k => $v)
            {
                if (isset($return_tmp[$key][$k])) $return_tmp[$key][$k]=$v;
            }
        }

        $return=$return_tmp;

        return $return;
    }
    //2，采购前十企业总占比
    public function cgqsqyzzb()
    {
        $return=$tmp=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            //只要最近两年的
            if (isset($return[$year][$name]))
            {
                $return[$year][$name]+=abs($one['totalAmount']);
            }else
            {
                $return[$year][$name]=abs($one['totalAmount']);
            }

            isset($tmp[$year]) ? $tmp[$year]+=abs($one['totalAmount']) : $tmp[$year]=abs($one['totalAmount']);
        }

        if (count($return) >= 3)
        {
            krsort($return);

            $return=array_slice($return,0,2);
        }

        //整理数组
        foreach ($return as $year => &$one)
        {
            foreach ($one as $name => &$val)
            {
                if ($tmp[$year] <= 0)
                {
                    $val=0;
                }else
                {
                    $val=sprintf('%.2f',$val/$tmp[$year]*100);
                }
            }
            unset($val);
        }
        unset($one);

        //排序，只要前十
        foreach ($return as $key => $val)
        {
            $tmp=$val;

            arsort($tmp);

            $return[$key]=$tmp;

            $return[$key]=array_slice($return[$key],0,10);
        }

        return $return;
    }

    //6.5.3企业采购情况预测
    public function qycgqkyc()
    {
        //按月统计个折线图
        //只要最近两年的

        $return=[];

        if (empty($this->in)) return $return;

        foreach ($this->in as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $year=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($year)!=3) continue;

            $mouth=(int)$year[1];
            $year=current($year);

            $name=trim($one['salesTaxName']);

            if (empty($name)) continue;

            if (!isset($return[$year]))
            {
                for ($i=1;$i<=12;$i++)
                {
                    $return[$year][$i]=0;
                }
            }

            $return[$year][$mouth]+=abs($one['totalAmount']);
        }

        krsort($return);

        if (count($return) >= 3) $return=array_slice($return,0,2);

        //金额变万元
        foreach ($return as &$one)
        {
            foreach ($one as &$two)
            {
                $two=sprintf('%.1f',$two/10000);
            }
            unset($two);
        }
        unset($one);

        ksort($return);

        $data=[
            'xAxes'=>[],
            'label'=>'',
            'data'=>[],
        ];

        if (empty($return)) return $return;

        //整理数据
        if (count($return) >= 2)
        {
            foreach ($return as $year => $val)
            {
                foreach ($val as $month => $v)
                {
                    if ($v > 0 || isset($takeData))
                    {
                        if ($data['label'] == '')
                        {
                            $data['label']=$year.".$month";
                        }elseif (strlen($data['label']) <= 10 && substr($data['label'],0,4)!=$year)
                        {
                            $data['label'].=" - $year";
                        }else
                        {
                            if (count($data['data']) == 12) continue;
                        }

                        array_push($data['xAxes'],$month.'月');
                        array_push($data['data'],$v);
                        $takeData=1;
                    }
                }
            }

            //最后在xAxes里找出最后一个月的月份值拼接到label里
            $mouth=$data['xAxes'][count($data['xAxes'])-1];

            $mouth=current(explode('月',$mouth));

            $data['label'].=".$mouth";

        }else
        {
            foreach ($return as $year => $one)
            {
                $data['label']=$year;

                foreach ($one as $month => $v)
                {
                    array_push($data['xAxes'],$month.'月');
                    array_push($data['data'],$v);
                }
            }
        }

        $return=$data;

        return $return;
    }

    //信动指数-发票项
    public function xdsForFaPiao()
    {
        $return=[];
        $tmp=[
            'type1'=>[],
            'type2'=>[]
        ];

        foreach ($this->out as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $date=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($date)!=3) continue;

            isset($return[$date[0]][$date[1]]) ?: $return[$date[0]][$date[1]]=0;

            $return[$date[0]][$date[1]]+=abs($one['totalAmount']);
        }

        $tmp['type1']=$return;

        $return=[];

        foreach ($this->in as $one)
        {
            //先判断是否是正常发票
            if ($one['state']!=0) continue;

            $date=explode('-',trim($one['billingDate']));

            //不是年月日的格式就下一条
            if (count($date)!=3) continue;

            isset($return[$date[0]][$date[1]]) ?: $return[$date[0]][$date[1]]=0;

            $return[$date[0]][$date[1]]+=abs($one['totalAmount']);
        }

        $tmp['type2']=$return;

        $return=$tmp;

        return $return;
    }

    //信动指数-上下游
    public function xdsForShangxiayou()
    {
        //下游企业司龄
        $tmp1=$this->xyqyslfb();
        //下游企业合作年限
        $tmp2=$this->xyqyhznxfb();
        //下游企业地域分布
        $tmp3=$this->xyqydyfb();
        //销售前十企业占比
        $tmp4=$this->xsqsqyzzb();

        //上游企业司龄
        $tmp5=$this->sygysslfb();
        //上游合作年限
        $tmp6=$this->sygyshznxfb();
        //上游地域分布
        $tmp7=$this->syqydyfb();
        //企业采购情况
        $tmp8=$this->cgqsqyzzb();

        return [
            '下游司龄'=>$tmp1,'下游合作年限'=>$tmp2,'下游地域分布'=>$tmp3,'下游销售前十'=>$tmp4,
            '上游司龄'=>$tmp5,'上游合作年限'=>$tmp6,'上游地域分布'=>$tmp7,'上游销售前十'=>$tmp8,
        ];
    }










}