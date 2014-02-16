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

$string['active']                               = 'Use this classroom in sessions?';
$string['active_help']                          = 'If enabled, you can start and plan new sessions in this classroom';
$string['activesessionsstudenttitle']           = 'You have {$a} active session(s)';
$string['activesessiontitle']                   = 'You have an active session';
$string['activestate']                          = 'Active';
$string['addclassroom']                         = 'Add a classroom ...';
$string['addclassroomnavbar']                   = 'Add classroom';
$string['addclassroompagetitle']                = 'Add classroom';
$string['addingnewclassroom']                   = 'Adding new classroom';
$string['addingnewlessontype']                  = 'Adding new lesson type';
$string['addingnewsession']                     = 'Plan new session';
$string['addlessontype']                        = 'Add a lesson type ...';
$string['addlessontypenavbar']                  = 'Add lesson type';
$string['addlessontypepagetitle']               = 'Add lesson type';
$string['addsessionpagetitle']                  = 'Plan new session';
$string['allclassrooms']                        = 'All classrooms';
$string['allgroups']                            = 'All groups';
$string['alllessontypes']                       = 'All lesson types';
$string['allstates']                            = 'All states';
$string['allusers']                             = 'All users';
$string['blocktitle']                           = 'Supervised';
$string['cannotdeleteclassroom']                = 'There were sessions in this classroom. Delete sessions using this classroom first. You can hide classroom';
$string['cannotdeletelessontype']               = 'You can not delete this lesson type, because it\'s used in sessions or quizzes';
$string['classroom']                            = 'Classroom';
$string['classroomsbreadcrumb']                 = 'Classrooms';
$string['classroomsdefinition']                 = 'Classrooms definition';
$string['classroomsdefinition_help']            = 'Any supervised sessions takes place somewhere - i.e. in the classroom. The classroom is defined by an IP subnet (ask you admin for IP range for you classroom, if you don\'t know what it is). Only students working on computers with specified IPs participate in supervised session, so students from the group can not cheat you acessing site from other places when you supervise their classmates in some classroom. Classrooms are acessible from all courses.';
$string['classroomsheader']                     = 'Classrooms list';
$string['classroomspagetitle']                  = 'Classrooms list';
$string['classroomsurl']                        = 'Classrooms';
$string['course']                               = 'Course';
$string['createclassroom']                      = 'To plan or start a session you must create (or make visible) at least one classroom!';
$string['deleteclassroomcheck']                 = 'Are you sure you want to completely delete this classroom from every course on the site?';
$string['deletelessontypecheck']                = 'Are you sure you want to completely delete this lesson type?';
$string['deletesessionnavbar']                  = 'Delete session?';
$string['duration']                             = 'Duration (min)';
$string['durationvalidationerror']              = 'Duration must be greater than zero value.';
$string['editclassroomnavbar']                  = 'Edit classroom';
$string['editclassroompagetitle']               = 'Edit classroom';
$string['editingclassroom']                     = 'Editing classroom';
$string['editinglessontype']                    = 'Editing lesson type';
$string['editingsession']                       = 'Editing session';
$string['editlessontypenavbar']                 = 'Edit lesson type';
$string['editlessontypepagetitle']              = 'Edit lesson type';
$string['editsessionnavbar']                    = 'Edit session';
$string['editsessionpagetitle']                 = 'Edit planned session';
$string['emaileditedsession']                   = 'Hi {$a->teachername},

Your session at \'{$a->sitename}\' has been edited.
Editor:        {$a->editorname}

Updated session information:
Course:         {$a->course}
Classroom:      {$a->classroom}
Group:          {$a->group}
Lesson type:    {$a->lessontype}
Time start:     {$a->timestart}
Duration (min): {$a->duration}
Time end:       {$a->timeend}

{$a->comment}

You can edit this session: {$a->editurl}
Or you can delete it: {$a->deleteurl}

Have a nice day!';
$string['emaileditedsessionsubject']            = '{$a->sitename}: session has been edited on {$a->timestart}';
$string['emailnewsession']                      = 'Hi {$a->teachername},

