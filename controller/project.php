<?php

namespace Goteo\Controller {

    use Goteo\Core\ACL,
        Goteo\Core\Error,
        Goteo\Core\Redirection,
        Goteo\Core\View,
        Goteo\Library\Text,
        Goteo\Model;

    class Project extends \Goteo\Core\Controller {

        public function index($id = null) {
            if ($id !== null) {

                if (isset($_GET['edit']))
                    return $this->edit($id); //Editar
                elseif (isset($_GET['finish']))
                    return $this->finish($id); // Para revision
                elseif (isset($_GET['enable']))
                    return $this->enable($id); // Re-habilitar la edición
                elseif (isset($_GET['publish']))
                    return $this->publish($id); // Publicarlo
                elseif (isset($_GET['disable']))
                    return $this->disable($id); // Cancelar
                elseif (isset($_GET['raw'])) {
                    $project = Model\Project::get($id);
                    die('<pre>' . print_r($project, 1) . '</pre>');
                }
                else
                    return $this->view($id);

            } else if (isset($_GET['create'])) {
                return $this->create();
            } else {
                throw new Error(Error::NOT_FOUND);
            }
        }

        //Aunque no esté en estado edición un admin siempre podrá editar un proyecto
        private function edit ($id) {
            //@TODO Verificar si tiene permisos para editar (usuario)
            $nodesign = true; // para usar el formulario de proyecto en Julian mode

            $project = Model\Project::get($id);
//            die ('<pre>' . print_r($project, 1) . '</pre>');
            //@TODO Verificar si tieme permiso para editar libremente
            if ($project->status != 1 && $_SESSION['user']->role != 1) // @FIXME!!! este piñonaco porque aun no tenemos el jodido ACL listo :(
                throw new Redirection("/project/{$project->id}");

            if (!isset($_SESSION['stepped']))
                $_SESSION['stepped'] = array();

            $steps = array(
                'userProfile' => array(
                    'name' => Text::get('step-1'),
                    'title' => Text::get('step-userProfile'),
                    'guide' => Text::get('guide-project-user-information'),
                    'offtopic' => true
                ),
                'userPersonal' => array(
                    'name' => Text::get('step-2'),
                    'title' => Text::get('step-userPersonal'),
                    'guide' => Text::get('guide-project-contract-information'),
                    'offtopic' => true
                ),
                'overview' => array(
                    'name' => Text::get('step-3'),
                    'title' => Text::get('step-overview'),
                    'guide' => Text::get('guide-project-description')
                ),
                'costs'=> array(
                    'name' => Text::get('step-4'),
                    'title' => Text::get('step-costs'),
                    'guide' => Text::get('guide-project-costs')
                ),
                'rewards' => array(
                    'name' => Text::get('step-5'),
                    'title' => Text::get('step-rewards'),
                    'guide' => Text::get('guide-project-rewards')
                ),
                'supports' => array(
                    'name' => Text::get('step-6'),
                    'title' => Text::get('step-supports'),
                    'guide' => Text::get('guide-project-support')
                ),
                'preview' => array(
                    'name' => Text::get('step-7'),
                    'title' => Text::get('step-preview'),
                    'guide' => Text::get('guide-project-overview'),
                    'offtopic' => true
                )
            );

            // variables para la vista
            $viewData = array(
                            'project'=>$project,
                            'steps'=>$steps,
                            'nodesign'=>$nodesign
                        );            
            
            $step = null;                        

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                $errors = array(); // errores al procesar, no son errores en los datos del proyecto
                foreach ($steps as $id => &$data) {
                    
                    if (call_user_func_array(array($this, "process_{$id}"), array(&$project, &$errors))) {
                        // si un process devuelve true es que han enviado datos de este paso, lo añadimos a los pasados
                        if (!in_array($id, $_SESSION['stepped']))
                            $_SESSION['stepped'][] = $id;
                    }
                    
                    
                    //print_r($_POST);Die;
                    // y el paso que vamos a mostrar
                    if (!empty($_POST['view-step-' . $id])) {
                        $step = $id;                        
                    }
                }

                // guardamos los datos que hemos tratado y los errores de los datos
                $project->save($errors);

                // si ha ocurrido algun error de proces (como p.ej. "no se ha podido guardar loqueseaa")
                if (!empty($errors))
                    throw new \Goteo\Core\Exception(implode('. ', $errors));

                //re-evaluar el proyecto
                $project->evaluate();

                //si nos estan pidiendo el error de un campo, se lo damos
                if (!empty($_GET['errors'])) {
                    foreach ($project->errors as $paso) {
                        if (!empty($paso[$_GET['errors']])) {
                            return new View(
                                'view/project/errors.json.php',
                                array('errors'=>array($paso[$_GET['errors']]))
                            );
                        }
                    }
                }
            }
            
