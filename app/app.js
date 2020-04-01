var todoApp=angular.module('todoApp',['ngRoute','ngAnimate', 'toaster','ui.bootstrap']);
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
			}).when('/logout', {
				title: 'logout',
				controller: 'authCtrl'
			}).otherwise({
                //redirectTo: '/login'
            });
}]).run(function($rootScope, $location){
	$rootScope.$on("$routeChangeStart", function (event, next, current) {
		//$location.path("/login");
	});
});