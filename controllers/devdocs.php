<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Devdocs extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->add_package_path(APPPATH.'third_party/modl_devdocs', true);
		$this->load->library('Modl_devdocs');
		$this->modl_devdocs->load();
	}

	public function index() {
		$page = 0;
		if( $this->input->get('_dp') ) {
			$page = $this->input->get('_dp');
		}
		$this->modl_devdocs->fetch($page);
	}

}