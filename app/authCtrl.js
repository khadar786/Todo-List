todoApp.controller('authCtrl',function ($scope,$rootScope,$routeParams,$location,$http,$window,toaster){
	//console.log('r');
	$http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
	$scope.loginuser={};
	$scope.loader='<i class="fa fa-circle-o-notch fa-spin" style="font-size:22px"></i>';
	$scope.loginbtn=false;
	$scope.auth_service='services/api.php';
	$scope.login_submitted=false;
	//Login form
	$scope.submitLoginForm=function(){
		$scope.loginbtn=true;
		$scope.login_submitted=true;
		if($scope.LoginForm.$valid){
			 $scope.loginuser.action='user_auth';
			 var params=$.param($scope.loginuser);
			 $http({method:'POST',url:$scope.auth_service,data:params}).
		        then(function(response){
		          if(response.data.error){
		          	toaster.pop('error', "error", response.data.message);
		          }else{
		          	  $rootScope.authenticated=true;
		          	  var user=response.data.data;
			          localStorage.setItem("user_id",user.user_id);
			          localStorage.setItem("first_name",user.first_name);
			          localStorage.setItem("last_name",user.last_name);
			          localStorage.setItem("email",user.email);
			          localStorage.setItem("user_level",user.user_level);
			          localStorage.setItem("user_status",user.user_status);
			          $location.path('/to-do-list');
		          }
		          
		          $scope.loginbtn=false;
		          //$scope.status = response.status;
		          //$scope.data = response.data;
		        },function(response){
		          //$scope.data = response.data || 'Request failed';
		          //$scope.status = response.status;
		          $scope.loginbtn=false;
		      });
		}else{
			$scope.loginbtn=false;
		}
		
	}
});