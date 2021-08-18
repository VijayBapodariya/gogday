<?php
namespace App\Repository;

use DB;
use Auth;
use App\Tip;
use App\Code;
use App\Goal;
use App\Image;
use App\State;
use App\Client;
use App\Trauma;
use App\MenuLink;
use App\ScaleTip;
use App\UserMenu;
use Carbon\Carbon;
use App\ClientPoint;
use App\SleepTracker;
use App\Subscription;
use App\ClientMoodMark;
use App\ExerciseTracker;
use App\ApiScaleQuestion;
use App\TraumaCopingCart;
use App\ApiUserScaleAnswer;
use App\ExerciseTrackerPoint;
use App\ApiScaleQuestionAnswer;
use App\GratitudeQuestionAnswer;
use App\Repository\Interfaces\GeneralRepositoryInterface;

class GeneralRepository implements GeneralRepositoryInterface
{
    private $tip, $trauma, $menu, $image, $question, $answer, $subscription, $exercise, $exercise_point, $state, $code, $goal, $user_scale_answer,
            $mood_mark, $trauma_copying, $scale_question_answer, $scale_tips, $sleep_tracker, $points, $gratitude_answer, $client, $user_menu;

    public function __construct(
        Tip $tip,
        Trauma $trauma,
        MenuLink $menu,
        Image $image,
        ApiScaleQuestion $question,
        ApiUserScaleAnswer $answer,
        Subscription $subscription,
        ClientMoodMark $mood_mark,
        TraumaCopingCart $trauma_copying,
        ApiScaleQuestionAnswer $scale_question_answer,
        ScaleTip $scale_tips,
        SleepTracker $sleep_tracker,
        ClientPoint $points,
        GratitudeQuestionAnswer $gratitude_answer,
        Client $client,
        ExerciseTracker $exercise,
        ExerciseTrackerPoint $exercise_point,
        State $state,
        UserMenu $user_menu,
        Code $code,
        Goal $goal,
        ApiUserScaleAnswer $user_scale_answer
    )
    {
        $this->tip = $tip;
        $this->trauma = $trauma;
        $this->menu = $menu;
        $this->image = $image;
        $this->question = $question;
        $this->answer = $answer;
        $this->subscription = $subscription;
        $this->mood_mark = $mood_mark;  
        $this->trauma_copying = $trauma_copying;
        $this->scale_question_answer = $scale_question_answer;
        $this->scale_tips = $scale_tips;
        $this->sleep_tracker = $sleep_tracker;
        $this->points = $points;
        $this->gratitude_answer = $gratitude_answer;
        $this->client = $client;
        $this->exercise = $exercise;
        $this->exercise_point = $exercise_point;
        $this->state = $state;
        $this->user_menu = $user_menu;
        $this->code = $code;
        $this->goal = $goal;
        $this->user_scale_answer = $user_scale_answer;
    }

    public function getTips()
    {
        return $this->tip->all();
    }

    public function getTraumas()
    {
        return $this->trauma->all();
    }

    public function getMenuLinks($request = '')
    {
        if (isset($request->id) && $request->id > 0) {
            return $this->menu->where('id', $request->id)->get();
        } else {
            return $this->menu->all();
        }
    }

    public function getImages()
    {
        return $this->image->all();
    }

    public function getQuestions()
    {
        return $this->question->all();
    }

    public function getAnswerScale($user_id)
    {
        $set_no = $this->answer->where('user_id', $user_id)->orderBy('set_no', 'DESC')->first();
        if (!empty($set_no)) {
            return $this->answer->where('user_id', $user_id)->where('set_no', $set_no->set_no)->get();
        }

        return [];
    }

