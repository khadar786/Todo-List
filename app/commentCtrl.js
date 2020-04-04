todoApp.controller('commentCtrl',function($scope,$rootScope,$routeParams,$location,$http,$location,$window,$timeout){
			$http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
			$scope.authenticated=$rootScope.authenticated=true;
			$scope.user_id=$rootScope.user_id;
            $scope.first_name=$rootScope.first_name;
            $scope.last_name=$rootScope.last_name;
            $scope.user_name=$scope.first_name+' '+$rootScope.last_name;
            $scope.email=$rootScope.email;
            $scope.user_level=$rootScope.user_level;
            $scope.user_status=$rootScope.user_status;
            $scope.addtask={};
            $scope.addtask_submitted=false;
            $scope.addmsg='';
    		$scope.task={};
    		$scope.comments={};
            $scope.auth_service='services/api.php';

    //Save Task Info
    $scope.submitAddTaskForm=function(valid){
    	$scope.addtask_submitted=true;
    	if(valid){
    		$scope.addtask.action='addcomment';
    		$scope.addtask.todo_id=$routeParams.todo_id;
    		$scope.addtask.user_id=$scope.user_id;
			var params=$.param($scope.addtask);
			$http({method:'POST',url:$scope.auth_service,data:params}).
		        then(function(response){
		          if(response.data.error){
		          	$scope.addmsg=response.data.message;
		          	//toaster.pop('error', "error", response.data.message);
		          }else{
		          	document.getElementById("AddTaskForm").reset();
		          	$scope.addmsg=response.data.message;
		          	$timeout(function(){
		          		$scope.addmsg='';
		          	},500);
		          	$scope.getCommentList();
		          }
		        },function(response){
		          
		      });
    	}

    	if ($scope.$root.$$phase != '$apply' && $scope.$root.$$phase != '$digest') {
			$scope.$apply();
		}
    }

  
	//View Comment
    $scope.getTask=function(){
    	var params=$.param({'action':'view_task','todo_id':$routeParams.todo_id});
		$http({method:'POST',url:$scope.auth_service,data:params}).
	        then(function(response){
	          $scope.task=response.data.data.task;
	          $scope.getCommentList();
	        },function(response){
	          
	      });
    }

    if($routeParams.todo_id){
    	$scope.getTask();
    }

    //View Comment
    $scope.getCommentList=function(){
    	var params=$.param({'action':'comment_list','todo_id':$routeParams.todo_id});
		$http({method:'POST',url:$scope.auth_service,data:params}).
	        then(function(response){
	          $scope.comments=response.data.data.comments;
	        },function(response){
	          
	      });
    }

	//Logout
	$scope.logout=function(){
		localStorage.removeItem("user_id");
		localStorage.removeItem("first_name");
		localStorage.removeItem("last_name");
		localStorage.removeItem("email");
		localStorage.removeItem("user_level");
		localStorage.removeItem("user_status");
		$location.path("/login");
	}
});