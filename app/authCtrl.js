todoApp.controller('authCtrl',function ($scope,$rootScope,$routeParams,$location,$http,$location,$window){
	console.log('r');
	$http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded";
	$scope.loginuser={};
	$scope.loader='<i class="fa fa-circle-o-notch fa-spin" style="font-size:22px"></i>';
	$scope.loginbtn=false;
	$scope.auth_service='services/api.php';
	//Login form
	$scope.submitLoginForm=function(){
		$scope.loginbtn=true;
		if($scope.LoginForm.$valid){
			 $scope.loginuser.action='user_auth';
			 var params=$.param($scope.loginuser);
			 $http({method:'POST',url:$scope.auth_service,data:params}).
		        then(function(response){
		          //$scope.status = response.status;
		          //$scope.data = response.data;
		        },function(response){
		          //$scope.data = response.data || 'Request failed';
		          //$scope.status = response.status;
		      });
			
				console.log($scope.loginuser);
				//localStorage.setItem("mytime", Date.now());
				//console.log(localStorage.getItem("mytime"));
				//localStorage.removeItem("key");
				//localStorage.clear();
		}
		//$scope.loginbtn=false;
	}
});