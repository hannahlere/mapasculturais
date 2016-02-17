<?php
namespace MapasCulturais;

use MapasCulturais\App;

/**
 * This is the default MapasCulturais View class. It extends the \Slim\View class adding a layout layer and the option to render the template partially.
 *
 * When rendering, the template can access view object whith the $this variable and the controller that call the render/partial methiod with $this->controller.
 *
 * @property \MapasCulturais\View $layout The layout to use when rendering the template.
 * @property \MapasCulturais\Controller $controller The controller that call the render / partial
 *
 * @property-read \MapasCulturais\AssetManager $assetManager The asset manager
 *
 * @hook **view.render:before ($template_name)** - executed before the render of the template and the layout
 * @hook **view.render({$template_name}):before ($template_name)** - executed before the render of the template and the layout
 * @hook **view.partial:before ($template_name)** - executed before the template render.
 * @hook **view.partial({$template_name}):before ($template_name)** - executed before the template render.
 * @hook **view.partial:after ($template_name, $html)** - executed after the template render.
 * @hook **view.partial({$template_name}):before ($template_name, $html)** - executed after the template render.
 * @hook **view.render:after ($template_name, $html)** - executed after the render of the template and the layout
 * @hook **view.render({$template_name}):before ($template_name, $html)** - executed after the render of the template and the layout
 */
abstract class Theme extends \Slim\View {
    use Traits\MagicGetter,
        Traits\MagicSetter,
        Traits\MagicCallers;


    /**
     * The controller that is using this view object.
     * @var \MapasCulturais\Controller
     */
    protected $_controller;

    /**
     * The layout to use when rendering the template.
     * @var string
     */
    protected $_layout = 'default';

    /**
     * The template that this view is rendering.
     * @var string
     */
    protected $template = '';

    /**
     * When to render the template partially
     * @var bool
     */
    protected $_partial = false;

    protected $_assetManager = null;

    protected $documentMeta = null;

    /**
     * CSS Classes to print in body tag
     * @var  \ArrayObject
     */
    protected $bodyClasses = null;

    /**
     * Properties of body tag
     * @var  \ArrayObject
     */
    protected $bodyProperties =  null;

    /**
     *
     * @var \ArrayObject
     */
    protected $jsObject = null;

    /**
     *
     * @var \ArrayObject
     */
    protected $path = null;

    protected $_dict = [];

    abstract protected function _init();

    abstract function register();


    public function __construct(AssetManager $asset_manager) {
        parent::__construct();

        $this->_assetManager = $asset_manager;

        $app = App::i();

        $this->documentMeta = new \ArrayObject;
        $this->bodyClasses = new \ArrayObject;
        $this->bodyProperties = new \ArrayObject;

        $this->jsObject = new \ArrayObject;
        $this->jsObject['baseURL'] = $app->baseUrl;
        $this->jsObject['assetURL'] = $app->assetUrl;
        $this->jsObject['maxUploadSize'] = $app->getMaxUploadSize($useSuffix=false);
        $this->jsObject['maxUploadSizeFormatted'] = $app->getMaxUploadSize();

        $folders = [];

        $class = get_called_class();
        while($class !== __CLASS__){
            if(!method_exists($class, 'getThemeFolder'))
                throw new \Exception ("getThemeFolder method is required for theme classes and is not present in {$class} class");

            $folders[] = $class::getThemeFolder() . '/';

            $class = get_parent_class($class);
        }

        $this->path = new \ArrayObject(array_reverse($folders));
    }

    function init(){
        $app = App::i();
        $app->applyHookBoundTo($this, 'theme.init:before');
        $this->_init();
        $app->applyHookBoundTo($this, 'theme.init:after');
    }

    protected function _addTexts(array $dict = []){
        $this->_dict = array_merge($dict, $this->_dict);
    }