    public function storeAnswer($data)
    {
        $totalScore = 0;
        $max = $this->answer->max('set_no');
        if (empty($max)) {
            $max = 0;
        }

        $max += 1;
        
        foreach ($data['answer_id'] as $key => $value) {
            $answer = new $this->answer;
            $answer->answer_id = $value;
            $answer->user_id = Auth::user()->id;
            $answer->set_no = $max;
            $answer->save();

            $score = $this->scale_question_answer->find($value);
            $totalScore = $totalScore + $score->score;
        }
        
        $tips = $this->scale_tips->where('min_value', '<=', $totalScore)->where('max_value', '>=', $totalScore)->where('lflag', $data['lflag'])->first();

        return [ 'tbl_score' => [ [ 'score' => $totalScore ] ], 'details' => [  $tips ] ];
    }

    public function getSubsciptions()
    {
        return $this->subscription->all();
    }

    /*public function storeMoodMarks($data)
    {
        $cnt = $this->mood_mark->max('set_no');
        if (empty($cnt)) {
            $cnt = 0;
        }

        $cnt += 1;

        if ((!empty($data['lower_mood_id']) && empty($data['mood_id'])) || (count($data['lower_mood_id']) > count($data['mood_id']))) {
            if (!empty($data['lower_mood_id'])) {
                foreach ($data['lower_mood_id'] as $key => $value) {
                    $mood = new $this->mood_mark;
                    $mood->mood_id = (isset($data['mood_id'][$key]) ? $data['mood_id'][$key] : 0);
                    $mood->lower_mood_id = $value;
                    $mood->marks = $data['marks'];
                    $mood->lower_marks = $data['lower_marks'];
                    $mood->date = $data['date'];
                    $mood->set_no = $cnt;
                    $mood->save();
                }
            }
        } else {
            if (!empty($data['mood_id'])) {
                foreach ($data['mood_id'] as $key => $mood_id) {
                    $mood = new $this->mood_mark;
                    $mood->mood_id = $mood_id;
                    $mood->lower_mood_id = (isset($data['lower_mood_id'][$key]) ? $data['lower_mood_id'][$key] : 0);
                    $mood->marks = $data['marks'];
                    $mood->lower_marks = $data['lower_marks'];
                    $mood->date = $data['date'];
                    $mood->set_no = $cnt;
                    $mood->save();
                }
            }
        }

        if (!empty($mood)) {
            $cnt = $this->points->whereDate('created_at', Carbon::now()->format('Y-m-d'))->where('client_id', Auth::user()->id)->where('rankable_type', get_class($mood))->count();

            if ($cnt == 0) {
                $points = new $this->points;
                $points->client_id = Auth::user()->id;
                $points->rankable_type = get_class($mood);
                $points->rankable_id = $mood->id;
                $points->points = 0.25;
                $points->save();
            }
        }

        return true;

    }*/
    public function storeMoodMarks($data)
    {
        $cnt = $this->mood_mark->max('set_no');
        if (empty($cnt)) {
            $cnt = 0;
        }

        $cnt += 1;

        if ((!empty($data['lower_mood_id']) && empty($data['mood_id'])) || (count($data['lower_mood_id']) > count($data['mood_id']))) {
            if (!empty($data['lower_mood_id'])) {
                foreach ($data['lower_mood_id'] as $key => $value) {
                    $mood = new $this->mood_mark;
                    $mood->mood_id = (isset($data['mood_id'][$key]) ? $data['mood_id'][$key] : 0);
                    $mood->lower_mood_id = $value;
                    $mood->marks = $data['marks'];
                    $mood->lower_marks = $data['lower_marks'];
                    $mood->mood_location_option = $data['selectedLocationtype'];
                    $mood->date = $data['date'];
                    $mood->set_no = $cnt;
                    $mood->save();
                }
            }
        } else {
            if (!empty($data['mood_id'])) {
                foreach ($data['mood_id'] as $key => $mood_id) {
                    $mood = new $this->mood_mark;
                    $mood->mood_id = $mood_id;
                    $mood->lower_mood_id = (isset($data['lower_mood_id'][$key]) ? $data['lower_mood_id'][$key] : 0);
                    $mood->marks = $data['marks'];
                    $mood->lower_marks = $data['lower_marks'];
                    $mood->mood_location_option = $data['selectedLocationtype'];
                    $mood->date = $data['date'];
                    $mood->set_no = $cnt;
                    $mood->save();
                }
            }
        }

        if (!empty($mood)) {
            $cnt = $this->points->whereDate('created_at', Carbon::now()->format('Y-m-d'))->where('client_id', Auth::user()->id)->where('rankable_type', get_class($mood))->count();

            if ($cnt == 0) {
                $points = new $this->points;
                $points->client_id = Auth::user()->id;
                $points->rankable_type = get_class($mood);
                $points->rankable_id = $mood->id;
                $points->points = 0.25;
                $points->save();
            }
        }

        return true;

    }

