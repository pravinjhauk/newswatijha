<?php
namespace SwatiJha;
defined('ABSPATH') || exit;
final class Blocks {
    public const BLOCKS = ['entity-cards'=>'Clinical cards','credentials'=>'Credentials','treatment-options'=>'Treatment options','clinician-profile'=>'Clinician profile','contributors'=>'Faculty and contributors','publications'=>'Publications','research'=>'Research','resources'=>'Educational resources','medical-review'=>'Medical review','references'=>'References','reviews'=>'Patient reviews','locations'=>'Clinic locations','enquiry-form'=>'Enquiry form','practice-contact'=>'Practice contact'];
    public static function boot(): void { add_action('init',[self::class,'register']); }
    public static function register(): void {
        $asset=is_file(SJ_CORE_PATH.'/build/editor.asset.php')?require SJ_CORE_PATH.'/build/editor.asset.php':['dependencies'=>[],'version'=>'0.1.0'];
        wp_register_script('sj-editor',plugins_url('build/editor.js',SJ_CORE_FILE),$asset['dependencies'],$asset['version'],true);
        wp_register_style('sj-blocks',plugins_url('build/blocks.css',SJ_CORE_FILE),[], '0.2.0');
        foreach(self::BLOCKS as $name=>$title) register_block_type(SJ_CORE_PATH.'/blocks/'.$name,['render_callback'=>static fn($attrs,$content,$block)=>self::render($name,$attrs,$block)]);
    }
    public static function render(string $name,array $attrs,$block=null): string {
        $ids=array_values(array_filter(array_map('intval',$attrs['entityIds']??[]),[Model::class,'verified']));
        $page=(int)($block->context['postId']??get_the_ID());
        $heading=$attrs['heading']??''; $out=$heading?'<h2>'.esc_html($heading).'</h2>':'';
        if($name==='enquiry-form') return Forms::render();
        if($name==='practice-contact') {
            $settings=Graph::settings();
            if(!empty($settings['telephone'])) $out.='<a href="tel:'.esc_attr(preg_replace('/[^+0-9]/','',$settings['telephone'])).'">'.esc_html($settings['telephone']).'</a>';
            if(!empty($settings['email'])) $out.='<a href="mailto:'.esc_attr($settings['email']).'">'.esc_html($settings['email']).'</a>';
            return '<div class="sj-contact">'.$out.'</div>';
        }
        if($name==='medical-review') {
            $reviewer=(int)Model::get($page,'reviewer_entity_id');
            if(!Model::verified($reviewer)||Model::get($page,'review_state')!=='approved') return '';
            return '<aside class="sj-medical-review" aria-label="Medical review">Reviewed by '.esc_html(get_the_title($reviewer)).' · <time datetime="'.esc_attr(Model::get($page,'medically_reviewed_on')).'">'.esc_html(Model::get($page,'medically_reviewed_on')).'</time></aside>';
        }
        if($name==='references') {
            $ids=array_values(array_filter((array)Model::get($page,'reference_ids',[]),[Model::class,'verified'])); $out.='<ol>';
            foreach($ids as $id) $out.='<li><a href="'.esc_url(Model::get($id,'source_url')).'">'.esc_html(get_the_title($id)).'</a></li>';
            return $ids?'<section class="sj-references">'.$out.'</ol></section>':'';
        }
        if($name==='credentials') {
            $out.='<ul class="sj-credentials">';
            foreach($ids as $id) if(get_post_type($id)==='sj_clinician') foreach((array)Model::get($id,'credentials',[]) as $credential) if(($credential['verification']??'')==='verified') $out.='<li>'.esc_html($credential['title']).'<span>'.esc_html($credential['organisation']??'').'</span></li>';
            return '<section>'.$out.'</ul></section>';
        }
        $types=['treatment-options'=>['sj_treatment'],'clinician-profile'=>['sj_clinician'],'contributors'=>['sj_clinician'],'publications'=>['sj_publication'],'research'=>['sj_research'],'resources'=>['sj_resource'],'reviews'=>['sj_review'],'locations'=>['sj_location']];
        $out.='<div class="sj-card-grid">';
        foreach($ids as $id) {
            $type=get_post_type($id); if(isset($types[$name])&&!in_array($type,$types[$name],true)) continue;
            if($type==='sj_review'&&(Model::get($id,'permission')!=='confirmed'||Model::get($id,'moderation')!=='approved')) continue;
            $out.='<article class="sj-card">';
            if($type==='sj_clinician' && $image=(int)Model::get($id,'portrait_id')) $out.=wp_get_attachment_image($image,'medium',false,['class'=>'sj-profile-image']);
            $out.='<h3>'.esc_html(get_the_title($id)).'</h3>';
            if($summary=Model::get($id,'summary')) $out.='<p>'.esc_html($summary).'</p>';
            if($type==='sj_review') $out.='<blockquote><p>'.esc_html(Model::get($id,'excerpt')).'</p><cite>'.esc_html(Model::get($id,'display_name')).'</cite></blockquote>';
            if($type==='sj_location') {
                $address=array_filter(array_map(static fn($field)=>Model::get($id,$field),['street','locality','region','postcode'])); $out.='<address>'.esc_html(implode(', ',$address)).'</address>';
                if(Model::get($id,'telephone')) $out.='<p><a href="tel:'.esc_attr(preg_replace('/[^+0-9]/','',Model::get($id,'telephone'))).'">'.esc_html(Model::get($id,'telephone')).'</a></p>';
            }
            if($type==='sj_publication') $out.='<p>'.esc_html(Model::get($id,'authors')).'</p><p>'.esc_html(Model::get($id,'publisher')).' '.esc_html(Model::get($id,'published_on')).'</p>';
            $landing=(int)Model::get($id,'landing_page_id'); $url=$landing&&get_post_status($landing)==='publish'?get_permalink($landing):'';
            if(!$url&&in_array($type,['sj_publication','sj_reference','sj_review','sj_research'],true)) $url=Model::get($id,'source_url');
            if($type==='sj_resource') $url=wp_get_attachment_url((int)Model::get($id,'attachment_id'))?:Model::get($id,'resource_url');
            if($type==='sj_location'&&!$url) $url=Model::get($id,'directions_url');
            if($url) $out.='<a class="sj-card-link" href="'.esc_url($url).'">'.esc_html($type==='sj_resource'?'Open resource':'Read more').'<span class="screen-reader-text"> about '.esc_html(get_the_title($id)).'</span> <span aria-hidden="true">→</span></a>';
            if($type==='sj_resource'&&Model::get($id,'transcript')) $out.='<details><summary>Transcript</summary><p>'.nl2br(esc_html(Model::get($id,'transcript'))).'</p></details>';
            $out.='</article>';
        }
        return '<section class="sj-entity-section">'.$out.'</div></section>';
    }
}
