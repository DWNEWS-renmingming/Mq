<?php
require_once "./vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
/**
 * Rabbit Message Queue
 *
 * 消息队列管理器
 * @author: lion
 * @email: lihe@imheixiu.com
 */
class RabbitMQ {
    
    // 实例资源
    private $connection = NULL;
    private $_config    = array();
    private $_channel_name    = '';
    
    /**
     * RabbitMQ constructor.
     */
    public function __construct() {
        $this->_config = array(
            'host' => '120.78.199.238',
            'port' => '5672',
            'login' => 'guest',
            'password' => 'guest',
            'vhost'=>'/'
        );
		// var_dump($this->_config);
        $this->initialization();
    }
    /**
     * 实例化MQ
     */
    private function initialization() {
        if ($this->connection == NULL) {
            $this->connection = new AMQPStreamConnection(
                $this->_config['host'], $this->_config['port'], $this->_config['login'], $this->_config['password']
            );
        }
    }
    
    /**
     * 发送数据到 MQ
     *
     * @param string $channel_name 渠道ID的名称
     * @param string $data json数据
     *
     */
    public function send($channel_name, $data = "") {
        $channel = $this->connection->channel();

        $channel->queue_declare($channel_name, false, true, false, false);

        $msg = new AMQPMessage($data);

        $channel->basic_publish($msg, '', $channel_name);

        echo " [x] Sent ", $data, "\n";

        $channel->close();
        $this->connection->close();
    }

    /**
     * 批量发送数据到 MQ
     *
     * @param string $channel_name 渠道ID的名称
     * @param string $data json数据
     *
     */
    public function batchSend($channel_name, $data = ""){
        $channel = $this->connection->channel();

        $channel->queue_declare($channel_name, false, true, false, false);

        $msg = new AMQPMessage($data);

        $channel->basic_publish($msg, '', $channel_name);

        echo " [x] Sent ", $data, "\n";


    }

    /**
     * 关闭连接
     * @param $channel_name
     */
    public function close($channel_name){
        $channel = $this->connection->channel();

        $channel->queue_declare($channel_name, false, true, false, false);
        $channel->close();
        $this->connection->close();
    }

        /**
     * 接收数据，请求RabbitMQ 接口
     *
     * @param string $channel_name 渠道ID的名称
     * @param  callable $callback 回调函数
     */
    public function receive($channel_name, callable $callback) {
        $channel = $this->connection->channel();
		$this->_channel_name = $channel_name;
		$this->log('Listent:'.$this->_config['host'].' '. $this->_config['port'].' '. $channel_name);
		$this->log('Date  :'.date('Y-m-d H:i:s'));
		
		try{
            $channel->queue_declare($channel_name, false, true, false, false);
            #第四个参数 no_ack = false 时，表示进行ack应答，确保消息已经处理
            #$callback 表示回调函数，传入消息参数
			$channel->basic_consume(
				$channel_name,
				'',
				false,
				false,
				false,
				false,
				function($req) use ($callback,$channel,$channel_name){
					$this->log(date('Y-m-d H:i:s').'-Receive:'.$req->body);
					call_user_func($callback,$req);
					$channel->basic_ack($req->delivery_info['delivery_tag']);
				}
			);
			
			while(count($channel->callbacks)) {
				$channel->wait();
			}
			$channel->close();
			$this->connection->close();
        }catch(Exception $e){ 
			echo PHP_EOL . date('Y-m-d H:i:s').'-Error:'.$e->getMessage();
			$this->log(date('Y-m-d H:i:s').'-Error:'.$e->getMessage());
		}
    }
    public function log($str){
		$str = PHP_EOL .$str;
		// echo $str;
		
		$base_path = './RabbitMQLog/';
		$date = date('Y-m-d');
		$dir = $base_path.$date;
		if(!is_dir($dir)){
			$this->makeDir($dir);
		}
		$file_name = $date.$this->_channel_name;
		file_put_contents($dir.'/'.$file_name.'.log',$str,FILE_APPEND);
    }
    
    public function makeDir($path) {
        $path = str_replace(array('/', '\\', '//', '\\\\'), DIRECTORY_SEPARATOR, $path);
        $dirs = explode(DIRECTORY_SEPARATOR, $path);
        $tmp = '';
        foreach ($dirs as $dir) {
            $tmp .= $dir . DIRECTORY_SEPARATOR;
            if (!file_exists($tmp) && !mkdir($tmp, 0777)) {

                return $tmp;
            }
        }
        return true;
    }
}