    public function getTraumaCopyingCart($request)
    {
        return $this->trauma_copying->where([ 'lflag' => $request->lflag, 'trauma_id' => $request->trauma_id ])->get();
    }

    public function storeSleepTracker($data)
    {

        $start = Carbon::parse($data['from']);
        $end = Carbon::parse($data['to']);
        $sleep = $end->diffInMinutes($start);
        
        $age = Carbon::parse(Auth::user()->birth_date)->diff(\Carbon\Carbon::now())->format('%y');
        $age = 18;
        if ($data['type'] == 'High') {
            $sleep = ( $sleep * 100 ) / 100; 
        } else if ($data['type'] == 'Moderate') {
            $sleep = ( $sleep * 80 ) / 100;
        } else {
            $sleep = ( $sleep * 60 ) / 100;
        }
        if ($age <= 15) {
            $depth = $sleep - 540;
        } else if ($age >=16 && $age <= 55) {
            $depth = $sleep - 480;
        } else {
            $depth = $sleep - 420;
        }

        $sleep_tracker = new $this->sleep_tracker;
        $sleep_tracker->client_id = Auth::user()->id;
        $sleep_tracker->date = Carbon::now()->format('Y-m-d');
        $sleep_tracker->from = $data['from'];
        $sleep_tracker->to = $data['to'];
        $sleep_tracker->depth = $depth;
        $sleep_tracker->type = $data['type'];
        $sleep_tracker->save();

        $cnt = $this->points->whereDate('created_at', Carbon::now()->format('Y-m-d'))->where('client_id', Auth::user()->id)->where('rankable_type', get_class($sleep_tracker))->count();

        if ($cnt == 0) {
            $points = new $this->points;
            $points->client_id = Auth::user()->id;
            $points->rankable_type = get_class($sleep_tracker);
            $points->rankable_id = $sleep_tracker->id;
            $points->points = 0.25;
            $points->save();
        }

        return sprintf("%02d", intdiv($depth, 60)).' Hours '. sprintf("%02d", (abs($depth) % 60)). ' Minutes';
    }

    public function storeGratitudeAnswer($data)
    {
        $resp = '';
        DB::transaction(function () use ($data, &$resp) {
            $set_no = $this->gratitude_answer->max('set_no');
            if (empty($set_no)) {
                $set_no = 1;
            } else {
                $set_no += 1;
            }
            $score = 0;
            for ($i = 1; $i <= 4; $i++) {
                if (isset($data['answer'.$i]) && !empty($data['answer'.$i])) {
                    $score += 0.25;  
                }
            }
            for ($i = 1; $i <= 4; $i++) {
                $gratitude_answer = new $this->gratitude_answer;
                $gratitude_answer->question = $data['question'.$i];
                $gratitude_answer->answer = (isset($data['answer'.$i]) ? $data['answer'.$i] : '');
                $gratitude_answer->score = $score;
                $gratitude_answer->set_no = $set_no;
                $gratitude_answer->client_id = Auth::user()->id;
                $gratitude_answer->save();
                $resp = $gratitude_answer;
            }

            
            $cnt = $this->points->whereDate('created_at', Carbon::now()->format('Y-m-d'))->where('rankable_type', get_class($gratitude_answer))->where('client_id', Auth::user()->id)->count();
            
            if ($cnt == 0) {
                $points = new $this->points;
                $points->client_id = Auth::user()->id;
                $points->rankable_type = get_class($gratitude_answer);
                $points->rankable_id = $gratitude_answer->id;
                $points->points = 0.25;
                $points->save();
            }

        });

        return $resp->score;
    }

