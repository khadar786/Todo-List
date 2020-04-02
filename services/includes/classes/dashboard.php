<?php 
class Dashboard extends DB{

	//Chapter Fitness Test Info
	function getChapterFitnessTestInfo($chapter_id,$user_id){
		$chapter_qstr="SELECT id FROM diagnostic_user_practice_test WHERE chapter_id=".$chapter_id." AND user_id=".$user_id." ORDER BY id DESC";
		$user_testinfo=$this->get_row($chapter_qstr);
		if(!empty($user_testinfo['id'])){
            $result=true;
        }else{
            $result=false;
        }

        return ['result'=>$result,'id'=>(!empty($user_testinfo['id']))?$user_testinfo['id']:''];
	}

	//Adaptive Test Info
	function getChapterAdaptiveTestInfo($user_id,$chapter_id,$flag){
		$chapter_qstr="SELECT ucd_id FROM user_completed_difficulty WHERE chapter_id=".$chapter_id." AND user_id=".$user_id." AND qtg='".$flag."' AND is_completed=1";
		$adaptive_info=$this->get_row($chapter_qstr);
		if(!empty($adaptive_info['ucd_id'])){
            $result=true;
        }else{
            $result=false;
        }

        return $result;
	}

	//Chapter Coverage
	function getChapterCoverage($user_id,$chapter_id,$flag){
		if($flag=='F'){
            $filter=['B','DF'];
            $q="(sum(acb)+sum(acd)) as qcnt";
        }else{
            $filter=['A','C'];
            $q="(sum(acc)+sum(aca)) as qcnt";
        }

        $qcnt_str="SELECT id FROM question AS q WHERE q.verification='V' AND q.category=".$chapter_id." AND q.id NOT IN(select question_id from diagnostic_user_practice_test_data where question_id=q.id AND chapter_id=".$chapter_id." AND user_id=".$user_id.")";
        $qcnt=$this->num_rows($qcnt_str);

        $ucnt_str="SELECT ".$q." FROM adaptive_test_data_counters WHERE chapter_id=".$chapter_id." AND uid=".$user_id."";
        $ucnt=$this->get_row($ucnt_str);

        if(!empty($ucnt['qcnt'])){
            $coverage=($ucnt['qcnt']/$qcnt)*100;
        }else{
            $coverage=0;
        }

        return $coverage;
	}

    function getCourseStatics($user_id,$course_id){
        //progess bar
        $qcnt_str="select cov.covered/tot.total * 100  as coverage from (select sum(upt.attempted_total) as covered from user_practice_test as upt where upt.user_id=".$user_id." and upt.course_id=".$course_id.") as cov,(select count(*) as total from question as q where q.verification='V' and q.course_id=".$course_id.") as tot";
        $progess_bar=$this->get_row($qcnt_str);
        $coverage=(!empty($progess_bar['coverage']))?$progess_bar['coverage']:0;

        //chapter coverage
        $chapter_str="SELECT cc.completed_count/cc.total_count * 100 as chapter_coverage from 
        (SELECT sum(is_completed) as completed_count,sum(1-is_completed) as working_count,(select count(*) from chapters where course_id=upt.course_id) as total_count FROM user_practice_test as upt where user_id=".$user_id." and upt.course_id=".$course_id.") as cc";
         $chapter_cov=$this->get_row($chapter_str);
         $chapter_coverage=(!empty($chapter_cov['chapter_coverage']))?$chapter_cov['chapter_coverage']:0;

        //Time Spent
         $time_spent_str="SELECT sum(solving_time_diff) as time_spent_in_msecs 
                                FROM user_practice_test_data as uptd 
                                LEFT JOIN user_practice_test as upt ON uptd.test_id=upt.id
                                WHERE uptd.user_id=".$user_id." AND upt.course_id=".$course_id."";
         $time_spent_obj=$this->get_row($time_spent_str);
         $time_spent=(!empty($time_spent_obj['time_spent_in_msecs']))?$this->formatMilliseconds($time_spent_obj['time_spent_in_msecs']):0;

         //Success ignorance rate
         $success_ir_qstr="SELECT (sum(attempted_correct)/sum(attempted_total)* 100) as success_rate , (sum(skipped_total) / sum(attempted_total)* 100) as ignorance_rate FROM user_practice_test as upt WHERE upt.user_id=".$user_id." AND upt.course_id=".$course_id."";
         $success_ir_obj=$this->get_row($success_ir_qstr);
         $success_rate=(!empty($success_ir_obj['success_rate']))?$success_ir_obj['success_rate']:0;
         $ignorance_rate=(!empty($success_ir_obj['ignorance_rate']))?$success_ir_obj['ignorance_rate']:0;

         return ([
                    'coverage'=>$coverage,
                    'chapter_coverage'=>$chapter_coverage,
                    'time_spent'=>$time_spent,
                    'success_rate'=>$success_rate,
                    'ignorance_rate'=>$ignorance_rate
                ]);
    }

