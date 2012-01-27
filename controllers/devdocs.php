<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Devdocs extends CI_Controller {

	public function __construct() {
		parent::__construct();

		$this->load->add_package_path(APPPATH.'third_party/modl_devdocs', true);
		$this->load->library('Modl_devdocs');
	}

	public function index() {

		$this->modl_devdocs->parse();
	}

}