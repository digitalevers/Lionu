<?php
/**
 * 模型基类
 */
namespace App\Models;


class BaseModel extends \CodeIgniter\Model
{
    /**
     * 对日期进行校验
     *
     * 不同的媒体渠道对查询日期有不同的要求
     * 例如 360的查询日期跨度不能超过90天
     *     百度的查询日期跨度不能超过730天
     */
    protected function checkDate($startDate, $endDate, $days = 90){
        //今日起始时间戳
        $todayStartTimestamp = strtotime(date('Y-m-d',time()));
        //查询起始日期起始时间戳
        $startDateStartTimestamp = strtotime(date('Y-m-d', strtotime($startDate)));
        //查询结束日期起始时间戳
        $endDateStartTimestamp = strtotime(date('Y-m-d', strtotime($endDate)));
        if(($startDateStartTimestamp > $endDateStartTimestamp) || ($startDateStartTimestamp > $todayStartTimestamp) || ($endDateStartTimestamp > $todayStartTimestamp)){
            _json(['code'=>199,'msg'=>'查询日期错误,不能选择超过当天的时间'], 1);
        }
        if($todayStartTimestamp - $startDateStartTimestamp > $days * 24 * 3600){
            _json(['code'=>198,'msg'=>'查询跨度不能超过'.$days.'天'], 1);
        }
        return ['todayST'=>$todayStartTimestamp,'startST'=>$startDateStartTimestamp,'endST'=>$endDateStartTimestamp];
    }

    /**
     * TODO 批量插入或更新数据
     * 判断是否插入新数据还是更新数据
     * 如果数据未发生更改 则什么都不做
     * @param unknown $insertGroup
     * @param unknown $oldAdvGroups
     *
     * return code == 2 插入
     *        code == 1 更新
     *        code == 0 什么都不做
     */
    protected function insertOrUpdateOrNothing($insertReport, &$oldReport, $dimension = 'media_creativeid'){
        do{
            $decide = [];
            if(!isset($oldReport[$insertReport[$dimension]][$insertReport['sdate']])){
                //dump($oldReport);
                //exit;
                $decide = ['code'=>2,'update'=>[]];
                break;
            }
            
            $update = [];
            $compare = $oldReport[$insertReport[$dimension]][$insertReport['sdate']];
            foreach ($insertReport as $k=>$v){
                //慎用  !== 比较   数据库类型为int 但PDO驱动查询会全部转为string类型
                //此外数据库字段类型为json的话 会自动伸缩json结构 使得无法使用 != 操作符来进行字符比对 解决方案 数据表采用string字段类型
                if(in_array($k, $this->needUpdateFields) && ($v != $compare[$k])){
                    //dump($compare);
                    //exit;
                    $update[$k] = $v;
                }
            }
            //dump($update);
            //dump($compare);
            if(count($update) > 0){
                $update['id'] = $compare['id'];
                $decide = ['code'=>1,'update'=>$update];
                break;
            } else {
                $decide = ['code'=>0,'update'=>[]];
                break;
            }
        } while(false);
        
        //根据decide决定是插入数据库还是更新数据库
        if($decide['code'] == 2){
            $insertID = $this->insert($insertReport, true);
            if($insertID){
                $insertReport['id'] = $insertID;
                $oldReport[$insertReport[$dimension]][$insertReport['sdate']] = $insertReport;
            } else {
                //TODO -2写入失败 记录日志
                return -$decide['code'];
            }
        } elseif($decide['code'] == 1){
            $updateRes = $this->save($decide['update']);
            if($updateRes > 0){
                $insertReport['id'] = $decide['update']['id'];
                $oldReport[$insertReport[$dimension]][$insertReport['sdate']] = $insertReport;
            } else {
                //TODO -1 更新失败 记录日志
                return -$decide['code'];
            }
        }
        return $decide['code'];
    }
}