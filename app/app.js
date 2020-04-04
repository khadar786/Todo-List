var todoApp=angular.module('todoApp',['ngSanitize','ngRoute','ngAnimate', 'toaster','ui.bootstrap','angularUtils.directives.dirPagination']);
todoApp.config(['$routeProvider',function ($routeProvider) {
	$routeProvider.
			when('/', {
                title: 'Login',
                templateUrl:'partials/login.html',
                controller: 'authCtrl'
            }).when('/login', {
				title: 'Login',
				templateUrl: 'partials/login.html',
				controller: 'authCtrl'
			}).when('/to-do-list', {
				title: 'To-do List',
				templateUrl: 'partials/list.html',
				controller: 'todoCtrl'
			}).when('/profile', {
				title: 'Profile',
				templateUrl: 'partials/profile.html',
				controller: 'todoCtrl'
			}).when('/write-comment/:todo_id', {
				title: 'Write Comment',
				templateUrl: 'partials/write_comment.html',
				controller: 'commentCtrl'
			}).otherwise({
                redirectTo: '/login'
            });
}]).run(function($rootScope, $location){
	$rootScope.$on("$routeChangeStart", function (event,next,current){
		$rootScope.authenticated=false;
		var user_id=localStorage.getItem("user_id");
		//
		if(user_id){
			$rootScope.authenticated=true;
			$rootScope.user_id=localStorage.getItem("user_id");
            $rootScope.first_name=localStorage.getItem("first_name");
            $rootScope.last_name=localStorage.getItem("last_name");
            $rootScope.email=localStorage.getItem("email");
            $rootScope.user_level=localStorage.getItem("user_level");
            $rootScope.user_status=localStorage.getItem("user_status");
    		var seach=$location.path();
			//array= seach.split('/');
			var array=new Array();
			array=seach.split('/');
			var default_url='/to-do-list';
			if(array[1]==''){
				default_url='/to-do-list';
			}else{
				if(array[1]!='login'){
					console.log($location.path());
					default_url=seach;
				}else{
					default_url='/to-do-list';
				}
				
			}

			$location.path(default_url);
		}else{
			$location.path("/login");
		}
		
	});
});