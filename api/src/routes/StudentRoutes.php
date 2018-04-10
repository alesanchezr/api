<?php

namespace Routes;

class StudentRoutes{
    
    var $studentHandler = null;
    
    public function __construct($app, $scopes){
        

        /**
         * Everything Related to the student itself
         **/
        $this->studentHandler = new \StudentHandler($app);
        $app->get('/students/', array($this->studentHandler, 'getAllHandler'))->add($scopes(['super_admin']));
        $app->get('/student/{student_id}', array($this->studentHandler, 'getStudentHandler'))->add($scopes(['super_admin']));
        
        $app->post('/student/', array($this->studentHandler, 'createStudentHandler'))->add($scopes(['super_admin']));
        $app->post('/student/{student_id}', array($this->studentHandler, 'updateStudentHandler'))->add($scopes(['super_admin']));
        $app->delete('/student/{student_id}', array($this->studentHandler, 'deleteStudentHandler'))->add($scopes(['super_admin']));
        
        $app->get('/briefing/student/{student_id}', array($this->studentHandler, 'getStudentBriefing'))->add($scopes(['read_basic_info']));
        
        $app->post('/student/status/{student_id}', array($this->studentHandler, 'updateStudentStatus'))->add($scopes(['super_admin']));
        
        $this->actitiviesRoutes($app, $scopes);
        

    }
    
    private function actitiviesRoutes($app, $scopes){

        /**
         * Everything Related to the student activities
         **/
        $app->post('/activity/student/{student_id}', array($this->studentHandler, 'createStudentActivityHandler'))->add($scopes(['super_admin']));
        $app->get('/activity/student/{student_id}', array($this->studentHandler, 'getStudentActivityHandler'))->add($scopes(['super_admin']));
        $app->delete('/activity/{activity_id}', array($this->studentHandler, 'deleteStudentActivityHandler'))->add($scopes(['super_admin']));
    }
    

}