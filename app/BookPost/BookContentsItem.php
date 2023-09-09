<?php

namespace KarsonJo\BookPost {
    class BookContentsItem
    {
        public $ID;
        public $post_title;
        public $post_parent;


        /**
         * @param WP_Post $WP_Post
         */
        public function __construct($WP_Post)
        {
            $this->ID = $WP_Post->ID;
            $this->post_title = $WP_Post->post_title;
            $this->post_parent = $WP_Post->post_parent;
        }
    }
}
