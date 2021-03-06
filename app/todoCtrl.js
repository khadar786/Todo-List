todoApp.controller('todoCtrl',function($scope,$rootScope,$routeParams,$location,$http,$location,$window,$timeout){
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
            $scope.users=[];
            $scope.todolist=[];
            $scope.totalItems = 0;
            $scope.pagination = {
        		current: 1
    		};
    		$scope.start_from=0;
    		$scope.task={};
    		$scope.comments={};
            $scope.auth_service='services/api.php';
    //Add Task
    $scope.addTask=function(){
    	$scope.addmsg='';
    	$scope.addtask={};
    	$scope.addtask_submitted=false;
		$("#add_task").modal({
            backdrop: 'static',
            keyboard: false
         });
		//document.getElementById("AddTaskForm").reset();
    	if ($scope.$root.$$phase != '$apply' && $scope.$root.$$phase != '$digest') {
			$scope.$apply();
		}
    	//$('#PauseTestID').modal('hide');
    }

    //Save Task Info
    $scope.submitAddTaskForm=function(valid){
    	$scope.addtask_submitted=true;
    	if(valid){
    		$scope.addtask.action='addtask';
    		$scope.addtask.user_id=$scope.user_id;
			var params=$.param($scope.addtask);
			$http({method:'POST',url:$scope.auth_service,data:params}).
		        then(function(response){
		          if(response.data.error){
		          	$scope.addmsg=response.data.message;
		          	//toaster.pop('error', "error", response.data.message);
		          }else{
		          	$scope.addmsg=response.data.message;
		          	$scope.getTodoList(1);
		          }
		        },function(response){
		          
		      });
    	}

    	if ($scope.$root.$$phase != '$apply' && $scope.$root.$$phase != '$digest') {
			$scope.$apply();
		}
    }

    //Edit Task
    $scope.editTask=function(tindex){
    	$("#edit_task").modal({
            backdrop: 'static',
            keyboard: false
         });
    }

    //Users
    $scope.getUsers=function(){
    	var params=$.param({'action':'users'});
		$http({method:'POST',url:$scope.auth_service,data:params}).
	        then(function(response){
	          $scope.users=response.data.data.users_list;
	          $scope.getTodoList(1);
	        },function(response){
	          
	      });
    }

    //todo list
    $scope.getTodoList=function(pageNumber){
    	var user_search=created_by="";
		if(angular.isDefined($scope.user_search))
			user_search=$scope.user_search;
		else
			user_search="";

		if(angular.isDefined($scope.created_by))
			created_by=$scope.created_by;
		else
			created_by="";

		var params=$.param({'user_search':user_search,'created_by':created_by,'page':pageNumber,'action':'todo_list'});
		$http({method:'POST',url:$scope.auth_service,data:params}).
	        then(function(response){
	          $scope.todolist=response.data.data.todo_list;
	          $scope.totalItems=response.data.data.total;
	          $scope.pagination.current=pageNumber;
	          $scope.start_from=response.data.data.start_from;
	        },function(response){
	          
	      });
    }

    //Filter
    $scope.getUserTodoList=function(){
    	$scope.getTodoList(1);
    }

    //Getting students based page
	$scope.pageChanged=function(newPage) {
		$scope.getTodoList(newPage);
	};

	//Clear Search
	$scope.clearSearch=function(){
		$scope.user_search='';
		$scope.created_by='';
		$scope.getTodoList(1);
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

	$scope.getUsers();
});