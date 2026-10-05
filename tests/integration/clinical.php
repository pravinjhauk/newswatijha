<?php
require '/wordpress/wp-load.php';
use SwatiJha\Model;
use SwatiJha\Editorial;
use SwatiJha\Graph;
use SwatiJha\Migration;
use SwatiJha\Forms;
use SwatiJha\Admin;
use SwatiJha\Mail;
wp_set_current_user(1);
// Workflow runs as separate, real, non-administrator accounts.
function person($login,$role){ return wp_insert_user(['user_login'=>$login,'user_pass'=>wp_generate_password(32),'role'=>$role,'display_name'=>ucfirst(str_replace('_',' ',$login))]); }
$ed=person('fixture_content_editor','sj_content_editor'); $rv=person('fixture_reviewer','sj_clinical_reviewer'); $pb=person('fixture_publisher','sj_publisher');
function as_user($user,$fn){ $previous=get_current_user_id(); wp_set_current_user($user); try { return $fn(); } finally { wp_set_current_user($previous); } }
$tests=[];
function check($name,$condition,$detail='') { global $tests; $tests[]=['name'=>$name,'passed'=>(bool)$condition,'detail'=>$condition?'':$detail]; }
function good($result){return !is_wp_error($result);}
function entity($type,$title) { $id=wp_insert_post(['post_type'=>$type,'post_status'=>'draft','post_title'=>$title]); update_post_meta($id,'_sj_source_url','https://example.org/evidence');update_post_meta($id,'_sj_verified_on','2026-09-01');update_post_meta($id,'_sj_verification','verified');return $id; }
function release($id,$reviewer){ global $ed,$rv,$pb;
 $requested=as_user($ed,fn()=>Editorial::act($id,'request')); if(is_wp_error($requested)) return $requested;
 $approved=as_user($rv,fn()=>Editorial::act($id,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$reviewer])); if(is_wp_error($approved)) return $approved;
 return as_user($pb,fn()=>Editorial::act($id,'release')); }
