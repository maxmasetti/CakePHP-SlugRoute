# CakePHP SlugRoute

SlugRoute is a first trial to implement automatic slugged url for CakePHP, that implies:

- Slugged url and link generated by CakeRoute system (i.e. $this->Html->url() in templates)
- Sitemap SEO semi-automatic generation
- Routing of slugged url to controller/action/parameter

Example:

- the file route.php and SitemapsController.php also contain some example code.

Installation:

- not a plugin yet (help required)
- copy files and folder into the standard cakephp folder structure
- only SlugRoute.php and routes.php are necessary for slugging routes, the other files are for the sitemap in xml

Usage:

- In the routes.php are defined two routes for Contests and Special, I think quite selfexplanatory.
- Remember to empty the slug cache when add or modify a slugged object
- The slug is taken from the display field as defined in the Model
