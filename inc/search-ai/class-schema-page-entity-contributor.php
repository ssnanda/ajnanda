<?php
/** Page-level primary entity contribution. @package AJNanda */
if (! defined('ABSPATH')) { exit; }

class AJNanda_Search_AI_Schema_Page_Entity_Contributor {
    public static function contribute($context) {
        $role = $context->semantic_intent['effective'];
        if ('service' === $role) {
            $node = array('@type' => 'Service', '@id' => $context->primary_id, 'name' => $context->title, 'url' => $context->url, 'mainEntityOfPage' => array('@id' => $context->webpage_id), 'provider' => array('@id' => $context->identity_id));
            if ($context->description) { $node['description'] = $context->description; }
            if ($context->image) { $node['image'] = $context->image; }
            $areas=AJNanda_Search_AI_Service_Area_Registry::effective($context->post_id);
            if ($areas['records']) $node['areaServed']=AJNanda_Search_AI_Service_Area_Registry::schema_values($areas['records']);
            $result=self::result($node,$context);
            $result['nodes']=array_merge($result['nodes'],AJNanda_Search_AI_Service_Area_Registry::schema_nodes($areas['records']));
            return $result;
        }
        if ('product' === $role) {
            $node = array('@type' => 'Product', '@id' => $context->primary_id, 'name' => $context->title, 'url' => $context->url, 'mainEntityOfPage' => array('@id' => $context->webpage_id));
            if ($context->description) { $node['description'] = $context->description; }
            if ($context->image) { $node['image'] = $context->image; }
            return self::result($node, $context);
        }
        if ('primary_location' === $role) {
            return array('nodes' => array(), 'relationships' => array(array($context->webpage_id, 'mainEntity', $context->identity_id)));
        }
        return array('nodes' => array(), 'relationships' => array());
    }

    private static function result($node, $context) {
        return array('nodes' => array($node), 'relationships' => array(array($context->webpage_id, 'mainEntity', $context->primary_id)));
    }
}
