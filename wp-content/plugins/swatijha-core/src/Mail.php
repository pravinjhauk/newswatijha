<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
/**
 * Outside production no message leaves the site except, on staging, to one nominated address.
 * Every message is kept as an administrator-only capture so password resets and invitations still work.
 * Define SJ_STAGING_MAIL_TO in wp-config.php on staging to receive copies at that single address.
 */
final class Mail {
    public const TYPE = 'sj_mail_capture';
    public const RETENTION_DAYS = 14;
    private static bool $redirected = false;
    public static function boot(): void {
        if(wp_get_environment_type()==='production') return;
        add_action('init',[self::class,'register']);
        add_filter('wp_mail',[self::class,'route'],999);
        add_filter('pre_wp_mail',[self::class,'intercept'],99,2);
    }
    public static function register(): void {
        $caps=array_fill_keys(['edit_posts','edit_others_posts','edit_private_posts','edit_published_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','publish_posts'],'manage_options');
        $caps['create_posts']='do_not_allow';
        register_post_type(self::TYPE,['labels'=>['name'=>'Captured mail','singular_name'=>'Captured message'],'public'=>false,'show_ui'=>true,'show_in_menu'=>'sj-practice','show_in_rest'=>false,'rewrite'=>false,'query_var'=>false,'supports'=>['title','editor'],'capabilities'=>$caps,'map_meta_cap'=>true]);
    }
    public static function redirect_address(): string {
        return wp_get_environment_type()==='staging' && defined('SJ_STAGING_MAIL_TO') ? (string)sanitize_email(SJ_STAGING_MAIL_TO) : '';
    }
    public static function route(array $atts): array {
        self::$redirected=false;
        self::capture($atts);
        $to=self::redirect_address();
        if(!$to) return $atts;
        $original=implode(', ',(array)$atts['to']);
        $headers=array_filter((array)$atts['headers'],static fn($h)=>!preg_match('/^\s*(cc|bcc)\s*:/i',(string)$h));
        self::$redirected=true;
        return array_merge($atts,['to'=>$to,'headers'=>array_values($headers),'subject'=>'[Staging, for '.$original.'] '.$atts['subject']]);
    }
    public static function intercept($result,array $atts) {
        if(self::$redirected) { self::$redirected=false; return $result; }
        return true;
    }
    public static function capture(array $atts): int {
        $to=implode(', ',(array)$atts['to']);
        $body='To: '.$to."\n".'Headers: '.implode(' | ',(array)$atts['headers'])."\n\n".(string)$atts['message'];
        $id=(int)wp_insert_post(['post_type'=>self::TYPE,'post_status'=>'private','post_title'=>wp_strip_all_tags((string)$atts['subject']).' → '.$to,'post_content'=>esc_html($body)]);
        foreach(get_posts(['post_type'=>self::TYPE,'post_status'=>'any','numberposts'=>50,'fields'=>'ids','date_query'=>[['before'=>self::RETENTION_DAYS.' days ago']]]) as $old) wp_delete_post($old,true);
        return $id;
    }
}
