<?php
//phpinfo();
print_r($_POST);
exit;
include('config.php');
//include('config_paytm.php');
//include('encdec_paytm.php');
//include('functions.php');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,Accept,Authorization');

$client = new SmsGatewayHub();

$response=array();
$error=true;
$message="Fail";
$data=[];
//Subject list
if(!empty($_POST['action'])){
	$post=(object)$_POST;
}else{
	$postdata=file_get_contents("php://input");
	$post=json_decode($postdata);
}

//print_r($post->course_id);
if(!empty($post->action)){
	switch($post->action) {
		case "register":
			$email_valid=$mobile_valid=true;
			//Check Email
			$where="email='".$post->email."'";
			$query_str="select user_id from user_profiles where $where";
			$email=$db->num_rows($query_str);
			if($email>0){
				$error=true;
				$message="Email already used";
				$email_valid=false;
			}

			if($email_valid==true){
				$where="mobile='".$post->phone."'";
				$query_str="select user_id from user_profiles where $where";
				$mobile=$db->num_rows($query_str);
				if($mobile>0){
					$error=true;
					$message="Mobile number already used";
					$mobile_valid=false;
				}
			}

			if($email_valid==true && $mobile_valid==true){


				//OTP Genration
				$otp=$db->generatePassword(6,6);

					
					//Check User
        			$sms_temp=$db->get_row("select content from sms_templates where id='1'");
                    $searchReplaceArray = array(
                        '[OTP]' => $otp, 
                    );

                    $sms_msg = str_replace(
                        array_keys($searchReplaceArray), 
                        array_values($searchReplaceArray), 
                        $sms_temp['content']
                    );
					$phone =$post->phone;
					//$otp_msg = 'OTP FROM JEE LEAGUE '.$otp;
					$otp_result=$client->send($phone,$sms_msg);

					$otp_response=json_decode($otp_result,true);
					if($otp_response['send_status']==true)
					{
							$check_user_qstr="select id from unverify_users where mobile='".$post->phone."' AND otpstatus=0";
			               	$check_user=$db->get_row($check_user_qstr);
			               	if(!empty($check_user['id']))
			               	{
			               		$user_fields=[
			           						"first_name"=>$post->first_name,
			           						"email"=>$post->email,
			           						"dob"=>(!empty($post->dob))?date("Y-m-d",strtotime($post->dob)):"0000-00-00",
			           						"password"=>sha1($post->password),
			           						"mobile"=>$post->phone,
			           						"otp"=>$otp,
			           						"otpstatus"=>0
			           					];
			           			 $where_condition=['id'=>$check_user['id'],'otpstatus'=>0];
			           			 $db->update('unverify_users',$user_fields,$where_condition);
			           			 $user_id=$check_user['id'];
			               	}
			               	else
			               	{
			               		$user_fields=[
			           						"first_name"=>$post->first_name,
			           						"email"=>$post->email,
			           						"dob"=>date("Y-m-d",strtotime($post->dob)),
			           						"password"=>sha1($post->password),
			           						"mobile"=>$post->phone,
			           						"otp"=>$otp,
			           						"otpstatus"=>0
			           					];
			               		$insert_status=$db->insert('unverify_users',$user_fields);
			               		if($insert_status)
			               		{
			               			$user_id=$db->lastid();
			               		}
			               	}
					}

				$data=$user_id;
				$error=false;
				$message="Success";
			}

			break;
		case "checkotp":
			$qry="select * from unverify_users where id='".$post->temp_id."' and otp='".$post->otp."'";
  			$user=$db->get_row($qry);
  			$data=$post->temp_id;
  			if(!empty($user['id']))
      		{
      			$check_user_profile="SELECT user_id FROM user_profiles WHERE email='".$user["email"]."'";
      			$user_profile=$db->get_row($check_user_profile);
      			if(empty($user_profile['user_id'])){
	      				$user_fields=[
							"first_name"=>$user["first_name"],
							"email"=>$user["email"],
							"dob"=>$user["dob"],
							"password"=>$user["password"],
							"mobile"=>$user["mobile"],
							"comming_from"=>'Mobile'
							];
					$insert_status=$db->insert('user_profiles',$user_fields);
					$user_id=$db->lastid();
					if($insert_status){
						//User Details
						$user_details_fields=[
							"user_id"=>$user_id,
							"created_date"=>date("Y-m-d H:i:s")
							];
						$db->insert('user_details',$user_details_fields);

						//Update Unverify User
						$unverify_user=[
							"otpstatus"=>1
						];
						$where_unverify_user=[
							"id"=>$user['id']
						];
						$db->update('unverify_users',$unverify_user,$where_unverify_user);

					}
      			}else{
      				$user_id=$user_profile['user_id'];
      			}

				$data=[
					"user_id"=>$user_id,
					"first_name"=>$user["first_name"],
					"email"=>$user["email"],
					"mobile"=>$user["mobile"],
					"dob"=>$user["dob"]
				  ];		
  				$error=false;
				$message="Success";	
				
      		}else{
      			$error=true;
				$message="Invalid OTP";
      		}
			break;
		case "jee_league_otp":
			$qry="select * from unverify_users where id='".$post->temp_id."' and otp='".$post->otp."'";
  			$user=$db->get_row($qry);
  			$data=$post->temp_id;
  			if(!empty($user['id']))
      		{
      			$check_user_profile="SELECT user_id FROM user_profiles WHERE email='".$user["email"]."'";
      			$user_profile=$db->get_row($check_user_profile);
      			if(empty($user_profile['user_id'])){
	      				$user_fields=[
							"first_name"=>$user["first_name"],
							"email"=>$user["email"],
							"dob"=>'0000-00-00',
							"password"=>$user["password"],
							"mobile"=>$user["mobile"],
							"comming_from"=>'Mobile'
							];
					$insert_status=$db->insert('user_profiles',$user_fields);
					$user_id=$db->lastid();
					if($insert_status){

						$ex=explode(",",$post->location);
						//User Details
						$user_details_fields=[
							"user_id"=>$user_id,
							"class_id"=>2,
							"city"=>($ex[0]!='')?$ex[0]:$ex[2],
							"state"=>($ex[1]!='')?$ex[1]:$ex[3],
							"country"=>($ex[2]!='')?$ex[2]:$ex[4],
							"created_date"=>date("Y-m-d H:i:s")
							];
						$db->insert('user_details',$user_details_fields);

						//Course
						$course=[
									"user_id"=>$user_id,
									"course_id"=>$post->course,
									"status"=>1
							    ];
						$insert_course=$db->insert('user_subscriptions',$course);

						//Update Unverify User
						$unverify_user=[
							"otpstatus"=>1
						];
						$where_unverify_user=[
							"id"=>$user['id']
						];
						$db->update('unverify_users',$unverify_user,$where_unverify_user);
					}
      			}else{
      				$user_id=$user_profile['user_id'];
      			}

				$data=[
					"user_id"=>$user_id,
					"first_name"=>$user["first_name"],
					"email"=>$user["email"],
					"mobile"=>$user["mobile"],
					"dob"=>"0000-00-00",
					"state"=>($ex[0]!='')?$ex[0]:$ex[2],
                	"country"=>($ex[2]!='')?$ex[2]:$ex[4],
                	"photo"=>"",
                	"course_id"=>$post->course,
                	"class_id"=>2
				  ];		
  				$error=false;
				$message="Success";	
				
      		}else{
      			$error=true;
				$message="Invalid OTP";
      		}
			break;
		case "login":
			 $email=$post->email;
			 $password=sha1($post->password);
			 $user_qstr="SELECT up.user_id,up.first_name,up.last_name,up.email,up.user_level,up.mobile_verification,up.user_status,up.mobile,up.dob,ud.class_id,ud.state,ud.country,ud.demo_status,cls.class_name,up.photo,up.is_social,up.is_social,up.referee_user_id,up.referee_code FROM user_profiles as up 
			 	 LEFT JOIN user_details as ud ON up.user_id=ud.user_id 
			 	 LEFT JOIN classes as cls ON cls.id=ud.class_id 
			 	 WHERE ( up.email='".$email."' or up.mobile='".$email."' ) AND up.password='".$password."'";
			 $user_obj=$db->get_row($user_qstr);
			 if(!empty($user_obj["user_id"])){
			 	//User Subscription
			 	$user_sub_qstr="SELECT us.course_id,c.course FROM user_subscriptions as us
			 					LEFT JOIN courses as c ON us.course_id=c.course_id
			 					WHERE us.user_id=".$user_obj["user_id"]." AND us.status=1 
			 					";
			 	$user_subscriptions=$db->get_row($user_sub_qstr);

			 	if(!empty($user_subscriptions["course_id"])){
                    $course_id=$user_subscriptions["course_id"];
                    $course=$user_subscriptions["course"];
                    $redirect="home";
                }else{
                    $course_id="";
                    $course="";
                    $redirect="welcome";
                }

                $data=[
                		'user_id'=>$user_obj["user_id"],
                		'first_name'=>$user_obj["first_name"],
                		'email'=>$user_obj["email"],
                		'mobile'=>$user_obj["mobile"],
                		'dob'=>$user_obj["dob"],
                		'user_level'=>$user_obj["user_level"],
                		'user_status'=>$user_obj["user_status"],
                		'class_id'=>$user_obj["class_id"],
                		'class_name'=>$user_obj["class_name"],
                		'state'=>$user_obj["state"],
                		'country'=>$user_obj["country"],
                		'course_id'=>$course_id,
                		'course'=>$course,
                		'photo'=>$user_obj["photo"],
                		'is_social'=>$user_obj["is_social"],
                		'redirect'=>$redirect
                	  ];

           		//Last Login
                $ip=$_SERVER['REMOTE_ADDR'];
                $ua=$_SERVER['HTTP_USER_AGENT'];
                $user_login_restriction=["user_id"=>$user_obj["user_id"],"login_time"=>date("Y-m-d H:i:s"),"IP"=>$ip,"UA"=>$ua,"Purpose"=>'LOGIN',"status"=>1];
                $db->insert("user_login_restriction",$user_login_restriction);

			 	$error=false;
			 	$message="Success";
			 }else{
			 	$error=true;
			 	$message="Invalid credentials";
			 	$redirect="";
			 }
			 
			 break;
		case "forgotPassword":
			$qry="select user_id,mobile from user_profiles where mobile='".$post->phone."'";
			$user=$db->get_row($qry);
			if(!empty($user['user_id'])){
				//OTP Genration
				//$otp=$db->generatePassword(6,6);
				    $otp=$db->generatePassword(6,6);

				    $phone =$post->phone;

					$sms_temp=$db->get_row("select content from sms_templates where id='1'");
                    $searchReplaceArray = array(
                        '[OTP]' => $otp, 
                    );
                    $sms_msg = str_replace(
                        array_keys($searchReplaceArray), 
                        array_values($searchReplaceArray), 
                        $sms_temp['content']
                    );

					$otp_result=$client->send($phone,$sms_msg);

					$otp_response=json_decode($otp_result,true);
					if($otp_response['send_status']==true)
					{
						$update_arr=['otp'=>$otp];
						$where_cond=['mobile'=>$user['mobile']];
						$update_otp=$db->update("user_profiles",$update_arr,$where_cond);
						if($update_otp){
							$error=false;
					 		$message="OTP sent successfully";
						}
					}
			}
			else
			{
				$error=true;
			 	$message="Mobile not exist";
			}
			break;
		case "resendotp":
			$qry="select * from user_profiles where mobile='".$post->phone."'";
			$user=$db->get_row($qry);
			if($db->num_rows($qry)>0)
			{
				    $otp=$db->generatePassword(6,6);
				    $phone =$post->phone;
					$sms_temp=$db->get_row("select content from sms_templates where id='1'");
                    $searchReplaceArray = array(
                        '[OTP]' => $otp, 
                    );
                    $sms_msg = str_replace(
                        array_keys($searchReplaceArray), 
                        array_values($searchReplaceArray), 
                        $sms_temp['content']
                    );
					$otp_result=$client->send($phone,$sms_msg);

					$otp_response=json_decode($otp_result,true);
					if($otp_response['send_status']==true)
					{
						$update_arr=['otp'=>$otp];
						$where_cond=['mobile'=>$user['mobile']];
						$update_otp=$db->update("user_profiles",$update_arr,$where_cond);
						if($update_otp){
							$error=false;
					 		$message="OTP sent successfully";
						}
					}
			}
			else
			{
				$error=true;
			 	$message="Mobile not exist";
			}
			break;
		case "changePassword":
			 $qry = "select user_id from user_profiles where mobile='".$post->phone."' and otp='".$post->otp."'";
			 $user=$db->get_row($qry);
			 if(!empty($user['user_id'])){
			 	$update_arr=['password'=>sha1($post->password)];
				$where_cond=['user_id'=>$user['user_id']];
				$update_otp=$db->update("user_profiles",$update_arr,$where_cond);
				if($update_otp){
					$error=false;
			 		$message="Password changed successfully";
				}
			 }else{
			 	$error=true;
			 	$message="Invalid OTP";	
			 }
			 break;
		case "updatephone":
			 $qry="select user_id from user_profiles where user_id='".$post->userid."' and otp='".$post->otp."'";
  			 $user=$db->get_row($qry);
  			 if(!empty($user['user_id'])){
			 	$update_arr=['mobile'=>$post->phone];
				$where_cond=['user_id'=>$user['user_id']];
				$update_otp=$db->update("user_profiles",$update_arr,$where_cond);
				if($update_otp){
					$data=['mobile'=>$post->phone];
					$error=false;
			 		$message="Mobile number updated successfully";
				}
  			 }else{
  			 	$error=true;
				$message="Invalid OTP";
  			 }
			 break;
		case "sendotp":
			 $qry="select user_id,mobile from user_profiles where user_id<>'".$post->userid."' AND mobile='".$post->phone."'";
  			 $user=$db->get_row($qry);
  			 if(empty($user['user_id'])){
  			 	$user_qry="select user_id,mobile from user_profiles where user_id='".$post->userid."' AND mobile='".$post->phone."'";
  			 	$user_info=$db->get_row($user_qry);
  			 	if(!empty($user_info['user_id'])){
  			 		$error=false;
				 	$message="Updated";
  			 	}else{
	  			 	//OTP Genration
					 $otp=$db->generatePassword(6,6);
				    $phone =$post->phone;
					$sms_temp=$db->get_row("select content from sms_templates where id='1'");
                    $searchReplaceArray = array(
                        '[OTP]' => $otp, 
                    );
                    $sms_msg = str_replace(
                        array_keys($searchReplaceArray), 
                        array_values($searchReplaceArray), 
                        $sms_temp['content']
                    );
					$otp_result=$client->send($phone,$sms_msg);

					$otp_response=json_decode($otp_result,true);
					if($otp_response['send_status']==true)
					{

						$update_arr=['otp'=>$otp];
						$where_cond=['user_id'=>$post->userid];
						$update_otp=$db->update("user_profiles",$update_arr,$where_cond);
						if($update_otp){
							$error=false;
					 		$message="OTP sent successfully";
						}
					}
  			 	}
  			 	
  			 }else{
  			 	$error=true;
			 	$message="Mobile already exist";
  			 }
			 break;
		case "saveLocation":
			 $qry="select user_id from user_profiles where user_id='".$post->userid."'";
			 $user=$db->get_row($qry);
			 if(!empty($user['user_id'])){
			 		$user_fields=[
							"user_id"=>$post->userid,
							"current_value"=>1,
							"message"=>$post->message,
							"requested_value"=>$post->location
							];
					$insert_status=$db->insert('changing_request',$user_fields);
					if($insert_status){
						$error=false;
						$message="Request sent successfully";
					}
			 }else{
			 	$error=true;
				$message="Invalid credentials";
			 }
			 break;
		case "savesubject":
			 $qry="select user_id from user_profiles where user_id='".$post->userid."'";
			 $user=$db->get_row($qry);
			 if(!empty($user['user_id'])){
			 		$user_fields=[
							"user_id"=>$post->userid,
							"current_value"=>2,
							"message"=>$post->message,
							"requested_value"=>$post->subject
							];
					$insert_status=$db->insert('changing_request',$user_fields);
					if($insert_status){
						$error=false;
						$message="Request sent successfully";
					}
			 }else{
			 	$error=true;
				$message="Invalid credentials";
			 }
			 break;
		case "updatePassword":
			 $qry="select user_id from user_profiles where user_id='".$post->userid."' and password='".sha1($post->password)."'";
			 $user=$db->get_row($qry);
			 if(!empty($user['user_id'])){
		 		$update_arr=['password'=>sha1($post->npassword)];
				$where_cond=['user_id'=>$user['user_id']];
				$update_otp=$db->update("user_profiles",$update_arr,$where_cond);
				if($update_otp){
					$error=false;
			 		$message="Password changed successfully";
				}
			 }else{
			 	$error=true;
			 	$message="Current password invalid";
			 } 
			 break;
		case "uploadImage":
				$url="https://stagecompete.etutor.co/image-upload"; 
			    $filename=$_FILES['file']['name'];
			    $filedata=$_FILES['file']['tmp_name'];
			    $filesize=$_FILES['file']['size'];
			    if($filedata!='')
			    {
			        $headers=array("content-type:multipart/form-data"); 
			        // cURL headers for file uploading
			        $postfields=array("filedata"=>"@$filedata","filename"=>$filename,"user_id"=>$post->user_id);
			        /*$postfields=array('filedata'=>'@'.$filedata.';filename='.$filename.';user_id='.$post->user_id);*/
			        $ch = curl_init();
			        $options = array(
			            CURLOPT_URL => $url,
			            CURLOPT_HEADER => true,
			            CURLOPT_POST => 1,
			            CURLOPT_HTTPHEADER => $headers,
			            CURLOPT_POSTFIELDS => $postfields,
			            CURLOPT_INFILESIZE => $filesize,
			            CURLOPT_RETURNTRANSFER => true
			        ); // cURL options
			        curl_setopt_array($ch,$options);
			        $result=curl_exec($ch);
			        print_r($result);
			        if(!curl_errno($ch))
			        {
		            	$info=curl_getinfo($ch);
		            	if($info['http_code']==200){
		            		$json_result=json_decode($result,true);
		            		print_r($json_result);
		            		//$data=['image'=>];
		            		$error=false;
		                	$messag ="File uploaded successfully";
		            	}
		            		
			        }
			        else
			        {
			        	$error=true;
			            $message = curl_error($ch);
			        }
			        curl_close($ch);
			    }
			    else
			    {
			    	$error=true;
			        $message = "Please select the file";
			    }
			 break;
		case "course":
			$res=[];
			//Courses
			$qry="select course_id,course from courses where status=1";
			$result=$db->get_results($qry);
			$res['courses']=$result;

			//Classes
			$classes_qstr="select id,class_name,year_of_batch from classes";
			$class_result=$db->get_results($classes_qstr);
			$res['classes']=$class_result;

			$data=$res;
			$error=false;
			$message="Success";
			break;
		case "insertCourse":
				//Course
				$course=[
							"user_id"=>$post->userid,
							"course_id"=>$post->course,
							"status"=>1
					    ];
				$insert_course=$db->insert('user_subscriptions',$course);
				if($insert_course){
					//Class location
					$ex=explode(",",$post->location);
					$location=[
							"class_id"=>$post->batch,
							"city"=>($ex[0]!='')?$ex[0]:$ex[2],
							"state"=>($ex[1]!='')?$ex[1]:$ex[3],
							"country"=>($ex[2]!='')?$ex[2]:$ex[4]
							];
					$where_cond=["user_id"=>$post->userid];
					$update_location=$db->update('user_details',$location,$where_cond);
					if($update_location){
						$error=false;
						$message="Success";
					}
				}
			break;
		case "CourseOverview":
			 $dashboard=new Dashboard();
			 $user_id=$post->user_id;
			 $course_id=$post->course_id;
			 $course_overview=$dashboard->getCourseStatics($user_id,$course_id);
			 if(count($course_overview)>0){
			 	$data=$course_overview;
			 	$error=false;
			 	$message="Success";
			 }
			 break;
		case "Subjects":
			$dashboard=new Dashboard();
			$user_id=$post->user_id;
			$subject_qstr="SELECT subject_id,subject,category_icon,sort_order FROM subjects WHERE course_id=".$post->course_id." AND status=1 ORDER BY sort_order ASC";
			$result=$db->get_results($subject_qstr);

			for($i=0;$i<count($result);$i++){
				$result[$i]['over_view']=$dashboard->getSubjectOverView($user_id,$result[$i]['subject_id']);
			}
			
			if(count($result)>0){
				$data=$result;
				$error=false;
				$message="Success";
			}
			break;
		case "Chapters":
			$dashboard=new Dashboard();
			$user_id=$post->user_id;
			$chapter_qstr="SELECT chapter_id,chapter,class_id FROM chapters WHERE subject_id=".$post->subject_id." AND status=1 AND class_id=".$post->class_id." ORDER BY sort_order ASC";
			$result=$db->get_results($chapter_qstr);
			if(count($result)>0){
				$result_data=[];
			    foreach ($result as $key=>$chapter) {
			    	$result[$key]['test_id']='';
			    	$ftest=$dashboard->getChapterFitnessTestInfo($chapter['chapter_id'],$user_id);
			    	$result[$key]['fitness_test']=$ftest['result'];
			    	if($result[$key]['fitness_test']==true){
			    		$result[$key]['test_id']=$ftest['id'];
			    		$result[$key]['is_fdm_complete']=$dashboard->getChapterAdaptiveTestInfo($user_id,$chapter['chapter_id'],'F');
			    		$result[$key]['is_fdm_coverage']=$dashboard->getChapterCoverage($user_id,$chapter['chapter_id'],'F');
			    		$result[$key]['is_applied_complete']=$dashboard->getChapterAdaptiveTestInfo($user_id,$chapter['chapter_id'],'A');
			    		$result[$key]['is_applied_coverage']=$dashboard->getChapterCoverage($user_id,$chapter['chapter_id'],'A');
			    	}else{
			    		$result[$key]['is_fdm_complete']=false;
			    		$result[$key]['is_fdm_coverage']=0;
			    		$result[$key]['is_applied_complete']=false;
			    		$result[$key]['is_applied_coverage']=0;
			    	}
			    	
			    }
			    
				$data=$result;
				$error=false;
				$message="Success";
			}
			break;
			case "packages":
			
			$res=[];
			//packages
			$qry="SELECT * FROM course_packages WHERE class_id=".$post->class_id." AND package_status=1 AND course_id=".$post->course_id."";
			$result=$db->get_results($qry);
			$res['packages']=$result;

			$data=$res;
			$error=false;
			$message="Success";
			break;
			case "singlePackage":
			
			$res=[];
			//package
			$qry="SELECT * FROM course_packages WHERE package_id=".$post->package_id."";
			$result=$db->get_row($qry);
			$res['package']=$result;

			$data=$res;
			$error=false;
			$message="Success";
			break;
		case "apply_coupon":
			$coupon=$post->coupon;
			$package_id=$post->package_id;
			//Check Coupon Code
			$coupon_code_qstr="SELECT * FROM discount_codes WHERE vouchercode='".$coupon."'";
			$coupon_result=$db->get_row($coupon_code_qstr);
			
			if(!empty($coupon_result['ID'])){
				$expiry= date("Y-m-d H:i:s",strtotime($coupon_result["expiry"]));
                $start_date=date("Y-m-d H:i:s",strtotime($coupon_result["start_date"]));
				$current=date("Y-m-d H:i:s");
				
				//check expiry date
				if($expiry>=$current)
                {
					if($start_date<=$current){
						//check no of usages
						if($coupon_result["num_vouchers"])   
                        {
							//Check coupons usage
							$coupon_usage_qstr="SELECT * FROM orders WHERE voucher_id='".$coupon_result["ID"]."' AND status='TXN_SUCCESS'";
							$usage_rows=$db->num_rows($coupon_usage_qstr);
							if($usage_rows<$coupon_result["num_vouchers"])
                            {
								//Package
								$package_qstr="SELECT * FROM course_packages WHERE package_id=".$package_id."";
								$package_details=$db->get_row($package_qstr);
								$posted['package_price']=$package_details['package_price'];
                                //Not applicable for 500rs packages
								if($package_details['package_price']>100){
									$amount=$package_details['package_price'];
									$discount_operation=$coupon_result["discount_operation"];
									$discount=$coupon_result["discount_amount"];

										if($discount_operation=="%")
                                        {
                                            $percentage= ($discount / 100) * $amount;
                                            $mydiscount=$percentage;
                                            $total=$amount-$percentage;
                                        }else if($discount_operation=="-")
                                        {
                                            $total=$amount-$discount;
                                            $mydiscount=$discount;
										}
									
									$error=false;
									$res['message']="Coupon applied successfully";
									$res['operation']=$discount_operation;
									$res['total']=number_format((float)$total, 2, '.', '');
									$res['discount']=number_format((float)$mydiscount, 2, '.', '');
									$res['package_price']=number_format((float)$package_details['package_price'], 2, '.', '');

									$data=$res;
								}else{
									$message="Coupon code is not applicable for this package";
								}
							}else{
								$message="Coupon Code Expired";
							}

						}else{
							$messag=="No Action";
						}
					}else{
						$messag="Coupon valid from ".date("d-m-Y H:i",strtotime($data["start_date"]));
					}
				}else{
                    $response['message']="Coupon Code Expired";
                }

			}else{
				$message="Invalid Coupon Code";
			}

			break;
			case "userPastrank":
				$course_id=$post->course_id;
				$user_id=$post->user_id;
				$value=$mdb->user_past_ranks->findOne(array('uid'=>intval($user_id)));
	   	  		$ar=array('uid'=>$value->uid,'orank'=>$value->orank,'srank'=>$value->srank,'score'=>$value->score);
	   	  		$q="SELECT sum(if(uptd.user_score>0,1,0))/sum(uptd.is_attempted)*100 as Percentage FROM user_practice_test_data as uptd WHERE uptd.course_id=".$course_id." and uptd.user_id=".$user_id."";
	   	  		$user_percentage=$db->get_row($q);
	   	  		$ar['percentile']=($user_percentage['Percentage']>0)?number_format($user_percentage['Percentage'],2):0;
				$data = $ar;
				$error=false;
				$message="Success";
			break;
			case "CPL_cycles_jee":
			
			$res=[];
			$user_id=$post->user_id;
			//cycles
			/*$pck="SELECT * FROM course_packages WHERE course_id=".$post->course_id." AND package_status=1 AND privilege_ref='CPL'";
			$package=$db->get_row($pck);
			
			 $qry="SELECT * FROM cpl_cycles WHERE cycle_type='".$post->test_type."' AND course_id=".$post->course_id." AND cycle_id NOT IN(17,34) AND package_id=".$package['package_id'].""; 
			$result1=$db->get_results($qry);
			$i=0;
			foreach ($result1 as $value) 
			{ $tes = "SELECT ct.id,ct.title,ct.questionType,ct.testSize,ct.testTime,ct.negativeMarks,ct.test_mode,ct.course_id,ct.subject_id,cc.cycle_id,ct.start_date,ct.end_date,cc.cycle_type,cc.report_status,cc.cycle_seo_slug,ct.test_type FROM cpl_testconfigs as ct LEFT JOIN cpl_cycles as cc ON cc.cycle_id=ct.cycle_id WHERE cc.cycle_type='".$post->test_type."' AND cc.cycle_id=".$value['cycle_id']." AND cc.course_id=".$post->course_id.""; */
						$tes = "SELECT ct.id,ct.title,ct.questionType,ct.testSize,ct.testTime,ct.negativeMarks,ct.test_mode,ct.course_id,ct.subject_id,cc.cycle_id,ct.start_date,ct.end_date,cc.cycle_type,cc.report_status,cc.cycle_seo_slug,ct.test_type FROM cpl_testconfigs as ct LEFT JOIN cpl_cycles as cc ON cc.cycle_id=ct.cycle_id WHERE cc.cycle_type='".$post->test_type."' AND cc.course_id=".$post->course_id."";
						$test_result1=$db->get_results($tes);
						$test_result=array();
						foreach ($test_result1 as $test_values) {
							  $cdate=new DateTime(date('Y-m-d H:i:s'));
				              $sdate=new DateTime($test_values['start_date']);
				              $edate=new DateTime($test_values['end_date']);
				              $test_values['test_date']=$sdate->format('d-m-Y');
				              $test_values['start_time']=$sdate->format('g A');
				              $test_values['end_time']=$edate->format('g A');
				              $interval=$sdate->diff($cdate);
				              $test_values['interval']=$interval;

							   //Written statu
				              //Test Expiry status
				              //Comming soon
				              $today=time();
				              $date=strtotime($test_values['end_date']);
				              $exam_date=date('Y-m-d',strtotime($test_values['start_date']));
				              $start_time=date('h:i A',strtotime($test_values['start_date']));
				              $end_time=date('h:i A',strtotime($test_values['end_date']));

				              $test_values['exam_current_time']=$today;
				              $test_values['exam_start_date_time']=$test_values['start_date'];
				              $test_values['exam_exact_start_time']=strtotime($test_values['start_date']);
				              $test_values['exam_exact_end_time']=strtotime($test_values['end_date'])*1000;
				              $test_values['exam_date']=$exam_date;
				              $test_values['start_time']=$start_time;
				              $test_values['end_time']=$end_time;
				              $test_values['comming_soon_status']=false;

				              //Check User Test Status
				              $check_user_test_q="SELECT ut.id,ut.is_test_finished,ut.is_valid_test FROM user_test as ut WHERE ut.test_id=".$test_values['id']." and ut.user_id=".$user_id."";
	              			  $check_user_test=$db->get_row($check_user_test_q);
	              			  if($date < $today){
	              			  	 if(!empty($check_user_test['id'])){
					              	  if($check_user_test['is_test_finished']==1){
					                    $written_status='TEST_FINISHED';
					                    $test_status='V';
					                    $rewrite='N';
					                  }else{
					                  	 $cpl_user_attempts_q="SELECT cpl_uta.* FROM cpl_usertest_attempts as cpl_uta WHERE cpl_uta.test_id=".$test_values['id']." and cpl_uta.user_test_id=".$check_user_test['id']."";
										 $cpl_user_attempts=$db->num_rows($cpl_user_attempts_q);
										  if($cpl_user_attempts>=3){
											//Expired (here user write the test but not consider)
					                        $written_status='TEST_BLOCKED';
					                        $test_status='B';
					                        $rewrite='Y';
										  }else{
												if($test_values['questionType']=="PT" || $test_values['questionType']=="CT" || $test_values['questionType']=="S" || $test_values['cycle_type']=="DTC" || $test_values['cycle_type']=="PPTC"){
						                            $written_status='RESUME_TEST';
						                            $test_status='R';
						                            $rewrite='Y';
						                        }else{
						                          $written_status='MISSED';
						                          $test_status='M';
						                          $rewrite='Y';
						                        }
										  }
					                  }
	              			  	 }else{
	              			  	 	  //Expired (here user write the test but not consider)
					                  $written_status='MISSED';
					                  $test_status='M';
					                  $rewrite='Y';
	              			  	 }
	              			  }else{
				              	if(!empty($check_user_test['id'])){
				                  if($check_user_test['is_test_finished']==1){
				                    $written_status='TEST_FINISHED';
				                    $rewrite='N';
			                      	$test_status='V';
				                  }else{
			                   		$cpl_user_attempts_q="SELECT cpl_uta.* FROM cpl_usertest_attempts as cpl_uta WHERE cpl_uta.test_id=".$test_values['id']." and cpl_uta.user_test_id=".$check_user_test['id']."";
									$cpl_user_attempts=$db->num_rows($cpl_user_attempts_q);
				                    if($cpl_user_attempts>=3){
				                        $written_status='TEST_BLOCKED';
				                        $test_status='B';
				                        $rewrite='Y';
				                    }else{
				                      $written_status='RESUME_TEST';
				                      $test_status='R';
				                      $rewrite='Y';
				                    }
				                    
				                  }
				                }else{
				                  if($interval->days>=1){
				                      $test_values['comming_soon_status']=true;
				                      $written_status='TAKE_TEST';
				                      $test_status='S';
				                      $rewrite='N';
				                  }else{
				                      $test_values['comming_soon_status']=false;
				                      $written_status='TAKE_TEST';
				                      $test_status='S';
				                      $rewrite='N';
				                  }
				                }
	              			  }

								$startdate = date('d-m-Y', strtotime($test_values['start_date']));
								$test_result[]=array('id'=>$test_values['id'],'title'=>$test_values['title'],'questionType'=>$test_values['questionType'],'testSize'=>$test_values['testSize'],'testTime'=>$test_values['testTime'],'negativeMarks'=>$test_values['negativeMarks'],'test_mode'=>$test_values['test_mode'],'course_id'=>$test_values['course_id'],'subject_id'=>$test_values['subject_id'],'cycle_id'=>$test_values['cycle_id'],'start_date'=>$startdate,'end_date'=>$test_values['end_date'],'cycle_type'=>$test_values['cycle_type'],'report_status'=>$test_values['report_status'],'cycle_seo_slug'=>$test_values['cycle_seo_slug'],'test_type'=>$test_values['test_type'],'test_status'=>$test_status,'rewrite'=>$rewrite,'written_exam_status'=>$written_status,'user_test_id'=>(!empty($check_user_test['id']))?$check_user_test['id']:"","test_date"=>$test_values['test_date'],"start_time"=>$test_values['start_time'],"end_time"=>$test_values['end_time'],"interval"=>$test_values['interval'],"exam_current_time"=>$test_values['exam_current_time'],"exam_start_date_time"=>$test_values['exam_start_date_time'],"exam_exact_start_time"=>$test_values['exam_exact_start_time'],"exam_exact_end_time"=>$test_values['exam_exact_end_time'],"exam_date"=>$test_values['exam_date'],"start_time"=>$test_values['start_time'],"end_time"=>$test_values['end_time'],"comming_soon_status"=>$test_values['comming_soon_status']);
						}

				/*$result[]=array('cycle_id'=>$value['cycle_id'],'course_id'=>$value['course_id'],'cycle_name'=>$value['cycle_name'],'cycle_seo_slug'=>$value['cycle_seo_slug'],'start_date'=>$value['start_date'],'end_date'=>$value['end_date'],'report_status'=>$value['report_status'],'cycle_type'=>$value['cycle_type'],'is_active'=>$value['is_active'],'package_id'=>$value['package_id'],'openstatus'=>$i++,'testresults'=>$test_result);*/
			//}

			$res['cpldata']=$test_result;

			$data=$res;
			$error=false;
			$message="Success";
			break;
			case "CPL_cycles":
			
			$res=[];
			$user_id=$post->user_id;
			//cycles
			$pck="SELECT * FROM course_packages WHERE course_id=".$post->course_id." AND package_status=1 AND privilege_ref='CPL'";
			$package=$db->get_row($pck);
			
			 $qry="SELECT * FROM cpl_cycles WHERE cycle_type='".$post->test_type."' AND course_id=".$post->course_id." AND cycle_id NOT IN(17,34) AND package_id=".$package['package_id'].""; 
			$result1=$db->get_results($qry);
			$i=0;
			foreach ($result1 as $value) 
			{ $tes = "SELECT ct.id,ct.title,ct.questionType,ct.testSize,ct.testTime,ct.negativeMarks,ct.test_mode,ct.course_id,ct.subject_id,cc.cycle_id,ct.start_date,ct.end_date,cc.cycle_type,cc.report_status,cc.cycle_seo_slug,ct.test_type FROM cpl_testconfigs as ct LEFT JOIN cpl_cycles as cc ON cc.cycle_id=ct.cycle_id WHERE cc.cycle_type='".$post->test_type."' AND cc.cycle_id=".$value['cycle_id']." AND cc.course_id=".$post->course_id.""; 
						$test_result1=$db->get_results($tes);
						$test_result=array();
						foreach ($test_result1 as $test_values) {
							  $cdate=new DateTime(date('Y-m-d H:i:s'));
				              $sdate=new DateTime($test_values['start_date']);
				              $edate=new DateTime($test_values['end_date']);
				              $test_values['test_date']=$sdate->format('d-m-Y');
				              $test_values['start_time']=$sdate->format('g A');
				              $test_values['end_time']=$edate->format('g A');
				              $interval=$sdate->diff($cdate);
				              $test_values['interval']=$interval;

							   //Written statu
				              //Test Expiry status
				              //Comming soon
				              $today=time();
				              $date=strtotime($test_values['end_date']);
				              $exam_date=date('Y-m-d',strtotime($test_values['start_date']));
				              $start_time=date('h:i A',strtotime($test_values['start_date']));
				              $end_time=date('h:i A',strtotime($test_values['end_date']));

				              $test_values['exam_current_time']=$today;
				              $test_values['exam_start_date_time']=$test_values['start_date'];
				              $test_values['exam_exact_start_time']=strtotime($test_values['start_date']);
				              $test_values['exam_exact_end_time']=strtotime($test_values['end_date'])*1000;
				              $test_values['exam_date']=$exam_date;
				              $test_values['start_time']=$start_time;
				              $test_values['end_time']=$end_time;
				              $test_values['comming_soon_status']=false;

				              //Check User Test Status
				              $check_user_test_q="SELECT ut.id,ut.is_test_finished,ut.is_valid_test FROM user_test as ut WHERE ut.test_id=".$test_values['id']." and ut.user_id=".$user_id."";
	              			  $check_user_test=$db->get_row($check_user_test_q);
	              			  if($date < $today){
	              			  	 if(!empty($check_user_test['id'])){
					              	  if($check_user_test['is_test_finished']==1){
					                    $written_status='TEST_FINISHED';
					                    $test_status='V';
					                    $rewrite='N';
					                  }else{
					                  	 $cpl_user_attempts_q="SELECT cpl_uta.* FROM cpl_usertest_attempts as cpl_uta WHERE cpl_uta.test_id=".$test_values['id']." and cpl_uta.user_test_id=".$check_user_test['id']."";
										 $cpl_user_attempts=$db->num_rows($cpl_user_attempts_q);
										  if($cpl_user_attempts>=3){
											//Expired (here user write the test but not consider)
					                        $written_status='TEST_BLOCKED';
					                        $test_status='B';
					                        $rewrite='Y';
										  }else{
												if($test_values['questionType']=="PT" || $test_values['questionType']=="CT" || $test_values['questionType']=="S" || $test_values['cycle_type']=="DTC" || $test_values['cycle_type']=="PPTC"){
						                            $written_status='RESUME_TEST';
						                            $test_status='R';
						                            $rewrite='Y';
						                        }else{
						                          $written_status='MISSED';
						                          $test_status='M';
						                          $rewrite='Y';
						                        }
										  }
					                  }
	              			  	 }else{
	              			  	 	  //Expired (here user write the test but not consider)
					                  $written_status='MISSED';
					                  $test_status='M';
					                  $rewrite='Y';
	              			  	 }
	              			  }else{
				              	if(!empty($check_user_test['id'])){
				                  if($check_user_test['is_test_finished']==1){
				                    $written_status='TEST_FINISHED';
				                    $rewrite='N';
			                      	$test_status='V';
				                  }else{
			                   		$cpl_user_attempts_q="SELECT cpl_uta.* FROM cpl_usertest_attempts as cpl_uta WHERE cpl_uta.test_id=".$test_values['id']." and cpl_uta.user_test_id=".$check_user_test['id']."";
									$cpl_user_attempts=$db->num_rows($cpl_user_attempts_q);
				                    if($cpl_user_attempts>=3){
				                        $written_status='TEST_BLOCKED';
				                        $test_status='B';
				                        $rewrite='Y';
				                    }else{
				                      $written_status='RESUME_TEST';
				                      $test_status='R';
				                      $rewrite='Y';
				                    }
				                    
				                  }
				                }else{
				                  if($interval->days>=1){
				                      $test_values['comming_soon_status']=true;
				                      $written_status='TAKE_TEST';
				                      $test_status='S';
				                      $rewrite='N';
				                  }else{
				                      $test_values['comming_soon_status']=false;
				                      $written_status='TAKE_TEST';
				                      $test_status='S';
				                      $rewrite='N';
				                  }
				                }
	              			  }

								$startdate = date('d-m-Y', strtotime($test_values['start_date']));
								$test_result[]=array('id'=>$test_values['id'],'title'=>$test_values['title'],'questionType'=>$test_values['questionType'],'testSize'=>$test_values['testSize'],'testTime'=>$test_values['testTime'],'negativeMarks'=>$test_values['negativeMarks'],'test_mode'=>$test_values['test_mode'],'course_id'=>$test_values['course_id'],'subject_id'=>$test_values['subject_id'],'cycle_id'=>$test_values['cycle_id'],'start_date'=>$startdate,'end_date'=>$test_values['end_date'],'cycle_type'=>$test_values['cycle_type'],'report_status'=>$test_values['report_status'],'cycle_seo_slug'=>$test_values['cycle_seo_slug'],'test_type'=>$test_values['test_type'],'test_status'=>$test_status,'rewrite'=>$rewrite,'written_exam_status'=>$written_status,'user_test_id'=>(!empty($check_user_test['id']))?$check_user_test['id']:"","test_date"=>$test_values['test_date'],"start_time"=>$test_values['start_time'],"end_time"=>$test_values['end_time'],"interval"=>$test_values['interval'],"exam_current_time"=>$test_values['exam_current_time'],"exam_start_date_time"=>$test_values['exam_start_date_time'],"exam_exact_start_time"=>$test_values['exam_exact_start_time'],"exam_exact_end_time"=>$test_values['exam_exact_end_time'],"exam_date"=>$test_values['exam_date'],"start_time"=>$test_values['start_time'],"end_time"=>$test_values['end_time'],"comming_soon_status"=>$test_values['comming_soon_status']);
						}

				$result[]=array('cycle_id'=>$value['cycle_id'],'course_id'=>$value['course_id'],'cycle_name'=>$value['cycle_name'],'cycle_seo_slug'=>$value['cycle_seo_slug'],'start_date'=>$value['start_date'],'end_date'=>$value['end_date'],'report_status'=>$value['report_status'],'cycle_type'=>$value['cycle_type'],'is_active'=>$value['is_active'],'package_id'=>$value['package_id'],'openstatus'=>$i++,'testresults'=>$test_result);
			}

			$res['cpldata']=$result;

			$data=$res;
			$error=false;
			$message="Success";
			break;
			case "getCplDemoCycle":
				$res=[];
				$user_id=$post->user_id;
				
						$qry = "select ct.id,ct.title,ct.questionType,ct.testSize,ct.testTime,ct.negativeMarks,ct.test_mode,ct.course_id,ct.subject_id,ct.slot_id,cc.cycle_id,ct.start_date,ct.end_date,cc.cycle_type from `cpl_testconfigs` as `ct` left join `cpl_cycles` as `cc` on `ct`.`cycle_id` = `cc`.`cycle_id` where cc.cycle_type='".$post->test_type."' AND cc.course_id=".$post->course_id." AND ct.testing_mode=1 AND ct.id IN(154,155,156,157) order by FIELD(ct.id,154,155,156,157)";
				
			
				$result=$db->get_results($qry);
				foreach ($result as $key=>$value) 
				{
					  $cdate=new DateTime(date('Y-m-d H:i:s'));
		              $sdate=new DateTime($result[$key]['start_date']);
		              $edate=new DateTime($result[$key]['end_date']);
		              $result[$key]['test_date']=$sdate->format('d-m-Y');
		              $result[$key]['start_time']=$sdate->format('g A');
		              $result[$key]['end_time']=$edate->format('g A');
		              $interval=$sdate->diff($cdate);
		              $result[$key]['interval']=$interval;

					   //Written statu
		              //Test Expiry status
		              //Comming soon
		              $today=time();
		              $date=strtotime($result[$key]['end_date']);
		              $exam_date=date('Y-m-d',strtotime($result[$key]['start_date']));
		              $start_time=date('h:i A',strtotime($result[$key]['start_date']));
		              $end_time=date('h:i A',strtotime($result[$key]['end_date']));

		              $result[$key]['exam_current_time']=$today;
		              $result[$key]['exam_start_date_time']=$result[$key]['start_date'];
		              $result[$key]['exam_exact_start_time']=strtotime($result[$key]['start_date']);
		              $result[$key]['exam_exact_end_time']=strtotime($result[$key]['end_date'])*1000;
		              $result[$key]['exam_date']=$exam_date;
		              $result[$key]['start_time']=$start_time;
		              $result[$key]['end_time']=$end_time;
		              $result[$key]['comming_soon_status']=false;

		              //Check User Test Status
		              $check_user_test_q="SELECT ut.id,ut.is_test_finished,ut.is_valid_test FROM user_test as ut WHERE ut.test_id=".$result[$key]['id']." and ut.user_id=".$user_id."";
	      			  $check_user_test=$db->get_row($check_user_test_q);
	    				if(!empty($check_user_test['id'])){
			              	  if($check_user_test['is_test_finished']==1){
			                    $written_status='TEST_FINISHED';
			                    $test_status='V';
			                    $rewrite='N';
			                  }else{
			                  	$written_status='RESUME_TEST';
	                			$test_status='R';
	                			$rewrite='Y';
			                  }
	      			  	 }else{
	      			  	 	  //Expired (here user write the test but not consider)
	      			  	 	  $result[$key]['comming_soon_status']=false;
			                  $written_status='TAKE_TEST';
	                		  $test_status='S';
	                		  $rewrite='N';
	      			  	 }

              			$result[$key]['written_exam_status']=$written_status;
              			$result[$key]['test_status']=$test_status;
              			$result[$key]['rewrite']=$rewrite;
				}

				$res['cpldata']=$result;
				$data=$res;
				$error=false;
				$message="Success";
			break;
			case "paytmChecksum":
				$paytmParams = array();

				function generateRandomString($length = 6) {
                   $characters = '0123456789';
                   $characters = str_shuffle($characters);
                   return substr($characters, 0, $length);
               }

				$orderid=generateRandomString(6);
				$userid=$post->userid;
				$package_id=$post->package_id;
				$paytmParams = array();
				
				//Package Details
				$query="SELECT * FROM course_packages WHERE package_id='".$package_id."'";
				$rowdata=$db->get_row($query);

				//Insert Order Details
				//$price=$rowdata['package_price'];
				$price="5.00";
				$coupon="";
				$voucher_id="";
				$orderdata = [   
	            'orderid'=>$orderid, 
	            'user_id'=>$userid, 
	            'package_id'=>$rowdata['package_id'],
	            'package_info'=>$rowdata['package_name'], 
	            'package_price'=>$price, 
	            'status'=>'PENDING',
	            'voucher_id'=>$voucher_id, 
	            'coupon_code'=>$coupon, 
	            'date'=>date('Y-m-d H:i:s'),            
	            ];
				$db->insert('orders',$orderdata);

				$paytmParams["MID"]=MID;
				$paytmParams["ORDER_ID"]=$orderid;
				$paytmParams["CUST_ID"]=$userid;
				$paytmParams["INDUSTRY_TYPE_ID"]=INDUSTRY_TYPE_ID;
				$paytmParams["CHANNEL_ID"]=CHANNEL_ID;
				$paytmParams["TXN_AMOUNT"]=$price;
				$paytmParams["WEBSITE"]=WEBSITE;
				$paytmParams["CALLBACK_URL"]=CALLBACK_URL.$orderid;

				$paytmChecksum=PaytmChecksum::generateSignature($paytmParams,MERCHANT_KEY);
				$verifyChecksum=PaytmChecksum::verifySignature($paytmParams,MERCHANT_KEY,$paytmChecksum);
				/*echo sprintf("generateSignature Returns: %s\n", $paytmChecksum);
				if($verifyChecksum == true){
				   echo "Checksum is verified";
				}else{
				   echo "Checksum is not verified";
				}
				exit;*/

				$data=['checksum'=>$paytmChecksum,'orderid'=>$orderid,'price'=>$price];
				$error=false;
				$message='Success';
			case "paytmStatus":
				$userid=$post->userid;
				$STATUS=$post->STATUS;
				$ORDERID=$post->ORDERID;
				$TXNID=$post->TXNID;
				$TXNAMOUNT=$post->TXNAMOUNT;
				$PAYMENTMODE=(!empty($post->PAYMENTMODE))?$post->PAYMENTMODE:"";;
				$TXNDATE=$post->TXNDATE;
				$RESPCODE=$post->RESPCODE;
				$GATEWAYNAME=(!empty($post->GATEWAYNAME))?$post->GATEWAYNAME:"";
				$RESPMSG=$post->RESPMSG;
				$BANKTXNID=(!empty($post->BANKTXNID))?$post->BANKTXNID:"";
				$BANKNAME=(!empty($post->BANKNAME))?$post->BANKNAME:"";

				//Check Order
				$query="SELECT od.id,od.txnid,od.status,od.package_id FROM orders as od WHERE od.orderid='".$ORDERID."'' and od.status='PENDING' and od.user_id=".$user_id." ORDER BY od.id DESC";
				$order=$db->get_row($query);
				if(!empty($order['id'])){
					//Update Payment Status
					$upd_where=['id'=>$ORDERID];
					$user_fields=['status'=>$STATUS,'payment_type'=>$PAYMENTMODE,'txnid'=>$TXNID];
					$db->update('orders',$user_fields,$upd_where);

					//Insert Order Info
					$order_info=[
	                    'ORDERID' => $ORDERID, 
	                    'TXNID' => $TXNID,
	                    'TXNAMOUNT'=> $TXNAMOUNT,
	                    'PAYMENTMODE' => $PAYMENTMODE, 
	                    'TXNDATE' => $TXNDATE,
	                    'STATUS' => $STATUS, 
	                    'RESPCODE' => $RESPCODE,
	                    'GATEWAYNAME' => $GATEWAYNAME, 
	                    'BANKTXNID' => $BANKTXNID,
	                    'BANKNAME' => $BANKNAME,
	                ];

	                $db->insert('order_info',$order_info);

	                $data['is_paid']=($STATUS=='TXN_SUCCESS')?true:false;
				}
				$error=false;
				$message="Success";
			break;
			case "verify_otp":
						
					$otp=$db->generatePassword(6,6);

					echo $otp; exit;

				$data['data']=$checkSum;
				$error=false;
				$message="Success";
			break;
			case "checkregular":
					

					function valid_email($str) 
					{
					return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
					}

						$str='9542921119';
					$chk = valid_email($str);

					print_r($chk);

					 exit;

				$data['data']=$checkSum;
				$error=false;
				$message="Success";
			break;
			case "uploadprofileImage":
					
						   $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);

						   $filename=rand().time().'.'.$ext;
						   	$siteurl ='https://compete.etutor.co';
						     $target = $siteurl.'./images/profile/'.$filename;
							   if(move_uploaded_file($_FILES['image']['tmp_name'],$target))
							   {
			                        $path ='https://compete.etutor.co/images/profile/'.$filename;
							       $upuser=$db->query("update user_profiles set photo='".$path."' where user_id='".$_REQUEST['userid']."'");
							       $image = 'https://compete.etutor.co/images/profile/'.$filename;
							       $response['status']="success";
							   }
							   else
							   {
							        $response['status']='error';
							  	 	$response['message']='Unable to move the image';
							   }
					 
			break;
			case "beforesaving":

					$course_id = $post->course_id;
			        $course = $post->course;
			        $class_id = $post->class_id;
			        $class_name = $post->class_name;
			        $user_id = $post->user_id;
			        $is_written_test=false;
			        $user_test_id=null;
			        $data=[];
		if(!empty($post->tid))
		{
            $tid=$post->tid;
            $qids=$post->qids;
            $qdataids=json_decode($post->qdataids);
            $default_score=$post->default_score;
            $course_id=$post->course_id;
            $is_test_status=$post->is_test_status;
            
                $query="SELECT * FROM user_test WHERE test_id='".$tid."' and user_id='".$user_id."'";
				$user_test_info=$db->get_row($query);
			if(!empty($user_test_info['test_id']))
			{
                $is_written_test=true;
                $user_test_id=$user_test_info['test_id'];
                $data['is_written_test']=$is_written_test;
                $data['user_test_id']=$user_test_id;
            }
            else
            {

            	$qry="SELECT * FROM cpl_testconfigs WHERE id='".$tid."'";
				$testconfig=$db->get_row($qry);

                $cdate = new DateTime(date('Y-m-d H:i:s'));
                $edate = new DateTime($testconfig['end_date']);
                $interval = $edate->diff($cdate);
                $h=$interval->format('%h')*60;
                $m=$interval->format('%i');
                $remaing_exam_duration=($h+$m);
                $late_duration=($testconfig['testTime']-$remaing_exam_duration);

              

                $testconfig['remaing_exam_duration']=$remaing_exam_duration;
                $testconfig['late_duration']=$late_duration;
                
               
                $user_test=['user_id'=>$user_id,
                            'duration'=>0,
                            'score'=>0,
                            'test_name'=>$testconfig['title'],
                            'test_type'=>$testconfig['questionType'],
                            'test_size'=>$testconfig['testSize'],
                            'negative_marks'=>$testconfig['negativeMarks'],
                            'for_total_score'=>($testconfig['testSize']*$default_score),
                            'course_id'=>$course_id,
                            'for_total_time'=>$testconfig['testTime'],
                            'test_id'=>$tid,
                            'user_late_duration'=>$testconfig['late_duration']];

		                $insert_status=$db->insert('user_test',$user_test);
		                $test_id=$db->lastid();
		                if(!empty($test_id)){
		                    $user_questions=[];
		                    for($q=0;$q<count($qids);$q++){
		                       //Question Choice
		                       $qchoice_type='';
		                       $qchoice_type_find=false;
		                       if(!empty($qdataids[$q]->qchoice_type)){
		                            if($qdataids[$q]->qchoice_type=='M' || $qdataids[$q]->qchoice_type=='N'){
		                                $qchoice_type=$qdataids[$q]->qchoice_type;
		                                $qchoice_type_find=true;
		                            }
		                       }

		                       if($qchoice_type_find==false){

		                             $qry1="SELECT qchoice_type FROM cpl_question WHERE id='".$qid."'";
									 $qchoice_data=$db->get_row($qry1);

		                            $qchoice_type=$qchoice_data['qchoice_type'];
		                            $qchoice_type_find=true;
		                       }

		                       $user_questions[]=['test_id'=>$test_id,
		                                           'question_id'=>$qids[$q],
		                                           'qpin_point'=>1,
		                                           'qchoice_type'=>$qchoice_type
		                                          ]; 
		                    }

		                    if(count($user_questions)){
		                    	$db->insert('user_test_data',$user_questions);
		                    }

		                    if($testconfig['questionType']=='MT' && $post->cycle_type=='GTC'){
		                      
		                        $db->insert('user_test_data',['uid'=>$user_id,'test_id'=>$tid,'user_test_id'=>$test_id,'test_title'=>$testconfig['title'],'maxtime'=>$testconfig['end_date'],'status'=>0]);

		                        $db->insert('cpl_usertest_attempts',['test_id'=>$tid,'user_test_id'=>$test_id,'user_id'=>$user_id,'test_name'=>$testconfig['title'],'start_time'=>date('Y-m-d H:i:s')]);
		                    }

		                    $is_written_test=true;
		                    $user_test_id=$test_id;
		                    $data['is_written_test']=$is_written_test;
		                    $data['user_test_id']=$user_test_id;

		                    if($post->is_test_status=='M' || $post->is_test_status=='B'){
		                        $data['remaing_exam_duration']=$testconfig['testTime'];
		                    }else{
		                        $data['remaing_exam_duration']=$testconfig['remaing_exam_duration'];
		                    }
		                    
		                    
		                }

            }

             $response["data"]=$data;
			 $response["error"]=false;
			 $response["message"]="Success";
			
        }

			break;
			case "loadquestion":
					$qid=$post->id;
			        $tid=$post->tid;
			        $user_test_id=$post->user_test_id;
			        $user_id=$post->user_id;
			        $qpin_point=($post->qpin_point>0)?$post->qpin_point:0;

			        //Question Data

			        $qry = "select q.id,q.category,q.topic_id,q.subject_id,q.course_id,qi.question,qi.answer,q.score,q.is_jee_numerical,q.qchoice_type,q.negative_marks from `cpl_question` as `q` left join `cpl_question_info` as `qi` on q.id = qi.question_id where qi.question_id='".$qid."'";
                	$question_data=$db->get_row($qry);

                	 //User Test Data Info 
        if(!empty($user_test_id)){
            
                $qry1 = "select solving_time_diff,user_score,user_answer,is_attempted,qpin_point from user_test_data where question_id='".$qid."' and test_id='".$user_test_id."'";
                $user_test_data=$db->get_row($qry1);

            $question_data->solving_time_diff=($user_test_data['is_attempted']>0)?$user_test_data['solving_time_diff']:0;
            $question_data->user_score=($user_test_data['is_attempted']>0)?$user_test_data['user_score']:0;
            $question_data->user_answer=($user_test_data['is_attempted']>0)?$user_test_data['user_answer']:'';
            $question_data->is_attempted=($user_test_data['is_attempted']>0)?$user_test_data['is_attempted']:0;
            $question_data->qpin_point=$user_test_data['qpin_point'];
        if($qpin_point>0)
        {
                $update_where=['test_id'=>$user_test_id,'question_id'=>$qid];
                $update_q=['qpin_point'=>$qpin_point];

                $db->update('user_test_data',$update_q,$update_where);
            
        }else{
            $question_data->solving_time_diff=0;
            $question_data->user_score=0;
            $question_data->user_answer='';
            $question_data->is_attempted=0;
            $question_data->qpin_point=1;
        }
        
        		//Question Options
                 $qry2 = "select * from cpl_question_options where question_id='".$qid."'";
                 $question_options=$db->get_row($qry2);
        		 $question_data->options=$question_options;

        		 //Category
        			$qry3 = "select ch.chapter_id,ch.chapter,s.subject_id,s.subject,c.course_id,c.course from `chapters` as `ch` left join `subjects` as `s` on ch.subject_id = s.subject_id left join `courses` as `c` on s.course_id = c.course_id where ch.chapter_id='".$question_data->category."'";
                	$category_q=$db->get_row($qry3);
       
       			 $question_data->cats=$category_q;
		        $question_data->answer=base64_encode($question_data->answer);
		        if(isset($post->index))
		        {
		        	$question_data->index=$post->index;
		        }
		        else
		        {
		        	 $question_data->qs_status=false;
		        }

       


        $response["data"]=$question_data;
		$response["error"]=false;
		$response["message"]="Success";
}
			break;
			case "get_qids":
			$qids=[];
	        $qtype = $post->questionType;
	        $tsize = (int)$post->testSize;
	        $course_id=$post->course_id;
	        $tid=$post->tid;
	        $test_mode=$post->test_mode;
	        $common_paper=$post->common_paper;
	        $cp_testconfig_q="select `cpltc`.`comman_qlist` from `cpl_testconfigs` as `cpltc` where `cpltc`.`id`=$tid";
	        $cp_testconfig=$db->get_row($cp_testconfig_q);
	        $qids=explode(",",rtrim($cp_testconfig['comman_qlist'],","));
	        //Get Subjects Questions
	        $subjects=[];
	        if($qtype=='MT' || $qtype=='CT'){
		        $k=$new_order=0;
		        $subject_q="select tm.test_id,tm.category_level_id,s.subject,tm.category_order,tm.questions_size,tm.course_id from `cpl_testconfigs_mock` as `tm` 
		        	left join `subjects` as `s` on `tm`.`category_level_id` = `s`.`subject_id` 
		        	where `tm`.`test_id`=$tid";
		        $sujects=$db->get_results($subject_q);
		        foreach($sujects as $key=>$suject){
		        	$new_order=$new_order+$suject['questions_size'];
		        	$array1=[];
		        	if($suject['category_order']==1){ 
		                for($j=0;$j<$suject['questions_size'];$j++){
		                    //array_push($array1,$j);
		                    $array1[] = array('ids' =>$j+1);
		                    $suject['cat_range']=$array1;
		                    $k++;
		                }
		            }else{
		                for($j=0;$j<$suject['questions_size'];$j++){
		                   // array_push($array1,$k);
		                	$array1[] = array('ids' =>$k+1);
		                    $suject['cat_range']=$array1;
		                    $k++;
		                }
		            }

		            array_push($subjects,$suject);
		        }
	        }

	        if(count($qids)>0){
		        $data['qids']=$qids;
		        $data['subjects']=$subjects;
		        $error=false;
		        $message="Success";
	        }
			break;

		default:
			break;
	}
}

$response["data"]=$data;
$response["error"]=$error;
$response["message"]=$message;
echo json_encode($response);

?>