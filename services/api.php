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
		case "addtask":
			 $user_id=$_POST['user_id'];
			 $title=$_POST['title'];
			 $description=$_POST['description'];

			 //Check Task
			 $check_q="SELECT todo_id FROM user_todo_list WHERE title='".$title."' AND created_by_user=$user_id";
			 $task=$db->get_row($check_q);
			 if(empty($task['todo_id'])){
			 	$task_details=['title'=>$title,
			 				   'description'=>$description,
			 				   'created_by_user'=>$user_id
			 				  ];
			 	$insert_status=$db->insert('user_todo_list',$task_details);
				$todo_id=$db->lastid();
				$data['todo_id']=$todo_id;
				$error=false;
			 	$message="Task added successfully";
			 }else{
			 	$error=true;
			 	$message="Task already existed!";
			 }
		break;
		case "edittask":
			 $todo_id=$_POST['todo_id'];
			 $user_id=$_POST['user_id'];
			 $title=$_POST['title'];
			 $description=$_POST['description'];

			 //Check Task
			 $check_q="SELECT todo_id FROM user_todo_list WHERE title='".$title."' AND created_by_user<>$user_id";
			 $task=$db->get_row($check_q);
			 if(!empty($task['todo_id'])){
			 	$task_details=['title'=>$title,
			 				   'description'=>$description
			 				  ];
			 	$insert_status=$db->update('user_todo_list',$task_details,['todo_id'=>$todo_id]);
				$data['todo_id']=$todo_id;
				$error=false;
			 	$message="Success";
			 }else{
			 	$error=true;
			 	$message="Task already existed!";
			 }
		break;
		case "deltask":
			  $todo_id=$_POST['todo_id'];
			  $del_q="DELETE FROM user_todo_list WHERE todo_id=$todo_id";
			  $del_status=$db->delete('user_todo_list',['todo_id'=>$todo_id]);
			  if($del_status){
			  	$error=false;
			 	$message="Task deleted successfully";
			  }else{
			  	$error=true;
			 	$message="Task deleted unsuccessfully";
			  }
		break;
		case "view_task":
		    $todo_id=$_POST['todo_id'];
			$task_q="SELECT * FROM user_todo_list as ut WHERE ut.todo_id=$todo_id";
			$task=$db->get_row($task_q);
			$data['task']=$task;
			$error=false;
			$message="Success";
		break;
		case "comment_list":
			$todo_id=$_POST['todo_id'];
			$comment_list_q="SELECT c.*,up.first_name FROM comments as c
							LEFT JOIN user_profiles as up ON c.comment_by_user=up.user_id 
							WHERE c.todo_id=$todo_id";
			$comment_list=$db->get_results($comment_list_q);
			$data['comments']=$comment_list;
			$error=false;
			$message="Success";
		break;
		case "addcomment":
			$todo_id=$_POST['todo_id'];
			$user_id=$_POST['user_id'];
			$title=$_POST['title'];
			$comment=$_POST['description'];

		 	$comment_details=['todo_id'=>$todo_id,
		 				   'comment_by_user'=>$user_id,
		 				   'title'=>$title,
		 				   'comment'=>$comment
		 				  ];
		 	$insert_status=$db->insert('comments',$comment_details);
			$comment_id=$db->lastid();
			$data['comment_id']=$comment_id;
			$error=false;
		 	$message="Success";
		break;
		case "users":
			$users_list_q="SELECT * FROM user_profiles as up WHERE 1";
			$users_list=$db->get_results($users_list_q);
			$data['users_list']=$users_list;
			$error=false;
			$message="Success";
		break;
		case "todo_list":
			$num_rec_per_page=5;
			if (isset($_POST["page"])) { $page  = $_POST["page"]; } else { $page=1; };
			$start_from = ($page-1) * $num_rec_per_page;

			$search='';
			if($_POST['user_search']!=''){
				$search.="AND (utl.title LIKE '%".$_POST["user_search"]."%' OR utl.description LIKE '%".$_POST["user_search"]."%')";
			}

			if($_POST['created_by']!=""){
				$search.="AND utl.created_by_user=".$_POST['created_by']."";
			}

			$todo_list_total_q="SELECT utl.todo_id FROM user_todo_list as utl 
						 LEFT JOIN user_profiles as up ON utl.created_by_user=up.user_id 
						 WHERE 1 $search";
			$todo_list_total=$db->num_rows($todo_list_total_q);
						 
			$todo_list_q="SELECT utl.*,up.user_id,up.first_name,up.last_name 
						 FROM user_todo_list as utl 
						 LEFT JOIN user_profiles as up ON utl.created_by_user=up.user_id 
						 WHERE 1 $search ORDER BY utl.todo_id DESC 
						 LIMIT $start_from,$num_rec_per_page";
			$todo_list=$db->get_results($todo_list_q);			 
			if($todo_list_total>0){
				$data['todo_list']=$todo_list;
				$data['total']=$todo_list_total;
				$data['start_from']=$start_from;
				$error=false;
				$message="Success";
			}else{
				$error=true;
				$message="Fail";
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