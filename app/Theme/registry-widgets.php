<?php
namespace NovelCabinet;

add_action('widgets_init',function()
{
    register_sidebar([
        'name'=>'Index Body 1',
        'id'=>'index-body1'
    ]);
});