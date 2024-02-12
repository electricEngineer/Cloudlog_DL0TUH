<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
	Handles Displaying of information for station tools.
*/

class Station extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->helper(array('form', 'url'));

		$this->load->model('user_model');
		if(!$this->user_model->authorize(2)) { $this->session->set_flashdata('notice', 'You\'re not allowed to do that!'); redirect('dashboard'); }
	}

	public function index()
	{
		$this->load->model('stations');
		$this->load->model('Logbook_model');
		$this->load->model('user_model');

		$data['is_admin'] = ($this->user_model->authorize(99));

		$data['stations'] = $this->stations->all_with_count();
		$data['stations_shared'] = $this->stations->all_with_count_shared();
		$data['current_active'] = $this->stations->find_active();
		$data['is_there_qsos_with_no_station_id'] = $this->Logbook_model->check_for_station_id();

		// Render Page
		$data['page_title'] = lang('station_location');
		$this->load->view('interface_assets/header', $data);
		$this->load->view('station_profile/index');
		$this->load->view('interface_assets/footer');
	}

	public function create() {
		$this->load->model('stations');
		$this->load->model('dxcc');
		$data['dxcc_list'] = $this->dxcc->list();

		$this->load->model('logbook_model');
		$data['iota_list'] = $this->logbook_model->fetchIota();

		$this->load->library('form_validation');

		$this->form_validation->set_rules('station_profile_name', 'Station Profile Name', 'required');

		if ($this->form_validation->run() == FALSE)
		{
			$data['page_title'] = lang('station_location_create_header');
			$this->load->view('interface_assets/header', $data);
			$this->load->view('station_profile/create');
			$this->load->view('interface_assets/footer');
		}
		else
		{
			if (($station_id = $this->stations->add()) !== false) {
				// [eQSL default msg] ADD to user options (option_type='eqsl_default_qslmsg'; option_name='key_station_id'; option_key=station_id; option_value=value) //
				$eqsl_default_qslmsg = xss_clean($this->input->post('eqsl_default_qslmsg', true));
				if (!empty(trim($eqsl_default_qslmsg))) {
					$this->load->model('user_options_model');
					$this->user_options_model->set_option('eqsl_default_qslmsg','key_station_id',array($station_id=>$eqsl_default_qslmsg));
				}
			}
			redirect('station');
		}
	}

	public function edit($id) {
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$data = $this->load_station_for_editing($id);
			$data['page_title'] = lang('station_location_edit') . $data['my_station_profile']->station_profile_name;

			if ($this->form_validation->run() == FALSE) {
				// [eQSL default msg] GET from user options (option_type='eqsl_default_qslmsg'; option_name='key_station_id'; option_key=station_id) //
				$this->load->model('user_options_model');
				$options_object = $this->user_options_model->get_options('eqsl_default_qslmsg',array('option_name'=>'key_station_id','option_key'=>$id))->result();
				$data['eqsl_default_qslmsg'] = (isset($options_object[0]->option_value))?$options_object[0]->option_value:'';

				$this->load->view('interface_assets/header', $data);
				$this->load->view('station_profile/edit');
				$this->load->view('interface_assets/footer');
			} else {
				if ($this->stations->edit() !== false) {
					// [eQSL default msg] ADD to user options (option_type='eqsl_default_qslmsg'; option_name='key_station_id'; option_key=station_id; option_value=value) //
					$eqsl_default_qslmsg = xss_clean($this->input->post('eqsl_default_qslmsg', true));
					$this->load->model('user_options_model');
					if (!empty(trim($eqsl_default_qslmsg))) {
						$this->user_options_model->set_option('eqsl_default_qslmsg','key_station_id',array($id=>$eqsl_default_qslmsg));
					} else {
						$this->user_options_model->del_option('eqsl_default_qslmsg','key_station_id',array('option_key'=>$id));
					}			
				}

				$data['notice'] = lang('station_location') . $this->security->xss_clean($this->input->post('station_profile_name', true)) . " Updated";

				redirect('station');
			}
		} else {
			redirect('station');
		}
	}

	public function copy($id) {
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$data = $this->load_station_for_editing($id);
			$data['page_title'] = "Duplicate Station Location: {$data['my_station_profile']->station_profile_name}";

			// we NULLify station_id and station_profile_name to make sure we are creating a new station
			$data['copy_from'] = $data['my_station_profile']->station_id;
			$data['my_station_profile']->station_id = NULL;
			$data['my_station_profile']->station_profile_name = '';

			if ($this->form_validation->run() == FALSE)
			{
				$this->load->view('interface_assets/header', $data);
				$this->load->view('station_profile/edit');
				$this->load->view('interface_assets/footer');
			}
			else
			{
				$this->stations->add();

				redirect('station');
			}
		} else {
			redirect('station');
		}
	}

	function load_station_for_editing($id): array {
		$this->load->library('form_validation');

		$this->load->model('stations');
		$this->load->model('dxcc');
		$this->load->model('logbook_model');

		$data['iota_list'] = $this->logbook_model->fetchIota();

		$item_id_clean = $this->security->xss_clean($id);

		$station_profile_query = $this->stations->profile($item_id_clean);

		$data['my_station_profile'] = $station_profile_query->row();

		$data['station_location_shares'] = $this->stations->list_location_shares($id);

		$data['dxcc_list'] = $this->dxcc->list();

		$this->form_validation->set_rules('station_profile_name', 'Station Profile Name', 'required');

		return $data;
	}

	// This function allows a user to claim ownership of a station location
	function claim_user($id) {
		// $id is the profile id
		$this->load->model('stations');
		$this->stations->claim_user($id);
		
		redirect('station');
	}

	function reassign_profile($id) {
		// $id is the profile that needs reassigned to QSOs
		$this->load->model('stations');
		$this->stations->reassign($id);

		//$this->stations->logbook_session_data();
		redirect('station');
	}

	function set_active($current, $new) {
		$this->load->model('stations');
		$this->stations->set_active($current, $new);

		//$this->stations->logbook_session_data();
		redirect('station');
	}

	public function delete($id) {
		$this->load->model('stations');
		if ($this->stations->check_station_is_accessible($id)) {
			$this->stations->delete($id);
			// [eQSL default msg] DELETE user options //
			$this->load->model('user_options_model');
			$this->user_options_model->del_option('eqsl_default_qslmsg','key_station_id',array('option_key'=>$id));
		}
		redirect('station');
	}

    	public function deletelog($id) {
        $this->load->model('stations');
	if ($this->stations->check_station_is_accessible($id)) {
        	$this->stations->deletelog($id);
	}
        redirect('station');
    }

    /*
	 * Function is used for autocompletion of Counties in the station profile form
	 */
    public function get_county() {
        $json = [];

        if(!empty($this->input->get("query"))) {
            $query = isset($_GET['query']) ? $_GET['query'] : FALSE;
            $county = $this->input->get("state");

            $file = 'assets/json/US_counties.csv';

            if (is_readable($file)) {
                $lines = file($file, FILE_IGNORE_NEW_LINES);
                $input = preg_quote($county, '~');
                $reg = '~^'. $input .'(.*)$~';
                $result = preg_grep($reg, $lines);
                $json = [];
                $i = 0;
                foreach ($result as &$value) {
                    $county = explode(',', $value);
                    // Limit to 300 as to not slowdown browser too much
                    if (count($json) <= 300) {
                        $json[] = ["name"=>$county[1]];
                    }
                }
            }
        }

        header('Content-Type: application/json');
        echo json_encode($json);
    }

    // [eQSL default msg] Function return options from this station (but can be general use) //
	public function get_options() {
		$return_json = array();
		$option_type = $this->input->post('option_type');
		$option_name = $this->input->post('option_name');
		$option_key = $this->input->post('option_key');
		if (!empty($option_type) && !empty($option_name) && ($option_key>0)) {
			$this->load->model('user_options_model');
			$options_object = $this->user_options_model->get_options($option_type,array('option_name'=>$option_name,'option_key'=>$option_key))->result();
			$return_json[$option_type] = (isset($options_object[0]->option_value))?$options_object[0]->option_value:'';
		}
        header('Content-Type: application/json');
        echo json_encode($return_json);
    }

	/*
		Deletes the share of a station by its share id
		@PARAM $share_id is currently the relationship id out of table station_logbooks_relationship
	*/
	public function delete_location_share($share_id) {
		// Clean ID
		$clean_share_id = $this->security->xss_clean($share_id);

		// be sure that station belongs to user
		$CI =& get_instance();
		$CI->load->model('Stations');

		$this->db->select('*');
		$this->db->from('station_logbooks_relationship');
		$this->db->where('logbook_relation_id', $share_id);
		$query = $this->db->get();
		$location_id = $query->row()->station_location_id;
		if (!$CI->Stations->check_station_is_accessible($location_id)) {
			return;
		}

		// Delete relationship
		$this->db->where('logbook_relation_id', $share_id);
		$this->db->delete('station_logbooks_relationship'); 
		redirect('station/edit/'.$location_id);
	}

	/*
		Deletes the share of a station by its share id
		@PARAM $share_id is currently the relationship id out of table station_logbooks_relationship
	*/
	public function create_location_share($location_id_to_share, $logbook_id_to_receive_share) {
		// Clean ID
		$clean_location_id_to_share = $this->security->xss_clean($location_id_to_share);
		$clean_logbook_id_to_receive_share = $this->security->xss_clean($logbook_id_to_receive_share);

		// be sure that station belongs to user
		$CI =& get_instance();
		$CI->load->model('Stations');
		if (!$CI->Stations->check_station_is_accessible($clean_location_id_to_share)) {
			return;
		}

		// Create data array with field values
		$data = array(
			'station_logbook_id' => $clean_logbook_id_to_receive_share,
			'station_location_id' =>  $clean_location_id_to_share,
		);

		// Insert Record
		$this->db->insert('station_logbooks_relationship', $data); 
		
		redirect('station/edit/'.$clean_location_id_to_share);
	}
}