    function dict($key, $print = true){
        if(!$this->_dict){
            $class = get_called_class();
            while($class !== __CLASS__){
                if(!method_exists($class, '_getTexts'))
                    throw new \Exception ("_getTexts method is required for theme classes and is not present in {$class} class");

                $this->_addTexts($class::_getTexts());
                $class = get_parent_class($class);
            }
        }
        $text = '';
        if(key_exists($key, $this->_dict)){
            $text = $this->_dict[$key];
        }

        if($print){
            echo $text;
        }else{
            return $text;
        }
    }

    /**
     * Sets partial property.
     *
     * Use this passing true when you want to render the template without the layout.
     *
     * @param bool $val
     */
    public function setPartial($val){
        $this->_partial = $val;
    }

    /**
     * Sets the layout property.
     * @param string $name
     */
    public function setLayout($name){
        $this->_layout = $name;
    }

    /**
     * Sets the controller property.
     *
     * @param \MapasCulturais\Controller $controller the controller.
     */
    public function setController(\MapasCulturais\Controller $controller){
        $this->_controller = $controller;
    }

    /**
     * Returns the controller that is using this view object (call render method).
     * @return \MapasCulturais\Controller the controller that call render method.
     */
    public function getController(){
        return $this->_controller;
    }

    /**
     * Render the template.
     *
     * Inside the template the view object is the $this variable and the controller that call the template is $this->controller.
     *
     * If the property "partial" is setted to true the template will be rendered without the layout.
     *
     * @param string $template the template name.
     * @return string The rendered template
     */
    public function render($template, $data = null){
        $this->template = $template;

        if($this->_partial)
            return $this->partialRender ($template, $this->data);
        else
            return $this->fullRender ($template);
    }

    /**
     * Render the template with the layout.
     *
     * To change the layout that will be used set the property "layout" of the this object inside the template file.
     * Inside the template file, the view object is the $this variable, so to set the layout you can do: $this->layout = 'my-layout';
     *
     * This method extracts the property "data" array to make the variables accessible inside the template.
     *
     * @param string $template the template to render
     *
     * @hook **view.render:before ($template_name)** - executed before the render of the template and the layout
     * @hook **view.render({$template_name}):before ($template_name)** - executed before the render of the template and the layout
     * @hook **view.render:after ($template_name, $html)** - executed after the render of the template and the layout
     * @hook **view.render({$template_name}):before ($template_name, $html)** - executed after the render of the template and the layout
     *
     * @return string The rendered template.
     */
    public function fullRender($template){
        $app = App::i();

        $template_filename = strtolower(substr($template, -4)) === '.php' ? $template : $template . '.php';
        $render_data = [];

        foreach($this->data->keys() as $k){
            $render_data[$k] = $this->data->get($k);
            $$k = $this->data->get($k);
        }

        if ($this->controller){
            $this->bodyClasses[] = "controller-{$this->controller->id}";
            $this->bodyClasses[] = "action-{$this->controller->action}";
        }

	if (isset($entity))
            $this->bodyClasses[] = 'entity';

        // render the template
        $templatePath = $this->resolveFilename('views', $template_filename);

        if(strtolower(substr($templatePath, -4)) !== '.php')
                $templatePath .= '.php';


        $template_name = preg_replace('#(.*\/)([^\/]+\/[^\/\.]+)(\.php)?$#', '$2', $templatePath);

        $app->applyHookBoundTo($this, 'view.render(' . $template_name . '):before', ['template' => $template_name]);

        $TEMPLATE_CONTENT = $this->partialRender($template_name, $this->data);

        $layout_filename = strtolower(substr($this->_layout, -4)) === '.php' ? $this->_layout : $this->_layout . '.php';

        // render the layout with template
        $layoutPath = $this->resolveFilename('layouts', $layout_filename);

        if(strtolower(substr($layoutPath, -4)) !== '.php')
                $layoutPath .= '.php';

        ob_start(function($output){
            return $output;
        });

        include $layoutPath;

        $html = ob_get_clean();

        $app->applyHookBoundTo($this, 'view.render(' . $template_name . '):after', ['template' => $template_name, 'html' => &$html]);

        return $html;
    }

