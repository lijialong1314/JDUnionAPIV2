<?php

class Unionv2API{
    private $appkey;
    private $appsecret;
    private $paramArr = ["couponUrls","spaceNameList"];
    private $url = "https://router.jd.com/api";
    private $method = [
        "queryOrder"=>"jd.union.open.order.query",
        "queryGoodsPromotionInfo"=>"jd.union.open.goods.promotiongoodsinfo.query",
        "getCommonPromotion"=>"jd.union.open.promotion.common.get",
        "getGoodsCategory"=>"jd.union.open.category.goods.get",
        "queryGoods"=>"jd.union.open.goods.query",
        "querySeckillGoods"=>"jd.union.open.goods.seckill.query",
        "getByUnionidPromotion"=>"jd.union.open.promotion.byunionid.get",
        "createPosition"=>"jd.union.open.position.create",
        "queryPosition"=>"jd.union.open.position.query",
        "queryCoupon"=>"jd.union.open.coupon.query"
    ];
     public function __construct($key,$secret) {
         $this->appkey = $key;
         $this->appsecret = $secret;
    }
    private function success($text,$data){
        return ["code"=>1,"msg"=>$text,"data"=>$data];
    }

    private function error($text,$data=""){
        return ["code"=>-1,"msg"=>$text,"data"=>$data];
    }
    
    private function check($param,$rule){
        if(isset($rule["Required"])){
            foreach($rule["Required"] as $n){
                if(!isset($param[$n])){
                    return $this->error("缺少参数：$n", "");
                }
            }
        }
        return true;
    }

    private function buildParam($method,$param=[]){
        $sysparam = [
            "method"=>$this->method[$method],
            "app_key"=>$this->appkey,
            "timestamp"=>date("Y-m-d H:i:s"),
            "format"=>"json",
            "v"=>"1.0",
            "sign_method"=>"md5",
            "param_json"=> json_encode($param)
        ];
        ksort($sysparam);
        $str="";
        foreach ($sysparam as $key => $value) {
            $str .= $key.$value;
        }

        $str = $this->appsecret.$str.$this->appsecret;
        $sign = strtoupper(md5($str));
        $sysparam['sign'] = $sign;
        return $sysparam;
    }

    private function isSetParam($paramNameArr,$param){
        $data = [];
        foreach ($paramNameArr as $key => $value) {
            if(isset($param[$value])){
                //针对创建推广位接口的spaceNameList字段需要转为数组形式
                if(in_array($value, $this->paramArr)){
                    $data[$value] = explode(",", $param[$value]);
                }else{
                    $data[$value] = $param[$value];
                }
            }
        }
        return $data;
    }

    private function doResponse($data,$repname){
        if($this->request->param("debug") == 1){
            return $this->success("ok",$data);
        }
        $data = json_decode($data,true);

        if(isset($data["errorResponse"])){
            return $this->error($data["errorResponse"]["msg"],"");
        }

        $data = $data[$repname]["result"];
        $data = json_decode($data,true);
        if($data["code"] === 200){
            if(isset($data['data'])){
                $data = $data["data"];
                return $this->success("ok",$data);
            }else{
                return $this->error($data["message"],"");
            }
        }else{
            return $this->error($data["message"],"");
        }
    }

    private function send_post($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$url);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        return $data;
    }
    
    //获取推广商品信息接口
    public function queryGoodsPromotionInfo($param=[]){
        $result = $this->check($param,[
            "Required"=>["skuIds"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam = [
            "skuIds"=>$param['skuIds']
        ];
        $p = $this->buildParam(__FUNCTION__,$appParam);

        $data = $this->send_post($this->url,$p);

        return $this->doResponse($data, "jd_union_open_goods_promotiongoodsinfo_query_response");
    }

    //订单查询接口
    public function queryOrder($param=[]){
        $result = $this->check($param,[
            "Required"=>["pageNo","pageSize","type","time"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam["orderReq"] = $this->isSetParam([
            "pageNo",
            "pageSize",
            "type",
            "time",
            "childUnionId",
            "key"
        ],$param);

        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_order_query_response");
    }

    //获取分类
    public function getGoodsCategory($param=[]){
        $result = $this->check($param,[
            "Required"=>["parentId","grade"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam["req"] = [
            "parentId"=>$param['parentId'],
            "grade"=>$param['grade']
        ];

        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_category_goods_get_response");
    }

    //关键词查询选品
    public function  queryGoods($param=[]){
        $result = $this->check($param,[
            "Required"=>["pageIndex"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam = [];
        $appParam["goodsReqDTO"] = $this->isSetParam([
            "cid1",
            "cid2",
            "cid3",
            "pageIndex",
            "pageSize",
            "skuIds",
            "keyword",
            "pricefrom",
            "priceto",
            "commissionShareStart",
            "commissionShareEnd",
            "owner",
            "sortName",
            "sort",
            "isCoupon",
            "isPG",
            "pingouPriceStart",
            "pingouPriceEnd",
            "isHot",
            "brandCode",
            "shopId"
        ], $param);


        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_goods_query_response");
    }

    //秒杀商品查询
    public function querySeckillGoods($param=[]){
        $result = $this->check($param,[
            "Required"=>["pageIndex"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam = [];

        $appParam["goodsReq"] = $this->isSetParam([
           "skuIds",
            "pageIndex",
            "pageSize",
            "isBeginSecKill",
            "secKillPriceFrom",
            "secKillPriceTo",
            "cid1",
            "cid2",
            "cid3",
            "owner",
            "commissionShareFrom",
            "commissionShareTo",
            "sortName",
            "sort"
        ], $param);

        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_goods_seckill_query_response");
    }

    //通过unionId获取推广链接
    public function getByUnionidPromotion($param=[]){
        $result=$this->check($param,[
            "Required"=>["materialId","unionId"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam["promotionCodeReq"] = $this->isSetParam([
           "materialId",
            "unionId",
            "positionId",
            "pid",
            "couponUrl"
        ], $param);
        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_promotion_byunionid_get_response");
    }

    //创建推广位
    public function createPosition($param=[]){
        $result= $this->check($param,[
            "Required"=>["unionId","key","unionType","type","spaceNameList","siteId"]
        ]);
        if($result !== true){
            return $result;
        }
        $appParam["positionReq"] = $this->isSetParam([
           "unionId",
            "key",
            "unionType",
            "type",
            "spaceNameList",
            "siteId"
        ], $param);
        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_position_create_response");
    }

    //查询推广位
    public function queryPosition($param=[]){
         $result= $this->check($param,[
            "Required"=>["unionId","key","unionType","pageIndex","pageSize"]
        ]);
         if($result !== true){
            return $result;
        }
        $appParam["positionReq"] = $this->isSetParam(["unionId","key","unionType","pageIndex","pageSize"], $param);
        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_position_query_response");
    }

    //优惠券领取情况查询接口
    public function queryCoupon($param=[]){
        $result=$this->check($param,[
            "Required"=>["couponUrls"]
        ]);
          if($result !== true){
            return $result;
        }
        $appParam["couponUrls"] = explode(",",$param["couponUrls"]);
        $p = $this->buildParam(__FUNCTION__,$appParam);
        $data = $this->send_post($this->url,$p);
        return $this->doResponse($data, "jd_union_open_coupon_query_response");
    }

}