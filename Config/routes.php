<?php

  // Examples of slugged routes
  
  Router::connect('/chef/:slug/*',
    array('controller' => 'chefs', 'action'=>'view'),
    array('routeClass' => 'SlugRoute')
  );

  Router::connect('/iniziativa/:contest/classifiche/*',
    array('controller' => 'contests', 'action' => 'classifiche'),
    array('routeClass' => 'SlugRoute',
        'contest' => '[a-z0-9-]+',
    )
  );

  Router::connect('/iniziativa/:contest/speciale/:special/*',
    array('controller' => 'specials', 'action' => 'view'),
    array('routeClass' => 'SlugRoute',
        'contest' => '[a-z0-9-]+',
        'special' => '[a-z0-9-]+',
    )
  );

  // also add this for sitemap

  Router::parseExtensions('xml');

  Router::connect('/sitemap', array('controller' => 'sitemaps', 'action' => 'index'));

