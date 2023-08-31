<?php

namespace KarsonJo\Utilities\Debug {
    class StopWatch
    {
        private $startTime;

        public function __construct(bool $autoStart = true)
        {
            if ($autoStart)
                $this->reset();
        }
        public function reset()
        {
            $this->startTime = hrtime(true);
        }

        public function getElapsed()
        {
            $elapsedTime = hrtime(true) - $this->startTime;
            return $elapsedTime;
        }

        public function getElapsedStringAndReset()
        {
            $elapsedTime = $this->getElapsed();
            $this->reset();
            // return $this->formatElapsed($elapsedTime);
            return $this->formatElapsedMs($elapsedTime);
        }

        public function formatElapsed($elapsedTime)
        {
            $elapsedMs = round($elapsedTime / 1000000, 2); // 毫秒
            $elapsedS = round($elapsedTime / 1000000000, 2); // 秒
            $elapsedNs = number_format($elapsedTime); // 纳秒

            return "({$elapsedS} s, {$elapsedMs} ms, {$elapsedNs} ns)";
        }

        public function formatElapsedMs($elapsedTime)
        {
            $elapsedMs = round($elapsedTime / 1000000, 2); // 毫秒

            return "({$elapsedMs} ms)";
        }
    }
}
