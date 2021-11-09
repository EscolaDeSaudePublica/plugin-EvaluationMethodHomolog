<?php
use MapasCulturais\i;
?>
<div ng-controller="HomologEvaluationMethodFormController" class="homolog-evaluation-form">
    <div ng-if="!data.empty">
        <?php i::_e('Avaliação'); ?>:<strong> {{ getStatusEvaluation() }}</strong><br><br>
        <section ng-repeat="criterion in ::data.criteria" class="criterion {{ evaluation[criterion.id] }}">
             {{criterion.name}}: <strong>{{ getStatusCriterionLabel(evaluation[criterion.id]) }}</strong>
             <li ng-repeat="item in ::data.items" ng-if="item.cid == criterion.id">
               <label for="{{item.id}}">{{item.title}}</label>
            </li>
        </section>
        <section class="criterion">
            <strong><?php i::_e('Observação'); ?>:</strong> {{ evaluation.obs }}
        </section>
        <hr>
    </div>
</div>