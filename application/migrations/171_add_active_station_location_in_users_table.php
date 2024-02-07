<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Adding a column to users table for the timestamp of the last login

class Migration_add_last_login extends CI_Migration
{

  public function up()
  {
    if (!$this->db->field_exists('active_station_location', 'users')) {
      $fields = array(
        'active_station_location int(11) NULL DEFAULT NULL AFTER `active_station_logbook`',
      );
      $this->dbforge->add_column('users', $fields);
    }
  }

  public function down()
  {
    $this->dbforge->drop_column('users', 'active_station_location');
  }
}
