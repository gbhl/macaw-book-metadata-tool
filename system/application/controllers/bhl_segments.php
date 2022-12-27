<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bhl_segments extends Controller {
    var $CI;

    function __construct() {
        parent::__construct();
    }

	// Function: save_segments()
	//
	// Parameters:
	//
	// Saves the segment data to the database.
    function save_segments() {
        if (!$this->common->check_session(true)) {
					return;
        }
        // Make sure the table exists.
        $this->_check_custom_table();

				// Get the data and decode it.

//				print_r($this->input->post('data'));
				$data = rawurldecode($this->input->post('data'));
//				print_r($data);
				$data = json_decode($data, TRUE);
//				print_r($data);
//				die;

        // Remove the segments saved to the database.
        $query = $this->db->query("DELETE FROM custom_bhl_segments WHERE item_id = ?", array($data['itemID']));
        
        // Insert the data.
        foreach ($data['segments'] as $segment) {
            $columns = [];
            $placeholders = [];
            $values = [];

            foreach ($segment as $field => $value) {
                if ($field == 'id' || is_null($value) || empty($value)) {
                    continue;
                }
                $columns[] = $field;
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $values[] = $value;
                $placeholders[] = '?';
            }
						$columns = implode(',', $columns);
						$placeholders = implode(',', $placeholders);

            $query = $this->db->query("INSERT INTO custom_bhl_segments ({$columns}) VALUES ({$placeholders})", $values);
        }
    }
    
    function load_segments($itemID) {
        if (!$this->common->check_session(true)) {
			return;
        }

        // Make sure the table exists.
        $this->_check_custom_table();
        if (!$itemID) {
            // Get the item ID.
            $itemID = json_decode($this->input->post('data'));
        }

        // Get the segments from the database.
        $segments = NULL;
        $query = $this->db->query("SELECT * FROM custom_bhl_segments WHERE item_id = ?", array($itemID));
        foreach ($query->result() as $row) {
            $segment = NULL;
            foreach ($row as $field => $value) {
                if ($field == 'id') {
                    continue;
                }

                if ($data = json_decode($value, TRUE)) {
                    $value = $data;
                }
                $segment[$field] = $value;
            }
            $segments[] = $segment;
        }
        echo json_encode($segments);
    }

	// ----------------------------
	// Function: _check_custom_table()
	//
	// Parameters:
	//
	// Makes sure that the CUSTOM_BHL_SEGMENTS table exists in the database.
	// ----------------------------
    function _check_custom_table() {
        if (!$this->db->table_exists('custom_bhl_segments')) {
            $this->load->dbforge();
            $this->dbforge->add_field([
                'id' => ['type' => 'int', 'constraint' => '9', 'auto_increment' => TRUE],
                'item_id' => ['type' => 'int'],
                'page_list' => ['type' => 'text'],
                'title' => ['type' => 'varchar', 'constraint' => '500'],
                'translated_title' => ['type' => 'varchar', 'constraint' => '500'],
                'volume' => ['type' => 'varchar', 'constraint' => '50'],
                'issue' => ['type' => 'varchar', 'constraint' => '10'],
                'series' => ['type' => 'varchar', 'constraint' => '10'],
                'date' => ['type' => 'varchar', 'constraint' => '100'],
                'genre' => ['type' => 'varchar', 'constraint' => '100'],
                'language' => ['type' => 'varchar', 'constraint' => '100'],
                'doi' => ['type' => 'varchar', 'constraint' => '100'],
                'author_list' => ['type' => 'text'],
            ]);
            $this->dbforge->add_key('id', TRUE);
            $this->dbforge->create_table('custom_bhl_segments');
        }
    }
}