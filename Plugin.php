<?php

namespace EvaluationMethodHomolog;

use MapasCulturais\i;
use MapasCulturais\App;
use MapasCulturais\Entities;
use MapasCulturais\Entities\Registration;

const STATUS_NOT_APPLICABLE = 'notapplicable';
const STATUS_INVALID = 'invalid';
const STATUS_VALID = 'valid';

class Plugin extends \MapasCulturais\EvaluationMethod {
    public function getSlug() {
        return 'homolog';
    }

    public function getName() {
        return i::__('Avaliação de homologação');
    }

    public function getDescription() {
        return i::__('Consiste na avaliação por critérios. Indicando se é válido, inválido ou não se aplica.');
    }

    public function cmpValues($value1, $value2){
        $value1 = (float) $value1;
        $value2 = (float) $value2;
        
        return parent::cmpValues($value1, $value2);
    }
    
    protected function _register() {
        $this->registerEvaluationMethodConfigurationMetadata('criteria', [
            'label' => i::__('Critérios'),
            'type' => 'json',
            'serialize' => function ($val){
                return json_encode($val);
            },
            'unserialize' => function($val){
                return json_decode($val);
            }
        ]);

        $this->registerEvaluationMethodConfigurationMetadata('items', [
            'label' => i::__('Itens'),
            'type' => 'json',
            'serialize' => function ($val){
                return json_encode($val);
            },
            'unserialize' => function($val){
                return json_decode($val);
            }
        ]);
    }

    function enqueueScriptsAndStyles() {
        $app = App::i();
        $app->view->enqueueStyle('app', 'homolog-evaluation-method', 'css/homolog-evaluation-method.css');
        $app->view->enqueueScript('app', 'homolog-evaluation-form', 'js/ng.evaluationMethod.homolog.js', ['entity.module.opportunity']);

        $app->view->localizeScript('homologEvaluationMethod', [
            'criterionNameAlreadyExists' => i::__('Já existe um critério com o mesmo nome'),
            'changesSaved' => i::__('Alteraçṍes salvas'),
            'deleteCriterionConfirmation' => i::__('Deseja remover o critério? Esta ação não poderá ser desfeita e também removerá todos os itens deste critério.'),
            'deleteItemConfirmation' => i::__('Deseja remover este item? Esta ação não poderá ser desfeita.')
        ]);
        $app->view->jsObject['angularAppDependencies'][] = 'ng.evaluationMethod.homolog';
    }

