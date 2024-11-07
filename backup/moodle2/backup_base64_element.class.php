<?php

class backup_base64_element extends backup_final_element implements processable {

    public function get_value() {
        return base64_encode(parent::get_value());
    }

}