A new session has been created for you at \'{$a->sitename}\'.
Creator:        {$a->creatorname}

Course:         {$a->course}
Classroom:      {$a->classroom}
Group:          {$a->group}
Lesson type:    {$a->lessontype}
Time start:     {$a->timestart}
Duration (min): {$a->duration}
Time end:       {$a->timeend}

{$a->comment}

You can edit this session: {$a->editurl}
Or you can delete it: {$a->deleteurl}

Have a nice day!';
$string['emailnewsessionsubject']               = '{$a->sitename}: new session on {$a->timestart}';
$string['emailremovedsession']                  = 'Hi {$a->teachername},

Your session at \'{$a->sitename}\' has been removed.
Remover:        {$a->removername}

Session state:  {$a->state}
Course:         {$a->course}
Classroom:      {$a->classroom}
Group:          {$a->group}
Lesson type:    {$a->lessontype}
Time start:     {$a->timestart}
Duration (min): {$a->duration}
Time end:       {$a->timeend}

{$a->comment}

{$a->custommessage}

Have a nice day!';
$string['emailremovedsessionmsg']               = 'A person who removed this session leaved a message for you:
--------------------------------------------------
{$a}
--------------------------------------------------
';
$string['emailremovedsessionsubject']           = '{$a->sitename}: session has been removed on {$a->timestart}';
$string['emailsessioncomment']                  = 'Session comment:
--------------------------------------------------
{$a}
--------------------------------------------------
';
$string['enrollteacher']                        = 'To plan a session you must enroll at least one user with ability to supervise sessions to the course!';
$string['filterlogsbyuser']                     = 'Filter logs by user';
$string['finishedstate']                        = 'Finished';
$string['finishsession']                        = 'Finish session';
$string['gotoclassrooms']                       = 'Go to classrooms page';
$string['gotoenrollment']                       = 'Go to user enrollment page';
$string['group']                                = 'Group';
$string['increaseduration']                     = 'Session time end must be greater than current time.';
$string['insertclassroomerror']                 = 'Database error! Can not insert classroom into database';
$string['insertlessontypeerror']                = 'Database error! Can not insert lesson type into database';
$string['insertsessionerror']                   = 'Database error! Can not insert session into database';
$string['invalidclassroomid']                   = 'You are trying to use an invalid classroom ID';
$string['invalidlessontypeid']                  = 'You are trying to use an invalid lesson type ID';
$string['invalidsessionid']                     = 'You are trying to use an invalid session ID';
$string['iplist']                               = 'IP list';
$string['iplist_help']                          =
'IP list is a comma separated string of subnet definitions.

Subnet strings can be in one of those formats:

* xxx.xxx.xxx.xxx     (full IP address)

* xxx.xxx.xxx.xxx/nn  (number of bits in net mask)

* xxx.xxx.xxx.xxx-yyy (a range of IP addresses in the last group)