function msg($r){ return is_wp_error($r)?$r->get_error_code().': '.$r->get_error_message():wp_json_encode($r); }
check('Theme is active',get_stylesheet()==='swatijha-theme');
check('All 10 entity types registered',count(array_filter(array_keys(Model::TYPES),'post_type_exists'))===10);
check('All dynamic blocks registered',count(array_filter(array_keys(SwatiJha\Blocks::BLOCKS),fn($name)=>WP_Block_Type_Registry::get_instance()->is_registered('sj/'.$name)))===14);
$clinician=entity('sj_clinician','Synthetic test clinician');
check('Stable UUID generated',wp_is_uuid(Model::get($clinician,'uuid')));
Admin::link_user($rv,$clinician);
$identity_log=get_option('sj_identity_log');check('Reviewer account linked to clinician and the link is logged',Editorial::linked_clinician($rv)===$clinician&&($identity_log[count($identity_log)-1]['clinician']??0)===$clinician);
$r=release($clinician,$clinician);check('Initial verified clinician can be approved and released',good($r),msg($r));
$condition=entity('sj_condition','Synthetic condition');$r=release($condition,$clinician);check('Entity release requires and accepts verified clinician',good($r),is_wp_error($r)?$r->get_error_message():'');
$clinical_fixture_id=wp_insert_post(['post_type'=>'page','post_title'=>'Clinical fixture','post_status'=>'draft','post_content'=>'<!-- wp:paragraph --><p>Approved original.</p><!-- /wp:paragraph -->']);
update_post_meta($clinical_fixture_id,'_sj_clinical',true);update_post_meta($clinical_fixture_id,'_sj_author_entity_ids',[$clinician]);
$r=Editorial::act($clinical_fixture_id,'release');check('Unreviewed page cannot be released',is_wp_error($r));
$r=release($clinical_fixture_id,$clinician);check('Reviewed page can be released',good($r),is_wp_error($r)?$r->get_error_message():'');
wp_update_post(['ID'=>$clinical_fixture_id,'post_content'=>'Unsafe direct overwrite']);check('Direct published content edit is blocked',str_contains(get_post($clinical_fixture_id)->post_content,'Approved original'));
update_post_meta($clinical_fixture_id,'_sj_clinical',false);check('Published clinical flag cannot bypass protection',(bool)Model::get($clinical_fixture_id,'clinical'));
check('Published clinical deletion is blocked',wp_trash_post($clinical_fixture_id)===false);
$change=Editorial::act($clinical_fixture_id,'change');check('Change draft created',good($change));$draft=$change['id']??0;
if($draft){
 wp_update_post(['ID'=>$draft,'post_content'=>'<!-- wp:paragraph --><p>Reviewed replacement.</p><!-- /wp:paragraph -->']);
 check('Draft edit leaves published content intact',str_contains(get_post($clinical_fixture_id)->post_content,'Approved original'));
 as_user($ed,fn()=>Editorial::act($draft,'request'));$r=as_user($rv,fn()=>Editorial::act($draft,'approve',['reviewed_on'=>'2099-01-01','reviewer_id'=>$clinician]));check('Future medical review date rejected',is_wp_error($r));
 $r=as_user($rv,fn()=>Editorial::act($draft,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician]));check('Draft approval succeeds',good($r),msg($r));
 wp_update_post(['ID'=>$draft,'post_content'=>'Modified after approval']);$r=as_user($pb,fn()=>Editorial::act($draft,'release'));check('Changed revision invalidates approval',is_wp_error($r));
 wp_update_post(['ID'=>$draft,'post_content'=>'<!-- wp:paragraph --><p>Reviewed replacement.</p><!-- /wp:paragraph -->']);$r=release($draft,$clinician);check('Approved replacement promoted',good($r),is_wp_error($r)?$r->get_error_message():'');
 check('Promotion retains original URL and page ID',str_contains(get_post($clinical_fixture_id)->post_content,'Reviewed replacement')&&get_post_status($clinical_fixture_id)==='publish');
}
$graph=Graph::graph($clinical_fixture_id);$encoded=wp_json_encode($graph);check('Graph generated from approved entities',str_contains($encoded,'MedicalWebPage')&&str_contains($encoded,'Synthetic test clinician'));
check('Graph has no localhost identifiers',!str_contains($encoded,'127.0.0.1')&&!str_contains($encoded,'localhost'));
check('No automatic review stars',!str_contains($encoded,'aggregateRating'));
check('Wrong relationship target rejected',is_wp_error(Model::validate($condition,'sj_condition','_sj_parent_id',$clinician)));
check('Unsafe source scheme rejected',is_wp_error(Model::validate($condition,'sj_condition','_sj_source_url','javascript:alert(1)')));
check('Unapproved URL map blocks migration',is_wp_error(Migration::run(['version'=>1,'records'=>[]])));
check('Enquiry disabled in local environment',!Forms::enabled()&&is_wp_error(Forms::validate([])));
$r=as_user($ed,fn()=>Editorial::act($clinical_fixture_id,'approve'));check('Content editor cannot approve clinical records',is_wp_error($r));
wp_set_current_user(0);$request=new WP_REST_Request('GET','/swatijha/v1/catalogue');$response=rest_do_request($request);check('Anonymous catalogue access denied',$response->get_status()===401||$response->get_status()===403);
$request=new WP_REST_Request('GET','/wp/v2/sj_clinician');$response=rest_do_request($request);check('Anonymous entity REST access denied',$response->get_status()===403);
$request=new WP_REST_Request('GET','/wp/v2/pages/'.$clinical_fixture_id);$response=rest_do_request($request);$json=wp_json_encode($response->get_data());check('Private editorial audit not in public REST',!str_contains($json,'_sj_audit')&&!str_contains($json,'_sj_approved_by'));
wp_set_current_user(1);
// Additional tests use synthetic records only; the real URL map remains untouched.
$clone=Editorial::act($condition,'change');
check('Entity can be revised without losing its UUID',good($clone)&&Model::get($clone['id'],'uuid')===Model::get($condition,'uuid'));
$term=wp_insert_term('Synthetic specialty','sj_specialty');
wp_set_object_terms($condition,[(int)$term['term_id']],'sj_specialty');
check('Published specialty changes cannot bypass review',count(wp_get_object_terms($condition,'sj_specialty'))===0);
$uuid=wp_generate_uuid4();
$manifest=['version'=>1,'approval'=>['approved_by'=>'Synthetic fixture only','approved_on'=>'2026-09-01'],'url_map'=>[['old_url'=>'https://example.org/synthetic','new_url'=>'https://example.org/synthetic','action'=>'KEEP','approval'=>'APPROVED']],'records'=>[['uuid'=>$uuid,'type'=>'sj_condition','title'=>'Synthetic imported draft','content'=>'<!-- wp:paragraph --><p>Fixture.</p><!-- /wp:paragraph -->','meta'=>['summary'=>'Synthetic test']]]];
$dry=Migration::run($manifest);check('Approved synthetic dry run proposes without writes',good($dry)&&$dry['creates']===1&&$dry['writes']===[]);
$import=Migration::run($manifest,false);check('Synthetic import creates drafts only',good($import));
$repeat=Migration::run($manifest,false);check('Synthetic import is idempotent',good($repeat)&&$repeat['creates']===0&&$repeat['updates']===1);
$bad=$manifest;$bad['records'][0]['uuid']=wp_generate_uuid4();$bad['records'][0]['type']='sj_treatment';$bad['records'][0]['meta']=['condition_ids'=>[wp_generate_uuid4()]];
$before=wp_count_posts('sj_treatment')->draft;$result=Migration::run($bad,false);
check('Unresolved import relationship rolls back',is_wp_error($result)&&wp_count_posts('sj_treatment')->draft===$before);
$all=new RecursiveIteratorIterator(new RecursiveDirectoryIterator('/wordpress/wp-content/plugins/swatijha-core'));
$syntax=true;foreach($all as $file) if($file->isFile()&&$file->getExtension()==='php'){try{token_get_all(file_get_contents($file->getPathname()),TOKEN_PARSE);}catch(Throwable $error){$syntax=false;}}
check('All plugin PHP parses on PHP 8.3',$syntax);
$revision=(int)Model::get($clinical_fixture_id,'approved_revision_id');
check('Approved revision identifies the published narrative',$revision>0&&get_post($revision)->post_content===get_post($clinical_fixture_id)->post_content,wp_json_encode(['revision'=>$revision,'enabled'=>wp_revisions_enabled(get_post($clinical_fixture_id)),'constant'=>defined('WP_POST_REVISIONS')?WP_POST_REVISIONS:'unset','content'=>$revision?get_post($revision)->post_content:'none','expected'=>get_post($clinical_fixture_id)->post_content]));
$notes=new WP_REST_Request('POST','/swatijha/v1/notes/'.$clone['id']);$notes->set_param('notes','Synthetic private evidence');$notes_result=rest_do_request($notes);check('Private evidence can be saved on a change draft',$notes_result->get_status()===200);
wp_set_current_user(0);$notes_read=rest_do_request(new WP_REST_Request('GET','/swatijha/v1/notes/'.$clone['id']));check('Anonymous private evidence access denied',$notes_read->get_status()===401||$notes_read->get_status()===403);wp_set_current_user(1);
$create_request=new WP_REST_Request('POST','/wp/v2/sj_treatment');$create_request->set_body_params(['title'=>'Synthetic REST treatment','status'=>'draft','meta'=>['_sj_category'=>'conservative','_sj_condition_ids'=>[$condition],'_sj_summary'=>'Synthetic REST save']]);
$create_response=rest_do_request($create_request);check('Native REST editor can save typed relationships',$create_response->get_status()===201,wp_json_encode($create_response->get_data()));
// ---- Phase 0 coverage: non-admin workflow, identity binding, SEO, hardening ----
// H1: published entities and pages can be revised by the custom roles, not only administrators.
$entity_change=as_user($ed,fn()=>Editorial::act($condition,'change'));
check('H1 Content editor can create a change draft of a published entity',good($entity_change),msg($entity_change));
if(good($entity_change)) {
 as_user($ed,fn()=>wp_update_post(['ID'=>$entity_change['id'],'post_content'=>'Editor revision of the condition']));
 $r=release($entity_change['id'],$clinician);
 check('H1 Editor → reviewer → publisher cycle releases an entity change',good($r)&&str_contains(get_post($condition)->post_content,'Editor revision'),msg($r));
}
$page_change=as_user($ed,fn()=>Editorial::act($clinical_fixture_id,'change'));
check('H1 Content editor can create a change draft of a published clinical page',good($page_change),msg($page_change));
check('H1 Reviewer cannot release',is_wp_error(as_user($rv,fn()=>Editorial::act($page_change['id']??0,'release'))));
check('H1 Publisher cannot approve',is_wp_error(as_user($pb,fn()=>Editorial::act($page_change['id']??0,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician]))));
// M4: role definitions reach an existing installation without reactivation.
get_role('sj_content_editor')->remove_cap('edit_published_posts'); get_role('sj_content_editor')->add_cap('manage_options');
update_option('sj_schema_version',1); Model::upgrade(); $role=get_role('sj_content_editor');
check('M4 Upgrade routine restores missing and removes stray capabilities',!empty($role->capabilities['edit_published_posts'])&&empty($role->capabilities['manage_options'])&&(int)get_option('sj_schema_version')===Model::SCHEMA_VERSION);
// H2: approval binds to the reviewer's own clinician; delegation is explicit and off by default.
$second=entity('sj_clinician','Second synthetic clinician');
$rv2=person('fixture_unlinked_reviewer','sj_clinical_reviewer');
$h2=wp_insert_post(['post_type'=>'page','post_title'=>'Identity fixture','post_status'=>'draft','post_content'=>'Identity test']);update_post_meta($h2,'_sj_clinical',true);
as_user($ed,fn()=>Editorial::act($h2,'request'));
$r=as_user($rv,fn()=>Editorial::act($h2,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$second]));
check('H2 Reviewer cannot approve as a different clinician',is_wp_error($r)&&$r->get_error_code()==='sj_reviewer_identity',msg($r));
$r=as_user($rv2,fn()=>Editorial::act($h2,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician]));
check('H2 Unlinked reviewer cannot approve',is_wp_error($r)&&$r->get_error_code()==='sj_reviewer_identity',msg($r));
$r=as_user($rv2,fn()=>Editorial::act($h2,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician,'delegated'=>true,'attestation'=>'Reviewed by Professor Jha in clinic on 1 September 2026 and confirmed by email.']));
check('H2 Delegated attestation is refused while switched off',is_wp_error($r)&&$r->get_error_code()==='sj_delegation_disabled',msg($r));
$settings=get_option('sj_practice');$settings['allow_delegated_review']=true;update_option('sj_practice',$settings);
$r=as_user($rv2,fn()=>Editorial::act($h2,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician,'delegated'=>true,'attestation'=>'too short']));
check('H2 Delegated approval needs a written attestation',is_wp_error($r)&&$r->get_error_code()==='sj_attestation',msg($r));
$r=as_user($rv2,fn()=>Editorial::act($h2,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician,'delegated'=>true,'attestation'=>'Reviewed by Professor Jha in clinic on 1 September 2026 and confirmed by email.']));
$audit=Model::get($h2,'audit',[]);$entry=$audit[count($audit)-1]??[];
check('H2 Delegated approval records delegate, clinician and attestation',good($r)&&($entry['mode']??'')==='delegated'&&($entry['user']??0)===$rv2&&($entry['reviewer_entity']??0)===$clinician&&str_contains($entry['attestation']??'','confirmed by email'),wp_json_encode($entry));
$settings['allow_delegated_review']=false;update_option('sj_practice',$settings);
$r=as_user($pb,fn()=>Editorial::act($h2,'release'));
check('H2 Different publisher can release',good($r),msg($r));
$released=Model::get($h2,'audit',[]);$released=$released[count($released)-1]??[];
check('H2 Release audit names approver and releaser',($released['event']??'')==='released'&&($released['approved_by']??0)===$rv2&&($released['user']??0)===$pb,wp_json_encode($released));
// Separation of duties: an administrator linked to a clinician cannot approve and release alone without a recorded reason.
Admin::link_user(1,$clinician);
$h2b=wp_insert_post(['post_type'=>'page','post_title'=>'Separation fixture','post_status'=>'draft','post_content'=>'Separation test']);update_post_meta($h2b,'_sj_clinical',true);
Editorial::act($h2b,'request');Editorial::act($h2b,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician]);
$r=Editorial::act($h2b,'release');
check('H2 Approver cannot release the same revision',is_wp_error($r)&&$r->get_error_code()==='sj_separation',msg($r));
$r=Editorial::act($h2b,'release',['override_reason'=>'Single-handed bootstrap of the staging site']);
$entry=Model::get($h2b,'audit',[]);$entry=$entry[count($entry)-1]??[];
check('H2 Administrator override is possible only with a recorded reason',good($r)&&str_contains($entry['override_reason']??'','bootstrap'),msg($r));
Admin::link_user(1,0);
// H3: search title, robots, social image and sitemap.
$att=wp_insert_attachment(['post_title'=>'Share image','post_mime_type'=>'image/jpeg','post_status'=>'inherit'],'2026/10/share.jpg');
update_post_meta($att,'_wp_attached_file','2026/10/share.jpg');wp_update_attachment_metadata($att,['width'=>1200,'height'=>630,'file'=>'2026/10/share.jpg']);update_post_meta($att,'_wp_attachment_image_alt','Professor Jha in clinic');
$seo=wp_insert_post(['post_type'=>'page','post_title'=>'Prolapse surgery','post_status'=>'publish','post_name'=>'seo-fixture']);
update_post_meta($seo,'_sj_seo_title','Prolapse Surgeon Sheffield | Professor Swati Jha');update_post_meta($seo,'_sj_seo_description','Specialist prolapse care.');update_post_meta($seo,'_sj_social_image_id',$att);
$hidden=wp_insert_post(['post_type'=>'page','post_title'=>'Hidden guide','post_status'=>'publish','post_name'=>'hidden-fixture']);update_post_meta($hidden,'_sj_robots','noindex');
query_posts(['page_id'=>$seo]); the_post();
check('H3 Search title replaces the default document title',wp_get_document_title()==='Prolapse Surgeon Sheffield | Professor Swati Jha',wp_get_document_title());
ob_start();Graph::head();$head=ob_get_clean();
check('H3 Open Graph uses search title, social image and Twitter card',str_contains($head,'og:title" content="Prolapse Surgeon Sheffield')&&str_contains($head,'og:image" content="')&&str_contains($head,'share.jpg')&&str_contains($head,'summary_large_image')&&str_contains($head,'og:image:alt'),$head);
wp_reset_query();
$urls=wp_json_encode((new WP_Sitemaps_Posts())->get_url_list(1,'page'));
check('H3 Noindex pages are excluded from the sitemap',str_contains($urls,'seo-fixture')&&!str_contains($urls,'hidden-fixture'),$urls);
check('H3 Invalid robots value rejected',is_wp_error(Model::validate($seo,'page','_sj_robots','nofollow-everything')));
$uuid2=wp_generate_uuid4();
$seo_manifest=$manifest;$seo_manifest['records']=[['uuid'=>$uuid2,'type'=>'sj_condition','title'=>'Synthetic SEO import','content'=>'','meta'=>['seo_title'=>'Imported title','medically_reviewed_on'=>'2026-01-01','reviewer_entity_id'=>$clinician]]];
$r=Migration::run($seo_manifest,false);$imported=0;foreach(Model::catalogue(false) as $p) if(Model::get($p->ID,'uuid')===$uuid2) $imported=$p->ID;
check('H3 Importer carries search title but never a review date or reviewer',good($r)&&Model::get($imported,'seo_title')==='Imported title'&&!Model::get($imported,'medically_reviewed_on')&&!Model::get($imported,'reviewer_entity_id'),msg($r));
// M1: clinical classification no longer depends on a checkbox.
$tpl=wp_insert_post(['post_type'=>'page','post_title'=>'Template clinical','post_status'=>'draft','page_template'=>'page-clinical']);
check('M1 Clinical template makes a page clinical',Editorial::clinical($tpl));
wp_update_post(['ID'=>$tpl,'post_status'=>'publish']);
check('M1 Clinical-template page cannot be published directly',get_post_status($tpl)==='pending',get_post_status($tpl));
$bound_page=wp_insert_post(['post_type'=>'page','post_title'=>'Bound page','post_status'=>'draft']);
$bound_entity=entity('sj_treatment','Synthetic bound treatment');update_post_meta($bound_entity,'_sj_landing_page_id',$bound_page);
check('M1 Page bound to an entity is clinical',Editorial::clinical($bound_page));
$flagged=wp_insert_post(['post_type'=>'page','post_title'=>'Flagged','post_status'=>'draft']);update_post_meta($flagged,'_sj_clinical',true);
as_user($ed,fn()=>update_post_meta($flagged,'_sj_clinical',false));
check('M1 Content editor cannot clear the clinical flag',(bool)Model::get($flagged,'clinical'));
as_user($rv,fn()=>update_post_meta($flagged,'_sj_clinical',false));
check('M1 Clinical reviewer can clear the clinical flag',!Model::get($flagged,'clinical'));
// M2: presentation fields of a published clinical page are locked and travel through change drafts.
$img2=wp_insert_attachment(['post_title'=>'Hero','post_mime_type'=>'image/jpeg','post_status'=>'inherit'],'2026/10/hero.jpg');
set_post_thumbnail($clinical_fixture_id,$img2);
wp_update_post(['ID'=>$clinical_fixture_id,'menu_order'=>7,'page_template'=>'page-profile']);
check('M2 Published clinical page rejects featured image, template and order changes',!get_post_thumbnail_id($clinical_fixture_id)&&get_page_template_slug($clinical_fixture_id)!=='page-profile'&&(int)get_post($clinical_fixture_id)->menu_order===0);
$m2=as_user($ed,fn()=>Editorial::act($clinical_fixture_id,'change'));
if(good($m2)) { as_user($ed,fn()=>set_post_thumbnail($m2['id'],$img2)); $r=release($m2['id'],$clinician);
 check('M2 Featured image changes are released through review',good($r)&&(int)get_post_thumbnail_id($clinical_fixture_id)===$img2,msg($r)); }
