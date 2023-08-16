<?php

namespace KarsonJo\BookPost;
//==================================
// 维护书本的最后更新时间
/**
 * 阻止修改元数据时更新书的最后更新时间
 * https://wordpress.stackexchange.com/questions/237878/how-to-prevent-wordpress-from-updating-the-modified-time
 * https://brogramo.com/how-to-update-a-wordpress-post-without-updating-the-modified-date-using-wp_update_post/
 */
function prevent_updating_modified_date($data, $postarr)
{

    // 只干预Book的父文章
    if ($postarr['post_type'] != KBP_BOOK || $postarr['post_parent'] != 0)
        return $data;

    // return if the modified date is not set
    // this happens in revisions and can heppen in other post types
    if (!isset($postarr['post_modified']) || !isset($postarr['post_modified_gmt']))
        return $data;

    // 将修改时间改回缺省值
    $data['post_modified'] = $postarr['post_modified'];
    $data['post_modified_gmt']  = $postarr['post_modified_gmt'];

    // write_log($data);
    // write_log($postarr);

    return $data;
}
add_filter('wp_insert_post_data', 'KarsonJo\\BookPost\\prevent_updating_modified_date', 1, 2);

// $postarr = [];
// $postarr['ID']         = 17; // post ID being updated
// $postarr['ababa'] = 'ababa'; // array of tags (Or it can be something like changing the post's status)
// $postarr['post_modified'] = "1970-1-1 00:00:00";
// $postarr['post_modified_gmt'] = "1970-1-1 00:00:00";

// update post
// $update_post = wp_update_post($postarr);
