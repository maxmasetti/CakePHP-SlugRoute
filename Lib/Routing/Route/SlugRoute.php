<?php

App::uses('ClassRegistry', 'Utility');

class SlugRoute extends CakeRoute {

  function parse($url) {

    $params = parent::parse($url);
    if (empty($params)) {
        return false;
    }
    #echo 'URL: '; var_dump($params);
    #echo 'PARAMS: '; var_dump($params);
    #echo 'OPTIONS: '; var_dump($this->options);
    #echo 'KEYS: '; var_dump($this->keys);
    #echo 'THIS: '; var_dump($this);

    if (isset($params['slug'])) {
      $controller = $this->defaults['controller'];
      $model = Inflector::classify($controller);
      $slugs = $this->slugs($model);
      array_unshift($params['pass'], $slugs[$params['slug']]);
      #echo 'PARAMS: '; var_dump($params);
      return $params;
    }

    $keys = $this->keys; sort($keys);
    $opts = array_keys($this->options); sort($opts);
    #echo 'KEYS: '; var_dump($keys);
    #echo 'OPTS: '; var_dump($opts);
    #echo 'inters: '; var_dump(array_intersect($keys, $opts));

    if(array_intersect($keys, $opts) == $opts) {
      $rev_keys = array_flip($this->keys);
      foreach ($this->options as $model => $dummy) {
        $slugs = $this->slugs($model);
        $params['pass'][$rev_keys[$model]] = $slugs[$params[$model]];
        #unset($params[$model]);
      }
      #echo 'PARAMS: '; var_dump($params);
      return $params;
    }

    return false;
  }


  public function match($url) {

    $controller = $this->defaults['controller'];
    $action = $this->defaults['action'];
    $options = array_diff($this->options, array('siteMap'=>''));

    if($url['controller'] == $controller && $url['action'] == $action) {

      #echo "CTRL: $controller\n";
      #echo "Action: $action\n";
      #echo "URL: "; var_dump($url);
      #echo "OPTIONS: "; var_dump($this->options);

      if(count($options) == 0) {
        // usa il tag :slug
        $model = Inflector::classify($controller);
        $slugs = array_flip($this->slugs($model));
        $url['slug'] = $slugs[$url[0]];
        unset($url[0]);

      } else {
        $i = 0;
        #  var_dump($url);
        #  echo "OPTIONS: "; var_dump($this->options);
        #  echo "KEYS: "; var_dump($this->options);
        foreach ($options as $model => $val) {
          $cntrl = Inflector::pluralize($model);
          #  echo "MODEL: $model\n";
          #  echo "CONTROLLER: $cntrl\n";
          $slugs = array_flip($this->slugs($model));
          #  echo "SLUGS: "; var_dump($slugs);
          if(empty($slugs)) {
            trigger_error("No items found for model $model", E_WARNING);
            return false;
          }
          #  echo "URL PRE: "; var_dump($url);
          $url[$model] = $slugs[$url[$i]];
          #  echo "URL POST: "; var_dump($url);
          unset($url[$i]);
          #  echo "URL RMVD: "; var_dump($url);
          #  echo "<hr>";
          $i++;
        }
      }
    }
    return parent::match($url);
  }

  
  private function slugs($model) {

    $slugs = Cache::read($model . '_slugs');

    if ($slugs === false || empty($slugs)) {

      App::import('Model', Inflector::classify($model));
      $Model = ClassRegistry::init(Inflector::classify($model));
      $titles = $Model->find('list', array(
          //'fields' => array($Model->displayField),
          'order' => array($Model->displayField => 'ASC', 'id' => 'ASC'),
          'recursive' => -1
      ));

      // remove double slugs adding a '-' at end
      $prev = '';
      foreach ($titles as $i => $t) {
        $titles[$i] = strtolower(Inflector::slug($t, '-'));
        if ($titles[$i] == $prev) {
          $titles[$i] .= '-';
        }
        $prev = $titles[$i];
      }

      $slugs = array_flip($titles);
      #var_dump($slugs);
      Cache::write($model . '_slugs', $slugs);
    }

    return $slugs;
  }


  static public function invalidateCache($model) {
    Cache::delete($model . '_slugs');
  }

}
