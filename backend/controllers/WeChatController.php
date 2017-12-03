<?php
/**
 * Created by PhpStorm.
 * User: alice
 * Date: 2017/11/30
 * Time: 下午4:12
 */

namespace backend\controllers;


use common\base\NoCsrf;
use common\form\WecharUserForm;
use yii\web\Controller;

class WeChatController extends Controller
{
    private $postXml;   //接收微信传入的xml

    private $postObj;   //接收xml转换成的对象

    private $myId;  //微信公众号ID    gh_24696e54a9b1

    private $openId; //访问人openID    og8XAwhR970_A1BkxAt7uB-3LT8Y

    private $createTime;  //请求建立时间

    private $msgType;   //消息类型

    private $encrypt;

    private $event; //事件类型

    private $eventKey;

    private $content;

    private $msgId;

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

    public function beforeAction($action)
    {
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
            /*<xml>
                <ToUserName><![CDATA[gh_24696e54a9b1]]></ToUserName>
                <FromUserName><![CDATA[og8XAwhR970_A1BkxAt7uB-3LT8Y]]></FromUserName>
                <CreateTime>1512204708</CreateTime>
                <MsgType><![CDATA[event]]></MsgType>
                <Event><![CDATA[subscribe]]></Event>
                <EventKey><![CDATA[]]></EventKey>
                <Encrypt><![CDATA[UoMUMP3WKooFFFz6iWF85R/8QL7wXyQb3HWclamiMOJb2gIZHWrqQS8E4hd//eqhqh9m+rSE72w5UlR3uYsHI5M9aXqNT0NyoEEgb1krnqYih1EP05HlfyQjMOcQ7YUB3icYZ91CmwiKNl8KmMWuQhP8xu3MSxK+8tN9jU0mfL0GrVCisky936Qaerw8dh60AcfMXZEEqmX4q3J7dNVj5c3Lnj7YoERvKEZ6NxQq/iUrODwWkbdFT7iraRioU7VNzPPS/dUF1c0t7CfeENmcDdjHGcqROre/mJzQXvv9YzeAHI1y7C/Bg63YLBunSo5mZSSwsqEQpHz1KPY05FrjIDjlDA+tJbDxmafIXf4NqoT4S48+IjLNSa1VZjWWzu2uGcmOnzo3x5Zxm3j/zHgaZRg7CuP8F3/rXsGTC3xSBdE=]]></Encrypt>
            </xml>*/
            /*<xml>
                <ToUserName><![CDATA[gh_24696e54a9b1]]></ToUserName>
                <FromUserName><![CDATA[og8XAwhR970_A1BkxAt7uB-3LT8Y]]></FromUserName>
                <CreateTime>1512309591</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[2396]]></Content>
                <MsgId>6495320235194850802</MsgId>
                <Encrypt><![CDATA[wElOgQyeKTx/raG0Y+wSnFlazE5EEfXo//0H8sTkP+SFry7jR6BUxDaC+OMCx2bn7NYct4gXPu8PZi1rPnRXQfa4z282IxfOFlcBfplAfwCAv1eru7c7AH+cBTrFkgcu5CSP81mlhBAfoDC3zLZDr5zEY3KiGqxntymHSQ9ZaDW2YZcVqXWB4MLzlrjzuZfpLBhxCesBNdM+XRLcKf2h7m0DTR3fBE+4YtwRnlYPlYhTQrRtkMq2SecZvcJ5tRjeEJ7HOtez0Ja8tcwfd0Z8SI0Iay5hHE2gkPrIYAyPioeT5r7lEtWixwSd83oK7i6G0mg+LMz2+kq7MIx0tTJVROvQj3fU92c7Y7kpKmgE2O4BHaEjODq1xDJB7Tq7T0B7QOFe6ORZ2WUaeIJQUAFlp4q01mOu30Mjr3kc7Suo+u4=]]></Encrypt>
            </xml>*/
            //1.获取到微信推送过来post数据
            $this->postXml = file_get_contents("php://input");
            error_log(print_r($this->postXml,1));
            //2.处理消息类型，并设置回复类型和内容
            $this->postObj = simplexml_load_string($this->postXml);

            $this->myId = $this->postObj->ToUserName;
            $this->openId = $this->postObj->FromUserName;
            $this->createTime = $this->postObj->CreateTime;
            $this->msgType = strtolower($this->postObj->MsgType);
            $this->encrypt = $this->postObj->Encrypt;
            //事件
            $this->event = strtolower($this->postObj->Event);
            $this->eventKey = $this->postObj->EventKey;
            //文本消息
            $this->content = $this->postObj->Content;
            $this->msgId = $this->postObj->MsgId;

            return parent::beforeAction($action); // TODO: Change the autogenerated stub
        }
    }

    public function actionIndex(){
        error_log('index');
        if ($this->msgType == 'event' && $this->event == 'subscribe') {
            //事件，并且是关注事件
            WecharUserForm::createWechatUser($this->openId, $this->createTime);
            $this->subscribe();
        }
        if ($this->msgType == 'text') {
            //文本消息
            $this->msg();
        }
    }

    //关注事件回复
    public function subscribe()
    {
        error_log('subscribe');
        $msgType = 'text';
        $content = '欢迎关注';
        $this->response($msgType, $content);
    }

    //消息回复
    public function msg()
    {
        error_log('msg');
        $msgType = 'text';
        $content = '谢谢您的留言，竹夭在吃提拉米苏，会尽快回复您！';
        $this->response($msgType, $content);

    }

    //构建响应消息格式
    public function response($msgType, $content)
    {
        error_log('response');
        //回复用户消息(纯文本格式)
        $toUser = $this->openId;
        $fromUser = $this->myId;
        $time = time();
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