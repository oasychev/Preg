<?php
abstract class grader {
    
    // ���������������� � ��������, ���������� ������, ����
    // ������� ���������� ����� ��� �������� �������
    function has_tests() {
        return false;
    }
    
    // ���������� 1, ���� ���� ��� ������� ������� ����� ���� ����� ������
    // ����� 2
    function test_mode() {
        return 1;
    }
    
    // ����������� ����� ���������� ����������
    private $testresults;
    
    function set_test_mode($testMode) {
        if($testMode<0 || $testMode>3)
            return;
        $this->testMode=$testMode;
    }
    function get_test_mode() {
        return $this->testMode;
    }
    
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
    
    // ��������� ��������� ������ � ���� �������������� ������
    function validation($data, &$errors) {
        return null;
    }
    
    // ���������� ������ ������ $submission �� ������� $taskid ����������� poasassignment'a
    function evaluate($submission,$poasassignmentid,$taskid=-1) {
        return array();
    }
    
    // ���������� ���������� ������������ ������
    function show_result($resultmode) {
        // � ����������� �� ���������, ������� ����������:
        // * ������
        // * ���������� ��������/���������� ������
        // * �������� ���� ������
        // * ������� ����� ��������� ������� � ����������� �� ������
        // * ������� ������ ������
        // * ��������� ���������-�������
       
    }
    
    // ���������� ������ ������
    function show_tests($poasassignmentid,$taskid=-1){
    
    }

        //display_question_editing_page ?
        // save_question ?
        // save_question_options get_question_options ?
}