<?php
/**
 * Created by PhpStorm.
 * User: alice
 * Date: 2017/11/30
 * Time: 下午4:12
 */

namespace backend\controllers;


use common\base\NoCsrf;
use yii\web\Controller;

class WeChatController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => NoCsrf::className(),
                'controller' => $this,
                'actions' => [
                    'index',
                ],
            ],
        ];
    }

    public function actionIndex(){
        //获得参数 signature nonce token timestamp echostr
        $nonce     = $_GET['nonce'];
        $token     = 'alice';
        $timestamp = $_GET['timestamp'];
        $signature = $_GET['signature'];
        //形成数组，然后按字典序排序
        $array = array($nonce, $timestamp, $token);
        sort($array);
        //拼接成字符串,sha1加密 ，然后与signature进行校验
        $str = sha1( implode( $array ) );
        if( $str  == $signature && isset($_GET['echostr']) ){
            //第一次接入weixin api接口的时候
            echo  $_GET['echostr'];
            exit;
        }else{
            $this->responseMsg();
        }
    }

    // 接收事件推送并回复
    public function responseMsg()
    {
        /*<xml>
            <ToUserName><![CDATA[toUser]]></ToUserName>
            <FromUserName><![CDATA[fromUser]]></FromUserName>
            <CreateTime>12345678</CreateTime>
            <MsgType><![CDATA[text]]></MsgType>
            <Content><![CDATA[你好]]></Content>
         </xml>*/
        //1.获取到微信推送过来post数据（xml格式）
        $postArr = $GLOBALS['HTTP_RAW_POST_DATA'];
        error_log(var_export($postArr,1));
        //2.处理消息类型，并设置回复类型和内容
        $postObj = simplexml_load_string($postArr);
        error_log(var_export($postObj,1));
        //判断该数据包是否是订阅的事件推送
        if (strtolower($postObj->MsgType) == 'event') {
            //如果是关注 subscribe 事件
            if (strtolower($postObj->Event == 'subscribe')) {
                //回复用户消息(纯文本格式)
                $toUser = $postObj->FromUserName;
                $fromUser = $postObj->ToUserName;
                $time = time();
                $msgType = 'text';
                $content = '欢迎关注我们的微信公众账号' . $postObj->FromUserName . '-' . $postObj->ToUserName;
                $template = "<xml>
							<ToUserName><![CDATA[%s]]></ToUserName>
							<FromUserName><![CDATA[%s]]></FromUserName>
							<CreateTime>%s</CreateTime>
							<MsgType><![CDATA[%s]]></MsgType>
							<Content><![CDATA[%s]]></Content>
							</xml>";
                $info = sprintf($template, $toUser, $fromUser, $time, $msgType, $content);
                echo $info;
            }
        }
    }

}