    public function getInstitueList()
    {
        $month = Carbon::now()->month;

        $clients = $this->client->select('id')->where('user_id', Auth::user()->user_id)->get();
        $points = DB::table('client_points')
            ->select(DB::raw('SUM(client_points.points) as points, clients.name'))
            ->join('clients', 'clients.id', 'client_points.client_id')
            ->whereIn('client_points.client_id', $clients->pluck('id')->toArray())
            ->whereMonth('client_points.created_at', $month)->groupBy('client_id')->orderBy('points', 'DESC')->get()->take(10);

        // $points = $this->points->selectRaw('SUM(points) as points')->addSelect('clients.name')->whereIn('client_id', $clients)->whereMonth('created_at', $month)->groupBy('client_id')->get()->take(10);
        return $points->map(function($key, $value) {
            $key->rank = $value + 1;
            return $key;
        });
    }

    public function storeExerciseTracker($data)
    {
        $exercise = new $this->exercise;
        $exercise->client_id = Auth::user()->id;
        $exercise->start_time = $data['start_time'];
        $exercise->end_time = $data['end_time'];
        $exercise->exercise_type = $data['exercise_type'];
        $exercise->date = $data['date'];
        $exercise->score = 0;
        $exercise->save();

        $total_physical = 0;
        $total_technical = 0;
        $today_excericses = $this->exercise->whereDate('date', $data['date'])->get();
        foreach ($today_excericses as $exc) {
            $startTime = Carbon::parse($data['date'].$exc->start_time);
            $finishTime = Carbon::parse($data['date'].$exc->end_time);
            if ($exc->exercise_type == 'Physical') {
                $total_physical += $finishTime->diffInMinutes($startTime);
            } else {
                $total_technical += $finishTime->diffInMinutes($startTime);
            }
        }

        $total_points = 0;
        if ($total_physical >= 20) {
            $total_points += 0.5;
        } 
        if ($total_technical >= 20) {
            $total_points += 0.5;
        }

        if ($total_points > 0) {
            $exercise_point = $this->exercise_point->where([ 'date' => $data['date'], 'client_id' => Auth::user()->id ])->first();
            if (empty($exercise_point)) {
                $exercise_point = new $this->exercise_point;
                $exercise_point->date = $data['date'];
                $exercise_point->client_id = Auth::user()->id;
            }

            $exercise_point->points = $total_points;
            $exercise_point->save();
        }
        
        $cnt = $this->points->whereDate('created_at', Carbon::now()->format('Y-m-d'))->where('client_id', Auth::user()->id)->where('rankable_type', get_class($exercise))->count();
            
        if ($cnt == 0) {
            $points = new $this->points;
            $points->client_id = Auth::user()->id;
            $points->rankable_type = get_class($exercise);
            $points->rankable_id = $exercise->id;
            $points->points = 0.25;
            $points->exercise_type = $data['exercise_type'];
            $points->save();
        }

        return $total_points;
    }

    public function getState()
    {
        return $this->state->all();
    }