    function getSubjectOverView($user_id,$subject_id){
        $subject_info="SELECT atdc.subject_id,sub.`subject`,
                      (sum(ccb)+sum(ccd)+sum(ccc)+sum(cca))/(sum(acb)+sum(acd)+sum(acc)+sum(aca))*100 as success_rate,
                      (sum(scb)+sum(scd)+sum(scc)+sum(sca))/(sum(acb)+sum(acd)+sum(acc)+sum(aca))*100 as ignorance_rate,
                      ((sum(ccb)+sum(ccd))*2)+((sum(ccc)+sum(cca))*4)-(sum(wcb)+sum(wcd)+sum(wcc)+sum(wca)) as score,
                      (SELECT sum(IF(typeofquestion='B' || typeofquestion='DF',2,IF(typeofquestion='C' || typeofquestion='A',4,0))) as tscore FROM question where verification='V' and subject_id= atdc.subject_id and typeofquestion is not null) as tscore
                       FROM adaptive_test_data_counters as atdc
                       LEFT JOIN subjects as sub ON atdc.subject_id=sub.subject_id
                       WHERE atdc.uid=".$user_id." AND atdc.subject_id=".$subject_id."
                       GROUP BY sub.subject_id";
        $subject_row=$this->get_row($subject_info);


        $schapter_info_q="SELECT upt.subject_id,sub.subject,
                          sum(upt.is_completed) as completed_count,
                          sum(1-upt.is_completed) as working_count,
                          (select count(*) from chapters where subject_id=upt.subject_id) as total_count
                          FROM user_practice_test as upt
                          LEFT JOIN subjects as sub ON upt.subject_id=sub.subject_id
                          WHERE upt.user_id=".$user_id." AND upt.subject_id=".$subject_id."
                          GROUP BY sub.subject_id";
        $schapter_info=$this->get_row($schapter_info_q);
                         
        //Total Chapters
        $chapters_q="SELECT count(*) as total_count FROM chapters WHERE subject_id=".$subject_id." AND status=1";
        $chapters_r=$this->get_row($chapters_q);

        $completed_per=(!empty($schapter_info['completed_count']))?($schapter_info['completed_count']/$chapters_r['total_count'])*100:0;
        $working_count_per=(!empty($schapter_info['working_count']))?($schapter_info['working_count']/$chapters_r['total_count'])*100:0;
   
        $completed_per=($completed_per<=10)?10:$completed_per;
        $working_count_per=($working_count_per<=10)?10:$working_count_per;

        $success_rate=(!empty($subject_row['subject_id']))?ceil($subject_row['success_rate']):0;
        $ignorance_rate=(!empty($subject_row['subject_id']))?ceil($subject_row['ignorance_rate']):0;
        $score=(!empty($subject_row['subject_id']))?$subject_row['score']:'--';
        $tscore=(!empty($subject_row['subject_id']))?$subject_row['tscore']:'--';

        $completed_count=(!empty($schapter_info['completed_count']))?$schapter_info['completed_count']:0;
        $total_count=$chapters_r['total_count'];
        $working_count=(!empty($schapter_info['working_count']))?$schapter_info['working_count']:0;

        $res=['completed_per'=>$completed_per,
              'working_count_per'=>$working_count_per,
              'success_rate'=>$success_rate,
              'ignorance_rate'=>$ignorance_rate,
              'score'=>$score,
              'tscore'=>$tscore,
              'completed_count'=>$completed_count,
              'total_count'=>$total_count,
              'working_count'=>$working_count
             ];
        return $res;

    }

