<?php

namespace EvaluationMethodHomolog;

use MapasCulturais\i;

$params = ['registration' => $entity, 'opportunity' => $opportunity];
//VERIFICA SE O AVALIADOR ENVIOU AS NOTAS
$enabled = $app->repo('AgentRelation')->findBy([
    'objectId' => $opportunity->evaluationMethodConfiguration->id,
    'agent' => $app->user->profile->id
]);
$disabled = '';
if (count($enabled)) {
    if($enabled[0]->status == 10){
        $disabled = 'disabled';
    }
}
 
if($disabled == 'disabled') :
    echo '<div class="alert danger">
    <span>A avaliação já foi enviada. Não é possível alterar.</span>
</div>';
endif;

$this->applyTemplateHook('evaluationForm.homolog', 'before', $params); ?>
<div ng-controller="HomologEvaluationMethodFormController" class="homolog-evaluation-form">
    <?php $this->applyTemplateHook('evaluationForm.homolog', 'begin', $params); ?>
    <div class="alert-evaluation-load" id="alert-evaluation-load-div">
        <span id="successEvaluationNote" class="load-evaluation-note">A avaliação foi salva</span>
    </div>
    <section ng-repeat="criterion in ::data.criteria">
        <table>
            <tr>
                <th colspan="2">
                    {{criterion.name}}</br>
                </th>
            </tr>
            <tr ng-repeat="item in ::data.items" ng-if="item.cid == criterion.id">
                <td><label for="{{item.id}}">{{item.title}}</label></td>
                <td></td>
            </tr>
            <tr class="subtotal">
                <td><?php i::_e('Situação')?></td>
                <td>
                    <select name="data[{{criterion.id}}]" id="{{criterion.id}}" class="form-control">
                        <option value="">Seleciona uma opção</option>
                        <option value="<?php echo STATUS_VALID ?>"><?php i::_e('Válido')?></option>
                        <option value="<?php echo STATUS_INVALID ?>"><?php i::_e('Inválido')?></option>
                        <option value="<?php echo STATUS_NOT_APPLICABLE ?>"><?php i::_e('Não de aplica')?></option>
                    </select>
                </td>
            </tr>
        </table>
    </section>
    <hr>
    <label>
        <?php i::_e('Justificativa/Observação') ?>
        <textarea name="data[obs]" ng-model="evaluation['obs']"></textarea>
    </label>
    <?php $this->applyTemplateHook('evaluationForm.homolog', 'end', $params); ?>
</div>
<?php $this->applyTemplateHook('evaluationForm.homolog', 'after', $params); ?>