            if (empty($step)) {
            
                // vista por defecto, el primer paso con errores
                if (!empty($project->errors['userProfile']))
                    $step = 'userProfile';
                elseif (!empty($project->errors['userPersonal']))
                    $step = 'userPersonal';
                elseif (!empty($project->errors['overview']))
                    $step = 'overview';
                elseif (!empty($project->errors['costs']))
                    $step = 'costs';
                elseif (!empty($project->errors['rewards']))
                    $step = 'rewards';
                elseif (!empty($project->errors['supports']))
                    $step = 'supports';
                else
                    $step = 'preview';
                
            }
            
            $viewData['step'] = $step;

            // segun el paso añadimos los datos auxiliares para pintar
            switch ($step) {
                case 'userProfile':
                    $viewData['user'] = Model\User::get($project->owner);
                    $viewData['interests'] = Model\User\Interest::getAll();
                    break;
                
                case 'overview':
                    $viewData['currently'] = Model\Project::currentStatus();
                    $viewData['categories'] = Model\Project\Category::getAll();
                    break;

                case 'costs':
                    $viewData['types'] = Model\Project\Cost::types();
                    break;

                case 'rewards':
                    $viewData['stypes'] = Model\Project\Reward::icons('social');
                    $viewData['itypes'] = Model\Project\Reward::icons('individual');
                    $viewData['licenses'] = Model\Project\Reward::licenses();
                    break;

                case 'supports':
                    $viewData['types'] = Model\Project\Support::types();
                    break;
                
                case 'preview':
                    $success = array();
                    if (empty($project->errors)) {
                        $success[] = Text::get('guide-project-success-noerrors');
                    }
                    if ($project->progress > 80 && $project->status == 1) {
                        $success[] = Text::get('guide-project-success-minprogress');
                        $success[] = Text::get('guide-project-success-okfinish');
                        $viewData['finishable'] = true;
                    }
                    $viewData['success'] = $success;
                    break;
            }


            $view = new View (
                "view/project/edit.html.php",
                $viewData
            );

            return $view;

        }

        private function create () {
            //@TODO Verificar si tienen permisos para crear nuevos proyectos
            $project = new Model\Project;
            $project->create($_SESSION['user']->id);
            $_SESSION['stepped'] = array();
                throw new Redirection("/project/{$project->id}/?edit");

            throw new \Goteo\Core\Exception('Fallo al crear un nuevo proyecto');
        }

        private function view ($id) {
            $project = Model\Project::get($id);
            return new View(
                'view/project/public.html.php',
                array(
                    'project' => $project
                )
            );
        }

        /*
         * Finalizar para revision, ready le cambia el estado
         */
        public function finish($id) {
            //@TODO verificar si tienen el mínimo progreso para verificación y si está en estado edición
            $project = Model\Project::get($id);

            if ($project->status != 1)
                throw new Redirection("/project/{$project->id}");

            $errors = array();
            if ($project->ready($errors))
                throw new Redirection("/project/{$project->id}");
            
            throw new \Goteo\Core\Exception(implode(' ', $errors));
        }

        /*
         * Rehabilitarlo para edición
         */
        public function enable($id) {
            //@TODO verificar si tiene permisos para rehabilitar la edición del proyecto (admin)
            if ($_SESSION['user']->role != 1) //@FIXME!! Piñonaco... ACL...
                throw new Redirection("/project/{$id}");

            $project = Model\Project::get($id);

            $errors = array();
            if ($project->enable($errors))
                throw new Redirection("/project/{$project->id}/?edit");

            throw new \Goteo\Core\Exception(implode(' ', $errors));
        }

        /*
         * Para cancelarlo
         */
        public function disable($id) {
            //@TODO verificar si tiene permisos para cancelar el proyecto (admin)
            if ($_SESSION['user']->role != 1) //@FIXME!! Piñonaco... ACL...
                throw new Redirection("/project/{$id}");

            $project = Model\Project::get($id);

            $errors = array();
            if ($project->fail($errors))
                throw new Redirection("/admin/checking");

            throw new \Goteo\Core\Exception(implode(' ', $errors));
        }

        public function publish($id) {
            //@TODO verificar si tiene permisos para publicar proyectos
            if ($_SESSION['user']->role != 1) //@FIXME!! Piñonaco... ACL...
                throw new Redirection("/project/{$id}");

            $project = Model\Project::get($id);

            $errors = array();
            if ($project->publish($errors))
                throw new Redirection("/project/{$project->id}");

            throw new \Goteo\Core\Exception(implode(' ', $errors));
        }

        /*
         *  Explorar proyectos, por el momento mostrará todos los proyectos publicados
         */
         public function explore() {
            $projects = Model\Project::published();

            return new View (
                'view/explore.html.php',
                array(
                    'message' => 'Estos son los proyectos actualmente activos',
                    'projects' => $projects
                )
            );
         }