* xxx.xxx or xxx.xxx. (incomplete address)';
$string['lessontype']                           = 'Lesson type';
$string['lessontypesbreadcrumb']                = 'Lesson types';
$string['lessontypesdefinition']                = 'Lesson types definition';
$string['lessontypesdefinition_help']           = 'Consider you have several different lesson types in the courses (i.e. exam, colloquim etc), and want certaing things only on certain lessons (i.e. exam quiz shoudn\'t be accessible on colloquim). You can use lesson types to sort them out. Session creates always for some lesson type. You can limit quiz acessibility and other features to the sessions of certain lesson type. Unlike classrooms, lesson types created separately for each course.';
$string['lessontypespagetitle']                 = 'Lesson types';
$string['lessontypesurl']                       = 'Lesson types';
$string['lessontypesview']                      = 'Lesson types in current course';
$string['logs']                                 = 'Logs';
$string['logsbreadcrumb']                       = 'Session logs';
$string['logspagetitle']                        = 'Session logs';
$string['logsview']                             = 'Session logs';
$string['messageforteacher']                    = 'Message for the teacher';
$string['nosessionsstudenttitle']               = 'You don\'t have any active session...';
$string['nosessionstitle']                      = 'You don\'t have any planned or active sessions. You can start new session right now';
$string['notifyteacher']                        = 'Notify teacher by e-mail';
$string['notifyteacher_help']                   = 'If checked, the teacher will be notified about session removing. You can add message for the teacher';
$string['notspecified']                         = 'Not specified';
$string['pagesizevalidationerror']              = 'Page size must be greater than zero value.';
$string['plannedsessiontitle']                  = 'You have a planned session';
$string['plannedstate']                         = 'Planned';
$string['plansession']                          = 'Plan a new session ...';
$string['plansessionnavbar']                    = 'Plan session';
$string['pluginname']                           = 'Supervised';
$string['savechanges']                          = 'Save changes';
$string['sendemail']                            = 'Send e-mail';
$string['sendemail_help']                       = 'If checked, selected teacher will be notified about creation, removing and any changes in his session. If the teacher will be changed, both will be notified.';
$string['sessioncomment']                       = 'Comment';
$string['sessiondeleteerror']                   = 'You can\'t delete active session. End session first.';
$string['sessiondeleteheader']                  = 'Do you want to delete this session?';
$string['sessiondeletetitle']                   = 'Delete session';
$string['sessionediterror']                     = 'You can edit only planned sessions';
$string['sessionendsbefore']                    = 'Session ends before';
$string['sessioninfo']                          = 'Session information';
$string['sessionlogserror']                     = 'You can\'t view logs of planned session - there is nothing to view';
$string['sessionsbreadcrumb']                   = 'Sessions';
$string['sessionsdefinition']                   = 'Sessions definition';
$string['sessionsdefinition_help']              = 'The course teachers creates sessions specifying the group, lesson type (e.g. laboratory work, exam, etc.), classroom and duration. After that students will be able to start quizzes from this course according next conditions:

- the session is active;

- student is in a group for which the session was created;

- student is in session\'s classroom (the teacher can specify the ip subnet for each classroom);

- the session was created for the lesson type which is specified for the quiz (go to quiz settings -> Extra restrictions on attempts).';
$string['sessionsheader']                       = 'Sessions list';
$string['sessionspagetitle']                    = 'Sessions';
$string['sessionstartsafter']                   = 'Session starts after';
$string['sessionsurl']                          = 'Sessions';
$string['showlogs']                             = 'Show logs';
$string['showlogsbutton']                       = 'Show logs';
$string['showsessions']                         = 'Show sessions';
$string['startsession']                         = 'Start session';
$string['state']                                = 'State';
$string['supervised:addinstance']               = 'Add a new Supervised block';
$string['supervised:besupervised']              = 'Participate in the supervised session (intended for students etc)';
$string['supervised:editclassrooms']            = 'Edit classrooms';
$string['supervised:editlessontypes']           = 'Edit lesson types';
$string['supervised:manageallsessions']         = 'Manage all sessions: plan, edit and remove unfinished sessions';
$string['supervised:managefinishedsessions']    = 'Remove finished sessions';
$string['supervised:manageownsessions']         = 'Manage own sessions: plan, edit and remove unfinished sessions';
$string['supervised:supervise']                 = 'Ability to supervised sessions: start planned and new ones, edit and finish active ones, view logs.';
$string['supervised:viewallsessions']           = 'View all sessions (planned, active and finished) and their logs';
$string['supervised:viewownsessions']           = 'View own sessions (planned, active and finished) and their logs';
$string['supervisedsettings']                   = 'Supervised settings';
$string['teacher']                              = 'Teacher';
$string['teacherhassession']                    = 'Teacher already has a session in this time.';
$string['teachervalidationerror']               = 'You can plan session only for yourself.';
$string['timeend']                              = 'Time end';
$string['timeendvalidationerror']               = 'The session must be active before {$a} at least.';
$string['timestart']                            = 'Time start';
$string['timetovalidationerror']                = 'Session\'s end must be greater or equal to session\'s start.';
$string['unknownstate']                         = 'Unknown';
$string['updatesession']                        = 'Update';