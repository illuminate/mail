<?php namespace Illuminate\Mail;

use Closure;
use Swift_Mailer;
use Swift_Message;
use Illuminate\Container;
use Illuminate\Support\Manager;

class Mailer {

	/**
	 * The view manager instance.
	 *
	 * @var Illuminate\Support\Manager
	 */
	protected $views;

    /**
     * The Swift Mailer instance.
     *
     * @var Swift_Mailer
     */
    protected $swift;

	/**
	 * The global from address and name.
	 *
	 * @var array
	 */
	protected $from;

	/**
	 * The IoC container instance.
	 *
	 * @var Illuminate\Container
	 */
	protected $container;

	/**
	 * Create a new Mailer instance.
	 *
	 * @param  Illuminate\Support\Manager  $views
	 * @param  Swift_Mailer  $swift
	 * @return void
	 */
	public function __construct(Manager $views, Swift_Mailer $swift)
	{
		$this->views = $views;
		$this->swift = $swift;
	}

	/**
	 * Set the global from address and name.
	 *
	 * @param  string  $address
	 * @param  string  $name
	 * @return void
	 */
	public function alwaysFrom($address, $name = null)
	{
		$this->from = compact('address', 'name');
	}

	/**
	 * Send a new message via a given view driver.
	 *
	 * @param  string  $driver
	 * @param  string  $view
	 * @param  array   $data
	 * @param  Closure|string  $callback
	 * @return void
	 */
	public function sendUsing($driver, $view, array $data = array(), $callback)
	{
		return $this->send($view, $data, $callback, $driver);
	}

	/**
	 * Send a new message using a view.
	 *
	 * @param  string   $view
	 * @param  array    $data
	 * @param  Closure|string  $callback
	 * @param  string   $driver
	 * @return void
	 */
	public function send($view, array $data = array(), $callback, $driver = null)
	{
		$data['message'] = $message = $this->createMessage();

		$this->callMessageBuilder($callback, $message);

		// Once we have retrieved the view content for the e-mail we will set the body
		// of this message using the HTML type, which will provide a simple wrapper
		// to creating view based emails that are able to receive arrays of data.
		$content = $this->getView($driver, $view, $data);

		$message->setBody($content, 'text/html');

		return $this->swift->send($message->getSwiftMessage());
	}

	/**
	 * Call the provided message builder.
	 *
	 * @param  Closure|string  $callback
	 * @param  Illuminate\Mail\Message  $message
	 * @return void
	 */
	protected function callMessageBuilder($callback, $message)
	{
		if ($callback instanceof Closure)
		{
			return call_user_func($callback, $message);
		}
		elseif (is_string($callback))
		{
			return $this->container[$callback]->mail($message);
		}

		throw new \InvalidArgumentException("Callback is not valid.");
	}

	/**
	 * Create a new message instance.
	 *
	 * @return Illuminate\Mail\Message
	 */
	protected function createMessage()
	{
		$message = new Message(new Swift_Message);

		// If a global from address has been specified we will set it on every message
		// instances so the developer does not have to repeat themselves every time
		// they create a new message. We will just go ahead and push the address.
		if (isset($this->from['address']))
		{
			$message->from($this->from['address'], $this->from['name']);
		}

		return $message;
	}

	/**
	 * Render the given view using the given driver.
	 *
	 * @param  string  $driver
	 * @param  string  $view
	 * @param  array   $data
	 * @return Illuminate\View\View
	 */
	protected function getView($driver, $view, $data)
	{
		return $this->views->driver($driver)->make($view, $data)->render();
	}

	/**
	 * Get the view environment instance.
	 *
	 * @return Illuminate\View\Environment
	 */
	public function getViewManager()
	{
		return $this->views;
	}

	/**
	 * Get the Swift Mailer instance.
	 *
	 * @return Swift_Mailer
	 */
	public function getSwiftMailer()
	{
		return $this->swift;
	}

	/**
	 * Set the Swift Mailer instance.
	 *
	 * @param  Swift_Mailer  $swift
	 * @return void
	 */
	public function setSwiftMailer($swift)
	{
		$this->swift = $swift;
	}

	/**
	 * Set the IoC container instance.
	 *
	 * @param  Illuminate\Container  $container
	 * @return void
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;
	}

}
