<?php

namespace KarsonJo\BookPost\Route {

    use Exception;
    use Throwable;
    use WP_Error;

    abstract class RestResource
    {
        /**
         * 结点的名称，如果不是root结点，应该始终给一个名称
         * @var string
         */
        protected string $name;
        /**
         * ID的匹配模式
         * @var string
         */
        protected static string $idPattern = '';
        /**
         * @var RestResource[]
         */
        protected array $subResources = [];
        /**
         * @var callable[]
         */
        protected array $permissions = [];

        protected function __construct()
        {
        }

        public static function create(string $name): static
        {
            $instance = new static();
            $instance->name = $name;
            // $instance->idPattern = $idPattern;
            return $instance;
        }

        protected function addRoutes(string $namespace, string $basePath = '', array $permissions = []): void
        {
            // print_r(get_class($this) . ": $namespace,$basePath,$this->name\n");

            /**
             * 注册当前资源
             */
            if (!empty($this->name))
                $basePath = "$basePath/$this->name";
            $permissions = array_merge($permissions, $this->permissions);

            $this->registerRoutes($namespace, $basePath, $permissions);
            /**
             * 注册子资源
             */
            foreach ($this->subResources as $subResource)
                // $subResource->addRoutes($namespace, $basePath. $this->name . $this->idPattern);
                $subResource->addRoutes($namespace, static::pathWithIdPattern($basePath), $permissions);
        }

        protected function getRoutes(string $namespace, string $basePath = '')
        {
            $result = [];
            if (!empty($this->name)) {
                $basePath = "$this->name";
                // $result[$this->getIdentifier()] = [
                //     'segment' => $basePath,
                //     'single' => empty(static::$idPattern),
                //     // 'identifier' => 
                // ];
                $result['segment'] = $basePath;
                $result['single'] = empty(static::$idPattern);
            }
            foreach ($this->subResources as $subResource)
                // $result = array_merge($result, $subResource->getRoutes($namespace, static::pathWithIdPattern($basePath)));
                $result[$subResource->getIdentifier()] = $subResource->getRoutes($namespace, static::pathWithIdPattern($basePath));
            return $result;
        }

        protected abstract function getIdentifier();

        /**
         * 获取第一层的资源
         * @return void 
         */
        // protected function getEntries()
        // {
        //     $result = [];
        //     foreach ($this->subResources as $subResource)
        //         $result[] = [
        //             'href' = $subResource->name
        //         ]
        // }

        /**
         * 增加一个下级资源
         * @param RestResource $resource 
         * @return $this 
         */
        public function addSubResource(RestResource $resource)
        {
            $this->subResources[] = $resource;
            return $this;
        }

        /**
         * 增加一个继承的权限
         * @param callable $permission 
         * @return $this 
         */
        public function addPermission(callable $permission)
        {
            $this->permissions[] = $permission;
            return $this;
        }

        /**
         * 注册当前资源的处理逻辑
         * @param mixed $namespace api的名称空间
         * @param mixed $path 检索当前资源集合的完整path
         * @return mixed 
         */
        protected abstract function registerRoutes(string $namespace, string $path, array $permissions);

        // ============helpers==============


        protected static function pathWithIdPattern($basePath)
        {
            return empty(static::$idPattern) ? $basePath : $basePath . '/' . static::$idPattern;
        }

        protected static function getErrorMessage(WP_Error|Throwable|array $error): array
        {
            if ($error instanceof WP_Error)
                return ['code' => $error->get_error_code(), 'message' => $error->get_error_message()];
            if ($error instanceof Throwable)
                return ['code' => $error->getCode(), 'message' => $error->getMessage()];
            return ['code' => $error[0] ?? '', 'message' => $error[1] ?? ''];
        }

        /**
         * @param callable[] $permissions 
         * @return callable
         */
        protected static function permissionCallback(array $permissions): callable
        {
            return function () use ($permissions) {
                foreach ($permissions as $permission)
                    if (!$permission())
                        return false;
                return true;
            };
        }
    }
}
