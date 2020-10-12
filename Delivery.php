<?php

/**
 * 消息队列功能
 *https://blog.csdn.net/qq_26656329/article/details/75502468
 * @author: lion
 * @email: lihe@imheixiu.com
 */

class Delivery {

    CONST ANALYSIS_CATEGORY_LIVE  = "analysis_live";
    CONST ANALYSIS_CATEGORY_LOGIN = "analysis_login";
    CONST ANALYSIS_CATEGORY_TIME  = "analysis_time";
    CONST ANALYSIS_CATEGORY_ROOM  = "analysis_room";
    CONST ANALYSIS_CATEGORY_COMSUMPTION = "analysis_comsumption";
    const CHANNEL_NAME = 'XINGE_MSG';

    public $rabbitmq;
    /**
     * Index constructor.
     */
    public function __construct() {
        include "./RabbitMQ.php";
        $this->rabbitmq = new RabbitMQ();
    }

    /**
     * 观看直播用户数据-接收数据
     *
     */
    public  function get_data() {
        //消费队列（接收）
        $this->rabbitmq->receive(static::ANALYSIS_CATEGORY_LIVE, function ($msg) {
            if (empty($msg->body)) {
                return false;
            };
            $body = json_decode($msg->body, true);
            // file_put_contents('a.txt', print_r($body,true));
        });
    }

    public  function send_data() {
        $data = ['电视机','电脑','电冰箱','点饭锅'];
        $this->rabbitmq->send(static::ANALYSIS_CATEGORY_LIVE,json_encode($data,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 主播直播时长
     *
     */
    // public function live_time() {
    //     $this->load->model('rabbitmq/Delivery_model');
    //     $this->rabbitmq->receive(static::ANALYSIS_CATEGORY_TIME, function ($msg) {
    //         if (empty($msg->body)) {
    //             return false;
    //         };
    //         $body = json_decode($msg->body, true);
    //         $this->Delivery_model->live_time($body);
    //     });
    // }

    public  function send_data_all() {
        $num = 10;
        for($i=0;$i<$num;$i++){
            $list = 'demo'. $i;
            $this->rabbitmq->batchSend(static::CHANNEL_NAME,$list);
        }
        $this->rabbitmq->close(static::CHANNEL_NAME);
    }

    public  function get_data_all() {
        //消费队列（接收）
        $this->rabbitmq->receive(static::CHANNEL_NAME, function ($msg) {
            if (empty($msg->body)) {
                return false;
            };
            $body = json_decode($msg->body, true);
            file_put_contents('b.txt', print_r($body,true));
        });
    }

    // public function live_spectator() {
    //     $this->load->model('rabbitmq/Delivery_model');
    //     $callback = function ($msg) {
    //         if (empty($msg->body)) {
    //             return false;
    //         };
    //         $body = json_decode($msg->body, true);
    //         $this->Delivery_model->live_spectator($body);
    //         $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    //     };
    //     $this->rabbitmq->receive(static::ANALYSIS_CATEGORY_LIVE, $callback);
    // }

}
$a =  $argv[1];
$Delivery =  new Delivery();
if( $a == 1) {
    $Delivery->send_data();
} else if($a == 2) {
    $Delivery->get_data();
} else if($a == 3) {
    $Delivery->send_data_all();
} else if($a == 4) {
    $Delivery->get_data_all();
}