    /**
     * Render the template without the layout.
     *
     * This method is called when the property "partial" was setted to true before the render method is called.
     *
     * This method extracts the data array to make the variables accessible inside the template.
     *
     * @param string $template the template to render
     * @param array $data the data to be passed to template.
     *
     * @hook **view.partial:before ($template_name)** - executed before the template render.
     * @hook **view.partial({$template_name}):before ($template_name)** - executed before the template render.
     * @hook **view.partial:after ($template_name, $html)** - executed after the template render.
     * @hook **view.partial({$template_name}):before ($template_name, $html)** - executed after the template render.
     *
     * @return string The rendered template.
     */
    public function partialRender($template, $data = [], $_is_part = false){
        $app = App::i();
        if(strtolower(substr($template, -4)) === '.php'){
            $template_filename = $template;
            $template = substr($template, 0, -4);
        } else {
            $template_filename = $template . '.php';
        }

        if(is_array($data))
            extract($data);
        elseif($data instanceof \Slim\Helper\Set)
            foreach($this->data->keys() as $k)
                $$k = $this->data->get($k);


        // render the template
        if($_is_part){
            $templatePath = $this->resolveFilename('layouts', 'parts/' . $template_filename);
        }else{
            $templatePath = $this->resolveFilename('views', $template_filename);
        }


        if(strtolower(substr($templatePath, -4)) !== '.php' && strtolower(substr($templatePath, -5)) !== '.html')
                $templatePath .= '.php';

        $template_name = substr(preg_replace('#^'.$this->templatesDirectory.'/?#', '', $templatePath),0,-4);

        $app->applyHookBoundTo($this, 'view.partial(' . $template . '):before', ['template' => $template]);

        ob_start(function($output){
            return $output;
        });
        
        if($app->config['themes.active.debugParts']){
            $template_debug = str_replace(THEMES_PATH, '', $template_name);
            echo '<!-- ' . $template_debug . ".php # BEGIN -->";
        }

        include $templatePath;
        
        if($app->config['themes.active.debugParts']){
            echo '<!-- ' . $template_debug . ".php # END -->";
        }

        $html = ob_get_clean();

        $app->applyHookBoundTo($this, 'view.partial(' . $template . '):after', ['template' => $template, 'html' => &$html]);

        return $html;
    }

    /**
     * Render a template without the layout.
     *
     * Use this method inside templates to include some part of html. This method call the method partialRender to render the
     * template part.
     *
     * If no folder is specified in $template param, the folder "parts" inside the layout folder will be used, so if you call
     * $this->part('foo'), the themes/active/layout/parts/foo.php will be rendered. Otherwise, if you call $this->part('foo/boo'),
     * the themes/active/views/foo/boo.php will be included.
     *
     * @param string $template
     * @param array $data Data to be passed to template part.
     */
    public function part($template, $data = []){
        echo $this->partialRender($template, $data, true);
    }

    function getTitle($entity = null){
        $app = App::i();
        $title = '';
        if($entity){
            $title = $entity->name . ' - ' . $app->siteName;
        }elseif($this->controller->id == 'site' && $this->controller->action === 'index'){
            $title = $app->siteName;
        }elseif($this->controller->id == 'panel' && $this->controller->action === 'index'){
            $title = $app->getReadableName('panel');
        }else{
            $title =$app->getReadableName($this->controller->action);
        }

        return $title;
    }

    function addPath($path){
        if(substr($path,-1) !== '/') $path .= '/';

        $this->path[] = (string) $path;
    }

    /**
     *
     * @return \MapasCulturais\AssetManager
     */
    function getAssetManager(){
        return $this->_assetManager;
    }

