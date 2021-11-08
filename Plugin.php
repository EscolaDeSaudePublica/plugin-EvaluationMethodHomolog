<?php

namespace EvaluationMethodHomolog;

use MapasCulturais\i;
use MapasCulturais\App;
use MapasCulturais\Entities;

const STATUS_INVALID = 'invalid';
const STATUS_VALID = 'valid';

class Plugin extends \MapasCulturais\EvaluationMethod {
    function __construct(array $config = []) {
        $config += ['step' => '0.1'];
        parent::__construct($config);
    }

    private $viability_status;

    public function getSlug() {
        return 'homolog';
    }

    public function getName() {
        return i::__('Avaliação homologação');
    }

    public function getDescription() {
        return i::__('Consiste em avaliação por homologação e validação de critérios.');
    }

    public function cmpValues($value1, $value2){
        $value1 = (float) $value1;
        $value2 = (float) $value2;
        
        return parent::cmpValues($value1, $value2);
    }

    public function getStep(){
        return $this->_config['step'];
    }
    
    protected function _register() {
        $this->registerEvaluationMethodConfigurationMetadata('sections', [
            'label' => i::__('Seções'),
            'type' => 'json',
            'serialize' => function ($val){
                return json_encode($val);
            },
            'unserialize' => function($val){
                return json_decode($val);
            }
        ]);

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
    }

    function enqueueScriptsAndStyles() {
        $app = App::i();
        $app->view->enqueueStyle('app', 'homolog-evaluation-method', 'css/homolog-evaluation-method.css');
        $app->view->enqueueScript('app', 'homolog-evaluation-form', 'js/ng.evaluationMethod.homolog.js', ['entity.module.opportunity']);

        $app->view->localizeScript('homologEvaluationMethod', [
            'sectionNameAlreadyExists' => i::__('Já existe uma seção com o mesmo nome'),
            'changesSaved' => i::__('Alteraçṍes salvas'),
            'deleteSectionConfirmation' => i::__('Deseja remover a seção? Esta ação não poderá ser desfeita e também removerá todas os critérios desta seção.'),
            'deleteCriterionConfirmation' => i::__('Deseja remover este item? Esta ação não poderá ser desfeita.')
        ]);
        $app->view->jsObject['angularAppDependencies'][] = 'ng.evaluationMethod.homolog';
    }

    public function _init() {
        $app = App::i();

        $app->hook('evaluationsReport(homolog).sections', function(Entities\Opportunity $opportunity, &$sections){
           
            $i = 0;
            $get_next_color = function($last = false) use(&$i){
                $colors = [
                    '#FFAAAA',
                    '#BB8888',
                    '#FFAA66',
                    '#AAFF00',
                    '#AAFFAA'
                ];

                $result = $colors[$i];

                $i++;

                return $result;
            };

            $cfg = $opportunity->evaluationMethodConfiguration;

            $result = [
                'registration' => $sections['registration'],
                'committee' => $sections['committee'],
            ];
            foreach($cfg->sections as $sec){
                $section = (object) [
                    'label' => $sec->name,
                    'color' => $get_next_color(),
                    'columns' => []
                ];

                foreach($cfg->criteria as $crit){
                    if($crit->sid != $sec->id) {
                        continue;
                    }
                }


                $result[] = $section;
            }

            $result['evaluation'] = $sections['evaluation'];

            // adiciona coluna do parecer técnico
            $result['evaluation']->columns[] = (object) [
                'label' => i::__('Parecer Técnico'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) use($crit) {
                    return isset($evaluation->evaluationData->obs) ? $evaluation->evaluationData->obs : '';
                }
            ];

            $viability = [
                'label' => i::__('Esta proposta apresenta exequibilidade?'),
                'getValue' => function(Entities\RegistrationEvaluation $evaluation) {
                    return $this->viabilityLabel($evaluation);
                }
            ];

            $result['evaluation']->columns[] = (object) $viability;

            $sections = 'kkkkkk';

            $this->viability_status = [
                'valid' => i::__('Válido'),
                'invalid' => i::__('Inválido')
            ];
        });
    }

    function getValidationErrors(Entities\EvaluationMethodConfiguration $evaluation_method_configuration, array $data){
        $errors = [];
        $empty = false;

        if($empty){
            $errors[] = i::__('Todos os campos devem ser preenchidos');
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
            return i::__('Válida');
        } else if($value == -1){
            return i::__('Inválida');
        }

        return $value ?: '';

    }

    public function fetchRegistrations() {
        return true;
    }

    private function viabilityLabel($evaluation) {
        if (isset($evaluation->evaluationData->viability)) {
            $viability = $evaluation->evaluationData->viability;

            return $this->viability_status[$viability];
        }

        return '';
    }

}
