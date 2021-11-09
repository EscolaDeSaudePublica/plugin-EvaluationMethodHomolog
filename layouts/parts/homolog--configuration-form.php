<?php
use MapasCulturais\i;
?>
<div ng-controller="HomologEvaluationMethodConfigurationController" class="homolog-evaluation-configuration registration-fieldset">
    <h4><?php i::_e('Critérios') ?></h4>
    <p><?php i::_e('Configure abaixo os critérios de avaliação homologação') ?>
    <section id="{{criterion.id}}" ng-repeat="criterion in data.criteria">
        <header>
            <input ng-model="criterion.name" placeholder="<?php i::_e('informe o nome do critério') ?>" class="criterion-name edit" ng-change="save({criteria: data.criteria})">
            <button ng-if="criterion.name.trim().length > 0" ng-click="deleteCriterion(criterion)" class="btn btn-danger delete alignright"><?php i::_e('Remover critério') ?></button>
            <button ng-if="criterion.name.trim().length == 0" ng-click="deleteCriterion(criterion)" class="btn btn-default delete alignright"><?php i::_e('Cancelar') ?></button>
        </header>
        <table>
            <tr>
                <th class="item-title"><?php i::_e('Itens do Regulamento') ?></th>
                <th>
                    <button ng-click="addItem(criterion)" class="btn btn-default add" title="<?php i::_e('Adicionar item') ?>"></button>
                </th>
            </tr>
            <tr id="{{item.id}}" ng-repeat="item in data.items" ng-if="item.cid == criterion.id">
                <td class="item-title"><input ng-model="item.title" placeholder="<?php i::_e('informe o item do regulamento') ?>" ng-change="save({item: data.item})" ng-model-options='{ debounce: data.debounce }'></td>
                <td>
                    <button ng-click="deleteItem(item)" class="btn btn-danger delete" title="<?php i::_e('Remover item') ?>"></button>
                </td>
            </tr>
        </table>
    </section>
    <button ng-click="addCriterion()" class="btn btn-default add"><?php i::_e('Adicionar critério de avaliação homologação') ?></button>
</div>

