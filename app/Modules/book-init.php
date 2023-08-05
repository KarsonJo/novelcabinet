<?php

/**
 * 首次加载主题时调用
 */

namespace KarsonJo\BookPost;


function create_db_table($table_name, $sql)
{
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    global $wpdb;
    $sql = sprintf($sql, $wpdb->prefix . $table_name, $wpdb->get_charset_collate());

    dbDelta($sql);
}

function book_database_init()
{
    // wp_kbp_postmeta
    $sql = "CREATE TABLE %s (
		post_id bigint(20) unsigned NOT NULL,
		rating_weight int(11) unsigned DEFAULT 0 NOT NULL,
		rating_avg double DEFAULT 0 NOT NULL,
        word_count int(11) unsigned DEFAULT 0 NOT NULL,
		PRIMARY KEY  (post_id),
        KEY idx_id_rating_word (post_id, rating_avg, word_count),
        KEY idx_rating_avg (rating_avg)
	) %s;";
    create_db_table('kbp_postmeta', $sql);

    // wp_kbp_rating
    $sql = "CREATE TABLE %s (
        post_id bigint(20) unsigned NOT NULL,
        user_id bigint(20) unsigned NOT NULL,
        weight int(11) NOT NULL,
        rating float NOT NULL,
        time datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (user_id, post_id),
        KEY idx_time (time)
    ) %s;";
    create_db_table('kbp_rating', $sql);

    // wp_kbp_favorite_lists
    $sql = "CREATE TABLE %s (
        ID int(11) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        list_title varchar(255) NOT NULL,
        visibility tinyint(1) unsigned NOT NULL,
        time datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (ID),
        UNIQUE idx_user_id_title (user_id, list_title)
    ) %s;";
    create_db_table('kbp_favorite_lists', $sql);

    // wp_kbp_favorite_relationships
    $sql = "CREATE TABLE %s (
        list_id int(11) unsigned NOT NULL,
        post_id bigint(20) unsigned NOT NULL,
        PRIMARY KEY  (list_id, post_id)
    ) %s;";
    create_db_table('kbp_favorite_relationships', $sql);
}

// register_activation_hook(__FILE__, 'create_the_custom_table');
add_action("after_switch_theme", "KarsonJo\\BookPost\\book_database_init");
