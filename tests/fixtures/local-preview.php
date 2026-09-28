<?php
require '/wordpress/wp-load.php';
wp_set_current_user(1);
$pattern=WP_Block_Patterns_Registry::get_instance()->get_registered('swatijha/hero');
$cta=WP_Block_Patterns_Registry::get_instance()->get_registered('swatijha/cta');
$content=$pattern['content'];
$content.='<!-- wp:group {"align":"wide","className":"sj-section","layout":{"type":"constrained"}} --><div class="wp-block-group alignwide sj-section"><!-- wp:heading --><h2 class="wp-block-heading">A local preview of your new website</h2><!-- /wp:heading --><!-- wp:paragraph --><p>The approved design is now a native WordPress block theme. Clinical content and existing pages will be migrated only after the URL map is approved.</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
$content.=$cta['content'];
$id=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Home','post_content'=>$content]);
update_option('show_on_front','page');update_option('page_on_front',$id);
// Local navigation examples are explicitly placeholders, not migrated content.
foreach(['about'=>'About Professor Jha','leaflets'=>'Patient information','contact'=>'Contact','book-consultation'=>'Book a consultation','privacy-notice'=>'Privacy notice','vaginal-prolapse-treatment-sheffield'=>'Prolapse care'] as $slug=>$title) wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_name'=>$slug,'post_title'=>$title,'post_content'=>'<!-- wp:paragraph --><p>Local layout preview. The existing approved content will be migrated after URL-map approval.</p><!-- /wp:paragraph -->'.($slug==='contact'?'<!-- wp:sj/enquiry-form /-->':'')]);
