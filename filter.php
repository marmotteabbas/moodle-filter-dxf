<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


defined('MOODLE_INTERNAL') || die();

// This class looks for text including markup and
// applies tidy's repair function to it.
// Tidy is a HTML clean and
// repair utility, which is currently available for PHP 4.3.x and PHP 5 as a
// PECL extension from http://pecl.php.net/package/tidy, in PHP 5 you need only
// to compile using the --with-tidy option.
// If you don't have the tidy extension installed or don't know, you can enable
// or disable this filter, it just won't have any effect.
// If you want to know what you can set in $tidyoptions and what their default
// values are, see http://php.net/manual/en/function.tidy-get-config.php.

/**
 * dxf filter for dxf viewer display
 *
 * @package    filter_dxf
 * @copyright  2018 Florent Paccalet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_dxf extends moodle_text_filter {
    
    public $page;
    
    public function setup($page, $context) {
         // This only requires execution once per request.
        
        $this->page = $page;
        
        static $jsinitialised = false;
        if ($jsinitialised) {
            return;
        }
        $jsinitialised = true;
        
        $page->requires->css('/filter/dxf/style.css');
    }

    function filter($text, array $options = array()) {
        global $CFG;
        //select file to display
        $text_origin = $text;
        $elem_to_replace = array();
        $output = "";
        $begin = 0;
        $end = strpos($text,".dxf")+4;
        while (strpos($text,".dxf") == true) {
            
            if (strrchr(substr($text, $begin, $end), " ") != null)
            {
                $pre_url = strrchr(substr($text, $begin, $end), " ");
            } else {
                $pre_url = substr($text, $begin, $end);
            }
            
            while (strpos($pre_url,">") !== false || mb_stripos($pre_url,";") !== false) {
                if (strpos($pre_url,">") > strpos($pre_url,";")) {
                    $begin = strpos($pre_url,">")+1;        
                } elseif (strpos($pre_url,">") != mb_stripos($pre_url,";")){
                    $begin = mb_stripos($pre_url,";")+1; 
                } else {
                    $begin = 1;
                }

                $pre_url = substr($pre_url, $begin);
                $begin = 0;
            }
            
               if(substr($pre_url,0,6) != " href=" && substr($pre_url,0,6) != "href=") {
                   $elem_to_replace[] = $pre_url;
                }
            
            
            $text = substr($text,strpos($text,".dxf")+4);
            $end = strpos($text,".dxf")+4;
        }

   
        if (!empty($elem_to_replace)) {
            
            $elem_to_replace_with_name = array();
            
            foreach ($elem_to_replace as $id => $elem) {              
                $patterns = array();
                $replacements = array();
                $replacements[0] = "viewer_dxf_".$id;
                $patterns[0] = '~<a\s[^>]*href="*"[^>]*>([^>]*)'.$elem.'</a>~';
                
                if(preg_match($patterns[0], $text_origin)) {
                    
                    $text_origin = preg_replace($patterns, $replacements, $text_origin,1);
                } else {
                    $patterns[0] = '~'.$elem.'~';
                    $text_origin = preg_replace($patterns, $replacements, $text_origin,1);
                }
                
                $elem_to_replace_with_name[$id]["name"] = $replacements[0];
                $elem_to_replace_with_name[$id]["url"] = $elem;
            }
            
            $all_url = array();
            foreach ($elem_to_replace_with_name as $id => $elem) {
                $all_url[] = $elem["url"];
                
                $replace_code = '
                        <div id="cad-view-'.($id+1).'" class="cad-view">
                            <div class="progress progress-striped">
                                <div id="file-progress-bar-'.($id+1).'" class="progress-bar progress-bar-success" role="progressbar" style="width: 0">
                                </div>
                            </div>
                    </div>';  
                
                $text_origin = str_replace($elem_to_replace_with_name[$id]["name"], $replace_code, $text_origin);
            }
                        
            $this->page->requires->js_call_amd('filter_dxf/launcher', 'init',array($all_url,$CFG->wwwroot));
        } 
  
        //include here to remove error 
        return $text_origin;
    }
}

