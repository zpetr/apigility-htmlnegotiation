apigility-htmlnegotiation
=========================

HtmlNegotiation module for Apigility.

Response type is based on *Accept* header :

- request that specifies **text/html** (or **text/\*+html**) get the content in HTML
- **application/hal+json** (or **application/\*+json**) request get the content in HalJson as usual.

### Installation
Install composer in your project

    curl -s http://getcomposer.org/installer | php

Define dependencies in your composer.json file

```json
{
    "require": {
        "zpetr/apigility-htmlnegotiation" : "1.*"
    }
}
```

Finally install dependencies

    php composer.phar install

or update it

    php composer.phar update

### Usage
- Add *zPetr\\HtmlNegotiation* to config/modules.config.php:
```php
	return array(
    	'modules' => array(
        	...,
            'zPetr\\HtmlNegotiation',
            ....
		)
	)     
```
- Go to admin, select your API and change *Content Negotiation Selector* to **HTML-HalJson**
- Add **text/html** to *Accept whitelist* and *Content-Type whitelist*. Add other headers if needed.
- Save configuration

### Templates

#### Layout
If you want to personalize custom layout template:

1. Copy default *zpetr/apigility-htmlnegotiation/view/layout.phtml* template to the view folder of your module (why not to Application module)
2. Add this to module config:
```php
	...
	'view_manager' => array(
		'template_map' => array(
			'zpetr/htmlnegotiation/layout'	=> __DIR__ . '/../view/layout.phtml',
		),
		'template_path_stack' => array(
			__DIR__ . '/../view',
		),
	),
```

#### API response templates

To customize any API template:

1. Create *view* folder in your API directory. For example, if you have API named Foo with REST service named Bar, create:<pre>module/Foo/src/Foo/V1/Rest/Bar/view/</pre>
2. Create *get.phtml* for Entity template and *get_list.phtml* for Collection template. You can use default ones from *zpetr/apigility-htmlnegotiation/view/zf/rest/* folder.
