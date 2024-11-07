<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

   $settings->add(new admin_setting_configtext('programming_ojip', get_string('programming_ojip', 'programming'),
                      get_string('configojip', 'programming'), ''));
   $settings->add(new admin_setting_configtext('programming_moss_userid', get_string('programming_moss_userid', 'programming'),
                      get_string('programming_moss_useridinfo', 'programming'), ''));
}
