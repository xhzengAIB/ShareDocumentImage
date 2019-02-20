<?php
/**
 * 微信支付的价格
 * User: huanghaiping
 * Date: 2018/1/15
 * Time: 22:10
 */

namespace app\common\model\order;


class OrderPrice extends \app\common\model\Common
{
    /**
     * 提交订单的人民币金额
     * @param $orderId
     * @param $orderPrice
     * @param $cnyOrderPrice
     * @param $newOrderSn
     * @param $rate
     * @return  boolean
     */
    public function addOrderPrice($orderId, $orderPrice, $cnyOrderPrice, $rate)
    {
        if (empty($orderId))
            return false;
        $orderCount = $this->where("order_id", $orderId)->count();
        $data = array('cny_price' => $cnyOrderPrice, 'rate' => $rate, 'usd_price' => $orderPrice);
        if ($orderCount > 0) {
            $result = $this->where("order_id", $orderId)->update($data);
        } else {
            $data['order_id'] = $orderId;
            $result = $this->insert($data);
        }
        return $result;
    }

    /**
     * 获取时间对维度
     * @param $hour
     * @return int|string
     */
    public function getHour($hour)
    {
        $hourType = 0;
        if ($hour >= 3 && $hour <= 12) {
            $hourType = "3_12";
        } else if ($hour > 12 && $hour <= 24) {
            $hourType = "12_24";
        } elseif ($hour > 24 && $hour <= 72) { //1天~3天
            $hourType = "24_72";
        } elseif ($hour > 72 && $hour <= 168) { //4~7天
            $hourType = "72_168";
        } elseif ($hour > 168) {
            $hourType = "168";
        }
        return $hourType;
    }


    /**
     * 根据写手对等级值获取价格,10专业老师,20金牌老师,30王牌老师
     * @param $hour
     * @param int $writerLevel
     * @return int
     */
    public function estimatedPriceWriterLevel($hour, $writerLevel = 10)
    {
        $price = 0;
        $hourKey = $this->getHour($hour);
        if (empty($hourKey)) {
            return $price;
        }
        $priceArray = array(
            "3_12" => array(10 => 60, 20 => 57, 30 => 65),
            "12_24" => array(10 => 42, 20 => 49, 30 => 57),
            "24_72" => array(10 => 40, 20 => 47, 30 => 55),//1天~3天
            "72_168" => array(10 => 34, 20 => 42, 30 => 49),//4~7天
            "168" => array(10 => 30, 20 => 35, 30 => 45)
        );
        if (isset($priceArray[$hourKey]) && isset($priceArray[$hourKey][$writerLevel])) {
            $price = $priceArray[$hourKey][$writerLevel];
        }
        return $price;
    }

    /**
     * 订单科目对应是文章修改的计算价格
     * @param $hour
     * @param int $writerLevel
     * @return int
     */
    public function estimatedPriceByEditing($hour, $writerLevel = 10)
    {
        $price = 0;
        $hourKey = $this->getHour($hour);
        if (empty($hourKey)) {
            return $price;
        }
        $priceArray = array(
            "3_12" => array(10 => 25, 20 => 28, 30 => 40),
            "12_24" => array(10 => 23, 20 => 25, 30 => 35),
            "24_72" => array(10 => 18, 20 => 20, 30 => 30),//1天~3天
            "72_168" => array(10 => 15, 20 => 17, 30 => 25),//4~7天
            "168" => array(10 => 9.99, 20 => 15, 30 => 20)
        );
        if (isset($priceArray[$hourKey]) && isset($priceArray[$hourKey][$writerLevel])) {
            $price = $priceArray[$hourKey][$writerLevel];
        }
        return $price;
    }

    /**
     * 根据订单类型的等级id获取价格,1基础,2中等,3超难度
     * @param $hour
     * @param int $orderTypeLevelId
     * @return int
     */
    public function estimatedPriceOrderTypeLevelId($hour, $orderTypeLevelId = 1)
    {
        $price = 0;
        $hourKey = $this->getHour($hour);
        if (empty($hourKey)) {
            return $price;
        }
        $priceArray = array(
            "3_12" => array(1 => 54, 2 => 57, 3 => 57),
            "12_24" => array(1 => 52, 2 => 55, 3 => 55),
            "24_72" => array(1 => 50, 2 => 52, 3 => 53),//1天~3天
            "72_168" => array(1 => 44, 2 => 46, 3 => 46),//4~7天
            "168" => array(1 => 40, 2 => 42, 3 => 44)
        );
        if (isset($priceArray[$hourKey]) && isset($priceArray[$hourKey][$orderTypeLevelId])) {
            $price = $priceArray[$hourKey][$orderTypeLevelId];
        }
        return $price;
    }

