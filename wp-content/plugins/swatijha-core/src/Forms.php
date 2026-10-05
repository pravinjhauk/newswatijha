<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
final class Forms {
    public static function boot(): void {
        add_action('admin_post_sj_enquiry',[self::class,'submit']); add_action('admin_post_nopriv_sj_enquiry',[self::class,'submit']);
    }
    public static function enabled(): bool { return wp_get_environment_type()==='production' && (bool)apply_filters('sj_enquiry_transport_ready',false); }
    public static function render(): string {
        if(!self::enabled()) return '<aside class="sj-notice"><h2>Contact the practice</h2><p>Online enquiry delivery is not enabled in this preview.</p></aside>';
        $id=wp_unique_id('sj-enquiry-'); $time=time(); $token=hash_hmac('sha256',(string)$time,wp_salt('nonce'));
        ob_start(); ?>
        <form class="sj-enquiry" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
          <h2>Enquire about a consultation</h2><p>Please do not include sensitive medical information. This form is not for urgent help.</p>
          <input type="hidden" name="action" value="sj_enquiry"><input type="hidden" name="started" value="<?php echo esc_attr($time); ?>"><input type="hidden" name="token" value="<?php echo esc_attr($token); ?>">
          <?php wp_nonce_field('sj_enquiry'); ?>
          <p><label for="<?php echo esc_attr($id); ?>name">Name (required)</label><input id="<?php echo esc_attr($id); ?>name" name="name" required maxlength="120" autocomplete="name"></p>
          <p><label for="<?php echo esc_attr($id); ?>email">Email (required)</label><input id="<?php echo esc_attr($id); ?>email" name="email" type="email" required maxlength="254" autocomplete="email"></p>
          <p><label for="<?php echo esc_attr($id); ?>message">Enquiry (required)</label><textarea id="<?php echo esc_attr($id); ?>message" name="message" required maxlength="2000" rows="5"></textarea></p>
          <div hidden><label>Leave blank<input name="website" tabindex="-1" autocomplete="off"></label></div>
          <p><label><input type="checkbox" name="consent" value="1" required> I have read the <a href="<?php echo esc_url(get_privacy_policy_url()); ?>">privacy notice</a> and agree to be contacted about my enquiry.</label></p>
          <button type="submit">Send enquiry</button>
        </form><?php return ob_get_clean();
    }
    public static function validate(array $data) {
        if(!self::enabled()) return new \WP_Error('disabled','Online enquiries are not enabled.');
        $started=(int)($data['started']??0); $elapsed=time()-$started;
        if(!wp_verify_nonce($data['_wpnonce']??'','sj_enquiry')||!hash_equals(hash_hmac('sha256',(string)$started,wp_salt('nonce')),(string)($data['token']??''))||$elapsed<5||$elapsed>DAY_IN_SECONDS||!empty($data['website'])) return new \WP_Error('invalid','Please reload the form and try again.');
        if(empty($data['name'])||strlen($data['name'])>120) return new \WP_Error('name','Enter your name using no more than 120 characters.');
        if(!is_email($data['email']??'')) return new \WP_Error('email','Enter a valid email address.');
        if(empty($data['message'])||strlen($data['message'])>2000) return new \WP_Error('message','Enter an enquiry using no more than 2000 characters.');
        if(empty($data['consent'])) return new \WP_Error('consent','Confirm that you have read the privacy notice and agree to be contacted.');
        return ['name'=>sanitize_text_field($data['name']),'email'=>sanitize_email($data['email']),'message'=>sanitize_textarea_field($data['message'])];
    }
    public static function submit(): void {
        $validated=self::validate(wp_unslash($_POST));
        if(is_wp_error($validated)) wp_die(esc_html($validated->get_error_message()),'Enquiry not sent',['response'=>400,'back_link'=>true]);
        $key='sj_rate_'.hash_hmac('sha256',($_SERVER['REMOTE_ADDR']??'unknown'),wp_salt()); $count=(int)get_transient($key);
        if($count>=3) wp_die('Please try again later.','Enquiry not sent',['response'=>429,'back_link'=>true]);
        set_transient($key,$count+1,HOUR_IN_SECONDS);
        $sent=apply_filters('sj_deliver_enquiry',false,$validated);
        if($sent!==true) wp_die('Your enquiry could not be sent. Please use the practice contact details.','Enquiry not sent',['response'=>503,'back_link'=>true]);
        wp_die('Your enquiry has been sent. This does not confirm an appointment.','Enquiry sent',['response'=>200,'back_link'=>true]);
    }
}
