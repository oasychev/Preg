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

/**
 * Unit tests for the shortanswer question type class.
 *
 * @package    qtype
 * @subpackage shortanswer
 * @copyright  2007 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../../config.php');

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/type/writeregex/questiontype.php');
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/vendor/phpunit/phpunit/PHPUnit/Framework/TestCase.php');


/**
 * Unit tests for the shortanswer question type class.
 *
 * @copyright  2007 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_writeregex_test extends PHPUnit_Framework_TestCase {
    var $qtype;

    function setUp() {
        $this->qtype = new qtype_writeregex();
        error_log('[setUp]', 3, 'writeregex_log.txt');
    }

    function tearDown() {
        $this->qtype = null;
        error_log('[tearDown]', 3, 'writeregex_log.txt');
    }

    function test_name() {
        error_log('[test_name]', 3, 'writeregex_log.txt');
        $this->assertEquals($this->qtype->name(), 'writeregex');
    }

    function test_get_question_options() {
        error_log('[test_get_question_options]', 3, 'writeregex_log.txt');

        global $DB;

        $DB->delete_records('qtype_writeregex_options');

        $record1 = $this->form_options(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $record2 = $this->form_options(2, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

        $DB->insert_record('qtype_writeregex_options', $record1);
        $DB->insert_record('qtype_writeregex_options', $record2);

        $this->assertEquals($DB->count_records('qtype_writeregex_options'), 2);

        $question = new stdClass();
        $question->id = 1;

        $new_question = $this->qtype->get_question_options($question);

        $this->assertEquals($new_question->options->id, 1);
        
    }

    function test_save_question_options() {
        error_log('[get_possible_responses]', 3, 'writeregex_log.txt');
        
        global $DB;

        $DB->delete_records('qtype_writeregex_options');

        $record1 = $this->generate_question_2(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $record2 = $this->generate_question_2(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

        $this->qtype->save_question_options($record1);
        $this->qtype->save_question_options($record2);

        $this->assertEquals($DB->count_records('qtype_writeregex_options'), 2);

    }

    function generate_question_2 ($questionid, $notation, $syntaxtreetype, $syntaxtreepenalty,
     $explgraphtype, $explgraphpenalty, $desctype, $descpenalty, $teststringtype, $teststringpenalty,
     $compareregex, $compareautomat) {

        $result = new stdClass();
        $result->id = $questionid;
        $result->wre_notation = $notation;
        $result->wre_st = $syntaxtreetype;
        $result->wre_st_penalty = $syntaxtreepenalty;
        $result->wre_eg = $explgraphtype;
        $result->wre_eg_penalty = $explgraphpenalty;
        $result->wre_d = $desctype;
        $result->wre_d_penalty = $descpenalty;
        $result->wre_td = $teststringtype;
        $result->wre_td_penalty = $teststringpenalty;
        $result->wre_cre_percentage = $compareregex;
        $result->wre_acre_percentage = $compareautomat;

        return $result;
    }

    private function form_options($id, $questionid, $notation, $syntaxtreetype, $syntaxtreepenalty,
     $explgraphtype, $explgraphpenalty, $desctype, $descpenalty, $teststringtype, $teststringpenalty,
     $compareregex, $compareautomat) {

        $result = new stdClass();

        $result->id = $id;
        $result->questionid = $questionid;
        $result->notation = $notation;
        $result->syntaxtreehinttype = $syntaxtreetype;
        $result->syntaxtreehintpenalty = $syntaxtreepenalty;
        $result->explgraphhinttype = $explgraphtype;
        $result->explgraphhintpenalty = $explgraphpenalty;
        $result->descriptionhinttype = $desctype;
        $result->descriptionhintpenalty = $descpenalty;
        $result->teststringshinttype = $teststringtype;
        $result->teststringshintpenalty = $teststringpenalty;
        $result->compareregexpercentage = $compareregex;
        $result->compareautomatercentage = $compareautomat;

        return $result;
    }

    function test_delete_question() {

        global $DB;

        $DB->delete_records('qtype_writeregex_options');

        $record1 = $this->form_options(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $record2 = $this->form_options(2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

        $DB->insert_record('qtype_writeregex_options', $record1);
        $DB->insert_record('qtype_writeregex_options', $record2);

        $this->assertEquals($DB->count_records('qtype_writeregex_options'), 2);

        $this->qtype->delete_question(1, 1);

        $this->assertEquals($DB->count_records('qtype_writeregex_options'), 0);

        $DB->delete_records('qtype_writeregex_options');

        $record1 = $this->form_options(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $record2 = $this->form_options(2, 2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);

        $DB->insert_record('qtype_writeregex_options', $record1);
        $DB->insert_record('qtype_writeregex_options', $record2);

        $this->assertEquals($DB->count_records('qtype_writeregex_options'), 2);

        $this->qtype->delete_question(1, 1);

        $this->assertEquals($DB->count_records('qtype_writeregex_options'), 1);


        error_log('[test_delete_question]', 3, 'writeregex_log.txt');
    }

    function test_generate_new_id() {
        error_log("[test_generate_new_id]\n", 3, 'writeregex_log.txt');

        // prepare test data
        global $DB;

        $DB->delete_records('qtype_writeregex_options');

        $record1 = $this->form_options(1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $new_id = $DB->insert_record('qtype_writeregex_options', $record1);
        $this->assertEquals($new_id, 1);

        $calc_id = $this->qtype->generate_new_id();
        $this->assertEquals($calc_id, 2);

        $record2 = $this->form_options(2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1);
        $new_id = $DB->insert_record('qtype_writeregex_options', $record2);
        $this->assertEquals($new_id, 2);

        $calc_id = $this->qtype->generate_new_id();
        $this->assertEquals($calc_id, 3);

        $DB->delete_records('qtype_writeregex_options', array('id' => 1));
        $calc_id = $this->qtype->generate_new_id();
        $this->assertEquals($calc_id, 1);
    }
}

?>
