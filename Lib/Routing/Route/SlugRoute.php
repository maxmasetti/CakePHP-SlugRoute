<?php

App::uses('ClassRegistry', 'Utility');

class SlugRoute extends CakeRoute {

  function parse($url) {

    $params = parent::parse($url);
    if (empty($params)) {
        return false;
    }

    #echo 'THIS: '; var_dump($this);
    #echo 'URL: '; var_dump($url);
    #echo 'OPTIONS: '; var_dump($this->options);

    #echo 'PARAMS: '; var_dump($params);
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
    #echo 'OPTIONS: '; var_dump($this->options);
    //$options = array_diff($this->options, array('siteMap'=>'' ));
    $options = $this->options; 
    unset($options['siteMap']);  // remove siteMap option
    unset($options['pass']);     // remove pass option
    $opts = array_keys($options);
    sort($opts);
    ##
    #echo 'KEYS: '; var_dump($keys);
    #echo 'OPTIONS: '; var_dump($options);
    #echo 'OPTS: '; var_dump($opts);
    #echo 'inters: '; var_dump(array_intersect($keys, $opts));

    if(array_intersect($keys, $opts) == $opts) {
      $rev_keys = array_flip($this->keys);
      #echo 'REV KEYS: '; var_dump($rev_keys);
      // sono le options (e non gli slug del template) che dicono in che ordine passare i parametri
      $add2pass = array();
      foreach ($options as $model => $dummy) {
        $slugs = $this->slugs($model);
        $add2pass[$rev_keys[$model]] = $slugs[$params[$model]];
        #unset($params[$model]);
      }
      #echo 'ADD2PASS: '; var_dump($add2pass);
      // inoltre i parametri (derivati dagli slug) vanno aggiunti ai pass parametri e sono posizionati prima dei pass
      $params['pass'] = array_merge($add2pass, $params['pass']);
      #echo 'PARAMS: '; var_dump($params);
      return $params;
    }

    return false;
  }


  public function match($url) {

    $controller = $this->defaults['controller'];
    $action = $this->defaults['action'];
    #$options = array_diff($this->options, array('siteMap'=>''));
    $options = $this->options; unset($options['siteMap']); unset($options['pass']);

    if( (!isset($url['admin']) || $url['admin']==false)
        && $url['controller'] == $controller && $url['action'] == $action) {

      #echo "CTRL: $controller\n";
      #echo "Action: $action\n";
      #echo "URL: "; var_dump($url);
      #echo "OPTIONS: "; var_dump($this->options);

      if(count($options) == 0) {
        // use the tag :slug
        $model = Inflector::classify($controller);
        $slugs = array_flip($this->slugs($model));

        if(!isset($slugs[$url[0]])) return false;  // slug not found

        $url['slug'] = $slugs[$url[0]];
        unset($url[0]);

      } else {
        // using the custom :tag
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
          'fields' => array($Model->displayField),
          #'order' => array($Model->displayField => 'ASC', 'id' => 'ASC'),
          'order' => array('id' => 'ASC'),
          'recursive' => -1,
      ));

      #print_r($titles); #die;

      foreach ($titles as $i => $t) {
        $titles[$i] = strtolower(Inflector::slug($t, '-'));
      }

      // QUESTO ORDINAMENTO NON PRESERVA L'ORDINE DELLE CHIAVI: CHE È UN PROBLEMA.  FIXME
      asort($titles);
      #print_r($titles); #die;

      // ELIMINA I DOPPI SLUG AGGIUNGENDO UN '-' ALLA FINE
      $prev = ''; $post = '-';
      foreach ($titles as $i => $t) {
        if ($titles[$i] == $prev) {
          $titles[$i] .= $post;
          $post .= '-';
        } else {
          $prev = $titles[$i];
          $post = '-';
        }
      }
      #print_r($titles); #die;

      $slugs = array_flip($titles);
      #print_r($slugs); die;

      Cache::write($model . '_slugs', $slugs);
    }

    return $slugs;
  }


  static public function invalidateCache($model) {
    Cache::delete($model . '_slugs');
  }

}