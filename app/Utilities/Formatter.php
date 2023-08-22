<?php

namespace NovelCabinet\Utilities {
    class Formatter {
        static function humanLookNumber(int $number): string
        {
            $units = array(1_000, 10_000, 100_000_000);
            $names = array('千', '万', '亿');
        
            $res_num = $number;
            $res_unit = '';
        
            for ($i = 0; $i < count($units); $i++) {
                $curr = $number / $units[$i];
                if ($curr < 1)
                    break;
                $res_num = $curr;
                $res_unit = $names[$i];
            }
        
            // todo: translate
            return sprintf('%s', round($res_num, 1) . $res_unit);
            // return round($res_num, 1) . $res_unit;
        }
        
    }
}