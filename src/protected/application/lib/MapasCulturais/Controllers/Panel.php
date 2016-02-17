<?php
namespace MapasCulturais\Controllers;

use \MapasCulturais\App;

/**
 * User Panel Controller
 *
 * By default this controller is registered with the id 'panel'.
 *
 */
class Panel extends \MapasCulturais\Controller {

    /**
     * Render the user panel.
     *
     * This method requires authentication and renders the template 'panel/index'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel');
     * </code>
     *
     */
    function GET_index(){
        $this->requireAuthentication();

        $app = App::i();

        $count = new \stdClass();
        $count->spaces = $this->countEntity('Space');
        $count->events = $this->countEntity('Event');
        $count->projects = $this->countEntity('Project');

        $count->agents = $app->em->createQuery("SELECT COUNT(a.id) FROM \MapasCulturais\Entities\Agent a JOIN a.user u
            WHERE u = :user AND a.status >= 0")->setParameter('user', $app->user)->getSingleScalarResult();
        $count->agents = str_pad($count->agents, 2, '0', STR_PAD_LEFT);

        $this->render('index', ['count'=>$count]);
    }

    protected function countEntity($entityName){
        $app = App::i();
        $entityClass = '\\MapasCulturais\\Entities\\' . $entityName;
        $dql = "SELECT COUNT(e.id) FROM $entityClass e JOIN e.owner o WHERE o.user = :user AND e.status >= 0";
        $query = $app->em->createQuery($dql);
        $query->setParameter('user', $app->user);
        $count = $query->getSingleScalarResult();
        $padded = str_pad($count, 2, '0', STR_PAD_LEFT);
        return $padded;
    }

    protected function _getUser(){
        $app = App::i();
        $user = null;
        if($app->user->is('admin') && key_exists('userId', $this->data)){
            $user = $app->repo('User')->find($this->data['userId']);


        }elseif($app->user->is('admin') && key_exists('agentId', $this->data)){
            $agent = $app->repo('Agent')->find($this->data['agentId']);
            $user = $agent->user;
        }
        if(!$user)
            $user = $app->user;

        return $user;
    }

    function GET_requireAuth(){
        $this->requireAuthentication();
        $this->render('require-authentication');
    }

    /**
     * Render the agent list of the user panel.
     *
     * This method requires authentication and renders the template 'panel/agents'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel', 'agents');
     * </code>
     *
     */
    function GET_agents(){
        $this->requireAuthentication();
        $user = $this->_getUser();

        $this->render('agents', ['user' => $user]);
    }


    /**
     * Render the space list of the user panel.
     *
     * This method requires authentication and renders the template 'panel/spaces'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel', 'spaces');
     * </code>
     *
     */
    function GET_spaces(){
        $this->requireAuthentication();
        $user = $this->_getUser();

        $this->render('spaces', ['user' => $user]);
    }

    /**
     * Render the event list of the user panel.
     *
     * This method requires authentication and renders the template 'panel/events'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel', 'events');
     * </code>
     *
     */
    function GET_events(){
        $this->requireAuthentication();
        $user = $this->_getUser();

        $this->render('events', ['user' => $user]);
    }

    /**
     * Render the project list of the user panel.
     *
     * This method requires authentication and renders the template 'panel/projects'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel', 'projects');
     * </code>
     *
     */
    function GET_projects(){
        $this->requireAuthentication();
        $user = $this->_getUser();

        $this->render('projects', ['user' => $user]);
    }

    /**
     * Render the project list of the user panel.
     *
     * This method requires authentication and renders the template 'panel/projects'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel', 'registrations');
     * </code>
     *
     */
    function GET_registrations(){
        $this->requireAuthentication();
        $user = $this->_getUser();

        $this->render('registrations', ['user' => $user]);
    }

    /**
     * Render the project list of the user panel.
     *
     * This method requires authentication and renders the template 'panel/projects'
     *
     * <code>
     * // creates the url to this action
     * $url = $app->createUrl('panel', 'registrations');
     * </code>
     *
     */
    function GET_apps(){
        $this->requireAuthentication();
        $user = $this->_getUser();
        $enabledApps = App::i()->repo('UserApp')->findBy(['user' => $user, 'status' => \MapasCulturais\Entities\UserApp::STATUS_ENABLED]);
        $thrashedApps = App::i()->repo('UserApp')->findBy(['user' => $user, 'status' => \MapasCulturais\Entities\UserApp::STATUS_TRASH]);
        $this->render('apps', ['user' => $user, 'enabledApps' => $enabledApps, 'thrashedApps' => $thrashedApps]);
    }
}