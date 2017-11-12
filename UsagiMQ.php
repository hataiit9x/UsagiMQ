<?php

/**
 * Class UsagiMQ
 * @author Jorge Castro C. MIT License.
 * @version 1.0 2017-11-12
 * @link https://www.google.cl
 */
class UsagiMQ
{
    /** @var Redis $redis */
    public $redis;
    /** @var  bool $connected */
    public $connected=false; // false if redis is not connected. true if its connected.

    const MAXTIMEKEEP=3600*24; // max time in seconds to keep the information,-1 for unlimited.
    const MAXPOST=1024*1024*20; // 20mb

    public function __construct($redisIP,$redisPort,$redisDB=0)
    {
        if (!class_exists("Redis")) {
            echo "this software required Redis https://pecl.php.net/package/redis";
            die(1);
        }
        $this->redis = new Redis();
        $ok=@$this->redis->connect($redisIP, $redisPort, 5); // 5 sec timeout.
        if (!$ok) {
            $this->redis=null;
            return;
        }
        @$this->redis->select($redisDB);
        $this->connected=true;
    }

    /**
     * receive a new envelope via post http://myserver/mq.php?id=<Customer>&op=<operation>&from=<AndroidApp> (and post info)
     * @return string OKI if the operation was successful, otherwise it returns the error.
     */
    public function receive() {
        try {
            $counter = $this->redis->incr('counterUsagiMQ');
            $post = file_get_contents('php://input');
            $id = @$_GET['id'];
            $op = @$_GET['op']; // operation.
            $from = @$_GET['from']; // security if any (optional)

            if (empty($post) || empty($op) || empty($id)) {
                return "NO INFO";
            }
            if (strlen($id)>1000 || strlen($op)>1000 || strlen($from)>1000 || strlen($post)>self::MAXPOST) {
                // avoid overflow.
                return "BAD INFO";
            }
            $envelope = array();
            $envelope['id'] = $id;
            $envelope['from'] = $from; // it could be used for security
            $envelope['body'] = $post;
            $envelope['date'] = time();
            $envelope['try'] = 0; // use future.
            $ok = $this->redis->set("UsagiMQ_{$op}:" . $counter, json_encode($envelope), self::MAXTIMEKEEP); // 24 hours
            if ($ok) {
                return "OKI";
            }
            return "error in receive";
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * List of all id of envelop pending.
     * @param $op
     * @return array
     */
    public function listPending($op) {
        $it = NULL;
        $redisKeys=array();
        while($arr_keys = $this->redis->scan($it, "UsagiMQ_{$op}:*", 1000)) { // 1000 read at the same time.
            foreach($arr_keys as $str_key) {
                $redisKeys[]=$str_key;
            }
        }
        return $redisKeys;
    }

    /**
     * @param $id
     * @return array Returns an array with the form of an envelope[id,from,body,date,try]
     */
    public function readItem($id) {
       return json_decode($this->redis->get($id), true);
    }

    /**
     * Delete an envelope
     * @param $op
     * @param $id
     */
    public function delete($op,$id) {
        $this->redis->delete("UsagiMQ_{$op}:".$id);
    }

    /**
     * Delete all envelope.
     */
    function deleteAll() {
        $it = NULL;
        while($arr_keys = $this->redis->scan($it, "UsagiMQ_*", 10000)) {
            foreach($arr_keys as $v) {
                $this->redis->delete($v);
            }
        }
        $this->redis->set('counterUsagiMQ',"0");
    }

    function close() {
        $this->redis->close();
    }

}