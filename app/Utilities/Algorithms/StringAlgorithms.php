<?php


namespace KarsonJo\Utilities\Algorithms {
    class StringAlgorithms
    {
        /**
         * 带阈值的levenshtein编辑距离算法
         * 超过阈值时提前返回
         * @param string $s1 
         * @param string $s2 
         * @param int $maximum 
         * @param mixed $failedValue 失败时返回的数值 
         * @return mixed 
         */
        static function levenshteinWithThreshold(string $s1, string $s2, int $maximum, $failedValue = false): mixed
        {
            if (strlen($s1) > strlen($s2)) {
                $tmp = $s1;
                $s1 = $s2;
                $s2 = $tmp;
            }

            $distances = range(0, strlen($s1));
            for ($i2 = 0; $i2 < strlen($s2); $i2++) {
                $distances_ = array($i2 + 1);

                for ($i1 = 0; $i1 < strlen($s1); $i1++) {

                    if ($s1[$i1] == $s2[$i2])
                        $distances_[] = $distances[$i1];
                    else
                        $distances_[] = 1 + min(array($distances[$i1], $distances[$i1 + 1], $distances_[$i1]));
                }
                $distances = $distances_;

                $allExceedThreshold = true;
                foreach ($distances as $distance) {
                    if ($distance <= $maximum) {
                        $allExceedThreshold = false;
                        break;
                    }
                }
                if ($allExceedThreshold)
                    return $failedValue;
            }
            return $distances[count($distances) - 1];
        }
    }
}
