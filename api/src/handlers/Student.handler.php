<?php

use Slim\Http\Request as Request;
use Slim\Http\Response as Response;
use Carbon\Carbon;
use Helpers\BCValidator;
use Helpers\AuthHelper;
use Helpers\Mailer;
use Helpers\ArgumentException;

class StudentHandler extends MainHandler{
    
    protected $slug = 'Student';
    
    public function getStudentHandler(Request $request, Response $response) {
        $breathecodeId = $request->getAttribute('student_id');
        
        $user = User::find($breathecodeId);
        if(!$user or !$user->student) throw new ArgumentException('Invalid student_id');
        
        return $this->success($response,$user->student);
    }
    
    public function updateStudentStatus(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParsedBody();
        
        $updated = false;
        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);

        if(!empty($data['status'])){
            if(!in_array($data['status'], ['currently_active', 'under_review', 'blocked', 'studies_finished', 'student_dropped']))
                throw new ArgumentException('Invalid student status: '.$data['status']);
                
            $updated = true;
            $student->status = $data['status'];
        }
        
        if(!empty($data['financial_status'])){
            if(!in_array($data['financial_status'], ['fully_paid', 'up_to_date', 'late', 'uknown']))
                throw new ArgumentException('Invalid student finantial status: '.$data['financial_status']);
            
            $updated = true;
            $student->financial_status = $data['financial_status'];
        }

        if($updated) $student->save();
        else throw new ArgumentException('You have to specify either the financial_status or status.');
        
