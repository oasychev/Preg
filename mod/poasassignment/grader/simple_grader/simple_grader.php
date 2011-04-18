<?php
require_once(dirname(dirname(__FILE__)).'\grader\grader.php');

class simple_grader extends grader{
    
    public function get_test_mode() {
        return POASASSIGNMENT_GRADER_COMMON_TEST;
    }
    
    public function name() {
        return 'simple grader';
    }
    public static function validation($data, &$errors) {
        
    }
    
    public function test_attempt($attemptid, $taskid) {
    }
    
    // ����������� ����� ���������� ���������� (������ simple_test_result'��)
    private $testresults;
    private $successfultestscount;
    
    public function show_result($options) {
        //TODO: �������� ����� ��� ����� ����� �������, ��������� ����� ����� ���������� ��� �����
        $html = "";
        if($options & POASASSIGNMENT_GRADER_SHOW_RATING) 
            $html += "<br>Rating : ".(100 * $successfultestscount / count($testresults));
        if($options & POASASSIGNMENT_GRADER_SHOW_NUMBER_OF_PASSED_TESTS)
            $html += "<br>Passed tests : ".$successfultestscount;
        
        foreach ($testresults as $testresult) {
            if($options & POASASSIGNMENT_GRADER_SHOW_TESTS_NAMES)
                $html += "<br>".$testresult->testname;
            if($options & POASASSIGNMENT_GRADER_SHOW_TEST_INPUT_DATA)
                $html += "<br>".$testresult->testinputdata;
        }
    }
    
    // TODO: ������ � ������� ����� ��� ������� �����
        
    // �������������� ������( �������� �� ���������� ����� ������ � �������������� ������������)
    function edit_tests($tests) {
        return null;
    }
    // ��������� ����
    function turn_off_test($testid) {
        return null;
    }
    // ��������� ������� ����
    function delete_test($testid) {
        return null;
    }
    
    // ������� ������
    function tests_export($exportParams) {
        return null;
    }
    
    // ������ ������
    function tests_import($importParams) {
        return null;
    }
        
    // ���������� ������ ������ $submission �� ������� $taskid ����������� poasassignment'a
    function evaluate($submission,$poasassignmentid,$taskid=-1) {
        return array();
    }
        
    // ���������� ������ ������
    function show_tests($poasassignmentid,$taskid=-1){
    
    }
}