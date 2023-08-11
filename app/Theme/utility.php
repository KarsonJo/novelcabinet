<?php

namespace NovelCabinet\Utility;

use DateTime;

// ==================== javascript ====================
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

/**
 * @param string $object_name javascript对象名，请确保是合法的
 * @param string[] $data 将序列化的数据
 * @param ?string $handle wp_enqueue_script的句柄，为空则根据主题与对象名生成
 * @param bool $in_footer 是否加在footer
 * @param
 */
function enqueue_script_data(string $object_name, array $data, ?string $handle = null, bool $in_footer = false)
{
    if (!preg_match('/^[a-zA-Z_$][0-9a-zA-Z_$]*$/', $object_name))
        return;

    if (!$handle)
        $handle = get_option('stylesheet') . '-' . $object_name;

    // https://wordpress.stackexchange.com/questions/298762/wp-add-inline-script-without-dependency
    // https://wordpress.stackexchange.com/questions/60546/use-wp-localize-script-for-non-existing-script
    wp_register_script($handle, '', [], '', $in_footer);
    wp_enqueue_script($handle);
    wp_add_inline_script($handle, "const $object_name = " . json_encode($data));
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
