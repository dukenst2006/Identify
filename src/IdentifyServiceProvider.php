<?php namespace Regulus\Identify;

use Illuminate\Support\ServiceProvider;

class IdentifyServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->publishes([
			__DIR__.'/config/auth.php'        => config_path('auth.php'),
			__DIR__.'/config/auth.routes.php' => config_path('auth.routes.php'),
		]);

		$this->loadTranslationsFrom(__DIR__.'/lang', 'identify');

		$this->loadViewsFrom(__DIR__.'/views', 'identify');

		\Auth::extend('session', function($app, $name, array $config)
		{
			$model = $app['config']['auth.providers.users.model'];

			$provider = new IdentifyUserProvider($app['hash'], $model);

			return new Identify($name, $provider, $this->app['session.store'], $this->app['request']);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['Regulus\Identify\Identify'];
	}

}