    /*public function getMoodMarks($data)
    {

        if ($data['flag'] == 1) {

            $start_date = Carbon::parse($data['start_date']);
            $end_date = Carbon::parse($data['end_date']);

            $diff_in_days = $end_date->diffInDays($start_date);
            if ($diff_in_days > 6) {
                $start_date = $end_date->subDays(6);
            }

            $marks = $this->mood_mark->select('marks', 'lower_marks', 'date')->where('date', '>=', $start_date->format('Y-m-d'))->where('date', '<=', $data['end_date'])->where('client_id', Auth::user()->id)->groupBy('set_no')->get();
            $marks = $marks->groupBy('date')->map(function ($row) {
                $data = [];
                $data['marks'] = $row->sum('marks');
                $data['lower_marks'] = $row->sum('lower_marks');
                $data['date'] = $row[0]->date;
                return $data;
            })->values();
            return $marks;
        } else {
            $start_date = Carbon::parse($data['start_date']);
            // $end_date = $start_date->subDays(10)->format('Y-m-d');
            $start_date = Carbon::parse($data['start_date'])->format('Y-m-d');
            $marks = $this->mood_mark->where('date', $start_date)->where('client_id', Auth::user()->id)->groupBy('set_no')->get();
            return [ 'marks' => $marks->sum('marks'), 'lower_marks' => $marks->sum('lower_marks'), 'date' => $start_date ];
        }
    }*/
    public function getMoodMarks($data)
    {

        if ($data['flag'] == 1) {

            $start_date = Carbon::parse($data['start_date']);
            $end_date = Carbon::parse($data['end_date']);

            $diff_in_days = $end_date->diffInDays($start_date);
            if ($diff_in_days > 6) {
                $start_date = $end_date->subDays(6);
            }
            $marks = $this->mood_mark->select('marks', 'lower_marks','mood_location_option', 'date')
                    ->where('date', '>=', $start_date->format('Y-m-d'))
                    ->where('date', '<=', $data['end_date'])
                    ->where('client_id', Auth::user()->id)
                    ->groupBy('mood_location_option')
                    ->groupBy('date')
                    ->get();
          
            
        } else {
            $start_date = Carbon::parse($data['start_date']);
            $start_date = Carbon::parse($data['start_date'])->format('Y-m-d');
            $marks = $this->mood_mark->where('date', $start_date)->where('client_id', Auth::user()->id)
                   ->groupBy('set_no')
                    ->get();
        }
        
       /* print_r( $this->mood_mark->where('date', $start_date)->where('client_id', Auth::user()->id)
                   ->groupBy('mood_location_option')->tosql());*/
       
              
        $mood_location_option=-1;
        $resultMarkSum=array();
        $resultMarkSumLower=array();
        $total=0;
        $totalMarkLower=0;
        $mood_location_option=0;
        $resposbilitycount=0;
        $reletoncount=0;
        $recreationcount=0;
        foreach($marks as $mkr){
            if($mood_location_option==-1 || $mood_location_option!=$mkr['mood_location_option']){ 
                
                $total=0;
                $totalMarkLower=0;
                $mood_location_option=$mkr['mood_location_option']; //3   
            }
           
            $total=$total+$mkr['marks']; //0+2=2
            $totalMarkLower=$totalMarkLower+$mkr['lower_marks']; //0+2=2
            if (array_key_exists($mood_location_option,$resultMarkSum)) 
            {
                
                $resultMarkSum[$mood_location_option]['marks']= $resultMarkSum[$mood_location_option]['marks']+$mkr['marks']; //0mark=>2+2=4 1mark=>2+2=4
                $resultMarkSum[$mood_location_option]['lower']=$resultMarkSum[$mood_location_option]['lower']+$mkr['lower_marks']; //0lower=>1+1=2 1lower=>1+1=2
            }else{
                $resultMarkSum[$mood_location_option]['marks']= $total; //3 mark=>2 0mark=>2
                $resultMarkSum[$mood_location_option]['lower']=$totalMarkLower; //1lower 1 0lower=>1
            }
            
            if($mood_location_option==1){
                $resposbilitycount=$resposbilitycount+1;
            }
            if($mood_location_option==2){
                $reletoncount=$reletoncount+1;
            }
            if($mood_location_option==3){
                $recreationcount=$recreationcount+1;
            }
           
        }
      
        $totalMarksRelationHealthy=0;
        $totalMarksRelationUnHealthy=0;
        $totalMarksResponsblityHealthy=0;
        $totalMarksResponsblityUnHealthy=0;
        $totalMarksRecaretionHealthy=0;
        $totalMarksRecaretionUnHealthy=0;
       
        if(sizeof($resultMarkSum)>0){
            $i=0;
            
           foreach($resultMarkSum as $key=>$resulmark){
              
                $div=$resulmark['lower'];
                if($div==0)$div=1;
               if($key==1){
                    $totalMarksResponsblityHealthy=$totalMarksResponsblityHealthy+ ($resulmark['marks']);
                    $totalMarksResponsblityUnHealthy=$totalMarksResponsblityUnHealthy+ ($resulmark['lower']);
               }
               if($key==2){
                    $totalMarksRelationHealthy=$totalMarksRelationHealthy+ ($resulmark['marks']);
                    $totalMarksRelationUnHealthy=$totalMarksRelationUnHealthy+ ($resulmark['lower']);
               }
               if($key==3){
                  $totalMarksRecaretionHealthy=$totalMarksRecaretionHealthy+ ($resulmark['marks']);
                  $totalMarksRecaretionUnHealthy=$totalMarksRecaretionUnHealthy+ ($resulmark['lower']);
               }
           }
        }
        
       
        if($resposbilitycount!=0){
            $totalMarksResponsblityHealthy=$totalMarksResponsblityHealthy/$resposbilitycount;
            $totalMarksResponsblityUnHealthy=$totalMarksResponsblityUnHealthy/$resposbilitycount;
        }
            
        if($reletoncount!=0){
            $totalMarksRelationHealthy=$totalMarksRelationHealthy/$reletoncount;
            $totalMarksRelationUnHealthy=$totalMarksRelationUnHealthy/$reletoncount;
        }
            
        if($recreationcount!=0){
             $totalMarksRecaretionHealthy=$totalMarksRecaretionHealthy/$recreationcount;
             $totalMarksRecaretionUnHealthy=$totalMarksRecaretionUnHealthy/$recreationcount;
        }
          
            
        return [ 
                    'relationmarks' => number_format($totalMarksRelationHealthy,2), 
                    'relation_lower_marks' => number_format($totalMarksRelationUnHealthy,2), 
                    'resposbltymarks' => number_format($totalMarksResponsblityHealthy,2), 
                    'resposblty_lower_marks' => number_format($totalMarksResponsblityUnHealthy,2),
                    'recreationmarks' => number_format($totalMarksRecaretionHealthy,2), 
                    'recreation_lower_marks' => number_format($totalMarksRecaretionUnHealthy,2),
                    'date' => $start_date 
                ];
    }