    function enqueueScript($group, $script_name, $script_filename, array $dependences = []){
        $app = App::i();
        if($app->config['app.log.assets']){
            $dep = implode(', ', $dependences);
            $app->log->debug("enqueueScript ({$group}) {$script_name} : {$script_filename} ({$dep})");
        }
        $this->_assetManager->enqueueScript($group, $script_name, $script_filename, $dependences);
    }

    function enqueueStyle($group, $style_name, $style_filename, array $dependences = [], $media = 'all'){
        $app = App::i();
        if($app->config['app.log.assets']){
            $dep = implode(', ', $dependences);
            $app->log->debug("enqueueScript ({$group}) {$style_name} : {$style_filename} ({$dep})");
        }
        $this->_assetManager->enqueueStyle($group, $style_name, $style_filename, $dependences, $media);
    }

    function printScripts($group){
        $this->_assetManager->printScripts($group);
    }

    function printStyles($group){
        $this->_assetManager->printStyles($group);
    }

    function printDocumentMeta(){
        foreach($this->documentMeta as $metacfg){
            $meta = "\n <meta";
            foreach($metacfg as $prop => $val){
                $val = htmlentities($val);
                $meta .= " {$prop}=\"{$val}\"";
            }
            $meta .= ' />';
            echo $meta;
        }
    }

    function resolveFilename($folder, $file){
        if(!substr($folder, -1) !== '/') $folder .= '/';

        $path = array_reverse($this->path->getArrayCopy());
        foreach($path as $dir){
            if(file_exists($dir . $folder . $file)){
                return $dir . $folder . $file;
            }
        }

        return null;
    }

    function getAssetFilename($file){
        $filename = $this->resolveFilename('assets', $file);
        if(!$filename) throw new \Exception('Asset not found: ' . $file);

        return $filename;
    }

    function asset($file, $print = true){
        $url = $this->getAssetManager()->assetUrl($file);
        if($print){
            echo $url;
        }

        return $url;
    }

    function renderMarkdown($markdown){
        $app = App::i();
        $matches = [];
        if(preg_match_all('#\{\{asset:([^\}]+)\}\}#', $markdown, $matches)){
            foreach($matches[0] as $i => $tag){
                $markdown = str_replace($tag, $this->asset($matches[1][$i], false), $markdown);
            }
        }

        if(preg_match_all('#\{\{dict:([^\}]+)\}\}#', $markdown, $matches)){
            foreach($matches[0] as $i => $tag){
                $markdown = str_replace($tag, $this->dict(trim($matches[1][$i]), false), $markdown);
            }
        }

        $markdown = str_replace('{{baseURL}}', $app->getBaseUrl(), $markdown);
        $markdown = str_replace('{{assetURL}}', $app->getAssetUrl(), $markdown);
        return \Michelf\MarkdownExtra::defaultTransform($markdown);
    }

    function isEditable(){
        return (bool) preg_match('#^\w+/(create|edit)$#', $this->template);
    }

    function isSearch(){
        return (bool) $this->controller->id === 'site' && $this->action === 'search';
    }

    function bodyBegin(){
        App::i()->applyHook('mapasculturais.body:before');
    }

    function bodyEnd(){
        App::i()->applyHook('mapasculturais.body:after');
    }

    function bodyProperties(){
        $body_properties = [];

        foreach ($this->bodyProperties as $key => $val)
            $body_properties[] = "{$key}=\"$val\"";

        $body_properties[] = 'class="' . implode(' ', $this->bodyClasses->getArrayCopy()) . '"';

        echo implode(' ', $body_properties);
    }

    function head(){
        $app = App::i();

        $app->applyHook('mapasculturais.head');

        $this->printDocumentMeta();

    }
    
    function applyTemplateHook($name, $sufix = ''){
        $hook = "template({$this->controller->id}.{$this->controller->action}.$name)";
        if($sufix){
            $hook .= ':' . $sufix;
        }
        App::i()->applyHookBoundTo($this, $hook);
    }
}