<?php
/**
 * Logger config class
 */
class TXLogger
{
    private static $_instance = null;

    public static function instance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public static $ConsoleOut = array();

    /**
     * 计算内存消耗
     * @param $size
     * @return string
     */
    private static function convert($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }

    /**
     * 事件触发写sql
     * @param $e
     * @param $sql
     */
    public function event($e, $sql)
    {
        $this->logger($sql, $e, "info");
    }

    /**
     * @param $message
     * @param $key
     * @param string $level
     */
    protected function logger($message, $key, $level="info")
    {
        self::$ConsoleOut[] = array('value' => $message, 'key' => $key, 'type' => $level);
    }

    public static function log($message, $key="phpLogs")
    {
        self::instance()->logger($message, $key, "log");
    }

    public static function memory($key="memory")
    {
        self::instance()->logger(self::convert(memory_get_usage()), $key, "warn");
    }

    public static function time($key="time")
    {
        self::instance()->logger(microtime(true), $key, "warn");
    }

    public static function info($message, $key="phpLogs")
    {
        self::instance()->logger($message, $key, "info");
    }

    public static function warn($message, $key="phpLogs")
    {
        self::instance()->logger($message, $key, "warn");
    }

    public static function error($message, $key="phpLogs")
    {
        self::instance()->logger($message, $key, "error");
    }

    public static function display($message)
    {
        echo '<pre>';
        $message = (is_object($message) && method_exists($message, '__toLogger')) ? $message->__toLogger() : $message;
        print_r($message);
        echo '</pre>';
    }

    /**
     * 获取实例
     * @param $obj
     * @return array
     */
    private static function object_to_array($obj)
    {
        $arr = [];
        $class = new ReflectionClass($obj);
        $properties = $class->getProperties();
        foreach ($properties as $propertie){
            $value = $propertie->isPrivate() ? ":private" :
                ($propertie->isProtected() ? ":protected" :
                    ($propertie->isPublic() ? ":public" : ""));
            $arr[$propertie->getName()] = $value;
        }
        return [$class->getName() => $arr];
    }

    /**
     * 格式化输出项
     */
    public static function format()
    {
        foreach (self::$ConsoleOut as &$Out){
            $value = $Out['value'];
            if (is_object($value)){
                if (method_exists($value, '__toLogger')){
                    $value = $value->__toLogger();
                } else {
                    $value = self::object_to_array($value);
                }
            } elseif ($value === null){
                $value = 'NULL';
            } else if (is_bool($value)){
                $value = $value ? "true" : "false";
            }
            $Out['value'] = $value;
        }
        unset($Out);
    }


    /**
     * 返回所有日志
     */
    public static function showLogs(){
        if (SYS_CONSOLE && self::$ConsoleOut){
            self::format();
            echo "\n<script type=\"text/javascript\">\n";
            foreach (self::$ConsoleOut as $Out){
                $value = $Out['value'];
                $key = $Out['key'];
                $type = $Out['type'];
                if (is_array($value)){
                    $value = json_encode($value);
                    $message = sprintf('console.%s("%s => ", %s);', $type, $key, $value ?: "false");
                } else {
                    $message = sprintf('console.%s("%s => ", "%s");', $type, $key, addslashes(str_replace(array("\r\n", "\r", "\n"), "", $value)));
                }
                echo $message."\n";
            }
            echo "</script>";
            self::$ConsoleOut = array();
        }
    }

    /**
     * 析构函数
     */
    public function __destruct(){
        if (TXApp::$base->request && !TXApp::$base->request->isAjax){
            self::showLogs();
        }
    }

    /**
     * 记录错误日志
     * @param $message
     */
    public static function addError($message){
        if (is_array($message) || is_object($message)){
            $message = var_export($message, true);
        }
        $header = sprintf("%s:%s [%s]", date('Y-m-d H:i:s'), substr(microtime(), 2, 3), TXApp::$base->request->getUserIp());
        $user = TXApp::$base->person ? TXApp::$base->person->getPk() : "guest";
        $message = $header."\n".$message."\nuser: $user\n";
        $filename = sprintf("%s/error_%s.log", TXApp::$log_root, date('Y-m-d'));
        file_put_contents($filename, $message, FILE_APPEND);
    }

    /**
     *记录日志
     * @param $message
     */
    public static function addLog($message){
        if (is_array($message) || is_object($message)){
            $message = var_export($message, true);
        }
        $header = sprintf("%s:%s [%s]", date('Y-m-d H:i:s'), substr(microtime(), 2, 3), TXApp::$base->request->getUserIp());
        $user = TXApp::$base->person ? TXApp::$base->person->getPk() : "guest";
        $message = $header."\n".$message."\nuser: $user\n";
        $filename = sprintf("%s/log_%s.log", TXApp::$log_root, date('Y-m-d'));
        file_put_contents($filename, $message, FILE_APPEND);
    }
}