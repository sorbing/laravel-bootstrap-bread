## Laravel 5 Bootstrap 4 BREAD/CRUD Resource Manager

**Laravel 5.6 package for simple start manage app resources (BREAD/CRUD)**


### What it is?

@todo Write a example usage.


### Use

#### Simple usage a BREAD

Register new resource routes:

    Route::resource('products', '\Sorbing\Bread\Controllers\BreadController');

#### Customize and Extend BreadController:

Generate and customize new controller:

    php artisan bread:controller products "Admin\ProductsController"

And register resource routes:

    Route::resource('products', 'Admin\ProductsController');

#### Dropdown menu

@note: For used `.dropdown` components - append the code `@stack('bread_assets')` in your layout template, before closed tag `</body>` (or other place).

#### Select in Form

Implement the Model method `getPlainOptions()` that return the options array `['ID' => 'Name', ...]` for `<select>...</select>` element.