<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Modl_devdocs {

	private $CI = false;
	private $contents = false;

	public function __construct() {
		$this->CI = get_instance();

		$this->CI->load->library('Textile');
		$this->CI->load->config('modl_devdocs', true);
		$this->CI->load->driver('cache', array('adapter' => 'file'));

	}

	public function fetch($path = false, $force = false, $return = false) {

		$path = $this->resolve_path($path);

		if( !$path ) {
			show_error(sprintf("Could not load %s", $path));
			return false;
		}

		if( $force === false
			&& $this->CI->config->item('enable_cache', 'modl_devdocs')
		) {
			$data = $this->fetch_cached($path);
		} else {
			$data = $this->fetch_file($path);
		}

		if( $return ) {
			return $data;
		}

		$this->CI->load->view('modl_devdocs/index.html', $data);

	}

	private function fetch_cached($path) {
		$hash = hash('crc32', $path);

		if( !($data = $this->CI->cache->get($hash)) ) {
			$data = $this->fetch_file($path);
		}

		if( !is_array($data) ) {
			return unserialize($data);
		}

		return $data;
	}

	private function fetch_file($path) {
		$this->contents = file_get_contents($path);
		$toc = false;

		if( $this->CI->config->item('auto_toc', 'modl_devdocs')) {
			$toc = $this->auto_toc();
		}

		$parsed = $this->CI->textile->TextileThis($this->contents);

		$data = array(
			'contents' => $parsed,
			'toc' => $toc
		);

		if( $this->CI->config->item('enable_cache', 'modl_devdocs') ) {
			$this->CI->cache->save(hash('crc32', $path), serialize($data));
		}

		return $data;
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

	private function auto_toc() {
		$str = $this->contents;

		// this is a un-textiled string, btw
		$lines = preg_split("/((?<!\\\|\r)\n)|((?<!\\\)\r\n)/", $str);
		$map = array();
		$tree = array(
			1 => false,
			2 => false,
			3 => false,
			4 => false,
			5 => false,
			6 => false
		);

		$out = array();

		foreach( $lines as $line ) {
			if( !strlen($line) ) {
				$out[] = $line;
				continue;
			}

			if( preg_match('/h([1-6])\./', substr($line, 0, 3)) ) {
				$hash = hash('crc32', $line);
				$anchor = sprintf('(#%s)', $hash);

				$line = $line;

				$marker = substr($line, 0, 2);
				$label = substr($line, 4);

				$line = sprintf("%s%s. %s", $marker, $anchor, $label);

				switch($marker) {
					case 'h1' :
						$map[$hash] = array('label' => $label, 'children' => array());
						$tree[1] = $hash;
						break;
					case 'h2' :
						$map[$tree[1]]['children'][$hash] = array('label' => $label, 'children' => array());
						$tree[2] = $hash;
						break;
					case 'h3' :
						$map[$tree[1]]['children'][$tree[2]]['children'][$hash] = array('label' => $label, 'children' => array());
						$tree[3] = $hash;
						break;
					case 'h4' :
						$map[$tree[1]]['children'][$tree[2]]['children'][$tree[3]]['children'][$hash] = array('label' => $label, 'children' => array());
						$tree[3] = $hash;
						break;
					case 'h5' :
						$map[$tree[1]]['children'][$tree[2]]['children'][$tree[3]]['children'][$tree[4]]['children'][$hash] = array('label' => $label, 'children' => array());
						$tree[3] = $hash;
						break;
					case 'h6' :
						$map[$tree[1]]['children'][$tree[2]]['children'][$tree[3]]['children'][$tree[4]]['children'][$tree[5]]['children'][$hash] = array('label' => $label);
						$tree[3] = $hash;
						break;
				}

			}

			$out[] = $line;
		}

		$toc = $this->build_toc($map);

		$this->contents = implode("\n", $out);

		return $toc;

	}

	public function build_toc($map) {
		$out = array();
		foreach( $map as $hash => $data ) {
			$label = $data['label'];
			$kids = '';

			if( count($data['children']) ) {
				$kids = $this->build_toc($data['children']);
			}

			$out[] = sprintf(
				'<li><a href="#%s">%s</a>%s</li>',
				$hash,
				$label,
				$kids
			);
		}

		return '<ul>'.implode("\n", $out)."</ul>\n";
	}

}