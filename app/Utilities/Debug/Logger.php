<?php

namespace KarsonJo\Utilities\Debug {
    class Logger
    {

        private array $logs;

        /**
         * @var (null|StopWatch)[]
         */
        private array $contexts;
        public function registerContext(string $context, bool $timed = true)
        {
            $this->contexts[$context] = $timed ? new StopWatch() : null;
        }

        public function resetContexts()
        {
            foreach ($this->contexts as $context)
                if ($context instanceof StopWatch)
                    $context->reset();
        }

        public function addLog($group, $context, $title, $message, $formatTemplate = "[%s] %s")
        {
            if (!isset($this->contexts[$context]))
                $this->registerContext($context, false);

            $log = sprintf($formatTemplate, $title, $message);
            $timer = $this->contexts[$context];

            $this->logs[$group][] = $timer instanceof StopWatch ? $log . ": {$timer->getElapsedStringAndReset()}" : $log;
        }

        public function getLog()
        {
            return $this->logs;
        }
    }
}
