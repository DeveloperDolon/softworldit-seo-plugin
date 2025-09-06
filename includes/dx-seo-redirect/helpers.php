<?php
if (!defined('ABSPATH')) exit;

class DXSEO_Helpers {
    public static function table(){
        global $wpdb; return $wpdb->prefix.'dxseo_redirects';
    }

    public static function now(){
        return current_time('mysql');
    }

    public static function sanitize_path($path){
        $path = trim($path);
        // Ensure leading slash, remove domain
        $parsed = wp_parse_url($path);
        if(!empty($parsed['path'])) $path = '/'.ltrim($parsed['path'],'/');
        if(!empty($parsed['query'])) $path .= '?'.$parsed['query'];
        return $path ?: '/';
    }

    public static function csv_download($rows, $filename='dxseo-redirects.csv'){
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename='.esc_attr($filename));
        $out = fopen('php://output','w');
        fputcsv($out, ['id','from_path','to_path','regex','status_code','active','start_at','end_at','note']);
        foreach($rows as $r){
            fputcsv($out, [
                $r->id,$r->from_path,$r->to_path,$r->regex,$r->status_code,$r->active,$r->start_at,$r->end_at,$r->note
            ]);
        }
        fclose($out); exit;
    }
}
