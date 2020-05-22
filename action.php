<?php
/**
 * DokuWiki Plugin searchform (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Gerrit Uitslag <klapinklapin@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

require_once(DOKU_INC . 'inc/fulltext.php');

/**
 * Class action_plugin_searchform
 */
class action_plugin_searchform2 extends DokuWiki_Action_Plugin {

    /**
     * Registers a callback function for a given event
     *
     * @param Doku_Event_Handler $controller DokuWiki's event controller object
     * @return void
     */
    public function register(Doku_Event_Handler $controller) {

        $controller->register_hook('SEARCH_QUERY_FULLPAGE', 'BEFORE', $this, '_search_query_fullpage');
        $controller->register_hook('SEARCH_QUERY_PAGELOOKUP', 'BEFORE', $this, '_search_query_pagelookup');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this,'_ajax_call');

    }

    /**
     * Restrict fullpage search to namespace given as url parameter
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function _search_query_fullpage(Doku_Event &$event, $param) {
        $this->_addNamespace2query($event->data['query']);
    }

    /**
     * Restrict page lookup search to namespace given as url parameter
     *
     * @param Doku_Event $event  event object by reference
     * @param mixed      $param  [the parameters passed as fifth argument to register_hook() when this
     *                           handler was registered]
     * @return void
     */
    public function _search_query_pagelookup(Doku_Event &$event, $param) {
        $this->_addNamespace2query($event->data['id']);
    }

    /**
     * Extend query string with namespace, if it doesn't contain a namespace expression
     *
     * @param string &$query (reference) search query string
     */
    private function _addNamespace2query(&$query) {
        global $INPUT;

        $ns = cleanID($INPUT->str('ns'));
        if($ns) {
            //add namespace if user hasn't already provide one
            if(!preg_match('/(?:^| )(?:@|ns:)[\w:]+/u', $query, $matches)) {
                $query .= ' @' . $ns;
            }
        }
    }
    
    # Funktion zum Ausschluß von Seitennamen bei der Suche
    function strposa($haystack, $needles=array(), $offset=0) {
        $chr = array();
        foreach($needles as $needle) {
            $res = stripos($haystack, $needle, $offset);
            if ($res !== false) $chr[$needle] = $res;
        }
        if(empty($chr)) return false;
        return min($chr);
    }
    
    # Adds the Ajax-Call "quickfullseach"
	public function _ajax_call(Doku_Event $event, $param) {
		if ($event->data !== 'quickfullsearch') {
			return;
		}
    
		# No other ajax call handlers needed
		$event->stopPropagation();
		$event->preventDefault();
 
 
		# Perform Fullsearch
		global $INPUT;

		$query = $INPUT->post->str('q');
		if(empty($query)) $query = $INPUT->get->str('q');
		if(empty($query)) return;
        
        if ($this->getConf('externalsearch')<>'') {
			echo file_get_contents(($this->getConf('externalsearch')).$query);
			return;
		}

		$query = urldecode($query);
        
        # Add * to each term (excepting for namespaces) for real fulltext search
		$terms = explode(' ',$query);
		foreach ($terms as &$t) {
			if (strpos($t,'*')===false && strpos($t,'@')===false) {
				$t="*$t*";
				$search[] = $t;
			}
		}
		$query=implode(' ',$terms);
        
		$regex = Array();
		$data = ft_pageSearch($query,$regex);
		
		if(!count($data)) return;
		
		# display number of results
		echo '<div class="qfs_result_num">' . count($data) . ' ' . $this->getLang('results') . '</div>';

        
        $result = Array();
        foreach ($data as $id => $counts) {
            $result[] = Array('id' => $id, 'title' => p_get_first_heading($id), 'counts' => $counts);
        }

        foreach ($terms as &$t) {$t=str_replace("*","",$t);}

        # einfacher Bubble-Sort nach id und dann Titel
        foreach (Array('id','title') as $sort) {
            for ($i=0;$i<Count($result);$i++) {
                for ($c=0;$c<Count($result);$c++) {
                    if ($this->strposa($result[$i][$sort],$terms)!== false) {
                        if ($this->strposa($result[$c][$sort],$terms)=== false) {
                            if ($i>$c) { # Swap
                                $temp = $result[$i];
                                $result[$i] = $result[$c];
                                $result[$c] = $temp;
                            }
                        }
                    }
                }
            }
        }

		# display results
		$i = Array();
		
        for ($c=0;$c<Count($result);$c++) {
            $id = $result[$c]['id'];
            
            # Extract Namespace
            if ($this->getConf('show_namespace')==1) {
                $n = strrpos($id,':');
                if ($n !== false) {
                    $ns = '<span class="qfs_namespace"> @'.substr($id,0,$n).'</span>';
                } else $ns = '';
            } else $ns = '';
            
            # Output title
			echo '<div class="qfs_result">';
			$t = html_wikilink(':' . $id, p_get_first_heading($id),$search).$ns.'<br><div>' ;
			echo $t;
			
			# Could not get ft_snippet to work properly, so uses rawWiki instead			
			echo substr(strip_tags(p_render('xhtml', p_get_instructions(rawWiki($id)),$i)),strlen(strip_tags($t)+2),300);
            
			echo '</div></div>';
		}

	}

}

// vim:ts=4:sw=4:et:
