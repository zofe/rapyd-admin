# library development

### Testing 

To run tests you must run composer install first, then:


```bash

./vendor/phpunit/phpunit/phpunit
```


### build & publish static assets

this library has dependencies on CSS/JS frameworks (bootstrap 5.3 & livewire)
this means that you may need to compile asset and publish them in your project


```bash
npm install

//from library root you can build assets
npm run prod

//from laravel root you can publish library assets
php artisan vendor:publish --provider="Zofe\Rapyd\RapydServiceProvider" --tag="public"
```



Rapyd enable "modules based" folder structure
```
laravel/
├─ app/
│  ├─ Modules/
│  │  ├─ ModuleName/
│  │  │  ├─ Component.php
│  │  │  ├─ component_view.blade.php
│  │  │  ├─ routes.php
│  │  │  ├─ config.php
│  │  │  ├─ Http/
│  │  │  ├─ Models/
│  │  │  │  ├─ Model.php  
│  │  ├─ AnotherModule/
```

