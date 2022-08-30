<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class CompanyBasic extends ModelBase
{
    protected $tableName = 'company_basic';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic');
    }

    public static function findById($id){
        $res =  CompanyBasic::create()
            ->where('companyid',$id)
            ->get();
        return $res;
    }

    public static function findByCode($UNISCID){
        $res =  CompanyBasic::create()
            ->where('UNISCID',$UNISCID)
            ->get();
        return $res;
    }

    public static function findByName($name){
        $res =  CompanyBasic::create()
            ->where('ENTNAME',$name)
            ->get();
        return $res;
    }


    public static function findRegion($code){
        $Sql = "SELECT * FROM code_region  WHERE `code` = '$code' limit 1  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic'));
        return $data[0];
    }




    public static function findCancelDateByCode($UNISCID){
        $res =  self::findByCode($UNISCID);
        return $res?$res->getAttr('CANDATE'):'';
    }



    public static function findByConditionV2($whereArr,$page){
        $model = CompanyBasic::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }
    public static function findByConditionV3($whereArr){
        $model = CompanyBasic::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findBriefName($name){
        //去除特殊符号
        $name = preg_replace('/\(.*?\)/', '', $name);
        $name = preg_replace('/\（.*?\）/', '', $name);


        $json = '{
                    "province": ["北京市", "天津市", "河北省", "山西省", "内蒙古自治区", "辽宁省", "吉林省", "黑龙江省", "上海市", "江苏省", "浙江省", "安徽省", "福建省", "江西省", "山东省", "河南省", "湖北省", "湖南省", "广东省", "海南省", "广西壮族自治区", "甘肃省", "陕西省", "新疆维吾尔自治区", "青海省", "宁夏回族自治区", "重庆市", "四川省", "贵州省", "云南省", "西藏自治区", "台湾省", "澳门特别行政区", "香港特别行政区", "北京", "天津", "河北", "山西", "内蒙古", "辽宁", "吉林", "黑龙江", "上海", "江苏", "浙江", "安徽", "福建", "江西", "山东", "河南", "湖北", "湖南", "广东", "海南", "广西壮族", "广西", "甘肃", "陕西", "新疆维吾尔", "青海", "宁夏回族", "宁夏", "重庆", "四川", "贵州", "云南", "西藏", "台湾", "澳门特别行政区", "澳门", "香港特别行政区", "香港"],
                    "cities": ["自治区", "呼和浩特市", "鄂尔多斯市", "呼伦贝尔市", "巴彦淖尔市", "乌兰察布市", "齐齐哈尔市", "乌鲁木齐市", "克拉玛依市", "石家庄市", "秦皇岛市", "张家口市", "呼和浩特", "鄂尔多斯", "呼伦贝尔", "巴彦淖尔", "乌兰察布", "葫芦岛市", "哈尔滨市", "齐齐哈尔", "双鸭山市", "佳木斯市", "七台河市", "牡丹江市", "连云港市", "马鞍山市", "景德镇市", "平顶山市", "三门峡市", "驻马店市", "张家界市", "防城港市", "攀枝花市", "六盘水市", "日喀则市", "嘉峪关市", "石嘴山市", "乌鲁木齐", "克拉玛依", "吐鲁番市", "北京市", "天津市", "石家庄", "唐山市", "秦皇岛", "邯郸市", "邢台市", "保定市", "张家口", "承德市", "沧州市", "廊坊市", "衡水市", "太原市", "大同市", "阳泉市", "长治市", "晋城市", "朔州市", "晋中市", "运城市", "忻州市", "临汾市", "吕梁市", "包头市", "乌海市", "赤峰市", "通辽市", "沈阳市", "大连市", "鞍山市", "抚顺市", "本溪市", "丹东市", "锦州市", "营口市", "阜新市", "辽阳市", "盘锦市", "铁岭市", "朝阳市", "葫芦岛", "长春市", "吉林市", "四平市", "辽源市", "通化市", "白山市", "松原市", "白城市", "哈尔滨", "鸡西市", "鹤岗市", "双鸭山", "大庆市", "伊春市", "佳木斯", "七台河", "牡丹江", "黑河市", "绥化市", "上海市", "南京市", "无锡市", "徐州市", "常州市", "苏州市", "南通市", "连云港", "淮安市", "盐城市", "扬州市", "镇江市", "泰州市", "宿迁市", "杭州市", "宁波市", "温州市", "嘉兴市", "湖州市", "绍兴市", "金华市", "衢州市", "舟山市", "台州市", "丽水市", "合肥市", "芜湖市", "蚌埠市", "淮南市", "马鞍山", "淮北市", "铜陵市", "安庆市", "黄山市", "滁州市", "阜阳市", "宿州市", "六安市", "亳州市", "池州市", "宣城市", "福州市", "厦门市", "莆田市", "三明市", "泉州市", "漳州市", "南平市", "龙岩市", "宁德市", "南昌市", "景德镇", "萍乡市", "九江市", "新余市", "鹰潭市", "赣州市", "吉安市", "宜春市", "抚州市", "上饶市", "济南市", "青岛市", "淄博市", "枣庄市", "东营市", "烟台市", "潍坊市", "济宁市", "泰安市", "威海市", "日照市", "临沂市", "德州市", "聊城市", "滨州市", "菏泽市", "郑州市", "开封市", "洛阳市", "平顶山", "安阳市", "鹤壁市", "新乡市", "焦作市", "濮阳市", "许昌市", "漯河市", "三门峡", "南阳市", "商丘市", "信阳市", "周口市", "驻马店", "武汉市", "黄石市", "十堰市", "宜昌市", "襄阳市", "鄂州市", "荆门市", "孝感市", "荆州市", "黄冈市", "咸宁市", "随州市", "长沙市", "株洲市", "湘潭市", "衡阳市", "邵阳市", "岳阳市", "常德市", "张家界", "益阳市", "郴州市", "永州市", "怀化市", "娄底市", "广州市", "韶关市", "深圳市", "珠海市", "汕头市", "佛山市", "江门市", "湛江市", "茂名市", "肇庆市", "惠州市", "梅州市", "汕尾市", "河源市", "阳江市", "清远市", "东莞市", "中山市", "潮州市", "揭阳市", "云浮市", "南宁市", "柳州市", "桂林市", "梧州市", "北海市", "防城港", "钦州市", "贵港市", "玉林市", "百色市", "贺州市", "河池市", "来宾市", "崇左市", "海口市", "三亚市", "三沙市", "儋州市", "重庆市", "成都市", "自贡市", "攀枝花", "泸州市", "德阳市", "绵阳市", "广元市", "遂宁市", "内江市", "乐山市", "南充市", "眉山市", "宜宾市", "广安市", "达州市", "雅安市", "巴中市", "资阳市", "贵阳市", "六盘水", "遵义市", "安顺市", "毕节市", "铜仁市", "昆明市", "曲靖市", "玉溪市", "保山市", "昭通市", "丽江市", "普洱市", "临沧市", "拉萨市", "日喀则", "昌都市", "林芝市", "山南市", "那曲市", "西安市", "铜川市", "宝鸡市", "咸阳市", "渭南市", "延安市", "汉中市", "榆林市", "安康市", "商洛市", "兰州市", "嘉峪关", "金昌市", "白银市", "天水市", "武威市", "张掖市", "平凉市", "酒泉市", "庆阳市", "定西市", "陇南市", "西宁市", "海东市", "银川市", "石嘴山", "吴忠市", "固原市", "中卫市", "吐鲁番", "哈密市", "北京", "天津", "唐山", "邯郸", "邢台", "保定", "承德", "沧州", "廊坊", "衡水", "太原", "大同", "阳泉", "长治", "晋城", "朔州", "晋中", "运城", "忻州", "临汾", "吕梁", "包头", "乌海", "赤峰", "通辽", "沈阳", "大连", "鞍山", "抚顺", "本溪", "丹东", "锦州", "营口", "阜新", "辽阳", "盘锦", "铁岭", "朝阳", "长春", "吉林", "四平", "辽源", "通化", "白山", "松原", "白城", "鸡西", "鹤岗", "大庆", "伊春", "黑河", "绥化", "上海", "南京", "无锡", "徐州", "常州", "苏州", "南通", "淮安", "盐城", "扬州", "镇江", "泰州", "宿迁", "杭州", "宁波", "温州", "嘉兴", "湖州", "绍兴", "金华", "衢州", "舟山", "台州", "丽水", "合肥", "芜湖", "蚌埠", "淮南", "淮北", "铜陵", "安庆", "黄山", "滁州", "阜阳", "宿州", "六安", "亳州", "池州", "宣城", "福州", "厦门", "莆田", "三明", "泉州", "漳州", "南平", "龙岩", "宁德", "南昌", "萍乡", "九江", "新余", "鹰潭", "赣州", "吉安", "宜春", "抚州", "上饶", "济南", "青岛", "淄博", "枣庄", "东营", "烟台", "潍坊", "济宁", "泰安", "威海", "日照", "临沂", "德州", "聊城", "滨州", "菏泽", "郑州", "开封", "洛阳", "安阳", "鹤壁", "新乡", "焦作", "濮阳", "许昌", "漯河", "南阳", "商丘", "信阳", "周口", "武汉", "黄石", "十堰", "宜昌", "襄阳", "鄂州", "荆门", "孝感", "荆州", "黄冈", "咸宁", "随州", "长沙", "株洲", "湘潭", "衡阳", "邵阳", "岳阳", "常德", "益阳", "郴州", "永州", "怀化", "娄底", "广州", "韶关", "深圳", "珠海", "汕头", "佛山", "江门", "湛江", "茂名", "肇庆", "惠州", "梅州", "汕尾", "河源", "阳江", "清远", "东莞", "中山", "潮州", "揭阳", "云浮", "南宁", "柳州", "桂林", "梧州", "北海", "钦州", "贵港", "玉林", "百色", "贺州", "河池", "来宾", "崇左", "海口", "三亚", "三沙", "儋州", "重庆", "成都", "自贡", "泸州", "德阳", "绵阳", "广元", "遂宁", "内江", "乐山", "南充", "眉山", "宜宾", "广安", "达州", "雅安", "巴中", "资阳", "贵阳", "遵义", "安顺", "毕节", "铜仁", "昆明", "曲靖", "玉溪", "保山", "昭通", "丽江", "普洱", "临沧", "拉萨", "昌都", "林芝", "山南", "那曲", "西安", "铜川", "宝鸡", "咸阳", "渭南", "延安", "汉中", "榆林", "安康", "商洛", "兰州", "金昌", "白银", "天水", "武威", "张掖", "平凉", "酒泉", "庆阳", "定西", "陇南", "西宁", "海东", "银川", "吴忠", "固原", "中卫", "哈密"],
                    "company_suffixes": ["公司","企业管理咨询有限责任公司", "企业管理咨询责任有限公司", "信息科技有限责任公司", "信息科技责任有限公司", "管理咨询责任有限公司", "管理咨询有限责任公司", "企业管理咨询有限公司", "信息技术有限责任公司", "信息技术责任有限公司", "劳务派遣服务有限公司", "劳务派遣有限责任公司", "劳务派遣有限公司", "科技股份有限公司", "科技有限责任公司", "科技责任有限公司", "信息科技有限公司", "网络科技有限公司", "咨询有限责任公司", "咨询责任有限公司", "管理咨询有限公司", "信息技术有限公司", "技术有限责任公司", "技术责任有限公司","责任", "管理有限责任公司", "管理责任有限公司", "科技服务有限公司", "食品发展有限公司", "食品集团有限公司", "集团股份有限公司", "食品有限公司", "实业有限公司", "集团有限公司", "责任有限公司", "有限责任公司", "股份有限公司", "科技有限公司", "信息有限公司", "咨询有限公司", "技术有限公司", "管理有限公司", "发展有限公司", "投资公司", "有限公司","有限", "集团"]
                }';
        $dataArr = json_decode($json,true);

        //分词
        $wordsArr = jieba($name, 1);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'findBriefName'=>'jieba',
                    '$wordsArr'=>$wordsArr,
                ]
            ])
        );
        $validWordsArr = [];
        $areasArr = '';
        foreach ($wordsArr as $wordItem){
            //删除省份
            if(
                in_array($wordItem, $dataArr['province'])
            ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'findBriefName'=>'is_province',
                        '$wordItem'=>$wordItem,
                    ]
                ])
            );

                $province = str_replace("省", "", $wordItem);
                $areasArr = $province;
                continue;
            }
            //删除市
            if(
                in_array($wordItem, $dataArr['cities'])
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'findBriefName'=>'is_cities',
                            '$wordItem'=>$wordItem,
                        ]
                    ])
                );

                $cities = str_replace("市", "", $wordItem);
                $areasArr = $cities;
                continue;
            }

            // 删除公司 前缀
            if(
                in_array($wordItem, $dataArr['company_suffixes'])
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'findBriefName'=>'is_company_suffixes',
                            '$wordItem'=>$wordItem,
                        ]
                    ])
                );

                continue;
            }
            // 删除县
            if (strpos($wordItem, '县') !== false) {
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'findBriefName'=>'is_district',
                            '$wordItem'=>$wordItem,
                        ]
                    ])
                );

                $district = str_replace("县", "", $wordItem);
                $areasArr = $district;
                continue;
            }
            $validWordsArr[] = $wordItem;
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'findBriefName'=>'$validWordsArr',
                    '$validWordsArr'=>$validWordsArr,
                ]
            ])
        );

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'findBriefName'=>'$areasArr',
                    '$areasArr'=>$areasArr,
                ]
            ])
        );

        $newName = '';
        $legth = 0;
        foreach ($validWordsArr as $wordItem){
           if($legth >= 16){
               CommonService::getInstance()->log4PHP(
                   json_encode([
                       __CLASS__.__FUNCTION__ .__LINE__,
                       [
                           'findBriefName'=>'legth_bigger_than_16',
                           '$legth'=>$legth,
                           '$newName'=>$newName,
                       ]
                   ])
               );
                continue;
           }

            if(
                ($legth+strlen($wordItem)) >= 16
            ){
                if($legth==0){
                    //$areasArr;
                    //太短了
                    if(strlen($wordItem)<=8){

                        $newName = $areasArr.$wordItem;
                    }
                    else{
                        $newName = $wordItem;
                    }
                }

                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        [
                            'findBriefName'=>'legth_bigger_than_16_2',
                            '$legth'=>$legth,
                            '$newName'=>$newName,
                            '$wordItem'=>$wordItem,
                        ]
                    ])
                );

                continue;
            }

            $newName .= $wordItem;
            $legth += strlen($wordItem);
        }

        return $newName;
    }

}
