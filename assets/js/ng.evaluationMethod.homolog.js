(function (angular) {
    "use strict";

    var module = angular.module('ng.evaluationMethod.homolog', ['ngSanitize']);

    module.config(['$httpProvider', function ($httpProvider) {
            $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
            $httpProvider.defaults.headers.patch['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
            $httpProvider.defaults.transformRequest = function (data) {
                var result = angular.isObject(data) && String(data) !== '[object File]' ? $.param(data) : data;

                return result;
            };
        }]);

    module.factory('HomologEvaluationMethodService', ['$http', '$rootScope', function ($http, $rootScope) {
            return {
                serviceProperty: null,
                getEvaluationMethodConfigurationUrl: function () {
                    return MapasCulturais.createUrl('evaluationMethodConfiguration', 'single', [MapasCulturais.evaluationConfiguration.id]);
                },
                patchEvaluationMethodConfiguration: function (entity) {
                    entity = JSON.parse(angular.toJson(entity));
                    return $http.patch(this.getEvaluationMethodConfigurationUrl(), entity);
                }
            };
        }]);

    module.controller('HomologEvaluationMethodConfigurationController', ['$scope', '$rootScope', '$timeout', 'HomologEvaluationMethodService', 'EditBox', function ($scope, $rootScope, $timeout, HomologEvaluationMethodService, EditBox) {
            $scope.editbox = EditBox;

            var labels = MapasCulturais.gettext.homologEvaluationMethod;
            
            $scope.data = {
                sections: MapasCulturais.evaluationConfiguration.sections || [],
                criteria: MapasCulturais.evaluationConfiguration.criteria || [],

                debounce: 2000
            };

            function sectionExists(name) {
                var exists = false;
                $scope.data.sections.forEach(function (s) {
                    if (s.name == name) {
                        exists = true;
                    }
                });

                return exists;
            }

            $scope.save = function(){
                var data = {
                    sections: $scope.data.sections,
                    criteria: [],
                };

                $scope.data.criteria.forEach(function (crit) {
                    for (var i in data.sections) {
                        var section = data.sections[i];
                        if (crit.sid == section.id) {
                            data.criteria.push(crit);
                        }
                    }
                });

                $timeout.cancel($scope.saveTimeout); 

                $scope.saveTimeout = $timeout(function() {
                    HomologEvaluationMethodService.patchEvaluationMethodConfiguration(data).success(function () {
                        MapasCulturais.Messages.success(labels.changesSaved);
                        $scope.data.sections = data.sections;
                        $scope.data.criteria = data.criteria;
                    });
                }, $scope.data.debounce);
            };

            $scope.addSection = function(){
                var date = new Date;
                var new_id = 's-' + date.getTime();
                $scope.data.sections.push({id: new_id, name: ''});

                $timeout(function(){
                    jQuery('#' + new_id + ' header input').focus();
                },1);
            };

            $scope.deleteSection = function(section){
                if(!confirm(labels.deleteSectionConfirmation)){
                    return;
                }
                var index = $scope.data.sections.indexOf(section);

                $scope.data.criteria = $scope.data.criteria.filter(function(cri){
                    if(cri.sid != section.id){
                        return cri;
                    }
                });

                $scope.data.sections.splice(index,1);

                $scope.save();
            }

            $scope.addCriterion = function(section){
                var date = new Date;
                var new_id = 'c-' + date.getTime();
                $scope.data.criteria.push({id: new_id, sid: section.id, title: null});
                $scope.save();

                $timeout(function(){
                    jQuery('#' + new_id + ' .criterion-title input').focus();
                },1);
            }

            $scope.deleteCriterion = function(criterion){
                if(!confirm(labels.deleteCriterionConfirmation)){
                    return;
                }
                var index = $scope.data.criteria.indexOf(criterion);

                $scope.data.criteria.splice(index,1);

                $scope.save();
            }
        }]);

    module.controller('HomologEvaluationMethodFormController', ['$scope', '$rootScope', '$timeout', 'HomologEvaluationMethodService', function ($scope, $rootScope, $timeout, HomologEvaluationMethodService) {
            var labels = MapasCulturais.gettext.homologEvaluationMethod;
            console.log(MapasCulturais.evaluation);
            if(MapasCulturais.evaluation){
                for(var id in MapasCulturais.evaluation.evaluationData){
                    if(id != 'obs' && id != 'viability'){
                        MapasCulturais.evaluation.evaluationData[id] = MapasCulturais.evaluation.evaluationData[id];
                    }
                }
            }
            
            $scope.data = {
                sections: MapasCulturais.evaluationConfiguration.sections || [],
                criteria: MapasCulturais.evaluationConfiguration.criteria || [],
                empty: true
            };
  
            if(MapasCulturais.evaluation){
                $scope.evaluation =  MapasCulturais.evaluation.evaluationData;
                $scope.data.empty = false;
            } else {
                $scope.evaluation =  {};
            }

            $timeout(function(){
                if(MapasCulturais.evaluation){
                    for(var id in MapasCulturais.evaluation.evaluationData){
                        if(id != 'obs' && id != 'viability'){
                            MapasCulturais.evaluation.evaluationData[id] = MapasCulturais.evaluation.evaluationData[id];
                                $("#"+id).val(MapasCulturais.evaluation.evaluationData[id]);
                        }
                    }
                }
            },1);

            
            
        }]);
})(angular);