// M3: an abandoned release lock does not block releases forever.
$m3=wp_insert_post(['post_type'=>'page','post_title'=>'Lock fixture','post_status'=>'draft','post_content'=>'Lock']);update_post_meta($m3,'_sj_clinical',true);
as_user($ed,fn()=>Editorial::act($m3,'request'));as_user($rv,fn()=>Editorial::act($m3,'approve',['reviewed_on'=>'2026-09-01','reviewer_id'=>$clinician]));
add_option('sj_release_lock_'.$m3,time(),'',false);
$r=as_user($pb,fn()=>Editorial::act($m3,'release'));check('M3 A fresh release lock still blocks concurrent release',is_wp_error($r)&&$r->get_error_code()==='sj_busy',msg($r));
update_option('sj_release_lock_'.$m3,time()-Editorial::LOCK_TTL-5);
$r=as_user($pb,fn()=>Editorial::act($m3,'release'));check('M3 A stale release lock is recovered',good($r)&&get_option('sj_release_lock_'.$m3)===false,msg($r));
// M5: anonymous exposure.
wp_set_current_user(0);
$json=wp_json_encode(rest_do_request(new WP_REST_Request('GET','/wp/v2/pages/'.$clinical_fixture_id))->get_data());
check('M5 Review metadata is not in anonymous page REST',!str_contains($json,'_sj_reviewer_entity_id')&&!str_contains($json,'_sj_review_due_on')&&!str_contains($json,'_sj_reference_sections'),$json);
$users=rest_do_request(new WP_REST_Request('GET','/wp/v2/users'));check('M5 Anonymous users endpoint denied',$users->get_status()===401,(string)$users->get_status());
wp_set_current_user(1);
check('M5 XML-RPC disabled',apply_filters('xmlrpc_enabled',true)===false);
check('M5 Users sitemap provider removed',apply_filters('wp_sitemaps_add_provider',new WP_Sitemaps_Users(),'users')===false);
$edit=new WP_REST_Request('GET','/wp/v2/pages/'.$clinical_fixture_id);$edit->set_param('context','edit');
check('M5 Editors still receive the metadata in edit context',str_contains(wp_json_encode(rest_do_request($edit)->get_data()),'_sj_reviewer_entity_id'));
// M7: mail outside production is captured, not dropped.
$before_mail=count(get_posts(['post_type'=>Mail::TYPE,'post_status'=>'any','numberposts'=>-1,'fields'=>'ids']));
$sent=wp_mail('someone@example.org','Password reset','Reset link: https://example.org/reset');
$after_mail=get_posts(['post_type'=>Mail::TYPE,'post_status'=>'any','numberposts'=>1]);
check('M7 Non-production mail is captured for administrators',$sent===true&&count(get_posts(['post_type'=>Mail::TYPE,'post_status'=>'any','numberposts'=>-1,'fields'=>'ids']))===$before_mail+1&&str_contains($after_mail[0]->post_content??'','Reset link'));
check('M7 Captured mail is administrator-only',!as_user($ed,fn()=>current_user_can('edit_post',$after_mail[0]->ID??0))&&current_user_can('edit_post',$after_mail[0]->ID??0));
$failed=count(array_filter($tests,fn($test)=>!$test['passed']));echo wp_json_encode(['passed'=>count($tests)-$failed,'failed'=>$failed,'tests'=>$tests]);