    /**
     * 订单价格的估算
     * @param $endTime                      截止日期
     * @param int $orderPageId 订单页数id
     * @param int $orderResourcesId 订单资源数id
     * @param int $orderTypeId 订单类型id
     * @param int $orderRowSpaceIngId 订单行距id
     * @param int $writerLevelId 写手等级id
     * @return float|int
     */
    public function orderEstimatedPrice($endTime, $orderPageId = 0, $orderResourcesId = 0, $orderTypeId = 0, $orderRowSpaceIngId = 4, $writerLevelId = 0)
    {
        //获取需要的页数，资源，订单类型
        $orderPageInfo = $orderResourcesInfo = $orderTypInfo = $orderRowInfo = array();
        $resourceArray = array('OrderPage' => 'orderPageId', 'OrderResources' => 'orderResourcesId', "OrderRow" => "orderRowSpaceIngId");
        if (!empty($orderTypeId)) {
            $resourceArray['OrderType'] = 'orderTypeId';
        }
        $orderModel = model("Order");
        foreach ($resourceArray as $key => $value) {
            if (!empty($$value)) {
                $a_key = lcfirst($key) . "Info";
                $$a_key = $orderModel->getAllResource($key, $$value);
            }
        }
        $orderPage = isset($orderPageInfo['title']) ? trim($orderPageInfo['title']) : 1;//计算页数
        $resourcesNum = isset($orderResourcesInfo['title']) ? intval(trim($orderResourcesInfo['title'])) : 0; //计算资源数
        $hour = ($endTime - time()) / 3600;//进行截止日期的总共的小时数
        $orderTypeLevelId = isset($orderTypInfo['level_id']) ? intval(trim($orderTypInfo['level_id'])) : 0;
        if (empty($writerLevelId) && empty($orderTypeLevelId)) {
            $priceType = 1;
            $writerLevelId = 10;
        }
        if (!empty($writerLevelId)) {
            if ($orderTypeId == 47 || $orderTypeId == model("order.OrderType")->getOrderTypeId("Editing")) {
                //根据写手的等级值按算基础价格（文章修改的计算方式）
                $price = $this->estimatedPriceByEditing($hour, $writerLevelId);
            } else {
                //根据写手的等级值按算基础价格（优先写手等级）
                $price = $this->estimatedPriceWriterLevel($hour, $writerLevelId);
            }
            $priceType = 1;
        } elseif (!empty($orderTypeLevelId)) {
            //根据订单类型去计算基础价格（3.0版本前使用）
            $price = $this->estimatedPriceOrderTypeLevelId($hour, $orderTypeLevelId); //计算出每页的单价
            $priceType = 0;
        }
        $orderRowSpaceIng = isset($orderRowInfo['id']) ? intval(trim($orderRowInfo['id'])) : 4; //根据行距
        switch ($orderRowSpaceIng) {
            //Single spaced
            case 1 :
                $coefficient = $priceType == 0 ? round($orderPage * 1.6, 1) : $orderPage / 0.6;//递增的系数,四舍五入
                $price = round(floatval($coefficient * $price), 2);
                break;
            //1.5 spaced
            case 2 :
                $coefficient = $priceType == 0 ? round($orderPage * 1.3, 1) : $orderPage / 0.8; //递增的系数，四舍五入
                $price = round(floatval($coefficient * $price), 2);
                break;
            //double spaced
            case 4 :
                $coefficient = $priceType == 0 ? round($orderPage, 1) : $orderPage / 1; //递增的系数，四舍五入
                $price = round(floatval($coefficient * $price), 2);
                break;
        }
        if ($priceType == 0) {
            $zeroPointFivePrice = floatval($price / 2); // 0.5 页的价格
            $pagesSize = 0.5;
            $price = floatval(ceil($orderPage / $pagesSize) * $zeroPointFivePrice); //计算Double spaced的价格//计算页数*价格
        }
        if ($resourcesNum > 0 && $resourcesNum > 4) { //资源数>4个的时候，每增加一个资源+6;
            $resourcesNum = $resourcesNum - 4;
            $price += $resourcesNum * 6;
        }
        return $price;
    }

    /**
     * 获取订单的时间
     */
    public function getOrderTimeFormat()
    {
        $time = time();
        $hourList = array(3, 6, 12, 24, 48, 72, 96, 120, 168, 240, 336, 480);
        $week = array("日", "一", "二", "三", "四", "五", "六");
        $timeArray = array();
        foreach ($hourList as $value) {
            $second = $value * 3600;
            $timestamp = $time + $second;
            $weekTitle="星期".$week[date('w',$timestamp)];
            $timeFormat=$this->lang=='en' ? " D, M d" : " ".$weekTitle.", n月 d";
            $format=getFormatTime($timestamp,'','',$timeFormat);
            if ($value<24){
                $title = $value." hours / ".$format;
            }else{
                $days=$value/24;
                $title = $days." days / ".$format;
            }
            $timeArray[$value] = array('t' => $value, 'timestamp' => $timestamp, 'title' => $title);
        }
        return $timeArray;
    }

}