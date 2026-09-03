<?php
/** Structured reusable service-area records and inheritance. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Service_Area_Registry {
    const RECORDS_MOD = 'search_ai_service_area_records';
    const DEFAULT_IDS_MOD = 'search_ai_profile_default_service_area_ids';
    const MODE_META = '_ajnanda_service_area_mode';
    const IDS_META = '_ajnanda_service_area_ids';

    public static function init() {
        register_post_meta('page', self::MODE_META, array('type'=>'string','single'=>true,'default'=>'inherit','show_in_rest'=>true,'sanitize_callback'=>array(__CLASS__,'sanitize_mode'),'auth_callback'=>static function(){return current_user_can('edit_pages');}));
        register_post_meta('page', self::IDS_META, array('type'=>'array','single'=>true,'default'=>array(),'show_in_rest'=>array('schema'=>array('type'=>'array','items'=>array('type'=>'string'))),'sanitize_callback'=>array(__CLASS__,'sanitize_ids'),'auth_callback'=>static function(){return current_user_can('edit_pages');}));
    }

    public static function types() { return array('country'=>__('Country','ajnanda'),'state'=>__('State or province','ajnanda'),'county'=>__('County or administrative area','ajnanda'),'city'=>__('City','ajnanda'),'postal'=>__('Postal or ZIP code','ajnanda'),'custom'=>__('Custom named region','ajnanda')); }
    public static function sanitize_mode($value) { return 'override' === $value ? 'override' : 'inherit'; }
    public static function sanitize_ids($ids) { return array_values(array_unique(array_filter(array_map('sanitize_key',(array)$ids)))); }

    public static function legacy_records() {
        $records=array();
        foreach ((array)get_theme_mod('search_ai_profile_service_areas',array()) as $name) {
            $name=sanitize_text_field($name); if (!$name) continue;
            $records[]=array('id'=>'legacy-'.substr(sha1(strtolower($name)),0,12),'type'=>'custom','name'=>$name,'country_code'=>'','country_name'=>'','region_code'=>'','region_name'=>'','postal_code'=>'','legacy'=>true);
        }
        return $records;
    }

    public static function records() {
        $stored=get_theme_mod(self::RECORDS_MOD,'__ajnanda_unset__');
        if ('__ajnanda_unset__' === $stored) return self::legacy_records();
        return self::sanitize_records($stored);
    }

    public static function sanitize_records($records) {
        $clean=array(); $types=self::types();
        foreach ((array)$records as $record) {
            $type=sanitize_key($record['type']??'custom'); if (!isset($types[$type])) $type='custom';
            $name=sanitize_text_field($record['name']??''); if (!$name) continue;
            $id=sanitize_key($record['id']??''); if (!$id) $id='area-'.wp_generate_uuid4();
            $country_code=strtoupper(sanitize_text_field($record['country_code']??''));
            if (!preg_match('/^[A-Z]{2}$/',$country_code)) $country_code='';
            $clean[$id]=array('id'=>$id,'type'=>$type,'name'=>$name,'country_code'=>$country_code,'country_name'=>sanitize_text_field($record['country_name']??''),'region_code'=>strtoupper(sanitize_text_field($record['region_code']??'')),'region_name'=>sanitize_text_field($record['region_name']??''),'postal_code'=>sanitize_text_field($record['postal_code']??''),'legacy'=>('custom'===$type&&!empty($record['legacy'])));
        }
        return array_values($clean);
    }

    public static function indexed() { $out=array(); foreach(self::records() as $r)$out[$r['id']]=$r; return $out; }
    public static function default_ids() {
        $stored=get_theme_mod(self::DEFAULT_IDS_MOD,'__ajnanda_unset__');
        if ('__ajnanda_unset__'===$stored) return wp_list_pluck(self::legacy_records(),'id');
        return self::sanitize_ids($stored);
    }
    public static function select($ids) { $index=self::indexed(); $out=array(); foreach(self::sanitize_ids($ids) as $id)if(isset($index[$id]))$out[]=$index[$id]; return $out; }
    public static function defaults() { return self::select(self::default_ids()); }
    public static function public_names($records=null) { return array_values(wp_list_pluck(null===$records?self::defaults():$records,'name')); }

    public static function effective($post_id) {
        $mode=self::sanitize_mode(get_post_meta($post_id,self::MODE_META,true));
        $requested='override'===$mode?self::sanitize_ids(get_post_meta($post_id,self::IDS_META,true)):self::default_ids();
        $records=self::select($requested); $found=wp_list_pluck($records,'id');
        return array('mode'=>$mode,'requested_ids'=>$requested,'records'=>$records,'missing_ids'=>array_values(array_diff($requested,$found)),'empty_override'=>'override'===$mode&&!$records);
    }

    public static function node_id($record) { return home_url('/#service-area-'.sanitize_key($record['id'])); }
    public static function schema_values($records) {
        $values=array();
        foreach($records as $r) {
            if ('custom'===$r['type'] || ('postal'===$r['type'] && !$r['country_code'] && !$r['country_name'])) $values[]=$r['name'];
            else $values[]=array('@id'=>self::node_id($r));
        }
        return $values;
    }
    public static function schema_nodes($records) {
        $nodes=array();
        foreach($records as $r) {
            if ('custom'===$r['type'] || ('postal'===$r['type'] && !$r['country_code'] && !$r['country_name'])) continue;
            $map=array('country'=>'Country','state'=>'State','county'=>'AdministrativeArea','city'=>'City','postal'=>'Place');
            $node=array('@type'=>$map[$r['type']],'@id'=>self::node_id($r),'name'=>$r['name']);
            if ('state'===$r['type']&&$r['region_code']) $node['identifier']=$r['region_code']; elseif ('country'===$r['type']&&$r['country_code']) $node['identifier']=$r['country_code'];
            if ('postal'===$r['type']) $node['address']=array_filter(array('@type'=>'PostalAddress','postalCode'=>$r['postal_code']?:$r['name'],'addressRegion'=>$r['region_name'],'addressCountry'=>$r['country_code']?:$r['country_name']));
            $nodes[]=$node;
        }
        return $nodes;
    }

    public static function diagnostics() {
        $issues=array(); $index=self::indexed();
        $stored=get_theme_mod(self::RECORDS_MOD,'__ajnanda_unset__');
        if ('__ajnanda_unset__'!==$stored) foreach((array)$stored as $record) {
            $type=sanitize_key($record['type']??''); $code=strtoupper(sanitize_text_field($record['country_code']??''));
            if (!sanitize_text_field($record['name']??'') || !isset(self::types()[$type]) || ($code&&!preg_match('/^[A-Z]{2}$/',$code))) $issues[]='invalid_record';
        }
        foreach(self::default_ids() as $id)if(!isset($index[$id]))$issues[]='missing_default';
        foreach(self::records() as $r)if(!empty($r['legacy']))$issues[]='legacy';
        $pages=get_posts(array('post_type'=>'page','post_status'=>'publish','meta_key'=>self::MODE_META,'fields'=>'ids','numberposts'=>-1,'no_found_rows'=>true));
        foreach($pages as $id)if('service'===AJNanda_Search_AI_Page_Semantic_Intent::evaluate($id)['effective']){$e=self::effective($id);if($e['empty_override'])$issues[]='empty_override';if($e['missing_ids'])$issues[]='missing_override';}
        return array_count_values($issues);
    }
}
