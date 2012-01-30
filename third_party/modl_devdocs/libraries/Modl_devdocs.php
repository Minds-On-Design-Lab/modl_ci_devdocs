<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Modl_devdocs {

	private $CI = false;

	public function __construct() {
		$this->CI = get_instance();

		$this->CI->load->library('Textile');
		$this->CI->load->config('modl_devdocs', true);

	}

	public function parse($path = false, $return = false) {
		$path = $this->load($path);
		if( !$path ) {
			show_error(sprintf("Could not load %s", $path));
			return false;
		}

		$contents = file_get_contents($path);

		if( $this->CI->config->item('auto_toc', 'modl_devdocs')) {
			$contents = $this->auto_toc($contents);
		}

		$parsed = $this->CI->textile->TextileThis($contents);


		if( $return ) {
			return $parsed;
		}

		$this->CI->load->view('modl_devdocs/index.html', array(
			'contents' => $parsed
		));

	}

	private function load($path = false) {

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

	private function auto_toc($str) {
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

		$toc = '<notextile><nav id="_auto_toc">'.$this->build_toc($map).'</nav></notextile>';

		return $toc."\n\n".implode("\n", $out);

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

		return '<ul>'.implode("\n", $out).'</ul>';
	}

}