    public function storeUserMenu($data)
    {
        $menu_limks = $this->menu->all();
        $max = $this->user_menu->max('set_no');
        if (empty($max)) {
            $max = 0;
        }
        $max += 1;
        foreach ($data['menu_list'] as $menu) {
            $new_menu = new $this->user_menu;
            $new_menu->client_id = Auth::user()->id;
            $new_menu->menu = $menu;
            $new_menu->set_no = $max;
            $new_menu->client_transaction_id = (isset($data['client_transaction_id']) ? $data['client_transaction_id'] : '');
            $new_menu->save();
        }

        return true;
    }


    public function getUserLastQuestions($user_id)
    {
        $ans = $this->answer->where('user_id', $user_id)->orderBy('id', 'DESC')->first();
        $allAnswers = $this->answer->where('user_id', $user_id)->where('set_no', (!empty($ans) ? $ans->set_no : ''))->get();

        return $this->question->whereIn('id', $allAnswers->pluck('answer')->flatten()->pluck('question_id'))->get();
    }

    public function validateCode($data)
    {
        return $this->code->where('code', $data['code'])->first();
    }

    public function getGoals()
    {
        return $this->goal->all();
    }

    public function checkUserMenu($menu)
    {

        $menues = $this->user_menu->where('client_id', Auth::user()->id)->orderBy('set_no', 'DESC')->get();
        if ($menues->count() > 0) {
            return $this->user_menu->where([ 'menu' => $menu, 'client_id' => Auth::user()->id, 'set_no' => $menues[0]->set_no ])->count();
        } else {
            return 0;
        }
    }

    public function usedUserMenu($data)
    {
        $menu = $this->user_menu->where([ 'client_id' => Auth::user()->id, 'menu' => $data['menu'] ])->get();
        foreach ($menu as $key => $value) {
            $value->is_used = 1;
            $value->save();
        }

        return true;
    }
}