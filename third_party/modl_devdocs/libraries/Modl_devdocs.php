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

}