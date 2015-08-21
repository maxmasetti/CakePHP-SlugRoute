<?php
class SitemapsController extends AppController {

  var $name = 'Sitemaps';

  var $uses = array (
      'Contest',
      'Special',
  );

  var $helpers = array (
      'Time'
  );

  var $components = array (
      'RequestHandler'
  );


  function index() {

    $debug = false;

    if ($debug) {
      Configure::write('debug', 2);
      $this->RequestHandler->respondAs('html');
      $this->layout = 'debug';
      $this->view = 'debug';
      $limit = 10;
      echo "<pre>";

    } else {
      Configure::write('debug', 0);
      $this->RequestHandler->respondAs('xml');
      $this->layout = 'sitemap';
      $limit = null;
    }

    if(isset($this->request->params['ext']) && $this->request->params['ext'] == 'xml') {
      // XML SET AUTOMATICALLY
      //$this->view = '/Sitemaps/index';  // impedisce nel path /xml/

    } else {
      // forza l'uso del template dalla directory giusta
      $this->layout = 'xml/' . $this->layout;
      $this->view = 'xml/' . $this->view;
    }

    #Configure::write('debug', 2);
    #$this->RequestHandler->respondAs('html');
    #echo "view: " . $this->view . "\n";
    #echo "layout: " . $this->layout . "\n\n";
    #die;

    $Models = array_combine($this->uses, $this->uses);
    $controllers = array_map(function($value){
      return Inflector::tableize($value);
    }, $Models);
    $Models = array_flip($controllers);
    #var_dump($controllers);

    //var_dump($this->uses); die;
    #  echo "ROUTES:\n";
    $routes = array();
    foreach(Router::$routes as $route) {
      if( true
          && $route->template != '/'
          && isset($route->defaults['controller'])
          && in_array($route->defaults['controller'], $controllers)
          && (!isset($route->defaults['prefix']) || $route->defaults['prefix'] == '')
          && (!isset($route->defaults['admin']) || $route->defaults['admin'] == false)
          && !isset($route->options['plugin'])
          && $route->defaults['plugin'] == null
      ) {
        #  echo "<span style='color:red'>" . $route->template . "</span>\n";
        $routes[] = $route;
      } else {
        #  echo "<span style='color:gray'>" . $route->template . "</span>\n";
      }
    }
    #  var_dump($routes);
    #  echo "<hr>";

    $urls = array();

    foreach ($routes as $route) {
      #  echo "<hr>";
      #  echo "<span style='color:red'>" . $route->template . "</span>\n";
      #  echo "KEYS: "; print_r($route->keys);

      $controller = $route->defaults['controller'];
      $action = $route->defaults['action'];
      $keys = array_flip($route->keys);

      if(isset($route->options['siteMap']) && $route->options['siteMap'] == false) {
        # NO SITEMAP
        continue;

      } elseif(empty($route->keys) /*$action == 'index'*/) {
        # INDEX
        $url = array(
          'url' => array('controller' => $controller, 'action' => $action),
          'mod' => date('Y-m-d H:i:s'),
          'pri' => '0.8',
        );
        #  echo "<span style='color: blue'>" . Router::url($url['url']) . "</span><hr>";
        $urls[] = $url;


      } else {

        if (isset($keys['slug'])) {
          // :SLUG

          $model = $Models[$controller];
          #  echo "Model: $model\n";

          $options = array (
              'conditions' => array (
                  "$model.visible" => true,
              ),
              'contain' => array(),
              'fields' => array (
                  "DISTINCT $model.id",
                  "$model.modified",
              ),
              'limit' => $limit,
          );

        } else {
          // IN ORDINE DI ROUTE->OPTIONS

          $models = array_keys($route->options);
          if(empty($models)) {
            // OPPURE IN ORDINE DI ROUTE->KEYS
            $models = $route->keys;
          }
          #  var_dump($models); //die;

          // PRENDE IL PRIMO COME MODELLO DI PARTENZA PER LA QUERY
          $model = Inflector::classify(array_shift($models));
          #  echo "START Model: $model\n";

          $options = array (
              'conditions' => array (
                  "$model.visible" => true,
              ),
              'contain' => array(),
              'fields' => array (
                  "DISTINCT $model.id",
                  "$model.modified",
              ),
              'limit' => $limit,
          );

          #  echo "ALTRI modelli: "; print_r($models);

          $models = array_diff($route->keys, array('slug', strtolower($model)));

          if (!empty($models)) {
            $options['contain'] = array();
            $prevModel = $model;

            $container = &$options['contain'];

            foreach ($models as $m) {
              $m = Inflector::classify($m);

              #  echo "$prevModel: "; var_dump($this->$prevModel);

              /** /
                echo "BELONGSTO: "; var_dump($this->$prevModel->belongsTo);
              if(isset($this->$prevModel->belongsTo[$m])) {
                $this->$prevModel->unbindModel(array('belongsTo'=>array($m)));
                echo "BELONGSTO: "; var_dump($this->$prevModel->belongsTo);
              }/**/

              if(isset($this->$prevModel->hasMany[$m])) {
                $this->$prevModel->unbindModel(array('hasMany'=>array($m)), true);
                #  echo "HASMANY: "; var_dump($this->$prevModel->hasMany);

              }
                #  echo "HASONE -> $m: "; var_dump($this->$prevModel->hasOne);
                if(!isset($this->$prevModel->hasOne[$m])) {
                  $this->$prevModel->bindModel(array('hasOne'=>array($m)), true);
                  #  echo "HASONE -> $m: "; var_dump($this->$prevModel->hasOne);
                }

              $container[$m] = array(
                  'fields' => array("id"),
                  'conditions' => array(
                      ##"NOT ISNULL($m.id)",
                      "$m.visible" => true,
                  ),
                  'limit' => $limit
              );

              $prevModel = $m;
              $container = &$container[$m];
            }
          }
        }
        #  echo "Options: "; print_r($options);

        $items = $this->$model->find('all', $options);
        #  echo "Items: "; print_r($items);

        foreach ($items as $item) {
          #  echo "ITEM: "; var_dump($item);
          $url = array(
              'url' => array('controller' => $controller, 'action' => $action),
              'mod' => $item[$model]['modified'],
              'pri' => '0.6',
          );

          foreach ($route->keys as $k ) {
            $m = Inflector::classify($k);
            $c = Inflector::pluralize($k);
            #  echo "key: $k -> $m -> $c -- model: $model \n";

            if ($k == 'slug') {
              $url['url'][] = $item[$model]['id'];
              break;

            } else /*if (!is_array($item[$m]))*/ {
              if($item[$m]['id'] == null) {
                #echo "AHAH!!!: "; var_dump($item[$m]); echo "<hr>";
                $url = null;
                break;
              }
              $url['url'][] = $item[$m]['id'];

            } /*else {
              echo "ITEM: "; var_dump($item);
              foreach ($item as $k=>$i) {
                $url['url'][] = $i[$Models[$c]]['id'];
              }
            } */
            #  echo "URL: "; print_r($url);
          }
          #  echo "<span style='color: green'>" . Router::url($url['url']) . "</span><hr>";
          if($url != null) {
            $urls[] = $url;
          }
        }

      }
    }


    $current_contest = $contests = array();  // block variables generation in AppController

    $this->set(compact('urls', 'current_contest', 'contests'));
    //var_dump($urls); die;

  }
}

