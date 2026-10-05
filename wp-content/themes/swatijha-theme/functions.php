<?php
namespace SwatiJhaTheme;
defined('ABSPATH') || exit;
add_action('after_setup_theme',static function(){
    add_theme_support('editor-styles');
    add_editor_style(['assets/fonts/fonts.css','assets/css/theme.css']);
    add_theme_support('responsive-embeds');
    add_theme_support('title-tag');
    remove_theme_support('core-block-patterns');
});
add_action('wp_enqueue_scripts',static function(){
    wp_enqueue_style('sj-fonts',get_theme_file_uri('assets/fonts/fonts.css'),[], '0.2.0');
    wp_enqueue_style('sj-theme',get_theme_file_uri('assets/css/theme.css'),['sj-fonts'], '0.2.0');
});
add_action('init',static function(){
    register_block_pattern_category('swatijha',['label'=>'Swati Jha']);
    register_block_style('core/button',['name'=>'secondary','label'=>'Secondary']);
    register_block_style('core/group',['name'=>'card','label'=>'Clinical card']);
    register_block_style('core/group',['name'=>'notice','label'=>'Notice']);
});
add_filter('render_block_core/image',static function($html,$block){
    if(!str_contains($block['attrs']['className']??'','sj-portrait')) return $html;
    $processor=new \WP_HTML_Tag_Processor($html);
    if($processor->next_tag('IMG') && str_contains($processor->get_attribute('src')??'','/swatijha-theme/assets/images/professor-swati-jha.jpg')) {
        $processor->set_attribute('src',get_theme_file_uri('assets/images/professor-swati-jha-453.webp'));
        $processor->set_attribute('srcset',get_theme_file_uri('assets/images/professor-swati-jha-320.webp').' 320w, '.get_theme_file_uri('assets/images/professor-swati-jha-453.webp').' 453w');
        $processor->set_attribute('sizes','(max-width: 479px) calc(100vw - 40px), (max-width: 1023px) 420px, 453px');
        $processor->set_attribute('width','453');
        $processor->set_attribute('height','600');
        $processor->set_attribute('fetchpriority','high');
        $processor->set_attribute('loading','eager');
        $processor->set_attribute('decoding','async');
    }
    return $processor->get_updated_html();
},10,2);

add_action('wp_head',static function(){
    foreach(['lora-latin.woff2','plus-jakarta-sans-latin.woff2'] as $font) echo '<link rel="preload" href="'.esc_url(get_theme_file_uri('assets/fonts/'.$font)).'" as="font" type="font/woff2" crossorigin>'."\n";
},2);
