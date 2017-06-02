var angularmap = angular.module('angularmap', []);

jQuery(document).ready(function() {
    angular.bootstrap(document.getElementById('map-container'),['angularmap']);
});


angularmap.controller('mapcontroller', function($scope) {
    $scope.hello = 'its work hello';
});