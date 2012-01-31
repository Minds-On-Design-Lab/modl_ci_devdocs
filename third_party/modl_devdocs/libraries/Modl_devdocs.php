<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Modl_devdocs {

	private $CI = false;

	private $contents = false;
	private $path = false;

	private $pages = array();

	private $links = array();

	public function __construct() {
		$this->CI = get_instance();

		$this->CI->load->library('Textile');
		$this->CI->load->config('modl_devdocs', true);
		$this->CI->load->driver('cache', array('adapter' => 'file'));
		$this->CI->load->helper('file');

	}

	public function load($path = false, $force = false) {

		$this->path = $this->resolve_path($path);

		if( !$this->path ) {
			show_error(sprintf("Could not load %s", $path));
			return false;
		}

		if( $force === false
			&& $this->CI->config->item('enable_cache', 'modl_devdocs')
		) {
			$this->load_cached();
		} else {
			$this->load_file();
		}
	}

	public function fetch($page = 0, $return = false) {
		if( !is_numeric($page) || count($this->pages) < $page-1) {
			show_error('Page not found');
			return false;
		}

		if( $return ) {
			return $this->pages[$page];
		}

		$this->CI->load->view('modl_devdocs/index.html', array(
			'contents' => $this->pages[$page]
		));

	}

	public function get_last_build() {
		if( $this->CI->config->item('enable_cache', 'modl_devdocs') ) {
			// check cache first
			$meta = $this->CI->cache->get_metadata(hash('crc32', $this->path));
			if( $meta && array_key_exists('mtime', $meta) ) {
				return $meta['mtime'];
			}
		}

		$meta = get_file_info($this->path, 'date');
		return $meta['date'];
	}

	public function get_links() {
		return $this->links;
	}

	private function build_links($pages) {
		$links = array();

		$url = current_url();

		foreach( $pages as $i => $page ) {
			$line = reset($page);
			$label = trim(substr($line, 3));

			$links[] = '<a href="'.$url.'?_dp='.$i.'">'.$label.'</a>';
		}

		return $links;
	}

	private function load_cached() {
		$hash = hash('crc32', $this->path);

		if( !($data = $this->CI->cache->get($hash)) ) {
			$this->load_file($this->path);
			return;
		}

		$data = unserialize($data);

		$this->pages = $data['pages'];
		$this->links = $data['links'];
	}

	private function load_file() {
		$this->contents = file($this->path);
		$toc = false;

		$pages = array($this->contents);
		if( $this->CI->config->item('auto_page', 'modl_devdocs')) {
			$pages = $this->build_pages();
		}

		$this->links = $this->build_links($pages);

		foreach( $pages as $i => $page ) {
			$page = $this->parse_code_blocks($page);

			$pages[$i] = $this->CI->textile->TextileThis(implode("", $page));
		}

		if( $this->CI->config->item('enable_cache', 'modl_devdocs') ) {
			$this->CI->cache->save(hash('crc32', $this->path), serialize(array(
				'pages' => $pages,
				'links' => $this->links
			)));
		}

		$this->pages = $pages;
	}

	private function resolve_path($path = false) {

		if( !$path ) {
			$default = $this->CI->config->item('default_name', 'modl_devdocs');

			if( file_exists(sprintf('%sdocs/%s.textile', APPPATH, $default)) ) {
				// looking for docs/foo.textile
				return sprintf('%sdocs/%s.textile', APPPATH, $default);
			} elseif( file_exists(sprintf('%s/%s.textile', APPPATH, $default)) ) {
				// looking for foo.textile
				return sprintf('%s/%s.textile', APPPATH, $default);
			}
		} elseif( $path && file_exists($path) ) {
			// full path
			return $path;
		} elseif( $path && file_exists(APPPATH.$path) ) {
			// relative path
			return APPPATH.$path;
		}

		// give up
		return false;
	}

	private function parse_code_blocks($lines) {
		$out = array();

		foreach( $lines as $line ) {
			if( strpos($line, '<example') !== false ) {
				$line = preg_replace(
					'/\<example( type\="([a-zA-Z]*)")?\>/',
					'<notextile><script type="syntaxhighlighter" class="brush: $2"><![CDATA[',
					$line
				);
			} elseif( strpos($line, '</example>') !== false ) {
				$line = str_replace(
					'</example>',
					']]></script></notextile>',
					$line
				);
			} elseif( strpos($line, '<pre') !== false ) {
				$line = str_replace(
					'<pre',
					'<notextile><pre',
					$line
				);
			} elseif( strpos($line, '</pre>') !== false ) {
				$line = str_replace(
					'</pre>',
					'</pre></notextile>',
					$line
				);
			}
			$out[] = $line;
		}

		return $out;
	}

	private function build_pages() {
		$lines = $this->contents;

		$pages = array();
		$current = array();

		foreach( $lines as $line ) {
			if( substr(trim($line), 0, 3) == 'h1.' && !empty($current)) {
				$pages[] = $current;
				$current = array();
			}

			$current[] = $line;
		}

		$pages[] = $current;

		return $pages;
	}
}