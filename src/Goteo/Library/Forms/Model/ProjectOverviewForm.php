<?php

/*
 * This file is part of the Goteo Package.
 *
 * (c) Platoniq y Fundación Goteo <fundacion@goteo.org>
 *
 * For the full copyright and license information, please view the README.md
 * and LICENSE files that was distributed with this source code.
 */

namespace Goteo\Library\Forms\Model;

use Goteo\Library\Forms\FormProcessorInterface;
use Goteo\Library\Forms\AbstractFormProcessor;
use Symfony\Component\Validator\Constraints;
use Goteo\Application\Lang;
use Goteo\Model\Project;
use Goteo\Model\SocialCommitment;
use Goteo\Library\Text;
use Goteo\Library\Currency;
use Goteo\Library\Forms\FormModelException;

class ProjectOverviewForm extends AbstractFormProcessor implements FormProcessorInterface {
    private $validations = [];

    public function getConstraints($field) {
        $constraints = [];
        if($field === 'name') {
            $constraints[] = new Constraints\NotBlank();
        }
        elseif($this->getFullValidation()) {
            // all fields
            $constraints[] = new Constraints\NotBlank();
        }
        return $constraints;
    }

    public function createForm() {
        $currencies = Currency::listAll('name', false);
        $langs = Lang::listAll('name', false);

        $this->getBuilder()
            ->add('name', 'text', [
                'label' => 'overview-field-name',
                'constraints' => $this->getConstraints('name'),
                'disabled' => $this->getReadonly(),
                'attr' => ['help' => Text::get('tooltip-project-name')]
            ])
            ->add('subtitle', 'text', [
                'label' => 'overview-field-subtitle',
                'constraints' => $this->getConstraints('subtitle'),
                'disabled' => $this->getReadonly(),
                'required' => false,
                'attr' => ['help' => Text::get('tooltip-project-subtitle')]
            ])
            ->add('lang', 'choice', [
                'label' => 'overview-field-lang',
                'constraints' => $this->getConstraints('lang'),
                'disabled' => $this->getReadonly(),
                'choices' => $langs,
                'attr' => ['help' => Text::get('tooltip-project-lang')]
            ])
            ->add('currency', 'choice', [
                'label' => 'overview-field-currency',
                'constraints' => $this->getConstraints('currency'),
                'disabled' => $this->getReadonly(),
                'choices' => $currencies,
                'attr' => ['help' => Text::get('tooltip-project-currency')]
            ])
            ->add('media', 'media', array(
                'label' => 'overview-field-media',
                'constraints' => $this->getConstraints('media'),
                'disabled' => $this->getReadonly(),
                'required' => false,
                'attr' => ['help' => Text::get('tooltip-project-media')]
            ))
            ->add('description', 'markdown', [
                'label' => 'overview-field-description',
                'constraints' => $this->getConstraints('description'),
                'disabled' => $this->getReadonly(),
                'required' => false,
                'attr' => ['help' => Text::get('tooltip-project-description'), 'rows' => 8]
            ])
            ->add('project_location', 'location', [
                'label' => 'overview-field-project_location',
                'constraints' => $this->getConstraints('project_location'),
                'type' => 'project',
                'disabled' => $this->getReadonly(),
                'item' => $this->getModel()->id,
                'required' => false,
                'pre_addon' => '<i class="fa fa-globe"></i>',
                'attr' => ['help' => Text::get('tooltip-project-project_location')]
            ])
            ->add('related', 'markdown', [
                'label' => 'overview-field-related',
                'constraints' => $this->getConstraints('related'),
                'disabled' => $this->getReadonly(),
                'required' => false,
                'attr' => ['help' => Text::get('tooltip-project-related'), 'rows' => 8]
            ])
            ->add('spread', 'textarea', [
                'label' => 'overview-field-spread',
                'disabled' => $this->getReadonly(),
                'constraints' => $this->getConstraints('spread'),
                'required' => false,
                'attr' => ['help' => Text::get('tooltip-project-spread'), 'rows' => 8]
            ])
            ->add('extra-title', 'title', [
                'label' => 'overview-extra-fields',
                'disabled' => $this->getReadonly(),
                'row_class' => 'extra'
            ])
            ->add('about', 'markdown', [
                'label' => 'overview-field-about',
                'constraints' => $this->getConstraints('about'),
                'disabled' => $this->getReadonly(),
                'required' => false,
                'row_class' => 'extra',
                'attr' => ['help' => Text::get('tooltip-project-about'), 'rows' => 8]
            ])
            ->add('motivation', 'markdown', [
                'label' => 'overview-field-motivation',
                'constraints' => $this->getConstraints('motivation'),
                'disabled' => $this->getReadonly(),
                'required' => false,
                'row_class' => 'extra',
                'attr' => ['help' => Text::get('tooltip-project-motivation'), 'rows' => 8]
            ])
            ->add('goal', 'markdown', [
                'label' => 'overview-field-goal',
                'disabled' => $this->getReadonly(),
                'constraints' => $this->getConstraints('goal'),
                'required' => false,
                'row_class' => 'extra',
                'attr' => ['help' => Text::get('tooltip-project-goal'), 'rows' => 8]
            ])
            ->add('scope', 'choice', [
                'label' => 'overview-field-scope',
                'disabled' => $this->getReadonly(),
                'constraints' => $this->getConstraints('choice'),
                'required' => true,
                'wrap_class' => 'col-sm-3 col-xs-4',
                'choices' => Project::scope(),
                'expanded' => true,
                'attr' => ['help' => Text::get('tooltip-project-scope')]
            ])
            ->add('social_commitment', 'choice', [
                'label' => 'overview-field-social-category',
                'disabled' => $this->getReadonly(),
                'constraints' => $this->getConstraints('social_commitment'),
                'required' => true,
                // 'wrap_class' => 'col-sm-3 col-xs-4',
                'choices' => array_map(function($el){
                        return [$el->id => $el->name];
                    }, SocialCommitment::getAll()),
                'expanded' => true,
                'attr' => ['help' => Text::get('tooltip-project-social-category')]
            ])
            ->add('social_commitment_description', 'textarea', [
                'disabled' => $this->getReadonly(),
                'label' => 'overview-field-social-description',
                'constraints' => $this->getConstraints('social_commitment_description'),
                'required' => false,
                'attr' => ['help' => Text::get('tooltip-project-social-description'), 'rows' => 8]
            ])
            ;
        return $this;
    }

}