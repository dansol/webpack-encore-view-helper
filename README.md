# webpack-encore-view-helper

webpack-encore-view-helper is a laminas-view helper that help render all of the dynamic script and link tags needed 
when using Symfony Webpack Encore( a simpler way to integrate Webpack into your application - https://symfony.com/doc/current/frontend.html#webpack-encore).

## Installation

Run the following to install:

```bash
$ composer require dansol/webpack-encore-view-helper
```

## Documentation
Copy webpack_encore_view_helper_config.global.php.dist to config/autoload and rename webpack_encore_view_helper_config.global.php<br>
Adjust configuration based on your enviroment.<br>
example:

```php
<?php
return [
	...
    'webpack_encore_view_helper_config'=>[
		/* webpack encore entry points */
        'entrypoints_map' => json_decode(file_get_contents(__DIR__ . '/../../public/build/entrypoints.json'),true),
		/* map view to entrypoint ( array can be empty if templates name match entrypoint name when using auto* parameters */
        'template_entrypoint_map'=>[
			
			'layout::default'=>'app',
        ]
    ]
];
```

Load ConfigProvider in your configuration loader.<br>
Mezzio example:

```php
<?php
$aggregator = new ConfigAggregator([
	// ... other stuff 
    \WebpackEncoreViewHelper\ConfigProvider::class,
    // Default App module config
      new PhpFileProvider(realpath(__DIR__) . '/autoload/{{,*.}global,{,*.}local}.php'),
    // Load development config if it exists
    new PhpFileProvider(realpath(__DIR__) . '/development.config.php'),
], $cacheConfig['config_cache_path']);
?>
```
### Basic usage

```php
...
// load asset ( in layout template)
$this->webpack_encore_assets('common entrypointname'); // if used a common entry

// load asset ( in template that consume an entrypoint)
$this->webpack_encore_assets('common entrypointname'); // if used a common entry

// in layout template
// render link tags
echo $this->webpack_encore_assets()->render('css');
...
// render script tags
echo $this->webpack_encore_assets()->render('js');
```

### Special parameters
these parameters can be used in parent template(layout template) - if used in child template an error will be raise:
* auto			-  load all assets automatically( parent and first child template in content)
* auto-route	-  load all assets for parent template(layout)
* auto-child	-  load all assets for child template(firt template in layout content)
this parameters avoid the need to load asset in every template that consume an entry point and load asset only in the main layout template

auto* parameters load entry with this logic:
asset is loaded matching templatename/entrypoint name configured in webpack_encore_view_helper_config.global.php configuration file in 
template_entrypoint_map key ( see webpack_encore_view_helper_config.global.php.dist)
if not matched the helper will try to match template name equal to entrypoint name.
If entrypoint is not resolved an error will be raise.


basic example:

```php
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="shortcut icon" href="https://getlaminas.org/images/favicon/favicon.ico" />
    <?=$this->headTitle('mezzio')->setSeparator(' - ')->setAutoEscape(false)?>
    <?=$this->headMeta()?>
    <?php 
		// load all asset for 
		$this->webpack_encore_assets('auto');
	?>
    <?php
		// render links tags for this template(shared entry) and every asset required for the first child template in content
		echo $this->webpack_encore_assets()->render('css')
	?> 
</head>
<body class="app">
	...
    <div class="app-content">
        <main class="container">
            <?=$this->content?> 
        </main>
    </div>
    <footer class="app-footer">
        ...
    </footer>
    <?php
		// render scripts tags for this template(shared entry) and every asset required for the first child template in content
		echo $this->webpack_encore_assets()->render('js')
	?>
</body>
</html>
```

