Validation
==========

Validation of user data
<ul>
<li>offline IČ, RČ
<li>online IČ over ARES, cached by Nette/Cache
</ul>

Based on
[JAK OVĚŘIT PLATNÉ IČ A RODNÉ ČÍSLO](http://phpfashion.com/jak-overit-platne-ic-a-rodne-cislo)

Requires
[Edgedesign ARES](https://github.com/EdgedesignCZ/ares)


Installation
------------

The best way to install Trejjam/Validation is using  [Composer](http://getcomposer.org/):

```sh
$ composer require trejjam/validation:dev-master
```

Configuration
-------------

.neon
```yml
extensions:
	validation: Trejjam\DI\ValidationExtension

validation:
	cache:
		use: true #disable cache
		name: ares #cache storage
		timeout: 60 minutes #cache expire
```

Usage
-----

Presenter:

```php
	/**
	 * @var \Trejjam\Validation @inject
	 */
	 public $validation;
	 
	 function renderDefault() {
	 
	    //offline check
	    $rcValid=$this->validation->rc("780123/3540"); //problematic RČ
	 
	    $icValid=$this->validation->ic("25596641"); //problematic IČ 
	 
	    //online check
	    $icValidOnline = $this->validation->aresIc("27604977"); //google IČ
        
        dump($rcValid);
        /*
        dump boolean
            TRUE
        */
        
        dump($icValid);
        /*
        dump boolean
            TRUE
        */
        
        dump($icValidOnline);        
        /*
        dump stdClass
	        ico => "27604977" (8)
	        dic => "CZ27604977" (10)
	        firma => "Google Czech Republic, s.r.o." (29)
	        ulice => "Stroupežnického" (17)
	        cisloOrientacni => "17" (2)
	        cisloPopisne => "3191" (4)
	        cisloMesto => "Praha" (5)
	        castObce => "Smíchov" (8)
	        psc => "15000" (5)
        */
	 }
```