    function fitnessTopics($chapter_id,$priority){
        $topics_q="SELECT * FROM topics WHERE chapter_id=".$chapter_id." AND status=1 AND priority IN(".$priority.")";
        $topics_r=$this->get_results($topics_q);
        return $topics_r;
    }

    function fitnessTopicsOrderBy($chapter_id,$order_by){
        $topics_q="SELECT * FROM topics WHERE chapter_id=".$chapter_id." AND status=1 ORDER BY ".$order_by."";
        $topics_r=$this->get_results($topics_q);
        return $topics_r;
    }

    function fitnessTestQ($priority){
        $test_q="SELECT * FROM diagnostic_test_adapter WHERE section IN(".$priority.")";
        $test_r=$this->get_results($test_q);
        return $test_r;
    }

    function GroupAllTopics($topics){
        $topic_ids=[];
        foreach ($topics as $key=>$topic) {
            $topic_ids[]=$topic['topic_id'];
        }
        return implode(",", $topic_ids);
    }

    function QuestionDistribution($topics,$qids_cnt,$alltopics_str,$k){
            $topics_cnt=(count($topics)>$qids_cnt)?4:count($topics);
            $remainder=$qids_cnt%$topics_cnt;
            $each_topic_cnt=($qids_cnt-$remainder)/$topics_cnt;
            $topics_cnt=0;

            foreach($topics as $key=>$topic) {
                $topics_cnt++;
                $topic['qsize']=$each_topic_cnt;
                if($topics_cnt>$qids_cnt)
                break;
                //$topic->start_range=$new_order;
                //$new_order =$new_order+$topic->qsize;
                //$topic->end_range=$new_order;
                for($j=0;$j<$topic['qsize'];$j++){
                     $second_priority=($topic['priority']=='L')?'M':'L';
                     $qids[($k+1)]=["qno"=>($k+1),"topic_id"=>$topic['topic_id'],"topic"=>$topic['topic'],"priority"=>$topic['priority'],'alltopics'=>$alltopics_str];
                     $k++;
                }
            }

            if(count($qids)<$qids_cnt){
                for($t=0;$t<$remainder;$t++){
                    $qids[($k+1)]=["qno"=>($k+1),"topic_id"=>$topics[$t]['topic_id'],"topic"=>$topics[$t]['topic'],"priority"=>$topics[$t]['priority'],'alltopics'=>$alltopics_str];
                    $k++;
                }
            }

            return ['qids'=>$qids,'k'=>$k];
    }

    function getGrade($tot_score){
        $grade_obj="SELECT * FROM diagnostic_test_grades WHERE ".$tot_score.">=start_range AND ".$tot_score."<=end_range";
        $grade_info=$this->get_row($grade_obj);
        if(!empty($grade_info)){
            $response=array('grade'=>$grade_info['grade'],'start_range'=>$grade_info['start_range'],'eng_range'=>$grade_info['end_range'],'percentage'=>$grade_info['percentage'],'grade_img'=>$grade_info['grade_img']);
        }
        return $response;
    }

    function getGradeComment($fcnt,$acnt,$grade){
        $grade_obj="SELECT * FROM diagnostic_test_comments WHERE fundamentals=".$fcnt." AND applied_conncepts=".$acnt." AND grade='".$grade."'";
        $grade_info=$this->get_row($grade_obj);
        return $grade_info['comment'];
    }

    function getInsights($widget_type,$attempted_correct){
        if($attempted_correct==0){
            $attempted_correct=1;
        }else if($attempted_correct>=5){
            $attempted_correct=4;
        }

        $grade_obj="SELECT * FROM diagnostic_performance_comments WHERE widget='".$widget_type."' AND attempted_correct=".$attempted_correct."";
        $grade_info=$this->get_row($grade_obj);
        $widget_degrees=['25'=>-70,'50'=>-23,'75'=>23,'100'=>70];
        $grade_info['widget_degrees']=$widget_degrees[$grade_info['percentage']];          
        return $grade_info;

    }


