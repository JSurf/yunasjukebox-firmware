/*global angular */
'use strict';

/**
 * The main app module
 * @name app
 * @type {angular.Module}
 */
var app = angular.module('jukeboxApp', ['ngRoute', 'flow'])
  .config(['flowFactoryProvider', function (flowFactoryProvider) {
    flowFactoryProvider.defaults = {
      target: 'api/index.php/rfid/upload',
      permanentErrors: [500, 501],
      maxChunkRetries: 1,
      chunkRetryInterval: 5000,
      simultaneousUploads: 1
    };
  }])
  .config(['$routeProvider', function($routeProvider) {
    $routeProvider.
      when('/albums', {
        templateUrl: 'partials/album-list.html',
        controller: 'AlbumsController'
      }).
      when('/album/:rfidTag', {
        templateUrl: 'partials/album-upload.html',
        controller: 'UploadFormController'
      }).
      otherwise({
        redirectTo: '/albums'
      });
  }])
  .controller('NavbarController', function($scope, $location) {
	  $scope.isActive = function (viewLocation) {
		  if($location.path().indexOf(viewLocation) != -1){
			  return true;
		  }
		  return false;	  
	  }
  })
  .controller('PlayerController', function($scope, $http, $interval) {
	  var statusInterval;
          var requestActive = false;

	  $scope.sendCommand = function(command) {
		  $http.get('api/index.php/player/'+command)
		  .success(function(data) {
                     requestActive = false;          
	             $scope.currentsong = data;
		  }).error(function() { 
                     requestActive = false;
                  });
	  }

	  $scope.statusInterval = function() {
		  if ( angular.isDefined(statusInterval) ) return;
		  $scope.rfidScan = undefined;
		  statusInterval = $interval(function() {
		     if (!requestActive) { 	  
                        $scope.sendCommand('status');
                     }
		  }, 10000);
	  };

	  $scope.stopStatusInterval = function() {
		  if (angular.isDefined(statusInterval)) {
			  $interval.cancel(statusInterval);
			  statusInterval = undefined;
		  }
	  };
	  
	  $scope.$on('$destroy', function() {
		  // Make sure that the interval is destroyed too
		  $scope.stopStatusInterval();
	  });

	  $scope.statusInterval();
          $scope.sendCommand('status');	  
  })
  .controller('UploadFormController', function($scope, $location, $http, $interval, $routeParams) {
      $scope.formData = {};
      $scope.flowData = {};
	  $scope.uploadComplete = false;

	  $scope.albumDetails = function(rfidTag) {
		   $http.get('api/index.php/rfid/'+rfidTag)
		   .success(function(data) {
			   $scope.formData.title = data.info.title;
			   $scope.tracks = data.tracks;
		   });
	  }
  
      $scope.submit = function() {
	      $scope.uploadComplete = false;
		  $scope.formData.files = [];
	      angular.forEach($scope.flowData.flow.files, function(obj, key) {
		    $scope.formData.files.push(obj.file);
		  }); 
		  $http.post('api/index.php/rfid/register',$scope.formData)
		  .success(function(data) {
				console.log(data);
				$location.path("/albums");
		  });
	  };
      
	  var rfidInterval;
      
	  $scope.rfidInterval = function() {
  	    if ( angular.isDefined(rfidInterval) ) return;
        $scope.rfidScan = undefined;
        
        var requestActive = false; 
        rfidInterval = $interval(function() {
                if (!requestActive) {
                   requestActive = true;
		   $http.get('api/index.php/rfid/last')
		   .success(function(data) {
                           requestActive = false;
			   if (angular.isDefined($scope.rfidScan) && data != $scope.rfidScan) {
				  $scope.formData.rfidTag = data;
				  $scope.albumDetails(data);
			   }
			   $scope.rfidScan = data;
		   }).error(function() {
                      requestActive = false; 
                   });
                }
        }, 1000);
      };
      
	  $scope.stopRfidInterval = function() {
        if (angular.isDefined(rfidInterval)) {
          $interval.cancel(rfidInterval);
          rfidInterval = undefined;
        }
      };
	  
	  $scope.$on('$destroy', function() {
        // Make sure that the interval is destroyed too
        $scope.stopRfidInterval();
      });
	  
	  $scope.rfidInterval();
	  
	  if ($routeParams.rfidTag !== "new") {
		 $scope.formData.rfidTag = $routeParams.rfidTag;
		 $scope.albumDetails($routeParams.rfidTag)
	  }
  })
  .controller('AlbumsController', function($scope, $http, $interval) {

	  $scope.refresh = function() {
		  $http.get('api/index.php/rfid/list')
		  .success(function(data) {
		     $scope.rfidTags = data;
		  });
	  };
	  
	  $scope.deleteTag = function(tag) {
		  $scope.tagToDelete = tag;
		  $('#modalDeleteRFID').modal('show');
	  }

	  $scope.deleteTagConfirmed = function() {
		  $http.get('api/index.php/rfid/delete/'+$scope.tagToDelete)
		  .success(function(data) {
		     $scope.refresh();	  
		  });
		  $('#modalDeleteRFID').modal('hide');
	  }
	  $scope.playTag = function(tag) {
		  $http.get('api/index.php/player/play/'+tag)
		  .success(function(data) {
			  $scope.refresh();	  
		  });
	  }

	  $scope.refresh();	  
  });