        //-----------------------------------------------
        // Métodos privados para el tratamiento de datos
        // del save y remove de las tablas relacionadas se enmcarga el model/project
        // primero añadir y luego quitar para que no se pisen los indices
        // En vez del hidden step, va a comprobar que esté definido en el post el primer campo del proceso
        //-----------------------------------------------
        /*
         * Paso 1 - PERFIL
         */
        private function process_userProfile(&$project, &$errors) {
            if (!isset($_POST['user_name']))
                return false;

            $user = Model\User::get($project->owner);

            // tratar la imagen y ponerla en la propiedad avatar
            // __FILES__

            $fields = array(
                'user_name'=>'name',
                'user_avatar'=>'avatar',
                'user_about'=>'about',
                'user_keywords'=>'keywords',
                'user_contribution'=>'contribution',
                'user_twitter'=>'twitter',
                'user_facebook'=>'facebook',
                'user_linkedin'=>'linkedin'
            );
                        
            foreach ($fields as $fieldPost=>$fieldTable) {
                $user->$fieldTable = $_POST[$fieldPost];
            }
            
            // Avatar
            if(!empty($_FILES['avatar_upload']['name'])) {
                $user->avatar = $_FILES['avatar_upload'];
            }

            $user->interests = $_POST['interests'];

            //tratar webs existentes
            foreach ($user->webs as $key=>&$web) {
                // luego aplicar los cambios
                
                if (isset($_POST['web-'. $web->id . '-url'])) {
                    $web->url = $_POST['web-'. $web->id . '-url'];
                }
                
            }

            //tratar nueva web
            if (!empty($_POST['web-add'])) {
                
                $web = new Model\User\Web();

                $web->id = '';
                $web->user = $user->id;
                $web->url = '';
                $user->webs[] = $web;
            }

            //quitar las que quiten
            foreach ($user->webs as $key=>$web) {
                // primero mirar si lo estan quitando
                // if ($_POST['remove-web' . $web->id] == 1)
                
                
                if (!empty($_POST['web-' . $web->id . '-remove'])) {
                    unset($user->webs[$key]);
                }
                    
            }

            /// este es el único save que se lanza desde un metodo process_
            $user->save($project->errors['userProfile']);
            return true;
        }

        /*
         * Paso 2 - DATOS PERSONALES
         */
        private function process_userPersonal(&$project, &$errors) {
            if (!isset($_POST['contract_name']))
                return false;

            // campos que guarda este paso
            $fields = array(
                'contract_name',
                'contract_surname',
                'contract_nif',
                'contract_email',
                'phone',
                'address',
                'zipcode',
                'location',
                'country'
            );

            foreach ($fields as $field) {
                $project->$field = $_POST[$field];
            }

            return true;
        }

        /*
         * Paso 3 - DESCRIPCIÓN
         */

        private function process_overview(&$project, &$errors) {
            if (!isset($_POST['name']))
                return false;

            // campos que guarda este paso
            $fields = array(
                'name',
                'image',
                'description',
                'motivation',
                'about',
                'goal',
                'related',
                'keywords',
                'media',
                'currently',
                'project_location'
            );

            foreach ($fields as $field) {
                $project->$field = $_POST[$field];
            }

            //categorias
            // añadir las que vienen y no tiene
            $tiene = $project->categories;
            if (!empty($_POST['categories'])) {
                $viene = $_POST['categories'];
                $quita = array_diff($tiene, $viene);
            } else {
                $quita = $tiene;
            }
            $guarda = array_diff($viene, $tiene);
            foreach ($guarda as $key=>$cat) {
                $category = new Model\Project\Category(array('id'=>$cat,'project'=>$project->id));
                $project->categories[] = $category;
            }

            // quitar las que tiene y no vienen
            foreach ($quita as $key=>$cat) {
                unset($project->categories[$key]);
            }

            $quedan = $project->categories; // truki para xdebug

            return true;
        }

        /*
         * Paso 4 - COSTES
         */
        private function process_costs(&$project, &$errors) {
            if (!isset($_POST['resource']))
                return false;

            $project->resource = $_POST['resource'];
            
            //tratar costes existentes
            foreach ($project->costs as $key=>$cost) {
                $cost->cost = $_POST['cost' . $cost->id];
                $cost->description = $_POST['cost-description' . $cost->id];
                $cost->amount = $_POST['cost-amount' . $cost->id];
                $cost->type = $_POST['cost-type' . $cost->id];
                $cost->required = $_POST['cost-required' . $cost->id];
                $cost->from = $_POST['cost-from' . $cost->id];
                $cost->until = $_POST['cost-until' . $cost->id];
            }

            //añadir nuevo coste
            if (!empty($_POST['ncost'])) {
                $cost = new Model\Project\Cost();

                $cost->id = '';
                $cost->project = $project->id;
                $cost->cost = $_POST['ncost'];
                $cost->description = $_POST['ncost-description'];
                $cost->amount = $_POST['ncost-amount'];
                $cost->type = $_POST['ncost-type'];
                $cost->required = $_POST['ncost-required'];
                $cost->from = $_POST['ncost-from'];
                $cost->until = $_POST['ncost-until'];

                $project->costs[] = $cost;
            }

            // quitar los que quiten
            $costes = $project->costs;
            foreach ($project->costs as $key=>$cost) {
                $este = $_POST['remove-cost' . $cost->id];
                if (!empty($este))
                    unset($project->costs[$key]);
            }
            $costes = $project->costs; //para xdebug

            return true;
        }

