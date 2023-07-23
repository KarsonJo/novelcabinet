<?php
namespace NovelCabinet\Utility;
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