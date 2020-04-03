<?php
include('includes/config.php');
//include('config_paytm.php');
//include('encdec_paytm.php');
//include('functions.php');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type,Accept,Authorization');
$response=array();
$error=true;
$message="Fail";
$data=[];

if(!empty($_POST['action'])){
	switch($_POST['action']){
		case "user_auth":
			$email=$_POST['email'];
			$password=sha1($_POST['userpwd']);
			$user_q="SELECT up.user_id,up.first_name,up.last_name,up.email,up.user_level,up.user_status,up.mobile,up.dob FROM user_profiles as up WHERE up.email='".$email."' AND up.password='".$password."'";
			$user_obj=$db->get_row($user_q);
			 if(!empty($user_obj["user_id"])){
			 	$data=[
                		'user_id'=>$user_obj["user_id"],
                		'first_name'=>$user_obj["first_name"],
                		'last_name'=>$user_obj["last_name"],
                		'email'=>$user_obj["email"],
                		'mobile'=>$user_obj["mobile"],
                		'dob'=>$user_obj["dob"],
                		'user_level'=>$user_obj["user_level"],
                		'user_status'=>$user_obj["user_status"]
                	  ];
                $error=false;
			 	$message="Success";
			 }else{
			 	$error=true;
			 	$message="Invalid credentials";
			 	$redirect="";
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