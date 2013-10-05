<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

function save_rules($quizid, $lessontypes) {
    global $DB;
    
    $rules = $DB->get_records('quizaccess_supervisedcheck', array(quizid=>$quizid));
    
    // Count of existing records more or equal than count of new records.
    if(count($rules) >= count($lessontypes)) {
        $remove_count = count($rules) - count($lessontypes);
        // Remove unnecessary records from DB.
        $keys = array_keys($rules);
        for ($i=0; $i<$remove_count; $i++) {
            $DB->delete_records('quizaccess_supervisedcheck', array('id'=>$keys[$i]));
            unset($rules[$keys[$i]]);   // Remove from local array.
        }
        // Now we have equal numbers of records. Just update all of them in DB.
        $i = 0;
        foreach ($rules as $id=>$record) {
            $record->lessontypeid = $lessontypes[$i];        
            $DB->update_record('quizaccess_supervisedcheck', $record);
            $i++;
        }
    }
    else{
        // Update existing records.
        $i = 0;
        foreach ($rules as $id=>$record) {
            $record->lessontypeid = $lessontypes[$i];        
            $DB->update_record('quizaccess_supervisedcheck', $record);
            $i++;
        }
        // Add new records.
        for($i; $i<count($lessontypes); $i++){
            $record = new stdClass();
            $record->quizid        = $quizid;
            $record->lessontypeid  = $lessontypes[$i];
            $DB->insert_record('quizaccess_supervisedcheck', $record);
        }
    }
    
}

class block_supervised extends block_base {
    public function init() {
        $this->title = get_string('supervised', 'block_supervised');
    }

    public function get_content() {
        global $DB;

        if ($this->content !== null) {
            return $this->content;
        }
        
        /*$quizid = 5;
        $lessontypes = array();
        save_rules($quizid, $lessontypes);*/
        
        
        $this->content         =  new stdClass;
        $this->content->text   = 'The content of supervised block!';
        $this->content->footer = 'Footer here...';
        

        return $this->content;
    }
}