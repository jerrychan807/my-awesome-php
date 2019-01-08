<?php
namespace fastphp;

// 框架根目录
//use function PHPSTORM_META\elementType;

defined('CORE_PATH') or define('CORE_PATH', __DIR__);

/*
 * fastphp框架核心
 */

class Fastphp
{
    // 配置内容
    protected $config = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    // 运行程序
    public function run()
    {
        spl_autoload_register(array($this, 'loadClass'));
        $this->setReporting();
        $this->removeMagicQuotes();
        $this->unregisterGlobals();
        $this->setDbConfig();
        $this->route();

    }

    // 路由处理
    public function route()
    {
        $controllerName = $this->config['defaultController'];
        $actionName = $this->config['defaultAction'];
        $param = array();

        $url = $_SERVER['REQUEST_URI'];
        // 清除?之后的内容
        $position = strpos($url, '?');
        $url = $position === false ? $url : substr($url, 0, $position); // 这里的语法不懂

        // 删除前后的"/"
        $url = trim($url, '/');

        if ($url){
            // 使用"/"分割字符串, 并保存在数组中
            $urlArray = explode('/', $url);
            // 删除空的数组元素
            $urlArray = array_filter($urlArray);

            // 获取控制器名
            $controllerName = ucfirst($urlArray[0]); // ucfirst()使字符串第一个字母大写

            // 获取动作名
            array_shift($urlArray); // array_shift() 去掉第一个元素
            $actionName = $urlArray ? $urlArray[0] : $actionName;

            // 获取URL参数
            array_shift($urlArray);
            $param = $urlArray ? $urlArray : array();

        }

        // 判断控制器和操作是否存在
        $controller = 'app\\controllers\\' . $controllerName . 'Controller';
        if (!class_exists($controller)){
            exit($controller . '控制器不存在');
        }
        if (!method_exists($controller, $actionName)){
            exit($actionName . '方法不存在');
        }

        // 如果控制器和操作名存在, 则实例化控制器, 因为控制器对象里面
        // 还会用到控制器名和操作名, 所以实例化的时候把题目俩的名称也
        // 传进去。 结合Controller基类一起看
        $dispatch = new $controller($controllerName, $actionName); // 实例化

        // $dispatch保存控制器实例化后的对象, 我们就可以调用它的方法.
        // 也可以像方法中传入参数, 以下等同于: $dispatch->$actionName($param)
        call_user_func_array(array($dispatch, $actionName), $param);
    }

    // 检测开发环境
    public function setReporting(){
        if (APP_DEBUG === true){  // === $a === $b	Identical	TRUE if $a is equal to $b, and they are of the same type.
            error_reporting(E_ALL); // Report all PHP errors
            ini_set('display_errors', 'On');
        }else{
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
            ini_set('log_errors', 'On');
        }
    }

    // 删除敏感字符
    public function stripSlashesDeep($value){
        $value = is_array($value) ? array_map(array($this, 'stripSlashesDeep'), $value) : stripslashes($value);
        // is_array(): Finds whether a variable is an array
        // array_map(): Applies the callback to the elements of the given arrays 所有$value都依次丢进stripSlashesDeep函数里处理
        // stripslashes() 删除反斜杠
        // ? : 三元运算符 ,如果是一个数组的话,丢给stripSlashesDeep 处理,否则直接丢给stripslashes处理
        // 这里有个递归调用
        return $value;
    }

    // 检测敏感字符并删除
    public function removeMagicQuotes(){

        if (get_magic_quotes_gpc()){ // get_magic_quotes_gpc — Gets the current configuration setting of magic_quotes_gpc
            $_GET = isset($_GET) ? $this->stripSlashesDeep($_GET) : '';
            $_POST = isset($_POST) ? $this->stripSlashesDeep($_POST ) : '';
            $_COOKIE = isset($_COOKIE) ? $this->stripSlashesDeep($_COOKIE) : '';
            $_SESSION = isset($_SESSION) ? $this->stripSlashesDeep($_SESSION) : '';
            /*
             * PHP programmer 若是從 PHP v4 或甚至更早的版本開始寫，一定對 magic_quotes_gpc 這個設定不會陌生。
             * 早期的 PHP 為了防止 user 端送到 server 端的資料，會被惡意內容攻擊，有 SQL injection 的疑慮，因此很體貼地設計一個這樣的開關。
             * 當 magic_quotes_gpc=on 時，$_GET、$_POST、$_COOKIE 等等從 user 端來的資料，如果含有單引號、雙引號、反斜線等內容，
             * 會自動被加一條反斜線在前面，把該字元跳脫掉，也就是做 addslashes() 的處理，
             * 以免生手 programmer 直接就把資料串在 SQL 指令上，導致系統沒兩天就被駭客爆台。
             * 在我自己剛使用 PHP 的前一兩年，老實說這個設定可能在暗中已經默默幫我擋掉不少 SQL injection 攻擊了。
             *
             * 但是，正如 C 語言的問世宗旨，優秀的語言不該替 programmer 顧慮太多旁支末節。
             * 當防範 SQL injection 已經成為 Web Programming 的基礎 ABC 之後，
             * 多了這個手續反而讓 PHP 的程式有移植困難、還得去偵測執行環境的 magic_quotes_gpc 是 on 還是 off，
             * 再判斷需不需要 addslashes()，根本是浪費時間的步驟。
             * 於是，PHP 從預設 magic_quotes_gpc = on，變成預設 magic_quotes_gpc = off，再到 PHP 5.3 版之後，宣告拿掉了這個功能。
             */
        }
    }

    // 检测自定义全局变量并移除. 因为 register_globals 已经弃用,如果
    // 已经弃用的 register_globals 指令被设置为on, 那么局部变量也将
    // 在脚本的全局作用域中可用。 例如, $_POST['foo'] 也将以$foo 的
    // 形式存在, 这样写是不好的实现,会影响代码中的其他变量 相关信息
    public function unregisterGlobals(){

        if (ini_get('register_globals')){
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            foreach ($array as $value){
                foreach ($GLOBALS[$value] as $key => $var){
                    if ($var === $GLOBALS[$value]){
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }

    // 配置数据库信息
    public function setDbConfig(){

        if ($this->config['db']){
            define('DB_HOST', $this->config['db']['host']);
            define('DB_NAME', $this->config['db']['dbname']);
            define('DB_USER', $this->config['db']['username']);
            define('DB_PASS', $this->config['db']['password']);
        }
    }

    // 自动加载类
    public function loadClass($className){

        $classMap = $this->classMap();

        if (isset($classMap[$className])){
            // 包含内核文件
            $file = $classMap[$className];
        }elseif (strpos($className, '\\') !== false) {
            // 包含应用(Application目录) 文件
            $file = APP_PATH . str_replace('\\', '/', $className) . '.php';
            if (!is_file($file)){
                return;
            }
        }else{
            return;
        }

        include $file;

        // 这里可以加入判断，如果名为$className的类、接口或者性状不存在，则在调试模式下抛出错误
    }

    // 内核文件命名空间映射关系
    protected function classMap(){
        return [
            'fastphp\base\Controller' => CORE_PATH . '/base/Controller.php',
            'fastphp\base\Model' => CORE_PATH . '/base/Model.php',
            'fastphp\base\View' => CORE_PATH . '/base/View.php',
            'fastphp\db\Db' => CORE_PATH . '/db/Db.php',
            'fastphp\db\Sql' => CORE_PATH . '/db/Sql.php',
        ];
    }
}