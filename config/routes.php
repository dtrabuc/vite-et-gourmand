<?php
return [
    // Public routes
    ['GET', '/', 'PublicController@index', ['Security']],
    ['GET', '/menus', 'PublicController@menusPage', ['Security']],
    ['GET', '/menus/{id}', 'PublicController@menuDetail', ['Security']],
    ['GET', '/public/menus', 'PublicController@getMenus', ['Security']],
    ['GET', '/public/menus/{id}', 'PublicController@getMenuById', ['Security']],
    ['GET', '/menus/filter', 'PublicController@filterMenus', ['Security']],
    ['GET', '/reviews/homepage', 'PublicController@getHomepageReviews', ['Security']],
    // NEW ENDPOINTS for frontend alignment
    ['GET', '/public/catalog', 'PublicController@getCatalog', ['Security']],
    ['GET', '/public/reviews', 'PublicController@getReviews', ['Security']],

    // Auth routes
    ['GET', '/login', 'AuthController@showLogin', ['Security']],
    ['POST', '/login', 'AuthController@login', ['Security']],
    ['GET', '/register', 'AuthController@showRegister', ['Security']],
    ['POST', '/register', 'AuthController@register', ['Security']],
    ['GET', '/profile', 'AuthController@profile', ['Security']],
    ['PUT', '/profile', 'AuthController@updateProfile', ['Security']],
    // Also accept POST with _method=PUT for updateProfile (we'll handle in controller or middleware)
    ['POST', '/profile', 'AuthController@updateProfile', ['Security']], // Temporary, we'll rely on controller to check _method
    ['PUT', '/password', 'AuthController@changePassword', ['Security']],
    ['POST', '/password', 'AuthController@changePassword', ['Security']], // Temporary
    ['POST', '/logout', 'AuthController@logout', ['Security']],
    ['GET', '/forgot-password', 'AuthController@showForgotPassword', ['Security']],
    ['POST', '/forgot-password', 'AuthController@forgotPassword', ['Security']],
    ['GET', '/reset-password/{token}', 'AuthController@showResetPassword', ['Security']],
    ['POST', '/reset-password/{token}', 'AuthController@resetPassword', ['Security']],

    // Order routes
    ['GET', '/orders', 'OrderController@index', ['Security']],
    ['GET', '/orders/new', 'OrderController@new', ['Security']],
    ['POST', '/orders', 'OrderController@create', ['Security']],
    ['GET', '/orders/confirmation/{id}', 'OrderController@confirmation', ['Security']],
    ['PUT', '/orders/{id}/status', 'OrderController@updateStatus', ['Security']],
    ['POST', '/orders/{id}/status', 'OrderController@updateStatus', ['Security']], // Temporary

    // Admin routes
    ['GET', '/admin/login', 'AdminController@showLogin', ['Security']],
    ['POST', '/admin/login', 'AdminController@login', ['Security']],
    ['GET', '/admin/dashboard', 'AdminController@dashboard', ['Security']],
    ['GET', '/admin/employees', 'AdminController@getEmployees', ['Security']],
    ['POST', '/admin/employees', 'AdminController@createEmployee', ['Security']],
    ['DELETE', '/admin/employees/{id}', 'AdminController@disableEmployee', ['Security']],
    ['PATCH', '/admin/employees/{id}', 'AdminController@enableEmployee', ['Security']],
    ['GET', '/admin/revenue/menu', 'AdminController@revenueByMenu', ['Security']],
    ['GET', '/admin/menus', 'AdminController@getMenus', ['Security']],
    ['POST', '/admin/menus', 'AdminController@createMenu', ['Security']],
    ['PUT', '/admin/menus/{id}', 'AdminController@updateMenu', ['Security']],
    ['POST', '/admin/menus/{id}', 'AdminController@updateMenu', ['Security']], // Temporary
    ['DELETE', '/admin/menus/{id}', 'AdminController@deleteMenu', ['Security']],
    ['GET', '/admin/comments/pending', 'AdminController@getPendingComments', ['Security']],
    ['PUT', '/admin/comments/{id}/validate', 'AdminController@validateComment', ['Security']],
    ['POST', '/admin/comments/{id}/validate', 'AdminController@validateComment', ['Security']], // Temporary
    ['PUT', '/admin/comments/{id}/reject', 'AdminController@rejectComment', ['Security']],
    ['POST', '/admin/comments/{id}/reject', 'AdminController@rejectComment', ['Security']], // Temporary
];
