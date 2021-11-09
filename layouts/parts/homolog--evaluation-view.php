<?php
use MapasCulturais\i;
?>
<div ng-controller="HomologEvaluationMethodFormController" class="homolog-evaluation-form">
    <div ng-if="!data.empty">
        <?php i::_e('Avaliação'); ?>:<strong> {{ getStatusEvaluation() }}</strong><br><br>
        <section ng-repeat="section in ::data.sections" class="section {{ evaluation[section.id] }}">
             {{section.name}}: <strong>{{ getStatusSectionLabel(evaluation[section.id]) }}</strong>
             <li ng-repeat="cri in ::data.criteria" ng-if="cri.sid == section.id">
               <label for="{{cri.id}}">{{cri.title}}</label>
            </li>
        </section>
        <section class="section">
            <strong><?php i::_e('Observação'); ?>:</strong> {{ evaluation.obs }}
        </section>
        <hr>
    </div>
</div>