    function getQuestionInfo($qno){
        $question_q="SELECT * FROM diagnostic_test_adapter WHERE qno=".$qno."";
        $question=$this->get_row($question_q);
        return $question;
    }

    function getQ($chapter_id,$topic_ids,$question_type,$qids){
        if($question_type=='DF'){
            $question_types=["'B'","'C'","'A'"];
        }else if($question_type=='C'){
            $question_types=["'A'","'DF'","'B'"];
        }else if($question_type=='A'){
            $question_types=["'C'","'DF'","'B'"];
        }else{
            $question_types=["'B'"];
        }

        $question_data_q="SELECT q.id,q.category,q.topic_id,q.subject_id,q.course_id,qi.question,qi.answer,
                        q.score,qs.level,q.typeofquestion FROM question as q
                        LEFT JOIN question_info as qi ON q.id=qi.question_id
                        LEFT JOIN question_stats_app as qs ON q.id=qs.question_id
                        WHERE q.category=".$chapter_id." AND q.topic_id IN(".$topic_ids.") AND q.typeofquestion IN(".implode(',', $question_types).") AND q.id NOT IN(".$qids.") AND q.verification='V' ORDER BY RAND()
                        ";
        $question_data=$this->get_row($question_data_q);               
        return $question_data;
    }

    function getQuestionOptions($qid){
        $qoptions_q="SELECT * FROM question_options WHERE question_id=".$qid."";
        return $this->get_results($qoptions_q);
    }

   function getTopic($percentage){
        if($percentage>=0 && $percentage<=20){
            $color='#db7e7e';
        }else if($percentage>=21 && $percentage<=40){
            $color='#d0ae72';
        }else if($percentage>=41 && $percentage<=60){
            $color='#c8c767';
        }else if($percentage>=61 && $percentage<=80){
            $color='#82ba60';
        }else{
            $color='#48ac70';
        }

        return $color;
    }

    //Get Subject Coverage
    function getSubjectCoverage($subject_id){
        //Get Chapter Questions
        $qcnt="SELECT q.id FROM question as q
               LEFT JOIN question_info as qi ON q.id=qi.question_id
               LEFT JOIN question_stats_app as qs ON q.id=qs.question_id
               WHERE qi.answer<>'NA' AND q.verification<>'U' AND q.ismain=0 AND q.link=0 AND q.subject_id=".$subject_id." AND qs.level<>''
              ";
        $qcount=$this->num_rows($qcnt);
        return $qcount;
    }

    //Coverage Information
    function getSubjectCoverageInfo($subject_id,$user_id){
        //Coverage Information
        $tot_subject_q=$this->getSubjectCoverage($subject_id);
        $subject_coverage_obj="SELECT (sum(upt.attempted_total)/".$tot_subject_q.")*100) as subject_coverage,sum(upt.attempted_total) FROM user_practice_test as upt WHERE upt.user_id=".$user_id." AND upt.subject_id=".$subject_id."";
        $coverage=(!empty($subject_coverage_obj['subject_coverage']))?ceil($subject_coverage_obj['subject_coverage']):0;

        //$user_subject=$this->getSubjectScore($subject_id,$user_id);
        //$score=(!empty($user_subject->score))?$user_subject->score:0;
        $sinfo=array('coverage'=>$coverage,'score'=>10);

        return $sinfo;
    }

    function formatMilliseconds($milliseconds) {
        $seconds = floor($milliseconds / 1000);
        $minutes = floor($seconds / 60);
        $hours = floor($minutes / 60);
        $milliseconds = $milliseconds % 1000;
        $seconds = $seconds % 60;
        $minutes = $minutes % 60;
        
        //$format = '%02u:%02u:%02u.%02u';
        //$time = sprintf($format, $hours, $minutes, $seconds, $milliseconds);
        $format = '%02u:%02u:%02u';
        $time = sprintf($format, $hours, $minutes, $seconds);
        //$rtrim_zero=rtrim($time, '0');
        //return rtrim($rtrim_zero, '.');
        return $time;
    }

    function generateRandomString($length = 6) 
    {
       $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
       $characters = str_shuffle($characters);
       return substr($characters, 0, $length);
    }
}
?>