        return $this->success($response,$student);
    }
    
    public function getStudentActivityHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParams();
        
        $limit = 10; 
        if(isset($data["limit"])) $limit = $data["limit"];
        $skip = 0; 
        if(isset($data["start"])) $skip = $data["start"];
        
        $activities = Activity::where('student_user_id', $studentId)->orderBy('created_at', 'desc')->skip($skip)->take($limit)->get();
        if(!$activities) throw new ArgumentException('Invalid student id:'.$studentId);
        
        return $this->success($response,$activities);
    }
    
    public function getStudentBriefing(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        
        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);
        
        $acumulatedPoints = $this->app->db->table('activities')->where([
            'activities.student_user_id' => $studentId
        ])->sum('activities.points_earned');
        $result['acumulated_points'] = $acumulatedPoints;
        
        $creation = $student->created_at;
        $result['creation_date'] = $creation->format('Y-m-d');

        $count = $this->_daysBetween($creation);
        $result['days'] = $count;
        
        $profile = Profile::find(1);
        $result['profile'] = $profile;
        
        return $this->success($response,$result);
    }
    
    public function createStudentHandler(Request $request, Response $response) {
        $data = $request->getParsedBody();

        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');
        
        $cohort = Cohort::where('slug', $data['cohort_slug'])->first();
        if(!$cohort) throw new ArgumentException('Invalid cohort slug');
        
        $user = User::where('username', $data['email'])->first();
        if($user && $user->student) throw new ArgumentException('There is already a student with this email on te API');
        else if(!$user)
        {
            $user = new User;
            $user->username = $data['email'];
            $user->type = 'student';
            $user->full_name = $data['full_name'];
            $user = $this->setOptional($user,$data,'wp_id');
            $user->save();
        }

        if($user)
        {
            $user = $this->setOptional($user,$data,'full_name');
            $user = $this->setOptional($user,$data,'avatar_url');
            $user = $this->setOptional($user,$data,'bio');
            $user->save();
            
            $student = new Student();
            $student = $this->setOptional($student,$data,'total_points');
            $student = $this->setOptional($student,$data,'phone');
            $student = $this->setOptional($student,$data,'github');
            $student = $this->setOptional($student,$data,'internal_profile_url');
            $user->student()->save($student);
            $student->cohorts()->save($cohort);
            
            $this->_sendUserInvitation($user);
            
        }
        
        return $this->success($response,$student);
    }
    
    private function _sendUserInvitation($user){
        
        $token = new Passtoken();
        $token->token = md5(AuthHelper::randomToken());
        $token->user()->associate($user);
        $token->save();
        
        $mailer = new Mailer();
        $callback = ($user->type == 'student') ? STUDENT_URL.'/login' : ADMIN_URL;
        $result = $mailer->sendAPI("invite", [
            "email"=> $user->username, 
            "url"=> ASSETS_URL.'/apps/remind/?invite=true&id='.$user->id.'&t='.$token->token.'&callback='.base64_encode($callback)
        ]);
        
    }
    
    public function updateStudentHandler(Request $request, Response $response) {
        
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParsedBody();
        
        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);

        if($data['email']) throw new ArgumentException('Students emails cannot be updated through this service');
        
        $user = $student->user;
        $user = $this->setOptional($user,$data,'full_name');
        $user = $this->setOptional($user,$data,'avatar_url');
        $user = $this->setOptional($user,$data,'description');
        $user->save();
        $student = $this->setOptional($student,$data,'internal_profile_url');
        $student = $this->setOptional($student,$data,'total_points');
        $student = $this->setOptional($student,$data,'github');
        $student = $this->setOptional($student,$data,'phone');
        $student->save();
        
        return $this->success($response,$student);
    }
    
    public function createStudentActivityHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        $data = $request->getParsedBody();

        if(empty($data)) throw new ArgumentException('There was an error retrieving the request content, it needs to be a valid JSON');
        
        $badge = Badge::where('slug', $data['badge_slug'])->first();
        if(!$badge) throw new ArgumentException('Invalid badge slug: '.$data['badge_slug']);
        
        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id: '.$studentId);
        
        if(!in_array($data['type'],Activity::$possibleTypes))  throw new ArgumentException('Invalid activity type: '.$data['type']);
        
        if(empty($data['points_earned'])) throw new ArgumentException('It seems you are trying to give 0 or NULL points to the student');
        
        $activity = new Activity();
        $activity = $this->setMandatory($activity,$data,'type');
        $activity = $this->setMandatory($activity,$data,'name', BCValidator::DESCRIPTION);
        $activity = $this->setMandatory($activity,$data,'description', BCValidator::DESCRIPTION);
        $activity = $this->setMandatory($activity,$data,'points_earned', BCValidator::POINTS);

        $activity->student()->associate($student);
        $activity->badge()->associate($badge);
        $activity->save();
        
        
        $student->updateBasedOnActivity();
        
        $activity->makeHidden(["student"]);
        
        return $this->success($response,$activity);
    }
    
    public function deleteStudentActivityHandler(Request $request, Response $response) {
        $activityId = $request->getAttribute('activity_id');
        
        $activity = Activity::find($activityId);
        if(!$activity) throw new ArgumentException('Invalid activity id');
        
        $attributes = $activity->getAttributes();
        $now = time(); // or your date as well
        $daysOld = floor(($now - strtotime($attributes['created_at'])) / DELETE_MAX_DAYS);
        if($daysOld>5) throw new ArgumentException('The activity is to old to delete');
        
        $student = $activity->student()->first();
        $activity->delete();
        $student->updateBasedOnActivity();
        
        return $this->success($response,"ok");
    }
    
    public function deleteStudentHandler(Request $request, Response $response) {
        $studentId = $request->getAttribute('student_id');
        
        $student = Student::find($studentId);
        if(!$student) throw new ArgumentException('Invalid student id');
        
        $attributes = $student->getAttributes();
        $now = time(); // or your date as well
        $daysOld = floor(($now - strtotime($attributes['created_at'])) / DELETE_MAX_DAYS);
        if($daysOld>5) throw new ArgumentException('The student is to old to delete');
        
        $student->activities()->delete();
        $student->badges()->delete();
        $student->delete();
        
        return $this->success($response,"ok");
    }
    
    private function _daysBetween($date1, $date2=null){
        
        if(!$date2) $date2 = Carbon::now('America/Vancouver');
        return $date1->diffInDays($date2);
    }
    
}