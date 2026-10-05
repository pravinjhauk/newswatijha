<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
/** Removes anonymous disclosure routes that a practice website does not need. */
final class Hardening {
    public static function boot(): void {
        add_filter('xmlrpc_enabled','__return_false');
        add_filter('xmlrpc_methods',static fn()=>[]);
        add_filter('wp_headers',static function($headers){ unset($headers['X-Pingback']); return $headers; });
        remove_action('wp_head','rsd_link');
        // Staff login names must not be listed in sitemaps, the REST API, author archives or oEmbed data.
        add_filter('wp_sitemaps_add_provider',static fn($provider,$name)=>$name==='users'?false:$provider,10,2);
        add_filter('rest_pre_dispatch',[self::class,'users_endpoint'],10,3);
        add_action('template_redirect',[self::class,'author_archives'],1);
        add_filter('oembed_response_data',static function($data){ unset($data['author_name'],$data['author_url']); return $data; });
    }
    public static function users_endpoint($result,$server,\WP_REST_Request $request) {
        if(str_starts_with($request->get_route(),'/wp/v2/users') && !is_user_logged_in()) return new \WP_Error('rest_user_cannot_view','Authentication is required.',['status'=>401]);
        return $result;
    }
    public static function author_archives(): void {
        if(!is_author()) return;
        global $wp_query; $wp_query->set_404(); status_header(404); nocache_headers();
    }
}