    public function _init() {
        $app = App::i();

        $app->hook('evaluationsReport(homolog).sections', function(Entities\Opportunity $opportunity, &$sections) use($app) {
            
            $cfg = $opportunity->evaluationMethodConfiguration;

            

            $result = [
                'registration' => $sections['registration'],
                'committee' => $sections['committee'],
            ];
            
            $section = (object) [
                'label' => 'Criterios',
                'color' => '#FFAAAA',
                'columns' => []
            ];
            
            $statuses = [
                "invalid" => "Inválido",
                "notapplicable" => "Não se aplica",
                "valid" => "Valido"
            ];
            foreach($cfg->criteria as $cri){
                
                $section->columns[] = (object) [
                    'label' => $cri->name,
                    'getValue' => function(Entities\RegistrationEvaluation $evaluation) use($cri , $statuses) {
                        return $statuses[$evaluation->evaluationData->{$cri->id}] ?? null;
                    }
                ];
            }

            $result[] = $section;

            $result['evaluation'] = $sections['evaluation'];

            $result['evaluation']->columns[0] = (object) [
                'label' => i::__('Itens de descumprimento'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) use ($cfg){
                    $invalids = [];
                    foreach($cfg->criteria as $cri){
                        if($evaluation->evaluationData->{$cri->id} == 'invalid'){
                            foreach($cfg->items as $item){
                                if($item->cid == $cri->id){
                                    $invalids[] = $item->title;
                                }
                            }
                        }
                    }
                    return implode(', ', $invalids);
                }
            ];
            
            // adiciona coluna do parecer técnico
            $result['evaluation']->columns[1] = (object) [
                'label' => i::__('Parecer Técnico'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) {
                    return isset($evaluation->evaluationData->obs) ? $evaluation->evaluationData->obs : '';
                }
            ];
            
            $result['registration']->columns[0] = $result['registration']->columns['number'];
            unset($result['registration']->columns['number']);

            $result['registration']->columns[1] = (object) [
                'label' => i::__('Nome do projeto'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) use ($cfg) {
                    return $cfg->opportunity->ownerEntity->name;
                }
            ];

            $result['registration']->columns[2] = $result['registration']->columns['category'];
            unset($result['registration']->columns['category']);

            $result['registration']->columns[3] = $result['registration']->columns['owner'];
            unset($result['registration']->columns['owner']);
            
            $sections = $result;
        });

        $app->hook('POST(opportunity.applyEvaluationsHomolog)', function() {
            $this->requireAuthentication();

            set_time_limit(0);
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', '-1');
    
            $app = App::i();
    
            $opp = $this->requestedEntity;
    
            $type = $opp->evaluationMethodConfiguration->getDefinition()->slug;
    
            if($type != 'homolog') {
                $this->errorJson(i::__('Somente para avaliações de homologação'), 400);
                die;
            }

            if (!is_numeric($this->data['to']) || !in_array($this->data['to'], [0,2,3,8,10])) {
                $this->errorJson(i::__('os status válidos são 0, 2, 3, 8 e 10'), 400);
                die;
            }
            $new_status = intval($this->data['to']);
            
            $apply_status = $this->data['status'] ?? false;
            if ($apply_status == 'all') {
                $status = 'r.status > 0';
            } else {
                $status = 'r.status = 1';
            }
    
            $opp->checkPermission('@control');

            // pesquise todas as registrations da opportunity que esta vindo na request
            $query = App::i()->getEm()->createQuery("
            SELECT 
                r
            FROM
                MapasCulturais\Entities\Registration r
            WHERE 
                r.opportunity = :opportunity_id AND
                r.consolidatedResult = :consolidated_result AND
                $status
            ");
        
            $params = [
                'opportunity_id' => $opp->id,
                'consolidated_result' => $this->data['from']
            ];
    
            $query->setParameters($params);
    
            $registrations = $query->getResult();
            
            // faça um foreach em cada registration e pegue as suas avaliações
            foreach ($registrations as $registration) {
                $app->log->debug("Alterando status da inscrição {$registration->number} para {$new_status}");
                switch ($new_status) {
                    case Registration::STATUS_DRAFT:
                        $registration->setStatusToDraft();
                    break;
                    case Registration::STATUS_INVALID:
                        $registration->setStatusToInvalid();
                    break;
                    case Registration::STATUS_NOTAPPROVED:
                        $registration->setStatusToNotApproved();
                    break;
                    case Registration::STATUS_WAITLIST:
                        $registration->setStatusToWaitlist();
                    break;
                    case Registration::STATUS_APPROVED:
                        $registration->setStatusToApproved();
                    break;
                    default:
                        $registration->_setStatusTo($new_status);
                    
                }
                $app->disableAccessControl();
                $registration->save(true);
                $app->enableAccessControl();
            }

    
            $this->finish(sprintf(i::__("Avaliações aplicadas à %s inscrições"), count($registrations)), 200);
    
        });

        $app->hook('template(opportunity.single.header-inscritos):actions', function() use($app) {
            $opportunity = $this->controller->requestedEntity;
            
            if ($opportunity->evaluationMethodConfiguration->getDefinition()->slug != 'homolog') {
                return;
            }

            $consolidated_results = $app->em->getConnection()->fetchAll("
                SELECT 
                    consolidated_result evaluation,
                    COUNT(*) as num
                FROM 
                    registration
                WHERE 
                    opportunity_id = :opportunity AND
                    status > 0 
                GROUP BY consolidated_result
                ORDER BY num DESC", ['opportunity' => $opportunity->id]);
            
            $this->part('homolog--apply-results', ['entity' => $opportunity, 'consolidated_results' => $consolidated_results]);
        });
    }

    function getValidationErrors(Entities\EvaluationMethodConfiguration $evaluation_method_configuration, array $data){
        $errors = [];
        $empty = false;
        foreach($data as $prop => $val){
            if(is_null($val) || $val == ''){
                $empty = true;
            }
        }

        if($empty){
            $errors[] = i::__('Campos obrigatórios. Verifique novamente.');
        }

        return $errors;
    }

    public function _getConsolidatedResult(\MapasCulturais\Entities\Registration $registration) {
        $app = App::i();

        $evaluations = $app->repo('RegistrationEvaluation')->findBy(['registration' => $registration]);

        if(is_array($evaluations) && count($evaluations) === 0){
            return 0;
        }

        $result = 1;

        foreach ($evaluations as $eval){
            if($eval->status === \MapasCulturais\Entities\RegistrationEvaluation::STATUS_DRAFT){
                return 0;
            }

            $result = ($result === 1 && $this->getEvaluationResult($eval) === 1) ? 1 : -1;
        }

        return $result;
    }

    public function getEvaluationResult(Entities\RegistrationEvaluation $evaluation) {
        $data = (array) $evaluation->evaluationData;
        
        if(is_array($data) && count($data) == 0){
            return 1; // valid
        }

        foreach ($data as $id => $value) {
            
            if(isset($value) && $value === STATUS_INVALID){
                return -1;
            }
        }

        return 1;
    }

    public function valueToString($value) {
        if($value == 1){
            return i::__('Válido');
        } else if($value == -1){
            return i::__('Inválido');
        }

        return $value ?: '';
    }

    public function fetchRegistrations() {
        return true;
    }
}
