<?php

namespace NovelCabinet\Utility;

use DateTime;

function js_asyncdefer_feature($tag, $handle)
{
    // if the unique handle/name of the registered script has 'async' in it
    if (strpos($handle, '-async') !== false) {
        // return the tag with the async attribute
        return str_replace('<script ', '<script async ', $tag);
    }
    // if the unique handle/name of the registered script has 'defer' in it
    else if (strpos($handle, '-defer') !== false) {
        // return the tag with the defer attribute
        return str_replace('<script ', '<script defer ', $tag); //js
    }
    // otherwise skip
    return $tag;
}

function css_defer_feature($tag, $handle)
{
    if (strpos($handle, '-defer') !== false) {
        return str_replace("media='all'", 'media="print" onload="this.media=\'all\';"', $tag);
    }
    return $tag;
}

if (!is_admin()) {
    add_filter('script_loader_tag', '\NovelCabinet\Utility\js_asyncdefer_feature', 10, 2);
    add_filter('style_loader_tag', '\NovelCabinet\Utility\css_defer_feature', 10, 2);
}


function human_look_number(int $number): string
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


    return round($res_num, 1) . $res_unit;
}


/**
 * home链接，并根据Permalink格式处理末尾斜杠
 */
function home_url_trailingslashit($path = ''): string
{
    return user_trailingslashit(home_url($path));
}

/**
 * 判断日期字符串是否为有效
 * 
 * https://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
 */
function validate_date($date, $format = 'Y-m-d')
{
    if (!$date) return false;
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}
