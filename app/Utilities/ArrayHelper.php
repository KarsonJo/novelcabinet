<?php

namespace NovelCabinet\Utilities {
    class ArrayHelper
    {
        static function arrayValuesEqual(array $a, array $b): bool
        {
            return is_array($a) && is_array($b) && count($a) == count($b) && array_diff($a, $b) === array_diff($b, $a);
        }

        /**
         * 根据列获取最小值
         * @param array $data 
         * @param callable $keySelector 
         * @param mixed $minimum 提前返回的最小值 
         * @return array 二元组[minItem, minValue]
         */
        static function minBy(array $data, callable $keySelector, $minimum = null)
        {
            $minValueItem = null;
            $minValue = null;
            foreach ($data as $item) {
                $value = $keySelector($item);

                if (!isset($minValue) || $value < $minValue) {
                    $minValue = $value;
                    $minValueItem = $item;

                    /**
                     * 已达最小值
                     */
                    if (isset($minimum) && $minValue <= $minimum)
                        break;
                }
            }
            return [$minValueItem, $minValue];
        }

        /**
         * 根据列获取最大值
         * @param array $data 
         * @param callable $keySelector 
         * @param mixed $maximum 提前返回的最大值
         * @return array 二元组[maxItem, maxValue]
         */
        static function maxBy(array $data, callable $keySelector, $maximum = null)
        {
            $maxValueItem = null;
            $maxValue = null;
            foreach ($data as $item) {
                $value = $keySelector($item);

                if (!isset($maxValue) || $value > $maxValue) {
                    $maxValue = $value;
                    $maxValueItem = $item;

                    /**
                     * 已达最大值
                     */
                    if (isset($maximum) && $maxValue >= $maximum)
                        break;
                }
            }
            return [$maxValueItem, $maxValue];
        }
    }
}