        /*
         * Paso 5 - RETORNO
         */
        private function process_rewards(&$project, &$errors) {
            if (!isset($_POST['nsocial_reward']))
                return false;

            //tratar retornos sociales
            foreach ($project->social_rewards as $key=>$reward) {
                $reward->reward = $_POST['social_reward' . $reward->id];
                $reward->description = $_POST['social_reward-description' . $reward->id];
                $reward->icon = $_POST['social_reward-icon' . $reward->id];
                $reward->license = $_POST['social_reward-license' . $reward->id];
            }

            // retornos individuales
            foreach ($project->individual_rewards as $key=>$reward) {
                $reward->reward = $_POST['individual_reward' . $reward->id];
                $reward->description = $_POST['individual_reward-description' . $reward->id];
                $reward->icon = $_POST['individual_reward-icon' . $reward->id];
                $reward->amount = $_POST['individual_reward-amount' . $reward->id];
                $reward->units = $_POST['individual_reward-units' . $reward->id];
            }

            // tratar nuevos retornos
            if (!empty($_POST['nsocial_reward'])) {
                $reward = new Model\Project\Reward();

                $reward->id = '';
                $reward->project = $project->id;
                $reward->reward = $_POST['nsocial_reward'];
                $reward->description = $_POST['nsocial_reward-description'];
                $reward->type = 'social';
                $reward->icon = $_POST['nsocial_reward-icon'];
                $reward->license = $_POST['nsocial_reward-license'];

                $project->social_rewards[] = $reward;
            }

            if (!empty($_POST['nindividual_reward'])) {
                $reward = new Model\Project\Reward();

                $reward->id = '';
                $reward->project = $project->id;
                $reward->reward = $_POST['nindividual_reward'];
                $reward->description = $_POST['nindividual_reward-description'];
                $reward->type = 'individual';
                $reward->icon = $_POST['nindividual_reward-icon'];
                $reward->amount = $_POST['nindividual_reward-amount'];
                $reward->units = $_POST['nindividual_reward-units'];

                $project->individual_rewards[] = $reward;
            }

            // quitar los retornos colectivos
            foreach ($project->social_rewards as $key=>$reward) {
                $este = $_POST['remove-social_reward' . $reward->id];
                if (!empty($este))
                    unset($project->social_rewards[$key]);
            }

            // quitar las recompensas individuales
            foreach ($project->individual_rewards as $key=>$reward) {
                $este = $_POST['remove-individual_reward' . $reward->id];
                if (!empty($este))
                    unset($project->individual_rewards[$key]);
            }

            return true;
        }

        /*
         * Paso 6 - COLABORACIONES
         */
         private function process_supports(&$project, &$errors) {
            if (!isset($_POST['nsupport']))
                return false;

            // tratar colaboraciones existentes
            foreach ($project->supports as $key=>$support) {
                $support->support = $_POST['support' . $support->id];
                $support->description = $_POST['support-description' . $support->id];
                $support->type = $_POST['support-type' . $support->id];
            }

            // añadir nueva colaboracion
            if (!empty($_POST['nsupport'])) {
                $support = new Model\Project\Support();

                $support->id = '';
                $support->project = $project->id;
                $support->support = $_POST['nsupport'];
                $support->description = $_POST['nsupport-description'];
                $support->type = $_POST['nsupport-type'];

                $project->supports[] = $support;
            }

            // quitar las colaboraciones marcadas para quitar
            foreach ($project->supports as $key=>$support) {
                if ($_POST['remove-support' . $support->id] == 1) 
                    unset($project->supports[$key]);
            }

            return true;
        }

        /*
         * Paso 7 - PREVIEW
         * No hay nada que tratar porque aq este paso no se le envia nada por post
         */
        private function process_preview(&$project) {
            if (!isset($_POST['comment']))
                return false;

            $project->comment = $_POST['comment'];

            return true;
        }
        //-------------------------------------------------------------
        // Hasta aquí los métodos privados para el tratamiento de datos
        //-------------------------------------------------------------
   }

}